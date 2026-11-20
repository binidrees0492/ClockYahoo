<?php

namespace Tests\Feature;

use App\Livewire\Timesheets;
use App\Livewire\TimesheetsView;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManualTimeEntryBreakdownAndAssignedJobFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Super Admin'], ['description' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Employee'], ['description' => 'Employee']);
    }

    public function test_add_manual_entry_strictly_filters_assigned_jobs_and_tasks_for_employee(): void
    {
        // Job 1: assigned to $employee
        $jobAssigned = Assignment::create(['name' => 'Assigned Project A', 'status' => 'active']);
        // Job 2: unassigned
        $jobUnassigned = Assignment::create(['name' => 'Unassigned Project B', 'status' => 'active']);
        // Job 3: inactive job
        $jobInactive = Assignment::create(['name' => 'Inactive Project C', 'status' => 'inactive']);

        // Tasks for Job 1
        $taskAssigned = Task::create([
            'name'          => 'Design Layout',
            'assignment_id' => $jobAssigned->id,
            'status'        => 'active',
        ]);
        $taskUnassigned = Task::create([
            'name'          => 'Backend API',
            'assignment_id' => $jobAssigned->id,
            'status'        => 'active',
        ]);
        $taskInactive = Task::create([
            'name'          => 'Deprecated Task',
            'assignment_id' => $jobAssigned->id,
            'status'        => 'inactive',
        ]);

        $employee = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'active',
            'permissions' => [
                'mobile_timesheets' => 2,
                'assigned_jobs'     => [$jobAssigned->id],
                'assigned_tasks'    => [$taskAssigned->id, $taskInactive->id],
            ],
        ]);

        // Acting as employee
        $this->actingAs($employee);

        $component = Livewire::test(TimesheetsView::class)
            ->call('openAdd');

        // Verify modalAssignments only contains $jobAssigned
        $modalAssignments = $component->get('modalAssignments');
        $this->assertCount(1, $modalAssignments);
        $this->assertEquals($jobAssigned->id, $modalAssignments->first()->id);

        // Select the assigned job
        $component->set('addAssignment', $jobAssigned->id);

        // Verify modalTasks only contains $taskAssigned (not $taskUnassigned, not $taskInactive)
        $modalTasks = $component->get('modalTasks');
        $this->assertCount(1, $modalTasks);
        $this->assertEquals($taskAssigned->id, $modalTasks->first()->id);
    }

    public function test_manager_adding_manual_entry_for_employee_filters_jobs_assigned_to_that_target_employee(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);

        $jobTarget = Assignment::create(['name' => 'Target Emp Job', 'status' => 'active']);
        $jobOther = Assignment::create(['name' => 'Other Emp Job', 'status' => 'active']);

        $taskTarget = Task::create(['name' => 'Target Task', 'assignment_id' => $jobTarget->id, 'status' => 'active']);
        $taskOther = Task::create(['name' => 'Other Task', 'assignment_id' => $jobOther->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'active',
            'permissions' => [
                'mobile_timesheets' => 2,
                'assigned_jobs'     => [$jobTarget->id],
                'assigned_tasks'    => [$taskTarget->id],
            ],
        ]);

        $this->actingAs($admin);

        // In TimesheetsView, admin opens modal and sets target employee
        $component = Livewire::test(TimesheetsView::class)
            ->call('openAdd', $employee->id)
            ->assertSet('addEmployeeId', $employee->id);

        $modalAssignments = $component->get('modalAssignments');
        $this->assertCount(1, $modalAssignments);
        $this->assertEquals($jobTarget->id, $modalAssignments->first()->id);

        $component->set('addAssignment', $jobTarget->id);
        $modalTasks = $component->get('modalTasks');
        $this->assertCount(1, $modalTasks);
        $this->assertEquals($taskTarget->id, $modalTasks->first()->id);

        // Attempting to bypass and save with unassigned job should be rejected
        $component->set('addAssignment', $jobOther->id)
            ->set('addTask', $taskOther->id)
            ->set('addNotes', 'Attempting unassigned job')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'You can only log time for jobs assigned to this employee.', type: 'error');
    }

    public function test_week_by_week_navigation_and_day_by_day_split_breakdown(): void
    {
        // Set week start day to Monday (1) for clean 2-week chunk test
        CompanySetting::set('week_start_day', 1);

        $job = Assignment::create(['name' => 'Construction', 'status' => 'active']);
        $task = Task::create(['name' => 'Framing', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'active',
            'permissions' => [
                'mobile_timesheets' => 2,
                'assigned_jobs'     => [$job->id],
                'assigned_tasks'    => [$task->id],
            ],
        ]);

        $this->actingAs($employee);

        // Date range spanning 2 weeks: Monday 2026-11-09 to Friday 2026-11-20 (12 days total)
        $component = Livewire::test(TimesheetsView::class)
            ->call('openAdd')
            ->set('addStartDate', '2026-11-09')
            ->set('addEndDate', '2026-11-20')
            ->call('regenerateDaysBreakdown');

        $breakdown = $component->get('addDaysBreakdown');
        $this->assertCount(12, $breakdown);

        // Check default hours inserted
        $this->assertEquals(8.0, $breakdown[0]['hours']);
        $this->assertTrue($breakdown[0]['enabled']);

        // Check weekly chunks
        $weekly = $component->get('addWeeklyBreakdown');
        $this->assertCount(2, $weekly); // 2 distinct ISO weeks

        // Test week navigation
        $this->assertEquals(0, $component->get('addBreakdownWeekPage'));
        $component->call('nextAddBreakdownWeek');
        $this->assertEquals(1, $component->get('addBreakdownWeekPage'));
        $component->call('prevAddBreakdownWeek');
        $this->assertEquals(0, $component->get('addBreakdownWeekPage'));

        // Toggle day 1 off
        $component->call('toggleAddDayEnabled', 0);
        $updatedBreakdown = $component->get('addDaysBreakdown');
        $this->assertFalse($updatedBreakdown[0]['enabled']);

        // Toggle break on day 1
        $component->call('toggleAddDayBreak', 1);
        $updatedBreakdown = $component->get('addDaysBreakdown');
        $this->assertTrue($updatedBreakdown[1]['has_break']);
        $this->assertEquals(30, $updatedBreakdown[1]['break_minutes']);
    }

    public function test_save_multi_day_breakdown_creates_timelogs_with_net_hours_and_pending_status(): void
    {
        $job = Assignment::create(['name' => 'HVAC Maintenance', 'status' => 'active']);
        $task = Task::create(['name' => 'Filter Replacement', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'active',
            'base_pay'    => 25.00,
            'permissions' => [
                'mobile_timesheets' => 2,
                'assigned_jobs'     => [$job->id],
                'assigned_tasks'    => [$task->id],
            ],
        ]);

        $this->actingAs($employee);

        // Create 2-day range: 2026-11-10 to 2026-11-11
        $component = Livewire::test(TimesheetsView::class)
            ->call('openAdd')
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addStartDate', '2026-11-10')
            ->set('addEndDate', '2026-11-11')
            ->call('regenerateDaysBreakdown')
            ->set('addNotes', 'Replaced filters across commercial units.')
            // Add a 30-min break to the second day (2026-11-11)
            ->call('toggleAddDayBreak', 1);

        $component->call('saveAdd')
            ->assertDispatched('toast', message: '2 manual time entries added. Submitted for approval.');

        // Assert 2 TimeLogs were created in database
        $logs = TimeLog::where('employee_id', $employee->id)->orderBy('clock_in')->get();
        $this->assertCount(2, $logs);

        // Day 1: 8 hours (480 mins), no break
        $log1 = $logs[0];
        $this->assertEquals('2026-11-10', Carbon::parse($log1->clock_in)->toDateString());
        $this->assertEquals(480, $log1->duration_minutes);
        $this->assertEquals(0, $log1->break_minutes);
        $this->assertEquals('pending', $log1->status);
        $this->assertTrue((bool)$log1->is_manual);
        $this->assertFalse((bool)$log1->approved);
        $this->assertEquals('Replaced filters across commercial units.', $log1->notes);

        // Day 2: 8 hours shift (480 mins) minus 30 min break = 450 mins net duration
        $log2 = $logs[1];
        $this->assertEquals('2026-11-11', Carbon::parse($log2->clock_in)->toDateString());
        $this->assertEquals(450, $log2->duration_minutes);
        $this->assertEquals(30, $log2->break_minutes);
        $this->assertEquals('pending', $log2->status);
        $this->assertTrue((bool)$log2->is_manual);
        $this->assertNotEmpty($log2->breaks);
    }

    public function test_timesheets_component_also_supports_breakdown_and_strict_assignment_filtering(): void
    {
        $job = Assignment::create(['name' => 'Site Inspection', 'status' => 'active']);
        $task = Task::create(['name' => 'Safety Audit', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'active',
            'permissions' => [
                'mobile_timesheets' => 2,
                'assigned_jobs'     => [$job->id],
                'assigned_tasks'    => [$task->id],
            ],
        ]);

        $this->actingAs($employee);

        $component = Livewire::test(Timesheets::class)
            ->call('openAdd')
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addStartDate', '2026-11-12')
            ->set('addEndDate', '2026-11-12')
            ->call('regenerateDaysBreakdown');

        $this->assertCount(1, $component->get('modalAssignments'));
        $this->assertCount(1, $component->get('modalTasks'));

        $component->call('saveAdd')
            ->assertDispatched('toast', message: 'Manual time entry added. Submitted for approval.');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'duration_minutes' => 480,
            'status'           => 'pending',
            'is_manual'        => true,
        ]);
    }
}
