<?php

namespace Tests\Feature;

use App\Livewire\CompanySettings;
use App\Livewire\Profile\PersonalConfigs;
use App\Livewire\ScheduleBoard;
use App\Models\CompanySetting;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsAndEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_admin_can_access_company_settings_and_update_theme_and_branding(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.company-settings'));
        $response->assertStatus(200);

        Livewire::test(CompanySettings::class)
            ->set('companyTheme', 'sage dark green')
            ->set('setAsFavicon', true)
            ->call('saveBranding')
            ->assertHasNoErrors();

        $this->assertEquals('sage dark green', CompanySetting::get('company_theme'));
    }

    public function test_admin_can_save_server_email_and_employee_email(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $employee = User::factory()->create([
            'name'   => 'Test Employee',
            'email'  => 'employee@example.com',
            'role'   => 'Employee',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        Livewire::test(CompanySettings::class)
            ->set('mailHost', 'smtp.testmail.com')
            ->set('mailPort', 587)
            ->set('mailEncryption', 'tls')
            ->set('mailUsername', 'admin@testmail.com')
            ->set('mailPassword', 'secret123')
            ->set('imapHost', 'imap.testmail.com')
            ->set('imapPort', 993)
            ->set('imapEncryption', 'ssl')
            ->call('saveServerEmail')
            ->assertHasNoErrors();

        $this->assertEquals('smtp.testmail.com', CompanySetting::get('mail_host'));
        $this->assertEquals('imap.testmail.com', CompanySetting::get('imap_host'));

        // Test Employee Email Config by Admin
        Livewire::test(CompanySettings::class)
            ->call('selectEmployee', $employee->id)
            ->set('empUseCustomEmail', true)
            ->set('empSmtpHost', 'custom.smtp.com')
            ->set('empSmtpPort', 465)
            ->set('empSmtpEncryption', 'ssl')
            ->set('empSmtpUsername', 'emp@custom.com')
            ->set('empSmtpPassword', 'custompass')
            ->call('saveEmployeeEmail')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertTrue((bool) $employee->use_custom_email);
        $this->assertEquals('custom.smtp.com', $employee->smtp_host);
        $this->assertEquals('custompass', $employee->smtp_password);
    }

    public function test_admin_can_view_email_logs_and_resend_failed_email(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $failedLog = EmailLog::create([
            'user_id'       => $admin->id,
            'recipient'     => 'recipient@example.com',
            'sender'        => 'sender@example.com',
            'subject'       => 'Failed Shift Assignment Alert',
            'body'          => '<p>Test email body</p>',
            'status'        => 'failed',
            'error_message' => 'Connection timeout',
            'retry_count'   => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(CompanySettings::class)
            ->set('activeTab', 'email_logs')
            ->call('resendEmail', $failedLog->id);

        $failedLog->refresh();
        $this->assertEquals('sent', $failedLog->status);
        $this->assertEquals(1, $failedLog->retry_count);
    }

    public function test_user_can_configure_personal_theme_and_email_and_view_activity_logs(): void
    {
        $user = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
            'email'  => 'jane@example.com',
        ]);

        $this->actingAs($user);

        // Add a dummy email log for Jane
        EmailLog::create([
            'user_id'   => $user->id,
            'recipient' => $user->email,
            'subject'   => 'Your Weekly Timesheet Approval',
            'body'      => 'Timesheet has been approved.',
            'status'    => 'sent',
        ]);

        Livewire::test(PersonalConfigs::class)
            ->set('theme_preference', 'gradient')
            ->set('timezone', 'America/New_York')
            ->set('use_custom_email', true)
            ->set('smtp_host', 'smtp.gmail.com')
            ->set('smtp_port', 587)
            ->set('smtp_encryption', 'tls')
            ->set('smtp_username', 'jane@example.com')
            ->set('smtp_password', 'secret_token')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('gradient', $user->theme_preference);
        $this->assertEquals('America/New_York', $user->timezone);
        $this->assertTrue((bool) $user->use_custom_email);
        $this->assertEquals('smtp.gmail.com', $user->smtp_host);
    }

    public function test_schedule_board_saves_shift_location_and_geofence(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
        ]);

        $job = \App\Models\Assignment::create(['name' => 'Central Warehouse Project', 'status' => 'active']);
        $task = \App\Models\Task::create(['name' => 'Logistics Sorting', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        $shiftDate = now()->toDateString();

        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift', $employee->id, $shiftDate, $job->id, $task->id)
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [$task->id])
            ->set('shiftStart', '09:00')
            ->set('shiftEnd', '17:00')
            ->set('shiftStandardHours', 8.00)
            ->set('shiftLocationName', 'Central Regional Warehouse')
            ->set('shiftLatitude', 24.860734)
            ->set('shiftLongitude', 67.001136)
            ->set('shiftGeofenceRadius', 350)
            ->call('saveShift')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('schedules', [
            'employee_id'     => $employee->id,
            'assignment_id'   => $job->id,
            'task_id'         => $task->id,
            'location_name'   => 'Central Regional Warehouse',
            'latitude'        => 24.860734,
            'longitude'       => 67.001136,
            'geofence_radius' => 350,
        ]);
    }

    public function test_first_week_day_is_selectable_in_company_settings_and_defaults_to_thursday_across_pages(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);
        $this->actingAs($admin);

        // 1. By default, CompanySetting::getWeekStartDay() is Carbon::THURSDAY (4)
        $this->assertEquals(\Carbon\Carbon::THURSDAY, CompanySetting::getWeekStartDay());

        // 2. ScheduleBoard starts on Thursday
        $startThursday = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::THURSDAY);
        Livewire::test(ScheduleBoard::class)
            ->assertViewHas('startOfWeek', function ($date) use ($startThursday) {
                return $date->toDateString() === $startThursday->toDateString();
            })
            ->assertViewHas('days', function ($days) {
                return $days->first()->isThursday();
            });

        // 3. Schedules starts on Thursday
        Livewire::test(\App\Livewire\Schedules::class)
            ->assertViewHas('startOfWeek', function ($date) use ($startThursday) {
                return $date->toDateString() === $startThursday->toDateString();
            })
            ->assertViewHas('days', function ($days) {
                return $days->first()->isThursday();
            });

        // 4. Admin selects another week start day (e.g. Monday = 1) in Company Settings
        Livewire::test(CompanySettings::class)
            ->set('weekStartDay', 1)
            ->set('locationPolicy', 'flag')
            ->set('defaultGeofenceRadius', 200)
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $this->assertEquals(1, CompanySetting::getWeekStartDay());

        // 5. ScheduleBoard now shifts to start on Monday
        $startMonday = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
        Livewire::test(ScheduleBoard::class)
            ->assertViewHas('startOfWeek', function ($date) use ($startMonday) {
                return $date->toDateString() === $startMonday->toDateString();
            })
            ->assertViewHas('days', function ($days) {
                return $days->first()->isMonday();
            });
    }

    public function test_super_admin_can_toggle_and_manage_active_pages_for_navbar_and_links(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);
        $this->actingAs($admin);

        // 1. Initial state: all pages are active
        $this->assertTrue(CompanySetting::isPageActive('map'));
        $this->assertTrue(CompanySetting::isPageActive('reports'));
        $this->assertTrue(CompanySetting::isPageActive('schedules'));

        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertSee(route('map'));
        $response->assertSee(route('reports'));
        $response->assertSee(route('schedules'));

        // 2. Admin navigates to Navigation & Active Pages tab and disables Map and Reports
        Livewire::test(CompanySettings::class)
            ->set('activeTab', 'navigation')
            ->call('toggleNavPage', 'map')
            ->call('toggleNavPage', 'reports')
            ->call('saveNavigationPages')
            ->assertHasNoErrors()
            ->assertSet('toastMessage', 'Navigation and active page configuration saved successfully.');

        // 3. Verify in CompanySetting model
        $this->assertFalse(CompanySetting::isPageActive('map'));
        $this->assertFalse(CompanySetting::isPageActive('reports'));
        $this->assertTrue(CompanySetting::isPageActive('schedules'));

        // 4. Verify navbar does not contain links to inactive pages
        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertDontSee(route('map'));
        $response->assertDontSee(route('reports'));
        $response->assertSee(route('schedules'));

        // 5. Admin clicks "Enable All" and saves
        Livewire::test(CompanySettings::class)
            ->set('activeTab', 'navigation')
            ->call('enableAllNavPages')
            ->call('saveNavigationPages')
            ->assertHasNoErrors();

        $this->assertTrue(CompanySetting::isPageActive('map'));
        $this->assertTrue(CompanySetting::isPageActive('reports'));
    }
}
