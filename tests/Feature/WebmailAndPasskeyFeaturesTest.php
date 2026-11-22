<?php

namespace Tests\Feature;

use App\Livewire\MailLogs;
use App\Livewire\NotificationsLog;
use App\Livewire\Profile\PersonalConfigs;
use App\Livewire\Webmail;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Livewire\Livewire;
use Tests\TestCase;

class WebmailAndPasskeyFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_webauthn_and_endpoints_are_available(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $this->assertInstanceOf(WebAuthnAuthenticatable::class, $user);

        // 1. WebAuthn Login Options endpoint (public)
        $response = $this->postJson(route('webauthn.login.options'), [
            'email' => 'user@example.com',
        ]);
        $response->assertOk();

        // 2. WebAuthn Register Options endpoint (authenticated)
        $this->actingAs($user);
        $regResponse = $this->postJson(route('webauthn.register.options'));
        $regResponse->assertOk();

        // 3. User relationship
        $this->assertNotNull($user->webAuthnCredentials);
    }

    public function test_login_page_renders_passkey_quick_login_button(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('Quick Sign in with Passkey');
        $response->assertSee('loginWithPasskey()');
    }

    public function test_profile_page_renders_horizontal_tabs_and_unsaved_changes_modal(): void
    {
        $user = User::factory()->create([
            'name'  => 'Arthur Dent',
            'email' => 'arthur@galaxy.com',
            'role'  => 'Employee',
        ]);

        $this->actingAs($user);

        $response = $this->get('/profile');
        $response->assertOk();

        // Horizontal tabs
        $response->assertSee('Profile Information');
        $response->assertSee('Appearance & Theme', false);
        $response->assertSee('Security & Passkeys', false);

        // Unsaved changes modal
        $response->assertSee('Unsaved Changes');
        $response->assertSee('Discard & Leave Tab', false);
        $response->assertSee('Keep Editing');

        // Passkey registration section in Security
        $response->assertSee('Register This Device / Add Passkey');
    }

    public function test_personal_configs_supports_tab_specific_saves(): void
    {
        $user = User::factory()->create([
            'role' => 'Employee',
        ]);

        $this->actingAs($user);

        // 1. Save Theme & Regional preferences
        Livewire::test(PersonalConfigs::class, ['activeTab' => 'theme'])
            ->set('theme_preference', 'sage dark green')
            ->set('timezone', 'Asia/Tokyo')
            ->call('saveTheme')
            ->assertHasNoErrors()
            ->assertDispatched('form-saved');

        $user->refresh();
        $this->assertEquals('sage dark green', $user->theme_preference);
        $this->assertEquals('Asia/Tokyo', $user->timezone);

        // 2. Save Email & IMAP settings
        Livewire::test(PersonalConfigs::class, ['activeTab' => 'email'])
            ->set('use_custom_email', true)
            ->set('smtp_host', 'mail.private.org')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'me@private.org')
            ->set('smtp_password', 'secret')
            ->set('imap_host', 'imap.private.org')
            ->set('imap_port', 993)
            ->set('imap_encryption', 'ssl')
            ->call('saveEmail')
            ->assertHasNoErrors()
            ->assertDispatched('form-saved');

        $user->refresh();
        $this->assertTrue((bool) $user->use_custom_email);
        $this->assertEquals('mail.private.org', $user->smtp_host);
        $this->assertEquals('imap.private.org', $user->imap_host);
    }

    public function test_webmail_module_loads_and_handles_compose_and_folders(): void
    {
        $user = User::factory()->create([
            'name'  => 'Sarah Connor',
            'email' => 'sarah@skynet.com',
            'role'  => 'Employee',
        ]);

        $this->actingAs($user);

        // Access webmail route
        $response = $this->get(route('webmail'));
        $response->assertOk();
        $response->assertSee('Webmail Client');

        // Test Livewire component compose & folder switching
        Livewire::test(Webmail::class)
            ->assertSee('Inbox')
            ->assertSee('Sent')
            ->assertSee('Drafts')
            ->call('openCompose')
            ->assertSet('showCompose', true)
            ->set('composeTo', 'john@resistance.org')
            ->set('composeSubject', 'Mission Briefing')
            ->set('composeBody', 'Prepare coordinates for rendezvous.')
            ->call('sendEmail')
            ->assertHasNoErrors()
            ->assertSet('activeFolder', 'sent');

        $this->assertDatabaseHas('email_logs', [
            'user_id'   => $user->id,
            'recipient' => 'john@resistance.org',
            'subject'   => 'Mission Briefing',
        ]);
    }

    public function test_mail_logs_module_filters_and_resends_failed_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'Administrator',
        ]);

        $failed = EmailLog::create([
            'user_id'       => $admin->id,
            'recipient'     => 'client@target.com',
            'subject'       => 'Weekly Invoice #102',
            'body'          => 'Your invoice is attached.',
            'status'        => 'failed',
            'error_message' => '550 Recipient rejected',
            'retry_count'   => 0,
        ]);

        $this->actingAs($admin);

        // Access route
        $this->get(route('mail.logs'))->assertOk()->assertSee('Mail & Delivery Logs', false);

        // Test Livewire component
        Livewire::test(MailLogs::class)
            ->assertSee('Weekly Invoice #102')
            ->call('filterStatus', 'failed')
            ->assertSet('statusFilter', 'failed')
            ->call('resend', $failed->id)
            ->assertHasNoErrors();

        $failed->refresh();
        $this->assertEquals(1, $failed->retry_count);
    }

    public function test_notifications_log_module_loads_and_marks_read(): void
    {
        $user = User::factory()->create([
            'role' => 'Employee',
        ]);

        $notifId = (string) Str::uuid();
        DatabaseNotification::create([
            'id'              => $notifId,
            'type'            => 'App\Notifications\ShiftAssignedNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => [
                'type'    => 'shift_assigned',
                'title'   => 'Shift Assigned: Morning Shift',
                'message' => 'You were assigned to shift on Monday 08:00.',
            ],
            'read_at'         => null,
        ]);

        $this->actingAs($user);

        // Access route
        $this->get(route('notifications.log'))->assertOk()->assertSee('Notifications Log');

        // Test Livewire component
        Livewire::test(NotificationsLog::class)
            ->assertSee('Shift Assigned: Morning Shift')
            ->call('markAsRead', $notifId)
            ->assertHasNoErrors();

        $this->assertNotNull(DatabaseNotification::find($notifId)->read_at);
    }

    public function test_navbar_displays_webmail_mail_log_and_notifications_log_links(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        $this->actingAs($employee);
        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertDontSee(route('webmail'));
        $response->assertDontSee(route('mail.logs'));

        $superAdmin = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $this->actingAs($superAdmin);
        $superResponse = $this->get('/dashboard');
        $superResponse->assertOk();
        $superResponse->assertSee(route('webmail'));
        $superResponse->assertSee('Webmail');
        $superResponse->assertSee(route('mail.logs'));
    }
}
