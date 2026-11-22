<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\User; // Added User model
use App\Models\AuditLog;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ApproveTimesheets extends Component
{
    public $filter = 'pending';       // all | pending | approved | rejected
    public $fromDate = null;
    public $toDate = null;
    public $assignmentFilter = null;

    public $employeeFilter = null;
    public $search = '';

    public $selected = [];            // selected log IDs for bulk
    public $selectAll = false;

    // Disapproval Workflow (Requirement 2 & 3)
    public $showDisapproveModal = false;
    public $disapprovingLogId = null;
    public $disapprovalReason = '';
    public $isBulkDisapprove = false;

    public $toastMessage = null;
    public $toastType = 'success';
    public $viewMode = 'summary'; // 'summary' (ClockShark Pay Period Summary default) | 'entries'
    public $payPeriodOffset = 0;

    public function mount()
    {
        $user = Auth::user();
        if (!$user->canApproveTimesheets()) {
            abort(403, 'Unauthorized to access timesheet approvals.');
        }

        $this->syncPayPeriodDates();
    }

    public function syncPayPeriodDates(): void
    {
        $weekStartDay = CompanySetting::getWeekStartDay();
        $anchor = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->payPeriodOffset * 2);
        $this->fromDate = $anchor->toDateString();
        $this->toDate   = $anchor->copy()->addDays(13)->toDateString();
    }

    public function previousPayPeriod(): void
    {
        $this->payPeriodOffset--;
        $this->syncPayPeriodDates();
    }

    public function nextPayPeriod(): void
    {
        $this->payPeriodOffset++;
        $this->syncPayPeriodDates();
    }

    public function currentPayPeriod(): void
    {
        $this->payPeriodOffset = 0;
        $this->syncPayPeriodDates();
    }

    public function selectPayPeriodPreset(int $offset): void
    {
        $this->payPeriodOffset = $offset;
        $this->syncPayPeriodDates();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->baseQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function clearFilters()
    {
        $this->filter = 'all';
        $this->fromDate = null;
        $this->toDate = null;
        $this->assignmentFilter = null;
        $this->employeeFilter = null;
        $this->search = '';
    }

    public function approveSelected()
    {
        if (!Auth::user()->canApproveTimesheets()) {
            abort(403, 'Unauthorized to approve timesheets.');
        }

        if (empty($this->selected)) {
            $this->toast('No rows selected.', 'error');
            return;
        }

        $accessibleIds = Auth::user()->getAccessibleEmployeeIds();
        $query = TimeLog::whereIn('id', $this->selected);
        if ($accessibleIds !== null) {
            $query->whereIn('employee_id', $accessibleIds);
        }

        $logs = $query->get();

        foreach ($logs as $log) {
            $log->update([
                'approved'         => true,
                'approved_by'      => Auth::id(),
                'approved_at'      => now(),
                'rejected_by'      => null,
                'rejected_at'      => null,
                'rejection_reason' => null,
            ]);

            AuditLog::record(
                "Bulk approved timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
                [
                    'Status'   => ['old' => 'pending', 'new' => 'approved'],
                    'Approved' => ['old' => 'No', 'new' => 'Yes'],
                ],
                $log,
                'Approval'
            );

            // Notify employee (Requirement 3)
            try {
                $emp = User::find($log->employee_id);
                if ($emp && $emp->id !== Auth::id()) {
                    $hours = $log->clock_out ? $log->clock_in->diffInMinutes($log->clock_out) / 60 : 0;
                    $emp->notify(new \App\Notifications\TimesheetApprovedNotification(
                        $log->clock_in ? $log->clock_in->format('M d, Y') : 'Recent',
                        Auth::user()->name,
                        $hours
                    ));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send timesheet approved notification: " . $e->getMessage());
            }
        }

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->toast("Approved {$count} entries and notified employees.");
    }

    public function approveOne($id)
    {
        if (!Auth::user()->canApproveTimesheets()) {
            abort(403, 'Unauthorized to approve timesheets.');
        }

        $log = TimeLog::findOrFail($id);
        if (!Auth::user()->canManageEmployee($log->employee_id)) {
            abort(403, 'Unauthorized to approve timesheet for this employee.');
        }

        $log->update([
            'approved'         => true,
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
            'rejected_by'      => null,
            'rejected_at'      => null,
            'rejection_reason' => null,
        ]);

        AuditLog::record(
            "Approved timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status'   => ['old' => 'pending', 'new' => 'approved'],
                'Approved' => ['old' => 'No', 'new' => 'Yes'],
            ],
            $log,
            'Approval'
        );

        try {
            $emp = User::find($log->employee_id);
            if ($emp && $emp->id !== Auth::id()) {
                $hours = $log->clock_out ? $log->clock_in->diffInMinutes($log->clock_out) / 60 : 0;
                $emp->notify(new \App\Notifications\TimesheetApprovedNotification(
                    $log->clock_in ? $log->clock_in->format('M d, Y') : 'Recent',
                    Auth::user()->name,
                    $hours
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to send timesheet notification: " . $e->getMessage());
        }

        $this->toast('Timesheet entry approved.');
    }

    public function openDisapproveModal($id = null)
    {
        if ($id) {
            $this->disapprovingLogId  = $id;
            $this->isBulkDisapprove   = false;
        } else {
            if (empty($this->selected)) {
                $this->toast('No entries selected to disapprove.', 'error');
                return;
            }
            $this->disapprovingLogId  = null;
            $this->isBulkDisapprove   = true;
        }

        $this->disapprovalReason   = '';
        $this->showDisapproveModal = true;
    }

    public function cancelDisapprove()
    {
        $this->showDisapproveModal = false;
        $this->disapprovingLogId   = null;
        $this->disapprovalReason   = '';
        $this->isBulkDisapprove    = false;
    }

    public function confirmDisapprove()
    {
        if (!Auth::user()->canApproveTimesheets()) {
            abort(403, 'Unauthorized.');
        }

        $reason = trim($this->disapprovalReason) ?: 'Time entry rejected by supervisor/HR.';

        if ($this->isBulkDisapprove) {
            $accessibleIds = Auth::user()->getAccessibleEmployeeIds();
            $query = TimeLog::whereIn('id', $this->selected);
            if ($accessibleIds !== null) {
                $query->whereIn('employee_id', $accessibleIds);
            }

            $logs = $query->get();
            foreach ($logs as $log) {
                $log->update([
                    'approved'         => false,
                    'status'           => 'rejected',
                    'rejected_by'      => Auth::id(),
                    'rejected_at'      => now(),
                    'rejection_reason' => $reason,
                ]);

                AuditLog::record(
                    "Bulk disapproved timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent') . " (Reason: {$reason})",
                    [
                        'Status'           => ['old' => 'pending', 'new' => 'rejected'],
                        'Rejection Reason' => ['old' => null, 'new' => $reason],
                    ],
                    $log,
                    'Reject'
                );

                try {
                    $emp = User::find($log->employee_id);
                    if ($emp && $emp->id !== Auth::id()) {
                        $emp->notify(new \App\Notifications\TimesheetDisapprovedNotification(
                            $log->clock_in ? $log->clock_in->format('M d, Y') : 'Recent',
                            Auth::user()->name,
                            $reason
                        ));
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to notify disapproval: " . $e->getMessage());
                }
            }

            $count = count($this->selected);
            $this->selected = [];
            $this->selectAll = false;
            $this->cancelDisapprove();
            $this->toast("Disapproved {$count} entries and notified employees.", 'error');
        } else {
            $log = TimeLog::findOrFail($this->disapprovingLogId);
            if (!Auth::user()->canManageEmployee($log->employee_id)) {
                abort(403, 'Unauthorized.');
            }

            $log->update([
                'approved'         => false,
                'status'           => 'rejected',
                'rejected_by'      => Auth::id(),
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLog::record(
                "Disapproved timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent') . " (Reason: {$reason})",
                [
                    'Status'           => ['old' => 'pending', 'new' => 'rejected'],
                    'Rejection Reason' => ['old' => null, 'new' => $reason],
                ],
                $log,
                'Reject'
            );

            try {
                $emp = User::find($log->employee_id);
                if ($emp && $emp->id !== Auth::id()) {
                    $emp->notify(new \App\Notifications\TimesheetDisapprovedNotification(
                        $log->clock_in ? $log->clock_in->format('M d, Y') : 'Recent',
                        Auth::user()->name,
                        $reason
                    ));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to notify disapproval: " . $e->getMessage());
            }

            $this->cancelDisapprove();
            $this->toast('Time entry disapproved and employee notified.', 'error');
        }
    }

    public function unapproveSelected()
    {
        if (!Auth::user()->canApproveTimesheets()) {
            abort(403, 'Unauthorized to unapprove timesheets.');
        }

        if (empty($this->selected)) {
            $this->toast('No rows selected.', 'error');
            return;
        }

        $accessibleIds = Auth::user()->getAccessibleEmployeeIds();
        $query = TimeLog::whereIn('id', $this->selected);
        if ($accessibleIds !== null) {
            $query->whereIn('employee_id', $accessibleIds);
        }

        $logs = $query->get();
        foreach ($logs as $log) {
            $log->update([
                'approved'         => false,
                'approved_by'      => null,
                'approved_at'      => null,
                'rejected_by'      => null,
                'rejected_at'      => null,
                'rejection_reason' => null,
            ]);

            AuditLog::record(
                "Reset approval on timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
                [
                    'Status'   => ['old' => 'approved', 'new' => 'pending'],
                    'Approved' => ['old' => 'Yes', 'new' => 'No'],
                ],
                $log,
                'Unapprove'
            );
        }

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->toast("Reset approval on {$count} entries.");
    }

    public function unapproveOne($id)
    {
        if (!Auth::user()->canApproveTimesheets()) {
            abort(403, 'Unauthorized to unapprove timesheets.');
        }

        $log = TimeLog::findOrFail($id);
        if (!Auth::user()->canManageEmployee($log->employee_id)) {
            abort(403, 'Unauthorized to unapprove timesheet for this employee.');
        }

        $log->update([
            'approved'         => false,
            'approved_by'      => null,
            'approved_at'      => null,
            'rejected_by'      => null,
            'rejected_at'      => null,
            'rejection_reason' => null,
        ]);

        AuditLog::record(
            "Reset approval on timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
            [
                'Status'   => ['old' => 'approved', 'new' => 'pending'],
                'Approved' => ['old' => 'Yes', 'new' => 'No'],
            ],
            $log,
            'Unapprove'
        );

        $this->toast('Entry status reset to pending.');
    }

    public function openDetail($id)
    {
        $this->dispatch('open-log-detail', logId: $id);
    }

    private function baseQuery()
    {
        $user = Auth::user();
        $q = TimeLog::query()->notArchived()->whereNotNull('clock_out');

        $accessibleIds = $user->getAccessibleEmployeeIds();
        if ($accessibleIds !== null) {
            $q->whereIn('employee_id', $accessibleIds);
        }

        if ($this->filter === 'pending') {
            $q->where('approved', false)->whereNull('rejected_at')->where('status', '!=', 'rejected');
        } elseif ($this->filter === 'approved') {
            $q->where('approved', true);
        } elseif ($this->filter === 'rejected') {
            $q->where(function ($sub) {
                $sub->whereNotNull('rejected_at')->orWhere('status', 'rejected');
            });
        }

        if ($this->fromDate) {
            $q->where('clock_in', '>=', Carbon::parse($this->fromDate)->startOfDay());
        }
        if ($this->toDate) {
            $q->where('clock_in', '<=', Carbon::parse($this->toDate)->endOfDay());
        }
        if ($this->assignmentFilter) {
            $q->where('assignment_id', $this->assignmentFilter);
        }

        // Added employee filter logic
        if ($this->employeeFilter) {
            $q->where('employee_id', $this->employeeFilter);
        }

        // Added search logic for Job, Task, or Employee
        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $q->where(function ($w) use ($term) {
                $w->whereHas('employee', fn ($e) => $e->where('name', 'like', $term))
                    ->orWhereHas('assignment', fn ($a) => $a->where('name', 'like', $term))
                    ->orWhereHas('task', fn ($t) => $t->where('name', 'like', $term));
            });
        }

        return $q->orderByDesc('clock_in');
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function deleteOne($id)
    {
        $user = Auth::user();
        if (!$user->canApproveTimesheets()) {
            abort(403, 'Unauthorized to delete timesheets.');
        }

        $log = TimeLog::findOrFail($id);
        if (!$user->canManageEmployee($log->employee_id) && !$user->canAccessAdmin()) {
            abort(403, 'Unauthorized to delete this timesheet entry.');
        }

        AuditLog::record(
            "Archived timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
            ['id' => $log->id],
            null,
            'Archive'
        );

        $log->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);
        $this->toast('Timesheet entry archived.', 'info');
    }

    public function deleteSelected()
    {
        $user = Auth::user();
        if (!$user->canApproveTimesheets()) {
            abort(403, 'Unauthorized to delete timesheets.');
        }

        if (empty($this->selected)) {
            $this->toast('No rows selected.', 'error');
            return;
        }

        $accessibleIds = $user->getAccessibleEmployeeIds();
        $query = TimeLog::whereIn('id', $this->selected);
        if ($accessibleIds !== null) {
            $query->whereIn('employee_id', $accessibleIds);
        }

        $logs = $query->get();
        $deleted = 0;
        foreach ($logs as $log) {
            TimeLog::where('parent_log_id', $log->id)->delete();
            AuditLog::record(
                "Bulk deleted timesheet entry for " . ($log->employee?->name ?? 'Employee') . " on " . ($log->clock_in?->format('m/d/Y') ?? 'Recent'),
                ['id' => $log->id],
                null,
                'Delete'
            );
            $log->delete();
            $deleted++;
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->toast("Deleted {$deleted} time entries.", 'info');
    }

    public function approveEmployeePayPeriod($employeeId)
    {
        $user = Auth::user();
        if (!$user->canApproveTimesheets()) {
            abort(403, 'Unauthorized.');
        }

        $q = TimeLog::where('employee_id', $employeeId)->whereNotNull('clock_out');
        if ($this->fromDate) $q->where('clock_in', '>=', Carbon::parse($this->fromDate)->startOfDay());
        if ($this->toDate) $q->where('clock_in', '<=', Carbon::parse($this->toDate)->endOfDay());

        $logs = $q->get();
        foreach ($logs as $log) {
            $log->update([
                'approved'    => true,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'status'      => $log->is_manual ? 'manual' : 'completed',
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
        }

        $this->toast("Approved all pay period entries for employee.", 'success');
    }

    public function unapproveEmployeePayPeriod($employeeId)
    {
        $user = Auth::user();
        if (!$user->canApproveTimesheets()) {
            abort(403, 'Unauthorized.');
        }

        $q = TimeLog::where('employee_id', $employeeId)->whereNotNull('clock_out');
        if ($this->fromDate) $q->where('clock_in', '>=', Carbon::parse($this->fromDate)->startOfDay());
        if ($this->toDate) $q->where('clock_in', '<=', Carbon::parse($this->toDate)->endOfDay());

        $logs = $q->get();
        foreach ($logs as $log) {
            $log->update([
                'approved'    => false,
                'approved_by' => null,
                'approved_at' => null,
                'status'      => $log->is_manual ? 'manual' : 'completed',
            ]);
        }

        $this->toast("Reset pay period entries to pending.", 'info');
    }

    public function dismissToast() { $this->toastMessage = null; }

    public function render()
    {
        $user = Auth::user();
        $logs = $this->baseQuery()->with(['employee', 'assignment', 'task', 'approver'])->get();

        $accessibleIds = $user->getAccessibleEmployeeIds();
        $employeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }

        $payPeriodSummaries = collect();
        if ($this->viewMode === 'summary') {
            $accessibleEmployees = $employeesQuery->get();
            $from = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : Carbon::now()->startOfWeek(CompanySetting::getWeekStartDay());
            $to   = $this->toDate ? Carbon::parse($this->toDate)->endOfDay() : Carbon::now()->startOfWeek(CompanySetting::getWeekStartDay())->addDays(6)->endOfDay();

            foreach ($accessibleEmployees as $emp) {
                $empLogs = TimeLog::where('employee_id', $emp->id)
                    ->whereNotNull('clock_out')
                    ->whereBetween('clock_in', [$from, $to])
                    ->get();

                if ($empLogs->isEmpty()) {
                    continue;
                }

                $totalMinutes = $empLogs->sum(fn($l) => (int)($l->duration_minutes ?: ceil($l->netSeconds() / 60)));
                $totalHours = $totalMinutes / 60;

                // Simple regular/OT split: up to 40h reg, remainder OT
                $regHours = min(40.0, $totalHours);
                $otHours = max(0.0, min(20.0, $totalHours - 40.0));
                $dtHours = max(0.0, $totalHours - 60.0);

                // Time off in period
                $timeOffReqs = \App\Models\TimeOffRequest::where('employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->where(function($q) use ($from, $to) {
                        $q->whereBetween('start_at', [$from, $to])
                          ->orWhereBetween('end_at', [$from, $to]);
                    })
                    ->get();

                $paidOff = (float) $timeOffReqs->sum('paid_hours');
                $unpaidOff = (float) $timeOffReqs->sum('unpaid_hours');

                $allApproved = $empLogs->every(fn($l) => (bool)$l->approved);
                $hasPending  = $empLogs->contains(fn($l) => !$l->approved && !$l->isRejected());
                $hasRejected = $empLogs->contains(fn($l) => $l->isRejected());

                $statusLabel = 'Pending';
                $statusColor = 'amber';
                if ($allApproved) {
                    $statusLabel = 'All Approved';
                    $statusColor = 'emerald';
                } elseif ($hasRejected && !$hasPending) {
                    $statusLabel = 'Disapproved';
                    $statusColor = 'rose';
                } elseif ($hasPending && $empLogs->contains(fn($l) => $l->approved)) {
                    $statusLabel = 'Mixed';
                    $statusColor = 'blue';
                }

                $nameParts = preg_split('/\s+/', trim($emp->name));
                $firstName = $nameParts[0] ?? $emp->name;
                $lastName  = count($nameParts) > 1 ? end($nameParts) : '';

                $payPeriodSummaries->push([
                    'employee_id' => $emp->id,
                    'last_name'   => $lastName ?: $firstName,
                    'first_name'  => $lastName ? $firstName : '',
                    'full_name'   => $emp->name,
                    'reg'         => number_format($regHours, 2),
                    'ot'          => number_format($otHours, 2),
                    'dt'          => number_format($dtHours, 2),
                    'paid_off'    => number_format($paidOff, 2),
                    'unpaid_off'  => number_format($unpaidOff, 2),
                    'total'       => number_format($totalHours + $paidOff + $unpaidOff, 2),
                    'status'      => $statusLabel,
                    'status_color'=> $statusColor,
                    'all_approved'=> $allApproved,
                    'log_count'   => $empLogs->count(),
                ]);
            }
        }

        return view('livewire.approve-timesheets', [
            'logs'               => $logs,
            'assignments'        => Assignment::orderBy('name')->get(),
            'employees'          => $employeesQuery->get(),
            'payPeriodSummaries' => $payPeriodSummaries,
        ]);
    }
}
