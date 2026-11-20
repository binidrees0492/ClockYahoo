<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Task;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TimeLogDetail extends Component
{
    use WithFileUploads;

    public $show = false;
    public $logId = null;
    public $log = null;
    public $editAudits = [];

    // Discussion / Notes
    public $noteDraft = '';
    public $attachmentDrafts = [];
    public $replyingToNoteId = null;
    public $replyDraft = '';

    // HR Actions
    public $showDisapproveInput = false;
    public $disapprovalReason = '';
    public $showActionRequestInput = false;
    public $actionRequestReason = '';

    // Copy Entry Form
    public $showCopyForm = false;
    public $copyTargetDate = '';
    public $copyNotes = '';
    public $copyAttachments = [];

    // Edit Entry Form
    public $showEditForm = false;
    public $editAssignment = null;
    public $editTask = null;
    public $editClockIn = null;
    public $editClockOut = null;
    public $editNotes = null;
    public $editHasBreak = false;
    public $editBreakStart = '';
    public $editBreakEnd = '';

    #[On('open-log-detail')]
    public function open($logId)
    {
        $this->logId = $logId;
        $this->show = true;
        $this->noteDraft = '';
        $this->attachmentDrafts = [];
        $this->showDisapproveInput = false;
        $this->showActionRequestInput = false;
        $this->showCopyForm = false;
        $this->showEditForm = false;
        $this->loadLog();
    }

    public function close()
    {
        $this->show = false;
        $this->logId = null;
        $this->log = null;
        $this->noteDraft = '';
        $this->attachmentDrafts = [];
        $this->showDisapproveInput = false;
        $this->showActionRequestInput = false;
        $this->showCopyForm = false;
        $this->showEditForm = false;
    }

    public function loadLog()
    {
        if (!$this->logId) return;

        $l = TimeLog::with(['assignment', 'task', 'employee', 'approver', 'rejecter'])
            ->find($this->logId);

        if (!$l) {
            $this->show = false;
            return;
        }

        $user = Auth::user();
        if ($user->id !== $l->employee_id && !$user->canManageEmployee($l->employee_id) && !$user->canAccessAdmin()) {
            $this->show = false;
            $this->toast('Unauthorized to view this time log.', 'error');
            return;
        }

        $hourlyRate = $l->hourly_rate !== null ? (float)$l->hourly_rate : ($l->task ? $l->task->getHourlyRateFor($l->employee) : ($l->employee?->base_pay ?: null));

        $userTz = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();
        $clockInCarbon = $l->getClockInCarbon();
        $clockOutCarbon = $l->getClockOutCarbon();

        $clockInUser = $clockInCarbon ? $clockInCarbon->copy()->setTimezone($userTz) : null;
        $clockOutUser = $clockOutCarbon ? $clockOutCarbon->copy()->setTimezone($userTz) : null;
        $approvedAtUser = $l->approved_at ? $l->approved_at->copy()->setTimezone($userTz) : null;
        $rejectedAtUser = $l->rejected_at ? $l->rejected_at->copy()->setTimezone($userTz) : null;

        $breaksList = [];
        if (!empty($l->breaks) && is_array($l->breaks)) {
            foreach ($l->breaks as $b) {
                $start = isset($b['start']) ? Carbon::parse($b['start'])->setTimezone($userTz) : null;
                $end   = isset($b['end']) ? Carbon::parse($b['end'])->setTimezone($userTz) : null;
                $mins  = ($start && $end) ? (int) round(abs($end->getTimestamp() - $start->getTimestamp()) / 60) : ($b['minutes'] ?? 0);
                $breaksList[] = [
                    'start_formatted' => $start ? $start->format('g:i A') : '—',
                    'end_formatted'   => $end ? $end->format('g:i A') : '—',
                    'minutes'         => $mins,
                ];
            }
        } elseif ($l->break_minutes && $l->break_minutes > 0) {
            $start = $l->getBreakStartedAtCarbon() ? $l->getBreakStartedAtCarbon()->copy()->setTimezone($userTz)->format('g:i A') : '—';
            $breaksList[] = [
                'start_formatted' => $start,
                'end_formatted'   => '—',
                'minutes'         => (int) $l->break_minutes,
            ];
        }

        $completedBreakMins = (int) round($l->completedBreakSeconds() / 60);
        $payableHours = (($l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60))) / 60;

        $this->log = [
            'id'                     => $l->id,
            'employee_id'            => $l->employee_id,
            'employee'               => $l->employee->name ?? '—',
            'assignment_id'          => $l->assignment_id,
            'assignment'             => $l->assignment->name ?? '—',
            'task_id'                => $l->task_id,
            'task'                   => $l->task->name ?? '—',
            'hourly_rate'            => $hourlyRate,
            'task_gps_policy'        => $l->task?->gps_policy ?? 'none',
            'task_radius'            => $l->task?->geofence_radius ?? 200,
            'clock_in'               => $clockInUser ? $clockInUser->format('m/d/Y g:i A') : '—',
            'clock_in_raw'           => $clockInUser ? $clockInUser->format('Y-m-d\TH:i') : null,
            'clock_out'              => $clockOutUser ? $clockOutUser->format('m/d/Y g:i A') : null,
            'clock_out_raw'          => $clockOutUser ? $clockOutUser->format('Y-m-d\TH:i') : null,
            'break_minutes'          => $completedBreakMins,
            'breaks'                 => $breaksList,
            'duration'               => number_format($payableHours, 2),
            'status'                 => $l->status,
            'approved'               => (bool)$l->approved,
            'is_rejected'            => $l->status === 'rejected' || (method_exists($l, 'isRejected') && $l->isRejected()),
            'approved_by'            => $l->approver->name ?? null,
            'approved_at'            => $approvedAtUser ? $approvedAtUser->format('m/d/Y g:i A') : null,
            'is_manual'              => $l->is_manual || $l->isManual(),
            'is_unusual'             => (bool)$l->is_unusual,
            'unusual_reason'         => $l->unusual_reason,
            'location_flagged'       => (bool)$l->location_flagged,
            'location_flag_reason'   => $l->location_flag_reason,
            'clock_in_latitude'      => $l->clock_in_latitude,
            'clock_in_longitude'     => $l->clock_in_longitude,
            'clock_out_latitude'     => $l->clock_out_latitude,
            'clock_out_longitude'    => $l->clock_out_longitude,
            'rejection_reason'       => $l->rejection_reason,
            'rejected_by'            => $l->rejecter->name ?? null,
            'rejected_at'            => $rejectedAtUser ? $rejectedAtUser->format('m/d/Y g:i A') : null,
            'action_requested'       => (bool)$l->action_requested,
            'action_requested_notes' => $l->action_requested_notes,
            'is_archived'            => (bool)$l->is_archived,
            'archived_at'            => $l->archived_at?->format('m/d/Y g:i A'),
            'is_locked'              => $l->isLocked(),
            'notes'                  => $l->notes,
        ];

        $this->editAudits = AuditLog::where('auditable_type', TimeLog::class)
            ->where('auditable_id', $l->id)
            ->where('action', 'Edit')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($a) => [
                'user_name'  => $a->user_name,
                'created_at' => $a->created_at ? $a->created_at->format('m/d/Y \@ g:i A') : '—',
                'changes'    => $a->changes,
            ])
            ->toArray();
    }

    // =========================================================================
    // COPY OPERATIONS
    // =========================================================================

    public function openCopy()
    {
        $this->showCopyForm = true;
        $this->showEditForm = false;
        $this->copyTargetDate = Carbon::tomorrow()->toDateString();
        $this->copyNotes = '';
        $this->copyAttachments = [];
    }

    public function cancelCopy()
    {
        $this->showCopyForm = false;
        $this->copyNotes = '';
        $this->copyAttachments = [];
    }

    public function confirmCopy()
    {
        $this->validate([
            'copyTargetDate'    => 'required|date',
            'copyNotes'         => 'required|string|min:3',
            'copyAttachments.*' => 'nullable|file|max:10240',
        ]);

        $sourceLog = TimeLog::find($this->logId);
        if (!$sourceLog) {
            $this->toast('Source entry not found.', 'error');
            return;
        }

        $sourceIn  = $sourceLog->clock_in;
        $sourceOut = $sourceLog->clock_out;

        $targetDate  = Carbon::parse($this->copyTargetDate);
        $newClockIn  = $targetDate->copy()->setTime($sourceIn->hour, $sourceIn->minute, $sourceIn->second);
        $newClockOut = $sourceOut
            ? $targetDate->copy()->setTime($sourceOut->hour, $sourceOut->minute, $sourceOut->second)
            : null;

        $newLog = TimeLog::create([
            'employee_id'          => Auth::id(),
            'assignment_id'        => $sourceLog->assignment_id,
            'task_id'              => $sourceLog->task_id,
            'clock_in'             => $newClockIn,
            'clock_out'            => $newClockOut,
            'duration_minutes'     => $sourceLog->duration_minutes,
            'status'               => 'manual',
            'notes'                => '[Copied from Log #' . $sourceLog->id . ' on ' . $sourceIn->toDateString() . '] ' . $this->copyNotes,
            'approved'             => false,
            'is_unusual'           => true,
            'unusual_reason'       => 'Copied manual time log requires supervisor approval',
        ]);

        TimeLogNote::create([
            'time_log_id' => $newLog->id,
            'user_id'     => Auth::id(),
            'body'        => 'Copied time entry: ' . $this->copyNotes,
        ]);

        if (!empty($this->copyAttachments)) {
            foreach ($this->copyAttachments as $file) {
                $path = $file->store('time_log_attachments', 'public');
                TimeLogAttachment::create([
                    'time_log_id'   => $newLog->id,
                    'user_id'       => Auth::id(),
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        \App\Services\TimeLogComplianceService::processTimeLogSubmission($newLog);

        $this->showCopyForm = false;
        $this->toast('Time entry copied successfully (submitted for approval).', 'success');
        $this->dispatch('log-updated', logId: $newLog->id);
    }

    // =========================================================================
    // EDIT & DELETE OPERATIONS
    // =========================================================================

    public function openEdit()
    {
        $l = TimeLog::find($this->logId);
        $user = Auth::user();
        $isHrOrAdmin = $user && ($user->isAdmin() || $user->isHrManager());

        if (!$isHrOrAdmin && $l && $l->isLocked()) {
            $this->toast('Approved, withdrawn, or rejected entries are locked and cannot be edited by employees.', 'error');
            return;
        }

        $this->showEditForm = true;
        $this->showCopyForm = false;
        $this->editAssignment = $this->log['assignment_id'];
        $this->editTask = $this->log['task_id'];
        $this->editClockIn = $this->log['clock_in_raw'];
        $this->editClockOut = $this->log['clock_out_raw'];
        $this->editNotes = $this->log['notes'];

        $this->editHasBreak = !empty($l->breaks) || ((int)($l->break_minutes ?? 0) > 0);
        if (!empty($l->breaks) && is_array($l->breaks) && isset($l->breaks[0])) {
            $b0 = $l->breaks[0];
            $this->editBreakStart = isset($b0['start']) ? Carbon::parse($b0['start'])->format('H:i') : '';
            $this->editBreakEnd   = isset($b0['end']) ? Carbon::parse($b0['end'])->format('H:i') : '';
        } else {
            $this->editBreakStart = '';
            $this->editBreakEnd   = '';
        }
    }

    public function cancelEdit()
    {
        $this->showEditForm = false;
    }

    public function saveEdit()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $user = Auth::user();
        $isHrOrAdmin = $user && ($user->isAdmin() || $user->isHrManager());

        if (!$isHrOrAdmin && $l->isLocked()) {
            $this->toast('Approved, withdrawn, or rejected entries are locked and cannot be edited by employees.', 'error');
            return;
        }

        $this->validate([
            'editAssignment' => 'required|exists:tbl_assignments,id',
            'editTask'       => 'required|exists:tbl_tasks,id',
            'editClockIn'    => 'required|date',
            'editClockOut'   => 'nullable|date|after:editClockIn',
        ]);

        $in  = Carbon::parse($this->editClockIn);
        $out = $this->editClockOut ? Carbon::parse($this->editClockOut) : null;

        $grossMinutes = $out ? (int) ceil($in->diffInMinutes($out)) : 0;
        $breakMinutes = 0;
        $breaks = [];

        if ($this->editHasBreak && $this->editBreakStart && $this->editBreakEnd) {
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

        $oldJob = $l->assignment?->name ?? 'None';
        $oldTask = $l->task?->name ?? 'None';
        $oldIn = $l->clock_in?->format('Y-m-d H:i');
        $oldOut = $l->clock_out?->format('Y-m-d H:i');
        $oldNotes = $l->notes;
        $oldBreak = (int)($l->break_minutes ?? 0);

        $l->assignment_id    = $this->editAssignment;
        $l->task_id          = $this->editTask;
        $l->clock_in         = $in;
        $l->clock_out        = $out;
        $l->duration_minutes = $durationMinutes;
        $l->break_minutes    = $breakMinutes;
        $l->breaks           = $breaks;
        $l->notes            = $this->editNotes;
        if ($isHrOrAdmin) {
            $l->approved    = true;
            $l->approved_by = $user->id;
            $l->approved_at = now();
            $l->status      = 'manual';
            $l->action_requested = false;
        } else {
            $l->approved         = false;
            $l->status           = 'manual';
            $l->is_manual        = true;
            $l->action_requested = false;
        }
        $l->save();

        $newJob = Assignment::find($this->editAssignment)?->name ?? 'None';
        $newTask = Task::find($this->editTask)?->name ?? 'None';
        $newIn = $in->format('Y-m-d H:i');
        $newOut = $out?->format('Y-m-d H:i');
        $newNotes = $this->editNotes;

        $changes = [];
        if ($oldJob !== $newJob) $changes['Job'] = ['old' => $oldJob, 'new' => $newJob];
        if ($oldTask !== $newTask) $changes['Task'] = ['old' => $oldTask, 'new' => $newTask];
        if ($oldIn !== $newIn) $changes['Clock In'] = ['old' => $oldIn, 'new' => $newIn];
        if ($oldOut !== $newOut) $changes['Clock Out'] = ['old' => $oldOut, 'new' => $newOut];
        if ($oldNotes !== $newNotes) $changes['Notes'] = ['old' => $oldNotes, 'new' => $newNotes];
        if ($oldBreak !== $breakMinutes) $changes['Break'] = ['old' => "{$oldBreak}m", 'new' => "{$breakMinutes}m"];

        AuditLog::record(
            "Edited time entry for " . ($l->employee?->name ?? 'Employee') . " on " . $in->format('m/d/Y'),
            $changes,
            $l,
            'Edit'
        );

        TimeLogNote::create([
            'time_log_id' => $l->id,
            'user_id'     => Auth::id(),
            'body'        => 'Timesheet entry updated by ' . Auth::user()->name . '.',
        ]);

        $this->showEditForm = false;
        $this->loadLog();
        $this->toast('Time entry updated successfully.', 'success');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function deleteLog()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $user = Auth::user();
        $isHrOrAdmin = $user && ($user->isAdmin() || $user->isHrManager() || $user->isSuperAdmin());

        if (!$isHrOrAdmin && $l->isLocked()) {
            $this->toast('Approved, withdrawn, or rejected entries are locked and cannot be deleted.', 'error');
            return;
        }

        $l->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);
        TimeLog::where('parent_log_id', $l->id)->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);

        AuditLog::record(
            "Archived time entry for " . ($l->employee?->name ?? 'Employee') . " on " . ($l->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status' => ['old' => $l->status, 'new' => 'archived'],
            ],
            $l,
            'Archive'
        );

        $id = $l->id;
        $this->close();
        $this->toast('Time entry archived successfully.', 'info');
        $this->dispatch('log-updated', logId: $id);
    }

    public function rejectArchivedLog()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can reject archived records.');
        }

        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $l->update([
            'status'      => 'rejected',
            'approved'    => false,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ]);
        TimeLog::where('parent_log_id', $l->id)->update([
            'status'      => 'rejected',
            'approved'    => false,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ]);

        $this->loadLog();
        $this->toast('Archived entry marked as rejected.', 'success');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function restoreArchivedLog()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore archived records.');
        }

        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $l->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);
        TimeLog::where('parent_log_id', $l->id)->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);

        $this->loadLog();
        $this->toast('Time entry restored successfully.', 'success');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function permanentlyDeleteLog()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can permanently delete records.');
        }

        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $id = $l->id;
        TimeLog::where('parent_log_id', $l->id)->delete();
        $l->logNotes()->delete();
        $l->logAttachments()->delete();
        $l->delete();

        $this->close();
        $this->toast('Time entry and any linked child records permanently deleted.', 'info');
        $this->dispatch('log-updated', logId: $id);
    }

    // =========================================================================
    // HR APPROVAL & ACTION REQUESTS
    // =========================================================================

    public function approve()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $oldStatus = $l->status;
        $l->approved         = true;
        $l->status           = 'approved';
        $l->approved_by      = Auth::id();
        $l->approved_at      = now();
        $l->rejected_by      = null;
        $l->rejected_at      = null;
        $l->rejection_reason = null;
        $l->action_requested = false;
        $l->save();

        AuditLog::record(
            "Approved timesheet entry for " . ($l->employee?->name ?? 'Employee') . " on " . ($l->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status'   => ['old' => $oldStatus, 'new' => 'approved'],
                'Approved' => ['old' => 'No', 'new' => 'Yes'],
            ],
            $l,
            'Approval'
        );

        try {
            $emp = User::find($l->employee_id);
            if ($emp && $emp->id !== Auth::id()) {
                $hours = $l->clock_out ? $l->clock_in->diffInMinutes($l->clock_out) / 60 : 0;
                $emp->notify(new \App\Notifications\TimesheetApprovedNotification(
                    $l->clock_in ? $l->clock_in->format('M d, Y') : 'Recent',
                    Auth::user()->name,
                    $hours
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to send timesheet approved notification: " . $e->getMessage());
        }

        $this->showDisapproveInput = false;
        $this->showActionRequestInput = false;
        $this->loadLog();
        $this->toast('Timesheet approved.', 'success');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function openDisapprove()
    {
        $this->showDisapproveInput = true;
        $this->showActionRequestInput = false;
        $this->disapprovalReason = '';
    }

    public function cancelDisapprove()
    {
        $this->showDisapproveInput = false;
        $this->disapprovalReason = '';
    }

    public function confirmDisapprove()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $reason = trim($this->disapprovalReason) ?: 'Time entry rejected by supervisor/HR.';
        $oldStatus = $l->status;

        $l->approved         = false;
        $l->status           = 'rejected';
        $l->rejected_by      = Auth::id();
        $l->rejected_at      = now();
        $l->rejection_reason = $reason;
        $l->save();

        AuditLog::record(
            "Disapproved timesheet entry for " . ($l->employee?->name ?? 'Employee') . " on " . ($l->clock_in?->format('m/d/Y') ?? 'Recent') . " (Reason: {$reason})",
            [
                'Status'           => ['old' => $oldStatus, 'new' => 'rejected'],
                'Rejection Reason' => ['old' => null, 'new' => $reason],
            ],
            $l,
            'Reject'
        );

        try {
            $emp = User::find($l->employee_id);
            if ($emp && $emp->id !== Auth::id()) {
                $emp->notify(new \App\Notifications\TimesheetDisapprovedNotification(
                    $l->clock_in ? $l->clock_in->format('M d, Y') : 'Recent',
                    Auth::user()->name,
                    $reason
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to send timesheet disapproved notification: " . $e->getMessage());
        }

        $this->showDisapproveInput = false;
        $this->disapprovalReason = '';
        $this->loadLog();
        $this->toast('Time entry disapproved.', 'error');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function openRequestAction()
    {
        $this->showActionRequestInput = true;
        $this->showDisapproveInput = false;
        $this->actionRequestReason = '';
    }

    public function cancelRequestAction()
    {
        $this->showActionRequestInput = false;
        $this->actionRequestReason = '';
    }

    public function confirmRequestAction()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $this->validate([
            'actionRequestReason' => 'required|string|min:3|max:1000',
        ]);

        $reason = trim($this->actionRequestReason);
        $oldStatus = $l->status;
        $l->action_requested       = true;
        $l->action_requested_notes = $reason;
        $l->status                 = 'action_requested';
        $l->save();

        AuditLog::record(
            "Requested action from " . ($l->employee?->name ?? 'Employee') . " on time entry " . ($l->clock_in?->format('m/d/Y') ?? 'Recent') . ": {$reason}",
            [
                'Status'           => ['old' => $oldStatus, 'new' => 'action_requested'],
                'Action Requested' => ['old' => 'No', 'new' => 'Yes'],
                'Reason'           => ['old' => null, 'new' => $reason],
            ],
            $l,
            'Action Request'
        );

        TimeLogNote::create([
            'time_log_id' => $l->id,
            'user_id'     => Auth::id(),
            'body'        => '⚠️ [Action Requested by HR/Supervisor]: ' . $reason,
        ]);

        $this->showActionRequestInput = false;
        $this->actionRequestReason = '';
        $this->loadLog();
        $this->toast('Action requested from employee.', 'warning');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function acceptActionRequest()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $l->action_requested = false;
        $l->action_requested_notes = null;
        if ($l->status === 'action_requested') {
            $l->status = $l->is_manual ? 'manual' : 'completed';
        }
        $l->save();

        AuditLog::record(
            "Resolved action request for time entry on " . ($l->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status'           => ['old' => 'action_requested', 'new' => $l->status],
                'Action Requested' => ['old' => 'Yes', 'new' => 'No'],
            ],
            $l,
            'Action Resolved'
        );

        TimeLogNote::create([
            'time_log_id' => $l->id,
            'user_id'     => Auth::id(),
            'body'        => '✅ [Action Request Accepted]: ' . Auth::user()->name . ' accepted/resolved the requested action.',
        ]);

        $this->loadLog();
        $this->toast('Action request accepted and marked as resolved.', 'success');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function unapprove()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $oldStatus = $l->status;
        $l->approved    = false;
        $l->status      = 'completed';
        $l->approved_by = null;
        $l->approved_at = null;
        $l->save();

        AuditLog::record(
            "Unapproved timesheet entry for " . ($l->employee?->name ?? 'Employee') . " on " . ($l->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status'   => ['old' => $oldStatus, 'new' => 'completed'],
                'Approved' => ['old' => 'Yes', 'new' => 'No'],
            ],
            $l,
            'Unapprove'
        );

        $this->loadLog();
        $this->toast('Entry reset to pending.', 'info');
        $this->dispatch('log-updated', logId: $l->id);
    }

    // =========================================================================
    // DISCUSSION & NOTES
    // =========================================================================

    public function addNote()
    {
        $this->validate([
            'noteDraft' => 'required|string|min:1|max:2000',
        ]);

        if (!$this->logId) return;

        TimeLogNote::create([
            'time_log_id' => $this->logId,
            'user_id'     => Auth::id(),
            'body'        => trim($this->noteDraft),
        ]);

        // If employee responded and action was requested, clear action requested flag
        $l = TimeLog::find($this->logId);
        if ($l && $l->action_requested && $l->employee_id === Auth::id()) {
            $l->action_requested = false;
            $l->save();
        }

        $this->noteDraft = '';
        $this->loadLog();
        $this->toast('Comment posted.', 'success');
        $this->dispatch('log-updated', logId: $this->logId);
    }

    public function startReply($noteId)
    {
        $this->replyingToNoteId = $noteId;
        $this->replyDraft = '';
    }

    public function cancelReply()
    {
        $this->replyingToNoteId = null;
        $this->replyDraft = '';
    }

    public function addReply()
    {
        $this->validate([
            'replyDraft' => 'required|string|min:1|max:2000',
        ]);

        if (!$this->logId || !$this->replyingToNoteId) return;

        TimeLogNote::create([
            'time_log_id' => $this->logId,
            'user_id'     => Auth::id(),
            'parent_id'   => $this->replyingToNoteId,
            'body'        => trim($this->replyDraft),
        ]);

        $this->replyingToNoteId = null;
        $this->replyDraft = '';
        $this->loadLog();
        $this->toast('Reply posted.', 'success');
        $this->dispatch('log-updated', logId: $this->logId);
    }

    public function deleteNote($id)
    {
        $note = TimeLogNote::find($id);
        if ($note && ($note->user_id === Auth::id() || Auth::user()->canAccessAdmin())) {
            $note->delete();
            $this->loadLog();
            $this->toast('Note deleted.', 'info');
            $this->dispatch('log-updated', logId: $this->logId);
        }
    }

    public function uploadAttachments()
    {
        if (!$this->logId) return;

        if (empty($this->attachmentDrafts)) {
            $this->toast('No files selected.', 'error');
            return;
        }

        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        foreach ($this->attachmentDrafts as $file) {
            $path = $file->store("time-log-attachments/{$this->logId}", 'public');

            TimeLogAttachment::create([
                'time_log_id'   => $this->logId,
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $this->attachmentDrafts = [];
        $this->loadLog();
        $this->toast('Files uploaded.', 'success');
        $this->dispatch('log-updated', logId: $this->logId);
    }

    public function deleteAttachment($id)
    {
        $att = TimeLogAttachment::find($id);
        if ($att && $att->user_id === Auth::id()) {
            Storage::disk('public')->delete($att->path);
            $att->delete();
            $this->loadLog();
            $this->toast('Attachment deleted.', 'info');
            $this->dispatch('log-updated', logId: $this->logId);
        }
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function render()
    {
        $notes = collect();
        $attachments = collect();
        $assignments = Assignment::orderBy('name')->get();
        $tasks = Task::orderBy('name')->get();

        if ($this->logId) {
            $l = TimeLog::find($this->logId);
            if ($l) {
                $notes = $l->logNotes()
                    ->whereNull('parent_id')
                    ->with(['user', 'replies.user'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn ($n) => [
                        'id'         => $n->id,
                        'body'       => $n->body,
                        'user'       => $n->user->name ?? '—',
                        'user_id'    => $n->user_id,
                        'created_at' => $n->created_at->diffForHumans(),
                        'replies'    => $n->replies->map(fn ($r) => [
                            'id'         => $r->id,
                            'body'       => $r->body,
                            'user'       => $r->user->name ?? '—',
                            'user_id'    => $r->user_id,
                            'created_at' => $r->created_at->diffForHumans(),
                        ]),
                    ]);
                $attachments = $l->logAttachments()->get()->map(fn ($a) => [
                    'id'      => $a->id,
                    'name'    => $a->original_name,
                    'size'    => $a->humanSize(),
                    'url'     => $a->url(),
                    'user_id' => $a->user_id,
                ]);
            }
        }

        return view('livewire.time-log-detail', [
            'notes'       => $notes,
            'attachments' => $attachments,
            'assignments' => $assignments,
            'tasks'       => $tasks,
        ]);
    }
}
