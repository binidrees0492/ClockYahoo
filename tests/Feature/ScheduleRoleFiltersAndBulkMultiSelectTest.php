<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Livewire\ScheduleCalendar;
use App\Livewire\ScheduleBoard;
use App\Livewire\Schedules;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleRoleFiltersAndBulkMultiSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_accessibility_includes_department_and_own_view()
    {
        $dept1 = Department::create(['name' => 'Engineering']);
        $dept2 = Department::create(['name' => 'Marketing']);

        // Manager in Engineering
        $manager = User::factory()->create([
            'role'          => 'Manager',
            'department_id' => $dept1->id,
            'permissions'   => ['can_manage_schedules' => true, 'can_edit_schedules' => true],
        ]);

        // Employees in Engineering
        $empEng1 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept1->id]);
        $empEng2 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept1->id]);

        // Employee in Marketing
        $empMkt = User::factory()->create(['role' => 'Employee', 'department_id' => $dept2->id]);

        $accessible = $manager->getAccessibleEmployeeIds();
        $this->assertIsArray($accessible);
        $this->assertContains($manager->id, $accessible, 'Manager must be able to view their own schedule');
        $this->assertContains($empEng1->id, $accessible, 'Manager must be able to view their department employee 1');
        $this->assertContains($empEng2->id, $accessible, 'Manager must be able to view their department employee 2');
        $this->assertNotContains($empMkt->id, $accessible, 'Manager must not view employees from other departments');

        // Test in ScheduleBoard
        $this->actingAs($manager);
        $component = Livewire::test(ScheduleBoard::class);
        $filterEmps = $component->viewData('filterEmployees');
        $this->assertNotNull($filterEmps);
        $filterIds = $filterEmps->pluck('id')->toArray();
        $this->assertContains($manager->id, $filterIds);
        $this->assertContains($empEng1->id, $filterIds);
        $this->assertContains($empEng2->id, $filterIds);
        $this->assertNotContains($empMkt->id, $filterIds);

        // Test in ScheduleCalendar
        $calComponent = Livewire::test(ScheduleCalendar::class);
        $calEmps = $calComponent->viewData('employees');
        $calIds = $calEmps->pluck('id')->toArray();
        $this->assertContains($manager->id, $calIds);
        $this->assertContains($empEng1->id, $calIds);
        $this->assertContains($empEng2->id, $calIds);
        $this->assertNotContains($empMkt->id, $calIds);

        // Test in Schedules weekly table
        $schedulesComponent = Livewire::test(Schedules::class);
        $schedEmps = $schedulesComponent->viewData('employees');
        $schedIds = $schedEmps->pluck('id')->toArray();
        $this->assertContains($manager->id, $schedIds);
        $this->assertContains($empEng1->id, $schedIds);
        $this->assertContains($empEng2->id, $schedIds);
        $this->assertNotContains($empMkt->id, $schedIds);
    }

    public function test_hr_manager_and_super_admin_have_unrestricted_employee_access()
    {
        $dept1 = Department::create(['name' => 'Engineering']);
        $dept2 = Department::create(['name' => 'Sales']);

        $hr = User::factory()->create(['role' => 'HR Manager']);
        $admin = User::factory()->create(['role' => 'Administrator']);
        $superAdmin = User::factory()->create(['role' => 'Super Administrator']);

        $emp1 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept1->id]);
        $emp2 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept2->id]);

        $this->assertNull($hr->getAccessibleEmployeeIds());
        $this->assertNull($superAdmin->getAccessibleEmployeeIds());

        $this->actingAs($hr);
        $component = Livewire::test(ScheduleBoard::class);
        $filterEmps = $component->viewData('filterEmployees');
        $filterIds = $filterEmps->pluck('id')->toArray();
        $this->assertContains($emp1->id, $filterIds);
        $this->assertContains($emp2->id, $filterIds);

        $this->actingAs($superAdmin);
        $componentAdmin = Livewire::test(ScheduleBoard::class);
        $filterEmpsAdmin = $componentAdmin->viewData('filterEmployees');
        $filterIdsAdmin = $filterEmpsAdmin->pluck('id')->toArray();
        $this->assertContains($emp1->id, $filterIdsAdmin);
        $this->assertContains($emp2->id, $filterIdsAdmin);
    }

    public function test_bulk_add_multiple_employees_multiple_jobs_and_tasks_with_no_break()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp1 = User::factory()->create(['role' => 'Employee']);
        $emp2 = User::factory()->create(['role' => 'Employee']);

        $job1 = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task1 = Task::create(['name' => 'Wiring', 'assignment_id' => $job1->id, 'status' => 'active']);
        $task2 = Task::create(['name' => 'Inspection', 'assignment_id' => $job1->id, 'status' => 'active']);

        $this->actingAs($admin);

        // In ScheduleCalendar: select 2 employees, 2 tasks across 2 dates
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift', '2026-11-16')
            ->set('shiftEmployeeIds', [$emp1->id, $emp2->id])
            ->set('shiftAssignmentIds', [$job1->id])
            ->set('shiftTaskIds', [$task1->id, $task2->id])
            ->set('shiftDate', '2026-11-16')
            ->set('shiftEndDate', '2026-11-17')
            ->set('shiftDaysBreakdown', [
                [
                    'date'       => '2026-11-16',
                    'day_name'   => 'Monday',
                    'formatted'  => 'Mon, Nov 16',
                    'is_weekend' => false,
                    'enabled'    => true,
                    'start_time' => '08:00',
                    'end_time'   => '16:00',
                    'hours'      => 8.0,
                ],
                [
                    'date'       => '2026-11-17',
                    'day_name'   => 'Tuesday',
                    'formatted'  => 'Tue, Nov 17',
                    'is_weekend' => false,
                    'enabled'    => true,
                    'start_time' => '08:00',
                    'end_time'   => '16:00',
                    'hours'      => 8.0,
                ],
            ])
            ->call('saveShift', true) // publish
            ->assertDispatched('toast');

        // Total shifts created should be:
        // 2 employees x 2 tasks x 2 dates = 8 independent schedules
        $schedules = Schedule::whereIn('employee_id', [$emp1->id, $emp2->id])->get();
        $this->assertCount(8, $schedules);

        // Verify that NO break deduction was added (break_minutes = 0, break_start = null)
        foreach ($schedules as $s) {
            $this->assertEquals(0, $s->break_minutes);
            $this->assertNull($s->break_start);
            $this->assertNull($s->break_end);
            $this->assertEquals('published', $s->status);
            $this->assertTrue($s->is_published);
        }

        // Verify option to alter/edit individually later
        $firstShift = $schedules->first();
        Livewire::test(ScheduleCalendar::class)
            ->call('openEditShift', $firstShift->id)
            ->set('startTime', '07:30')
            ->set('endTime', '15:30')
            ->call('saveShift', true);

        $firstShift->refresh();
        $this->assertEquals('07:30', substr($firstShift->start_time, 0, 5));
        $this->assertEquals('15:30', substr($firstShift->end_time, 0, 5));

        // The remaining 7 shifts remain unaltered
        $unaltered = Schedule::where('id', '!=', $firstShift->id)->get();
        foreach ($unaltered as $s) {
            $this->assertEquals('08:00', substr($s->start_time, 0, 5));
        }
    }

    public function test_schedule_board_bulk_add_multiple_employees_and_tasks()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp1 = User::factory()->create(['role' => 'Employee']);
        $emp2 = User::factory()->create(['role' => 'Employee']);

        $job = Assignment::create(['name' => 'Solar Installation', 'status' => 'active']);
        $task = Task::create(['name' => 'Mounting Panels', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [$emp1->id, $emp2->id])
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [$task->id])
            ->set('shiftDateFrom', '2026-11-20')
            ->set('shiftDateTo', '2026-11-20')
            ->call('saveShift', false) // draft
            ->assertDispatched('toast');

        // 2 employees x 1 task x 1 date = 2 schedules
        $created = Schedule::whereIn('employee_id', [$emp1->id, $emp2->id])->get();
        $this->assertCount(2, $created);

        foreach ($created as $s) {
            $this->assertEquals(0, $s->break_minutes);
            $this->assertNull($s->break_start);
            $this->assertEquals('draft', $s->status);
            $this->assertFalse($s->is_published);
        }
    }

    public function test_shift_creation_requires_employee_job_and_task_in_schedule_board()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Roofing Project', 'status' => 'active']);
        $task = Task::create(['name' => 'Tiling', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        // 1. Missing Employee
        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [])
            ->set('shiftEmployeeId', null)
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [$task->id])
            ->call('saveShift')
            ->assertHasErrors(['shiftEmployeeIds']);

        // 2. Missing Job
        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [$emp->id])
            ->set('shiftAssignmentIds', [])
            ->set('shiftAssignmentId', null)
            ->set('shiftTaskIds', [$task->id])
            ->call('saveShift')
            ->assertHasErrors(['shiftAssignmentIds']);

        // 3. Missing Task
        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [$emp->id])
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [])
            ->set('shiftTaskId', null)
            ->call('saveShift')
            ->assertHasErrors(['shiftTaskIds']);

        // Assert 0 schedules created
        $this->assertEquals(0, Schedule::count());
    }

    public function test_shift_creation_requires_employee_job_and_task_in_schedule_calendar()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Concrete Laying', 'status' => 'active']);
        $task = Task::create(['name' => 'Mixing', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        // 1. Missing Employee
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [])
            ->set('shiftEmployeeId', null)
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [$task->id])
            ->call('saveShift')
            ->assertHasErrors(['shiftEmployeeIds']);

        // 2. Missing Job
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [$emp->id])
            ->set('shiftAssignmentIds', [])
            ->set('shiftAssignmentId', null)
            ->set('shiftTaskIds', [$task->id])
            ->call('saveShift')
            ->assertHasErrors(['shiftAssignmentIds']);

        // 3. Missing Task
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift')
            ->set('shiftEmployeeIds', [$emp->id])
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [])
            ->set('shiftTaskId', null)
            ->call('saveShift')
            ->assertHasErrors(['shiftTaskIds']);

        // Assert 0 schedules created
        $this->assertEquals(0, Schedule::count());
    }

    public function test_dashboard_schedule_calendar_uses_same_modal_and_bindings()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Electrical Panel', 'status' => 'active']);
        $task = Task::create(['name' => 'Testing breakers', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift', '2026-11-25')
            ->assertSet('showShiftModal', true)
            ->assertSet('showAddShift', true)
            ->assertSet('shiftDateFrom', '2026-11-25')
            ->assertSet('shiftDateTo', '2026-11-25')
            ->assertSet('shiftStart', '08:00')
            ->assertSet('shiftEnd', '17:00')
            ->assertSet('shiftStandardHours', 8.00)
            ->set('shiftEmployeeIds', [$emp->id])
            ->set('shiftAssignmentIds', [$job->id])
            ->set('shiftTaskIds', [$task->id])
            ->call('saveShift', true)
            ->assertDispatched('toast');

        $schedule = Schedule::where('employee_id', $emp->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals($job->id, $schedule->assignment_id);
        $this->assertEquals($task->id, $schedule->task_id);
        $this->assertEquals('published', $schedule->status);
        $this->assertTrue($schedule->is_published);
    }
}
