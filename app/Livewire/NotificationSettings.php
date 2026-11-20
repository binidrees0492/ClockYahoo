<?php

namespace App\Livewire;

use App\Models\CompanySetting;
use App\Models\NotificationRule;
use App\Models\User;
use App\Services\NotificationRoutingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationSettings extends Component
{
    public $activeTab = 'personal'; // 'personal' | 'rules' | 'manager' | 'email'
    public $selectedCategory = 'all';

    // Editable notification rules structure
    public $rules = [];

    // Notification Email Configuration
    public $notificationFromEmail = '';
    public $notificationFromName = '';
    public $notificationReplyTo = '';
    public $notificationEmailEnabled = true;

    // Test email
    public $testEmailRecipient = '';
    public $testEmailResult = null;

    // Manager Preferences
    public $managerScope = 'department';
    public $managerActivities = [];

    // Personal Notification Preferences (For Every Individual User)
    public $personalChannels = [
        'in_app' => true,
        'toast'  => true,
        'email'  => true,
    ];
    public $personalEvents = [];
    public $personalChannelPreference = 'both';

    // Toast
    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $tabParam = request()->query('tab');
        if ($tabParam && in_array($tabParam, ['rules', 'personal', 'manager', 'email'])) {
            $this->activeTab = $tabParam;
        } elseif ($user->canAccessAdmin()) {
            $this->activeTab = 'rules';
        } else {
            $this->activeTab = 'personal';
        }

        $this->loadPersonalPreferences();
        $this->loadRules();
        $this->loadEmailSettings();
        $this->loadManagerSettings();
    }

    public function loadPersonalPreferences()
    {
        $user = Auth::user();
        $prefs = $user->getPersonalNotificationPreferences();

        $this->personalChannels = $prefs['channels'];
        $this->personalEvents   = $prefs['events'];
        $this->personalChannelPreference = $user->notification_channel_preference ?: 'both';
    }

    public function loadRules()
    {
        $allRules = NotificationRule::orderBy('category')->orderBy('name')->get();
        $this->rules = [];

        foreach ($allRules as $r) {
            $this->rules[$r->id] = [
                'id'                      => $r->id,
                'event_key'               => $r->event_key,
                'name'                    => $r->name,
                'category'                => $r->category,
                'description'             => $r->description,
                'person_involved_enabled' => (bool) $r->person_involved_enabled,
                'person_involved_channels'=> $r->person_involved_channels ?: ['in_app', 'toast', 'email'],
                'roles_config'            => $r->roles_config ?: [],
                'manager_scope'           => $r->manager_scope ?: 'department',
                'is_active'               => (bool) $r->is_active,
            ];
        }
    }

    public function loadEmailSettings()
    {
        $sender = NotificationRoutingService::getNotificationSender();
        $this->notificationFromEmail    = CompanySetting::get('notification_from_email', $sender['address']);
        $this->notificationFromName     = CompanySetting::get('notification_from_name', $sender['name']);
        $this->notificationReplyTo      = CompanySetting::get('notification_reply_to', $sender['reply_to'] ?: '');
        $this->notificationEmailEnabled = (bool) CompanySetting::get('notification_email_enabled', true);
        $this->testEmailRecipient       = Auth::user()?->email ?: '';
    }

    public function loadManagerSettings()
    {
        $user = Auth::user();
        $settings = $user->getManagerNotificationSettings();

        $this->managerScope = $settings['scope'] ?? 'department';
        $this->managerActivities = [
            'notify_clock_in'       => $settings['notify_clock_in'] ?? ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_clock_out'      => $settings['notify_clock_out'] ?? ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_time_submitted' => $settings['notify_time_submitted'] ?? ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_time_off'       => $settings['notify_time_off'] ?? ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_shift_assigned' => $settings['notify_shift_assigned'] ?? ['in_app' => true, 'toast' => true, 'email' => false],
            'notify_unusual'        => $settings['notify_unusual'] ?? ['in_app' => true, 'toast' => true, 'email' => true],
            'notify_geofence'       => $settings['notify_geofence'] ?? ['in_app' => true, 'toast' => true, 'email' => true],
        ];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function setCategory($cat)
    {
        $this->selectedCategory = $cat;
    }

    public function toggleRuleActive($ruleId)
    {
        if (isset($this->rules[$ruleId])) {
            $this->rules[$ruleId]['is_active'] = !$this->rules[$ruleId]['is_active'];
        }
    }

    public function togglePersonInvolved($ruleId)
    {
        if (isset($this->rules[$ruleId])) {
            $this->rules[$ruleId]['person_involved_enabled'] = !$this->rules[$ruleId]['person_involved_enabled'];
        }
    }

    public function togglePersonChannel($ruleId, $channel)
    {
        if (isset($this->rules[$ruleId])) {
            $channels = $this->rules[$ruleId]['person_involved_channels'] ?? [];
            if (in_array($channel, $channels)) {
                $channels = array_values(array_filter($channels, fn($c) => $c !== $channel));
            } else {
                $channels[] = $channel;
            }
            $this->rules[$ruleId]['person_involved_channels'] = $channels;
        }
    }

    public function toggleRoleChannel($ruleId, $role, $channel)
    {
        if (isset($this->rules[$ruleId])) {
            $curr = $this->rules[$ruleId]['roles_config'][$role][$channel] ?? false;
            $this->rules[$ruleId]['roles_config'][$role][$channel] = !$curr;

            // If enabling any channel, make sure role enabled is true
            if (!$curr) {
                $this->rules[$ruleId]['roles_config'][$role]['enabled'] = true;
            }
        }
    }

    public function toggleRoleEnabled($ruleId, $role)
    {
        if (isset($this->rules[$ruleId])) {
            $curr = $this->rules[$ruleId]['roles_config'][$role]['enabled'] ?? false;
            $newVal = !$curr;
            $this->rules[$ruleId]['roles_config'][$role]['enabled'] = $newVal;

            if (!$newVal) {
                $this->rules[$ruleId]['roles_config'][$role]['in_app'] = false;
                $this->rules[$ruleId]['roles_config'][$role]['toast']  = false;
                $this->rules[$ruleId]['roles_config'][$role]['email']  = false;
            } else {
                $this->rules[$ruleId]['roles_config'][$role]['in_app'] = true;
                $this->rules[$ruleId]['roles_config'][$role]['toast']  = true;
            }
        }
    }

    public function saveRules()
    {
        $user = Auth::user();
        if (!$user->canAccessAdmin()) {
            $this->toastMessage = 'Only administrators can update global role routing rules.';
            $this->toastType = 'error';
            return;
        }

        foreach ($this->rules as $id => $ruleData) {
            $record = NotificationRule::find($id);
            if ($record) {
                $record->update([
                    'person_involved_enabled'  => (bool) $ruleData['person_involved_enabled'],
                    'person_involved_channels' => $ruleData['person_involved_channels'],
                    'roles_config'             => $ruleData['roles_config'],
                    'manager_scope'            => $ruleData['manager_scope'] ?? 'department',
                    'is_active'                => (bool) $ruleData['is_active'],
                ]);
            }
        }

        $this->setToast('Notification routing rules successfully updated.', 'success');
    }

    public function saveEmailSettings()
    {
        $user = Auth::user();
        if (!$user->canAccessAdmin()) {
            $this->setToast('Only administrators can update email server sender configuration.', 'error');
            return;
        }

        $this->validate([
            'notificationFromEmail' => 'required|email|max:255',
            'notificationFromName'  => 'required|string|max:255',
            'notificationReplyTo'   => 'nullable|email|max:255',
        ]);

        CompanySetting::set('notification_from_email', trim($this->notificationFromEmail));
        CompanySetting::set('notification_from_name', trim($this->notificationFromName));
        CompanySetting::set('notification_reply_to', trim($this->notificationReplyTo));
        CompanySetting::set('notification_email_enabled', (bool) $this->notificationEmailEnabled);

        $this->setToast('Notification sender email settings saved successfully.', 'success');
    }

    public function sendTestEmail()
    {
        $this->validate([
            'testEmailRecipient' => 'required|email|max:255',
        ]);

        $res = NotificationRoutingService::sendTestEmail($this->testEmailRecipient);
        $this->testEmailResult = $res;

        if ($res['success']) {
            $this->setToast($res['message'], 'success');
        } else {
            $this->setToast($res['message'], 'error');
        }
    }

    public function saveManagerSettings()
    {
        $user = Auth::user();

        $settings = array_merge($this->managerActivities, [
            'scope' => $this->managerScope,
        ]);

        $user->update([
            'manager_notification_settings' => $settings,
        ]);

        // Also if user is an admin, optionally propagate default manager scope to rules
        if ($user->canAccessAdmin()) {
            NotificationRule::where('id', '>', 0)->update([
                'manager_scope' => $this->managerScope,
            ]);
        }

        $this->setToast('Manager employee activity notification preferences saved.', 'success');
    }

    public function togglePersonalGlobalChannel($channel)
    {
        $this->personalChannels[$channel] = !($this->personalChannels[$channel] ?? true);
        if ($channel === 'email' && !$this->personalChannels['email']) {
            $this->personalChannelPreference = $this->personalChannels['in_app'] ? 'bell_only' : 'none';
        } elseif ($channel === 'in_app' && !$this->personalChannels['in_app']) {
            $this->personalChannelPreference = $this->personalChannels['email'] ? 'email_only' : 'none';
        } else {
            $this->personalChannelPreference = 'both';
        }
    }

    public function togglePersonalEventChannel($eventKey, $channel)
    {
        if (!isset($this->personalEvents[$eventKey])) {
            $this->personalEvents[$eventKey] = ['in_app' => true, 'toast' => true, 'email' => true];
        }
        $curr = $this->personalEvents[$eventKey][$channel] ?? false;
        $this->personalEvents[$eventKey][$channel] = !$curr;
    }

    public function togglePersonalEventAll($eventKey, $target = null)
    {
        if (!isset($this->personalEvents[$eventKey])) {
            $this->personalEvents[$eventKey] = ['in_app' => true, 'toast' => true, 'email' => true];
        }
        $currentAny = !empty($this->personalEvents[$eventKey]['in_app']) || !empty($this->personalEvents[$eventKey]['email']);
        $newVal = $target !== null ? (bool) $target : !$currentAny;
        $this->personalEvents[$eventKey]['in_app'] = $newVal;
        $this->personalEvents[$eventKey]['toast']  = $newVal;
        $this->personalEvents[$eventKey]['email']  = $newVal;
    }

    public function enableAllPersonalChannels()
    {
        $this->personalChannels = ['in_app' => true, 'toast' => true, 'email' => true];
        $this->personalChannelPreference = 'both';
        foreach ($this->personalEvents as $key => $val) {
            $this->personalEvents[$key] = ['in_app' => true, 'toast' => true, 'email' => true];
        }
        $this->setToast('All notification channels and alert types enabled for your account.', 'success');
    }

    public function muteAllPersonalNotifications()
    {
        $this->personalChannels = ['in_app' => false, 'toast' => false, 'email' => false];
        $this->personalChannelPreference = 'none';
        foreach ($this->personalEvents as $key => $val) {
            $this->personalEvents[$key] = ['in_app' => false, 'toast' => false, 'email' => false];
        }
        $this->setToast('All personal notifications muted for your account.', 'info');
    }

    public function restoreDefaultPersonalPreferences()
    {
        $user = Auth::user();
        $user->update([
            'notification_channel_preference' => 'both',
            'detailed_notification_settings'  => null,
        ]);
        $this->loadPersonalPreferences();
        $this->setToast('Personal notification preferences restored to system defaults.', 'success');
    }

    public function savePersonalPreferences()
    {
        $user = Auth::user();

        $user->update([
            'notification_channel_preference' => $this->personalChannelPreference,
            'detailed_notification_settings'  => [
                'channels' => $this->personalChannels,
                'events'   => $this->personalEvents,
            ],
        ]);

        $this->setToast('Your personal notification preferences have been saved successfully.', 'success');
    }

    public function resetDefaultRules()
    {
        $user = Auth::user();
        if (!$user->canAccessAdmin()) {
            $this->setToast('Unauthorized.', 'error');
            return;
        }

        // Re-run the migration logic for defaults
        $migration = new \CreateNotificationRulesAndSettingsTable();
        // Or update each rule to active with defaults
        foreach (NotificationRule::all() as $r) {
            $r->update([
                'is_active' => true,
                'person_involved_enabled' => true,
            ]);
        }

        $this->loadRules();
        $this->setToast('Notification rules restored to default policy.', 'success');
    }

    public function setToast($message, $type = 'success')
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->dispatch('toast', message: $message, type: $type);
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $user = Auth::user();

        $categories = NotificationRule::CATEGORIES;
        $roles = NotificationRule::ROLES;

        $filteredRules = collect($this->rules);
        if ($this->selectedCategory !== 'all') {
            $filteredRules = $filteredRules->where('category', $this->selectedCategory);
        }

        $eventsByCategory = [
            'Scheduling' => [
                'shift_assigned'  => ['name' => 'Shift Assigned', 'desc' => 'When a new shift or work schedule is assigned to you', 'icon' => 'fa-calendar-plus', 'color' => '#005a9c'],
                'shift_updated'   => ['name' => 'Shift Updated / Cancelled', 'desc' => 'When your scheduled shift time or location changes', 'icon' => 'fa-calendar-check', 'color' => '#0284c7'],
                'shift_published' => ['name' => 'Weekly Schedule Published', 'desc' => 'When the company publishes the full week team schedule', 'icon' => 'fa-bullhorn', 'color' => '#059669'],
            ],
            'Time Tracking' => [
                'clock_in'  => ['name' => 'Clock-In Confirmation', 'desc' => 'Confirmation when you start working a shift', 'icon' => 'fa-right-to-bracket', 'color' => '#16a34a'],
                'clock_out' => ['name' => 'Clock-Out Confirmation', 'desc' => 'Summary of recorded hours when you finish working', 'icon' => 'fa-right-from-bracket', 'color' => '#ea580c'],
            ],
            'Timesheet Approvals' => [
                'time_submitted'        => ['name' => 'Timesheet Submitted', 'desc' => 'Notice when your time logs are submitted for review', 'icon' => 'fa-clock-rotate-left', 'color' => '#ca8a04'],
                'timesheet_approved'    => ['name' => 'Timesheet Approved', 'desc' => 'When management approves your recorded time logs', 'icon' => 'fa-circle-check', 'color' => '#16a34a'],
                'timesheet_disapproved' => ['name' => 'Timesheet Disapproved', 'desc' => 'When management requests adjustments or rejects logs', 'icon' => 'fa-circle-xmark', 'color' => '#dc2626'],
            ],
            'Time Off' => [
                'time_off_submitted' => ['name' => 'Time Off Request Submitted', 'desc' => 'Notice when your vacation or PTO request is filed', 'icon' => 'fa-plane-departure', 'color' => '#8b5cf6'],
                'time_off_decided'   => ['name' => 'Time Off Decision', 'desc' => 'When your time off request is approved or denied', 'icon' => 'fa-calendar-day', 'color' => '#4f46e5'],
            ],
            'Compliance & Alerts' => [
                'unusual_activity' => ['name' => 'Unusual Time / Overtime Alert', 'desc' => 'Alerts on abnormal hours, excessive overtime, or missed clocks', 'icon' => 'fa-triangle-exclamation', 'color' => '#d97706'],
                'geofence_breach'  => ['name' => 'Geofence / Location Alert', 'desc' => 'Alerts when time is clocked outside authorized work boundaries', 'icon' => 'fa-location-dot', 'color' => '#e11d48'],
            ],
            'System' => [
                'system_alert' => ['name' => 'System & Company Announcements', 'desc' => 'Important organizational notices and maintenance broadcasts', 'icon' => 'fa-shield-halved', 'color' => '#2563eb'],
            ],
        ];

        return view('livewire.notification-settings', [
            'categories'        => $categories,
            'roles'             => $roles,
            'filteredRules'     => $filteredRules,
            'eventsByCategory'  => $eventsByCategory,
            'isAdmin'           => $user->canAccessAdmin(),
            'isManager'         => $user->isManager(),
        ])->layout('layouts.app');
    }
}
