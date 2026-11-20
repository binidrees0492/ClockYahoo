<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Livewire\NotificationsLog;
use App\Livewire\NotificationSettings;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\EmailLog;
use App\Models\NotificationRule;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\GenericNotification;
use App\Notifications\ShiftAssignedNotification;
use App\Services\NotificationRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationManagementAndRealtimeToastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_admin_can_access_notification_settings_page_and_view_matrix()
    {
        $admin = User::factory()->create([
            'role' => 'Administrator',
        ]);

        $this->actingAs($admin)
            ->get(route('notification.settings'))
            ->assertOk()
            ->assertSee('Notification Management')
            ->assertSee('Role Routing Matrix')
            ->assertSee('Manager Activity Alerts')
            ->assertSee('Notification Email Sender');

        Livewire::actingAs($admin)
            ->test(NotificationSettings::class)
            ->assertSet('activeTab', 'rules')
            ->assertSee('Shift Assigned / Scheduled')
            ->assertSee('Timesheet / Time Log Submitted')
            ->assertSee('Employee Clock In Activity');
    }

    public function test_admin_can_update_notification_routing_matrix_rules()
    {
        $admin = User::factory()->create([
            'role' => 'Administrator',
        ]);

        $rule = NotificationRule::where('event_key', 'shift_assigned')->first();
        $this->assertNotNull($rule);

        Livewire::actingAs($admin)
            ->test(NotificationSettings::class)
            ->call('toggleRuleActive', $rule->id)
            ->call('saveRules')
            ->assertSet('toastType', 'success');

        $rule->refresh();
        $this->assertFalse((bool) $rule->is_active);
    }

    public function test_admin_can_configure_notification_sender_email_and_send_test()
    {
        $admin = User::factory()->create([
            'role'  => 'Administrator',
            'email' => 'admin@company.com',
        ]);

        Livewire::actingAs($admin)
            ->test(NotificationSettings::class)
            ->set('activeTab', 'email')
            ->set('notificationFromEmail', 'alerts@company.com')
            ->set('notificationFromName', 'Company Automated Bot')
            ->set('notificationReplyTo', 'support@company.com')
            ->set('notificationEmailEnabled', true)
            ->call('saveEmailSettings')
            ->assertSet('toastType', 'success');

        $this->assertEquals('alerts@company.com', CompanySetting::get('notification_from_email'));
        $this->assertEquals('Company Automated Bot', CompanySetting::get('notification_from_name'));
        $this->assertEquals('support@company.com', CompanySetting::get('notification_reply_to'));

        // Test sending email
        Livewire::actingAs($admin)
            ->test(NotificationSettings::class)
            ->set('testEmailRecipient', 'admin@company.com')
            ->call('sendTestEmail')
            ->assertSet('toastType', 'success');

        $this->assertDatabaseHas('email_logs', [
            'recipient' => 'admin@company.com',
            'sender'    => 'alerts@company.com',
            'status'    => 'sent',
        ]);
    }

    public function test_manager_can_access_and_save_employee_activity_alert_preferences()
    {
        $dept = Department::create(['name' => 'Field Operations']);

        $manager = User::factory()->create([
            'role'          => 'Manager',
            'department_id' => $dept->id,
        ]);

        $this->actingAs($manager)
            ->get(route('notification.settings'))
            ->assertOk()
            ->assertSee('Manager Activity Alerts');

        Livewire::actingAs($manager)
            ->test(NotificationSettings::class)
            ->set('activeTab', 'manager')
            ->set('managerScope', 'direct_reports')
            ->set('managerActivities.notify_clock_in', ['in_app' => true, 'toast' => true, 'email' => true])
            ->set('managerActivities.notify_clock_out', ['in_app' => false, 'toast' => false, 'email' => false])
            ->call('saveManagerSettings')
            ->assertSet('toastType', 'success');

        $manager->refresh();
        $settings = $manager->getManagerNotificationSettings();
        $this->assertEquals('direct_reports', $settings['scope']);
        $this->assertTrue($settings['notify_clock_in']['email']);
        $this->assertFalse($settings['notify_clock_out']['in_app']);
    }

    public function test_notification_routing_serves_person_involved_and_scoped_manager()
    {
        $dept = Department::create(['name' => 'Logistics']);

        $manager = User::factory()->create([
            'role'          => 'Manager',
            'department_id' => $dept->id,
        ]);

        $employee = User::factory()->create([
            'role'          => 'Employee',
            'department_id' => $dept->id,
            'manager_id'    => $manager->id,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => 'Employee',
        ]);

        // Dispatch shift_assigned notification through NotificationRoutingService
        $result = NotificationRoutingService::send(
            eventKey: 'shift_assigned',
            data: [
                'title'   => 'Shift Scheduled for Dec 01',
                'message' => 'Your logistics morning shift has been scheduled.',
                'link'    => '/schedules',
            ],
            personInvolved: $employee
        );

        $this->assertEquals('success', $result['status']);
        $this->assertContains($employee->id, $result['recipients']);
        $this->assertContains($manager->id, $result['recipients']);
        $this->assertNotContains($otherEmployee->id, $result['recipients']);

        // Verify database notification records created for employee & manager with toast flag
        $empNotif = $employee->notifications()->first();
        $this->assertNotNull($empNotif);
        $this->assertEquals('Shift Scheduled for Dec 01', $empNotif->data['title']);
        $this->assertTrue($empNotif->data['toast']);

        $mgrNotif = $manager->notifications()->first();
        $this->assertNotNull($mgrNotif);
        $this->assertEquals('manager', $mgrNotif->data['recipient_role']);
    }

    public function test_realtime_toast_appears_in_notification_bell_and_auto_dismisses()
    {
        $user = User::factory()->create([
            'role' => 'Employee',
        ]);

        // Create an unread notification with toast = true
        $user->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'shift_assigned',
                'title'      => 'Urgent Schedule Notice',
                'message'    => 'You have been assigned an urgent delivery shift.',
                'link'       => '/schedules',
                'toast'      => true,
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
            'created_at'  => now(),
        ]);

        $component = Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSee('Urgent Schedule Notice');

        $activeToasts = $component->get('activeToasts');
        $this->assertNotEmpty($activeToasts);
        $this->assertEquals('Urgent Schedule Notice', $activeToasts[0]['title']);

        // Dismiss toast
        $component->call('dismissToast', $activeToasts[0]['id']);
        $this->assertEmpty($component->get('activeToasts'));
    }

    public function test_notifications_history_only_shows_notifications_configured_for_user()
    {
        $user = User::factory()->create([
            'role' => 'Employee',
        ]);

        $allowedNotif = $user->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'shift_assigned',
                'category'   => 'Scheduling',
                'title'      => 'Allowed Shift Notification',
                'message'    => 'Shift assigned properly.',
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
        ]);

        $otherUser = User::factory()->create(['role' => 'Employee']);
        $otherNotif = $otherUser->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'shift_assigned',
                'category'   => 'Scheduling',
                'title'      => 'Other User Secret Shift',
                'message'    => 'Private message.',
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationsLog::class)
            ->assertSee('Allowed Shift Notification')
            ->assertDontSee('Other User Secret Shift');
    }

    public function test_notifications_history_excludes_events_disabled_for_user_role()
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        // Disable clock_in for Employee role
        $rule = NotificationRule::where('event_key', 'clock_in')->first();
        if ($rule) {
            $cfg = $rule->roles_config;
            $cfg['Employee']['enabled'] = false;
            $rule->update(['roles_config' => $cfg]);
        }

        $employee->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'clock_in',
                'category'   => 'Time Tracking',
                'title'      => 'Disabled Clock In Alert',
                'message'    => 'Should not be served in history.',
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
        ]);

        Livewire::actingAs($employee)
            ->test(NotificationsLog::class)
            ->assertDontSee('Disabled Clock In Alert');
    }

    public function test_any_user_can_access_and_save_their_personal_notification_preferences()
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        $this->actingAs($employee)
            ->get(route('notification.settings'))
            ->assertOk()
            ->assertSee('My Notification Preferences')
            ->assertSee('In-App Bell')
            ->assertSee('Real-Time Toast Pop-Up')
            ->assertSee('Email Notifications');

        Livewire::actingAs($employee)
            ->test(NotificationSettings::class)
            ->assertSet('activeTab', 'personal')
            ->call('togglePersonalEventChannel', 'shift_assigned', 'email')
            ->call('togglePersonalGlobalChannel', 'toast')
            ->call('savePersonalPreferences')
            ->assertSet('toastType', 'success');

        $employee->refresh();
        $this->assertFalse($employee->prefersNotificationChannel('shift_assigned', 'email'));
        $this->assertFalse($employee->prefersNotificationChannel('global', 'toast'));
    }

    public function test_individual_can_mute_notifications_for_specific_event()
    {
        $employee = User::factory()->create([
            'role'  => 'Employee',
            'email' => 'employee.mute@company.com',
        ]);

        // Employee explicitly turns off email and toast for shift_assigned
        $prefs = $employee->getPersonalNotificationPreferences();
        $prefs['events']['shift_assigned']['email'] = false;
        $prefs['events']['shift_assigned']['toast'] = false;
        $employee->update(['detailed_notification_settings' => $prefs]);

        $result = NotificationRoutingService::send(
            eventKey: 'shift_assigned',
            data: [
                'title'   => 'Assigned Weekend Shift',
                'message' => 'Check your schedule.',
                'link'    => '/schedules',
            ],
            personInvolved: $employee
        );

        $this->assertContains($employee->id, $result['recipients']);

        // Check stored notification: toast must be false because employee turned off toast
        $notif = $employee->notifications()->first();
        $this->assertNotNull($notif);
        $this->assertFalse($notif->data['toast']);

        // Check email: no email should be logged for this recipient
        $this->assertDatabaseMissing('email_logs', [
            'recipient' => 'employee.mute@company.com',
        ]);
    }

    public function test_individual_mute_all_prevents_any_notification_dispatch()
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        Livewire::actingAs($employee)
            ->test(NotificationSettings::class)
            ->call('muteAllPersonalNotifications')
            ->call('savePersonalPreferences')
            ->assertSet('toastType', 'success');

        $employee->refresh();

        $result = NotificationRoutingService::send(
            eventKey: 'shift_assigned',
            data: [
                'title'   => 'Ignored Shift',
                'message' => 'Should not be delivered.',
            ],
            personInvolved: $employee
        );

        $this->assertNotContains($employee->id, $result['recipients']);
        $this->assertEquals(0, $employee->notifications()->count());
    }

    public function test_personal_preference_disabling_toast_prevents_toast_popup_in_bell()
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        // Employee turns off toast globally
        $prefs = $employee->getPersonalNotificationPreferences();
        $prefs['channels']['toast'] = false;
        $employee->update(['detailed_notification_settings' => $prefs]);

        // Notification has toast = true in database, but user has globally disabled toast
        $employee->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'shift_assigned',
                'title'      => 'No Toast Alert',
                'message'    => 'User does not want toast popups.',
                'toast'      => true,
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
            'created_at'  => now(),
        ]);

        $component = Livewire::actingAs($employee)->test(NotificationBell::class);
        $this->assertEmpty($component->get('activeToasts'));
    }

    public function test_notifications_history_excludes_events_personally_muted_by_user()
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        // Employee personally mutes in_app for timesheet_approved
        $prefs = $employee->getPersonalNotificationPreferences();
        $prefs['events']['timesheet_approved']['in_app'] = false;
        $employee->update(['detailed_notification_settings' => $prefs]);

        $employee->notifications()->create([
            'id'          => (string) Str::uuid(),
            'type'        => GenericNotification::class,
            'data'        => [
                'event_key'  => 'timesheet_approved',
                'category'   => 'Timesheet Approvals',
                'title'      => 'Personally Muted Approval',
                'message'    => 'Should not be shown in my history.',
                'created_at' => now()->toISOString(),
            ],
            'read_at'     => null,
            'archived_at' => null,
        ]);

        Livewire::actingAs($employee)
            ->test(NotificationsLog::class)
            ->assertDontSee('Personally Muted Approval');
    }
}
