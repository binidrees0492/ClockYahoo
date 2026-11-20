<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
        ]);
    }

    public function test_admin_can_access_audit_log_route()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit-log'));
        $response->assertStatus(200);
        $response->assertSee('Audit Log');
    }

    public function test_employee_cannot_access_audit_log_route()
    {
        $response = $this->actingAs($this->employee)->get(route('admin.audit-log'));
        $response->assertStatus(403);
    }

    public function test_audit_log_record_helper_creates_entry()
    {
        $this->actingAs($this->admin);

        $log = AuditLog::record(
            'Updated policy configuration',
            ['Weekly OT' => ['old' => '40', 'new' => '80']],
            null,
            'Edit'
        );

        $this->assertDatabaseHas('audit_logs', [
            'id'          => $log->id,
            'user_id'     => $this->admin->id,
            'user_name'   => $this->admin->name,
            'action'      => 'Edit',
            'description' => 'Updated policy configuration',
        ]);

        $this->assertEquals(['old' => '40', 'new' => '80'], $log->changes['Weekly OT']);
    }

    public function test_time_log_detail_edit_records_audit_log()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create(['name' => 'Original Job']);
        $newJob = Assignment::create(['name' => 'New Project Job']);
        $task = Task::create(['name' => 'Original Task']);
        $newTask = Task::create(['name' => 'New Task']);

        $timeLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::now()->subHours(4),
            'clock_out'     => Carbon::now()->subHours(2),
            'status'        => 'completed',
            'approved'      => false,
        ]);

        Livewire::test(\App\Livewire\TimeLogDetail::class)
            ->call('open', $timeLog->id)
            ->call('openEdit')
            ->set('editAssignment', $newJob->id)
            ->set('editTask', $newTask->id)
            ->set('editNotes', 'Adjusted tasks per manager request')
            ->call('saveEdit');

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $this->admin->id,
            'action'         => 'Edit',
            'auditable_type' => TimeLog::class,
            'auditable_id'   => $timeLog->id,
        ]);

        $audit = AuditLog::where('auditable_id', $timeLog->id)->latest()->first();
        $this->assertNotNull($audit);
        $this->assertArrayHasKey('Job', $audit->changes);
        $this->assertEquals('Original Job', $audit->changes['Job']['old']);
        $this->assertEquals('New Project Job', $audit->changes['Job']['new']);
    }

    public function test_time_log_detail_approve_records_audit_log()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create(['name' => 'Approve Job']);
        $task = Task::create(['name' => 'Approve Task']);

        $timeLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::now()->subHours(5),
            'clock_out'     => Carbon::now()->subHours(1),
            'approved'      => false,
            'status'        => 'completed',
        ]);

        Livewire::test(\App\Livewire\TimeLogDetail::class)
            ->call('open', $timeLog->id)
            ->call('approve');

        $this->assertDatabaseHas('audit_logs', [
            'user_id'   => $this->admin->id,
            'action'    => 'Approval',
            'auditable_id' => $timeLog->id,
        ]);
    }

    public function test_time_log_detail_disapprove_records_audit_log()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create(['name' => 'Disapprove Job']);
        $task = Task::create(['name' => 'Disapprove Task']);

        $timeLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::now()->subHours(3),
            'clock_out'     => Carbon::now()->subHour(),
            'approved'      => false,
            'status'      => 'completed',
        ]);

        Livewire::test(\App\Livewire\TimeLogDetail::class)
            ->call('open', $timeLog->id)
            ->call('openDisapprove')
            ->set('disapprovalReason', 'Missed lunch break log')
            ->call('confirmDisapprove');

        $this->assertDatabaseHas('audit_logs', [
            'user_id'   => $this->admin->id,
            'action'    => 'Reject',
            'auditable_id' => $timeLog->id,
        ]);

        $audit = AuditLog::where('auditable_id', $timeLog->id)->latest()->first();
        $this->assertNotNull($audit);
        $this->assertEquals('Missed lunch break log', $audit->changes['Rejection Reason']['new']);
    }

    public function test_audit_log_livewire_component_filtering_and_modal()
    {
        $this->actingAs($this->admin);

        AuditLog::create([
            'user_id'     => $this->admin->id,
            'user_name'   => 'Admin User',
            'action'      => 'Edit',
            'description' => 'Updated payroll rate for Jane',
            'changes'     => ['Rate' => ['old' => '20', 'new' => '25']],
            'created_at'  => Carbon::now(),
        ]);

        AuditLog::create([
            'user_id'     => $this->admin->id,
            'user_name'   => 'Supervisor Dave',
            'action'      => 'Create',
            'description' => 'Created job Downtown Site',
            'changes'     => ['Name' => ['old' => null, 'new' => 'Downtown Site']],
            'created_at'  => Carbon::now()->subDays(2),
        ]);

        $component = Livewire::test(\App\Livewire\AuditLog::class);

        $component->assertSee('Updated payroll rate for Jane')
            ->assertSee('Created job Downtown Site');

        // Search test
        $component->set('search', 'Jane')
            ->assertSee('Updated payroll rate for Jane')
            ->assertDontSee('Downtown Site');

        // Reset search and test preset
        $component->set('search', '')
            ->call('applyPreset', 'today');

        $component->assertSee('Updated payroll rate for Jane');

        // View details modal
        $targetLog = AuditLog::where('description', 'like', '%Jane%')->first();
        $component->call('viewDetails', $targetLog->id)
            ->assertSet('showDetailsModal', true)
            ->assertSet('selectedLogId', $targetLog->id)
            ->assertSee('Property')
            ->assertSee('Old Value')
            ->assertSee('New Value')
            ->assertSee('Rate')
            ->assertSee('20')
            ->assertSee('25')
            ->call('closeDetails')
            ->assertSet('showDetailsModal', false);
    }

    public function test_approve_timesheets_component_records_audit_log()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create(['name' => 'Field Job']);
        $task = Task::create(['name' => 'Field Task']);

        $timeLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::now()->subHours(6),
            'clock_out'     => Carbon::now()->subHours(2),
            'approved'      => false,
            'status'        => 'completed',
        ]);

        Livewire::test(\App\Livewire\ApproveTimesheets::class)
            ->call('approveOne', $timeLog->id);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'   => $this->admin->id,
            'action'    => 'Approval',
            'auditable_id' => $timeLog->id,
        ]);

        $this->assertTrue($timeLog->fresh()->approved);
    }
}
