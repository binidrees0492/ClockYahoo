<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class AccessManagement extends Component
{
    public $tab = 'roles'; // 'roles' | 'users'

    // Role-based UAM
    public $selectedRoleId = null;
    public $rolePermissions = [];

    // Role CRUD Modal
    public $showRoleModal = false;
    public $isEditingRole = false;
    public $editingRoleId = null;
    public $roleName = '';
    public $roleDescription = '';

    // User-based Overrides
    public $selectedUserId = null;
    public $modulePermissions = [];
    public $search = '';
    public $roleFilter = 'all';

    public $toastMessage = null;
    public $toastType = 'success';

    public const MODULES = [
        'timesheets'          => ['name' => 'Timesheets', 'desc' => 'View, submit, and manage employee hours & clock records', 'icon' => 'fa-solid fa-clock'],
        'schedules'           => ['name' => 'Schedules & Shifts', 'desc' => 'View, create, and assign shifts on the calendar/board', 'icon' => 'fa-solid fa-calendar-days'],
        'jobs'                => ['name' => 'Work Jobs', 'desc' => 'Manage projects, work sites, and ongoing jobs', 'icon' => 'fa-solid fa-briefcase'],
        'tasks'               => ['name' => 'Work Tasks & GPS', 'desc' => 'Manage tasks, GPS geofences, and task billing rates', 'icon' => 'fa-solid fa-list-check'],
        'employees'           => ['name' => 'Employee Directory', 'desc' => 'Manage employee profiles, base pay, and credentials', 'icon' => 'fa-solid fa-users'],
        'departments'         => ['name' => 'Departments', 'desc' => 'Manage company departments and branch teams', 'icon' => 'fa-solid fa-building'],
        'locations'           => ['name' => 'Franchise Locations', 'desc' => 'Manage franchise buildings, store branches, and HQ sites', 'icon' => 'fa-solid fa-map-location-dot'],
        'reports'             => ['name' => 'Reports & Analytics', 'desc' => 'Access attendance, payroll, and productivity reports', 'icon' => 'fa-solid fa-chart-pie'],
        'time_off'            => ['name' => 'Time Off & PTO', 'desc' => 'Request, review, and configure leave policies', 'icon' => 'fa-solid fa-plane-departure'],
        'executive_dashboard' => ['name' => 'Executive Dashboard', 'desc' => 'Access high-level workforce metrics, labor costs, and locked audit', 'icon' => 'fa-solid fa-gauge-high'],
        'webmail'             => ['name' => 'In-App Webmail', 'desc' => 'Access personal in-app mailbox, compose, and send emails', 'icon' => 'fa-solid fa-envelope'],
        'mail_logs'           => ['name' => 'Mail Delivery Logs', 'desc' => 'View system outbound email delivery records and resend', 'icon' => 'fa-solid fa-envelope-open-text'],
        'notifications_log'   => ['name' => 'Notifications Audit', 'desc' => 'Access notification logs, history, and archival', 'icon' => 'fa-solid fa-bell'],
        'company_settings'    => ['name' => 'Company System Settings', 'desc' => 'Configure themes, logos, server mailers, and geofences', 'icon' => 'fa-solid fa-sliders'],
    ];

    public function mount()
    {
        $user = Auth::user();
        if (!$user->canAccessAdmin()) {
            abort(403, 'Unauthorized access to User Access Management.');
        }

        // Initialize default role selection (prefer Employee or Manager or first)
        $defaultRole = Role::where('name', 'Employee')->first() ?: Role::first();
        if ($defaultRole) {
            $this->selectRole($defaultRole->id);
        }

        // Initialize default user selection
        $firstUser = User::notSuperAdmin()->where('id', '!=', $user->id)->first() ?: $user;
        if ($firstUser) {
            $this->selectUser($firstUser->id);
        }
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    // ==========================================
    // ROLE-BASED PERMISSIONS METHODS
    // ==========================================

    public function selectRole($roleId)
    {
        $this->selectedRoleId = $roleId;
        $role = Role::find($roleId);
        if (!$role) return;

        $perms = $role->permissions ?: Role::getDefaultPermissionsForRole($role->name);

        $this->rolePermissions = [];
        foreach (array_keys(self::MODULES) as $mod) {
            if ($role->name === 'Super Admin') {
                $this->rolePermissions[$mod] = 2;
            } else {
                $this->rolePermissions[$mod] = isset($perms[$mod]) ? (int)$perms[$mod] : 1;
            }
        }
    }

    public function setRolePermission($module, $level)
    {
        $this->rolePermissions[$module] = (int)$level;
    }

    public function applyRolePreset($preset)
    {
        foreach (array_keys(self::MODULES) as $mod) {
            if ($preset === 'full') {
                $this->rolePermissions[$mod] = 2;
            } elseif ($preset === 'readonly') {
                $this->rolePermissions[$mod] = 1;
            } elseif ($preset === 'none') {
                $this->rolePermissions[$mod] = 0;
            }
        }
    }

    public function saveRolePermissions()
    {
        $role = Role::find($this->selectedRoleId);
        if (!$role) {
            $this->toastMessage = 'Selected role not found.';
            $this->toastType = 'error';
            return;
        }

        if ($role->name === 'Super Admin') {
            $this->toastMessage = 'Super Admin has immutable master bypass permissions across all modules.';
            $this->toastType = 'error';
            return;
        }

        $role->permissions = $this->rolePermissions;
        $role->save();

        $this->toastMessage = "Access permissions for role '{$role->name}' saved successfully. All users with this role will inherit these settings.";
        $this->toastType = 'success';
    }

    // ==========================================
    // ROLE CRUD METHODS (Admin & Super Admin)
    // ==========================================

    public function openCreateRoleModal()
    {
        $this->resetRoleForm();
        $this->isEditingRole = false;
        $this->showRoleModal = true;
    }

    public function openEditRoleModal($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) return;

        $this->editingRoleId   = $role->id;
        $this->roleName        = $role->name;
        $this->roleDescription = $role->description ?? '';
        $this->isEditingRole   = true;
        $this->showRoleModal   = true;
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->resetRoleForm();
    }

    private function resetRoleForm()
    {
        $this->editingRoleId   = null;
        $this->roleName        = '';
        $this->roleDescription = '';
        $this->resetValidation();
    }

    public function saveRole()
    {
        if ($this->isEditingRole) {
            $this->validate([
                'roleName'        => 'required|string|max:50|unique:roles,name,' . $this->editingRoleId,
                'roleDescription' => 'nullable|string|max:255',
            ]);

            $role = Role::find($this->editingRoleId);
            if (!$role) return;

            // System role names cannot be renamed
            if (!$role->isSystem()) {
                $role->name = trim($this->roleName);
                $role->slug = Str::slug($this->roleName);
            }
            $role->description = $this->roleDescription;
            $role->save();

            $this->toastMessage = "Role '{$role->name}' updated successfully.";
            $this->toastType = 'success';
        } else {
            $this->validate([
                'roleName'        => 'required|string|max:50|unique:roles,name',
                'roleDescription' => 'nullable|string|max:255',
            ]);

            $role = Role::create([
                'name'        => trim($this->roleName),
                'slug'        => Str::slug($this->roleName),
                'description' => $this->roleDescription,
                'is_system'   => false,
                'status'      => 'active',
                'permissions' => array_fill_keys(array_keys(self::MODULES), 1),
            ]);

            $this->selectedRoleId = $role->id;
            $this->selectRole($role->id);

            $this->toastMessage = "Custom role '{$role->name}' created successfully.";
            $this->toastType = 'success';
        }

        $this->closeRoleModal();
    }

    public function deleteRole($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) return;

        if ($role->isSystem()) {
            $this->toastMessage = "System role '{$role->name}' cannot be removed.";
            $this->toastType = 'error';
            return;
        }

        // Strong RDBMS: Check if any child user records exist with this role
        $hasUsers = User::where('role', $role->name)->exists();
        if ($hasUsers) {
            $role->status = 'inactive';
            $role->save();

            $this->toastMessage = "Role '{$role->name}' has child employee records assigned to it. It has been marked as Inactive instead to maintain database integrity.";
            $this->toastType = 'error';
            return;
        }

        $roleName = $role->name;
        $role->delete();

        if ($this->selectedRoleId == $roleId) {
            $next = Role::where('status', 'active')->first();
            if ($next) {
                $this->selectRole($next->id);
            }
        }

        $this->toastMessage = "Role '{$roleName}' deleted successfully.";
        $this->toastType = 'success';
    }

    // ==========================================
    // USER-BASED OVERRIDES METHODS
    // ==========================================

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $user = User::find($userId);

        if (!$user) return;

        $perms = $user->permissions ?? [];

        $this->modulePermissions = [];
        foreach (array_keys(self::MODULES) as $mod) {
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $this->modulePermissions[$mod] = 2;
            } elseif ($user->isHrManager()) {
                $this->modulePermissions[$mod] = in_array($mod, ['company_settings']) ? 1 : 2;
            } else {
                $this->modulePermissions[$mod] = isset($perms[$mod]) ? (int)$perms[$mod] : $user->getEffectivePermission($mod);
            }
        }
    }

    public function setUserPermission($module, $level)
    {
        $this->modulePermissions[$module] = (int)$level;
    }

    public function applyUserPreset($preset)
    {
        foreach (array_keys(self::MODULES) as $mod) {
            if ($preset === 'full') {
                $this->modulePermissions[$mod] = 2;
            } elseif ($preset === 'readonly') {
                $this->modulePermissions[$mod] = 1;
            } elseif ($preset === 'none') {
                $this->modulePermissions[$mod] = 0;
            }
        }
    }

    public function saveUserPermissions()
    {
        $target = User::find($this->selectedUserId);
        if (!$target) {
            $this->toastMessage = 'Selected user not found.';
            $this->toastType = 'error';
            return;
        }

        if ($target->isSuperAdmin()) {
            $this->toastMessage = 'Super Admin permissions cannot be modified.';
            $this->toastType = 'error';
            return;
        }

        $existing = $target->permissions ?? [];
        foreach ($this->modulePermissions as $mod => $lvl) {
            $existing[$mod] = (int)$lvl;
        }

        $target->permissions = $existing;
        $target->save();

        $this->toastMessage = "Individual access overrides for {$target->name} saved successfully.";
        $this->toastType = 'success';
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $roles = Role::orderBy('is_system', 'desc')->orderBy('name')->get();
        $selectedRole = Role::find($this->selectedRoleId);

        $query = User::notSuperAdmin();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('role', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->where('role', $this->roleFilter);
        }

        $users = $query->orderBy('name')->get();
        $selectedUser = User::find($this->selectedUserId);

        return view('livewire.access-management', [
            'roles'        => $roles,
            'selectedRole' => $selectedRole,
            'users'        => $users,
            'selectedUser' => $selectedUser,
            'modules'      => self::MODULES,
            'isSuperAdmin' => Auth::user()->isSuperAdmin(),
        ])->layout('layouts.app');
    }
}
