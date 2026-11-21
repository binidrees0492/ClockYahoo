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

class ScheduleCalendar extends Component
{
    public $monthOffset = 0;
    public $viewMode = 'month';
    public $selectedEmployeeId = null;

    // View Details Modal
    public $selectedSchedule = null;

    // Add / Edit Shift Modal
    public $showShiftModal = false;
    public $editingScheduleId = null;
    public $shiftEmployeeId = null;
    public $shiftAssignmentId = null;
    public $shiftTaskId = null;
    public $shiftMode = 'single'; // 'single' | 'range'
    public $shiftDate = null;
    public $shiftEndDate = null;
    public $shiftDays = ['1', '2', '3', '4', '5']; // ISO 1=Mon .. 7=Sun
    public $startTime = '08:00';
    public $endTime = '17:00';
    public $standardHours = 8.00;
    public $latitude = null;
    public $longitude = null;
    public $geofenceRadius = 200;
    public $locationName = null;
    public $locationPresetId = null;
    public $shiftNotes = '';
    public $shiftStatus = 'published';
    public $isMySchedule = false;

    // Multi-Employee, Multi-Job, Multi-Task Properties
    public array $shiftEmployeeIds = [];
    public array $shiftAssignmentIds = [];
    public array $shiftTaskIds = [];

    // Breakdown Properties for Range Bulk Adding
    public $shiftDaysBreakdown = [];
    public $shiftBreakdownWeekPage = 0;
    public $shiftHasBreak = false;
    public $shiftBreakStart = '12:00';
    public $shiftBreakEnd = '12:30';
    public $shiftBreakMinutes = 30;
    public $excludeWeekends = false;

    // Property aliases matching ScheduleBoard for identical modal structure
    public $showAddShift = false;
    public $editingShiftId = null;
    public $employeeSearch = '';
    public $shiftDateFrom = null;
    public $shiftDateTo = null;
    public $shiftStart = '08:00';
    public $shiftEnd = '17:00';
    public $shiftStandardHours = 8.00;
    public $shiftLatitude = null;
    public $shiftLongitude = null;
    public $shiftGeofenceRadius = 200;
    public $shiftLocationName = null;
    public $shiftLocationPresetId = null;
    public $selectedShiftDays = [];

    public function mount($selectedEmployeeId = null, $isMySchedule = false)
    {
        $this->isMySchedule = (bool) $isMySchedule;
        $this->selectedEmployeeId = $this->isMySchedule ? Auth::id() : ($selectedEmployeeId ?: Auth::id());
    }

    public function previousMonth() { $this->monthOffset--; }
    public function nextMonth()     { $this->monthOffset++; }
    public function thisMonth()     { $this->monthOffset = 0; }

    public function setView($mode)
    {
        $this->viewMode = $mode;
    }

    public function setEmployee($empId)
    {
        if ($this->isMySchedule) {
            $this->selectedEmployeeId = Auth::id();
            return;
        }

        if ($empId === '' || $empId === 'all') {
            $this->selectedEmployeeId = 'all';
            return;
        }

        $user = Auth::user();
        if ($empId != Auth::id() && !$user->canManageEmployee($empId) && !$user->canAccessSchedules()) {
            return;
        }
        $this->selectedEmployeeId = (int)$empId;
    }

    public function viewSchedule($scheduleId)
    {
        $this->selectedSchedule = Schedule::with(['assignment', 'task', 'employee'])->find($scheduleId);
    }

    public function closeScheduleModal()
    {
        $this->selectedSchedule = null;
    }

    public function toggleEmployeeSelection($id)
    {
        $id = (int)$id;
        if ($this->editingScheduleId || $this->editingShiftId) {
            $this->shiftEmployeeId = $id;
            $this->shiftEmployeeIds = [$id];
            return;
        }

        if (in_array($id, $this->shiftEmployeeIds, true)) {
            $this->shiftEmployeeIds = array_values(array_diff($this->shiftEmployeeIds, [$id]));
        } else {
            $this->shiftEmployeeIds[] = $id;
        }
        $this->shiftEmployeeId = $this->shiftEmployeeIds[0] ?? null;
    }

    public function selectAllEmployees()
    {
        $user = Auth::user();
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $q = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $q->whereIn('id', $accessibleIds);
        }
        if (!empty($this->employeeSearch)) {
            $search = '%' . trim($this->employeeSearch) . '%';
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', $search)
                   ->orWhere('email', 'like', $search)
                   ->orWhere('role', 'like', $search);
            });
        }
        $newIds = $q->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->shiftEmployeeIds = array_values(array_unique(array_merge($this->shiftEmployeeIds, $newIds)));
        if (!empty($this->shiftEmployeeIds) && !$this->shiftEmployeeId) {
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
        if (count($this->shiftAssignmentIds) === 1) {
            $this->shiftAssignmentId = $this->shiftAssignmentIds[0];
        }
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
        $taskIds = $q->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->shiftTaskIds = $taskIds;

        // Auto-select parent jobs of these child tasks
        $parentJobIds = Task::whereIn('id', $taskIds)
            ->whereNotNull('assignment_id')
            ->pluck('assignment_id')
            ->map(fn($id) => (int)$id)
            ->unique()
            ->toArray();
        $this->shiftAssignmentIds = array_values(array_unique(array_merge($this->shiftAssignmentIds, $parentJobIds)));
        if (count($this->shiftAssignmentIds) === 1) {
            $this->shiftAssignmentId = $this->shiftAssignmentIds[0];
        }
        if (count($taskIds) === 1) {
            $this->shiftTaskId = $taskIds[0];
        }
    }

    public function clearTasks()
    {
        $this->shiftTaskIds = [];
        $this->shiftTaskId = null;
    }

    public function toggleJobTasks($jobId)
    {
        $jobId = (int)$jobId;
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
            // Interdependent: Ensure the parent job is selected
            if (!in_array($jobId, $this->shiftAssignmentIds, true)) {
                $this->shiftAssignmentIds[] = $jobId;
            }
            $this->shiftAssignmentId = $jobId;
        }
    }

    public function openAddShift($date = null, $employeeId = null, $assignmentId = null, $taskId = null)
    {
        $user = Auth::user();
        if (!$user->canEditSchedules() && !$user->canAccessAdmin() && !$user->isManager()) {
            $this->toast('You do not have permission to add scheduled shifts.', 'error');
            return;
        }

        $targetDate = $date ?: Carbon::now()->toDateString();
        $this->editingScheduleId = null;
        $this->editingShiftId = null;
        $this->employeeSearch = '';

        $initialEmpId = $employeeId ?: ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all' ? $this->selectedEmployeeId : Auth::id());
        $this->shiftEmployeeId   = $initialEmpId;
        $this->shiftEmployeeIds  = $initialEmpId ? [(int)$initialEmpId] : [];

        $this->shiftAssignmentId = $assignmentId ?: null;
        $this->shiftAssignmentIds= $assignmentId ? [(int)$assignmentId] : [];
        $this->shiftTaskId       = $taskId ?: null;
        $this->shiftTaskIds      = $taskId ? [(int)$taskId] : [];

        $this->shiftMode         = 'single';
        $this->shiftDate         = $targetDate;
        $this->shiftEndDate      = $targetDate;
        $this->shiftDateFrom     = $targetDate;
        $this->shiftDateTo       = $targetDate;
        $this->shiftDays         = ['1', '2', '3', '4', '5'];
        $this->startTime         = '08:00';
        $this->endTime           = '17:00';
        $this->shiftStart        = '08:00';
        $this->shiftEnd          = '17:00';
        $this->standardHours     = 8.00;
        $this->shiftStandardHours= 8.00;
        $this->shiftHasBreak     = false;
        $this->shiftBreakStart   = '12:00';
        $this->shiftBreakEnd     = '12:30';
        $this->shiftBreakMinutes = 0;
        $this->excludeWeekends   = false;
        $this->latitude          = null;
        $this->longitude         = null;
        $this->geofenceRadius    = 200;
        $this->locationName      = null;
        $this->locationPresetId  = null;
        $this->shiftLatitude     = null;
        $this->shiftLongitude    = null;
        $this->shiftGeofenceRadius = 200;
        $this->shiftLocationName = null;
        $this->shiftLocationPresetId = null;
        $this->shiftNotes        = '';
        $this->shiftStatus       = 'draft';
        $this->shiftBreakdownWeekPage = 0;

        $this->generateSelectedDays();
        $this->showShiftModal    = true;
        $this->showAddShift      = true;
    }

    public function updatedLocationPresetId($value)
    {
        $this->shiftLocationPresetId = $value;
        if ($value) {
            $preset = \App\Models\Location::find($value);
            if ($preset) {
                $this->locationName        = $preset->site_alias ?: $preset->name;
                $this->shiftLocationName   = $this->locationName;
                $this->latitude            = $preset->latitude;
                $this->shiftLatitude       = $this->latitude;
                $this->longitude           = $preset->longitude;
                $this->shiftLongitude      = $this->longitude;
                $this->geofenceRadius      = $preset->geofence_radius ?: 200;
                $this->shiftGeofenceRadius = $this->geofenceRadius;

                $this->dispatch('coordinates-updated', [
                    'lat'    => $this->latitude,
                    'lng'    => $this->longitude,
                    'radius' => $this->geofenceRadius,
                    'name'   => $this->locationName,
                ]);
            }
        } else {
            $this->locationName        = null;
            $this->shiftLocationName   = null;
            $this->latitude            = null;
            $this->shiftLatitude       = null;
            $this->longitude           = null;
            $this->shiftLongitude      = null;
            $this->geofenceRadius      = 200;
            $this->shiftGeofenceRadius = 200;

            $this->dispatch('coordinates-updated', [
                'lat'    => null,
                'lng'    => null,
                'radius' => 200,
                'name'   => null,
            ]);
        }
    }

    public function updatedShiftLocationPresetId($value)
    {
        $this->locationPresetId = $value;
        $this->updatedLocationPresetId($value);
    }

    public function updatedShiftEmployeeId($value)
    {
        if ($value) {
            $this->shiftEmployeeIds = [(int)$value];
        }
    }

    public function updatedShiftAssignmentId($value)
    {
        if ($value) {
            $this->shiftAssignmentIds = [(int)$value];
            if ($this->shiftTaskId) {
                $task = Task::find($this->shiftTaskId);
                if (!$task || $task->assignment_id != $value) {
                    $this->shiftTaskId = null;
                    $this->shiftTaskIds = [];
                }
            }
            $job = Assignment::find($value);
            if ($job) {
                if ($job->location_id) {
                    $this->locationPresetId = $job->location_id;
                    $this->updatedLocationPresetId($job->location_id);
                } elseif ($job->latitude && $job->longitude) {
                    $this->latitude            = $job->latitude;
                    $this->shiftLatitude       = $job->latitude;
                    $this->longitude           = $job->longitude;
                    $this->shiftLongitude      = $job->longitude;
                    $this->geofenceRadius      = $job->geofence_radius ?: 200;
                    $this->shiftGeofenceRadius = $this->geofenceRadius;
                    $this->locationName        = $job->location ?: $job->name;
                    $this->shiftLocationName   = $this->locationName;
                    $this->dispatch('coordinates-updated', [
                        'lat'    => $this->latitude,
                        'lng'    => $this->longitude,
                        'radius' => $this->geofenceRadius,
                        'name'   => $this->locationName,
                    ]);
                }
            }
        } else {
            $this->shiftTaskId = null;
            $this->shiftTaskIds = [];
        }
    }

    public function updatedShiftAssignmentIds($values)
    {
        $selectedJobIds = array_map('intval', (array)$values);
        $this->shiftAssignmentIds = $selectedJobIds;
        if (!empty($selectedJobIds)) {
            // Keep only child tasks belonging to the selected parent jobs
            $validTaskIds = Task::whereIn('assignment_id', $selectedJobIds)
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->toArray();
            $this->shiftTaskIds = array_values(array_intersect(array_map('intval', $this->shiftTaskIds), $validTaskIds));
            if ($this->shiftTaskId && !in_array((int)$this->shiftTaskId, $validTaskIds, true)) {
                $this->shiftTaskId = null;
            }
            if (count($selectedJobIds) === 1) {
                $this->shiftAssignmentId = $selectedJobIds[0];
            }
        } else {
            $this->shiftTaskIds = [];
            $this->shiftTaskId = null;
            $this->shiftAssignmentId = null;
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
                    $this->latitude = $task->latitude;
                    $this->shiftLatitude = $task->latitude;
                    $this->longitude = $task->longitude;
                    $this->shiftLongitude = $task->longitude;
                }
                if ($task->geofence_radius) {
                    $this->geofenceRadius = $task->geofence_radius;
                    $this->shiftGeofenceRadius = $task->geofence_radius;
                }
                if (empty($this->locationName) && $task->name) {
                    $this->locationName = $task->name;
                    $this->shiftLocationName = $task->name;
                }
                $this->dispatch('coordinates-updated', [
                    'lat'    => $this->latitude,
                    'lng'    => $this->longitude,
                    'radius' => $this->geofenceRadius,
                    'name'   => $this->locationName,
                ]);
            }
        }
    }

    public function openEditShift($scheduleId)
    {
        $s = Schedule::find($scheduleId);
        if (!$s) return;

        $user = Auth::user();
        if (!$user->canManageEmployee($s->employee_id) && !$user->canAccessAdmin() && !$user->canEditSchedules()) {
            $this->toast('You do not have permission to edit this schedule.', 'error');
            return;
        }

        $this->editingScheduleId = $s->id;
        $this->editingShiftId    = $s->id;
        $this->employeeSearch    = '';
        $this->shiftEmployeeId   = $s->employee_id;
        $this->shiftEmployeeIds  = [(int)$s->employee_id];
        $this->shiftAssignmentId = $s->assignment_id;
        $this->shiftAssignmentIds= $s->assignment_id ? [(int)$s->assignment_id] : [];
        $this->shiftTaskId       = $s->task_id;
        $this->shiftTaskIds      = $s->task_id ? [(int)$s->task_id] : [];
        $this->shiftMode         = 'single';
        $this->shiftDate         = $s->shift_date->toDateString();
        $this->shiftEndDate      = $s->shift_date->toDateString();
        $this->shiftDateFrom     = $s->shift_date->toDateString();
        $this->shiftDateTo       = $s->shift_date->toDateString();
        $this->startTime         = substr($s->start_time, 0, 5);
        $this->endTime           = substr($s->end_time, 0, 5);
        $this->shiftStart        = substr($s->start_time, 0, 5);
        $this->shiftEnd          = substr($s->end_time, 0, 5);
        $this->standardHours     = (float) ($s->standard_hours ?: 8.00);
        $this->shiftStandardHours= (float) ($s->standard_hours ?: 8.00);
        $this->shiftBreakStart   = $s->break_start ? substr($s->break_start, 0, 5) : '12:00';
        $this->shiftBreakEnd     = $s->break_end ? substr($s->break_end, 0, 5) : '12:30';
        $this->shiftBreakMinutes = (int) ($s->break_minutes ?: 30);
        $this->shiftHasBreak     = !empty($s->break_start) || !empty($s->break_minutes);
        $this->latitude          = $s->latitude;
        $this->longitude         = $s->longitude;
        $this->geofenceRadius    = $s->geofence_radius ?: 200;
        $this->locationName      = $s->location_name;
        $this->shiftLatitude     = $s->latitude;
        $this->shiftLongitude    = $s->longitude;
        $this->shiftGeofenceRadius = $s->geofence_radius ?: 200;
        $this->shiftLocationName = $s->location_name;
        $this->shiftNotes        = $s->notes;
        $this->shiftStatus       = $s->status ?: ($s->is_published ? 'published' : 'draft');
        $this->selectedSchedule  = null;

        $this->shiftDaysBreakdown = [[
            'date'          => $this->shiftDate,
            'day_name'      => $s->shift_date?->format('l') ?? '',
            'formatted'     => $s->shift_date?->format('D, M d') ?? '',
            'is_weekend'    => $s->shift_date?->isWeekend() ?? false,
            'enabled'       => true,
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'hours'         => $this->standardHours,
            'has_break'     => $this->shiftHasBreak,
            'break_start'   => $this->shiftBreakStart,
            'break_end'     => $this->shiftBreakEnd,
            'break_minutes' => $this->shiftBreakMinutes,
        ]];
        $this->shiftBreakdownWeekPage = 0;
        $this->syncSelectedShiftDays();

        $preset = \App\Models\Location::where('site_alias', $s->location_name)
            ->orWhere('name', $s->location_name)
            ->first();
        $this->locationPresetId      = $preset?->id;
        $this->shiftLocationPresetId = $preset?->id;

        if (($this->latitude === null || $this->longitude === null) && $this->shiftTaskId) {
            $task = Task::find($this->shiftTaskId);
            if ($task && $task->latitude !== null && $task->longitude !== null) {
                $this->latitude = $task->latitude;
                $this->longitude = $task->longitude;
                $this->shiftLatitude = $task->latitude;
                $this->shiftLongitude = $task->longitude;
                if ($task->geofence_radius) {
                    $this->geofenceRadius = $task->geofence_radius;
                    $this->shiftGeofenceRadius = $task->geofence_radius;
                }
                if (empty($this->locationName) && $task->name) {
                    $this->locationName = $task->name;
                    $this->shiftLocationName = $task->name;
                }
            }
        }

        $this->showShiftModal    = true;
        $this->showAddShift      = true;

        $this->dispatch('coordinates-updated', [
            'lat'    => $this->latitude,
            'lng'    => $this->longitude,
            'radius' => $this->geofenceRadius,
            'name'   => $this->locationName,
        ]);
    }

    public function cancelShift()
    {
        $this->closeShiftModal();
    }

    public function closeShiftModal()
    {
        $this->showShiftModal = false;
        $this->showAddShift = false;
        $this->editingScheduleId = null;
        $this->editingShiftId = null;
        $this->reset([
            'editingScheduleId', 'editingShiftId', 'shiftEmployeeId', 'shiftEmployeeIds', 'shiftAssignmentId', 'shiftAssignmentIds',
            'shiftTaskId', 'shiftTaskIds', 'shiftNotes', 'latitude', 'longitude', 'locationName', 'locationPresetId',
            'shiftLatitude', 'shiftLongitude', 'shiftLocationName', 'shiftLocationPresetId',
            'shiftDaysBreakdown', 'shiftBreakdownWeekPage', 'selectedShiftDays', 'employeeSearch',
        ]);
        $today = Carbon::now()->toDateString();
        $this->shiftDate           = $today;
        $this->shiftEndDate        = $today;
        $this->shiftDateFrom       = $today;
        $this->shiftDateTo         = $today;
        $this->startTime           = '08:00';
        $this->endTime             = '17:00';
        $this->shiftStart          = '08:00';
        $this->shiftEnd            = '17:00';
        $this->standardHours       = 8.00;
        $this->shiftStandardHours  = 8.00;
        $this->shiftStatus         = 'draft';
        $this->excludeWeekends     = false;
        $this->shiftHasBreak       = false;
        $this->shiftBreakStart     = '12:00';
        $this->shiftBreakEnd       = '12:30';
        $this->shiftBreakMinutes   = 0;
        $this->generateSelectedDays();
    }

    public function syncSelectedShiftDays(): void
    {
        $this->selectedShiftDays = collect($this->shiftDaysBreakdown)
            ->where('enabled', true)
            ->pluck('date')
            ->values()
            ->toArray();
    }

    public function selectAllShiftDays()
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['enabled'] = true;
            if ((float)($d['hours'] ?? 0) <= 0) {
                $d['hours'] = (float)($this->shiftStandardHours ?: ($this->standardHours ?: 8.00));
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

    public function updatedShiftDateFrom($val)
    {
        $this->shiftDate = $val;
        if (empty($this->shiftDateTo) || Carbon::parse($this->shiftDateTo)->lt(Carbon::parse($val))) {
            $this->shiftDateTo = $val;
            $this->shiftEndDate = $val;
        }
        $this->generateSelectedDays();
    }

    public function updatedShiftDateTo($val)
    {
        $this->shiftEndDate = $val;
        $this->generateSelectedDays();
    }

    public function updatedShiftStart($val)
    {
        $this->startTime = $val;
        $this->updatedStartTime($val);
    }

    public function updatedShiftEnd($val)
    {
        $this->endTime = $val;
        $this->updatedEndTime($val);
    }

    public function updatedShiftStandardHours($val)
    {
        $this->standardHours = $val;
        $this->updatedStandardHours($val);
    }

    public function generateSelectedDays(): void
    {
        if (empty($this->shiftDate)) {
            $this->shiftDaysBreakdown = [];
            return;
        }

        $endDate = $this->shiftEndDate ?: $this->shiftDate;

        try {
            $start = Carbon::parse($this->shiftDate);
            $end = Carbon::parse($endDate);

            if ($end->lt($start)) {
                $end = $start->copy();
                $this->shiftEndDate = $this->shiftDate;
            }

            // Map existing breakdown items so we preserve per-day customizations
            $existingMap = [];
            foreach ($this->shiftDaysBreakdown as $existing) {
                if (!empty($existing['date'])) {
                    $existingMap[$existing['date']] = $existing;
                }
            }

            $breakdown = [];
            $cur = $start->copy();
            $count = 0;
            $stdHours = max(0.5, (float)($this->standardHours ?: 8.00));

            while ($cur->lte($end) && $count < 60) {
                $dateStr = $cur->toDateString();
                $isWeekend = $cur->isWeekend();

                if (isset($existingMap[$dateStr])) {
                    $entry = $existingMap[$dateStr];
                    $entry['day_name'] = $cur->format('l');
                    $entry['formatted'] = $cur->format('D, M d');
                    $breakdown[] = $entry;
                } else {
                    $enabled = $this->excludeWeekends ? !$isWeekend : (in_array((string)$cur->dayOfWeekIso, $this->shiftDays, true));
                    $breakdown[] = [
                        'date'          => $dateStr,
                        'day_name'      => $cur->format('l'),
                        'formatted'     => $cur->format('D, M d'),
                        'is_weekend'    => $isWeekend,
                        'enabled'       => $enabled,
                        'start_time'    => $this->startTime ?: '08:00',
                        'end_time'      => $this->endTime ?: '17:00',
                        'hours'         => ($isWeekend && $this->excludeWeekends) ? 0.00 : $stdHours,
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
            $this->shiftBreakdownWeekPage = 0;
        } catch (\Throwable $e) {
            $this->shiftDaysBreakdown = [];
        }
    }

    public function updatedShiftDate($val)
    {
        if (empty($this->shiftEndDate) || Carbon::parse($this->shiftEndDate)->lt(Carbon::parse($val))) {
            $this->shiftEndDate = $val;
        }
        $this->generateSelectedDays();
    }

    public function updatedShiftEndDate($val)
    {
        $this->generateSelectedDays();
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

    public function updatedStandardHours($val)
    {
        $stdHours = max(0.0, (float)$val);
        foreach ($this->shiftDaysBreakdown as &$d) {
            if (!empty($d['enabled'])) {
                $d['hours'] = $stdHours;
            }
        }
    }

    public function updatedStartTime($val)
    {
        foreach ($this->shiftDaysBreakdown as &$d) {
            $d['start_time'] = $val;
        }
    }

    public function updatedEndTime($val)
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

    public function toggleShiftDayEnabled(int $index): void
    {
        if (isset($this->shiftDaysBreakdown[$index])) {
            $this->shiftDaysBreakdown[$index]['enabled'] = !$this->shiftDaysBreakdown[$index]['enabled'];
            if ($this->shiftDaysBreakdown[$index]['enabled'] && (float)($this->shiftDaysBreakdown[$index]['hours'] ?? 0) <= 0) {
                $this->shiftDaysBreakdown[$index]['hours'] = (float) ($this->standardHours ?: 8.00);
            }
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

    public function toggleShiftDay($date)
    {
        foreach ($this->shiftDaysBreakdown as $idx => $d) {
            if ($d['date'] === $date) {
                $this->shiftDaysBreakdown[$idx]['enabled'] = !$this->shiftDaysBreakdown[$idx]['enabled'];
                if ($this->shiftDaysBreakdown[$idx]['enabled'] && (float)($this->shiftDaysBreakdown[$idx]['hours'] ?? 0) <= 0) {
                    $this->shiftDaysBreakdown[$idx]['hours'] = (float) ($this->standardHours ?: 8.00);
                }
                break;
            }
        }
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

        if (!$this->shiftDate && $this->shiftDateFrom) {
            $this->shiftDate = $this->shiftDateFrom;
        }
        if (!$this->shiftDateFrom && $this->shiftDate) {
            $this->shiftDateFrom = $this->shiftDate;
        }
        if (!$this->shiftEndDate && $this->shiftDateTo) {
            $this->shiftEndDate = $this->shiftDateTo;
        }
        if (!$this->shiftDateTo && $this->shiftEndDate) {
            $this->shiftDateTo = $this->shiftEndDate;
        }
        if (!$this->startTime && $this->shiftStart) {
            $this->startTime = $this->shiftStart;
        }
        if (!$this->shiftStart && $this->startTime) {
            $this->shiftStart = $this->startTime;
        }
        if (!$this->endTime && $this->shiftEnd) {
            $this->endTime = $this->shiftEnd;
        }
        if (!$this->shiftEnd && $this->endTime) {
            $this->shiftEnd = $this->endTime;
        }
        if (!$this->standardHours && $this->shiftStandardHours) {
            $this->standardHours = $this->shiftStandardHours;
        }
        if (!$this->shiftStandardHours && $this->standardHours) {
            $this->shiftStandardHours = $this->standardHours;
        }
        if (!$this->latitude && $this->shiftLatitude) {
            $this->latitude = $this->shiftLatitude;
        }
        if (!$this->longitude && $this->shiftLongitude) {
            $this->longitude = $this->shiftLongitude;
        }
        if (!$this->locationName && $this->shiftLocationName) {
            $this->locationName = $this->shiftLocationName;
        }

        $isEditing = (bool) ($this->editingScheduleId || $this->editingShiftId);

        $rules = [
            'shiftDate'         => 'required|date',
            'startTime'         => 'required',
            'endTime'           => 'required|after:startTime',
            'standardHours'     => 'required|numeric|min:0.5|max:24',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'geofenceRadius'    => 'nullable|integer|min:10|max:10000',
        ];

        if ($isEditing) {
            $rules['shiftEmployeeId']   = 'required|exists:users,id';
            $rules['shiftAssignmentId'] = 'required|exists:tbl_assignments,id';
            $rules['shiftTaskId']       = 'required|exists:tbl_tasks,id';
        } else {
            $rules['shiftEndDate']       = 'nullable|date|after_or_equal:shiftDate';

            $rules['shiftEmployeeIds']   = 'required_without:shiftEmployeeId|array|min:1';
            $rules['shiftEmployeeIds.*'] = 'exists:users,id';
            $rules['shiftEmployeeId']    = 'nullable|exists:users,id';

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
            'shiftDate.required'                  => 'Please select a date.',
            'startTime.required'                  => 'Please select a start time.',
            'endTime.required'                    => 'Please select an end time.',
            'endTime.after'                       => 'End time must be after start time.',
            'standardHours.required'              => 'Standard hours is required.',
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

        if ($isEditing) {
            $editId = $this->editingScheduleId ?: $this->editingShiftId;
            $s = Schedule::findOrFail($editId);
            $s->update([
                'employee_id'     => $this->shiftEmployeeId,
                'assignment_id'   => $this->shiftAssignmentId,
                'task_id'         => $this->shiftTaskId,
                'shift_date'      => $this->shiftDate,
                'status'          => $status,
                'is_published'    => $isPublished,
                'start_time'      => $this->startTime,
                'end_time'        => $this->endTime,
                'standard_hours'  => $this->standardHours,
                'break_start'     => null,
                'break_end'       => null,
                'break_minutes'   => 0,
                'latitude'        => $this->latitude,
                'longitude'       => $this->longitude,
                'geofence_radius' => $this->geofenceRadius ?: 200,
                'location_name'   => $this->locationName,
                'notes'           => $this->shiftNotes,
            ]);

            if ($isPublished) {
                \App\Services\TimeLogComplianceService::notifyScheduleAssigned($s, Auth::id());
                $this->toast('Shift updated and published for employee time logging.', 'success');
            } else {
                $this->toast('Shift updated as draft.', 'info');
            }
        } else {
            // Target Employees
            $targetEmpIds = !empty($this->shiftEmployeeIds)
                ? array_values(array_unique(array_map('intval', $this->shiftEmployeeIds)))
                : ($this->shiftEmployeeId ? [(int)$this->shiftEmployeeId] : [Auth::id()]);

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

            // Target Days
            $isMultiDay = count($this->shiftDaysBreakdown) > 1 || ($this->shiftEndDate && $this->shiftEndDate > $this->shiftDate);
            if ($isMultiDay) {
                $enabledDays = collect($this->shiftDaysBreakdown)->where('enabled', true)->values();
                if ($enabledDays->isEmpty()) {
                    $start = Carbon::parse($this->shiftDate);
                    $end   = Carbon::parse($this->shiftEndDate ?: $this->shiftDate);
                    $cursor = $start->copy();
                    $daysList = [];
                    while ($cursor->lte($end)) {
                        $dowIso = (string) $cursor->dayOfWeekIso;
                        if (in_array($dowIso, $this->shiftDays, true)) {
                            $daysList[] = [
                                'date'          => $cursor->toDateString(),
                                'start_time'    => $this->startTime,
                                'end_time'      => $this->endTime,
                                'hours'         => $this->standardHours,
                            ];
                        }
                        $cursor->addDay();
                    }
                    $enabledDays = collect($daysList);
                }
            } else {
                $enabledDays = collect([[
                    'date'          => $this->shiftDate,
                    'start_time'    => $this->startTime,
                    'end_time'      => $this->endTime,
                    'hours'         => $this->standardHours,
                ]]);
            }

            $createdCount = 0;
            $lastSchedule = null;

            foreach ($targetEmpIds as $empId) {
                foreach ($pairs as $pair) {
                    $lat = $this->latitude;
                    $lng = $this->longitude;
                    $rad = $this->geofenceRadius ?: 200;
                    $loc = $this->locationName;

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
                            'start_time'      => $day['start_time'] ?? $this->startTime,
                            'end_time'        => $day['end_time'] ?? $this->endTime,
                            'standard_hours'  => $day['hours'] ?? $this->standardHours,
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

            if ($isPublished && $lastSchedule) {
                \App\Services\TimeLogComplianceService::notifyScheduleAssigned($lastSchedule, Auth::id());
                $this->toast($createdCount === 1 ? 'Shift created and published for employee time logging.' : "{$createdCount} shift(s) published successfully.", 'success');
            } else {
                $this->toast($createdCount === 1 ? 'Shift saved as draft (drafted by default).' : "{$createdCount} shift(s) saved as draft (drafted by default).", 'info');
            }
        }

        $this->showShiftModal = false;
        $this->showAddShift = false;
        $this->editingScheduleId = null;
        $this->editingShiftId = null;
    }

    public function publishShift($scheduleId)
    {
        $s = Schedule::findOrFail($scheduleId);
        $s->status = 'published';
        $s->is_published = true;
        $s->save();

        \App\Services\TimeLogComplianceService::notifyScheduleAssigned($s, Auth::id());
        $this->toast('Shift published. Now available for employee time logging.', 'success');

        if ($this->selectedSchedule && $this->selectedSchedule->id == $scheduleId) {
            $this->selectedSchedule->refresh();
        }
    }

    public function deleteShift($scheduleId)
    {
        $s = Schedule::find($scheduleId);
        if ($s) {
            $s->delete();
            $this->toast('Shift deleted.', 'info');
        }
        $this->selectedSchedule = null;
        $this->showShiftModal = false;
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function render()
    {
        $user = Auth::user();
        $anchor = Carbon::now()->startOfMonth()->addMonths($this->monthOffset);
        $weekStartDay = CompanySetting::getWeekStartDay();

        $gridStart = $anchor->copy()->startOfMonth()->startOfWeek($weekStartDay);
        $endOfMonth = $anchor->copy()->endOfMonth();
        $gridEnd   = $endOfMonth->copy()->startOfWeek($weekStartDay)->addDays(6)->endOfDay();

        $days = collect();
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        $dayHeaders = collect(range(0, 6))->map(fn ($i) => Carbon::now()->startOfWeek($weekStartDay)->addDays($i)->format('D'));

        // Accessible employees for manager/admin dropdown
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $employeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }
        $employees = $employeesQuery->get();

        $modalEmployees = $employees;
        if (!empty($this->employeeSearch)) {
            $search = strtolower(trim($this->employeeSearch));
            $modalEmployees = $modalEmployees->filter(function ($e) use ($search) {
                return str_contains(strtolower($e->name), $search)
                    || str_contains(strtolower($e->email ?? ''), $search)
                    || str_contains(strtolower($e->role ?? ''), $search);
            });
        }

        $targetEmpId = $this->isMySchedule ? $user->id : $this->selectedEmployeeId;

        // Schedules query
        $schedulesQuery = Schedule::with(['assignment','task','employee'])
            ->whereBetween('shift_date', [$gridStart->toDateString(), $gridEnd->toDateString()]);

        if ($targetEmpId && $targetEmpId !== 'all') {
            $schedulesQuery->where('employee_id', $targetEmpId);
        } else {
            if ($accessibleIds !== null) {
                $schedulesQuery->whereIn('employee_id', $accessibleIds);
            }
        }

        // If standard employee viewing their own calendar, hide draft shifts
        if ($user->isEmployee() && !$user->canAccessAdmin() && !$user->isManager()) {
            $schedulesQuery->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            });
        }

        $schedules = $schedulesQuery->get()->groupBy(fn ($s) => $s->shift_date->toDateString());

        $timeLogsQuery = TimeLog::with(['assignment','task'])
            ->whereBetween('clock_in', [$gridStart, $gridEnd->copy()->endOfDay()]);

        if ($targetEmpId && $targetEmpId !== 'all') {
            $timeLogsQuery->where('employee_id', $targetEmpId);
        } else {
            if ($accessibleIds !== null) {
                $timeLogsQuery->whereIn('employee_id', $accessibleIds);
            }
        }

        $timeLogs = $timeLogsQuery->get()->groupBy(fn ($l) => $l->clock_in->toDateString());

        $isAllowedToFilter = $user->isSuperAdmin() || $user->isAdmin() || $user->isHrManager() || $user->isManager() || $user->canAccessAdmin();

        return view('livewire.schedule-calendar', [
            'anchor'            => $anchor,
            'days'              => $days,
            'dayHeaders'        => $dayHeaders,
            'schedules'         => $schedules,
            'timeLogs'          => $timeLogs,
            'todayKey'          => Carbon::now()->toDateString(),
            'employees'         => $employees,
            'modalEmployees'    => $modalEmployees,
            'locationPresets'   => \App\Models\Location::where('status', '!=', 'inactive')->orderBy('name')->get(),
            'assignments'       => Assignment::ongoing()->orderBy('name')->get(),
            'tasks'             => Task::ongoing()->orderBy('name')->get(),
            'assignableJobs'    => Assignment::ongoing()->orderBy('name')->get(),
            'assignableTasks'   => Task::ongoing()->orderBy('name')->get(),
            'isManager'         => $user->canAccessSchedules() || $user->isManager() || $user->canAccessAdmin(),
            'isAllowedToFilter' => $isAllowedToFilter,
            'isMySchedule'      => $this->isMySchedule,
            'selectedEmployeeId'=> $this->selectedEmployeeId,
        ]);
    }
}
