<?php

namespace App\Livewire\TimeOff;

use Livewire\Component;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffPolicyEmployee;
use App\Models\User;
use Carbon\Carbon;

class PolicyWizard extends Component
{
    // Which step: PolicyDetails | AdditionalSettings | EmployeeSelection | EmployeeDetails
    public $step = 'PolicyDetails';

    // Which existing policy (null when adding)
    public $policyId = null;

    // Step 1 — Policy Details
    public $policyName = '';
    public $policyType = 0;              // 0=PTO 1=Sick 2=Unpaid
    public $policyIsLimit = null;        // null | 1 | 0
    public $policyAccrualMethod = 0;     // 0..3
    public $policyAccrualHours = null;

    // Step 2 — Additional Settings
    public $policyIsWaiting = null;      // null | 1 | 0
    public $policyWaitingDays = null;
    public $policyIsCarryover = null;    // null | 1 | 0
    public $policyMaxCarryover = null;
    public $policyMaxBalance = null;

    // Step 3 — Employee Selection
    public $allEmployees = '0';          // 0=individual 1=unassigned 2=all
    public $selectedEmployeeIds = [];    // only used when allEmployees = 0

    // Step 4 — Employee Details
    // [employeeId => ['hire_date' => 'Y-m-d', 'hours_remaining' => float]]
    public $employeeDetails = [];

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount($policyId = null)
    {
        if ($policyId) {
            $p = TimeOffPolicy::find($policyId);
            if ($p) {
                $this->policyId             = $p->id;
                $this->policyName           = $p->name;
                $this->policyType           = (int) $p->policy_type;
                $this->policyIsLimit        = (bool) $p->is_time_off_limit ? 1 : 0;
                $this->policyAccrualMethod  = (int) $p->accrual_method;
                $this->policyAccrualHours   = $p->accrual_hours;
                $this->policyIsWaiting      = (bool) $p->is_waiting_period ? 1 : 0;
                $this->policyWaitingDays    = $p->waiting_period_days;
                $this->policyIsCarryover    = (bool) $p->is_carryover_limit ? 1 : 0;
                $this->policyMaxCarryover   = $p->max_carryover_hours;
                $this->policyMaxBalance     = $p->max_balance_hours;

                // Load existing employees
                $rows = TimeOffPolicyEmployee::where('policy_id', $p->id)->get();
                if ($rows->count()) {
                    $this->allEmployees = '0';
                    foreach ($rows as $r) {
                        $this->selectedEmployeeIds[] = (string) $r->employee_id;
                        $this->employeeDetails[$r->employee_id] = [
                            'hire_date'        => $r->hire_date?->format('Y-m-d'),
                            'hours_remaining'  => (float) $r->hours_remaining,
                        ];
                    }
                }
            }
        }
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

    // ============ STEP 1 ============

    public function goToAdditionalSettings()
    {
        $this->validate([
            'policyName' => 'required|string|max:100',
            'policyType' => 'required|integer|in:0,1,2',
        ]);

        if ($this->policyType !== 2) { // not Unpaid
            if ($this->policyIsLimit === null) {
                $this->addError('policyIsLimit', 'You must make a selection.');
                return;
            }
            if ($this->policyIsLimit == 1 && empty($this->policyAccrualHours)) {
                $this->addError('policyAccrualHours', 'Please enter accrual hours.');
                return;
            }
        } else {
            // Unpaid: no accrual, no limit, skip Additional Settings
            $this->policyIsLimit     = 0;
            $this->policyIsCarryover = 0;
            $this->savePolicy();
            $this->step = 'EmployeeSelection';
            return;
        }

        $this->savePolicy();
        $this->step = 'AdditionalSettings';
    }

    public function goBackToPolicyDetails()
    {
        $this->step = 'PolicyDetails';
    }

    // ============ STEP 2 ============

    public function goToEmployeeSelection()
    {
        if ($this->policyType !== 2) {
            if ($this->policyIsWaiting === null) {
                $this->addError('policyIsWaiting', 'You must make a selection.');
                return;
            }
            if ($this->policyIsWaiting == 1 && empty($this->policyWaitingDays)) {
                $this->addError('policyWaitingDays', 'Please enter waiting period days.');
                return;
            }
            if ($this->policyIsLimit == 1) {
                if ($this->policyIsCarryover === null) {
                    $this->addError('policyIsCarryover', 'You must make a selection.');
                    return;
                }
                if ($this->policyIsCarryover == 1 && empty($this->policyMaxCarryover)) {
                    $this->addError('policyMaxCarryover', 'Please enter carryover limit.');
                    return;
                }
            }
        }

        $this->savePolicy();
        $this->step = 'EmployeeSelection';
    }

    // ============ STEP 3 ============

    public function goToEmployeeDetails()
    {
        // Build the list of employees being assigned
        $employees = $this->resolveEmployees();

        // Merge with existing employeeDetails so we don't lose data when navigating back
        $merged = [];
        foreach ($employees as $e) {
            $merged[$e->id] = $this->employeeDetails[$e->id] ?? [
                'hire_date'       => null,
                'hours_remaining' => 0,
            ];
        }
        $this->employeeDetails = $merged;

        $this->step = 'EmployeeDetails';
    }

    public function goBackToEmployeeSelection()
    {
        $this->step = 'EmployeeSelection';
    }

    private function resolveEmployees()
    {
        if ($this->allEmployees === '2') {
            return User::orderBy('name')->get();
        }
        if ($this->allEmployees === '1') {
            $assigned = TimeOffPolicyEmployee::pluck('employee_id')->toArray();
            return User::whereNotIn('id', $assigned)->orderBy('name')->get();
        }
        // Individual
        return User::whereIn('id', $this->selectedEmployeeIds ?: [0])->orderBy('name')->get();
    }

    // ============ STEP 4 ============

    public function saveAndFinish()
    {
        if (!$this->policyId) {
            // Create policy if we somehow got here without it (Unpaid flow already saves)
            $this->savePolicy();
        }

        // Persist employees
        $employees = $this->resolveEmployees();
        $employeeIds = $employees->pluck('id')->toArray();

        // Remove anyone no longer selected
        TimeOffPolicyEmployee::where('policy_id', $this->policyId)
            ->whereNotIn('employee_id', $employeeIds ?: [0])
            ->delete();

        // Upsert
        foreach ($employees as $e) {
            $details = $this->employeeDetails[$e->id] ?? [];

            TimeOffPolicyEmployee::updateOrCreate(
                ['policy_id' => $this->policyId, 'employee_id' => $e->id],
                [
                    'hire_date'       => $details['hire_date'] ?? null,
                    'hours_remaining' => $details['hours_remaining'] ?? 0,
                ]
            );
        }

        session()->flash('success', $this->policyId ? 'Policy saved.' : 'Policy created.');
        return redirect()->route('timeoff.policies');
    }

    public function cancel()
    {
        return redirect()->route('timeoff.policies');
    }

    // ============ SHARED ============

    private function savePolicy()
    {
        $data = [
            'name'                 => $this->policyName,
            'policy_type'          => (int) $this->policyType,
            'accrual_method'       => (int) $this->policyAccrualMethod,
            'is_time_off_limit'    => $this->policyIsLimit == 1,
            'accrual_hours'        => $this->policyAccrualHours,
            'is_waiting_period'    => $this->policyIsWaiting == 1,
            'waiting_period_days'  => $this->policyWaitingDays,
            'is_carryover_limit'   => $this->policyIsCarryover == 1,
            'max_carryover_hours'  => $this->policyMaxCarryover,
            'max_balance_hours'    => $this->policyMaxBalance,
        ];

        if ($this->policyId) {
            TimeOffPolicy::find($this->policyId)->update($data);
        } else {
            $p = TimeOffPolicy::create($data);
            $this->policyId = $p->id;
        }
    }

    public function render()
    {
        return view('livewire.time-off.policy-wizard', [
            'employees'  => User::orderBy('name')->get(),
            'titleLabel' => $this->policyId ? 'Edit Policy' : 'Add Policy',
        ]);
    }
}
