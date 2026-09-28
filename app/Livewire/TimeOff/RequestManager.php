<?php

namespace App\Livewire\TimeOff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TimeOffRequest;
use App\Models\TimeOffRequestThread;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffPolicyEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RequestManager extends Component
{
    use WithPagination;

    // Filters
    public $status_filter = '';
    public $employee_filter = '';

    // Modals
    public $isModalOpen = false;
    public $isDecisionModalOpen = false;
    public $isDetailModalOpen = false;
    public $isAdjustmentModalOpen = false;

    // New Request Form Fields
    public $selected_employee_id = '';
    public $policy_id = '';
    public $is_full_day = true;
    public $default_daily_hours = 8.00;
    public $start_date = '';
    public $end_date = '';
    public $reason = '';
    public $days_breakdown = [];

    // Computed Hours
    public $available_hours = 0.00;
    public $total_requested_hours = 0.00;
    public $paid_hours = 0.00;
    public $unpaid_hours = 0.00;
    public $projected_balance = 0.00;
    public $is_over_balance = false;
    public $routed_manager_name = 'Administrators / HR';
    public $routed_manager_id = null;

    // Decision Modal
    public $activeRequestId = null;
    public $decisionAction = 'approved';
    public $decisionNotes = '';

    // Detail/Thread Review Drawer
    public $selectedRequest = null;
    public $newThreadComment = '';
    public $employeeLeaveHistory = [];
    public $employeeEntitlements = [];

    // In-Drawer Live Day Breakdown Editing
    public $detail_days_breakdown = [];
    public $is_detail_breakdown_dirty = false;

    // Direct HR Balance Adjustment
    public $adj_employee_id = null;
    public $adj_employee_name = '';
    public $adj_policy_allocations = [];
    public $adj_reason = '';

    public function mount()
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->selected_employee_id = Auth::id();
        $this->syncEmployeeContext();
    }

    public function updatedSelectedEmployeeId()
    {
        $this->syncEmployeeContext();
    }

    public function updatedPolicyId()
    {
        $this->syncAvailableBalance();
        $this->calculateBalances();
    }

    public function updatedIsFullDay($val)
    {
        $this->default_daily_hours = $val ? 8.00 : 4.00;
        $this->regenerateDaysBreakdown();
    }

    public function updatedDefaultDailyHours($val)
    {
        $this->default_daily_hours = (float)$val;
        foreach ($this->days_breakdown as &$day) {
            if (!$day['is_weekend'] && $day['type'] === 'full') {
                $day['hours'] = $this->default_daily_hours;
            }
        }
        $this->recalculateTotals();
    }

    public function updatedStartDate()
    {
        if (strtotime($this->end_date) < strtotime($this->start_date)) {
            $this->end_date = $this->start_date;
        }
        $this->regenerateDaysBreakdown();
    }

    public function updatedEndDate()
    {
        if (strtotime($this->end_date) < strtotime($this->start_date)) {
            $this->start_date = $this->end_date;
        }
        $this->regenerateDaysBreakdown();
    }

    public function updatedDaysBreakdown()
    {
        $this->recalculateTotals();
    }

    public function setDayType($index, $type)
    {
        if (!isset($this->days_breakdown[$index])) return;

        $this->days_breakdown[$index]['type'] = $type;
        if ($type === 'full') {
            $this->days_breakdown[$index]['hours'] = (float)$this->default_daily_hours;
        } elseif ($type === 'off') {
            $this->days_breakdown[$index]['hours'] = 0.00;
        } elseif ($type === 'partial' && (float)$this->days_breakdown[$index]['hours'] === 0.0) {
            $this->days_breakdown[$index]['hours'] = 4.00;
        }

        $this->recalculateTotals();
    }

    public function toggleDayUnpaid($index)
    {
        if (!isset($this->days_breakdown[$index])) return;

        $this->days_breakdown[$index]['is_unpaid'] = !$this->days_breakdown[$index]['is_unpaid'];
        $this->recalculateTotals();
    }

    public function syncEmployeeContext()
    {
        $emp = User::with('manager')->find($this->selected_employee_id);
        if ($emp) {
            if ($emp->manager) {
                $this->routed_manager_name = $emp->manager->name . ' (' . $emp->manager->role . ') & HR Managers';
                $this->routed_manager_id = $emp->manager->id;
            } else {
                $this->routed_manager_name = 'HR Managers & Company Admins';
                $this->routed_manager_id = null;
            }

            $firstAssigned = TimeOffPolicyEmployee::where('employee_id', $emp->id)->first();
            $this->policy_id = $firstAssigned ? (string)$firstAssigned->policy_id : '';
        } else {
            $this->policy_id = '';
        }

        $this->syncAvailableBalance();
        $this->regenerateDaysBreakdown();
    }

    public function syncAvailableBalance()
    {
        if ($this->selected_employee_id && $this->policy_id) {
            $balance = TimeOffPolicyEmployee::where('employee_id', $this->selected_employee_id)
                ->where('policy_id', $this->policy_id)
                ->first();

            $this->available_hours = $balance ? (float)$balance->hours_remaining : 0.00;
        } else {
            $this->available_hours = 0.00;
        }
    }

    public function regenerateDaysBreakdown()
    {
        $this->days_breakdown = [];

        if ($this->start_date && $this->end_date) {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);

            $current = $start->copy();
            while ($current->lte($end)) {
                $isWeekend = $current->isWeekend();
                $type = $isWeekend ? 'off' : ($this->is_full_day ? 'full' : 'partial');
                $hours = $isWeekend ? 0.00 : (float)$this->default_daily_hours;

                $this->days_breakdown[] = [
                    'date'       => $current->format('Y-m-d'),
                    'day_name'   => $current->format('l'),
                    'formatted'  => $current->format('M d, Y'),
                    'is_weekend' => $isWeekend,
                    'type'       => $type,
                    'is_unpaid'  => false,
                    'hours'      => $hours,
                ];
                $current->addDay();
            }
        }

        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $totalSum = 0;
        $paidSum = 0;
        $unpaidSum = 0;

        foreach ($this->days_breakdown as $item) {
            $hrs = (float)($item['hours'] ?? 0);
            $totalSum += $hrs;
            if (!empty($item['is_unpaid'])) {
                $unpaidSum += $hrs;
            } else {
                $paidSum += $hrs;
            }
        }

        $this->total_requested_hours = (float)$totalSum;
        $this->paid_hours            = (float)$paidSum;
        $this->unpaid_hours          = (float)$unpaidSum;

        $this->calculateBalances();
    }

    public function calculateBalances()
    {
        $this->projected_balance = (float)$this->available_hours - (float)$this->paid_hours;
        $this->is_over_balance = $this->projected_balance < 0;
    }

    public function openRequestModal()
    {
        $this->resetValidation();
        $this->selected_employee_id = Auth::id();
        $this->start_date = now()->addDay()->format('Y-m-d');
        $this->end_date = now()->addDay()->format('Y-m-d');
        $this->is_full_day = true;
        $this->default_daily_hours = 8.00;
        $this->reason = '';

        $this->syncEmployeeContext();
        $this->isModalOpen = true;
    }

    public function closeRequestModal()
    {
        $this->isModalOpen = false;
    }

    public function submitRequest()
    {
        $this->validate([
            'selected_employee_id' => 'required|exists:users,id',
            'policy_id'            => 'required|exists:time_off_policies,id',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'default_daily_hours'  => 'required|numeric|min:0.5|max:24',
            'reason'               => 'nullable|string|max:500',
            'days_breakdown.*.hours' => 'required|numeric|min:0|max:24',
        ]);

        $isEnrolled = TimeOffPolicyEmployee::where('employee_id', $this->selected_employee_id)
            ->where('policy_id', $this->policy_id)
            ->exists();

        if (!$isEnrolled) {
            $this->addError('policy_id', 'The employee is not assigned to this time off policy.');
            return;
        }

        $this->recalculateTotals();

        $request = TimeOffRequest::create([
            'employee_id'     => $this->selected_employee_id,
            'policy_id'       => $this->policy_id,
            'approver_id'     => $this->routed_manager_id,
            'is_full_day'     => $this->is_full_day,
            'daily_hours'     => $this->default_daily_hours,
            'daily_breakdown' => $this->days_breakdown,
            'start_at'        => Carbon::parse($this->start_date)->startOfDay(),
            'end_at'          => Carbon::parse($this->end_date)->endOfDay(),
            'hours'           => $this->total_requested_hours,
            'paid_hours'      => $this->paid_hours,
            'unpaid_hours'    => $this->unpaid_hours,
            'reason'          => $this->reason,
            'status'          => 'pending',
        ]);

        $creator = Auth::user()->name;
        $flagNote = $this->is_over_balance ? ' [WARNING: Over-Credit Requested]' : '';
        $paidNote = $this->unpaid_hours > 0 ? " ({$this->paid_hours}h paid, {$this->unpaid_hours}h unpaid)" : '';
        TimeOffRequestThread::create([
            'time_off_request_id' => $request->id,
            'user_id'             => Auth::id(),
            'type'                => 'system_event',
            'message'             => "Request filed by {$creator} for {$this->total_requested_hours} hrs{$paidNote}. Routed to {$this->routed_manager_name}.{$flagNote}",
        ]);

        if (!empty($this->reason)) {
            TimeOffRequestThread::create([
                'time_off_request_id' => $request->id,
                'user_id'             => Auth::id(),
                'type'                => 'message',
                'message'             => $this->reason,
            ]);
        }

        session()->flash('message', 'Time off request submitted.');
        $this->closeRequestModal();
    }

    public function withdrawRequest($id)
    {
        $req = TimeOffRequest::where('id', $id)
            ->where('employee_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $req->update(['status' => 'withdrawn']);

        TimeOffRequestThread::create([
            'time_off_request_id' => $req->id,
            'user_id'             => Auth::id(),
            'type'                => 'status_change',
            'message'             => 'Request was withdrawn by the employee.',
        ]);

        session()->flash('message', 'Request successfully withdrawn.');
    }

    public function openViewModal($id)
    {
        $this->selectedRequest = TimeOffRequest::with(['employee.manager', 'policy', 'approver', 'decidedByUser', 'threads.user'])
            ->findOrFail($id);

        $this->detail_days_breakdown = $this->selectedRequest->daily_breakdown ?? [];
        $this->is_detail_breakdown_dirty = false;

        $this->reloadEntitlementsAndHistory();
        $this->isDetailModalOpen = true;
    }

    public function canEditDetailBreakdown(): bool
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isHrManager()) {
            return true;
        }

        // Employee can edit if request is pending
        if ($user->id === $this->selectedRequest->employee_id && $this->selectedRequest->status === 'pending') {
            return true;
        }

        return false;
    }

    public function setDetailDayType($index, $type)
    {
        if (!$this->canEditDetailBreakdown()) return;

        $this->detail_days_breakdown[$index]['type'] = $type;
        if ($type === 'full') {
            $this->detail_days_breakdown[$index]['hours'] = 8.00;
        } elseif ($type === 'off') {
            $this->detail_days_breakdown[$index]['hours'] = 0.00;
        } elseif ($type === 'partial' && (float)$this->detail_days_breakdown[$index]['hours'] === 0.0) {
            $this->detail_days_breakdown[$index]['hours'] = 4.00;
        }

        $this->is_detail_breakdown_dirty = true;
    }

    public function toggleDetailDayUnpaid($index)
    {
        if (!$this->canEditDetailBreakdown()) return;

        $this->detail_days_breakdown[$index]['is_unpaid'] = !($this->detail_days_breakdown[$index]['is_unpaid'] ?? false);
        $this->is_detail_breakdown_dirty = true;
    }

    public function updatedDetailDaysBreakdown()
    {
        $this->is_detail_breakdown_dirty = true;
    }

    public function saveDetailBreakdownChanges()
    {
        if (!$this->canEditDetailBreakdown()) {
            session()->flash('error', 'Unauthorized to modify daily hours.');
            return;
        }

        $totalSum = 0;
        $paidSum = 0;
        $unpaidSum = 0;

        foreach ($this->detail_days_breakdown as $item) {
            $hrs = (float)($item['hours'] ?? 0);
            $totalSum += $hrs;
            if (!empty($item['is_unpaid'])) {
                $unpaidSum += $hrs;
            } else {
                $paidSum += $hrs;
            }
        }

        $oldTotal = (float)$this->selectedRequest->hours;
        $this->selectedRequest->update([
            'daily_breakdown' => $this->detail_days_breakdown,
            'hours'           => $totalSum,
            'paid_hours'      => $paidSum,
            'unpaid_hours'    => $unpaidSum,
        ]);

        $user = Auth::user();
        TimeOffRequestThread::create([
            'time_off_request_id' => $this->selectedRequest->id,
            'user_id'             => $user->id,
            'type'                => 'status_change',
            'message'             => "Daily hours breakdown updated by {$user->name} ({$user->role}). Total adjusted from {$oldTotal}h to {$totalSum}h ({$paidSum}h paid, {$unpaidSum}h unpaid).",
        ]);

        $this->is_detail_breakdown_dirty = false;
        $this->selectedRequest->load('threads.user');
        session()->flash('message', 'Daily hours breakdown updated successfully.');
    }

    public function reloadEntitlementsAndHistory()
    {
        $this->employeeEntitlements = TimeOffPolicyEmployee::with('policy')
            ->where('employee_id', $this->selectedRequest->employee_id)
            ->get();

        $this->employeeLeaveHistory = TimeOffRequest::with('policy')
            ->where('employee_id', $this->selectedRequest->employee_id)
            ->where('id', '!=', $this->selectedRequest->id)
            ->orderBy('start_at', 'desc')
            ->take(5)
            ->get();
    }

    public function closeViewModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedRequest = null;
        $this->newThreadComment = '';
    }

    public function openAdjustmentModal()
    {
        $this->resetValidation();
        $emp = $this->selectedRequest->employee;
        $this->adj_employee_id = $emp->id;
        $this->adj_employee_name = $emp->name;
        $this->adj_reason = '';

        $allAssigned = TimeOffPolicyEmployee::with('policy')
            ->where('employee_id', $emp->id)
            ->get();

        $this->adj_policy_allocations = [];
        foreach ($allAssigned as $item) {
            $this->adj_policy_allocations[] = [
                'policy_id'      => $item->policy_id,
                'name'           => $item->policy->name,
                'original_hours' => (float)$item->hours_remaining,
                'new_hours'      => (float)$item->hours_remaining,
            ];
        }

        $this->isAdjustmentModalOpen = true;
    }

    public function closeAdjustmentModal()
    {
        $this->isAdjustmentModalOpen = false;
        $this->adj_policy_allocations = [];
        $this->adj_reason = '';
    }

    public function saveAdjustment()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHrManager()) {
            session()->flash('error', 'Only Administrators and HR Managers can adjust leave quotas.');
            $this->closeAdjustmentModal();
            return;
        }

        $this->validate([
            'adj_reason' => 'required|string|max:255',
            'adj_policy_allocations.*.new_hours' => 'required|numeric',
        ]);

        $details = [];

        foreach ($this->adj_policy_allocations as $alloc) {
            $orig = (float)$alloc['original_hours'];
            $new = (float)$alloc['new_hours'];

            if ($orig !== $new) {
                $record = TimeOffPolicyEmployee::where('employee_id', $this->adj_employee_id)
                    ->where('policy_id', $alloc['policy_id'])
                    ->first();

                if ($record) {
                    $record->update(['hours_remaining' => $new]);
                    $diff = $new - $orig;
                    $diffStr = ($diff > 0 ? '+' : '') . number_format($diff, 1) . 'h';
                    $details[] = "{$alloc['name']}: {$orig}h → {$new}h ({$diffStr})";
                }
            }
        }

        if (count($details) > 0) {
            $log = implode(', ', $details);
            TimeOffRequestThread::create([
                'time_off_request_id' => $this->selectedRequest->id,
                'user_id'             => $user->id,
                'type'                => 'status_change',
                'message'             => "HR Adjustment by {$user->name} ({$user->role}): {$log}. Reason: {$this->adj_reason}",
            ]);

            $this->selectedRequest->load('threads.user');
            $this->reloadEntitlementsAndHistory();
            session()->flash('message', "Policy balances updated successfully.");
        }

        $this->closeAdjustmentModal();
    }

    public function addThreadComment()
    {
        $this->validate([
            'newThreadComment' => 'required|string|max:1000',
        ]);

        TimeOffRequestThread::create([
            'time_off_request_id' => $this->selectedRequest->id,
            'user_id'             => Auth::id(),
            'type'                => 'message',
            'message'             => trim($this->newThreadComment),
        ]);

        $this->newThreadComment = '';
        $this->selectedRequest->load('threads.user');
    }

    public function openDecisionModal($id, $action)
    {
        $this->activeRequestId = $id;
        $this->decisionAction = $action;
        $this->decisionNotes = '';
        $this->isDecisionModalOpen = true;
    }

    public function closeDecisionModal()
    {
        $this->isDecisionModalOpen = false;
    }

    public function processDecision()
    {
        $req = TimeOffRequest::findOrFail($this->activeRequestId);
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isHrManager()) {
            if ($req->approver_id !== $user->id && !$user->canManageEmployee($req->employee_id)) {
                session()->flash('error', 'Unauthorized to decide on this request.');
                $this->closeDecisionModal();
                return;
            }
        }

        $paidDeduction = $req->paid_hours > 0 ? (float)$req->paid_hours : (float)$req->hours;

        if ($this->decisionAction === 'approved') {
            $balance = TimeOffPolicyEmployee::where('employee_id', $req->employee_id)
                ->where('policy_id', $req->policy_id)
                ->first();

            if ($balance) {
                $balance->hours_remaining = (float)$balance->hours_remaining - $paidDeduction;
                $balance->save();
            }

            $req->update([
                'status'         => 'approved',
                'decided_by'     => $user->id,
                'decided_at'     => now(),
                'decision_notes' => $this->decisionNotes,
            ]);

            TimeOffRequestThread::create([
                'time_off_request_id' => $req->id,
                'user_id'             => $user->id,
                'type'                => 'status_change',
                'message'             => "Approved by {$user->name} ({$user->role})." . ($this->decisionNotes ? " Note: {$this->decisionNotes}" : ""),
            ]);

            session()->flash('message', 'Request approved. Balances updated.');
        } else {
            if ($req->status === 'approved') {
                $balance = TimeOffPolicyEmployee::where('employee_id', $req->employee_id)
                    ->where('policy_id', $req->policy_id)
                    ->first();
                if ($balance) {
                    $balance->hours_remaining = (float)$balance->hours_remaining + $paidDeduction;
                    $balance->save();
                }
            }

            $req->update([
                'status'         => 'denied',
                'decided_by'     => $user->id,
                'decided_at'     => now(),
                'decision_notes' => $this->decisionNotes,
            ]);

            TimeOffRequestThread::create([
                'time_off_request_id' => $req->id,
                'user_id'             => $user->id,
                'type'                => 'status_change',
                'message'             => "Denied by {$user->name} ({$user->role})." . ($this->decisionNotes ? " Reason: {$this->decisionNotes}" : ""),
            ]);

            session()->flash('message', 'Request marked as denied.');
        }

        if ($this->selectedRequest && $this->selectedRequest->id === $req->id) {
            $this->selectedRequest->load('threads.user');
        }

        $this->closeDecisionModal();
    }

    public function render()
    {
        $user = Auth::user();

        $query = TimeOffRequest::with(['employee.manager', 'policy', 'approver', 'decidedByUser'])
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->employee_filter, fn($q) => $q->where('employee_id', $this->employee_filter));

        if (!$user->isAdmin() && !$user->isHrManager()) {
            if ($user->isManager()) {
                $query->where(function($q) use ($user) {
                    $q->where('approver_id', $user->id)
                        ->orWhere('employee_id', $user->id);
                });
            } else {
                $query->where('employee_id', $user->id);
            }
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        $requestableEmployees = ($user->isAdmin() || $user->isHrManager() || $user->isManager())
            ? User::where('status', 'Active')->orderBy('name')->get()
            : collect([$user]);

        $empPolicies = TimeOffPolicyEmployee::with('policy')
            ->where('employee_id', $this->selected_employee_id)
            ->whereHas('policy', fn($q) => $q->where('active', true))
            ->get();

        return view('livewire.time-off.request-manager', [
            'requests'             => $requests,
            'requestableEmployees' => $requestableEmployees,
            'empPolicies'          => $empPolicies,
        ])->layout('layouts.app');
    }
}
