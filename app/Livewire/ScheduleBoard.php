<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleBoard extends Component
{
    public $weekOffset = 0;
    public $weekView = 'week';       // week | month
    public $employeeFilter = null;
    public $showDatePicker = false;
    public $calendarYearMonth = null;

    public $showAddShift = false;
    public $editingShiftId = null;
    public $shiftEmployeeId = null;
    public array $shiftEmployeeIds = [];
    public $shiftAssignmentId = null;
    public array $shiftAssignmentIds = [];
    public $shiftTaskId = null;
    public array $shiftTaskIds = [];
    public $shiftDate = null;
    public $shiftDateFrom = null;
    public $shiftDateTo = null;
    public $excludeWeekends = false;
    public $selectedShiftDays = [];
    public $shiftStatus = 'draft';
    public $shiftIsPublished = false;

    public $shiftStart = '08:00';
    public $shiftEnd = '17:00';
    public $shiftStandardHours = 8.00;
    public $shiftDaysBreakdown = [];
    public $shiftBreakdownWeekPage = 0;
    public $shiftHasBreak = false;
    public $shiftBreakStart = '12:00';
    public $shiftBreakEnd = '12:30';
    public $shiftBreakMinutes = 0;
    public $shiftLatitude = null;
    public $shiftLongitude = null;
    public $shiftGeofenceRadius = 200;
    public $shiftLocationName = null;
    public $shiftLocationPresetId = null;
    public $shiftNotes = null;
    public $statusFilter = 'ongoing'; // 'all', 'ongoing', 'scheduled', 'in progress', 'completed', 'on hold', 'cancelled'

    // Copy / Clone Shift Modal State
    public $showCopyShift = false;
    public $copySourceShiftId = null;
    public $copySourceShift = null;
    public $copyTargetEmployeeId = null;
    public $copyDateFrom = null;
    public $copyDateTo = null;
    public $copyExcludeWeekends = true;
    public $copySelectedDays = [];

    public $showAddJob = false;
    public $newJobName = '';
    public $newJobStatus = 'active';

    public $showAddTask = false;
    public $newTaskName = '';
    public $newTaskStatus = 'active';

    public $showAddEmployee = false;
    public $newEmployeeName = '';
    public $newEmployeeEmail = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $user = auth()->user();
        if (!$user->canAccessSchedules()) {
            abort(403, 'Unauthorized access to Schedules.');
        }

        if ($user->isEmployee()) {
            $this->employeeFilter = (string) $user->id;
        }

        $today = Carbon::now()->toDateString();
        $this->shiftDateFrom = $today;
        $this->shiftDateTo   = $today;
        $this->shiftDate     = $today;
        $this->copyDateFrom  = $today;
        $this->copyDateTo    = $today;
        $this->generateSelectedDays();
    }

    public function canEditScheduleFor($employeeId): bool
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        if ($user->isManager()) {
            return $user->canManagerEdit('schedules') && $user->canManageEmployee($employeeId);
        }

        return (int) $user->id === (int) $employeeId && $user->canEdit('schedules');
    }

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function toggleDatePicker()
    {
        $this->showDatePicker = !$this->showDatePicker;
        if ($this->showDatePicker && !$this->calendarYearMonth) {
            $weekStartDay = CompanySetting::getWeekStartDay();
            $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
            $this->calendarYearMonth = $start->format('Y-m');
        }
    }

    public function closeDatePicker()
    {
        $this->showDatePicker = false;
    }

    public function selectPreset($offset)
    {
        $this->weekOffset = (int) $offset;
        $this->showDatePicker = false;
    }

    public function selectDateFromCalendar($dateStr)
    {
        $weekStartDay = CompanySetting::getWeekStartDay();
        $targetWeekStart = Carbon::parse($dateStr)->startOfWeek($weekStartDay);
        $currentWeekStart = Carbon::now()->startOfWeek($weekStartDay);
        $this->weekOffset = (int) $currentWeekStart->diffInWeeks($targetWeekStart, false);
        $this->showDatePicker = false;
    }

    public function prevMonth()
    {
        $cur = Carbon::parse(($this->calendarYearMonth ?: now()->format('Y-m')) . '-01');
        $this->calendarYearMonth = $cur->subMonth()->format('Y-m');
    }

    public function nextMonth()
    {
        $cur = Carbon::parse(($this->calendarYearMonth ?: now()->format('Y-m')) . '-01');
        $this->calendarYearMonth = $cur->addMonth()->format('Y-m');
    }

    public function getStatusBadgeClass(?string $status): string
    {
        return match(strtolower((string) $status)) {
            'active'      => 'bg-green-100 text-green-800 border border-green-200',
            'in progress' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            'scheduled'   => 'bg-blue-100 text-blue-800 border border-blue-200',
            'completed'   => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            'on hold'     => 'bg-amber-100 text-amber-800 border border-amber-200',
            'cancelled'   => 'bg-red-100 text-red-800 border border-red-200',
            default       => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
    }

    public function generateSelectedDays(): void
    {
        if (!$this->shiftDateFrom) {
            $this->shiftDateFrom = Carbon::now()->toDateString();
        }
        if (!$this->shiftDateTo) {
            $this->shiftDateTo = $this->shiftDateFrom;
        }

        try {
            $start = Carbon::parse($this->shiftDateFrom);
            $end = Carbon::parse($this->shiftDateTo);
            if ($start->gt($end)) {
                $end = $start->copy();
                $this->shiftDateTo = $this->shiftDateFrom;
            }

            $existingMap = [];
            foreach ($this->shiftDaysBreakdown as $d) {
                if (!empty($d['date'])) {
                    $existingMap[$d['date']] = $d;
                }
            }

            $breakdown = [];
            $cur = $start->copy();
            $count = 0;
            $stdHours = (float) ($this->shiftStandardHours ?: 8.00);

            while ($cur->lte($end) && $count < 60) {
                $dateStr = $cur->toDateString();
                $isWeekend = $cur->isWeekend();

                if (isset($existingMap[$dateStr])) {
                    $entry = $existingMap[$dateStr];
                    $entry['day_name'] = $cur->format('l');
                    $entry['formatted'] = $cur->format('D, M d');
                    $breakdown[] = $entry;
                } else {
                    $enabled = $this->excludeWeekends ? !$isWeekend : true;
                    $breakdown[] = [
                        'date'          => $dateStr,
                        'day_name'      => $cur->format('l'),
                        'formatted'     => $cur->format('D, M d'),
                        'is_weekend'    => $isWeekend,
                        'enabled'       => $enabled,
                        'start_time'    => $this->shiftStart ?: '08:00',
                        'end_time'      => $this->shiftEnd ?: '17:00',
                        'hours'         => $isWeekend ? 0.00 : $stdHours,
                        'has_break'     => (bool) $this->shiftHasBreak,
                        'break_start'   => $this->shiftBreakStart ?: '12:00',
                        'break_end'     => $this->shiftBreakEnd ?: '12:30',
                        'break_minutes' => (int) ($this->shiftBreakMinutes ?: 30),
                    ];
                }
                $cur->addDay();
                $count++;
            }

            $this->shiftDaysBreakdown = $breakdown;
            $this->syncSelectedShiftDays();
            $this->shiftBreakdownWeekPage = 0;
        } catch (\Throwable $e) {
            $this->shiftDaysBreakdown = [];
            $this->selectedShiftDays = [$this->shiftDateFrom];
        }
    }

    public function syncSelectedShiftDays(): void
    {
        $this->selectedShiftDays = collect($this->shiftDaysBreakdown)
            ->where('enabled', true)
            ->pluck('date')
            ->values()
            ->toArray();
    }

    public function updatedShiftDateFrom($val)
    {
        $this->shiftDate = $val;
        $this->generateSelectedDays();
    }

    public function updatedShiftDateTo($val)
    {
        $this->generateSelectedDays();
    }

    public function toggleExcludeWeekends()
    {
        $this->excludeWeekends = !$this->excludeWeekends;
        foreach ($this->shiftDaysBreakdown as &$d) {
            if ($d['is_weekend']) {
                $d['enabled'] = !$this->excludeWeekends;
            }
        }
        $this->syncSelectedShiftDays();
    }

    public function updatedExcludeWeekends($val)
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            if ($d['is_weekend']) {
                $d['enabled'] = !$val;
            }
        }
        $this->syncSelectedShiftDays();
    }

    public function toggleShiftDay($date)
    {
        foreach ($this->shiftDaysBreakdown as $idx => $d) {
            if ($d['date'] === $date) {
                $this->shiftDaysBreakdown[$idx]['enabled'] = !$this->shiftDaysBreakdown[$idx]['enabled'];
                if ($this->shiftDaysBreakdown[$idx]['enabled'] && (float)($this->shiftDaysBreakdown[$idx]['hours'] ?? 0) <= 0) {
                    $this->shiftDaysBreakdown[$idx]['hours'] = (float) ($this->shiftStandardHours ?: 8.00);
                }
                break;
            }
        }
        $this->syncSelectedShiftDays();
    }

    public function toggleShiftDayEnabled(int $index): void
    {
        if (isset($this->shiftDaysBreakdown[$index])) {
            $this->shiftDaysBreakdown[$index]['enabled'] = !$this->shiftDaysBreakdown[$index]['enabled'];
            if ($this->shiftDaysBreakdown[$index]['enabled'] && (float)($this->shiftDaysBreakdown[$index]['hours'] ?? 0) <= 0) {
                $this->shiftDaysBreakdown[$index]['hours'] = (float) ($this->shiftStandardHours ?: 8.00);
            }
            $this->syncSelectedShiftDays();
        }
    }

    public function toggleShiftDayBreak(int $index): void
    {
        if (isset($this->shiftDaysBreakdown[$index])) {
            $current = !empty($this->shiftDaysBreakdown[$index]['has_break']);
            $this->shiftDaysBreakdown[$index]['has_break'] = !$current;
            if (!$current) {
                if (empty($this->shiftDaysBreakdown[$index]['break_start'])) {
                    $this->shiftDaysBreakdown[$index]['break_start'] = $this->shiftBreakStart ?: '12:00';
                }
                if (empty($this->shiftDaysBreakdown[$index]['break_end'])) {
                    $this->shiftDaysBreakdown[$index]['break_end'] = $this->shiftBreakEnd ?: '12:30';
                }
                if (empty($this->shiftDaysBreakdown[$index]['break_minutes'])) {
                    $this->shiftDaysBreakdown[$index]['break_minutes'] = $this->shiftBreakMinutes ?: 30;
                }
            }
        }
    }

    public function recomputeBreakMinutes(): void
    {
        if ($this->shiftBreakStart && $this->shiftBreakEnd) {
            try {
                $s = Carbon::parse('2000-01-01 ' . $this->shiftBreakStart);
                $e = Carbon::parse('2000-01-01 ' . $this->shiftBreakEnd);
                if ($e->gt($s)) {
                    $this->shiftBreakMinutes = $s->diffInMinutes($e);
                }
            } catch (\Throwable $t) {}
        }
    }

    public function updatedShiftBreakStart($val)
    {
        $this->recomputeBreakMinutes();
        foreach ($this->shiftDaysBreakdown as &$day) {
            if (!empty($day['has_break'])) {
                $day['break_start'] = $val;
                $day['break_minutes'] = $this->shiftBreakMinutes;
            }
        }
    }

    public function updatedShiftBreakEnd($val)
    {
        $this->recomputeBreakMinutes();
        foreach ($this->shiftDaysBreakdown as &$day) {
            if (!empty($day['has_break'])) {
                $day['break_end'] = $val;
                $day['break_minutes'] = $this->shiftBreakMinutes;
            }
        }
    }

    public function updatedShiftHasBreak($val)
    {
        $this->recomputeBreakMinutes();
        foreach ($this->shiftDaysBreakdown as &$day) {
            $day['has_break'] = (bool) $val;
            if ($val) {
                $day['break_start'] = $this->shiftBreakStart ?: '12:00';
                $day['break_end'] = $this->shiftBreakEnd ?: '12:30';
                $day['break_minutes'] = (int) ($this->shiftBreakMinutes ?: 30);
            }
        }
    }

    public function updatedShiftStandardHours($val)
    {
        $stdHours = max(0.0, (float)$val);
        foreach ($this->shiftDaysBreakdown as &$d) {
            if (!empty($d['enabled'])) {
                $d['hours'] = $stdHours;
            }
        }
    }

    public function updatedShiftStart($val)
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['start_time'] = $val;
        }
    }

    public function updatedShiftEnd($val)
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['end_time'] = $val;
        }
    }

    public function updatedShiftDaysBreakdown($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if (($field === 'break_start' || $field === 'break_end') && isset($this->shiftDaysBreakdown[$index])) {
                $bStart = $this->shiftDaysBreakdown[$index]['break_start'] ?? null;
                $bEnd   = $this->shiftDaysBreakdown[$index]['break_end'] ?? null;
                if ($bStart && $bEnd) {
                    try {
                        $s = Carbon::parse('2000-01-01 ' . $bStart);
                        $e = Carbon::parse('2000-01-01 ' . $bEnd);
                        if ($e->gt($s)) {
                            $this->shiftDaysBreakdown[$index]['break_minutes'] = $s->diffInMinutes($e);
                        }
                    } catch (\Throwable $t) {}
                }
            }
        }
    }

    public function selectAllShiftDays()
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['enabled'] = true;
            if ((float)($d['hours'] ?? 0) <= 0) {
                $d['hours'] = (float)($this->shiftStandardHours ?: 8.00);
            }
        }
        $this->syncSelectedShiftDays();
    }

    public function deselectAllShiftDays()
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['enabled'] = false;
        }
        $this->syncSelectedShiftDays();
    }

    public function getShiftWeeklyBreakdownProperty(): array
    {
        if (empty($this->shiftDaysBreakdown)) {
            return [];
        }

        $weekStartDay = CompanySetting::getWeekStartDay();
        $weeks = [];

        foreach ($this->shiftDaysBreakdown as $index => $day) {
            $dt = Carbon::parse($day['date']);
            $diff = ($dt->dayOfWeek - $weekStartDay + 7) % 7;
            $weekStart = $dt->copy()->subDays($diff)->startOfDay();
            $weekEnd   = $weekStart->copy()->addDays(6)->endOfDay();
            $weekKey   = $weekStart->format('Y-m-d');

            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'start_date'  => $weekStart->format('Y-m-d'),
                    'end_date'    => $weekEnd->format('Y-m-d'),
                    'label'       => $weekStart->format('M d') . ' – ' . $weekEnd->format('M d, Y'),
                    'short_label' => $weekStart->format('n/j') . ' – ' . $weekEnd->format('n/j'),
                    'days'        => [],
                    'total_hours' => 0.0,
                    'total_break' => 0,
                ];
            }

            $dayWithIndex = $day;
            $dayWithIndex['original_index'] = $index;
            $weeks[$weekKey]['days'][] = $dayWithIndex;

            if (!empty($day['enabled'])) {
                $hrs = (float)($day['hours'] ?? 0);
                $weeks[$weekKey]['total_hours'] += $hrs;
                if (!empty($day['has_break'])) {
                    $weeks[$weekKey]['total_break'] += (int)($day['break_minutes'] ?? 0);
                }
            }
        }

        return array_values($weeks);
    }

    public function setShiftBreakdownWeekPage(int $page): void
    {
        $total = count($this->shiftWeeklyBreakdown);
        $this->shiftBreakdownWeekPage = max(0, min($page, max(0, $total - 1)));
    }

    public function prevShiftBreakdownWeek(): void
    {
        $this->setShiftBreakdownWeekPage($this->shiftBreakdownWeekPage - 1);
    }

    public function nextShiftBreakdownWeek(): void
    {
        $this->setShiftBreakdownWeekPage($this->shiftBreakdownWeekPage + 1);
    }

    public function getRangeDaysProperty(): array
    {
        $days = [];
        foreach ($this->shiftDaysBreakdown as $index => $d) {
            $days[] = [
                'index'      => $index,
                'date'       => $d['date'],
                'day_name'   => $d['day_name'],
                'formatted'  => $d['formatted'],
                'is_weekend' => $d['is_weekend'],
                'is_checked' => !empty($d['enabled']),
            ];
        }
        return $days;
    }

    public function openAddShift($employeeId = null, $date = null, $assignmentId = null, $taskId = null)
    {
        $user = auth()->user();
        if (!$user->canEditSchedules()) {
            $this->toast('You only have read-only access to Schedules.', 'error');
            return;
        }

        if ($employeeId && !$this->canEditScheduleFor($employeeId)) {
            $this->toast('You do not have permission to schedule this employee.', 'error');
            return;
        }

        $this->reset([
            'editingShiftId', 'shiftEmployeeId', 'shiftEmployeeIds', 'shiftAssignmentId', 'shiftAssignmentIds',
            'shiftTaskId', 'shiftTaskIds', 'shiftNotes', 'shiftLatitude', 'shiftLongitude', 'shiftLocationName',
            'shiftDaysBreakdown', 'shiftBreakdownWeekPage',
        ]);
        $targetDate                = $date ?: Carbon::now()->toDateString();
        $this->shiftDate           = $targetDate;
        $this->shiftDateFrom       = $targetDate;
        $this->shiftDateTo         = $targetDate;
        $this->shiftStatus         = 'draft';
        $this->shiftIsPublished    = false;
        $this->excludeWeekends     = false;

        $initialEmpId = $employeeId ?: ($user->isEmployee() ? $user->id : null);
        $this->shiftEmployeeId     = $initialEmpId;
        $this->shiftEmployeeIds    = $initialEmpId ? [(int)$initialEmpId] : [];

        $this->shiftAssignmentId   = $assignmentId ?: null;
        $this->shiftAssignmentIds  = $assignmentId ? [(int)$assignmentId] : [];
        $this->shiftTaskId         = $taskId ?: null;
        $this->shiftTaskIds        = $taskId ? [(int)$taskId] : [];

        $this->shiftStart          = '08:00';
        $this->shiftEnd            = '17:00';
        $this->shiftStandardHours  = 8.00;
        $this->shiftHasBreak       = false;
        $this->shiftBreakStart     = '12:00';
        $this->shiftBreakEnd       = '12:30';
        $this->shiftBreakMinutes   = 0;
        $this->shiftGeofenceRadius = 200;

        $this->generateSelectedDays();

        if ($this->shiftTaskId) {
            $task = Task::find($this->shiftTaskId);
            if ($task) {
                if ($task->latitude !== null && $task->longitude !== null) {
                    $this->shiftLatitude = $task->latitude;
                    $this->shiftLongitude = $task->longitude;
                }
                if ($task->geofence_radius) {
                    $this->shiftGeofenceRadius = $task->geofence_radius;
                }
                if (empty($this->shiftLocationName) && $task->name) {
                    $this->shiftLocationName = $task->name;
                }
            }
        }

        $this->showAddShift        = true;

        if ($this->shiftLatitude !== null && $this->shiftLongitude !== null) {
            $this->dispatch('coordinates-updated', [
                'lat'    => $this->shiftLatitude,
                'lng'    => $this->shiftLongitude,
                'radius' => $this->shiftGeofenceRadius,
            ]);
        }
    }

    public function selectAllEmployees()
    {
        $user = auth()->user();
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $q = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $q->whereIn('id', $accessibleIds);
        }
        $this->shiftEmployeeIds = $q->pluck('id')->map(fn($id) => (int)$id)->toArray();
        if (!empty($this->shiftEmployeeIds)) {
            $this->shiftEmployeeId = $this->shiftEmployeeIds[0];
        }
    }

    public function clearEmployees()
    {
        $this->shiftEmployeeIds = [];
        $this->shiftEmployeeId = null;
    }

    public function selectAllJobs()
    {
        $this->shiftAssignmentIds = Assignment::where('status', '!=', 'inactive')->ongoing()->pluck('id')->map(fn($id) => (int)$id)->toArray();
    }

    public function clearJobs()
    {
        $this->shiftAssignmentIds = [];
        $this->shiftTaskIds = [];
        $this->shiftAssignmentId = null;
        $this->shiftTaskId = null;
    }

    public function selectAllTasks()
    {
        $q = Task::where('status', '!=', 'inactive')->ongoing();
        if (!empty($this->shiftAssignmentIds)) {
            $q->whereIn('assignment_id', $this->shiftAssignmentIds);
        }
        $this->shiftTaskIds = $q->pluck('id')->map(fn($id) => (int)$id)->toArray();
    }

    public function clearTasks()
    {
        $this->shiftTaskIds = [];
        $this->shiftTaskId = null;
    }

    public function toggleJobTasks($jobId)
    {
        $jobTaskIds = Task::where('assignment_id', $jobId)
            ->where('status', '!=', 'inactive')
            ->ongoing()
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->toArray();

        $allSelected = count($jobTaskIds) > 0 && empty(array_diff($jobTaskIds, $this->shiftTaskIds));
        if ($allSelected) {
            $this->shiftTaskIds = array_values(array_diff($this->shiftTaskIds, $jobTaskIds));
        } else {
            $this->shiftTaskIds = array_values(array_unique(array_merge($this->shiftTaskIds, $jobTaskIds)));
        }
    }

    public function cancelShift()
    {
        $this->showAddShift = false;
        $this->reset([
            'editingShiftId', 'shiftEmployeeId', 'shiftEmployeeIds', 'shiftAssignmentId', 'shiftAssignmentIds',
            'shiftTaskId', 'shiftTaskIds', 'shiftNotes',
            'shiftLatitude', 'shiftLongitude', 'shiftLocationName', 'shiftLocationPresetId',
            'shiftDaysBreakdown', 'shiftBreakdownWeekPage',
        ]);
        $today = Carbon::now()->toDateString();
        $this->shiftDate           = $today;
        $this->shiftDateFrom       = $today;
        $this->shiftDateTo         = $today;
        $this->shiftStatus         = 'draft';
        $this->shiftIsPublished    = false;
        $this->excludeWeekends     = false;
        $this->shiftHasBreak       = false;
        $this->shiftBreakStart     = '12:00';
        $this->shiftBreakEnd       = '12:30';
        $this->shiftBreakMinutes   = 0;
        $this->generateSelectedDays();
    }

    public function updatedShiftAssignmentId($value)
    {
        $this->shiftTaskId = null;
        if ($value) {
            $this->shiftAssignmentIds = [(int)$value];
            $job = Assignment::find($value);
            if ($job) {
                if ($job->location_id) {
                    $this->shiftLocationPresetId = $job->location_id;
                    $this->updatedShiftLocationPresetId($job->location_id);
                } elseif ($job->latitude && $job->longitude) {
                    $this->shiftLatitude       = $job->latitude;
                    $this->shiftLongitude      = $job->longitude;
                    $this->shiftGeofenceRadius = $job->geofence_radius ?: 200;
                    $this->shiftLocationName   = $job->location ?: $job->name;
                    $this->dispatch('coordinates-updated', [
                        'lat'    => $this->shiftLatitude,
                        'lng'    => $this->shiftLongitude,
                        'radius' => $this->shiftGeofenceRadius,
                        'name'   => $this->shiftLocationName,
                    ]);
                }
            }
        }
    }

    public function updatedShiftLocationPresetId($value)
    {
        if ($value) {
            $preset = \App\Models\Location::find($value);
            if ($preset) {
                $this->shiftLocationName   = $preset->site_alias ?: $preset->name;
                $this->shiftLatitude       = $preset->latitude;
                $this->shiftLongitude      = $preset->longitude;
                $this->shiftGeofenceRadius = $preset->geofence_radius ?: 200;

                $this->dispatch('coordinates-updated', [
                    'lat'    => $this->shiftLatitude,
                    'lng'    => $this->shiftLongitude,
                    'radius' => $this->shiftGeofenceRadius,
                    'name'   => $this->shiftLocationName,
                ]);
            }
        } else {
            $this->shiftLocationName   = null;
            $this->shiftLatitude       = null;
            $this->shiftLongitude      = null;
            $this->shiftGeofenceRadius = 200;

            $this->dispatch('coordinates-updated', [
                'lat'    => null,
                'lng'    => null,
                'radius' => 200,
                'name'   => null,
            ]);
        }
    }

    public function updatedShiftEmployeeId($value)
    {
        if ($value) {
            $this->shiftEmployeeIds = [(int)$value];
        }
    }

    public function updatedShiftTaskId($value)
    {
        if ($value) {
            $this->shiftTaskIds = [(int)$value];
            $task = Task::find($value);
            if ($task) {
                if ($task->assignment_id) {
                    $this->shiftAssignmentId = $task->assignment_id;
                    $this->shiftAssignmentIds = [(int)$task->assignment_id];
                }
                if ($task->latitude !== null && $task->longitude !== null) {
                    $this->shiftLatitude = $task->latitude;
                    $this->shiftLongitude = $task->longitude;
                    $this->shiftGeofenceRadius = $task->geofence_radius ?: 200;
                    $this->shiftLocationName = $task->name;
                    $this->dispatch('coordinates-updated', [
                        'lat'    => $this->shiftLatitude,
                        'lng'    => $this->shiftLongitude,
                        'radius' => $this->shiftGeofenceRadius,
                    ]);
                }
            }
        }
    }

    public function saveShift($publish = false)
    {
        if (empty($this->shiftEmployeeIds) && $this->shiftEmployeeId) {
            $this->shiftEmployeeIds = [(int)$this->shiftEmployeeId];
        }
        if (empty($this->shiftAssignmentIds) && $this->shiftAssignmentId) {
            $this->shiftAssignmentIds = [(int)$this->shiftAssignmentId];
        }
        if (empty($this->shiftTaskIds) && $this->shiftTaskId) {
            $this->shiftTaskIds = [(int)$this->shiftTaskId];
        }

        if ($this->editingShiftId) {
            if (!$this->canEditScheduleFor($this->shiftEmployeeId)) {
                abort(403, 'Unauthorized to modify schedule for this employee.');
            }
        } else {
            $checkIds = !empty($this->shiftEmployeeIds) ? $this->shiftEmployeeIds : [$this->shiftEmployeeId ?: auth()->id()];
            foreach ($checkIds as $targetEmpId) {
                if (!$this->canEditScheduleFor($targetEmpId)) {
                    abort(403, 'Unauthorized to modify schedule for this employee.');
                }
            }
        }

        if (!$this->shiftDateFrom && $this->shiftDate) {
            $this->shiftDateFrom = $this->shiftDate;
        }
        if (!$this->shiftDateTo && $this->shiftDateFrom) {
            $this->shiftDateTo = $this->shiftDateFrom;
        }

        $rules = [
            'shiftDateFrom'       => 'required|date',
            'shiftDateTo'         => 'nullable|date|after_or_equal:shiftDateFrom',
            'shiftStart'          => 'required',
            'shiftEnd'            => 'required|after:shiftStart',
            'shiftStandardHours'  => 'required|numeric|min:0.5|max:24',
            'shiftLatitude'       => 'nullable|numeric|between:-90,90',
            'shiftLongitude'      => 'nullable|numeric|between:-180,180',
            'shiftGeofenceRadius' => 'nullable|integer|min:10|max:10000',
        ];

        if ($this->editingShiftId) {
            $rules['shiftEmployeeId']   = 'required|exists:users,id';
            $rules['shiftAssignmentId'] = 'required|exists:tbl_assignments,id';
            $rules['shiftTaskId']       = 'required|exists:tbl_tasks,id';
        } else {
            $rules['shiftEmployeeIds']    = 'required_without:shiftEmployeeId|array|min:1';
            $rules['shiftEmployeeIds.*']  = 'exists:users,id';
            $rules['shiftEmployeeId']     = 'nullable|exists:users,id';

            $rules['shiftAssignmentIds']   = 'required_without:shiftAssignmentId|array|min:1';
            $rules['shiftAssignmentIds.*'] = 'exists:tbl_assignments,id';
            $rules['shiftAssignmentId']    = 'nullable|exists:tbl_assignments,id';

            $rules['shiftTaskIds']         = 'required_without:shiftTaskId|array|min:1';
            $rules['shiftTaskIds.*']       = 'exists:tbl_tasks,id';
            $rules['shiftTaskId']          = 'nullable|exists:tbl_tasks,id';
        }

        $messages = [
            'shiftEmployeeId.required'            => 'Please select an employee.',
            'shiftEmployeeIds.required_without'   => 'Please select at least one employee.',
            'shiftEmployeeIds.min'                => 'Please select at least one employee.',
            'shiftAssignmentId.required'          => 'Please select a job.',
            'shiftAssignmentIds.required_without' => 'Please select at least one job.',
            'shiftAssignmentIds.min'              => 'Please select at least one job.',
            'shiftTaskId.required'                => 'Please select a task.',
            'shiftTaskIds.required_without'       => 'Please select at least one task.',
            'shiftTaskIds.min'                    => 'Please select at least one task.',
            'shiftDateFrom.required'              => 'Please select a date.',
            'shiftStart.required'                 => 'Please select a start time.',
            'shiftEnd.required'                   => 'Please select an end time.',
            'shiftEnd.after'                      => 'End time must be after start time.',
            'shiftStandardHours.required'         => 'Standard hours is required.',
        ];

        if (empty($this->shiftEmployeeIds) && !$this->shiftEmployeeId) {
            $this->addError('shiftEmployeeIds', 'Please select at least one employee.');
            return;
        }
        if (empty($this->shiftAssignmentIds) && !$this->shiftAssignmentId) {
            $this->addError('shiftAssignmentIds', 'Please select at least one job.');
            return;
        }
        if (empty($this->shiftTaskIds) && !$this->shiftTaskId) {
            $this->addError('shiftTaskIds', 'Please select at least one task.');
            return;
        }

        $this->validate($rules, $messages);

        if ($this->shiftAssignmentId) {
            $assignedJob = Assignment::ongoing()->find($this->shiftAssignmentId);
            if (!$assignedJob) {
                $this->toast('Only active/ongoing jobs can be assigned.', 'error');
                return;
            }
        }

        if ($this->shiftTaskId) {
            $assignedTask = Task::ongoing()->find($this->shiftTaskId);
            if (!$assignedTask) {
                $this->toast('Only active/ongoing tasks can be assigned.', 'error');
                return;
            }
        }

        $status = $publish ? 'published' : 'draft';
        $isPublished = ($status === 'published');

        if ($this->editingShiftId) {
            $schedule = Schedule::findOrFail($this->editingShiftId);
            $firstDay = !empty($this->shiftDaysBreakdown) ? $this->shiftDaysBreakdown[0] : null;
            $shiftHours = $firstDay ? (float)($firstDay['hours'] ?? $this->shiftStandardHours) : $this->shiftStandardHours;
            $startTime = $firstDay ? ($firstDay['start_time'] ?? $this->shiftStart) : $this->shiftStart;
            $endTime = $firstDay ? ($firstDay['end_time'] ?? $this->shiftEnd) : $this->shiftEnd;

            $schedule->update([
                'employee_id'     => $this->shiftEmployeeId,
                'assignment_id'   => $this->shiftAssignmentId,
                'task_id'         => $this->shiftTaskId,
                'shift_date'      => $this->shiftDateFrom ?: $schedule->shift_date,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'standard_hours'  => $shiftHours,
                'break_start'     => null,
                'break_end'       => null,
                'break_minutes'   => 0,
                'latitude'        => $this->shiftLatitude,
                'longitude'       => $this->shiftLongitude,
                'geofence_radius' => $this->shiftGeofenceRadius ?: 200,
                'location_name'   => $this->shiftLocationName,
                'notes'           => $this->shiftNotes,
                'status'          => $status,
                'is_published'    => $isPublished,
            ]);
            $this->toast($isPublished ? 'Shift updated and published.' : 'Shift updated as draft.');
        } else {
            // Target Employees
            $targetEmpIds = !empty($this->shiftEmployeeIds)
                ? array_values(array_unique(array_map('intval', $this->shiftEmployeeIds)))
                : ($this->shiftEmployeeId ? [(int)$this->shiftEmployeeId] : [auth()->id()]);

            // Target Job & Task Pairs (Both job and task are strictly required; no nulls allowed)
            $pairs = [];
            if (!empty($this->shiftTaskIds)) {
                $selectedTasks = Task::whereIn('id', $this->shiftTaskIds)->get();
                foreach ($selectedTasks as $t) {
                    $jobId = $t->assignment_id ?: $this->shiftAssignmentId;
                    if ($jobId) {
                        $pairs[] = [
                            'assignment_id' => (int)$jobId,
                            'task_id'       => (int)$t->id,
                        ];
                    }
                }
            } elseif ($this->shiftTaskId) {
                $t = Task::find($this->shiftTaskId);
                $jobId = $t?->assignment_id ?: $this->shiftAssignmentId;
                if ($jobId) {
                    $pairs[] = [
                        'assignment_id' => (int)$jobId,
                        'task_id'       => (int)$this->shiftTaskId,
                    ];
                }
            }

            if (empty($pairs)) {
                $this->addError('shiftTaskIds', 'Please select at least one task assigned to a job.');
                return;
            }

            $enabledDays = collect($this->shiftDaysBreakdown)->where('enabled', true)->values();
            if ($enabledDays->isEmpty()) {
                $targetDates = !empty($this->selectedShiftDays) ? $this->selectedShiftDays : [$this->shiftDateFrom];
                $enabledDays = collect($targetDates)->map(fn($d) => [
                    'date'          => $d,
                    'start_time'    => $this->shiftStart,
                    'end_time'      => $this->shiftEnd,
                    'hours'         => $this->shiftStandardHours,
                ]);
            }

            $createdCount = 0;
            $lastSchedule = null;

            foreach ($targetEmpIds as $empId) {
                foreach ($pairs as $pair) {
                    $lat = $this->shiftLatitude;
                    $lng = $this->shiftLongitude;
                    $rad = $this->shiftGeofenceRadius ?: 200;
                    $loc = $this->shiftLocationName;

                    if ($pair['task_id']) {
                        $t = Task::find($pair['task_id']);
                        if ($t && $t->latitude !== null && $t->longitude !== null) {
                            $lat = $t->latitude;
                            $lng = $t->longitude;
                            $rad = $t->geofence_radius ?: $rad;
                            if ($t->name) { $loc = $t->name; }
                        }
                    }

                    foreach ($enabledDays as $day) {
                        $bStart = !empty($day['has_break']) && !empty($day['break_start']) ? $day['break_start'] : null;
                        $bEnd   = !empty($day['has_break']) && !empty($day['break_end']) ? $day['break_end'] : null;
                        $bMins  = ($bStart && $bEnd) ? (int)($day['break_minutes'] ?? 0) : 0;

                        $lastSchedule = Schedule::create([
                            'employee_id'     => $empId,
                            'assignment_id'   => $pair['assignment_id'],
                            'task_id'         => $pair['task_id'],
                            'shift_date'      => $day['date'],
                            'status'          => $status,
                            'is_published'    => $isPublished,
                            'start_time'      => $day['start_time'] ?? $this->shiftStart,
                            'end_time'        => $day['end_time'] ?? $this->shiftEnd,
                            'standard_hours'  => $day['hours'] ?? $this->shiftStandardHours,
                            'break_start'     => $bStart,
                            'break_end'       => $bEnd,
                            'break_minutes'   => $bMins,
                            'latitude'        => $lat,
                            'longitude'       => $lng,
                            'geofence_radius' => $rad,
                            'location_name'   => $loc,
                            'notes'           => $this->shiftNotes,
                        ]);
                        $createdCount++;
                    }
                }
            }

            if ($isPublished) {
                if ($lastSchedule) {
                    \App\Services\TimeLogComplianceService::notifyScheduleAssigned($lastSchedule, Auth::id());
                }
                $this->toast($createdCount === 1 ? 'Shift added. Published successfully for employee time logging.' : "{$createdCount} shift(s) published successfully for employee time logging.", 'success');
            } else {
                $this->toast($createdCount === 1 ? 'Shift added. Saved as draft (drafted by default).' : "{$createdCount} shift(s) saved as draft (drafted by default).", 'info');
            }
        }

        $this->cancelShift();
    }

    public function openEditShift($id)
    {
        $s = Schedule::find($id);
        if (!$s) return;

        if (!$this->canEditScheduleFor($s->employee_id)) {
            $this->toast('You do not have permission to edit this shift.', 'error');
            return;
        }

        $this->editingShiftId      = $s->id;
        $this->shiftEmployeeId     = $s->employee_id;
        $this->shiftAssignmentId   = $s->assignment_id;
        $this->shiftTaskId         = $s->task_id;
        $this->shiftDate           = $s->shift_date?->toDateString();
        $this->shiftDateFrom       = $this->shiftDate;
        $this->shiftDateTo         = $this->shiftDate;
        $this->selectedShiftDays   = [$this->shiftDate];
        $this->shiftStatus         = $s->status ?: ($s->is_published ? 'published' : 'draft');
        $this->shiftIsPublished    = (bool) $s->is_published;
        $this->shiftStart          = substr($s->start_time, 0, 5);
        $this->shiftEnd            = substr($s->end_time, 0, 5);
        $this->shiftStandardHours  = (float) ($s->standard_hours ?: 8.00);
        $this->shiftBreakStart     = $s->break_start ? substr($s->break_start, 0, 5) : '12:00';
        $this->shiftBreakEnd       = $s->break_end ? substr($s->break_end, 0, 5) : '12:30';
        $this->shiftBreakMinutes   = (int) ($s->break_minutes ?: 30);
        $this->shiftHasBreak       = !empty($s->break_start) || !empty($s->break_minutes);
        $this->shiftLatitude       = $s->latitude;
        $this->shiftLongitude      = $s->longitude;
        $this->shiftGeofenceRadius = $s->geofence_radius ?: 200;
        $this->shiftLocationName   = $s->location_name;
        $this->shiftNotes          = $s->notes;

        $this->shiftDaysBreakdown = [[
            'date'          => $this->shiftDate,
            'day_name'      => $s->shift_date?->format('l') ?? '',
            'formatted'     => $s->shift_date?->format('D, M d') ?? '',
            'is_weekend'    => $s->shift_date?->isWeekend() ?? false,
            'enabled'       => true,
            'start_time'    => $this->shiftStart,
            'end_time'      => $this->shiftEnd,
            'hours'         => $this->shiftStandardHours,
            'has_break'     => $this->shiftHasBreak,
            'break_start'   => $this->shiftBreakStart,
            'break_end'     => $this->shiftBreakEnd,
            'break_minutes' => $this->shiftBreakMinutes,
        ]];
        $this->shiftBreakdownWeekPage = 0;

        $preset = \App\Models\Location::where('site_alias', $s->location_name)
            ->orWhere('name', $s->location_name)
            ->first();
        $this->shiftLocationPresetId = $preset?->id;

        if (($this->shiftLatitude === null || $this->shiftLongitude === null) && $this->shiftTaskId) {
            $task = Task::find($this->shiftTaskId);
            if ($task && $task->latitude !== null && $task->longitude !== null) {
                $this->shiftLatitude = $task->latitude;
                $this->shiftLongitude = $task->longitude;
                if ($task->geofence_radius) {
                    $this->shiftGeofenceRadius = $task->geofence_radius;
                }
                if (empty($this->shiftLocationName) && $task->name) {
                    $this->shiftLocationName = $task->name;
                }
            }
        }

        $this->showAddShift        = true;

        $this->dispatch('coordinates-updated', [
            'lat'    => $this->shiftLatitude,
            'lng'    => $this->shiftLongitude,
            'radius' => $this->shiftGeofenceRadius,
        ]);
    }

    public function deleteShift($id)
    {
        $s = Schedule::findOrFail($id);

        if (!$this->canEditScheduleFor($s->employee_id)) {
            abort(403, 'Unauthorized to delete this shift.');
        }

        $s->delete();
        $this->toast('Shift deleted.');
    }

    // ==========================================
    // COPY / CLONE SHIFT METHODS
    // ==========================================

    public function openCopyShift($shiftId)
    {
        $source = Schedule::with(['assignment', 'task', 'employee'])->findOrFail($shiftId);
        $this->copySourceShiftId    = $source->id;
        $this->copySourceShift      = $source;
        $this->copyTargetEmployeeId = $source->employee_id;
        $today                      = Carbon::now()->toDateString();
        $this->copyDateFrom         = $today;
        $this->copyDateTo           = $today;
        $this->copyExcludeWeekends  = true;
        $this->generateCopySelectedDays();
        $this->showCopyShift        = true;
    }

    public function closeCopyShift()
    {
        $this->showCopyShift        = false;
        $this->copySourceShiftId    = null;
        $this->copySourceShift      = null;
    }

    public function generateCopySelectedDays(): void
    {
        $this->copySelectedDays = [];
        if (!$this->copyDateFrom) $this->copyDateFrom = Carbon::now()->toDateString();
        if (!$this->copyDateTo)   $this->copyDateTo   = $this->copyDateFrom;

        try {
            $start = Carbon::parse($this->copyDateFrom);
            $end = Carbon::parse($this->copyDateTo);
            if ($start->gt($end)) {
                $end = $start->copy();
                $this->copyDateTo = $this->copyDateFrom;
            }

            $cur = $start->copy();
            $count = 0;
            while ($cur->lte($end) && $count < 60) {
                $isWeekend = $cur->isWeekend();
                $dateStr = $cur->toDateString();
                if (!$this->copyExcludeWeekends || !$isWeekend) {
                    $this->copySelectedDays[] = $dateStr;
                }
                $cur->addDay();
                $count++;
            }
        } catch (\Throwable $e) {
            $this->copySelectedDays = [$this->copyDateFrom];
        }
    }

    public function updatedCopyDateFrom($val)
    {
        $this->generateCopySelectedDays();
    }

    public function updatedCopyDateTo($val)
    {
        $this->generateCopySelectedDays();
    }

    public function toggleCopyExcludeWeekends()
    {
        $this->copyExcludeWeekends = !$this->copyExcludeWeekends;
        $this->generateCopySelectedDays();
    }

    public function toggleCopyDay($date)
    {
        if (in_array($date, $this->copySelectedDays, true)) {
            $this->copySelectedDays = array_values(array_diff($this->copySelectedDays, [$date]));
        } else {
            $this->copySelectedDays[] = $date;
        }
    }

    public function selectAllCopyDays()
    {
        $this->copySelectedDays = [];
        $start = Carbon::parse($this->copyDateFrom ?: now()->toDateString());
        $end = Carbon::parse($this->copyDateTo ?: $this->copyDateFrom);
        $cur = $start->copy();
        while ($cur->lte($end)) {
            $this->copySelectedDays[] = $cur->toDateString();
            $cur->addDay();
        }
    }

    public function deselectAllCopyDays()
    {
        $this->copySelectedDays = [];
    }

    public function getCopyRangeDaysProperty(): array
    {
        $days = [];
        if (!$this->copyDateFrom) return $days;
        try {
            $start = Carbon::parse($this->copyDateFrom);
            $end = Carbon::parse($this->copyDateTo ?: $this->copyDateFrom);
            if ($start->gt($end)) $end = $start->copy();

            $cur = $start->copy();
            $count = 0;
            while ($cur->lte($end) && $count < 60) {
                $dateStr = $cur->toDateString();
                $days[] = [
                    'date'       => $dateStr,
                    'day_name'   => $cur->format('l'),
                    'formatted'  => $cur->format('D, M d'),
                    'is_weekend' => $cur->isWeekend(),
                    'is_checked' => in_array($dateStr, $this->copySelectedDays, true),
                ];
                $cur->addDay();
                $count++;
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $days;
    }

    public function executeCopyShift($publish = false)
    {
        if (!$this->copySourceShiftId) return;
        $source = Schedule::findOrFail($this->copySourceShiftId);

        if (!$this->canEditScheduleFor($this->copyTargetEmployeeId)) {
            abort(403, 'Unauthorized to schedule for this employee.');
        }

        if (empty($this->copySelectedDays)) {
            $this->toast('Please select at least one target day to clone shift.', 'error');
            return;
        }

        $status = $publish ? 'published' : 'draft';
        $isPublished = ($status === 'published');
        $clonedCount = 0;

        foreach ($this->copySelectedDays as $targetDate) {
            Schedule::create([
                'employee_id'     => $this->copyTargetEmployeeId ?: $source->employee_id,
                'assignment_id'   => $source->assignment_id,
                'task_id'         => $source->task_id,
                'shift_date'      => $targetDate,
                'start_time'      => $source->start_time,
                'end_time'        => $source->end_time,
                'standard_hours'  => $source->standard_hours ?: 8.00,
                'break_start'     => $source->break_start,
                'break_end'       => $source->break_end,
                'break_minutes'   => $source->break_minutes,
                'latitude'        => $source->latitude,
                'longitude'       => $source->longitude,
                'geofence_radius' => $source->geofence_radius ?: 200,
                'location_name'   => $source->location_name,
                'notes'           => $source->notes,
                'status'          => $status,
                'is_published'    => $isPublished,
            ]);
            $clonedCount++;
        }

        $label = $isPublished ? 'published' : 'drafted';
        $this->toast("{$clonedCount} shift(s) successfully cloned as {$label}.", 'success');
        $this->closeCopyShift();
    }

    public function publishWeek()
    {
        $user = auth()->user();
        if (!$user->canEditSchedules()) {
            abort(403, 'Unauthorized to publish schedules.');
        }

        $weekStartDay = CompanySetting::getWeekStartDay();
        $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
        $end   = $start->copy()->addDays(6)->endOfDay();

        $count = Schedule::whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('status', 'draft')->orWhere('is_published', false);
            })
            ->update([
                'status'       => 'published',
                'is_published' => true,
            ]);

        $this->toast("{$count} draft shift(s) published for this week. Employees can now view shifts and log time.", 'success');
    }

    public function revertWeekToDraft()
    {
        $user = auth()->user();
        if (!$user->canEditSchedules()) {
            abort(403, 'Unauthorized to revert schedules.');
        }

        $weekStartDay = CompanySetting::getWeekStartDay();
        $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
        $end   = $start->copy()->addDays(6)->endOfDay();

        $count = Schedule::whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->update([
                'status'       => 'draft',
                'is_published' => false,
            ]);

        $this->toast("{$count} published shift(s) reverted back to draft for this week.", 'info');
    }

    public function publishShift($id)
    {
        $user = auth()->user();
        $s = Schedule::findOrFail($id);
        if (!$this->canEditScheduleFor($s->employee_id)) {
            abort(403, 'Unauthorized to publish this shift.');
        }

        $s->update([
            'status'       => 'published',
            'is_published' => true,
        ]);

        \App\Services\TimeLogComplianceService::notifyScheduleAssigned($s, Auth::id());
        $this->toast("Shift published for employee time logging.", 'success');
    }

    public function revertShiftToDraft($id)
    {
        $user = auth()->user();
        $s = Schedule::findOrFail($id);
        if (!$this->canEditScheduleFor($s->employee_id)) {
            abort(403, 'Unauthorized to revert this shift.');
        }

        $s->update([
            'status'       => 'draft',
            'is_published' => false,
        ]);

        $this->toast("Shift reverted back to draft.", 'info');
    }

    public function openAddJob()
    {
        return redirect()->route('work.jobs');
    }

    public function saveJob()
    {
        if (!auth()->user()->canEdit('jobs')) {
            abort(403, 'Unauthorized to create jobs.');
        }

        $this->validate([
            'newJobName'   => 'required|string|max:100',
            'newJobStatus' => 'required|in:scheduled,in progress,completed,on hold,cancelled,active,inactive',
        ]);

        Assignment::create([
            'name'   => $this->newJobName,
            'status' => $this->newJobStatus ?: 'active',
        ]);

        $this->newJobName = '';
        $this->newJobStatus = 'active';
        $this->showAddJob = false;
        $this->toast('Job added.');
    }

    public function cancelJob()
    {
        $this->showAddJob = false;
        $this->newJobName = '';
        $this->newJobStatus = 'active';
    }

    public function openAddTask()
    {
        return redirect()->route('work.tasks');
    }

    public function saveTask()
    {
        if (!auth()->user()->canEdit('tasks')) {
            abort(403, 'Unauthorized to create tasks.');
        }

        $this->validate([
            'newTaskName'   => 'required|string|max:100',
            'newTaskStatus' => 'required|in:scheduled,in progress,completed,on hold,cancelled,active,inactive',
        ]);

        Task::create([
            'name'   => $this->newTaskName,
            'status' => $this->newTaskStatus ?: 'active',
        ]);

        $this->newTaskName = '';
        $this->newTaskStatus = 'active';
        $this->showAddTask = false;
        $this->toast('Task added.');
    }

    public function cancelTask()
    {
        $this->showAddTask = false;
        $this->newTaskName = '';
        $this->newTaskStatus = 'active';
    }

    public function openAddEmployee()
    {
        return redirect()->route('admin.employees');
    }

    public function saveEmployee()
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Only administrators and HR managers can add employees.');
        }

        $this->validate([
            'newEmployeeName'  => 'required|string|max:100',
            'newEmployeeEmail' => 'required|email|unique:users,email',
        ]);

        User::create([
            'name'     => $this->newEmployeeName,
            'email'    => $this->newEmployeeEmail,
            'password' => bcrypt(str()->random(16)),
        ]);

        $this->showAddEmployee = false;
        $this->toast('Employee added.');
    }

    public function cancelEmployee()
    {
        $this->showAddEmployee = false;
        $this->newEmployeeName = '';
        $this->newEmployeeEmail = '';
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $weekStartDay = CompanySetting::getWeekStartDay();
        $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
        $end   = $start->copy()->addDays(6)->endOfDay();

        $user = auth()->user();
        $accessibleIds = $user->getAccessibleEmployeeIds();

        $employeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }

        if ($this->employeeFilter) {
            if ($accessibleIds === null || in_array((int) $this->employeeFilter, $accessibleIds, true)) {
                $employeesQuery->where('id', $this->employeeFilter);
            }
        }

        $employees = $employeesQuery->get();

        $accessibleEmployeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $accessibleEmployeesQuery->whereIn('id', $accessibleIds);
        }
        $filterEmployees = $accessibleEmployeesQuery->get();

        $jobsQuery = Assignment::query();
        $tasksQuery = Task::query();

        if ($this->statusFilter === 'ongoing') {
            $jobsQuery->ongoing();
            $tasksQuery->ongoing();
        } elseif ($this->statusFilter && $this->statusFilter !== 'all') {
            $jobsQuery->where('status', $this->statusFilter);
            $tasksQuery->where('status', $this->statusFilter);
        }

        $jobs = $jobsQuery->orderBy('name')->get();
        $tasks = $tasksQuery->orderBy('name')->get();

        // For modal assignment: strictly active/ongoing jobs and tasks
        $assignableJobs  = Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get();
        $assignableTasks = Task::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get();

        $rawSchedulesQuery = Schedule::with(['employee', 'assignment', 'task'])
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDoesntHave('assignment', fn($q) => $q->where('status', 'inactive'))
            ->whereDoesntHave('task', fn($q) => $q->where('status', 'inactive'));

        // Employee visibility guard: Standard employees can ONLY see published entries!
        if ($user->isEmployee() && !$user->canAccessAdmin()) {
            $rawSchedulesQuery->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            });
        }

        $rawSchedules = $rawSchedulesQuery->get();

        // Group rows by Job + Task and Employee
        $groupedRows = collect();
        $groupedByJobTask = $rawSchedules->groupBy(function ($s) {
            return ($s->assignment_id ?: '0') . '_' . ($s->task_id ?: '0');
        });

        foreach ($groupedByJobTask as $groupKey => $groupShifts) {
            $sample = $groupShifts->first();
            $job = $sample->assignment;
            $task = $sample->task;

            // Group by employee within this Job + Task group
            $byEmployee = $groupShifts->groupBy('employee_id');

            foreach ($byEmployee as $empId => $empShifts) {
                $emp = $employees->firstWhere('id', $empId) ?? $empShifts->first()->employee;
                if (!$emp) continue;

                $mins = 0;
                foreach ($empShifts as $s) {
                    $mins += Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time));
                }

                $shiftsByDay = $empShifts->groupBy(fn ($s) => $s->shift_date->toDateString());

                $groupedRows->push([
                    'group_key'   => $groupKey,
                    'job_id'      => $sample->assignment_id,
                    'job'         => $job,
                    'task_id'     => $sample->task_id,
                    'task'        => $task,
                    'employee_id' => $emp->id,
                    'employee'    => $emp,
                    'shifts'      => $shiftsByDay,
                    'total_hours' => round($mins / 60, 1),
                ]);
            }
        }

        // Include employees who don't have any shifts yet this week
        $scheduledEmpIds = $groupedRows->pluck('employee_id')->unique();
        foreach ($employees as $emp) {
            if (!$scheduledEmpIds->contains($emp->id)) {
                $groupedRows->push([
                    'group_key'   => '0_0',
                    'job_id'      => null,
                    'job'         => null,
                    'task_id'     => null,
                    'task'        => null,
                    'employee_id' => $emp->id,
                    'employee'    => $emp,
                    'shifts'      => collect(),
                    'total_hours' => 0.0,
                ]);
            }
        }

        // Sort grouped rows: First by Job name (or 'ZZZ' for unassigned), then Task name, then Employee name
        $groupedRows = $groupedRows->sortBy([
            fn ($a, $b) => strcmp($a['job']?->name ?? 'ZZZ', $b['job']?->name ?? 'ZZZ'),
            fn ($a, $b) => strcmp($a['task']?->name ?? 'ZZZ', $b['task']?->name ?? 'ZZZ'),
            fn ($a, $b) => strcmp($a['employee']->name, $b['employee']->name),
        ])->values();

        // Legacy format for backwards compatibility
        $schedules = $rawSchedules
            ->groupBy(fn ($s) => $s->shift_date->toDateString())
            ->map(fn ($day) => $day->groupBy('employee_id'));

        // Per-employee week hours (from schedules)
        $employeeHours = [];
        foreach ($employees as $e) {
            $mins = 0;
            foreach ($rawSchedules->where('employee_id', $e->id) as $s) {
                $mins += Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time));
            }
            $employeeHours[$e->id] = round($mins / 60, 1);
        }

        // Mini calendar data for popup
        $calDate = Carbon::parse(($this->calendarYearMonth ?: $start->format('Y-m')) . '-01');
        $calMonthTitle = strtoupper($calDate->format('F Y'));
        $calStart = $calDate->copy()->startOfWeek($weekStartDay);
        $calEnd = $calDate->copy()->endOfMonth()->startOfWeek($weekStartDay)->addDays(6)->endOfDay();

        $calendarWeeks = [];
        $cur = $calStart->copy();
        while ($cur->lte($calEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $cur->toDateString();
                $week[] = [
                    'date'             => $dateStr,
                    'day'              => $cur->day,
                    'is_current_month' => $cur->month === $calDate->month,
                    'is_selected'      => $cur->betweenIncluded($start, $end),
                    'is_today'         => $cur->isToday(),
                ];
                $cur->addDay();
            }
            $calendarWeeks[] = $week;
        }

        // Count draft shifts in the week
        $draftShiftsCount = Schedule::whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('status', 'draft')->orWhere('is_published', false);
            })
            ->count();

        // Count published shifts in the week
        $publishedShiftsCount = Schedule::whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->count();

        return view('livewire.schedule-board', [
            'startOfWeek'          => $start,
            'endOfWeek'            => $end,
            'days'                 => collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i)),
            'calendarWeeks'        => $calendarWeeks,
            'calMonthTitle'        => $calMonthTitle,
            'employees'            => $employees,
            'filterEmployees'      => $filterEmployees,
            'jobs'                 => $jobs,
            'tasks'                => $tasks,
            'assignableJobs'       => $assignableJobs,
            'assignableTasks'      => $assignableTasks,
            'allStatuses'          => Assignment::allStatuses(),
            'groupedRows'          => $groupedRows,
            'locationPresets'      => \App\Models\Location::where('status', '!=', 'inactive')->orderBy('name')->get(),
            'schedules'            => $schedules,
            'employeeHours'        => $employeeHours,
            'todayKey'             => Carbon::now()->toDateString(),
            'rangeDays'            => $this->rangeDays,
            'copyRangeDays'        => $this->copyRangeDays,
            'draftShiftsCount'     => $draftShiftsCount,
            'publishedShiftsCount' => $publishedShiftsCount,
        ]);
    }
}
