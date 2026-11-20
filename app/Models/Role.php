<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'status',
        'permissions',
    ];

    protected $casts = [
        'is_system'   => 'boolean',
        'permissions' => 'array',
    ];

    public const SYSTEM_ROLES = [
        'Super Admin',
        'Administrator',
        'HR Manager',
        'Manager',
        'Employee',
    ];

    public function isSystem(): bool
    {
        return (bool) $this->is_system || in_array($this->name, self::SYSTEM_ROLES);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    public static function getDefaultPermissionsForRole(string $role): array
    {
        $allModules = [
            'timesheets', 'schedules', 'jobs', 'tasks', 'employees',
            'departments', 'locations', 'reports', 'time_off',
            'executive_dashboard', 'webmail', 'mail_logs',
            'notifications_log', 'company_settings'
        ];

        if ($role === 'Super Admin' || $role === 'Administrator') {
            return array_fill_keys($allModules, 2); // Full Write
        }

        if ($role === 'HR Manager') {
            $perms = array_fill_keys($allModules, 2);
            $perms['company_settings'] = 1;
            return $perms;
        }

        if ($role === 'Manager') {
            return [
                'timesheets'          => 2,
                'schedules'           => 2,
                'jobs'                => 1,
                'tasks'               => 1,
                'employees'           => 1,
                'departments'         => 1,
                'locations'           => 1,
                'reports'             => 1,
                'time_off'            => 2,
                'executive_dashboard' => 0,
                'webmail'             => 2,
                'mail_logs'           => 0,
                'notifications_log'   => 1,
                'company_settings'    => 0,
            ];
        }

        // Employee
        return [
            'timesheets'          => 2,
            'schedules'           => 1,
            'jobs'                => 1,
            'tasks'               => 1,
            'employees'           => 0,
            'departments'         => 0,
            'locations'           => 0,
            'reports'             => 0,
            'time_off'            => 1,
            'executive_dashboard' => 0,
            'webmail'             => 2,
            'mail_logs'           => 0,
            'notifications_log'   => 1,
            'company_settings'    => 0,
        ];
    }
}
