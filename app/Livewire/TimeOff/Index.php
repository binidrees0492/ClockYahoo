<?php

namespace App\Livewire\TimeOff;

use Livewire\Component;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\TimeOffPolicyEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Index extends Component
{
    public $tab = 'requests'; // requests | policies

    // Requests list
    public $requestFilter = 'all';
    public $requestEmployeeFilter = null;
    public $requestSearch = '';

    // Add Request modal
    public $showRequestModal = false;
    public $rqEmployeeId = null;
    public $rqPolicyId = null;
    public $rqStart = null;
    public $rqEnd = null;
    public $rqReason = null;

    // Policies list
    public $policySearch = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function openRequest()
    {
        $this->reset(['rqEmployeeId', 'rqPolicyId', 'rqStart', 'rqEnd', 'rqReason']);
        $now = Carbon::now();
        $this->rqStart = $now->copy()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->rqEnd   = $now->copy()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
        $this->showRequestModal = true;
    }

    public function cancelRequest()
    {
        $this->showRequestModal = false;
        $this->reset(['rqEmployeeId', 'rqPolicyId', 'rqStart', 'rqEnd', 'rqReason']);
    }

    public function saveRequest()
    {
        $this->validate([
            'rqEmployeeId' => 'required|exists:users,id',
            'rqPolicyId'   => 'nullable|exists:time_off_policies,id',
            'rqStart'      => 'required|date',
            'rqEnd'        => 'required|date|after:rqStart',
        ]);

        $start = Carbon::parse($this->rqStart);
        $end   = Carbon::parse($this->rqEnd);

        TimeOffRequest::create([
            'employee_id' => $this->rqEmployeeId,
            'policy_id'   => $this->rqPolicyId,
            'start_at'    => $start,
            'end_at'      => $end,
            'hours'       => round($start->diffInMinutes($end) / 60, 2),
            'reason'      => $this->rqReason,
            'status'      => 'pending',
        ]);

        $this->cancelRequest();
        $this->toast('Time off request submitted.');
    }

    public function approveRequest($id)
    {
        $r = TimeOffRequest::find($id);
        if (!$r) return;

        $r->status     = 'approved';
        $r->decided_by = Auth::id();
        $r->decided_at = now();
        $r->save();

        $this->toast('Request approved.');
    }

    public function denyRequest($id)
    {
        $r = TimeOffRequest::find($id);
        if (!$r) return;

        $r->status     = 'denied';
        $r->decided_by = Auth::id();
        $r->decided_at = now();
        $r->save();

        $this->toast('Request denied.');
    }

    public function deletePolicy($id)
    {
        TimeOffPolicy::find($id)?->delete();
        $this->toast('Policy deleted.');
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        // Requests
        $rq = TimeOffRequest::with(['employee', 'policy', 'decider']);

        if ($this->requestFilter !== 'all') {
            $rq->where('status', $this->requestFilter);
        }
        if ($this->requestEmployeeFilter) {
            $rq->where('employee_id', $this->requestEmployeeFilter);
        }
        if (trim($this->requestSearch) !== '') {
            $term = '%' . trim($this->requestSearch) . '%';
            $rq->where(function ($w) use ($term) {
                $w->whereHas('employee', fn ($e) => $e->where('name', 'like', $term))
                    ->orWhere('reason', 'like', $term);
            });
        }
        $requests = $rq->orderByDesc('start_at')->get();

        // Policies
        $pq = TimeOffPolicy::withCount('employees');
        if (trim($this->policySearch) !== '') {
            $pq->where('name', 'like', '%' . trim($this->policySearch) . '%');
        }
        $policies = $pq->orderBy('name')->get();

        return view('livewire.time-off.index', [
            'requests'  => $requests,
            'policies'  => $policies,
            'employees' => User::orderBy('name')->get(),
        ]);
    }
}
