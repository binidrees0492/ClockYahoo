<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;

class Timesheets extends Component
{
    use WithFileUploads;

    #[On('log-updated')]
    public function onLogUpdated()
    {
        // Re-renders automatically on log changes
    }

    public $selectedEmployeeId = null;
    public $weekOffset = 0;
    public $viewMode = 'grid'; // 'grid' (matches My Schedule) or 'table'
    public $viewBy = 'job_task'; // 'job_task', 'task_job', 'job', 'task'
    public $showViewByDropdown = false;
    public $showDatePicker = false;
    public $includeDeleted = false;
    public $calendarYearMonth = null;

    public function mount($selectedEmployeeId = null)
    {
        $this->selectedEmployeeId = $selectedEmployeeId ?: Auth::id();
    }

    #[On('navigate-to-timesheet')]
    public function navigateToTimesheet($date = null, $employeeId = null)
    {
        if ($date) {
            $weekStartDay = CompanySetting::getWeekStartDay();
            $targetWeekStart = Carbon::parse($date)->startOfWeek($weekStartDay);
            $currentWeekStart = Carbon::now()->startOfWeek($weekStartDay);
            $this->weekOffset = (int) $currentWeekStart->diffInWeeks($targetWeekStart, false);
        }
        if ($employeeId !== null) {
            $this->selectedEmployeeId = $employeeId;
        }
    }

    public $editingLogId = null;
    public $editAssignment = null;
    public $editTask = null;
    public $editClockIn = null;
    public $editClockOut = null;
    public $editNotes = null;

    public $showAddModal = false;
    public $addAssignment = null;
    public $addTask = null;
    public $addStartDate = null;
    public $addEndDate = null;
    public $addStandardShiftHours = 8.00;
    public $addDefaultStartTime = '09:00';
    public $addDefaultEndTime = '17:00';
    public $addDaysBreakdown = [];
    public $addBreakdownWeekPage = 0;
    public $addClockIn = null;
    public $addClockOut = null;
    public $addNotes = null;
    public $addAttachments = [];
    public $hasBreak = false;
    public $addBreakStart = '';
    public $addBreakEnd = '';

    // Copy to other days (Requirement 6)
    public $showCopyModal = false;
    public $copySourceLog = null;
    public $copyTargetDate = '';
    public $copyNotes = '';
    public $copyAttachments = [];

    // Copy-paste to day range
    public $copiedLogId = null;
    public $showPasteModal = false;
    public $pasteStartDate = '';
    public $pasteEndDate = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function toggleViewByDropdown()
    {
        $this->showViewByDropdown = !$this->showViewByDropdown;
    }

    public function setViewBy($mode)
    {
        if (in_array($mode, ['job_task', 'task_job', 'job', 'task'])) {
            $this->viewBy = $mode;
        }
        $this->showViewByDropdown = false;
    }

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

    public function prevCalendarMonth()
    {
        $cur = Carbon::parse(($this->calendarYearMonth ?: Carbon::now()->format('Y-m')) . '-01');
        $this->calendarYearMonth = $cur->subMonth()->format('Y-m');
    }

    public function nextCalendarMonth()
    {
        $cur = Carbon::parse(($this->calendarYearMonth ?: Carbon::now()->format('Y-m')) . '-01');
        $this->calendarYearMonth = $cur->addMonth()->format('Y-m');
    }

    public function selectCalendarDate($dateStr)
    {
        $weekStartDay = CompanySetting::getWeekStartDay();
        $targetWeekStart = Carbon::parse($dateStr)->startOfWeek($weekStartDay);
        $currentWeekStart = Carbon::now()->startOfWeek($weekStartDay);
        $this->weekOffset = (int) $currentWeekStart->diffInWeeks($targetWeekStart, false);
        $this->showDatePicker = false;
    }

    public static function formatHMM(int $minutes, bool $showZero = false): string
    {
        if ($minutes <= 0) return $showZero ? '0:00' : '';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    public function startEdit($logId)
    {
        $log = TimeLog::where('employee_id', Auth::id())->findOrFail($logId);

        $this->editingLogId   = $log->id;
        $this->editAssignment = $log->assignment_id;
        $this->editTask       = $log->task_id;
        $this->editClockIn    = $log->clock_in?->format('Y-m-d\TH:i');
        $this->editClockOut   = $log->clock_out?->format('Y-m-d\TH:i');
        $this->editNotes      = $log->notes;
    }

    public function cancelEdit()
    {
        $this->editingLogId = null;
        $this->reset(['editAssignment','editTask','editClockIn','editClockOut','editNotes']);
    }

    public function saveEdit()
    {
        $this->validate([
            'editAssignment' => 'required|exists:tbl_assignments,id',
            'editTask'       => 'required|exists:tbl_tasks,id',
            'editClockIn'    => 'required|date',
            'editClockOut'   => 'nullable|date|after:editClockIn',
        ]);

        $ongoingAssignment = Assignment::ongoing()->find($this->editAssignment);
        $ongoingTask = Task::ongoing()->find($this->editTask);

        if (!$ongoingAssignment || !$ongoingTask) {
            $this->toast('You can only assign ongoing tasks and jobs.', 'error');
            return;
        }

        $log = TimeLog::where('employee_id', Auth::id())->findOrFail($this->editingLogId);

        $log->assignment_id = $this->editAssignment;
        $log->task_id       = $this->editTask;
        $log->clock_in      = Carbon::parse($this->editClockIn);
        $log->clock_out     = $this->editClockOut ? Carbon::parse($this->editClockOut) : null;
        $log->notes         = $this->editNotes;

        if ($log->clock_out) {
            $log->duration_minutes = (int) ceil(
                $log->clock_in->diffInMinutes($log->clock_out) - ($log->break_minutes ?: 0)
            );
        }

        $log->save();

        $this->cancelEdit();
        $this->toast('Entry updated.');
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function openAdd($date = null, $assignmentId = null, $taskId = null, $employeeId = null)
    {
        $dStr = $date ? Carbon::parse($date)->toDateString() : Carbon::now()->toDateString();
        $user = Auth::user();

        if ($employeeId) {
            $this->selectedEmployeeId = $employeeId;
        } elseif (!$this->selectedEmployeeId || $this->selectedEmployeeId === 'all') {
            $this->selectedEmployeeId = $user->id;
        }

        $this->reset([
            'addAssignment', 'addTask',
            'addClockIn', 'addClockOut', 'addNotes', 'addAttachments',
            'hasBreak', 'addBreakStart', 'addBreakEnd',
            'addDaysBreakdown', 'addBreakdownWeekPage',
        ]);

        $this->addStartDate          = $dStr;
        $this->addEndDate            = $dStr;
        $this->addStandardShiftHours = 8.00;
        $this->addDefaultStartTime   = '09:00';
        $this->addDefaultEndTime     = '17:00';
        $this->addClockIn            = $dStr . 'T09:00';
        $this->addClockOut           = $dStr . 'T17:00';

        if ($assignmentId) {
            $this->addAssignment = $assignmentId;
        }
        if ($taskId) {
            $this->addTask = $taskId;
        }

        $this->syncShiftConfigForEmployee();
        $this->regenerateDaysBreakdown();
        $this->showAddModal = true;
    }

    public function updatedSelectedEmployeeId($val)
    {
        $this->addAssignment = null;
        $this->addTask = null;
        $this->syncShiftConfigForEmployee();
        $this->regenerateDaysBreakdown();
    }

    public function updatedAddAssignment($val)
    {
        $this->addTask = null;
        $this->syncShiftConfigForEmployee();
        $this->regenerateDaysBreakdown();
    }

    public function updatedAddStartDate($val)
    {
        if (strtotime($this->addEndDate) < strtotime($this->addStartDate)) {
            $this->addEndDate = $this->addStartDate;
        }
        $this->validateAndSyncShiftSelections();
        $this->regenerateDaysBreakdown();
    }

    public function updatedAddEndDate($val)
    {
        if (strtotime($this->addEndDate) < strtotime($this->addStartDate)) {
            $this->addStartDate = $this->addEndDate;
        }
        $this->validateAndSyncShiftSelections();
        $this->regenerateDaysBreakdown();
    }

    private function validateAndSyncShiftSelections(): void
    {
        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();

        if (!$targetUser) {
            return;
        }

        // Auto-select shift if exactly one exists for single day selection
        if ($this->addStartDate && $this->addStartDate === $this->addEndDate && $targetUser->hasSchedules()) {
            $shifts = \App\Models\Schedule::where('employee_id', $targetUser->id)
                ->published()
                ->whereDate('shift_date', $this->addStartDate)
                ->get();

            if ($shifts->count() === 1) {
                $shift = $shifts->first();
                $this->addAssignment = $shift->assignment_id;
                $this->addTask       = $shift->task_id;
                if ($shift->start_time) {
                    $this->addDefaultStartTime = substr($shift->start_time, 0, 5);
                }
                if ($shift->end_time) {
                    $this->addDefaultEndTime = substr($shift->end_time, 0, 5);
                }
                if ($shift->standard_hours) {
                    $this->addStandardShiftHours = (float) $shift->standard_hours;
                }
            }
        }

        $validJobIds = $this->modalAssignments->pluck('id')->toArray();
        if ($this->addAssignment && !in_array((int) $this->addAssignment, $validJobIds, true)) {
            $this->addAssignment = null;
            $this->addTask = null;
        }

        $validTaskIds = $this->modalTasks->pluck('id')->toArray();
        if ($this->addTask && !in_array((int) $this->addTask, $validTaskIds, true)) {
            $this->addTask = null;
        }
    }

    public function updatedAddStandardShiftHours($val)
    {
        $stdHours = max(0.0, (float)$val);
        foreach ($this->addDaysBreakdown as &$d) {
            if (!empty($d['enabled'])) {
                $d['hours'] = $stdHours;
            }
        }
    }

    public function syncShiftConfigForEmployee(): void
    {
        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();

        if (!$targetUser) {
            return;
        }

        if ($this->addAssignment) {
            $shiftConfig = $targetUser->getJobShiftConfig((int) $this->addAssignment);
            if (!empty($shiftConfig['shift_hours'])) {
                $this->addStandardShiftHours = (float) $shiftConfig['shift_hours'];
            }
            if (!empty($shiftConfig['shift_from'])) {
                $this->addDefaultStartTime = $shiftConfig['shift_from'];
            }
            if (!empty($shiftConfig['shift_to'])) {
                $this->addDefaultEndTime = $shiftConfig['shift_to'];
            }
        }
    }

    public function regenerateDaysBreakdown(): void
    {
        if (!$this->addStartDate) {
            $this->addStartDate = Carbon::now()->toDateString();
        }
        if (!$this->addEndDate) {
            $this->addEndDate = $this->addStartDate;
        }

        $start = Carbon::parse($this->addStartDate);
        $end   = Carbon::parse($this->addEndDate);

        if ($end->lt($start)) {
            $end = $start->copy();
            $this->addEndDate = $this->addStartDate;
        }

        $existingMap = [];
        foreach ($this->addDaysBreakdown as $d) {
            if (!empty($d['date'])) {
                $existingMap[$d['date']] = $d;
            }
        }

        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();

        $hasSchedules = $targetUser && $targetUser->hasSchedules();
        $breakdown = [];
        $cur = $start->copy();
        $stdHours = (float) ($this->addStandardShiftHours ?: 8.00);

        while ($cur->lte($end)) {
            $dateStr = $cur->toDateString();
            $isWeekend = $cur->isWeekend();

            $matchingSchedule = null;
            if ($hasSchedules) {
                $schedQ = \App\Models\Schedule::where('employee_id', $targetUser->id)
                    ->published()
                    ->whereDate('shift_date', $dateStr);
                if ($this->addAssignment) {
                    $schedQ->where('assignment_id', $this->addAssignment);
                }
                $matchingSchedule = $schedQ->first();
            }

            if (isset($existingMap[$dateStr])) {
                $entry = $existingMap[$dateStr];
                $entry['day_name']  = $cur->format('l');
                $entry['formatted'] = $cur->format('M d, Y');
                if ($hasSchedules) {
                    $entry['enabled'] = (bool) $matchingSchedule;
                    if ($matchingSchedule) {
                        $entry['hours'] = (float) ($matchingSchedule->standard_hours ?: $stdHours);
                        if ($matchingSchedule->start_time) {
                            $entry['start_time'] = substr($matchingSchedule->start_time, 0, 5);
                        }
                        if ($matchingSchedule->end_time) {
                            $entry['end_time'] = substr($matchingSchedule->end_time, 0, 5);
                        }
                    } else {
                        $entry['hours'] = 0.00;
                    }
                }
                $breakdown[] = $entry;
            } else {
                $isEnabled = $hasSchedules ? (bool) $matchingSchedule : !$isWeekend;
                $hours = $isEnabled ? (float) (($matchingSchedule?->standard_hours) ?: $stdHours) : 0.00;
                $startTime = ($matchingSchedule && $matchingSchedule->start_time) ? substr($matchingSchedule->start_time, 0, 5) : ($this->addDefaultStartTime ?: '09:00');
                $endTime   = ($matchingSchedule && $matchingSchedule->end_time) ? substr($matchingSchedule->end_time, 0, 5) : ($this->addDefaultEndTime ?: '17:00');
                $hasBreak  = ($matchingSchedule && $matchingSchedule->break_minutes > 0);
                $breakMins = ($matchingSchedule && $matchingSchedule->break_minutes) ? (int)$matchingSchedule->break_minutes : 30;

                $breakdown[] = [
                    'date'          => $dateStr,
                    'day_name'      => $cur->format('l'),
                    'formatted'     => $cur->format('M d, Y'),
                    'is_weekend'    => $isWeekend,
                    'enabled'       => $isEnabled,
                    'hours'         => $hours,
                    'has_break'     => $hasBreak,
                    'break_minutes' => $breakMins,
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                ];
            }
            $cur->addDay();
        }

        $this->addDaysBreakdown = $breakdown;
        $this->addBreakdownWeekPage = 0;
    }

    public function getAddWeeklyBreakdownProperty(): array
    {
        if (empty($this->addDaysBreakdown)) {
            return [];
        }

        $weekStartDay = CompanySetting::getWeekStartDay();
        $weeks = [];

        foreach ($this->addDaysBreakdown as $index => $day) {
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

    public function setAddBreakdownWeekPage(int $page): void
    {
        $total = count($this->addWeeklyBreakdown);
        $this->addBreakdownWeekPage = max(0, min($page, max(0, $total - 1)));
    }

    public function prevAddBreakdownWeek(): void
    {
        $this->setAddBreakdownWeekPage($this->addBreakdownWeekPage - 1);
    }

    public function nextAddBreakdownWeek(): void
    {
        $this->setAddBreakdownWeekPage($this->addBreakdownWeekPage + 1);
    }

    public function toggleAddDayEnabled(int $index): void
    {
        if (isset($this->addDaysBreakdown[$index])) {
            $this->addDaysBreakdown[$index]['enabled'] = !$this->addDaysBreakdown[$index]['enabled'];
            if ($this->addDaysBreakdown[$index]['enabled'] && (float)$this->addDaysBreakdown[$index]['hours'] <= 0) {
                $this->addDaysBreakdown[$index]['hours'] = (float) ($this->addStandardShiftHours ?: 8.00);
            }
        }
    }

    public function toggleAddDayBreak(int $index): void
    {
        if (isset($this->addDaysBreakdown[$index])) {
            $this->addDaysBreakdown[$index]['has_break'] = !($this->addDaysBreakdown[$index]['has_break'] ?? false);
            if ($this->addDaysBreakdown[$index]['has_break'] && empty($this->addDaysBreakdown[$index]['break_minutes'])) {
                $this->addDaysBreakdown[$index]['break_minutes'] = 30;
            }
        }
    }

    public function getModalAssignmentsProperty(): \Illuminate\Support\Collection
    {
        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();

        if (!$targetUser) {
            return collect();
        }

        if ($targetUser->isAdmin() || $targetUser->isHrManager() || $targetUser->isManager()) {
            return Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get();
        }

        $startDate = $this->addStartDate ?: Carbon::today()->toDateString();
        $endDate   = $this->addEndDate ?: $startDate;

        return $targetUser->getAssignedJobs($startDate, null, $endDate);
    }

    public function getModalTasksProperty(): \Illuminate\Support\Collection
    {
        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();

        if (!$targetUser) {
            return collect();
        }

        $jobId = $this->addAssignment ? (int) $this->addAssignment : null;
        if (!$jobId) {
            return collect();
        }

        $startDate = $this->addStartDate ?: Carbon::today()->toDateString();
        $endDate   = $this->addEndDate ?: $startDate;

        $assignedTasks = $targetUser->getAssignedTasks($jobId, $startDate, null, $endDate);
        if ($assignedTasks->isNotEmpty()) {
            return $assignedTasks;
        }

        if (isset($targetUser->permissions['assigned_tasks']) && is_array($targetUser->permissions['assigned_tasks'])) {
            return $assignedTasks;
        }

        if ($targetUser->hasSchedules()) {
            return collect();
        }

        return Task::where('status', '!=', 'inactive')
            ->ongoing()
            ->where('assignment_id', $jobId)
            ->orderBy('name')
            ->get();
    }

    public function cancelAdd()
    {
        $this->showAddModal = false;
        $this->reset([
            'addAssignment', 'addTask', 'addStartDate', 'addEndDate',
            'addClockIn', 'addClockOut', 'addNotes', 'addAttachments',
            'hasBreak', 'addBreakStart', 'addBreakEnd',
            'addDaysBreakdown', 'addBreakdownWeekPage',
        ]);
    }

    public function saveAdd()
    {
        $this->validate([
            'addAssignment'    => 'required|exists:tbl_assignments,id',
            'addTask'          => 'required|exists:tbl_tasks,id',
            'addNotes'         => 'nullable|string',
            'addAttachments.*' => 'nullable|file|max:10240',
        ]);

        $targetUser = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? \App\Models\User::find($this->selectedEmployeeId)
            : Auth::user();
        $targetUser = $targetUser ?: Auth::user();

        // Verify assignment and task belong to employee
        $allowedJobIds = $this->modalAssignments->pluck('id')->map(fn($id)=>(int)$id)->toArray();
        if (!empty($allowedJobIds) && !in_array((int)$this->addAssignment, $allowedJobIds, true)) {
            $this->toast('You can only log time for jobs assigned to this employee.', 'error');
            return;
        }

        $allowedTaskIds = $this->modalTasks->pluck('id')->map(fn($id)=>(int)$id)->toArray();
        if (!empty($allowedTaskIds) && !in_array((int)$this->addTask, $allowedTaskIds, true)) {
            $this->toast('You can only log time for tasks assigned to this employee.', 'error');
            return;
        }

        $ongoingAssignment = Assignment::where('status', '!=', 'inactive')->ongoing()->find($this->addAssignment);
        $ongoingTask = Task::where('status', '!=', 'inactive')->ongoing()->find($this->addTask);

        if (!$ongoingAssignment || !$ongoingTask) {
            $this->toast('You can only log time for ongoing tasks and jobs.', 'error');
            return;
        }

        $hourlyRate = $ongoingTask ? $ongoingTask->getHourlyRateFor($targetUser) : ($targetUser?->base_pay ?: null);

        // Multi-day breakdown check
        $enabledDays = array_filter($this->addDaysBreakdown, fn($d) => !empty($d['enabled']) && (float)($d['hours'] ?? 0) > 0);

        if (!empty($enabledDays)) {
            $createdCount = 0;
            $companyRequireSched = CompanySetting::get('require_published_schedule', false);

            foreach ($enabledDays as $day) {
                $targetDate = $day['date'];

                $hasDraftShift = \App\Models\Schedule::where('employee_id', $targetUser->id)
                    ->whereDate('shift_date', $targetDate)
                    ->where(function ($q) {
                        $q->where('status', 'draft')->orWhere('is_published', false);
                    })
                    ->exists();

                $publishedSchedule = \App\Models\Schedule::where('employee_id', $targetUser->id)
                    ->whereDate('shift_date', $targetDate)
                    ->where(function ($q) {
                        $q->where('status', 'published')->orWhere('is_published', true);
                    })
                    ->first();

                if ($hasDraftShift && !$publishedSchedule) {
                    $this->toast('The scheduled shift for this date is currently in draft. You can only log time once the schedule has been published.', 'error');
                    return;
                }

                if ($companyRequireSched && !$publishedSchedule) {
                    $this->toast("A published schedule is required to log time for {$targetDate}.", 'error');
                    return;
                }

                if ($targetUser->hasSchedules()) {
                    if (!$publishedSchedule) {
                        $this->toast("You do not have a scheduled shift for {$targetDate}. You can only log manual time for dates and times where a shift was assigned to you.", 'error');
                        return;
                    }

                    $matchingJobSchedule = \App\Models\Schedule::where('employee_id', $targetUser->id)
                        ->published()
                        ->whereDate('shift_date', $targetDate)
                        ->where('assignment_id', $this->addAssignment)
                        ->first();

                    if (!$matchingJobSchedule) {
                        $this->toast("The selected job is not assigned to your scheduled shift on {$targetDate}.", 'error');
                        return;
                    }

                    if ($matchingJobSchedule->task_id && (int)$matchingJobSchedule->task_id !== (int)$this->addTask) {
                        $this->toast("The selected task is not assigned to your scheduled shift on {$targetDate}.", 'error');
                        return;
                    }

                    $startTime = !empty($day['start_time']) ? $day['start_time'] : ($this->addDefaultStartTime ?: '09:00');
                    $tempIn = Carbon::parse($targetDate . ' ' . $startTime);
                    $tempDur = (int) round(((float)$day['hours']) * 60);
                    $tempBrk = (!empty($day['has_break']) && !empty($day['break_minutes'])) ? (int) $day['break_minutes'] : 0;
                    $tempOut = $tempIn->copy()->addMinutes($tempDur + $tempBrk);

                    if (!$matchingJobSchedule->coversTimeRange($tempIn, $tempOut, 30, 60)) {
                        $startFmt = $matchingJobSchedule->start_carbon ? $matchingJobSchedule->start_carbon->format('g:i A') : 'scheduled start';
                        $endFmt = $matchingJobSchedule->end_carbon ? $matchingJobSchedule->end_carbon->format('g:i A') : 'scheduled end';
                        $this->toast("Logged time for {$targetDate} ({$tempIn->format('g:i A')} - {$tempOut->format('g:i A')}) falls outside your scheduled shift ({$startFmt} - {$endFmt}).", 'error');
                        return;
                    }
                }

                $startTime = !empty($day['start_time']) ? $day['start_time'] : ($this->addDefaultStartTime ?: '09:00');
                $in = Carbon::parse($targetDate . ' ' . $startTime);

                $durationMinutes = (int) round(((float)$day['hours']) * 60);
                $breakMinutes = (!empty($day['has_break']) && !empty($day['break_minutes'])) ? (int) $day['break_minutes'] : 0;
                $grossMinutes = $durationMinutes + $breakMinutes;
                $out = $in->copy()->addMinutes($grossMinutes);

                $breaks = [];
                if ($breakMinutes > 0) {
                    $halfWork = (int) round($durationMinutes / 2);
                    $bStart = $in->copy()->addMinutes($halfWork);
                    $bEnd = $bStart->copy()->addMinutes($breakMinutes);
                    $breaks[] = [
                        'start'   => $bStart->toIso8601String(),
                        'end'     => $bEnd->toIso8601String(),
                        'seconds' => $breakMinutes * 60,
                    ];
                }

                $log = TimeLog::create([
                    'employee_id'      => $targetUser->id,
                    'assignment_id'    => $this->addAssignment,
                    'task_id'          => $this->addTask,
                    'hourly_rate'      => $hourlyRate,
                    'clock_in'         => $in,
                    'clock_out'        => $out,
                    'duration_minutes' => $durationMinutes,
                    'break_minutes'    => $breakMinutes,
                    'breaks'           => $breaks,
                    'status'           => 'pending',
                    'is_manual'        => true,
                    'approved'         => false,
                    'approved_by'      => null,
                    'approved_at'      => null,
                    'notes'            => $this->addNotes,
                ]);

                if (!empty($this->addAttachments)) {
                    foreach ($this->addAttachments as $file) {
                        $path = $file->store('attachments/timelogs', 'public');
                        TimeLogAttachment::create([
                            'time_log_id'   => $log->id,
                            'user_id'       => Auth::id(),
                            'original_name' => $file->getClientOriginalName(),
                            'path'          => $path,
                            'mime_type'     => $file->getMimeType(),
                            'size'          => $file->getSize(),
                        ]);
                    }
                }

                \App\Services\TimeLogComplianceService::processTimeLogSubmission($log);
                $createdCount++;
            }

            $this->cancelAdd();
            $msg = $createdCount > 1 
                ? "{$createdCount} manual time entries added. Submitted for approval."
                : 'Manual time entry added. Submitted for approval.';
            $this->toast($msg);
            return;
        }

        // Fallback for single clock_in/clock_out
        $this->validate([
            'addClockIn'  => 'required|date',
            'addClockOut' => 'required|date|after:addClockIn',
        ]);

        $in  = Carbon::parse($this->addClockIn);
        $out = Carbon::parse($this->addClockOut);

        $grossMinutes = (int) ceil($in->diffInMinutes($out));
        $breakMinutes = 0;
        $breaks = [];

        if ($this->hasBreak && $this->addBreakStart && $this->addBreakEnd) {
            $bStart = Carbon::parse($in->toDateString() . ' ' . $this->addBreakStart);
            $bEnd   = Carbon::parse($in->toDateString() . ' ' . $this->addBreakEnd);
            if ($bEnd->gt($bStart)) {
                $bSecs = $bStart->diffInSeconds($bEnd);
                $breakMinutes = (int) ceil($bSecs / 60);
                $breaks[] = [
                    'start'   => $bStart->toIso8601String(),
                    'end'     => $bEnd->toIso8601String(),
                    'seconds' => $bSecs,
                ];
            }
        }

        $durationMinutes = max(0, $grossMinutes - $breakMinutes);
        $targetDate = $in->toDateString();
        $hasDraftShift = \App\Models\Schedule::where('employee_id', $targetUser->id)
            ->whereDate('shift_date', $targetDate)
            ->where(function ($q) {
                $q->where('status', 'draft')->orWhere('is_published', false);
            })
            ->exists();

        $publishedSchedule = \App\Models\Schedule::where('employee_id', $targetUser->id)
            ->whereDate('shift_date', $targetDate)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->first();

        if ($hasDraftShift && !$publishedSchedule) {
            $this->toast('The scheduled shift for this date is currently in draft. You can only log time once the schedule has been published.', 'error');
            return;
        }

        if (CompanySetting::get('require_published_schedule', false) && !$publishedSchedule) {
            $this->toast('You can only log time if you have a published schedule against your name.', 'error');
            return;
        }

        if ($targetUser->hasSchedules()) {
            if (!$publishedSchedule) {
                $this->toast("You do not have a scheduled shift for {$targetDate}. You can only log manual time for dates and times where a shift was assigned to you.", 'error');
                return;
            }

            $matchingJobSchedule = \App\Models\Schedule::where('employee_id', $targetUser->id)
                ->published()
                ->whereDate('shift_date', $targetDate)
                ->where('assignment_id', $this->addAssignment)
                ->first();

            if (!$matchingJobSchedule) {
                $this->toast("The selected job is not assigned to your scheduled shift on {$targetDate}.", 'error');
                return;
            }

            if ($matchingJobSchedule->task_id && (int)$matchingJobSchedule->task_id !== (int)$this->addTask) {
                $this->toast("The selected task is not assigned to your scheduled shift on {$targetDate}.", 'error');
                return;
            }

            if (!$matchingJobSchedule->coversTimeRange($in, $out, 30, 60)) {
                $startFmt = $matchingJobSchedule->start_carbon ? $matchingJobSchedule->start_carbon->format('g:i A') : 'scheduled start';
                $endFmt = $matchingJobSchedule->end_carbon ? $matchingJobSchedule->end_carbon->format('g:i A') : 'scheduled end';
                $this->toast("Logged time for {$targetDate} ({$in->format('g:i A')} - {$out->format('g:i A')}) falls outside your scheduled shift ({$startFmt} - {$endFmt}).", 'error');
                return;
            }
        }

        $log = TimeLog::create([
            'employee_id'      => $targetUser->id,
            'assignment_id'    => $this->addAssignment,
            'task_id'          => $this->addTask,
            'hourly_rate'      => $hourlyRate,
            'clock_in'         => $in,
            'clock_out'        => $out,
            'duration_minutes' => $durationMinutes,
            'break_minutes'    => $breakMinutes,
            'breaks'           => $breaks,
            'status'           => 'pending',
            'is_manual'        => true,
            'notes'            => $this->addNotes,
            'approved'         => false,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        if (!empty($this->addAttachments)) {
            foreach ($this->addAttachments as $file) {
                $path = $file->store('attachments/timelogs', 'public');
                TimeLogAttachment::create([
                    'time_log_id'   => $log->id,
                    'user_id'       => Auth::id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        \App\Services\TimeLogComplianceService::processTimeLogSubmission($log);

        $this->cancelAdd();
        $this->toast('Manual time entry added. Submitted for approval.');
    }

    public function openDetail($logId)
    {
        $this->dispatch('open-log-detail', logId: $logId);
    }

    public function openCopyModal($logId)
    {
        $log = TimeLog::where('employee_id', Auth::id())->with(['assignment', 'task'])->findOrFail($logId);
        $this->copySourceLog   = $log;
        $this->copyTargetDate  = Carbon::tomorrow()->toDateString();
        $this->copyNotes       = '';
        $this->copyAttachments = [];
        $this->showCopyModal   = true;
    }

    public function closeCopyModal()
    {
        $this->showCopyModal   = false;
        $this->copySourceLog   = null;
        $this->copyTargetDate  = '';
        $this->copyNotes       = '';
        $this->copyAttachments = [];
    }

    public function copyCell($logId)
    {
        $this->copiedLogId = $logId;
        $this->toast('Time entry copied. Click "Paste" on any day cell to paste.', 'success');
    }

    public function clearCopied()
    {
        $this->copiedLogId = null;
    }

    public function openPasteModal($date = null)
    {
        if (!$this->copiedLogId) {
            $this->toast('No entry copied. Copy a cell first.', 'error');
            return;
        }
        $this->pasteStartDate = $date ?: Carbon::tomorrow()->toDateString();
        $this->pasteEndDate   = $date ?: Carbon::tomorrow()->toDateString();
        $this->showPasteModal = true;
    }

    public function cancelPaste()
    {
        $this->showPasteModal = false;
        $this->pasteStartDate = '';
        $this->pasteEndDate   = '';
    }

    public function confirmPaste()
    {
        if (!$this->copiedLogId) return;

        $this->validate([
            'pasteStartDate' => 'required|date',
            'pasteEndDate'   => 'required|date|after_or_equal:pasteStartDate',
        ]);

        $source = TimeLog::with(['assignment', 'task'])->findOrFail($this->copiedLogId);

        $inTime  = $source->clock_in ? $source->clock_in->format('H:i:s') : '09:00:00';
        $outTime = $source->clock_out ? $source->clock_out->format('H:i:s') : '17:00:00';

        $task = $source->task;
        $targetUser = $source->employee ?? Auth::user();
        $hourlyRate = $source->hourly_rate ?? ($task ? $task->getHourlyRateFor($targetUser) : ($targetUser->base_pay ?: null));

        $start = Carbon::parse($this->pasteStartDate);
        $end   = Carbon::parse($this->pasteEndDate);
        $count = 0;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $newIn  = Carbon::parse($cursor->toDateString() . ' ' . $inTime);
            $newOut = Carbon::parse($cursor->toDateString() . ' ' . $outTime);

            $newLog = TimeLog::create([
                'employee_id'      => $source->employee_id ?: Auth::id(),
                'assignment_id'    => $source->assignment_id,
                'task_id'          => $source->task_id,
                'hourly_rate'      => $hourlyRate,
                'clock_in'         => $newIn,
                'clock_out'        => $newOut,
                'break_minutes'    => $source->break_minutes ?: 0,
                'duration_minutes' => (int) ceil($newIn->diffInMinutes($newOut)) - ($source->break_minutes ?: 0),
                'status'           => 'pending',
                'is_manual'        => true,
                'approved'         => false,
                'notes'            => 'Pasted from ' . $source->clock_in->format('M d') . ' entry',
            ]);

            TimeLogNote::create([
                'time_log_id' => $newLog->id,
                'user_id'     => Auth::id(),
                'body'        => '[Pasted Entry] Copied from log #' . $source->id . ' (' . $source->clock_in->format('M d H:i') . ' – ' . ($source->clock_out ? $source->clock_out->format('H:i') : 'running') . ')',
            ]);

            $count++;
            $cursor->addDay();
        }

        $this->cancelPaste();
        $this->copiedLogId = null;
        $this->toast("Pasted to {$count} day(s) successfully.");
    }

    public function confirmCopy()
    {
        if (!$this->copySourceLog) return;

        $this->validate([
            'copyTargetDate'    => 'required|date',
            'copyNotes'         => 'required|string|min:3|max:1000',
            'copyAttachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $source = $this->copySourceLog;

        $inTime = $source->clock_in ? $source->clock_in->format('H:i:s') : '09:00:00';
        $outTime = $source->clock_out ? $source->clock_out->format('H:i:s') : '17:00:00';

        $newIn = Carbon::parse($this->copyTargetDate . ' ' . $inTime);
        $newOut = Carbon::parse($this->copyTargetDate . ' ' . $outTime);

        $task = Task::find($source->task_id);
        $hourlyRate = $source->hourly_rate ?? ($task ? $task->getHourlyRateFor(Auth::user()) : (Auth::user()->base_pay ?: null));

        $newLog = TimeLog::create([
            'employee_id'      => $source->employee_id ?: Auth::id(),
            'assignment_id'    => $source->assignment_id,
            'task_id'          => $source->task_id,
            'hourly_rate'      => $hourlyRate,
            'clock_in'         => $newIn,
            'clock_out'        => $newOut,
            'break_minutes'    => $source->break_minutes ?: 0,
            'duration_minutes' => (int) ceil($newIn->diffInMinutes($newOut)) - ($source->break_minutes ?: 0),
            'status'           => 'pending',
            'is_manual'        => true,
            'approved'         => false,
            'notes'            => $this->copyNotes,
        ]);

        // Create proof note
        TimeLogNote::create([
            'time_log_id' => $newLog->id,
            'user_id'     => Auth::id(),
            'body'        => "[Copied Manual Entry] " . $this->copyNotes,
        ]);

        // Store attachments
        if (!empty($this->copyAttachments)) {
            foreach ($this->copyAttachments as $file) {
                $path = $file->store("time-log-attachments/{$newLog->id}", 'public');
                TimeLogAttachment::create([
                    'time_log_id'   => $newLog->id,
                    'user_id'       => Auth::id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime_type'     => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        // Notify HR for approval
        \App\Services\TimeLogComplianceService::processTimeLogSubmission($newLog);

        $this->closeCopyModal();
        $this->toast('Time entry copied successfully (marked as manual, submitted for approval).');
    }

    
    public function deleteLog($logId)
    {
        $log = TimeLog::findOrFail($logId);
        $user = Auth::user();

        if ($log->employee_id != $user->id && !$user->canManageEmployee($log->employee_id) && !$user->canAccessAdmin()) {
            abort(403, 'Unauthorized to delete this time entry.');
        }

        if (!$user->isAdmin() && !$user->isHrManager() && !$user->isSuperAdmin()) {
            if ($log->approved || $log->status === 'rejected' || (method_exists($log, 'isRejected') && $log->isRejected())) {
                $this->toast('Approved or rejected entries cannot be deleted.', 'error');
                return;
            }
        }

        $log->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);
        TimeLog::where('parent_log_id', $log->id)->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);
        $this->toast('Entry archived successfully.');
    }

    public function restoreLog($logId)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore deleted records.');
        }

        $log = TimeLog::findOrFail($logId);
        $log->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);
        TimeLog::where('parent_log_id', $log->id)->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);
        $this->toast('Time entry restored successfully.');
    }

    public function permanentlyDeleteLog($logId)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can permanently delete records.');
        }

        $log = TimeLog::findOrFail($logId);
        TimeLog::where('parent_log_id', $log->id)->delete();
        $log->logNotes()->delete();
        $log->logAttachments()->delete();
        $log->delete();

        $this->toast('Time entry and any linked child records permanently deleted.');
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = null;
        $this->toastType    = $type;
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function dismissToast() { $this->toastMessage = null; }

    public function render()
    {
        $weekStartDay = CompanySetting::getWeekStartDay();
        $currentWeekStart = Carbon::now()->startOfWeek($weekStartDay);
        $start = $currentWeekStart->copy()->addWeeks($this->weekOffset);
        $end   = $start->copy()->addDays(6)->endOfDay();

        $days = collect();
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        // Preset label (ClockShark style)
        $presetLabel = match($this->weekOffset) {
            0       => 'This Week',
            -1      => 'Last Week',
            -2      => '2 Weeks Ago',
            -3      => '3 Weeks Ago',
            1       => 'Next Week',
            default => $this->weekOffset < 0 ? abs($this->weekOffset) . ' Weeks Ago' : $this->weekOffset . ' Weeks Later',
        };

        if ($start->month === $end->month) {
            $dateRangeString = $start->format('M j') . ' – ' . $end->format('j, Y');
        } else {
            $dateRangeString = $start->format('M j') . ' – ' . $end->format('M j, Y');
        }

        $user = Auth::user();
        $accessibleIds = $user->getAccessibleEmployeeIds();

        $query = TimeLog::with(['assignment','task','employee','logNotes','logAttachments'])
            ->where('status', '!=', 'break')
            ->whereBetween('clock_in', [$start, $end])
            ->orderBy('clock_in');

        if (!($this->includeDeleted && Auth::user()?->isSuperAdmin())) {
            $query->notArchived();
        }

        if ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all') {
            if ($this->selectedEmployeeId != $user->id && !$user->canManageEmployee($this->selectedEmployeeId) && !$user->canAccessAdmin()) {
                $query->where('employee_id', $user->id);
            } else {
                $query->where('employee_id', $this->selectedEmployeeId);
            }
        } else {
            if ($accessibleIds !== null) {
                $query->whereIn('employee_id', $accessibleIds);
            }
        }

        $logs = $query->get();
        $logsByDay = $logs->groupBy(fn ($l) => $l->clock_in->toDateString());

        $employeesQuery = \App\Models\User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }
        $employees = $employeesQuery->get();

        // Calculate approved days count (out of 7)
        $approvedDaysCount = $days->filter(function ($d) use ($logs) {
            $dayLogs = $logs->filter(fn ($l) => $l->clock_in->toDateString() === $d->toDateString());
            return $dayLogs->isNotEmpty() && $dayLogs->every(fn ($l) => (bool)$l->approved);
        })->count();

        // Mini calendar data for popup
        $calDate = Carbon::parse(($this->calendarYearMonth ?: $start->format('Y-m')) . '-01');
        $calMonthTitle = strtoupper($calDate->format('F Y'));
        $calStart = $calDate->copy()->startOfWeek($weekStartDay);
        $calEnd = $calDate->copy()->endOfMonth()->endOfWeek($weekStartDay);

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

        // Grouping logs based on viewBy
        $groupedByJobTask = $logs->groupBy(function ($l) {
            return match($this->viewBy) {
                'task_job' => ($l->task_id ?: 0) . '_' . ($l->assignment_id ?: 0),
                'job'      => (string) ($l->assignment_id ?: 0),
                'task'     => (string) ($l->task_id ?: 0),
                default    => ($l->assignment_id ?: 0) . '_' . ($l->task_id ?: 0),
            };
        });

        $groupedRows = collect();

        foreach ($groupedByJobTask as $groupKey => $groupLogs) {
            $sample = $groupLogs->first();
            $job = $sample->assignment;
            $task = $sample->task;

            $byEmployee = $groupLogs->groupBy('employee_id');

            foreach ($byEmployee as $empId => $empLogs) {
                $emp = $employees->firstWhere('id', $empId) ?? $empLogs->first()->employee ?? \App\Models\User::find($empId);
                if (!$emp) continue;

                $mins = $empLogs->sum(fn ($l) => $l->clock_out
                    ? $l->duration_minutes
                    : (int) ceil($l->netSeconds() / 60));

                $rowLogsByDay = $empLogs->groupBy(fn ($l) => $l->clock_in->toDateString());

                $groupedRows->push([
                    'group_key'       => $groupKey,
                    'job_id'          => $sample->assignment_id,
                    'job'             => $job,
                    'task_id'         => $sample->task_id,
                    'task'            => $task,
                    'employee_id'     => $emp->id,
                    'employee'        => $emp,
                    'logs'            => $rowLogsByDay,
                    'total_hours'     => round($mins / 60, 2),
                    'total_minutes'   => $mins,
                    'formatted_total' => self::formatHMM($mins),
                ]);
            }
        }

        // If no logs, show placeholder row
        if ($groupedRows->isEmpty()) {
            $targetEmp = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
                ? ($employees->firstWhere('id', $this->selectedEmployeeId) ?? \App\Models\User::find($this->selectedEmployeeId))
                : $user;
            if ($targetEmp) {
                $groupedRows->push([
                    'group_key'       => '0_0',
                    'job_id'          => null,
                    'job'             => null,
                    'task_id'         => null,
                    'task'            => null,
                    'employee_id'     => $targetEmp->id,
                    'employee'        => $targetEmp,
                    'logs'            => collect(),
                    'total_hours'     => 0.0,
                    'total_minutes'   => 0,
                    'formatted_total' => '',
                ]);
            }
        }

        // Sort grouped rows
        $groupedRows = $groupedRows->sortBy([
            fn ($a, $b) => strcmp($a['job']?->name ?? 'ZZZ', $b['job']?->name ?? 'ZZZ'),
            fn ($a, $b) => strcmp($a['task']?->name ?? 'ZZZ', $b['task']?->name ?? 'ZZZ'),
            fn ($a, $b) => strcmp($a['employee']->name ?? '', $b['employee']->name ?? ''),
        ])->values();

        // Query approved Time Off requests overlapping the week
        $ptoQuery = \App\Models\TimeOffRequest::with(['policy', 'employee'])
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereBetween('end_at', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_at', '<=', $start)
                          ->where('end_at', '>=', $end);
                  });
            });

        if ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all') {
            if ($this->selectedEmployeeId != $user->id && !$user->canManageEmployee($this->selectedEmployeeId) && !$user->canAccessAdmin()) {
                $ptoQuery->where('employee_id', $user->id);
            } else {
                $ptoQuery->where('employee_id', $this->selectedEmployeeId);
            }
        } else {
            if ($accessibleIds !== null) {
                $ptoQuery->whereIn('employee_id', $accessibleIds);
            }
        }

        $approvedTimeOff = $ptoQuery->get();

        $ptoMinutesByDay = [];
        foreach ($days as $d) {
            $ptoMinutesByDay[$d->toDateString()] = 0;
        }

        $timeOffRows = [];

        foreach ($approvedTimeOff as $to) {
            $policyName = $to->policy?->name ?? 'Paid Time Off';
            $isPaid = ($to->policy?->type ?? 'paid') === 'paid';

            if (!isset($timeOffRows[$policyName])) {
                $timeOffRows[$policyName] = [
                    'policy'        => $policyName,
                    'is_paid'       => $isPaid,
                    'days'          => array_fill_keys(array_keys($ptoMinutesByDay), 0),
                    'total_minutes' => 0,
                ];
            }

            if (!empty($to->daily_breakdown) && is_array($to->daily_breakdown)) {
                foreach ($to->daily_breakdown as $item) {
                    $itemDate = $item['date'] ?? null;
                    if ($itemDate && isset($ptoMinutesByDay[$itemDate])) {
                        $hours = (float) ($item['hours'] ?? 0);
                        $isUnpaid = !empty($item['is_unpaid']);
                        $mins = (int) round($hours * 60);

                        $timeOffRows[$policyName]['days'][$itemDate] += $mins;
                        $timeOffRows[$policyName]['total_minutes'] += $mins;

                        if ($isPaid && !$isUnpaid) {
                            $ptoMinutesByDay[$itemDate] += $mins;
                        }
                    }
                }
            } else {
                $toStart = $to->start_at->copy()->startOfDay();
                $toEnd   = $to->end_at->copy()->endOfDay();
                $dailyH  = (float) ($to->daily_hours ?: 8.0);

                foreach ($days as $d) {
                    $dStr = $d->toDateString();
                    if ($d->betweenIncluded($toStart, $toEnd) && !$d->isWeekend()) {
                        $mins = (int) round($dailyH * 60);
                        $timeOffRows[$policyName]['days'][$dStr] += $mins;
                        $timeOffRows[$policyName]['total_minutes'] += $mins;

                        if ($isPaid) {
                            $ptoMinutesByDay[$dStr] += $mins;
                        }
                    }
                }
            }
        }

        // Calculate bottom summary rows (REGULAR TIME, PAID TIME OFF, SUBTOTAL)
        $regularMinutesByDay = [];
        $subtotalMinutesByDay = [];

        foreach ($days as $d) {
            $dayStr = $d->toDateString();
            $dayLogs = $logs->filter(fn ($l) => $l->clock_in->toDateString() === $dayStr);
            $dayMins = $dayLogs->sum(fn ($l) => ($l->duration_minutes > 0 ? (int)$l->duration_minutes : (int) ceil($l->netSeconds() / 60)));
            $regularMinutesByDay[$dayStr] = $dayMins;
            $subtotalMinutesByDay[$dayStr] = $dayMins + ($ptoMinutesByDay[$dayStr] ?? 0);
        }

        $totalRegularMins  = array_sum($regularMinutesByDay);
        $totalPtoMins      = array_sum($ptoMinutesByDay);
        $totalSubtotalMins = array_sum($subtotalMinutesByDay);

        // View-by dropdown label
        $viewByLabel = match($this->viewBy) {
            'task_job' => 'TASK/JOB',
            'job'      => 'JOB',
            'task'     => 'TASK',
            default    => 'JOB/TASK',
        };

        return view('livewire.timesheets', [
            'startOfWeek'          => $start,
            'endOfWeek'            => $end,
            'days'                 => $days,
            'presetLabel'          => $presetLabel,
            'dateRangeString'      => $dateRangeString,
            'approvedDaysCount'    => $approvedDaysCount,
            'calendarWeeks'        => $calendarWeeks,
            'calMonthTitle'        => $calMonthTitle,
            'viewByLabel'          => $viewByLabel,
            'logs'                 => $logs,
            'logsByDay'            => $logsByDay,
            'groupedRows'          => $groupedRows,
            'timeOffRows'          => $timeOffRows,
            'regularMinutesByDay'  => $regularMinutesByDay,
            'ptoMinutesByDay'      => $ptoMinutesByDay,
            'subtotalMinutesByDay' => $subtotalMinutesByDay,
            'totalRegularMins'     => $totalRegularMins,
            'totalPtoMins'         => $totalPtoMins,
            'totalSubtotalMins'    => $totalSubtotalMins,
            'todayKey'             => Carbon::now()->toDateString(),
            'assignments'          => Assignment::ongoing()->orderBy('name')->get(),
            'tasks'                => Task::ongoing()->orderBy('name')->get(),
            'employees'            => $employees,
            'isManagerOrHr'        => $user->canAccessAdmin() || $user->isManager(),
            'totalMinutes'         => $totalSubtotalMins,
        ]);
    }
}
