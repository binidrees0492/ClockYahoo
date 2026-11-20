<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Location;
use App\Models\Customer;
use App\Models\TimeOffPolicy;
use App\Services\DataSheddingService;
use App\Services\InactiveRecordsHubService;
use App\Livewire\InactiveRecordsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Carbon\Carbon;

class SuperAdminDatabaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
            'email'  => 'superadmin@hub.test',
        ]);

        $this->regularUser = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
            'email'  => 'employee@hub.test',
        ]);
    }

    /**
     * Test the bug fix: Data shedding of departments and locations does not throw QueryException on schedules.
     */
    public function test_data_shedding_departments_and_locations_does_not_crash_on_schedules()
    {
        $dept = Department::create(['name' => 'Architecture', 'status' => 'Active']);
        $loc = Location::create(['name' => 'HQ Facility', 'status' => 'Active', 'address' => '123 Tech Way']);

        $job = Assignment::create(['name' => 'HQ Build', 'location' => 'HQ Facility', 'status' => 'active']);
        $task = Task::create(['name' => 'Blueprint Drafting', 'assignment_id' => $job->id, 'status' => 'active']);

        $schedule = Schedule::create([
            'employee_id'   => $this->regularUser->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'location_name' => 'HQ Facility',
            'status'        => 'scheduled',
        ]);

        // Purge departments and locations - must NOT throw "no such column: department_id" on schedules
        $results = DataSheddingService::shedData(['departments', 'locations'], $this->superAdmin->id);

        $this->assertArrayHasKey('departments', $results);
        $this->assertArrayHasKey('locations', $results);
        $this->assertEquals(0, Department::count());
        $this->assertEquals(0, Location::count());

        // Full shedding of all entities at once
        $allKeys = array_keys(DataSheddingService::getAvailableEntities());
        $fullResults = DataSheddingService::shedData($allKeys, $this->superAdmin->id);

        // Super Admin account must still exist!
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => 'Super Admin']);
        // Regular user was safely purged
        $this->assertDatabaseMissing('users', ['id' => $this->regularUser->id]);
    }

    /**
     * Test tabulated database tables overview in InactiveRecordsHubService and Livewire component.
     */
    public function test_tabulated_database_tables_overview()
    {
        $summary = InactiveRecordsHubService::getTablesSummary();

        $this->assertArrayHasKey('employees', $summary);
        $this->assertArrayHasKey('jobs', $summary);
        $this->assertArrayHasKey('tasks', $summary);
        $this->assertArrayHasKey('time_logs', $summary);
        $this->assertArrayHasKey('schedules', $summary);
        $this->assertArrayHasKey('departments', $summary);
        $this->assertArrayHasKey('locations', $summary);
        $this->assertArrayHasKey('customers', $summary);
        $this->assertArrayHasKey('time_off_policies', $summary);

        foreach ($summary as $key => $tableInfo) {
            $this->assertArrayHasKey('label', $tableInfo);
            $this->assertArrayHasKey('table_name', $tableInfo);
            $this->assertArrayHasKey('total_count', $tableInfo);
            $this->assertArrayHasKey('active_count', $tableInfo);
            $this->assertArrayHasKey('inactive_count', $tableInfo);
            $this->assertArrayHasKey('children_desc', $tableInfo);
        }

        // Livewire component starts in tables_hub mode and displays tabulated table
        Livewire::actingAs($this->superAdmin)
            ->test(InactiveRecordsManager::class)
            ->assertSet('viewMode', 'tables_hub')
            ->assertSee('System Database Tables Directory')
            ->assertSee('Employees & Staff')
            ->assertSee('Jobs & Projects')
            ->assertSee('Tasks & Sub-tasks')
            ->call('openTableExplorer', 'jobs')
            ->assertSet('viewMode', 'records_explorer')
            ->assertSet('selectedTable', 'jobs')
            ->call('switchToTablesHub')
            ->assertSet('viewMode', 'tables_hub');
    }

    /**
     * Test strict Orphan Prevention Rule on Restore:
     * Child record CANNOT be restored if its parent record is inactive or deleted.
     */
    public function test_orphan_prevention_rule_blocks_restoring_children_with_inactive_or_deleted_parent()
    {
        // 1. Inactive Job with Inactive Child Task
        $job = Assignment::create(['name' => 'Highway Bridge 404', 'status' => 'inactive']);
        $task = Task::create(['name' => 'Concrete Pouring', 'assignment_id' => $job->id, 'status' => 'inactive']);

        // Check canRestoreRecord for task: must be blocked because parent job is inactive!
        $check = InactiveRecordsHubService::canRestoreRecord('tasks', $task->id);
        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('inactive/archived', $check['reason']);
        $this->assertEquals('Job', $check['blocking_parent']['type']);

        // Attempting restore via restoreRecord returns blocked result
        $restoreRes = InactiveRecordsHubService::restoreRecord('tasks', $task->id);
        $this->assertTrue($restoreRes['blocked']);
        $this->assertEquals(0, $restoreRes['restored']);
        $this->assertEquals('inactive', $task->fresh()->status); // Still inactive

        // 2. Time Log orphan check: Associated Job is inactive
        $activeWorker = User::factory()->create(['role' => 'Employee', 'status' => 'Active']);
        $inactiveJob = Assignment::create(['name' => 'Suspended Project', 'status' => 'inactive']);
        $taskOnInactiveJob = Task::create(['name' => 'Sub Task', 'assignment_id' => $inactiveJob->id, 'status' => 'active']);
        $logOnInactiveJob = TimeLog::create([
            'employee_id'      => $activeWorker->id,
            'assignment_id'    => $inactiveJob->id,
            'task_id'          => $taskOnInactiveJob->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 16:00:00'),
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $jobCheck = InactiveRecordsHubService::canRestoreRecord('time_logs', $logOnInactiveJob->id);
        $this->assertFalse($jobCheck['allowed']);
        $this->assertStringContainsString('Suspended Project', $jobCheck['reason']);
        $this->assertStringContainsString('inactive or archived', $jobCheck['reason']);

        // 3. Time Log orphan check: Employee is inactive
        $inactiveWorker = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Inactive',
            'name'   => 'Archived Arthur',
        ]);
        $activeJob = Assignment::create(['name' => 'Active Solar Site', 'status' => 'active']);
        $activeTask = Task::create(['name' => 'Panel Mount', 'assignment_id' => $activeJob->id, 'status' => 'active']);

        $log = TimeLog::create([
            'employee_id'      => $inactiveWorker->id,
            'assignment_id'    => $activeJob->id,
            'task_id'          => $activeTask->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 16:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $logCheck = InactiveRecordsHubService::canRestoreRecord('time_logs', $log->id);
        $this->assertFalse($logCheck['allowed']);
        $this->assertStringContainsString('Archived Arthur', $logCheck['reason']);
        $this->assertStringContainsString('inactive or deleted', $logCheck['reason']);

        // Restore worker first -> Now log can be restored!
        InactiveRecordsHubService::restoreRecord('employees', $inactiveWorker->id);
        $this->assertEquals('Active', $inactiveWorker->fresh()->status);

        $logCheckAfterWorkerActive = InactiveRecordsHubService::canRestoreRecord('time_logs', $log->id);
        $this->assertTrue($logCheckAfterWorkerActive['allowed']);

        $restoreLogRes = InactiveRecordsHubService::restoreRecord('time_logs', $log->id);
        $this->assertEquals(1, $restoreLogRes['restored']);
        $this->assertFalse((bool) $log->fresh()->is_archived);
    }

    /**
     * Test Relational Tree Generator and Inspection Modal.
     */
    public function test_relational_tree_generator_and_modal()
    {
        $job = Assignment::create(['name' => 'Mega Data Center', 'status' => 'inactive']);
        $task1 = Task::create(['name' => 'HVAC Ducting', 'assignment_id' => $job->id, 'status' => 'active']);
        $task2 = Task::create(['name' => 'Fiber Optics', 'assignment_id' => $job->id, 'status' => 'inactive']);

        $log = TimeLog::create([
            'employee_id'      => $this->superAdmin->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task1->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 17:00:00'),
            'duration_minutes' => 540,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $tree = InactiveRecordsHubService::getRecordRelationalTree('jobs', $job->id);

        $this->assertNotNull($tree);
        $this->assertEquals('Mega Data Center', $tree['entity']['name']);
        $this->assertCount(3, $tree['children_groups']); // Tasks, TimeLogs, Schedules
        $this->assertEquals(2, $tree['children_groups'][0]['total_count']); // 2 child tasks
        $this->assertEquals(1, $tree['children_groups'][1]['total_count']); // 1 time log

        // Livewire inspectRelationalTree opens modal and populates activeTreeData
        Livewire::actingAs($this->superAdmin)
            ->test(InactiveRecordsManager::class)
            ->call('inspectRelationalTree', "jobs:{$job->id}")
            ->assertSet('showTreeModal', true)
            ->assertSet('treeRecordComposite', "jobs:{$job->id}")
            ->assertSee('Relational Tree & Cascade Impact Visualizer')
            ->assertSee('Mega Data Center')
            ->assertSee('Child Tasks')
            ->call('closeTreeModal')
            ->assertSet('showTreeModal', false);
    }

    /**
     * Test table shedding for production readiness with Super Admin protection.
     */
    public function test_table_shedding_wipes_records_and_safeguards_super_admin()
    {
        // Populate tasks and jobs
        $job = Assignment::create(['name' => 'Alpha Job', 'status' => 'active']);
        Task::create(['name' => 'Alpha Task 1', 'assignment_id' => $job->id, 'status' => 'active']);
        Task::create(['name' => 'Alpha Task 2', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->assertEquals(2, Task::count());

        // Shed tasks table via Livewire
        Livewire::actingAs($this->superAdmin)
            ->test(InactiveRecordsManager::class)
            ->call('promptShedTable', 'tasks')
            ->assertSet('showConfirmModal', true)
            ->assertSet('modalMode', 'shed_table')
            ->assertSet('targetTableKey', 'tasks')
            ->set('confirmText', 'CONFIRM')
            ->call('executeDelete');

        $this->assertEquals(0, Task::count());
        $this->assertEquals(1, Assignment::count()); // Jobs was not shed

        // Shed employees table: must delete regular employee but strictly preserve Super Admin
        Livewire::actingAs($this->superAdmin)
            ->test(InactiveRecordsManager::class)
            ->call('promptShedTable', 'employees')
            ->set('confirmText', 'CONFIRM')
            ->call('executeDelete');

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => 'Super Admin']);
        $this->assertDatabaseMissing('users', ['id' => $this->regularUser->id]);
    }
}
