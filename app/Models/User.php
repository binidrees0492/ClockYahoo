<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;

class User extends Authenticatable implements WebAuthnAuthenticatable
{
    use HasFactory, Notifiable, WebAuthnAuthentication;

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
        'timezone',
        'notification_channel_preference',
        'detailed_notification_settings',
        'standard_shift_start',
        'standard_shift_end',
        'standard_shift_hours',
        'avatar',
        'theme_preference',
        'font_preference',
        'use_custom_email',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_encryption',
        'manager_notification_settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'               => 'datetime',
        'password'                        => 'hashed',
        'permissions'                     => 'array',
        'managed_departments'             => 'array',
        'managed_locations'               => 'array',
        'managed_employees'               => 'array',
        'manager_permissions'             => 'array',
        'detailed_notification_settings'  => 'array',
        'manager_notification_settings'   => 'array',
        'base_pay'                        => 'decimal:2',
        'standard_shift_hours'            => 'decimal:2',
    ];

    public function getTimezone(): string
    {
        try {
            if (function_exists('session')) {
                $tempTz = session('temp_timezone');
                if (!empty($tempTz) && is_string($tempTz)) {
                    return $tempTz;
                }
            }
        } catch (\Throwable $e) {
            // Fallback gracefully if session unavailable
        }
        return $this->timezone ?: \App\Models\CompanySetting::getServerTimezone();
    }

    public function isTemporaryTimezone(): bool
    {
        try {
            if (function_exists('session')) {
                $tempTz = session('temp_timezone');
                return !empty($tempTz);
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    public function getTimezoneAbbr(): string
    {
        try {
            $dt = new \DateTime('now', new \DateTimeZone($this->getTimezone()));
            return $dt->format('T');
        } catch (\Throwable $e) {
            return 'UTC';
        }
    }

    public function toUserTime($datetime, string $format = 'H:i'): string
    {
        if (!$datetime) return '—';
        $carbon = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);
        return $carbon->setTimezone($this->getTimezone())->format($format);
    }

    public function toUserDateTime($datetime, string $format = 'M d, Y H:i'): string
    {
        if (!$datetime) return '—';
        $carbon = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);
        return $carbon->setTimezone($this->getTimezone())->format($format);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'data:')) {
            return $this->avatar;
        }

        $clean = ltrim($this->avatar, '/\\');
        $clean = preg_replace('#^(public/|storage/)+#', '', $clean);

        return url('storage/' . $clean);
    }

    public function getInitialsAttribute(): string
    {
        $name = trim($this->name ?: 'User');
        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    public function getNotificationChannels(): array
    {
        $pref = $this->notification_channel_preference ?? 'both';
        if ($pref === 'bell_only') {
            return ['database'];
        }
        if ($pref === 'email_only') {
            return ['mail'];
        }
        return ['database', 'mail'];
    }

    public function getPersonalNotificationPreferences(): array
    {
        $settings = $this->detailed_notification_settings;
        $channelPref = $this->notification_channel_preference ?: 'both';

        $defaultChannels = [
            'in_app' => $channelPref !== 'email_only',
            'toast'  => $channelPref !== 'email_only',
            'email'  => $channelPref !== 'bell_only',
        ];

        $defaultEvents = [
            'shift_assigned'        => ['in_app' => true, 'toast' => true, 'email' => true],
            'shift_updated'         => ['in_app' => true, 'toast' => true, 'email' => true],
            'shift_published'       => ['in_app' => true, 'toast' => true, 'email' => true],
            'clock_in'              => ['in_app' => true, 'toast' => true, 'email' => false],
            'clock_out'             => ['in_app' => true, 'toast' => true, 'email' => false],
            'time_submitted'        => ['in_app' => true, 'toast' => true, 'email' => false],
            'timesheet_approved'    => ['in_app' => true, 'toast' => true, 'email' => true],
            'timesheet_disapproved' => ['in_app' => true, 'toast' => true, 'email' => true],
            'time_off_submitted'    => ['in_app' => true, 'toast' => true, 'email' => true],
            'time_off_decided'      => ['in_app' => true, 'toast' => true, 'email' => true],
            'unusual_activity'      => ['in_app' => true, 'toast' => true, 'email' => true],
            'geofence_breach'       => ['in_app' => true, 'toast' => true, 'email' => true],
            'system_alert'          => ['in_app' => true, 'toast' => true, 'email' => true],
        ];

        if (!is_array($settings) || empty($settings)) {
            return [
                'channels' => $defaultChannels,
                'events'   => $defaultEvents,
            ];
        }

        if (isset($settings['events']) && is_array($settings['events'])) {
            $events = array_merge($defaultEvents, $settings['events']);
            $channels = isset($settings['channels']) && is_array($settings['channels'])
                ? array_merge($defaultChannels, $settings['channels'])
                : $defaultChannels;
            return [
                'channels' => $channels,
                'events'   => $events,
            ];
        }

        // Support legacy flat settings format: ['shift_assigned' => ['bell' => 1, 'email' => 1], ...]
        $events = $defaultEvents;
        foreach ($settings as $key => $val) {
            if (is_array($val)) {
                $mappedKey = match ($key) {
                    'time_approval' => 'timesheet_approved',
                    default => $key,
                };
                if (isset($events[$mappedKey])) {
                    $hasBell = !empty($val['bell']) || !empty($val['in_app']);
                    $hasToast = isset($val['toast']) ? !empty($val['toast']) : $hasBell;
                    $hasEmail = !empty($val['email']);
                    $events[$mappedKey] = [
                        'in_app' => $hasBell,
                        'toast'  => $hasToast,
                        'email'  => $hasEmail,
                    ];
                }
            }
        }

        return [
            'channels' => $defaultChannels,
            'events'   => $events,
        ];
    }

    public function prefersNotificationChannel(string $eventKey, string $channel): bool
    {
        $prefs = $this->getPersonalNotificationPreferences();

        // 1. Global master channel toggle
        if ($channel === 'in_app' && empty($prefs['channels']['in_app'])) {
            return false;
        }
        if ($channel === 'toast' && empty($prefs['channels']['toast'])) {
            return false;
        }
        if ($channel === 'email' && empty($prefs['channels']['email'])) {
            return false;
        }

        // If checking global toggle only
        if ($eventKey === 'global') {
            return true;
        }

        // 2. Specific event preference
        $eventPrefs = $prefs['events'][$eventKey] ?? null;
        if ($eventPrefs && is_array($eventPrefs)) {
            if (array_key_exists($channel, $eventPrefs)) {
                return (bool) $eventPrefs[$channel];
            }
        }

        $alias = match ($eventKey) {
            'timesheet_approved', 'timesheet_disapproved' => 'time_approval',
            default => null,
        };
        if ($alias && isset($prefs['events'][$alias][$channel])) {
            return (bool) $prefs['events'][$alias][$channel];
        }

        return true;
    }

    public function getNotificationChannelsFor(string $event): array
    {
        $channels = [];
        if ($this->prefersNotificationChannel($event, 'in_app')) {
            $channels[] = 'database';
        }
        if ($this->prefersNotificationChannel($event, 'email')) {
            $channels[] = 'mail';
        }
        return !empty($channels) ? $channels : [];
    }

    public function getManagerNotificationSettings(): array
    {
        $settings = $this->manager_notification_settings;
        if (is_array($settings) && !empty($settings)) {
            return $settings;
        }

        return [
            'scope'                 => 'department',
            'notify_clock_in'       => ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_clock_out'      => ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_time_submitted' => ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_time_off'       => ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_shift_assigned' => ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_unusual'        => ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_geofence'       => ['in_app' => true, 'toast' => true, 'email' => true],
        ];
    }

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

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, ['Super Admin', 'SuperAdmin', 'super_admin', 'Super Administrator']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Administrator' || $this->isSuperAdmin();
    }

    public function isHrManager(): bool
    {
        return $this->role === 'HR Manager' || in_array(strtolower($this->role ?? ''), ['hr', 'hr manager', 'hr_manager']);
    }

    public function isManager(): bool
    {
        return $this->role === 'Manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'Employee';
    }

    public function canHardDelete(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!empty($this->permissions['allow_hard_delete']) || !empty($this->permissions['can_hard_delete'])) {
            return true;
        }

        if ($this->isHrManager() && (bool) \App\Models\CompanySetting::get('allow_hr_hard_delete', false)) {
            return true;
        }

        return false;
    }

    public function scopeNotSuperAdmin($query)
    {
        return $query->whereNotIn('role', ['Super Admin', 'SuperAdmin', 'super_admin']);
    }

    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function getEffectivePermission(string $module): int
    {
        if ($this->isSuperAdmin()) {
            return self::ACCESS_WRITE;
        }

        // 1. Explicit user-level override if set
        if (isset($this->permissions[$module]) && $this->permissions[$module] !== null && $this->permissions[$module] !== '') {
            return (int) $this->permissions[$module];
        }

        // 2. Role-based permissions from database
        if ($this->role) {
            $roleRecord = Role::where('name', $this->role)->first();
            if ($roleRecord && isset($roleRecord->permissions[$module])) {
                return (int) $roleRecord->permissions[$module];
            }
            $defaults = Role::getDefaultPermissionsForRole($this->role);
            if (isset($defaults[$module])) {
                return (int) $defaults[$module];
            }
        }

        if ($this->isAdmin() || $this->isHrManager()) {
            return self::ACCESS_WRITE;
        }

        return self::ACCESS_NONE;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager();
    }

    public function canManageTimeOff(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager() || ($this->isManager() && $this->canManagerDo('manage_time_off'));
    }

    public function canWebClock(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        return (bool) ($this->permissions['allow_web_clock'] ?? true);
    }

    public function hasSchedules(): bool
    {
        return Schedule::where('employee_id', $this->id)->exists();
    }

    public function getAssignedJobs(?string $date = null, ?string $time = null, ?string $endDate = null)
    {
        if ($this->isAdmin() || $this->isHrManager() || $this->isManager()) {
            return Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get();
        }

        $hasSchedules = $this->hasSchedules();

        $assignedIds = [];
        if (isset($this->permissions['assigned_jobs']) && is_array($this->permissions['assigned_jobs'])) {
            $assignedIds = array_map('intval', $this->permissions['assigned_jobs']);
        }

        // Include jobs assigned to the employee via published schedules
        $schedQuery = Schedule::where('employee_id', $this->id)
            ->whereNotNull('assignment_id')
            ->published();

        if ($date !== null) {
            if ($endDate !== null && $endDate !== $date) {
                $schedQuery->whereBetween('shift_date', [min($date, $endDate), max($date, $endDate)]);
            } else {
                $schedQuery->whereDate('shift_date', $date);
            }
        }

        $schedules = $schedQuery->get();

        if ($time !== null && $date !== null) {
            $schedules = $schedules->filter(function ($s) use ($date, $time) {
                try {
                    $dt = \Carbon\Carbon::parse($date . ' ' . $time);
                    return $s->coversTime($dt);
                } catch (\Throwable $e) {
                    return true;
                }
            });
        }

        $scheduledJobIds = $schedules->pluck('assignment_id')->map(fn($id) => (int)$id)->all();

        $allIds = array_values(array_unique(array_filter(array_merge($assignedIds, $scheduledJobIds))));

        if (empty($allIds)) {
            return Assignment::whereRaw('1 = 0')->get();
        }

        return Assignment::where('status', '!=', 'inactive')->ongoing()->whereIn('id', $allIds)->orderBy('name')->get();
    }

    public function getJobShiftConfig(int $jobId): ?array
    {
        $shifts = $this->permissions['job_shifts'] ?? [];
        if (isset($shifts[$jobId])) {
            return $shifts[$jobId];
        }
        if (isset($shifts[(string)$jobId])) {
            return $shifts[(string)$jobId];
        }
        return null;
    }

    public function getAssignedTasks(?int $jobId = null, ?string $date = null, ?string $time = null, ?string $endDate = null)
    {
        $query = Task::where('status', '!=', 'inactive')->ongoing();

        if ($jobId !== null) {
            $query->where(function ($q) use ($jobId) {
                $q->where('assignment_id', $jobId)->orWhereNull('assignment_id');
            });
        }

        if ($this->isAdmin() || $this->isHrManager() || $this->isManager()) {
            return $query->orderBy('name')->get();
        }

        $hasSchedules = $this->hasSchedules();

        $assignedIds = [];
        if (isset($this->permissions['assigned_tasks']) && is_array($this->permissions['assigned_tasks'])) {
            $assignedIds = array_map('intval', $this->permissions['assigned_tasks']);
        }

        // Include tasks assigned to the employee via published schedules
        $schedQuery = Schedule::where('employee_id', $this->id)
            ->whereNotNull('task_id')
            ->published();

        if ($jobId !== null) {
            $schedQuery->where(function ($q) use ($jobId) {
                $q->where('assignment_id', $jobId)->orWhereNull('assignment_id');
            });
        }

        if ($date !== null) {
            if ($endDate !== null && $endDate !== $date) {
                $schedQuery->whereBetween('shift_date', [min($date, $endDate), max($date, $endDate)]);
            } else {
                $schedQuery->whereDate('shift_date', $date);
            }
        }

        $schedules = $schedQuery->get();

        if ($time !== null && $date !== null) {
            $schedules = $schedules->filter(function ($s) use ($date, $time) {
                try {
                    $dt = \Carbon\Carbon::parse($date . ' ' . $time);
                    return $s->coversTime($dt);
                } catch (\Throwable $e) {
                    return true;
                }
            });
        }

        $scheduledTaskIds = $schedules->pluck('task_id')->map(fn($id) => (int)$id)->all();

        $allIds = array_values(array_unique(array_filter(array_merge($assignedIds, $scheduledTaskIds))));

        if (empty($allIds)) {
            if (isset($this->permissions['assigned_tasks']) && is_array($this->permissions['assigned_tasks'])) {
                return Task::whereRaw('1 = 0')->get();
            }
            if ($date !== null && $hasSchedules) {
                return Task::whereRaw('1 = 0')->get();
            }
            if ($jobId !== null) {
                return $query->orderBy('name')->get();
            }
            return Task::whereRaw('1 = 0')->get();
        }

        return $query->whereIn('id', $allIds)->orderBy('name')->get();
    }

    public function canApproveTimesheets(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerDo('approve_timesheets');
        }

        return false;
    }

    public function canViewMap(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerDo('view_map_location');
        }

        return $this->getEffectivePermission('locations') >= self::ACCESS_READONLY;
    }

    public function canViewReports(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            if (isset($this->manager_permissions['view_reports'])) {
                return (bool) $this->manager_permissions['view_reports'];
            }
            if (isset($this->permissions['view_reports'])) {
                return (bool) $this->permissions['view_reports'];
            }
            return $this->canView('reports');
        }

        return $this->canView('reports');
    }

    public function canAccessTimesheets(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerView('timesheets') || $this->canView('mobile_timesheets');
        }

        return $this->canView('mobile_timesheets') || $this->canView('timesheets');
    }

    public function canEditTimesheets(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerEdit('timesheets') || $this->canEdit('mobile_timesheets');
        }

        return $this->canEdit('mobile_timesheets') || $this->canEdit('timesheets');
    }

    public function canAccessSchedules(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerView('schedules') || $this->canView('schedules');
        }

        return $this->canView('schedules');
    }

    public function canEditSchedules(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if ($this->isManager()) {
            return $this->canManagerEdit('schedules') || $this->canEdit('schedules');
        }

        return $this->canEdit('schedules');
    }

    public function canView(string $module): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        return $this->getEffectivePermission($module) >= self::ACCESS_READONLY;
    }

    public function canEdit(string $module): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        return $this->getEffectivePermission($module) >= self::ACCESS_WRITE;
    }

    public function hasModuleAccess(string $module, string $level = 'read'): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        $perm = $this->getEffectivePermission($module);
        if ($level === 'write') {
            return $perm >= self::ACCESS_WRITE;
        }
        return $perm >= self::ACCESS_READONLY;
    }

    public function canAccessExecutiveDashboard(): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }
        return $this->hasModuleAccess('executive_dashboard', 'read');
    }

    public function canManagerView(string $module): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        $access = (int) ($this->manager_permissions[$module] ?? $this->permissions[$module] ?? self::ACCESS_NONE);
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

        $access = (int) ($this->manager_permissions[$module] ?? $this->permissions[$module] ?? self::ACCESS_NONE);
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

        return (bool) ($this->manager_permissions[$action] ?? $this->permissions[$action] ?? false);
    }

    public function getAccessibleEmployeeIds(): ?array
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return null; // Unrestricted (Super Admin, Administrator, HR Manager)
        }

        if ($this->isManager()) {
            if (($this->managed_team_type ?? 'restricted') === 'all') {
                return null;
            }

            $ids = [(int) $this->id];

            // Manager's own department
            if ($this->department_id) {
                $deptUserIds = self::where('department_id', $this->department_id)->pluck('id')->map(fn($uid) => (int)$uid)->toArray();
                $ids = array_merge($ids, $deptUserIds);
            }

            // Manager's managed departments
            if (!empty($this->managed_departments) && is_array($this->managed_departments)) {
                $deptUserIds = self::whereIn('department_id', $this->managed_departments)->pluck('id')->map(fn($uid) => (int)$uid)->toArray();
                $ids = array_merge($ids, $deptUserIds);
            }

            // Direct reports
            $directReports = self::where('manager_id', $this->id)->pluck('id')->map(fn($id) => (int)$id)->toArray();
            $ids = array_merge($ids, $directReports);

            // Managed entities
            $entities = $this->managed_employees ?? [];
            foreach ($entities as $item) {
                $type = $item['type'] ?? null;
                $id   = (int) ($item['id'] ?? 0);
                if (!$id) continue;

                if ($type === 'emp') {
                    $ids[] = $id;
                } elseif ($type === 'dept') {
                    $deptUserIds = self::where('department_id', $id)->pluck('id')->map(fn($uid) => (int)$uid)->toArray();
                    $ids = array_merge($ids, $deptUserIds);
                } elseif ($type === 'loc') {
                    $locUserIds = self::where('location_id', $id)->pluck('id')->map(fn($uid) => (int)$uid)->toArray();
                    $ids = array_merge($ids, $locUserIds);
                }
            }

            return array_values(array_unique($ids));
        }

        // Standard employee can only access themself
        return [(int) $this->id];
    }

    public function canManageEmployee(User|int $target): bool
    {
        if ($this->isAdmin() || $this->isHrManager()) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        $targetId = $target instanceof User ? (int) $target->id : (int) $target;
        if ($targetId === (int) $this->id) {
            return true;
        }

        $accessible = $this->getAccessibleEmployeeIds();
        if ($accessible === null) {
            return true;
        }

        return in_array($targetId, $accessible, true);
    }

    public function emailLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\EmailLog::class);
    }
}
