<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use App\Models\TimeOffPolicy;
use App\Models\Assignment;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Employees extends Component
{
    use WithPagination, WithFileUploads;

    public const ACCESS_NONE     = 0;
    public const ACCESS_READONLY = 1;
    public const ACCESS_WRITE    = 2;

    public $search = '';
    public $department_filter = '';
    public $status_filter = '';
    public $includeDeleted = false;

    public $isModalOpen = false;
    public $modalMode = 'add';
    public $activeTab = 'details';

    public $employeeId = null;
    public $avatarFile = null;
    public $currentAvatarUrl = null;
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
    public $assigned_jobs = [];
    public $assigned_tasks = [];
    public $job_shifts = [];

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
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized access to Employee administration.');
        }

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

        foreach ($this->employeePolicies as $index => $item) {
            if ((string)$item['policy_id'] === (string)$policyId) {
                $this->employeePolicies[$index]['enrolled'] = true;
                $this->selectedPolicyToAdd = '';
                return;
            }
        }

        $policy = TimeOffPolicy::find($policyId);
        if ($policy) {
            $this->employeePolicies[] = [
                'policy_id'       => $policy->id,
                'name'            => $policy->name,
                'type_label'      => $policy->policyTypeLabel(),
                'enrolled'        => true,
                'hire_date'       => now()->format('Y-m-d'),
                'hours_remaining' => 0.00,
            ];
        }

        $this->selectedPolicyToAdd = '';
    }

    public function togglePolicyEnrollment($index)
    {
        if (isset($this->employeePolicies[$index])) {
            $this->employeePolicies[$index]['enrolled'] = !($this->employeePolicies[$index]['enrolled'] ?? false);
        }
    }

    public function removePolicy($index)
    {
        if (isset($this->employeePolicies[$index])) {
            $this->employeePolicies[$index]['enrolled'] = false;
        }
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
            'employeePolicies', 'avatarFile', 'currentAvatarUrl', 'assigned_jobs', 'assigned_tasks', 'job_shifts'
        ]);

        $this->password = 'password';
        $this->role = 'Employee';
        $this->status = 'Active';
        $this->color = '#ffa526';
        $this->managed_team_type = 'restricted';
        $this->activeTab = 'details';

        $allPolicies = TimeOffPolicy::where('active', true)->orderBy('name')->get();
        $this->employeePolicies = [];
        foreach ($allPolicies as $policy) {
            $this->employeePolicies[] = [
                'policy_id'       => $policy->id,
                'name'            => $policy->name,
                'type_label'      => $policy->policyTypeLabel(),
                'enrolled'        => false,
                'hire_date'       => now()->format('Y-m-d'),
                'hours_remaining' => 0.00,
            ];
        }

        $this->applyRoleDefaults();
        $this->modalMode = 'add';
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $user = User::with('assignedPolicies')->findOrFail($id);

        $this->employeeId       = $user->id;
        $this->avatarFile       = null;
        $this->currentAvatarUrl = $user->avatar_url;
        $nameParts              = explode(' ', $user->name ?? '', 2);
        $this->first_name       = $user->first_name ?: ($nameParts[0] ?? '');
        $this->last_name        = $user->last_name ?: ($nameParts[1] ?? 'Staff');
        $this->email            = $user->email;
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

        $activeJobIds = Assignment::where('status', '!=', 'inactive')->pluck('id')->toArray();
        $this->assigned_jobs  = array_values(array_intersect(array_map('intval', $user->permissions['assigned_jobs'] ?? []), $activeJobIds));

        $activeTaskIds = Task::where('status', '!=', 'inactive')->pluck('id')->toArray();
        $this->assigned_tasks = array_values(array_intersect(array_map('intval', $user->permissions['assigned_tasks'] ?? []), $activeTaskIds));
        $this->job_shifts     = $user->permissions['job_shifts'] ?? [];

        $allPolicies = TimeOffPolicy::where('active', true)->orderBy('name')->get();
        $enrolledMap = $user->assignedPolicies->keyBy('id');
        $this->employeePolicies = [];
        foreach ($allPolicies as $policy) {
            $assigned = $enrolledMap->get($policy->id);
            $this->employeePolicies[] = [
                'policy_id'       => $policy->id,
                'name'            => $policy->name,
                'type_label'      => $policy->policyTypeLabel(),
                'enrolled'        => (bool) $assigned,
                'hire_date'       => $assigned && $assigned->pivot->hire_date
                    ? \Carbon\Carbon::parse($assigned->pivot->hire_date)->format('Y-m-d')
                    : ($user->created_at ? $user->created_at->format('Y-m-d') : now()->format('Y-m-d')),
                'hours_remaining' => $assigned ? (float) $assigned->pivot->hours_remaining : 0.00,
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
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => ['required', 'email', Rule::unique('users', 'email')->ignore($this->employeeId)],
            'role'       => 'required|string|max:50|not_in:Super Admin',
            'status'     => 'required|in:Active,Inactive',
            'base_pay'   => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $permissions = $this->permissions;
        $permissions['assigned_jobs'] = array_values(array_unique(array_filter(array_map('intval', $this->assigned_jobs))));
        $permissions['assigned_tasks'] = array_values(array_unique(array_filter(array_map('intval', $this->assigned_tasks))));
        $permissions['job_shifts'] = $this->job_shifts;
        $this->permissions = $permissions;

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
            'permissions'         => $permissions,
            'managed_team_type'   => $this->managed_team_type,
            'managed_employees'   => $this->managed_entities,
            'manager_permissions' => $this->manager_permissions,
        ];

        if ($this->avatarFile) {
            $this->validate([
                'avatarFile' => 'nullable|image|max:5120',
            ]);
            $path = $this->avatarFile->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        if (!$this->employeeId) {
            $data['password'] = Hash::make($this->password ?: 'password');
        } elseif (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->employeeId], $data);

        $syncData = [];
        foreach ($this->employeePolicies as $policy) {
            $isEnrolled = isset($policy['enrolled']) ? (bool)$policy['enrolled'] : true;
            if ($isEnrolled) {
                $syncData[$policy['policy_id']] = [
                    'hire_date'       => $policy['hire_date'] ?: null,
                    'hours_remaining' => (float)($policy['hours_remaining'] ?? 0.00),
                ];
            }
        }
        $user->assignedPolicies()->sync($syncData);

        $msg = $this->employeeId ? 'Employee updated successfully.' : 'Employee created successfully.';
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
        $this->closeModal();
    }

    public function removeAvatar()
    {
        $this->avatarFile = null;
        $this->currentAvatarUrl = null;
        if ($this->employeeId) {
            $u = User::find($this->employeeId);
            if ($u) {
                $u->avatar = null;
                $u->save();
            }
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::findOrFail($id);

        if ($user->isSuperAdmin()) {
            session()->flash('error', 'Super Admin accounts cannot be deleted.');
            $this->dispatch('toast', message: 'Super Admin accounts cannot be deleted.', type: 'error');
            return;
        }

        // Strong RDBMS Check: if employee has child records, prevent hard deletion and mark Inactive instead
        $hasTimeLogs      = \App\Models\TimeLog::where('employee_id', $id)->exists();
        $hasSchedules     = \App\Models\Schedule::where('employee_id', $id)->exists();
        $hasAssignments   = \App\Models\Assignment::where('employee_id', $id)->exists();
        $hasDirectReports = User::where('manager_id', $id)->exists();

        if ($hasTimeLogs || $hasSchedules || $hasAssignments || $hasDirectReports) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('employees', $id);
                $msg = "Employee '{$user->name}' and all linked child records permanently deleted.";
                session()->flash('message', $msg);
                $this->dispatch('toast', message: $msg, type: 'info');
                return;
            }

            $user->update(['status' => 'Inactive']);
            $msg = "Employee '{$user->name}' has existing child records (timesheets, schedules, or team assignments) and cannot be deleted. Marked as Inactive instead to preserve database integrity.";
            session()->flash('message', $msg);
            $this->dispatch('toast', message: $msg, type: 'warning');
            return;
        }

        $user->delete();
        session()->flash('message', 'Employee removed successfully.');
        $this->dispatch('toast', message: 'Employee removed successfully.', type: 'success');
    }

        public function restoreEmployee($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore deleted records.');
        }

        $user = User::findOrFail($id);
        $user->update(['status' => 'Active']);

        // Restore child time logs if archived
        \App\Models\TimeLog::where('employee_id', $id)
            ->where('is_archived', true)
            ->update([
                'is_archived' => false,
                'archived_at' => null,
                'archived_by' => null,
            ]);

        $this->dispatch('toast', message: "Employee '{$user->name}' and child records restored successfully.", type: 'success');
    }

    public function permanentlyDeleteEmployee($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can permanently delete records.');
        }

        $user = User::findOrFail($id);
        if ($user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Super Admin accounts cannot be permanently deleted.', type: 'error');
            return;
        }

        $name = $user->name;

        // Cascade delete all child records
        \App\Models\TimeLogAttachment::where('user_id', $id)->delete();
        \App\Models\TimeLogNote::where('user_id', $id)->delete();
        \App\Models\TimeLog::where('employee_id', $id)->delete();
        \App\Models\Schedule::where('employee_id', $id)->delete();
        \App\Models\TimeOffRequestThread::where('user_id', $id)->delete();
        \App\Models\TimeOffRequest::where('employee_id', $id)->delete();
        \App\Models\TimeOffPolicyEmployee::where('employee_id', $id)->delete();

        User::where('manager_id', $id)->update(['manager_id' => null]);
        if (\Illuminate\Support\Facades\Schema::hasColumn('tbl_assignments', 'employee_id')) {
            \App\Models\Assignment::where('employee_id', $id)->update(['employee_id' => null]);
        }

        $user->delete();

        $this->dispatch('toast', message: "Employee '{$name}' and all linked child records permanently deleted.", type: 'info');
    }

    public function render()
    {
        $employees = User::query()
            ->notSuperAdmin()
            ->when(!$this->includeDeleted && empty($this->status_filter), function ($q) {
                $q->whereNotIn('status', ['Inactive', 'inactive', 'deleted']);
            })
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
            'allEmployees'      => User::notSuperAdmin()->where('id', '!=', $this->employeeId)->orderBy('name')->get(),
            'departments'       => Department::whereNotIn('status', ['Inactive', 'inactive'])->orderBy('name')->get(),
            'locations'         => Location::whereNotIn('status', ['Inactive', 'inactive'])->orderBy('name')->get(),
            'eligibleManagers'  => User::notSuperAdmin()->where('id', '!=', $this->employeeId)
                ->whereIn('role', ['Administrator', 'HR Manager', 'Manager'])
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(),
            'availablePolicies' => TimeOffPolicy::where('active', true)
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->get(),
            'availableRoles'    => \App\Models\Role::where('status', 'Active')
                ->where('name', '!=', 'Super Admin')
                ->orderBy('name')
                ->get(),
            'availableJobs'     => \App\Models\Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get(),
            'availableTasks'    => \App\Models\Task::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
