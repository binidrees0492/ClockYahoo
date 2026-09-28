<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use App\Models\TimeOffPolicy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Employees extends Component
{
    use WithPagination;

    public const ACCESS_NONE     = 0;
    public const ACCESS_READONLY = 1;
    public const ACCESS_WRITE    = 2;

    public $search = '';
    public $department_filter = '';
    public $status_filter = '';

    public $isModalOpen = false;
    public $modalMode = 'add';
    public $activeTab = 'details';

    public $employeeId = null;
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $password = 'password';
    public $display_id = '';
    public $role = 'Employee';
    public $status = 'Active';
    public $color = '#ffa526';
    public $base_pay = '';
    public $department_id = '';
    public $location_id = '';
    public $manager_id = '';

    public $permissions = [];

    // Time Off (Live from time_off_policies & time_off_policy_employees)
    public $selectedPolicyToAdd = '';
    public $employeePolicies = [];

    // Manager Scope
    public $managed_team_type = 'restricted';
    public $selectedEntityToAdd = '';
    public $managed_entities = [];
    public $manager_permissions = [];

    public function mount()
    {
        $this->applyRoleDefaults();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedRole($val)
    {
        $this->applyRoleDefaults();
    }

    public function applyRoleDefaults()
    {
        if ($this->role === 'Administrator' || $this->role === 'HR Manager') {
            $this->permissions = [
                'mobile_timesheets' => self::ACCESS_WRITE,
                'jobs'              => self::ACCESS_WRITE,
                'tasks'             => self::ACCESS_WRITE,
                'customers'         => self::ACCESS_WRITE,
                'quotes'            => self::ACCESS_WRITE,
                'invoices'          => self::ACCESS_WRITE,
                'reports'           => self::ACCESS_WRITE,
                'schedules'         => self::ACCESS_WRITE,
                'allow_web_clock'   => true,
                'require_location'  => false,
                'restrict_geofence' => false,
            ];

            $this->manager_permissions = [
                'timesheets'        => self::ACCESS_WRITE,
                'schedules'         => self::ACCESS_WRITE,
                'view_map_location' => true,
                'crew_clock'        => true,
                'view_reports'      => true,
                'approve_timesheets'=> true,
                'manage_time_off'   => true,
            ];
        } elseif ($this->role === 'Manager') {
            $this->permissions = [
                'mobile_timesheets' => self::ACCESS_WRITE,
                'jobs'              => self::ACCESS_READONLY,
                'tasks'             => self::ACCESS_READONLY,
                'customers'         => self::ACCESS_READONLY,
                'quotes'            => self::ACCESS_NONE,
                'invoices'          => self::ACCESS_NONE,
                'reports'           => self::ACCESS_READONLY,
                'schedules'         => self::ACCESS_WRITE,
                'allow_web_clock'   => true,
                'require_location'  => true,
                'restrict_geofence' => false,
            ];

            $this->manager_permissions = [
                'timesheets'        => self::ACCESS_READONLY,
                'schedules'         => self::ACCESS_READONLY,
                'view_map_location' => true,
                'crew_clock'        => true,
                'view_reports'      => true,
                'approve_timesheets'=> true,
                'manage_time_off'   => true,
            ];
        } else {
            $this->permissions = [
                'mobile_timesheets' => self::ACCESS_READONLY,
                'jobs'              => self::ACCESS_READONLY,
                'tasks'             => self::ACCESS_READONLY,
                'customers'         => self::ACCESS_NONE,
                'quotes'            => self::ACCESS_NONE,
                'invoices'          => self::ACCESS_NONE,
                'reports'           => self::ACCESS_NONE,
                'schedules'         => self::ACCESS_READONLY,
                'allow_web_clock'   => true,
                'require_location'  => true,
                'restrict_geofence' => true,
            ];

            $this->manager_permissions = [
                'timesheets'        => self::ACCESS_NONE,
                'schedules'         => self::ACCESS_NONE,
                'view_map_location' => false,
                'crew_clock'        => false,
                'view_reports'      => false,
                'approve_timesheets'=> false,
                'manage_time_off'   => false,
            ];
        }
    }

    public function updatedSelectedPolicyToAdd($policyId)
    {
        if (empty($policyId)) return;

        foreach ($this->employeePolicies as $item) {
            if ((string)$item['policy_id'] === (string)$policyId) {
                $this->selectedPolicyToAdd = '';
                return;
            }
        }

        $policy = TimeOffPolicy::find($policyId);
        if ($policy) {
            $this->employeePolicies[] = [
                'policy_id'       => $policy->id,
                'name'            => $policy->name,
                'hire_date'       => now()->format('Y-m-d'),
                'hours_remaining' => 0.00,
            ];
        }

        $this->selectedPolicyToAdd = '';
    }

    public function removePolicy($index)
    {
        unset($this->employeePolicies[$index]);
        $this->employeePolicies = array_values($this->employeePolicies);
    }

    public function updatedSelectedEntityToAdd($value)
    {
        if (empty($value)) return;

        [$type, $id] = explode(':', $value);

        foreach ($this->managed_entities as $item) {
            if ($item['type'] === $type && (string)$item['id'] === (string)$id) {
                $this->selectedEntityToAdd = '';
                return;
            }
        }

        $name = '';
        if ($type === 'dept') {
            $name = Department::find($id)?->name ?? 'Department';
        } elseif ($type === 'loc') {
            $name = Location::find($id)?->name ?? 'Location';
        } elseif ($type === 'emp') {
            $name = User::find($id)?->name ?? 'Employee';
        }

        $this->managed_entities[] = [
            'type' => $type,
            'id'   => (int) $id,
            'name' => $name,
        ];

        $this->selectedEntityToAdd = '';
    }

    public function removeManagedEntity($index)
    {
        unset($this->managed_entities[$index]);
        $this->managed_entities = array_values($this->managed_entities);
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->reset([
            'employeeId', 'first_name', 'last_name', 'email', 'phone',
            'display_id', 'department_id', 'location_id', 'manager_id', 'base_pay',
            'managed_entities', 'selectedEntityToAdd', 'selectedPolicyToAdd',
            'employeePolicies'
        ]);

        $this->password = 'password';
        $this->role = 'Employee';
        $this->status = 'Active';
        $this->color = '#ffa526';
        $this->managed_team_type = 'restricted';
        $this->activeTab = 'details';

        $this->applyRoleDefaults();
        $this->modalMode = 'add';
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $user = User::with('assignedPolicies')->findOrFail($id);

        $this->employeeId    = $user->id;
        $this->first_name    = $user->first_name ?? '';
        $this->last_name     = $user->last_name ?? '';
        $this->email         = $user->email;
        $this->phone         = $user->phone ?? '';
        $this->display_id    = $user->display_id ?? '';
        $this->role          = $user->role ?? 'Employee';
        $this->status        = $user->status ?? 'Active';
        $this->color         = $user->color ?? '#ffa526';
        $this->base_pay      = $user->base_pay ?? '';
        $this->department_id = $user->department_id ?? '';
        $this->location_id   = $user->location_id ?? '';
        $this->manager_id    = $user->manager_id ?? '';

        $this->managed_team_type = $user->managed_team_type ?? 'restricted';
        $this->managed_entities  = $user->managed_employees ?? [];

        $this->employeePolicies = [];
        foreach ($user->assignedPolicies as $policy) {
            $this->employeePolicies[] = [
                'policy_id'       => $policy->id,
                'name'            => $policy->name,
                'hire_date'       => $policy->pivot->hire_date ? \Carbon\Carbon::parse($policy->pivot->hire_date)->format('Y-m-d') : null,
                'hours_remaining' => (float) $policy->pivot->hours_remaining,
            ];
        }

        $this->applyRoleDefaults();

        if (!empty($user->permissions)) {
            $this->permissions = array_merge($this->permissions, $user->permissions);
        }

        if (!empty($user->manager_permissions)) {
            $this->manager_permissions = array_merge($this->manager_permissions, $user->manager_permissions);
        }

        $this->password  = '';
        $this->activeTab = 'details';
        $this->modalMode = 'edit';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => ['required', 'email', Rule::unique('users', 'email')->ignore($this->employeeId)],
            'role'       => 'required|in:Administrator,HR Manager,Manager,Employee',
            'status'     => 'required|in:Active,Inactive',
            'base_pay'   => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $data = [
            'first_name'          => trim($this->first_name),
            'last_name'           => trim($this->last_name),
            'name'                => trim($this->first_name . ' ' . $this->last_name),
            'email'               => trim($this->email),
            'phone'               => $this->phone,
            'display_id'          => $this->display_id,
            'role'                => $this->role,
            'status'              => $this->status,
            'color'               => $this->color,
            'base_pay'            => $this->base_pay ?: null,
            'department_id'       => $this->department_id ?: null,
            'location_id'         => $this->location_id ?: null,
            'manager_id'          => $this->manager_id ?: null,
            'permissions'         => $this->permissions,
            'managed_team_type'   => $this->managed_team_type,
            'managed_employees'   => $this->managed_entities,
            'manager_permissions' => $this->manager_permissions,
        ];

        if (!$this->employeeId) {
            $data['password'] = Hash::make($this->password ?: 'password');
        } elseif (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->employeeId], $data);

        $syncData = [];
        foreach ($this->employeePolicies as $policy) {
            $syncData[$policy['policy_id']] = [
                'hire_date'       => $policy['hire_date'] ?: null,
                'hours_remaining' => $policy['hours_remaining'] ?? 0.00,
            ];
        }
        $user->assignedPolicies()->sync($syncData);

        session()->flash('message', $this->employeeId ? 'Employee updated successfully.' : 'Employee created successfully.');
        $this->closeModal();
    }

    public function delete($id)
    {
        User::findOrFail($id)->delete();
        session()->flash('message', 'Employee removed.');
    }

    public function render()
    {
        $employees = User::query()
            ->when($this->search, function ($query) {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('display_id', 'like', $term);
                });
            })
            ->when($this->department_filter, fn($q) => $q->where('department_id', $this->department_filter))
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->orderBy('name')
            ->paginate(15);

        $enrolledIds = collect($this->employeePolicies)->pluck('policy_id')->all();

        return view('livewire.employees', [
            'employees'         => $employees,
            'allEmployees'      => User::where('id', '!=', $this->employeeId)->orderBy('name')->get(),
            'departments'       => Department::orderBy('name')->get(),
            'locations'         => Location::orderBy('name')->get(),
            'eligibleManagers'  => User::where('id', '!=', $this->employeeId)
                ->whereIn('role', ['Administrator', 'HR Manager', 'Manager'])
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(),
            'availablePolicies' => TimeOffPolicy::where('active', true)
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }
}
