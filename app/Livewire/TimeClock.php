<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TimeClock extends Component
{
    use WithFileUploads;

    public $assignments = [];
    public $tasks = [];

    public $selectedAssignment = null;
    public $selectedTask = null;

    public $activeLogId = null;

    public $hours = '00';
    public $minutes = '00';
    public $seconds = '00';
    public $hoursPercent = 0;
    public $minutesPercent = 0;
    public $secondsPercent = 0;

    public $breakHours = '00';
    public $breakMinutes = '00';
    public $breakSeconds = '00';

    public $clockInEpoch = null;
    public $shiftClockInEpoch = null;
    public $shiftPriorNetSeconds = 0;
    public $completedBreakSeconds = 0;
    public $breakStartEpoch = null;
    public $serverEpoch = null;

    public $startedAtText = 'Not Clocked In';
    public $shiftStartedAtText = '';
    public $hasSwitched = false;
    public $activeJobName = '';
    public $activeTaskName = '';

    public $isOnBreak = false;
    public $isActive = false;

    public $toastMessage = null;
    public $toastType = 'success';

    public $showSwitchModal = false;
    public $switchAssignment = null;
    public $switchTask = null;

    public $showAddNote = false;
    public $showAddAttachment = false;

    public $noteDraft = '';
    public $attachmentDrafts = [];
    public $activeNotes = [];
    public $activeAttachments = [];

    public $recentActivity = [];

    public function mount()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $this->assignments = $user ? $user->getAssignedJobs($today) : Assignment::ongoing()->orderBy('name')->get();
        $this->tasks       = $user ? $user->getAssignedTasks(null, $today) : Task::ongoing()->orderBy('name')->get();

        $this->refreshState();
    }

    #[On('timezone-changed')]
    public function onTimezoneChanged($timezone = null)
    {
        $this->refreshState();
    }

    public function toggleAddNote()
    {
        $this->showAddNote = !$this->showAddNote;
    }

    public function toggleAddAttachment()
    {
        $this->showAddAttachment = !$this->showAddAttachment;
    }

    public function refreshState()
    {
        $user = Auth::user();
        $activeTz = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();
        $tzAbbr = $user ? $user->getTimezoneAbbr() : 'UTC';

        $today = Carbon::today()->toDateString();
        $this->assignments = $user ? $user->getAssignedJobs($today) : Assignment::ongoing()->orderBy('name')->get();
        $this->tasks       = $user ? $user->getAssignedTasks(null, $today) : Task::ongoing()->orderBy('name')->get();

        $log = TimeLog::where('employee_id', Auth::id())
            ->notArchived()
            ->whereNull('clock_out')
            ->where('status', '!=', 'break')
            ->with(['assignment', 'task'])
            ->latest()
            ->first();

        if (!$log) {
            $this->activeLogId          = null;
            $this->isActive             = false;
            $this->isOnBreak            = false;
            $this->startedAtText        = 'Not Clocked In';
            $this->shiftStartedAtText   = '';
            $this->hasSwitched          = false;
            $this->activeJobName        = '';
            $this->activeTaskName       = '';
            $this->clockInEpoch         = null;
            $this->shiftClockInEpoch    = null;
            $this->shiftPriorNetSeconds = 0;
            $this->completedBreakSeconds = 0;
            $this->breakStartEpoch      = null;
            $this->serverEpoch          = Carbon::now()->getTimestampMs();
            $this->resetTimer();
            $this->loadRecentActivity();
            return;
        }

        $this->activeLogId    = $log->id;
        $this->isActive       = true;
        $this->isOnBreak      = $log->isOnBreak();
        $this->activeJobName  = $log->assignment?->name ?? '—';
        $this->activeTaskName = $log->task?->name ?? '—';

        // Calculate Shift-level chain timing
        $shiftLogs = $log->getShiftLogs();
        $rootLog   = $shiftLogs->first() ?? $log;
        $this->hasSwitched = ($shiftLogs->count() > 1);

        $shiftStart = $rootLog->getClockInCarbon() ?? $log->getClockInCarbon();
        $this->shiftClockInEpoch = $shiftStart ? $shiftStart->getTimestampMs() : null;
        $this->clockInEpoch      = $log->getClockInCarbon() ? $log->getClockInCarbon()->getTimestampMs() : null;

        // Sum net seconds from previous completed segments in this same shift
        $priorSecs = 0;
        foreach ($shiftLogs as $sLog) {
            if ($sLog->id !== $log->id) {
                $priorSecs += $sLog->netSeconds();
            }
        }
        $this->shiftPriorNetSeconds  = $priorSecs;
        $this->completedBreakSeconds = $log->completedBreakSeconds();
        $this->breakStartEpoch       = $log->getBreakStartedAtCarbon() ? $log->getBreakStartedAtCarbon()->getTimestampMs() : null;
        $this->serverEpoch           = Carbon::now()->getTimestampMs();

        $clockInUserTz = $log->getClockInCarbon() ? $log->getClockInCarbon()->copy()->setTimezone($activeTz) : null;
        $shiftStartUserTz = $shiftStart ? $shiftStart->copy()->setTimezone($activeTz) : null;

        if ($this->hasSwitched) {
            $this->startedAtText      = 'Current Task: ' . ($clockInUserTz ? $clockInUserTz->format('g:i A') . " ({$tzAbbr})" : '—');
            $this->shiftStartedAtText = 'Shift Started: ' . ($shiftStartUserTz ? $shiftStartUserTz->format('D n/j @ g:i A') . " ({$tzAbbr})" : '—') . ' (' . $shiftLogs->count() . ' segments logged)';
        } else {
            $this->startedAtText      = 'Started ' . ($clockInUserTz ? $clockInUserTz->format('D n/j @ g:i A') . " ({$tzAbbr})" : '—');
            $this->shiftStartedAtText = '';
        }

        // Shift total net seconds (prior switched tasks + current active task net seconds)
        $totalShiftNet = $priorSecs + $log->netSeconds();
        $this->hours   = str_pad(intdiv($totalShiftNet, 3600), 2, '0', STR_PAD_LEFT);
        $this->minutes = str_pad(intdiv($totalShiftNet % 3600, 60), 2, '0', STR_PAD_LEFT);
        $this->seconds = str_pad($totalShiftNet % 60, 2, '0', STR_PAD_LEFT);

        $this->secondsPercent = round(((int) $this->seconds / 60) * 100, 1);
        $this->minutesPercent = round(((int) $this->minutes / 60) * 100, 1);
        $this->hoursPercent   = min(100, round((((int) $this->hours % 12) / 12) * 100, 1));

        $brk = $log->currentBreakSeconds();
        $this->breakHours   = str_pad(intdiv($brk, 3600), 2, '0', STR_PAD_LEFT);
        $this->breakMinutes = str_pad(intdiv($brk % 3600, 60), 2, '0', STR_PAD_LEFT);
        $this->breakSeconds = str_pad($brk % 60, 2, '0', STR_PAD_LEFT);

        $this->activeNotes = $log->logNotes()->with('user')->get()->map(fn ($n) => [
            'id'         => $n->id,
            'body'       => $n->body,
            'user'       => $n->user->name ?? '—',
            'created_at' => $n->created_at->diffForHumans(),
        ])->toArray();

        $this->activeAttachments = $log->logAttachments()->get()->map(fn ($a) => [
            'id'   => $a->id,
            'name' => $a->original_name,
            'size' => $a->humanSize(),
            'url'  => $a->url(),
            'mime' => $a->mime_type,
        ])->toArray();

        $this->loadRecentActivity();
    }

    private function loadRecentActivity()
    {
        $user = Auth::user();
        $activeTz = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();

        $this->recentActivity = TimeLog::where('employee_id', Auth::id())
            ->notArchived()
            ->where('status', '!=', 'break')
            ->with(['assignment', 'task'])
            ->orderByDesc('clock_in')
            ->limit(8)
            ->get()
            ->map(function ($l) use ($activeTz) {
                $inCarbon = $l->getClockInCarbon();
                $outCarbon = $l->getClockOutCarbon();
                $inFormatted = $inCarbon ? $inCarbon->copy()->setTimezone($activeTz)->format('D n/j @ g:i A') : '—';
                $outFormatted = $outCarbon ? $outCarbon->copy()->setTimezone($activeTz)->format('D n/j @ g:i A') : ($l->clock_out ? '—' : 'In Progress...');

                return [
                    'id'                   => $l->id,
                    'in'                   => $inCarbon?->toIso8601String(),
                    'in_formatted'         => $inFormatted,
                    'out'                  => $outCarbon?->toIso8601String(),
                    'out_formatted'        => $outFormatted,
                    'status'               => $l->status,
                    'is_active'            => is_null($l->clock_out),
                    'is_switched'          => ($l->status === 'switched'),
                    'break_minutes'        => (int) round($l->completedBreakSeconds() / 60),
                    'duration_minutes'     => $l->duration_minutes,
                    'is_manual'            => $l->isManual(),
                    'is_unusual'           => (bool) $l->is_unusual,
                    'unusual_reason'       => $l->unusual_reason,
                    'location_flagged'     => (bool) $l->location_flagged,
                    'location_flag_reason' => $l->location_flag_reason,
                    'approved'             => (bool) $l->approved,
                    'is_rejected'          => $l->isRejected(),
                    'action_requested'     => (bool) $l->isActionRequested(),
                    'clock_in_latitude'    => $l->clock_in_latitude,
                    'clock_in_longitude'   => $l->clock_in_longitude,
                    'task_gps_policy'      => $l->task?->gps_policy ?? 'none',
                    'job_name'             => $l->assignment?->name ?? 'Job',
                    'task_name'            => $l->task?->name ?? 'Task',
                    'task_radius'          => $l->task?->geofence_radius ?? 200,
                ];
            })
            ->toArray();
    }

    private function resetTimer()
    {
        $this->hours = $this->minutes = $this->seconds = '00';
        $this->hoursPercent = $this->minutesPercent = $this->secondsPercent = 0;
        $this->breakHours = $this->breakMinutes = $this->breakSeconds = '00';
        $this->activeNotes = [];
        $this->activeAttachments = [];
    }

    public function clockIn($lat = null, $lng = null)
    {
        if (!Auth::user()->canWebClock()) {
            $this->toast('Web browser time clocking is disabled for your account. Please use the mobile app.', 'error');
            return;
        }

        $this->validate([
            'selectedAssignment' => 'required|exists:tbl_assignments,id',
            'selectedTask'       => 'required|exists:tbl_tasks,id',
        ], [
            'selectedAssignment.required' => 'Please select a job to clock in.',
            'selectedTask.required'       => 'A job cannot be started unless a task is selected too.',
        ]);

        $ongoingAssignment = Assignment::ongoing()->find($this->selectedAssignment);
        $ongoingTask = Task::ongoing()->find($this->selectedTask);

        if (!$ongoingAssignment || !$ongoingTask) {
            $this->toast('You can only clock in to ongoing tasks and jobs.', 'error');
            return;
        }

        if ($ongoingTask->assignment_id && (int) $ongoingTask->assignment_id !== (int) $ongoingAssignment->id) {
            $this->addError('selectedTask', 'The selected task does not belong to this job.');
            $this->toast('The selected task does not belong to the selected job.', 'error');
            return;
        }

        $existing = TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->first();

        if ($existing) {
            $this->toast('You are already clocked in.', 'error');
            return;
        }

        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        if ($user && $user->isEmployee() && !$user->canAccessAdmin()) {
            $assignedJobIds = $user->getAssignedJobs($today)->pluck('id')->toArray();
            $assignedTaskIds = $user->getAssignedTasks((int) $this->selectedAssignment, $today)->pluck('id')->toArray();

            if (!in_array((int) $this->selectedAssignment, $assignedJobIds, true)) {
                $this->toast('You are only allowed to clock in to jobs assigned to you.', 'error');
                return;
            }
            if (!in_array((int) $this->selectedTask, $assignedTaskIds, true)) {
                $this->toast('You are only allowed to clock in to tasks assigned to you.', 'error');
                return;
            }
        }

        $schedule = \App\Models\Schedule::where('employee_id', Auth::id())
            ->published()
            ->whereDate('shift_date', $today)
            ->first();

        $hasDraftShiftToday = \App\Models\Schedule::where('employee_id', Auth::id())
            ->whereDate('shift_date', $today)
            ->draft()
            ->exists();

        if ($hasDraftShiftToday && !$schedule) {
            $this->toast('Your scheduled shift for today is currently in draft. You can only log time once your schedule has been published.', 'error');
            return;
        }

        if (CompanySetting::get('require_published_schedule', false) && !$schedule) {
            $this->toast('You can only log time if you have a published schedule against your name.', 'error');
            return;
        }

        $isProfileAssigned = $user && isset($user->permissions['assigned_jobs'])
            && is_array($user->permissions['assigned_jobs'])
            && in_array((int)$this->selectedAssignment, array_map('intval', $user->permissions['assigned_jobs']), true);

        $matchingJobSchedule = \App\Models\Schedule::where('employee_id', Auth::id())
            ->published()
            ->whereDate('shift_date', $today)
            ->where('assignment_id', $this->selectedAssignment)
            ->first();

        if (!$isProfileAssigned || CompanySetting::get('require_published_schedule', false)) {
            if (!$matchingJobSchedule) {
                if (!$schedule) {
                    $this->toast('You do not have a scheduled shift for today. You can only start a log for dates where a shift was assigned to you.', 'error');
                    return;
                } else {
                    $this->toast('The selected job is not assigned to your scheduled shift for today.', 'error');
                    return;
                }
            }
        }

        if ($matchingJobSchedule) {
            if ($matchingJobSchedule->task_id && (int)$matchingJobSchedule->task_id !== (int)$this->selectedTask) {
                $this->toast('The selected task is not assigned to your scheduled shift for today.', 'error');
                return;
            }

            if (!$matchingJobSchedule->coversTime($now, 30, 30)) {
                $startFmt = $matchingJobSchedule->start_carbon ? $matchingJobSchedule->start_carbon->format('g:i A') : 'scheduled start';
                $endFmt = $matchingJobSchedule->end_carbon ? $matchingJobSchedule->end_carbon->format('g:i A') : 'scheduled end';
                $this->toast("Your scheduled shift is from {$startFmt} to {$endFmt}. You can only clock in during your scheduled shift date and time.", 'error');
                return;
            }
        }

        $task = \App\Models\Task::find($this->selectedTask);

        // Location Check & Policy Enforcement (Task policy off by default, GPS N/A flagged)
        $locCheck = \App\Services\TimeLogComplianceService::checkLocation(
            Auth::user(),
            $schedule,
            $lat ? (float) $lat : null,
            $lng ? (float) $lng : null,
            $task
        );

        $now = Carbon::now();
        $shiftId = 'shift_' . Auth::id() . '_' . $now->timestamp;

        $log = TimeLog::create([
            'employee_id'          => Auth::id(),
            'shift_id'             => $shiftId,
            'assignment_id'        => $this->selectedAssignment,
            'task_id'              => $this->selectedTask,
            'hourly_rate'          => $task ? $task->getHourlyRateFor(Auth::user()) : (Auth::user()->base_pay ?: null),
            'timezone'             => CompanySetting::getServerTimezone() ?: config('app.timezone', 'America/New_York'),
            'clock_in'             => $now,
            'status'               => 'active',
            'clock_in_latitude'    => $lat ? (float) $lat : null,
            'clock_in_longitude'   => $lng ? (float) $lng : null,
            'location_flagged'     => $locCheck['flagged'],
            'location_flag_reason' => $locCheck['reason'],
            'is_unusual'           => $locCheck['flagged'],
            'unusual_reason'       => $locCheck['flagged'] ? $locCheck['reason'] : null,
        ]);

        try {
            \App\Services\NotificationRoutingService::notifyClockActivity($log, 'clock_in');
            if ($locCheck['flagged']) {
                \App\Services\NotificationRoutingService::send(
                    'geofence_breach',
                    [
                        'title'   => 'Geofence Alert: ' . Auth::user()->name,
                        'message' => Auth::user()->name . ' clocked in with location discrepancy: ' . $locCheck['reason'],
                        'link'    => '/timesheets',
                        'type'    => 'geofence_breach',
                    ],
                    Auth::user()
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Clock-in notification error: " . $e->getMessage());
        }

        $this->refreshState();
        if ($locCheck['flagged']) {
            $this->toast('Clocked in (' . $locCheck['reason'] . ')', 'error');
        } else {
            $this->toast('Clocked in successfully.');
        }
    }

    public function updatedSelectedAssignment($assignmentId)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        if ($assignmentId) {
            $this->tasks = $user ? $user->getAssignedTasks((int) $assignmentId, $today) : Task::ongoing()->where('assignment_id', $assignmentId)->orderBy('name')->get();
        } else {
            $this->tasks = $user ? $user->getAssignedTasks(null, $today) : Task::ongoing()->orderBy('name')->get();
        }
        $this->selectedTask = null;
    }

    public function updatedSwitchAssignment($assignmentId)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        if ($assignmentId) {
            $this->tasks = $user ? $user->getAssignedTasks((int) $assignmentId, $today) : Task::ongoing()->where('assignment_id', $assignmentId)->orderBy('name')->get();
        } else {
            $this->tasks = $user ? $user->getAssignedTasks(null, $today) : Task::ongoing()->orderBy('name')->get();
        }
        $this->switchTask = null;
    }

    public function clockOut($lat = null, $lng = null)
    {
        $log = TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        $clockIn = $log->getClockInCarbon() ?: $log->clock_in;
        $now = Carbon::now($log->getLogTimezone());
        $elapsedSeconds = $clockIn ? abs($now->getTimestamp() - $clockIn->getTimestamp()) : 999;
        if ($elapsedSeconds < 60) {
            $remaining = 60 - $elapsedSeconds;
            $this->toast("Please wait at least 1 minute after clocking in before clocking out to avoid accidental double-clicks ({$remaining}s remaining).", 'warning');
            return;
        }

        $today = Carbon::today()->toDateString();
        $schedule = \App\Models\Schedule::where('employee_id', Auth::id())
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->whereDate('shift_date', $today)
            ->first();

        $task = $log->task;

        // Location Check & Policy Enforcement
        $locCheck = \App\Services\TimeLogComplianceService::checkLocation(
            Auth::user(),
            $schedule,
            $lat ? (float) $lat : null,
            $lng ? (float) $lng : null,
            $task
        );

        if (!$locCheck['allowed']) {
            $this->toast($locCheck['reason'] ?: 'Clock-out blocked by location policy.', 'error');
            return;
        }

        if ($log->isOnBreak()) {
            $breakStart = $log->getBreakStartedAtCarbon();
            $now        = Carbon::now($log->getLogTimezone());
            $diffSecs   = $breakStart ? abs($now->getTimestamp() - $breakStart->getTimestamp()) : 0;
            $breaks     = $log->breaks ?? [];
            $breaks[]   = [
                'start'   => $breakStart ? $breakStart->toIso8601String() : $now->toIso8601String(),
                'end'     => $now->toIso8601String(),
                'seconds' => $diffSecs,
                'minutes' => (int) round($diffSecs / 60),
            ];
            $log->breaks          = $breaks;
            $log->break_minutes   = (int) round($log->completedBreakSeconds() / 60);
            $log->break_started_at = null;
        }

        $log->clock_out           = Carbon::now($log->getLogTimezone());
        $log->duration_minutes    = (int) ceil($log->netSeconds() / 60);
        $log->status              = 'completed';
        $log->clock_out_latitude  = $lat ? (float) $lat : null;
        $log->clock_out_longitude = $lng ? (float) $lng : null;

        if ($locCheck['flagged']) {
            $log->location_flagged = true;
            $log->location_flag_reason = ($log->location_flag_reason ? $log->location_flag_reason . ' | ' : '') . $locCheck['reason'];
        }

        $log->save();

        // Evaluate Unusual Activity & Notify HR to approve (Requirements 2 & 5)
        \App\Services\TimeLogComplianceService::processTimeLogSubmission($log);

        try {
            \App\Services\NotificationRoutingService::notifyClockActivity($log, 'clock_out');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Clock-out notification error: " . $e->getMessage());
        }

        $this->refreshState();
        $this->selectedAssignment = null;
        $this->selectedTask       = null;

        if ($log->is_unusual) {
            $this->toast("Clocked out. Unusual shift activity flagged: {$log->unusual_reason}", 'error');
        } else {
            $this->toast('Clocked out successfully. Submitted to HR for review.');
        }
    }

    public function startBreak()
    {
        $log = $this->currentLog();
        if (!$log || $log->isOnBreak()) return;

        $log->break_started_at = Carbon::now($log->getLogTimezone());
        $log->status           = 'on_break';
        $log->save();

        $this->refreshState();
        $this->toast('Break started.');
    }

    public function endBreak()
    {
        $log = $this->currentLog();
        if (!$log || !$log->isOnBreak()) return;

        $breakStart = $log->getBreakStartedAtCarbon();
        $now        = Carbon::now($log->getLogTimezone());
        $diffSecs   = $breakStart ? abs($now->getTimestamp() - $breakStart->getTimestamp()) : 0;

        $breaks = $log->breaks ?? [];
        $breaks[] = [
            'start'   => $breakStart ? $breakStart->toIso8601String() : $now->toIso8601String(),
            'end'     => $now->toIso8601String(),
            'seconds' => $diffSecs,
            'minutes' => (int) round($diffSecs / 60),
        ];

        $log->breaks          = $breaks;
        $log->break_minutes   = (int) round($log->completedBreakSeconds() / 60);
        $log->break_started_at = null;
        $log->status           = 'active';
        $log->save();

        $this->refreshState();
        $mins = (int) round($diffSecs / 60);
        $this->toast("Break ended ({$mins} min). Resumed work.");
    }

    public function openSwitchModal()
    {
        $log = $this->currentLog();
        if (!$log) return;

        $this->switchAssignment = $log->assignment_id;
        $this->switchTask       = $log->task_id;
        $this->showSwitchModal  = true;
    }

    public function cancelSwitch()
    {
        $this->showSwitchModal  = false;
        $this->switchAssignment = null;
        $this->switchTask       = null;
    }

    public function confirmSwitch()
    {
        $this->validate([
            'switchAssignment' => 'required|exists:tbl_assignments,id',
            'switchTask'       => 'required|exists:tbl_tasks,id',
        ], [
            'switchAssignment.required' => 'Please select a job to switch.',
            'switchTask.required'       => 'A job cannot be started unless a task is selected too.',
        ]);

        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        if ($user && $user->isEmployee() && !$user->canAccessAdmin()) {
            $assignedJobIds = $user->getAssignedJobs($today)->pluck('id')->toArray();
            $assignedTaskIds = $user->getAssignedTasks((int) $this->switchAssignment, $today)->pluck('id')->toArray();

            if (!in_array((int) $this->switchAssignment, $assignedJobIds, true)) {
                $this->toast('You are only allowed to switch to jobs assigned to you.', 'error');
                return;
            }
            if (!in_array((int) $this->switchTask, $assignedTaskIds, true)) {
                $this->toast('You are only allowed to switch to tasks assigned to you.', 'error');
                return;
            }
        }

        $isProfileAssigned = $user && isset($user->permissions['assigned_jobs'])
            && is_array($user->permissions['assigned_jobs'])
            && in_array((int)$this->switchAssignment, array_map('intval', $user->permissions['assigned_jobs']), true);

        $matchingJobSchedule = \App\Models\Schedule::where('employee_id', Auth::id())
            ->published()
            ->whereDate('shift_date', $today)
            ->where('assignment_id', $this->switchAssignment)
            ->first();

        if (!$isProfileAssigned || CompanySetting::get('require_published_schedule', false)) {
            if (!$matchingJobSchedule) {
                $this->toast('The selected job is not assigned to your scheduled shift for today.', 'error');
                return;
            }
        }

        if ($matchingJobSchedule) {
            if ($matchingJobSchedule->task_id && (int)$matchingJobSchedule->task_id !== (int)$this->switchTask) {
                $this->toast('The selected task is not assigned to your scheduled shift for today.', 'error');
                return;
            }

            if (!$matchingJobSchedule->coversTime($now, 30, 30)) {
                $startFmt = $matchingJobSchedule->start_carbon ? $matchingJobSchedule->start_carbon->format('g:i A') : 'scheduled start';
                $endFmt = $matchingJobSchedule->end_carbon ? $matchingJobSchedule->end_carbon->format('g:i A') : 'scheduled end';
                $this->toast("Your scheduled shift is from {$startFmt} to {$endFmt}. You can only switch jobs during your scheduled shift date and time.", 'error');
                return;
            }
        }

        $ongoingAssignment = Assignment::ongoing()->find($this->switchAssignment);
        $ongoingTask = Task::ongoing()->find($this->switchTask);

        if (!$ongoingAssignment || !$ongoingTask) {
            $this->toast('You can only switch to ongoing tasks and jobs.', 'error');
            return;
        }

        if ($ongoingTask->assignment_id && (int) $ongoingTask->assignment_id !== (int) $ongoingAssignment->id) {
            $this->addError('switchTask', 'The selected task does not belong to this job.');
            $this->toast('The selected task does not belong to the selected job.', 'error');
            return;
        }

        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session to switch.', 'error');
            $this->showSwitchModal = false;
            return;
        }

        if ($log->assignment_id == $this->switchAssignment
            && $log->task_id == $this->switchTask) {
            $this->toast('You selected the same Job/Task.', 'error');
            $this->showSwitchModal = false;
            return;
        }

        $log->switchTo((int) $this->switchAssignment, (int) $this->switchTask);

        $this->showSwitchModal  = false;
        $this->switchAssignment = null;
        $this->switchTask       = null;

        $this->refreshState();
        $this->toast('Switched successfully.');
    }

    public function addNote()
    {
        $this->validate([
            'noteDraft' => 'required|string|min:1|max:2000',
        ]);

        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        TimeLogNote::create([
            'time_log_id' => $log->id,
            'user_id'     => Auth::id(),
            'body'        => trim($this->noteDraft),
        ]);

        $this->noteDraft = '';
        $this->refreshState();
        $this->toast('Note added.');
    }

    public function deleteNote($id)
    {
        $note = TimeLogNote::find($id);
        if ($note && $note->user_id === Auth::id()) {
            $note->delete();
            $this->refreshState();
            $this->toast('Note deleted.');
        }
    }

    public function updatedAttachmentDrafts()
    {
        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);
    }

    public function uploadAttachments()
    {
        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        if (empty($this->attachmentDrafts)) {
            $this->toast('No files selected.', 'error');
            return;
        }

        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        foreach ($this->attachmentDrafts as $file) {
            $path = $file->store("time-log-attachments/{$log->id}", 'public');

            TimeLogAttachment::create([
                'time_log_id'   => $log->id,
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $this->attachmentDrafts = [];
        $this->refreshState();
        $this->toast('Files uploaded.');
    }

    public function deleteAttachment($id)
    {
        $att = TimeLogAttachment::find($id);
        if ($att && $att->user_id === Auth::id()) {
            Storage::disk('public')->delete($att->path);
            $att->delete();
            $this->refreshState();
            $this->toast('Attachment deleted.');
        }
    }

    public function openDetail($id)
    {
        $this->dispatch('open-log-detail', logId: $id);
    }

    private function currentLog(): ?TimeLog
    {
        return TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->where('status', '!=', 'break')
            ->latest()
            ->first();
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = null;
        $this->toastType    = $type;
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        return view('livewire.time-clock');
    }
}
