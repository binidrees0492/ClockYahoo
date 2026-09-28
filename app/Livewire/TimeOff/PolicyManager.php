<?php

namespace App\Livewire\TimeOff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TimeOffPolicy;

class PolicyManager extends Component
{
    use WithPagination;

    public $search = '';
    public $type_filter = '';
    public $isModalOpen = false;
    public $modalMode = 'add'; // add or edit

    // Form fields
    public $policyId = null;
    public $name = '';
    public $policy_type = 0; // 0=PTO, 1=Sick, 2=Unpaid
    public $accrual_method = 0; // 0=pay period, 1=first of year, 2=anniversary, 3=hrs-based
    public $is_time_off_limit = false;
    public $accrual_hours = 0.00;
    public $is_waiting_period = false;
    public $waiting_period_days = 0;
    public $is_carryover_limit = false;
    public $max_carryover_hours = 0;
    public $max_balance_hours = 0;
    public $active = true;

    protected function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'policy_type'         => 'required|integer|in:0,1,2',
            'accrual_method'      => 'required|integer|in:0,1,2,3',
            'is_time_off_limit'   => 'boolean',
            'accrual_hours'       => 'nullable|numeric|min:0',
            'is_waiting_period'   => 'boolean',
            'waiting_period_days' => 'nullable|integer|min:0',
            'is_carryover_limit'  => 'boolean',
            'max_carryover_hours' => 'nullable|integer|min:0',
            'max_balance_hours'   => 'nullable|integer|min:0',
            'active'              => 'boolean',
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->reset([
            'policyId', 'name', 'policy_type', 'accrual_method',
            'is_time_off_limit', 'accrual_hours', 'is_waiting_period',
            'waiting_period_days', 'is_carryover_limit', 'max_carryover_hours',
            'max_balance_hours'
        ]);

        $this->policy_type = 0;
        $this->accrual_method = 0;
        $this->active = true;
        $this->modalMode = 'add';
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $policy = TimeOffPolicy::findOrFail($id);

        $this->policyId            = $policy->id;
        $this->name                = $policy->name;
        $this->policy_type         = $policy->policy_type;
        $this->accrual_method      = $policy->accrual_method;
        $this->is_time_off_limit   = (bool) $policy->is_time_off_limit;
        $this->accrual_hours       = $policy->accrual_hours ?? 0;
        $this->is_waiting_period   = (bool) $policy->is_waiting_period;
        $this->waiting_period_days = $policy->waiting_period_days ?? 0;
        $this->is_carryover_limit  = (bool) $policy->is_carryover_limit;
        $this->max_carryover_hours = $policy->max_carryover_hours ?? 0;
        $this->max_balance_hours   = $policy->max_balance_hours ?? 0;
        $this->active              = (bool) $policy->active;

        $this->modalMode = 'edit';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $validated = $this->validate();

        TimeOffPolicy::updateOrCreate(
            ['id' => $this->policyId],
            $validated
        );

        session()->flash('message', $this->policyId ? 'Policy updated successfully.' : 'Policy created successfully.');
        $this->closeModal();
    }

    public function delete($id)
    {
        TimeOffPolicy::findOrFail($id)->delete();
        session()->flash('message', 'Policy removed.');
    }

    public function render()
    {
        $policies = TimeOffPolicy::withCount('policyEmployees')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->when($this->type_filter !== '', fn($q) => $q->where('policy_type', $this->type_filter))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.time-off.policy-manager', [
            'policies' => $policies,
        ])->layout('layouts.app');
    }
}
