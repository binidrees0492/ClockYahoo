<?php

namespace Tests\Feature;

use App\Livewire\ExecutiveDashboard;
use App\Livewire\NotificationsLog;
use App\Livewire\Schedules;
use App\Livewire\TimesheetScheduleTabs;
use App\Livewire\Webmail;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutiveDashboardAndEnhancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_webmail_in_app_settings_and_attachment_and_archival(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'officer@example.com',
            'role'  => 'Employee',
        ]);
        $this->actingAs($user);

        // 1. Save SMTP/IMAP directly in Webmail settings modal
        Livewire::test(Webmail::class)
            ->call('openSettings')
            ->assertSet('showSettings', true)
            ->set('useCustomEmail', true)
            ->set('smtpHost', 'mail.webmailtest.com')
            ->set('smtpPort', 587)
            ->set('smtpUsername', 'officer@example.com')
            ->set('smtpPassword', 'secretpass')
            ->call('saveMailboxSettings')
            ->assertSet('showSettings', false)
            ->assertSet('toastType', 'success');

        $user->refresh();
        $this->assertTrue((bool) $user->use_custom_email);
        $this->assertEquals('mail.webmailtest.com', $user->smtp_host);

        // 2. Compose email with attachment and send
        $fakeFile = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');

        Livewire::test(Webmail::class)
            ->call('openCompose')
            ->set('composeTo', 'director@example.com')
            ->set('composeSubject', 'Quarterly Review')
            ->set('composeBody', 'Please review the attached quarterly summary.')
            ->set('attachments', [$fakeFile])
            ->call('sendEmail')
            ->assertHasNoErrors()
            ->assertSet('activeFolder', 'sent');

        $this->assertDatabaseHas('email_logs', [
            'user_id'   => $user->id,
            'recipient' => 'director@example.com',
            'subject'   => 'Quarterly Review',
        ]);

        $log = \App\Models\EmailLog::where('recipient', 'director@example.com')->where('user_id', $user->id)->first();
        $this->assertNotNull($log);

        // Archive all logs for this email to cleanly test folder view
        \App\Models\EmailLog::where('subject', 'Quarterly Review')->update(['is_archived' => true, 'folder' => 'archived']);

        // 3. Archive sent email
        Livewire::test(Webmail::class)
            ->call('archiveEmail', $log->id)
            ->assertSet('toastType', 'success');

        $log->refresh();
        $this->assertTrue((bool) $log->is_archived);
        $this->assertEquals('archived', $log->folder);

        // 4. View archived folder and restore
        Livewire::test(Webmail::class)
            ->call('setFolder', 'archived')
            ->assertSet('activeFolder', 'archived')
            ->assertSee('Quarterly Review')
            ->call('unarchiveEmail', $log->id)
            ->assertSet('toastType', 'success');

        $log->refresh();
        $this->assertFalse((bool) $log->is_archived);
    }

    public function test_notifications_are_archived_instead_of_deleted(): void
    {
        $user = User::factory()->create([
            'role' => 'Employee',
        ]);
        $this->actingAs($user);

        $notifId = (string) Str::uuid();
        $notif = DatabaseNotification::create([
            'id'              => $notifId,
            'type'            => 'App\Notifications\ShiftAssignedNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => [
                'type'    => 'shift_assigned',
                'title'   => 'Urgent Schedule Update',
                'message' => 'Your shift for tomorrow has been updated.',
            ],
            'read_at'         => null,
        ]);

        // 1. Archive the notification (record is never deleted from database)
        Livewire::test(NotificationsLog::class)
            ->assertSee('Urgent Schedule Update')
            ->call('archiveNotification', $notifId)
            ->assertSet('toastType', 'success');

        // Verify still in database but marked with archived_at
        $this->assertDatabaseHas('notifications', ['id' => $notifId]);
        $notif->refresh();
        $this->assertNotNull($notif->archived_at);

        // 2. View Archived tab and restore to active
        Livewire::test(NotificationsLog::class)
            ->call('setFilter', 'archived')
            ->assertSet('filter', 'archived')
            ->assertSee('Urgent Schedule Update')
            ->call('unarchiveNotification', $notifId)
            ->assertSet('toastType', 'success');

        $notif->refresh();
        $this->assertNull($notif->archived_at);
    }

    public function test_executive_dashboard_access_and_kpis(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee', 'base_pay' => 30.00]);

        $job = Assignment::create(['name' => 'HQ Renovation', 'status' => 'active']);
        $task = Task::create(['name' => 'Demolition', 'status' => 'active']);

        // Log 1: Approved (locked)
        TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->subHours(6),
            'clock_out'        => Carbon::now(),
            'duration_minutes' => 360,
            'hourly_rate'      => 30.00,
            'status'           => 'approved',
            'approved'         => true,
        ]);

        // Log 2: Rejected (locked)
        TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->subHours(3),
            'clock_out'        => Carbon::now(),
            'duration_minutes' => 180,
            'hourly_rate'      => 30.00,
            'status'           => 'rejected',
            'approved'         => false,
        ]);

        // Regular employee is forbidden from Executive Dashboard
        $this->actingAs($employee);
        $this->get(route('executive.dashboard'))->assertForbidden();

        // Admin can access and see executive workforce metrics & locked audit
        $this->actingAs($admin);
        $response = $this->get(route('executive.dashboard'));
        $response->assertOk();
        $response->assertSee('Executive Dashboard & Workforce Audit', false);

        Livewire::test(ExecutiveDashboard::class)
            ->assertSee('HQ Renovation')
            ->assertSee('Locked')
            ->assertSee('approved')
            ->assertSee('rejected');
    }

    public function test_timesheet_schedule_tabs_filters_by_department_job_and_task(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        $dept = Department::create(['name' => 'Engineering']);
        $emp = User::factory()->create(['role' => 'Employee', 'department_id' => $dept->id]);

        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Code Review', 'status' => 'active']);

        $log = TimeLog::create([
            'employee_id'      => $emp->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(1)->setTime(10, 0),
            'clock_out'        => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays(1)->setTime(14, 0),
            'duration_minutes' => 240,
            'status'           => 'completed',
        ]);

        Livewire::test(TimesheetScheduleTabs::class)
            ->assertSee('Project Alpha')
            ->set('selectedDepartmentId', $dept->id)
            ->assertSet('selectedDepartmentId', $dept->id)
            ->set('selectedJobId', $job->id)
            ->assertSet('selectedJobId', $job->id)
            ->set('selectedTaskId', $task->id)
            ->assertSet('selectedTaskId', $task->id)
            ->call('resetFilters')
            ->assertSet('selectedDepartmentId', 'all')
            ->assertSet('selectedJobId', 'all')
            ->assertSet('selectedTaskId', 'all');
    }

    public function test_schedule_shift_modal_links_read_only_coordinates_from_task(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        $task = Task::create([
            'name'            => 'Bridge Maintenance',
            'latitude'        => 37.8199,
            'longitude'       => -122.4783,
            'geofence_radius' => 350,
            'gps_policy'      => 'mandatory',
        ]);

        Livewire::test(Schedules::class)
            ->call('openAdd')
            ->set('taskId', $task->id)
            ->assertSet('latitude', 37.8199)
            ->assertSet('longitude', -122.4783)
            ->assertSet('geofenceRadius', 350)
            ->assertSee('Task Location GPS', false)
            ->assertSee('Read-Only', false);
    }
}
