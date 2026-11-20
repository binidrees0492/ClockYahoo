<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Location;
use App\Models\Customer;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\TimeOffPolicyEmployee;
use App\Services\InactiveRecordsHubService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class InactiveRecordsHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
            'email'  => 'superadmin@hubtest.com',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
            'email'  => 'admin@hubtest.com',
        ]);

        $this->employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
            'email'  => 'worker@hubtest.com',
        ]);
    }

    /**
     * Test route and component authorization.
     */
    public function test_access_control_to_inactive_records_hub()
    {
        // 1. Employee is forbidden
        $this->actingAs($this->employee)
            ->get(route('admin.inactive-records'))
            ->assertStatus(403);

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->assertStatus(403);

        // 2. Super Admin can access
        $this->actingAs($this->superAdmin)
            ->get(route('admin.inactive-records'))
            ->assertStatus(200)
            ->assertSee('Inactive &amp; Deleted Records Manager', false);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->assertSee('Inactive & Deleted Records Manager');
    }

    /**
     * Test scanning tables and calculating child record impacts.
     */
    public function test_inactive_records_scanning_and_child_impact_calculation()
    {
        // Create an inactive employee with child records
        $inactiveWorker = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Inactive',
            'name'   => 'Inactive Alex',
        ]);

        $job = Assignment::create(['name' => 'Solar Installation', 'status' => 'inactive']);
        $task = Task::create(['name' => 'Wiring Phase', 'assignment_id' => $job->id, 'status' => 'inactive']);

        $timeLog = TimeLog::create([
            'employee_id'      => $inactiveWorker->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 16:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $note = TimeLogNote::create([
            'time_log_id' => $timeLog->id,
            'user_id'     => $this->superAdmin->id,
            'body'        => 'Wiring verification note',
        ]);

        $schedule = Schedule::create([
            'employee_id'   => $inactiveWorker->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '08:00:00',
            'end_time'      => '16:00:00',
        ]);

        // 1. Service summary
        $summary = InactiveRecordsHubService::getTablesSummary();
        $this->assertGreaterThanOrEqual(1, $summary['employees']['count']);
        $this->assertGreaterThanOrEqual(1, $summary['jobs']['count']);
        $this->assertGreaterThanOrEqual(1, $summary['tasks']['count']);
        $this->assertGreaterThanOrEqual(1, $summary['time_logs']['count']);

        // 2. Service records with child impacts
        $records = InactiveRecordsHubService::getInactiveRecords('employees');
        $alexRecord = $records->firstWhere('record_id', $inactiveWorker->id);

        $this->assertNotNull($alexRecord);
        $this->assertEquals('Inactive Alex', $alexRecord['name']);
        $this->assertStringContainsString('Restores this employee', $alexRecord['restore_summary']);
        $this->assertStringContainsString('Permanently deletes this employee', $alexRecord['delete_summary']);
        $this->assertGreaterThanOrEqual(1, $alexRecord['total_children']);

        // Job child impact
        $jobRecords = InactiveRecordsHubService::getInactiveRecords('jobs');
        $solarJobRecord = $jobRecords->firstWhere('record_id', $job->id);

        $this->assertNotNull($solarJobRecord);
        $this->assertStringContainsString('Solar Installation', $solarJobRecord['name']);
        $this->assertStringContainsString('tasks', $solarJobRecord['delete_summary']);
        $this->assertGreaterThanOrEqual(1, $solarJobRecord['total_children']);
    }

    /**
     * Test restoring single, multiple, and all inactive records with child restoration.
     */
    public function test_restoring_inactive_records_and_children()
    {
        $job = Assignment::create(['name' => 'Wind Turbine Project', 'status' => 'inactive']);
        $task = Task::create(['name' => 'Turbine Assembly', 'assignment_id' => $job->id, 'status' => 'inactive']);
        $log = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 09:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 17:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $inactiveDept = Department::create(['name' => 'Research Dept', 'status' => 'Inactive']);

        // 1. Restore Single Job via Livewire
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->call('restoreSingle', "jobs:{$job->id}");

        $this->assertEquals('active', $job->fresh()->status);
        $this->assertEquals('active', $task->fresh()->status);
        $this->assertFalse((bool) $log->fresh()->is_archived);

        // 2. Restore Multiple Selected Records
        $inactiveDept2 = Department::create(['name' => 'Archive Ops', 'status' => 'Inactive']);
        $inactiveCust = Customer::create(['name' => 'Old Client Inc', 'active' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->set('selectedRecords', ["departments:{$inactiveDept2->id}", "customers:{$inactiveCust->id}"])
            ->call('restoreSelected');

        $this->assertEquals('Active', $inactiveDept2->fresh()->status);
        $this->assertTrue((bool) $inactiveCust->fresh()->active);

        // 3. Restore All Across Current Table
        $task1 = Task::create(['name' => 'Old Task A', 'status' => 'inactive']);
        $task2 = Task::create(['name' => 'Old Task B', 'status' => 'inactive']);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->set('selectedTable', 'tasks')
            ->call('restoreCurrentTable');

        $this->assertEquals('active', $task1->fresh()->status);
        $this->assertEquals('active', $task2->fresh()->status);
    }

    /**
     * Test permanent deletion with child record cascading and CONFIRM guard.
     */
    public function test_permanent_deletion_cascades_and_safety_confirm()
    {
        $inactiveWorker = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Inactive',
            'name'   => 'Doomed Worker',
        ]);

        $job = Assignment::create(['name' => 'Demolition Tower', 'status' => 'inactive']);
        $task = Task::create(['name' => 'Sledgehammering', 'assignment_id' => $job->id, 'status' => 'inactive']);

        $timeLog = TimeLog::create([
            'employee_id'      => $inactiveWorker->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 10:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 14:00:00'),
            'duration_minutes' => 240,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $note = TimeLogNote::create([
            'time_log_id' => $timeLog->id,
            'user_id'     => $this->superAdmin->id,
            'body'        => 'Child note',
        ]);

        $schedule = Schedule::create([
            'employee_id'   => $inactiveWorker->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '10:00:00',
            'end_time'      => '14:00:00',
        ]);

        // 1. Delete single record
        $component = Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\InactiveRecordsManager::class)
            ->call('promptDeleteSingle', "employees:{$inactiveWorker->id}")
            ->assertSet('showConfirmModal', true)
            ->call('executeDelete');

        $this->assertDatabaseMissing('users', ['id' => $inactiveWorker->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $timeLog->id]);
        $this->assertDatabaseMissing('time_log_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);

        // 2. Bulk delete with CONFIRM required
        $component->set('selectedRecords', ["jobs:{$job->id}"])
            ->call('promptDeleteSelected')
            ->assertSet('showConfirmModal', true)
            ->set('confirmText', 'WRONG')
            ->call('executeDelete')
            ->assertHasErrors(['confirmText'])
            ->assertSet('showConfirmModal', true);

        // Job and task should still exist
        $this->assertDatabaseHas('tbl_assignments', ['id' => $job->id]);
        $this->assertDatabaseHas('tbl_tasks', ['id' => $task->id]);

        // Provide correct CONFIRM
        $component->set('confirmText', 'CONFIRM')
            ->call('executeDelete')
            ->assertHasNoErrors()
            ->assertSet('showConfirmModal', false);

        // Job and child task permanently deleted
        $this->assertDatabaseMissing('tbl_assignments', ['id' => $job->id]);
        $this->assertDatabaseMissing('tbl_tasks', ['id' => $task->id]);
    }
}
