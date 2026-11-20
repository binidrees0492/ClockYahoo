<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Department;
use App\Models\Location;
use App\Models\Customer;
use App\Models\CompanySetting;
use App\Models\Schedule;
use App\Services\ReportDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class HardDeleteAndInactiveIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrManager;
    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
            'email'  => 'superadmin@isolatetest.com',
        ]);

        $this->hrManager = User::factory()->create([
            'role'   => 'HR Manager',
            'status' => 'Active',
            'email'  => 'hr@isolatetest.com',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
            'email'  => 'admin@isolatetest.com',
        ]);

        $this->employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
            'email'  => 'worker@isolatetest.com',
        ]);
    }

    /**
     * Test 1: Inactive jobs and tasks are NOT returned against personnel.
     */
    public function test_inactive_jobs_and_tasks_are_never_listed_against_personnel()
    {
        $activeJob = Assignment::create(['name' => 'Active Build Project', 'status' => 'active']);
        $inactiveJob = Assignment::create(['name' => 'Archived Legacy Project', 'status' => 'inactive']);

        $activeTask = Task::create([
            'name'          => 'Active Blueprinting',
            'assignment_id' => $activeJob->id,
            'status'        => 'active',
        ]);
        $inactiveTask = Task::create([
            'name'          => 'Defunct Demolition',
            'assignment_id' => $activeJob->id,
            'status'        => 'inactive',
        ]);

        // Assign both active and inactive to employee via permissions
        $this->employee->update([
            'permissions' => [
                'assigned_jobs'  => [$activeJob->id, $inactiveJob->id],
                'assigned_tasks' => [$activeTask->id, $inactiveTask->id],
            ],
        ]);

        // getAssignedJobs() should strictly isolate and only return active jobs
        $assignedJobs = $this->employee->fresh()->getAssignedJobs();
        $this->assertTrue($assignedJobs->contains('id', $activeJob->id));
        $this->assertFalse($assignedJobs->contains('id', $inactiveJob->id));

        // getAssignedTasks() should strictly isolate and only return active tasks
        $assignedTasks = $this->employee->fresh()->getAssignedTasks();
        $this->assertTrue($assignedTasks->contains('id', $activeTask->id));
        $this->assertFalse($assignedTasks->contains('id', $inactiveTask->id));
    }

    /**
     * Test 2: Inactive records are hidden from employee edit modal pickers and schedule board.
     */
    public function test_inactive_entities_hidden_from_management_pickers()
    {
        $activeDept = Department::create(['name' => 'Active Engineering', 'status' => 'Active']);
        $inactiveDept = Department::create(['name' => 'Old Archives', 'status' => 'Inactive']);

        $activeLoc = Location::create(['name' => 'Active HQ', 'status' => 'Active']);
        $inactiveLoc = Location::create(['name' => 'Closed Warehouse', 'status' => 'Inactive']);

        // Employees component render modal pickers
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Employees::class)
            ->call('openAddModal')
            ->assertSee('Active Engineering')
            ->assertDontSee('Old Archives')
            ->assertSee('Active HQ')
            ->assertDontSee('Closed Warehouse');

        // ScheduleBoard assignable lists
        $activeJob = Assignment::create(['name' => 'Active Runway Job', 'status' => 'active']);
        $inactiveJob = Assignment::create(['name' => 'Old Pipeline Job', 'status' => 'inactive']);

        $board = Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\ScheduleBoard::class);

        $board->assertSee('Active Runway Job')
            ->assertDontSee('Old Pipeline Job');
    }

    /**
     * Test 3: Purging / Inactive Records module is ONLY accessible to Super Admins.
     */
    public function test_inactive_records_hub_strictly_restricted_to_super_admin()
    {
        // Employee gets 403
        $this->actingAs($this->employee)
            ->get(route('admin.inactive-records'))
            ->assertStatus(403);

        // HR Manager gets 403
        $this->actingAs($this->hrManager)
            ->get(route('admin.inactive-records'))
            ->assertStatus(403);

        // Administrator gets 403
        $this->actingAs($this->admin)
            ->get(route('admin.inactive-records'))
            ->assertStatus(403);

        // Super Admin gets 200
        $this->actingAs($this->superAdmin)
            ->get(route('admin.inactive-records'))
            ->assertStatus(200);
    }

    /**
     * Test 4: Navigation links for Inactive Records and Data Shedding strictly guarded by Super Admin.
     */
    public function test_navigation_options_restricted_to_super_admin()
    {
        // Super Admin sees both links
        $this->actingAs($this->superAdmin)
            ->get('/dashboard')
            ->assertSee('Inactive Records Hub')
            ->assertSee('Data Shedding');

        // HR Manager does not see them
        $this->actingAs($this->hrManager)
            ->get('/dashboard')
            ->assertDontSee('Inactive Records Hub')
            ->assertDontSee('Data Shedding');

        // Administrator does not see them
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertDontSee('Inactive Records Hub')
            ->assertDontSee('Data Shedding');
    }

    /**
     * Test 5: Hard delete authorization rules via User::canHardDelete().
     */
    public function test_can_hard_delete_authorization_logic()
    {
        // Super Admin can always hard delete
        $this->assertTrue($this->superAdmin->canHardDelete());

        // Standard HR Manager cannot hard delete by default
        $this->assertFalse($this->hrManager->canHardDelete());

        // HR Manager with user setting 'allow_hard_delete' => true can hard delete
        $this->hrManager->update([
            'permissions' => ['allow_hard_delete' => true],
        ]);
        $this->assertTrue($this->hrManager->fresh()->canHardDelete());

        // Reset user permission
        $this->hrManager->update([
            'permissions' => ['allow_hard_delete' => false],
        ]);
        $this->assertFalse($this->hrManager->fresh()->canHardDelete());

        // Company setting 'allow_hr_hard_delete' => true enables it for all HR Managers
        CompanySetting::set('allow_hr_hard_delete', true);
        $this->assertTrue($this->hrManager->fresh()->canHardDelete());

        // But employee still cannot hard delete even if company setting is true
        $this->assertFalse($this->employee->canHardDelete());
    }

    /**
     * Test 6: Direct Hard Delete by authorized HR manager deletes upwards from child to parent.
     */
    public function test_direct_hard_delete_by_authorized_hr_manager_deletes_child_to_parent_upwards()
    {
        // Authorize HR manager
        $this->hrManager->update([
            'permissions' => ['allow_hard_delete' => true],
        ]);

        $job = Assignment::create(['name' => 'Subway Extension', 'status' => 'active']);
        $task = Task::create([
            'name'          => 'Tunnel Excavation',
            'assignment_id' => $job->id,
            'status'        => 'active',
        ]);

        $timeLog = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 16:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
        ]);

        $schedule = Schedule::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => '08:00:00',
            'end_time'      => '16:00:00',
        ]);

        // When authorized HR Manager deletes the job, it permanently deletes children and parent upwards
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Work\Jobs::class)
            ->call('delete', $job->id);

        $this->assertDatabaseMissing('tbl_assignments', ['id' => $job->id]);
        $this->assertDatabaseMissing('tbl_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $timeLog->id]);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    /**
     * Test 7: Unauthorized user deleting record with children marks it inactive instead of hard deleting.
     */
    public function test_unauthorized_user_deleting_record_with_children_marks_it_inactive()
    {
        // Ensure HR Manager has NO hard delete permission
        $this->hrManager->update([
            'permissions' => ['allow_hard_delete' => false],
        ]);
        CompanySetting::set('allow_hr_hard_delete', false);

        $job = Assignment::create(['name' => 'Bridge Maintenance', 'status' => 'active']);
        $task = Task::create([
            'name'          => 'Cable Inspection',
            'assignment_id' => $job->id,
            'status'        => 'active',
        ]);

        $timeLog = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 16:00:00'),
            'duration_minutes' => 480,
            'status'           => 'completed',
        ]);

        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Work\Jobs::class)
            ->call('delete', $job->id);

        // Job should NOT be deleted, but marked inactive!
        $this->assertDatabaseHas('tbl_assignments', [
            'id'     => $job->id,
            'status' => 'inactive',
        ]);
        // Child records remain preserved
        $this->assertDatabaseHas('tbl_tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('tbl_time_logging', ['id' => $timeLog->id]);
    }

    /**
     * Test 8: Deleting child records first individually allows deleting parent upwards.
     */
    public function test_individual_child_deletion_allows_parent_upward_deletion()
    {
        $this->hrManager->update([
            'permissions' => ['allow_hard_delete' => true],
        ]);

        $job = Assignment::create(['name' => 'Roof Renovation', 'status' => 'active']);
        $task = Task::create([
            'name'          => 'Shingle Replacement',
            'assignment_id' => $job->id,
            'status'        => 'active',
        ]);

        $timeLog = TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out'        => Carbon::parse('2026-11-08 12:00:00'),
            'duration_minutes' => 240,
            'status'           => 'completed',
        ]);

        // 1. Delete child task first
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Work\Tasks::class)
            ->call('delete', $task->id);

        $this->assertDatabaseMissing('tbl_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $timeLog->id]);

        // 2. Now delete parent job upwards
        Livewire::actingAs($this->hrManager)
            ->test(\App\Livewire\Work\Jobs::class)
            ->call('delete', $job->id);

        $this->assertDatabaseMissing('tbl_assignments', ['id' => $job->id]);
    }

    /**
     * Test 9: Times formatting in 0h 0m for totals.
     */
    public function test_totals_display_format_0h_0m()
    {
        $this->assertEquals('0h 0m', ReportDataService::formatDurationHM(0));
        $this->assertEquals('0h 0m', ReportDataService::formatDurationHM(null));
        $this->assertEquals('8h 30m', ReportDataService::formatDurationHM(510));
        $this->assertEquals('1h 5m', ReportDataService::formatDurationHM(65));
        $this->assertEquals('40h 0m', ReportDataService::formatDurationHM(2400));

        $this->assertEquals('0h 0m', ReportDataService::formatHoursToHM(0));
        $this->assertEquals('0h 0m', ReportDataService::formatHoursToHM(null));
        $this->assertEquals('8h 30m', ReportDataService::formatHoursToHM(8.5));
        $this->assertEquals('40h 0m', ReportDataService::formatHoursToHM(40));
    }
}
