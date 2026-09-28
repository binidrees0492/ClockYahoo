<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ACCESS_NONE     = 0;
    public const ACCESS_READONLY = 1;
    public const ACCESS_WRITE    = 2;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'display_id',
        'role', // Administrator, HR Manager, Manager, Employee
        'status',
        'color',
        'base_pay',
        'department_id',
        'location_id',
        'manager_id',
        'permissions',
        'managed_team_type',
        'managed_departments',
        'managed_locations',
        'managed_employees',
        'manager_permissions',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'password'            => 'hashed',
        'permissions'         => 'array',
        'managed_departments' => 'array',
        'managed_locations'   => 'array',
        'managed_employees'   => 'array',
        'manager_permissions' => 'array',
        'base_pay'            => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function directReports()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function timeOffPolicyEmployees()
    {
        return $this->hasMany(TimeOffPolicyEmployee::class, 'employee_id');
    }

    public function assignedPolicies()
    {
        return $this->belongsToMany(TimeOffPolicy::class, 'time_off_policy_employees', 'employee_id', 'policy_id')
            ->withPivot(['hire_date', 'hours_remaining'])
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Administrator';
    }

    public function isHrManager(): bool
    {
        return $this->role === 'HR Manager';
    }

    public function isManager(): bool
    {
        return $this->role === 'Manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'Employee';
    }

    public function canManageTimeOff(): bool
    {
        return $this->isAdmin() || $this->isHrManager() || ($this->isManager() && $this->canManagerDo('manage_time_off'));
    }

    public function canView(string $module): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        $access = (int) ($this->permissions[$module] ?? self::ACCESS_NONE);
        return $access >= self::ACCESS_READONLY;
    }

    public function canEdit(string $module): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        $access = (int) ($this->permissions[$module] ?? self::ACCESS_NONE);
        return $access >= self::ACCESS_WRITE;
    }

    public function canManagerView(string $module): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        $access = (int) ($this->manager_permissions[$module] ?? self::ACCESS_NONE);
        return $access >= self::ACCESS_READONLY;
    }

    public function canManagerEdit(string $module): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        $access = (int) ($this->manager_permissions[$module] ?? self::ACCESS_NONE);
        return $access >= self::ACCESS_WRITE;
    }

    public function canManagerDo(string $action): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        return (bool) ($this->manager_permissions[$action] ?? false);
    }

    public function canManageEmployee(User|int $target): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        $targetUser = $target instanceof User ? $target : User::find($target);
        if (!$targetUser) {
            return false;
        }

        if ((int) $targetUser->manager_id === (int) $this->id) {
            return true;
        }

        if ($this->managed_team_type === 'all') {
            return true;
        }

        $entities = $this->managed_employees ?? [];

        foreach ($entities as $item) {
            $type = $item['type'] ?? null;
            $id   = (int) ($item['id'] ?? 0);

            if ($type === 'emp' && (int) $targetUser->id === $id) {
                return true;
            }
            if ($type === 'dept' && (int) $targetUser->department_id === $id) {
                return true;
            }
            if ($type === 'loc' && (int) $targetUser->location_id === $id) {
                return true;
            }
        }

        return false;
    }
}
