<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogAttachment;
use App\Models\User;
use App\Models\Schedule;
use App\Services\TimeLogComplianceService;
use App\Services\ReportDataService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimesheetsView extends Component
{
    use WithFileUploads;

    public $weekOffset = 0;

    public $currencyOrTz = 'PKT';
    public $employeeFilter = null;

    // Filters drawer
    public $showFilters = false;
    public $departmentFilter = null;
    public $locationFilter = null;
    public $jobFilter = null;
    public $statusFilter = null;
    public $viewArchived = false;
    public $includeDeleted = false;

    public function updatedIncludeDeleted($val)
    {
        $this->viewArchived = (bool) $val;
    }

    public function updatedViewArchived($val)
    {
        $this->includeDeleted = (bool) $val;
    }

    // Date picker popup
    public $showDatePicker = false;
    public $calendarYearMonth = null;

    public $showAddModal = false;
    public $addEmployeeId = null;
    public $addAssignment = null;
    public $addTask = null;
    public $addDate = null;
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

    // Add break
    public $hasBreak = false;
    public $addBreakStart = null;
    public $addBreakEnd = null;
    public $addStatus = 'approved';

    public $editingLogId = null;
    public $editAssignment = null;
    public $editTask = null;
    public $editClockIn = null;
    public $editClockOut = null;
    public $editNotes = null;

    // Edit break
    public $editHasBreak = false;
    public $editBreakStart = null;
    public $editBreakEnd = null;

    // Copy to other days (Requirement 6)
    public $showCopyModal = false;
    public $copySourceLogId = null;
    public $copyEmployeeId = null;
    public $copyAssignmentId = null;
    public $copyTaskId = null;
    public $copyStartTime = '08:00';
    public $copyEndTime = '17:00';
    public $copyTargetDates = [];
    public $copyNotes = '';
    public $copyAttachments = [];
    public $availableCopyDays = [];

    public $toastMessage = null;
    public $toastType = 'success';

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
        $this->weekOffset = (int) $targetWeekStart->diffInWeeks($currentWeekStart, false) * -1;
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

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user->canAccessTimesheets()) {
            abort(403, 'Unauthorized access to Timesheets.');
        }

        if ($user->isEmployee()) {
            $this->employeeFilter = (string) $user->id;
        }

        $this->currencyOrTz = $user->getTimezoneAbbr();
    }

    public function canEditFor($employeeId): bool
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        if ($user->isManager()) {
            return $user->canManagerEdit('timesheets') && $user->canManageEmployee($employeeId);
        }

        return (int) $user->id === (int) $employeeId && $user->canEdit('mobile_timesheets');
    }

    public function openAdd($employeeId = null, $date = null)
    {
        $date = $date ?: Carbon::now()->toDateString();
        $user = Auth::user();

        $accessibleIds = $user->getAccessibleEmployeeIds();
        $availableEmps = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $availableEmps->whereIn('id', $accessibleIds);
        }
        $firstValid = $availableEmps->first();

        if (!$employeeId || ($user->isSuperAdmin() && (int)$employeeId === (int)$user->id)) {
            $employeeId = $this->employeeFilter ?: ($firstValid?->id ?? $user->id);
        }

        if (!$this->canEditFor($employeeId)) {
            if ($firstValid && $this->canEditFor($firstValid->id)) {
                $employeeId = $firstValid->id;
            } else {
                $this->toast('You do not have permission to add time entries for this employee.', 'error');
                return;
            }
        }

        $this->reset([
            'editingLogId',
            'addAssignment', 'addTask',
            'addClockIn', 'addClockOut', 'addNotes', 'addAttachments',
            'hasBreak', 'addBreakStart', 'addBreakEnd',
            'addDaysBreakdown', 'addBreakdownWeekPage',
        ]);

        $this->addEmployeeId         = $employeeId;
        $this->addDate               = $date;
        $this->addStartDate          = $date;
        $this->addEndDate            = $date;
        $this->addStandardShiftHours = 8.00;
        $this->addDefaultStartTime   = '09:00';
        $this->addDefaultEndTime     = '17:00';
        $this->addClockIn            = $date . 'T09:00';
        $this->addClockOut           = $date . 'T17:00';
        $this->addStatus             = ($user && ($user->isAdmin() || $user->isHrManager() || $user->isSuperAdmin())) ? 'approved' : 'pending';

        $this->syncShiftConfigForEmployee();
        $this->regenerateDaysBreakdown();
        $this->showAddModal          = true;
    }

    public function updatedAddEmployeeId($val)
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
        $this->regenerateDaysBreakdown();
    }

    public function updatedAddEndDate($val)
    {
        if (strtotime($this->addEndDate) < strtotime($this->addStartDate)) {
            $this->addStartDate = $this->addEndDate;
        }
        $this->regenerateDaysBreakdown();
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
        if (!$this->addEmployeeId) {
            return;
        }

        $emp = User::find($this->addEmployeeId);
        if (!$emp) {
            return;
        }

        if ($this->addAssignment) {
            $shiftConfig = $emp->getJobShiftConfig((int) $this->addAssignment);
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

        $breakdown = [];
        $cur = $start->copy();
        $stdHours = (float) ($this->addStandardShiftHours ?: 8.00);

        while ($cur->lte($end)) {
            $dateStr = $cur->toDateString();
            $isWeekend = $cur->isWeekend();

            if (isset($existingMap[$dateStr])) {
                $entry = $existingMap[$dateStr];
                $entry['day_name']  = $cur->format('l');
                $entry['formatted'] = $cur->format('M d, Y');
                $breakdown[] = $entry;
            } else {
                $breakdown[] = [
                    'date'          => $dateStr,
                    'day_name'      => $cur->format('l'),
                    'formatted'     => $cur->format('M d, Y'),
                    'is_weekend'    => $isWeekend,
                    'enabled'       => !$isWeekend,
                    'hours'         => $isWeekend ? 0.00 : $stdHours,
                    'has_break'     => false,
                    'break_minutes' => 30,
                    'start_time'    => $this->addDefaultStartTime ?: '09:00',
                    'end_time'      => $this->addDefaultEndTime ?: '17:00',
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
        if (!$this->addEmployeeId) {
            return collect();
        }
        $targetUser = User::find($this->addEmployeeId);
        if (!$targetUser) {
            return collect();
        }

        if ($targetUser->isAdmin() || $targetUser->isHrManager() || $targetUser->isManager()) {
            return Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get();
        }

        return $targetUser->getAssignedJobs();
    }

    public function getModalTasksProperty(): \Illuminate\Support\Collection
    {
        if (!$this->addEmployeeId) {
            return collect();
        }
        $targetUser = User::find($this->addEmployeeId);
        if (!$targetUser) {
            return collect();
        }

        $jobId = $this->addAssignment ? (int) $this->addAssignment : null;
        if (!$jobId) {
            return collect();
        }

        $assignedTasks = $targetUser->getAssignedTasks($jobId);
        if ($assignedTasks->isNotEmpty()) {
            return $assignedTasks;
        }

        if (isset($targetUser->permissions['assigned_tasks']) && is_array($targetUser->permissions['assigned_tasks'])) {
            return $assignedTasks;
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
            'addEmployeeId', 'addDate', 'addStartDate', 'addEndDate',
            'addAssignment', 'addTask',
            'addClockIn', 'addClockOut', 'addNotes', 'addAttachments',
            'hasBreak', 'addBreakStart', 'addBreakEnd', 'addStatus',
            'addDaysBreakdown', 'addBreakdownWeekPage',
        ]);
    }

    public function updatedHasBreak($val)
    {
        if ($val) {
            if (!$this->addBreakStart) $this->addBreakStart = '12:00';
            if (!$this->addBreakEnd) $this->addBreakEnd = '12:30';
            $this->applyBreakToBreakdown();
        } else {
            foreach ($this->addDaysBreakdown as &$d) {
                $d['has_break'] = false;
            }
        }
    }

    public function updatedAddBreakStart($val)
    {
        $this->applyBreakToBreakdown();
    }

    public function updatedAddBreakEnd($val)
    {
        $this->applyBreakToBreakdown();
    }

    private function applyBreakToBreakdown()
    {
        if (!$this->hasBreak) return;
        $mins = 30;
        if ($this->addBreakStart && $this->addBreakEnd) {
            $s = Carbon::parse('2000-01-01 ' . $this->addBreakStart);
            $e = Carbon::parse('2000-01-01 ' . $this->addBreakEnd);
            if ($e->gt($s)) {
                $mins = (int) ceil($s->diffInMinutes($e));
            }
        }
        foreach ($this->addDaysBreakdown as &$d) {
            $d['has_break'] = true;
            $d['break_minutes'] = $mins;
            $d['break_start'] = $this->addBreakStart;
            $d['break_end'] = $this->addBreakEnd;
        }
    }

    public function saveAdd()
    {
        if (!$this->canEditFor($this->addEmployeeId)) {
            abort(403, 'Unauthorized to add time.');
        }

        // Require notes for manual time entries
        $this->validate([
            'addEmployeeId'    => 'required|exists:users,id',
            'addAssignment'    => 'required|exists:tbl_assignments,id',
            'addTask'          => 'required|exists:tbl_tasks,id',
            'addNotes'         => 'required|string|min:3',
            'addAttachments.*' => 'nullable|file|max:10240',
        ], [
            'addNotes.required' => 'A detailed note is required for manual time entries to explain validity.',
        ]);

        $emp = User::findOrFail($this->addEmployeeId);

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
            $this->toast('You can only log time for active/ongoing tasks and jobs.', 'error');
            return;
        }

        $hourlyRate = $ongoingTask ? $ongoingTask->getHourlyRateFor($emp) : ($emp?->base_pay ?: null);

        // Multi-day breakdown check
        $enabledDays = array_filter($this->addDaysBreakdown, fn($d) => !empty($d['enabled']) && (float)($d['hours'] ?? 0) > 0);

        if (!empty($enabledDays)) {
            $createdCount = 0;
            $companyRequireSched = CompanySetting::get('require_published_schedule', false);

            foreach ($enabledDays as $day) {
                $targetDate = $day['date'];

                $hasDraftShift = Schedule::where('employee_id', $this->addEmployeeId)
                    ->whereDate('shift_date', $targetDate)
                    ->where(function ($q) {
                        $q->where('status', 'draft')->orWhere('is_published', false);
                    })
                    ->exists();

                $publishedSchedule = Schedule::where('employee_id', $this->addEmployeeId)
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

                $startTime = !empty($day['start_time']) ? $day['start_time'] : ($this->addDefaultStartTime ?: '09:00');
                $in = Carbon::parse($targetDate . ' ' . $startTime);

                $breakMinutes = (!empty($day['has_break']) && !empty($day['break_minutes'])) ? (int) $day['break_minutes'] : 0;
                $grossMinutes = (int) round(((float)$day['hours']) * 60);
                $durationMinutes = max(0, $grossMinutes - $breakMinutes);
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

                $authUser = Auth::user();
                $isHrOrAdmin = $authUser && ($authUser->isAdmin() || $authUser->isHrManager() || $authUser->isSuperAdmin());
                $isDirectApprove = ($this->addStatus === 'approved') || ($isHrOrAdmin && $this->addStatus !== 'pending');

                $status = $isDirectApprove ? 'approved' : 'pending';
                $approved = $isDirectApprove ? true : false;
                $approvedBy = $isDirectApprove ? $authUser->id : null;
                $approvedAt = $isDirectApprove ? Carbon::now() : null;

                $log = TimeLog::create([
                    'employee_id'      => $this->addEmployeeId,
                    'assignment_id'    => $this->addAssignment,
                    'task_id'          => $this->addTask,
                    'hourly_rate'      => $hourlyRate,
                    'clock_in'         => $in,
                    'clock_out'        => $out,
                    'duration_minutes' => $durationMinutes,
                    'break_minutes'    => $breakMinutes,
                    'breaks'           => $breaks,
                    'status'           => $status,
                    'is_manual'        => true,
                    'approved'         => $approved,
                    'approved_by'      => $approvedBy,
                    'approved_at'      => $approvedAt,
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

                TimeLogComplianceService::processTimeLogSubmission($log);
                $createdCount++;
            }

            $this->cancelAdd();
            $msg = $isDirectApprove 
                ? ($createdCount > 1 ? "{$createdCount} manual time entries added and approved." : 'Manual time entry added and approved.')
                : ($createdCount > 1 ? "{$createdCount} manual time entries added. Submitted for approval." : 'Manual time entry added. Submitted for approval.');
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
        $hasDraftShift = Schedule::where('employee_id', $this->addEmployeeId)
            ->whereDate('shift_date', $targetDate)
            ->where(function ($q) {
                $q->where('status', 'draft')->orWhere('is_published', false);
            })
            ->exists();

        $publishedSchedule = Schedule::where('employee_id', $this->addEmployeeId)
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

        $authUser = Auth::user();
        $isHrOrAdmin = $authUser && ($authUser->isAdmin() || $authUser->isHrManager() || $authUser->isSuperAdmin());
        $isDirectApprove = ($this->addStatus === 'approved') || ($isHrOrAdmin && $this->addStatus !== 'pending');

        $status = $isDirectApprove ? 'approved' : 'pending';
        $approved = $isDirectApprove ? true : false;
        $approvedBy = $isDirectApprove ? $authUser->id : null;
        $approvedAt = $isDirectApprove ? Carbon::now() : null;

        $log = TimeLog::create([
            'employee_id'      => $this->addEmployeeId,
            'assignment_id'    => $this->addAssignment,
            'task_id'          => $this->addTask,
            'hourly_rate'      => $hourlyRate,
            'clock_in'         => $in,
            'clock_out'        => $out,
            'duration_minutes' => $durationMinutes,
            'break_minutes'    => $breakMinutes,
            'breaks'           => $breaks,
            'status'           => $status,
            'is_manual'        => true,
            'approved'         => $approved,
            'approved_by'      => $approvedBy,
            'approved_at'      => $approvedAt,
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

        TimeLogComplianceService::processTimeLogSubmission($log);

        $this->cancelAdd();
        $msg = $isDirectApprove ? 'Manual time entry added and approved.' : 'Manual time entry added. Submitted for approval.';
        $this->toast($msg);
    }

    public function startEdit($logId)
    {
        $log = TimeLog::findOrFail($logId);
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isHrManager()) {
            if ($log->isLocked()) {
                $this->toast('Approved, withdrawn, or rejected entries are locked and cannot be edited.', 'error');
                return;
            }
        }

        if (!$this->canEditFor($log->employee_id)) {
            $this->toast('You do not have permission to edit this time entry.', 'error');
            return;
        }

        $this->editingLogId    = $log->id;
        $this->editAssignment  = $log->assignment_id;
        $this->editTask        = $log->task_id;
        $this->editClockIn     = $log->clock_in?->format('Y-m-d\TH:i');
        $this->editClockOut    = $log->clock_out?->format('Y-m-d\TH:i');
        $this->editNotes       = $log->notes;

        $this->editHasBreak = !empty($log->breaks);
        if ($this->editHasBreak && isset($log->breaks[0])) {
            $this->editBreakStart = isset($log->breaks[0]['start']) ? Carbon::parse($log->breaks[0]['start'])->format('H:i') : null;
            $this->editBreakEnd   = isset($log->breaks[0]['end']) ? Carbon::parse($log->breaks[0]['end'])->format('H:i') : null;
        } else {
            $this->editBreakStart = null;
            $this->editBreakEnd   = null;
        }
    }

    public function cancelEdit()
    {
        $this->editingLogId = null;
        $this->reset(['editAssignment', 'editTask', 'editClockIn', 'editClockOut', 'editNotes', 'editHasBreak', 'editBreakStart', 'editBreakEnd']);
    }

    public function saveEdit()
    {
        $log = TimeLog::findOrFail($this->editingLogId);
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isHrManager()) {
            if ($log->isLocked()) {
                $this->toast('Approved, withdrawn, or rejected entries are locked and cannot be edited.', 'error');
                return;
            }
        }

        if (!$this->canEditFor($log->employee_id)) {
            abort(403, 'Unauthorized to edit this time entry.');
        }

        $this->validate([
            'editAssignment' => 'required|exists:tbl_assignments,id',
            'editTask'       => 'required|exists:tbl_tasks,id',
            'editClockIn'    => 'required|date',
            'editClockOut'   => 'nullable|date|after:editClockIn',
            'editNotes'      => 'required|string|min:3',
        ], [
            'editNotes.required' => 'A note is required when editing manual time entries.',
        ]);

        $ongoingAssignment = Assignment::ongoing()->find($this->editAssignment);
        $ongoingTask = Task::ongoing()->find($this->editTask);

        if (!$ongoingAssignment || !$ongoingTask) {
            $this->toast('You can only assign active/ongoing tasks and jobs.', 'error');
            return;
        }

        $in  = Carbon::parse($this->editClockIn);
        $out = $this->editClockOut ? Carbon::parse($this->editClockOut) : null;

        $grossMinutes = $out ? (int) ceil($in->diffInMinutes($out)) : $log->duration_minutes;
        $breakMinutes = 0;
        $breaks = [];

        if ($this->editHasBreak && $this->editBreakStart && $this->editBreakEnd && $out) {
            $bStart = Carbon::parse($in->toDateString() . ' ' . $this->editBreakStart);
            $bEnd   = Carbon::parse($in->toDateString() . ' ' . $this->editBreakEnd);
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

        $log->assignment_id    = $this->editAssignment;
        $log->task_id          = $this->editTask;
        $log->clock_in         = $in;
        $log->clock_out        = $out;
        $log->duration_minutes = $durationMinutes;
        $log->break_minutes    = $breakMinutes;
        $log->breaks           = $breaks;
        $log->notes            = $this->editNotes;
        $log->save();

        $this->cancelEdit();
        $this->toast('Time entry updated successfully.');
    }

    public function openCopyModal($logId)
    {
        $log = TimeLog::findOrFail($logId);

        if (!$this->canEditFor($log->employee_id)) {
            $this->toast('You do not have permission to copy this time entry.', 'error');
            return;
        }

        $this->copySourceLogId  = $log->id;
        $this->copyEmployeeId   = $log->employee_id;
        $this->copyAssignmentId = $log->assignment_id;
        $this->copyTaskId       = $log->task_id;
        $this->copyStartTime    = $log->clock_in ? $log->clock_in->format('H:i') : '08:00';
        $this->copyEndTime      = $log->clock_out ? $log->clock_out->format('H:i') : '17:00';
        $this->copyNotes        = $log->notes ? "Copied entry: " . $log->notes : "Copied time entry from " . ($log->clock_in ? $log->clock_in->format('M d') : 'previous date');
        $this->copyAttachments  = [];
        $this->copyTargetDates  = [];

        $weekStartDay = CompanySetting::getWeekStartDay();
        $startOfWeek = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
        $this->availableCopyDays = [];
        $sourceDate = $log->clock_in ? $log->clock_in->toDateString() : null;

        for ($i = 0; $i < 7; $i++) {
            $d = $startOfWeek->copy()->addDays($i);
            $dStr = $d->toDateString();
            if ($dStr !== $sourceDate) {
                $this->availableCopyDays[] = [
                    'date'      => $dStr,
                    'label'     => $d->format('D, M d'),
                    'is_today'  => $d->isToday(),
                ];
            }
        }

        $this->showCopyModal = true;
    }

    public function cancelCopy()
    {
        $this->showCopyModal    = false;
        $this->copySourceLogId  = null;
        $this->copyEmployeeId   = null;
        $this->copyAssignmentId = null;
        $this->copyTaskId       = null;
        $this->copyTargetDates  = [];
        $this->copyNotes        = '';
        $this->copyAttachments  = [];
    }

    public function saveCopy()
    {
        $this->validate([
            'copyEmployeeId'     => 'required|exists:users,id',
            'copyAssignmentId'   => 'required|exists:tbl_assignments,id',
            'copyTaskId'         => 'required|exists:tbl_tasks,id',
            'copyStartTime'      => 'required|date_format:H:i',
            'copyEndTime'        => 'required|date_format:H:i|after:copyStartTime',
            'copyTargetDates'    => 'required|array|min:1',
            'copyNotes'          => 'required|string|min:3',
            'copyAttachments.*'  => 'nullable|file|max:10240',
        ]);

        if (!$this->canEditFor($this->copyEmployeeId)) {
            abort(403, 'Unauthorized.');
        }

        $emp = User::find($this->copyEmployeeId);
        $task = Task::find($this->copyTaskId);
        $hourlyRate = $task ? $task->getHourlyRateFor($emp) : ($emp?->base_pay ?: null);

        $savedAttachmentPaths = [];
        if (!empty($this->copyAttachments)) {
            foreach ($this->copyAttachments as $file) {
                $path = $file->store('attachments/timelogs', 'public');
                $savedAttachmentPaths[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ];
            }
        }

        $createdCount = 0;

        foreach ($this->copyTargetDates as $targetDate) {
            $in  = Carbon::parse($targetDate . ' ' . $this->copyStartTime);
            $out = Carbon::parse($targetDate . ' ' . $this->copyEndTime);
            $durationMin = (int) ceil($in->diffInMinutes($out));

            $newLog = TimeLog::create([
                'employee_id'      => $this->copyEmployeeId,
                'assignment_id'    => $this->copyAssignmentId,
                'task_id'          => $this->copyTaskId,
                'hourly_rate'      => $hourlyRate,
                'clock_in'         => $in,
                'clock_out'        => $out,
                'duration_minutes' => $durationMin,
                'status'           => 'pending',
                'is_manual'        => true,
                'approved'         => false,
                'approved_by'      => null,
                'approved_at'      => null,
                'notes'            => $this->copyNotes,
            ]);

            foreach ($savedAttachmentPaths as $attInfo) {
                TimeLogAttachment::create([
                    'time_log_id'   => $newLog->id,
                    'user_id'       => Auth::id(),
                    'original_name' => $attInfo['original_name'],
                    'path'          => $attInfo['path'],
                    'mime_type'     => $attInfo['mime_type'],
                    'size'          => $attInfo['size'],
                ]);
            }

            \App\Services\TimeLogComplianceService::processTimeLogSubmission($newLog);
            $createdCount++;
        }

        $this->cancelCopy();
        $this->toast("Successfully copied time entry to {$createdCount} day(s). Submitted for approval.");
    }

    public function deleteLog($logId)
    {
        $log = TimeLog::findOrFail($logId);
        $user = Auth::user();

        if (!$this->canEditFor($log->employee_id)) {
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

    public function rejectArchivedLog($logId)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can reject archived records.');
        }

        $log = TimeLog::findOrFail($logId);
        $log->update([
            'status'      => 'rejected',
            'approved'    => false,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ]);
        TimeLog::where('parent_log_id', $log->id)->update([
            'status'      => 'rejected',
            'approved'    => false,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ]);
        $this->toast('Archived entry marked as rejected.');
    }

    public function restoreArchivedLog($logId)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore archived records.');
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

    public function approveLog($logId)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHrManager() && !$user->isSuperAdmin()) {
            $this->toast('Unauthorized to approve entries.', 'error');
            return;
        }

        $log = TimeLog::findOrFail($logId);
        $log->update([
            'approved'    => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'approved',
        ]);
        $this->toast('Time entry approved.');
    }

    public function disapproveLog($logId)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHrManager() && !$user->isSuperAdmin()) {
            $this->toast('Unauthorized to disapprove entries.', 'error');
            return;
        }

        $log = TimeLog::findOrFail($logId);
        $log->update([
            'approved'    => false,
            'approved_by' => null,
            'approved_at' => null,
            'status'      => 'pending',
        ]);
        $this->toast('Time entry returned to pending.');
    }

    public function openDetail($logId)
    {
        $this->dispatch('open-log-detail', logId: $logId);
    }

    #[On('log-updated')]
    public function onLogUpdated($logId = null)
    {
        // Automatically re-renders the timesheet grid when a log is modified/approved/deleted
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
        $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset)->startOfDay();
        $end   = $start->copy()->addDays(6)->endOfDay();

        $user = Auth::user();
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

        if ($this->departmentFilter) {
            $employeesQuery->where('department_id', $this->departmentFilter);
        }

        if ($this->locationFilter) {
            $employeesQuery->where('location_id', $this->locationFilter);
        }

        $employees = $employeesQuery->get();

        $accessibleEmployeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $accessibleEmployeesQuery->whereIn('id', $accessibleIds);
        }
        $filterEmployees = $accessibleEmployeesQuery->get();

        $logsQuery = TimeLog::with(['assignment', 'task', 'employee'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('clock_in', [$start, $end])
            ->orderBy('clock_in');

        if ($this->jobFilter) {
            $logsQuery->where('assignment_id', $this->jobFilter);
        }

        if ($this->viewArchived || $this->statusFilter === 'archived') {
            $logsQuery->archived();
        } else {
            $logsQuery->notArchived();
        }

        if ($this->statusFilter === 'approved') {
            $logsQuery->where('approved', true);
        } elseif ($this->statusFilter === 'pending') {
            $logsQuery->where('approved', false)->where('status', '!=', 'rejected');
        }

        $logs = $logsQuery->get();

        // Group logs: [employeeId][YYYY-MM-DD] = Collection
        $grouped = [];
        foreach ($logs as $log) {
            $empId = $log->employee_id;
            $day   = $log->clock_in->toDateString();
            $grouped[$empId][$day][] = $log;
        }

        // Per-day totals
        $dayTotals = [];
        foreach (range(0, 6) as $i) {
            $day = $start->copy()->addDays($i)->toDateString();
            $mins = 0;
            foreach ($grouped as $empId => $days) {
                if (isset($days[$day])) {
                    foreach ($days[$day] as $log) {
                        $mins += $log->clock_out
                            ? $log->duration_minutes
                            : (int) ceil($log->netSeconds() / 60);
                    }
                }
            }
            $dayTotals[$day] = $mins;
        }

        // Weekly total
        $weekTotal = array_sum($dayTotals);

        // Approval status per employee (true if all logs are approved and at least one log exists)
        $employeeApproved = [];
        foreach ($employees as $e) {
            $empLogs = collect($grouped[$e->id] ?? [])->flatten();
            $employeeApproved[$e->id] = $empLogs->count() > 0 && $empLogs->every(fn ($l) => (bool) $l->approved);
        }

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

        $activeClockIns = TimeLog::with(['assignment', 'task'])
            ->notArchived()
            ->whereNull('clock_out')
            ->where('status', 'running')
            ->get()
            ->keyBy('employee_id');

        if (Department::count() === 0) {
            Department::firstOrCreate(['name' => 'General'], ['status' => 'Active']);
            Department::firstOrCreate(['name' => 'Operations'], ['status' => 'Active']);
            Department::firstOrCreate(['name' => 'Field Services'], ['status' => 'Active']);
        }

        return view('livewire.timesheets-view', [
            'startOfWeek'      => $start,
            'endOfWeek'        => $end,
            'employees'        => $employees,
            'filterEmployees'  => $filterEmployees,
            'grouped'          => $grouped,
            'dayTotals'        => $dayTotals,
            'weekTotal'        => $weekTotal,
            'employeeApproved' => $employeeApproved,
            'assignments'      => Assignment::orderBy('name')->get(),
            'tasks'            => Task::orderBy('name')->get(),
            'departments'      => Department::orderBy('name')->get(),
            'locations'        => \App\Models\Location::where('status', 'active')->orderBy('name')->get(),
            'calendarWeeks'    => $calendarWeeks,
            'calMonthTitle'    => $calMonthTitle,
            'activeClockIns'   => $activeClockIns,
        ]);
    }
}
