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
use App\Services\DataSheddingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class SuperAdminDeletedRecordsAndDataSheddingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrManager;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
            'email'  => 'superadmin@example.com',
        ]);

        $this->hrManager = User::factory()->create([
            'role'   => 'HR Manager',
            'status' => 'Active',
            'email'  => 'hr@example.com',
        ]);

        $this->employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
            'email'  => 'employee@example.com',
        ]);
    }

    /**
     * Test Employees table: includeDeleted checkbox, restore, and permanent deletion with cascade.
     */
    public function test_employees_table_super_admin_restore_and_permanent_delete()
    {
        $inactiveEmployee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Inactive',
            'name'   => 'Inactive Worker',
        ]);

        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Task One', 'assignment_id' => $job->id, 'status' => 'active']);

        $timeLog = TimeLog::create([
            'employee_id'      => $inactiveEmployee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 09:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 17:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        $note = TimeLogNote::create([
            'time_log_id' => $timeLog->id,
            'user_id'     => $this->superAdmin->id,
            'body'        => 'Worker note',
        ]);

        $schedule = Schedule::create([
            'employee_id'   => $inactiveEmployee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
        ]);

        // 1. Non-super admin cannot see includeDeleted and cannot see inactive employee by default
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Employees::class)
            ->assertDontSee('Include Deleted Records')
            ->assertDontSee('Inactive Worker');

        // 2. Non-super admin is forbidden from calling restore or permanently delete
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Employees::class)
            ->call('restoreEmployee', $inactiveEmployee->id)
            ->assertStatus(403);

        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Employees::class)
            ->call('permanentlyDeleteEmployee', $inactiveEmployee->id)
            ->assertStatus(403);

        // 3. Super Admin sees the checkbox and can toggle to view deleted records
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Employees::class)
            ->assertSee('Include Deleted Records')
            ->assertDontSee('Inactive Worker')
            ->set('includeDeleted', true)
            ->assertSee('Inactive Worker');

        // 4. Super Admin restores the employee and their child records
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Employees::class)
            ->call('restoreEmployee', $inactiveEmployee->id);

        $this->assertEquals('Active', $inactiveEmployee->fresh()->status);
        $this->assertFalse((bool) $timeLog->fresh()->is_archived);

        // Deactivate again and test permanently delete
        $inactiveEmployee->update(['status' => 'Inactive']);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Employees::class)
            ->call('permanentlyDeleteEmployee', $inactiveEmployee->id);

        $this->assertDatabaseMissing('users', ['id' => $inactiveEmployee->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $timeLog->id]);
        $this->assertDatabaseMissing('time_log_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    /**
     * Test Jobs & Tasks table: includeDeleted checkbox, restore, and permanent deletion with cascade.
     */
    public function test_jobs_and_tasks_super_admin_restore_and_permanent_delete()
    {
        $job = Assignment::create(['name' => 'Demolition Job', 'status' => 'inactive']);
        $task = Task::create(['name' => 'Clean Debris', 'assignment_id' => $job->id, 'status' => 'inactive']);

        $log = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 12:00:00'),
            'duration_minutes' => 240,
            'status'           => 'completed',
            'is_archived'      => true,
        ]);

        // Jobs component tests
        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Work\Jobs::class)
            ->assertDontSee('Include Deleted Records');

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Work\Jobs::class)
            ->call('restoreJob', $job->id)
            ->assertStatus(403);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Work\Jobs::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->assertSee('Demolition Job')
            ->call('restoreJob', $job->id);

        $this->assertEquals('active', $job->fresh()->status);
        $this->assertEquals('active', $task->fresh()->status);
        $this->assertFalse((bool) $log->fresh()->is_archived);

        // Tasks component tests
        $task->update(['status' => 'inactive']);

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Work\Tasks::class)
            ->assertDontSee('Include Deleted Records');

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Work\Tasks::class)
            ->call('restoreTask', $task->id)
            ->assertStatus(403);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Work\Tasks::class)
            ->set('includeDeleted', true)
            ->assertSee('Clean Debris')
            ->call('permanentlyDeleteTask', $task->id);

        $this->assertDatabaseMissing('tbl_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $log->id]);

        // Permanently delete job
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Work\Jobs::class)
            ->call('permanentlyDeleteJob', $job->id);

        $this->assertDatabaseMissing('tbl_assignments', ['id' => $job->id]);
    }

    /**
     * Test TimesheetsView & Timesheets (Grid): includeDeleted checkbox, restore, and permanent deletion with cascade.
     */
    public function test_timesheets_super_admin_restore_and_permanent_delete()
    {
        $job = Assignment::create(['name' => 'HVAC Project', 'status' => 'active']);
        $task = Task::create(['name' => 'Installation', 'assignment_id' => $job->id, 'status' => 'active']);

        $parentLog = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->startOfWeek()->addHours(10),
            'clock_out'        => Carbon::now()->startOfWeek()->addHours(14),
            'duration_minutes' => 240,
            'status'           => 'completed',
            'is_archived'      => true,
            'archived_at'      => now(),
            'archived_by'      => $this->superAdmin->id,
        ]);

        $childSwitchLog = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'parent_log_id'    => $parentLog->id,
            'clock_in'         => Carbon::now()->startOfWeek()->addHours(12),
            'clock_out'        => Carbon::now()->startOfWeek()->addHours(14),
            'duration_minutes' => 120,
            'status'           => 'completed',
            'is_archived'      => true,
            'archived_at'      => now(),
            'archived_by'      => $this->superAdmin->id,
        ]);

        $note = TimeLogNote::create([
            'time_log_id' => $parentLog->id,
            'user_id'     => $this->superAdmin->id,
            'body'        => 'HVAC Note',
        ]);

        // TimesheetsView tests
        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\TimesheetsView::class)
            ->assertDontSee('Include Deleted Records');

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\TimesheetsView::class)
            ->call('restoreArchivedLog', $parentLog->id)
            ->assertStatus(403);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\TimesheetsView::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->call('restoreArchivedLog', $parentLog->id);

        $this->assertFalse((bool) $parentLog->fresh()->is_archived);
        $this->assertFalse((bool) $childSwitchLog->fresh()->is_archived);

        // Timesheets (Grid) tests
        $parentLog->update(['is_archived' => true]);
        $childSwitchLog->update(['is_archived' => true]);

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Timesheets::class)
            ->assertDontSee('Include Deleted Records');

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Timesheets::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->call('permanentlyDeleteLog', $parentLog->id);

        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $parentLog->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $childSwitchLog->id]);
        $this->assertDatabaseMissing('time_log_notes', ['id' => $note->id]);
    }

    /**
     * Test Departments, Locations, Customers, and PolicyManager restore and permanent delete.
     */
    public function test_departments_locations_customers_policies_management()
    {
        // 1. Department
        $dept = Department::create(['name' => 'Archived Marketing', 'status' => 'Inactive']);
        $sched = Schedule::create([
            'employee_id'   => $this->employee->id,
            'department_id' => $dept->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '08:00:00',
            'end_time'      => '16:00:00',
        ]);
        $this->employee->update(['department_id' => $dept->id]);

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\Departments::class)
            ->assertStatus(403);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Departments::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->assertSee('Archived Marketing')
            ->call('restoreDepartment', $dept->id);

        $this->assertEquals('Active', $dept->fresh()->status);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Departments::class)
            ->call('permanentlyDeleteDepartment', $dept->id);

        $this->assertDatabaseMissing('tbl_departments', ['id' => $dept->id]);
        $this->assertNull($this->employee->fresh()->department_id);

        // 2. Location
        $loc = Location::create(['name' => 'Old Branch', 'status' => 'Inactive']);
        $this->employee->update(['location_id' => $loc->id]);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Locations::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->assertSee('Old Branch')
            ->call('restoreLocation', $loc->id);

        $this->assertEquals('Active', $loc->fresh()->status);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Locations::class)
            ->call('permanentlyDeleteLocation', $loc->id);

        $this->assertDatabaseMissing('tbl_locations', ['id' => $loc->id]);
        $this->assertNull($this->employee->fresh()->location_id);

        // 3. Customer
        $cust = Customer::create(['name' => 'Inactive Client Corp', 'active' => false]);
        $jobCust = Assignment::create(['name' => 'Client Project', 'customer_id' => $cust->id, 'status' => 'active']);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Work\Customers::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->assertSee('Inactive Client Corp')
            ->call('restoreCustomer', $cust->id);

        $this->assertTrue((bool) $cust->fresh()->active);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Work\Customers::class)
            ->call('permanentlyDeleteCustomer', $cust->id);

        $this->assertDatabaseMissing('customers', ['id' => $cust->id]);
        $this->assertNull($jobCust->fresh()->customer_id);

        // 4. PolicyManager
        $policy = TimeOffPolicy::create([
            'name'           => 'Old Vacation Policy',
            'policy_type'    => 0,
            'accrual_method' => 0,
            'active'         => false,
        ]);
        $enrollment = TimeOffPolicyEmployee::create([
            'policy_id'      => $policy->id,
            'employee_id'    => $this->employee->id,
            'balance_hours'  => 40,
        ]);
        $toReq = TimeOffRequest::create([
            'employee_id' => $this->employee->id,
            'policy_id'   => $policy->id,
            'start_at'    => Carbon::today()->addDays(5),
            'end_at'      => Carbon::today()->addDays(7),
            'status'      => 'pending',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\TimeOff\PolicyManager::class)
            ->assertSee('Include Deleted Records')
            ->set('includeDeleted', true)
            ->assertSee('Old Vacation Policy')
            ->call('restorePolicy', $policy->id);

        $this->assertTrue((bool) $policy->fresh()->active);

        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\TimeOff\PolicyManager::class)
            ->call('permanentlyDeletePolicy', $policy->id);

        $this->assertDatabaseMissing('time_off_policies', ['id' => $policy->id]);
        $this->assertDatabaseMissing('time_off_policy_employees', ['id' => $enrollment->id]);
        $this->assertDatabaseMissing('time_off_requests', ['id' => $toReq->id]);
    }

    /**
     * Test DataSheddingService directly: selectively purges chosen tables and their children,
     * while strictly preserving the Super Admin user account and system settings.
     */
    public function test_data_shedding_service_execution()
    {
        $service = new DataSheddingService();
        $available = $service->getAvailableEntities();

        $this->assertCount(10, $available);
        $this->assertArrayHasKey('time_logs', $available);
        $this->assertArrayHasKey('employees', $available);
        $this->assertArrayHasKey('jobs', $available);
        $this->assertArrayHasKey('tasks', $available);

        // Seed some data
        $job = Assignment::create(['name' => 'Demo Job', 'status' => 'active']);
        $task = Task::create(['name' => 'Demo Task', 'assignment_id' => $job->id, 'status' => 'active']);
        $log = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->subHours(2),
            'clock_out'        => Carbon::now(),
            'duration_minutes' => 120,
            'status'           => 'completed',
        ]);

        // Purge only time_logs and employees (leaving jobs and tasks intact)
        $result = $service->shedData(['time_logs', 'employees'], $this->superAdmin->id);

        $this->assertArrayHasKey('time_logs', $result);
        $this->assertArrayHasKey('employees', $result);

        // Time log and non-super-admin employee must be gone
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $log->id]);
        $this->assertDatabaseMissing('users', ['id' => $this->employee->id]);

        // Super Admin MUST be preserved!
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => 'Super Admin']);

        // Job and Task were NOT selected, so they MUST still exist!
        $this->assertDatabaseHas('tbl_assignments', ['id' => $job->id]);
        $this->assertDatabaseHas('tbl_tasks', ['id' => $task->id]);
    }

    /**
     * Test Livewire DataShedding component: authentication gate, UI selection, and CONFIRM requirement.
     */
    public function test_livewire_data_shedding_component_workflow()
    {
        // 1. Non-super admin cannot access
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\DataShedding::class)
            ->assertStatus(403);

        Livewire::actingAs($this->employee)
            ->test(\App\Livewire\DataShedding::class)
            ->assertStatus(403);

        // 2. Super admin can access and interact
        $component = Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\DataShedding::class)
            ->assertSee('Production Readiness')
            ->assertSee('Select All')
            ->call('selectAll');

        $this->assertCount(10, $component->get('selectedEntities'));

        // Deselect all and pick only 'time_logs'
        $component->call('deselectAll');
        $this->assertEmpty($component->get('selectedEntities'));

        $component->call('toggleEntity', 'time_logs');
        $this->assertTrue(in_array('time_logs', $component->get('selectedEntities')));

        // 3. Attempting to execute purge without CONFIRM fails
        $component->call('promptConfirmation')
            ->assertSet('showConfirmModal', true)
            ->set('confirmText', 'WRONG')
            ->call('executePurge');

        // selectedEntities should not have been purged with wrong confirmText
        $this->assertTrue(in_array('time_logs', $component->get('selectedEntities')));

        // 4. Executing purge with CONFIRM succeeds
        $component->set('confirmText', 'CONFIRM')
            ->call('executePurge')
            ->assertSet('showConfirmModal', false);

        $this->assertEmpty($component->get('selectedEntities'));
    }
}
