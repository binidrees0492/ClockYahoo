<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    protected $table = 'notification_rules';

    protected $fillable = [
        'event_key',
        'name',
        'category',
        'description',
        'person_involved_enabled',
        'person_involved_channels',
        'roles_config',
        'manager_scope',
        'is_active',
    ];

    protected $casts = [
        'person_involved_enabled'  => 'boolean',
        'person_involved_channels' => 'array',
        'roles_config'            => 'array',
        'is_active'               => 'boolean',
    ];

    public const CATEGORIES = [
        'Scheduling',
        'Time Tracking',
        'Timesheet Approvals',
        'Time Off',
        'Compliance & Alerts',
        'System',
    ];

    public const ROLES = [
        'Super Admin',
        'Administrator',
        'HR Manager',
        'Manager',
        'Employee',
    ];

    public const CHANNELS = [
        'in_app' => 'In-App Bell Icon',
        'toast'  => 'Real-Time Pop-Up Toast',
        'email'  => 'Email Notification',
    ];

    /**
     * Check if a specific role is enabled for this event rule.
     */
    public function isRoleEnabled(string $role): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $config = $this->roles_config[$role] ?? null;
        if (!$config) {
            return false;
        }

        return !empty($config['enabled']);
    }

    /**
     * Get channels enabled for a specific role.
     */
    public function getRoleChannels(string $role): array
    {
        if (!$this->isRoleEnabled($role)) {
            return [];
        }

        $config = $this->roles_config[$role] ?? [];
        $channels = [];
        if (!empty($config['in_app'])) {
            $channels[] = 'in_app';
        }
        if (!empty($config['toast'])) {
            $channels[] = 'toast';
        }
        if (!empty($config['email'])) {
            $channels[] = 'email';
        }

        return $channels;
    }

    /**
     * Get manager scope (department, direct_reports, all, none).
     */
    public function getManagerScope(): string
    {
        return $this->manager_scope ?: ($this->roles_config['Manager']['scope'] ?? 'department');
    }

    /**
     * Find rule by event_key.
     */
    public static function getRule(string $eventKey): ?self
    {
        return static::where('event_key', $eventKey)->first();
    }

    /**
     * Return list of disabled event keys for a given user role.
     */
    public static function getDisabledEventsForRole(string $role): array
    {
        $all = static::all();
        $disabled = [];

        foreach ($all as $rule) {
            if (!$rule->is_active || !$rule->isRoleEnabled($role)) {
                $disabled[] = $rule->event_key;
            }
        }

        return $disabled;
    }

    /**
     * Check if an event is allowed for a user.
     */
    public static function isAllowedForUser(string $eventKey, User $user, bool $asPersonInvolved = false): bool
    {
        $rule = static::getRule($eventKey);
        if (!$rule || !$rule->is_active) {
            return false;
        }

        if ($asPersonInvolved) {
            return (bool) $rule->person_involved_enabled;
        }

        $role = $user->role ?: 'Employee';
        return $rule->isRoleEnabled($role);
    }
}
