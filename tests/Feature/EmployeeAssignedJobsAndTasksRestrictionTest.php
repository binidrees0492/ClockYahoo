<?php

namespace Tests\Feature;

use App\Livewire\Employees;
use App\Livewire\TimeClock;
use App\Livewire\Work\Jobs;
use App\Livewire\Work\Tasks;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeAssignedJobsAndTasksRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_only_see_and_clock_into_assigned_jobs_and_tasks()
    {
        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 25.00,
        ]);

        $jobA = Assignment::create(['name' => 'Assigned Job A', 'status' => 'active']);
        $jobB = Assignment::create(['name' => 'Unassigned Job B', 'status' => 'active']);
        $jobC = Assignment::create(['name' => 'Scheduled Shift Job C', 'status' => 'active']);

        $task1 = Task::create(['name' => 'Assigned Task 1', 'status' => 'in progress', 'hourly_rate' => 25.00]);
        $task2 = Task::create(['name' => 'Unassigned Task 2', 'status' => 'in progress', 'hourly_rate' => 25.00]);
        $task3 = Task::create(['name' => 'Scheduled Shift Task 3', 'status' => 'in progress', 'hourly_rate' => 25.00]);

        // Explicitly assign Job A and Task 1
        $employee->update([
            'permissions' => [
                'assigned_jobs'  => [$jobA->id],
                'assigned_tasks' => [$task1->id],
                'allow_web_clock'=> true,
                'jobs'           => 1,
                'tasks'          => 1,
            ],
        ]);

        // Assign Job C and Task 3 via scheduled shift
        Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $jobC->id,
            'task_id'       => $task3->id,
            'shift_date'    => Carbon::today()->toDateString(),
            'start_time'    => Carbon::today()->setTime(9, 0),
            'end_time'      => Carbon::today()->setTime(17, 0),
            'status'        => 'scheduled',
        ]);

        $this->actingAs($employee);

        // 1. TimeClock dropdowns should contain Job A & C, but NOT Job B
        $clockComponent = Livewire::test(TimeClock::class);
        $assignments = $clockComponent->get('assignments');
        $tasks = $clockComponent->get('tasks');

        $this->assertTrue($assignments->contains('id', $jobA->id));
        $this->assertTrue($assignments->contains('id', $jobC->id));
        $this->assertFalse($assignments->contains('id', $jobB->id));

        $this->assertTrue($tasks->contains('id', $task1->id));
        $this->assertTrue($tasks->contains('id', $task3->id));
        $this->assertFalse($tasks->contains('id', $task2->id));

        // 2. Jobs management list should only list Job A and Job C
        Livewire::test(Jobs::class)
            ->assertSee('Assigned Job A')
            ->assertSee('Scheduled Shift Job C')
            ->assertDontSee('Unassigned Job B');

        // 3. Tasks management list should only list Task 1 and Task 3
        Livewire::test(Tasks::class)
            ->assertSee('Assigned Task 1')
            ->assertSee('Scheduled Shift Task 3')
            ->assertDontSee('Unassigned Task 2');

        // 4. Attempting to clock in to unassigned Job B is rejected
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobB->id)
            ->set('selectedTask', $task1->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'You are only allowed to clock in to jobs assigned to you.', type: 'error');

        // 5. Attempting to clock in to unassigned Task 2 is rejected
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobA->id)
            ->set('selectedTask', $task2->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'You are only allowed to clock in to tasks assigned to you.', type: 'error');

        // 6. Clocking in to assigned Job A and Task 1 succeeds
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobA->id)
            ->set('selectedTask', $task1->id)
            ->call('clockIn');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $jobA->id,
            'task_id'       => $task1->id,
            'clock_out'     => null,
        ]);
    }

    public function test_admin_and_manager_have_global_visibility_to_all_ongoing_jobs_and_tasks()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $manager = User::factory()->create(['role' => 'Manager']);

        $jobA = Assignment::create(['name' => 'Global Job 1', 'status' => 'active']);
        $jobB = Assignment::create(['name' => 'Global Job 2', 'status' => 'active']);
        $task1 = Task::create(['name' => 'Global Task 1', 'status' => 'in progress']);
        $task2 = Task::create(['name' => 'Global Task 2', 'status' => 'in progress']);

        // Admin sees all
        $this->actingAs($admin);
        $adminClock = Livewire::test(TimeClock::class);
        $this->assertTrue($adminClock->get('assignments')->contains('id', $jobA->id));
        $this->assertTrue($adminClock->get('assignments')->contains('id', $jobB->id));
        $this->assertTrue($adminClock->get('tasks')->contains('id', $task1->id));
        $this->assertTrue($adminClock->get('tasks')->contains('id', $task2->id));

        // Manager sees all
        $this->actingAs($manager);
        $managerClock = Livewire::test(TimeClock::class);
        $this->assertTrue($managerClock->get('assignments')->contains('id', $jobA->id));
        $this->assertTrue($managerClock->get('assignments')->contains('id', $jobB->id));
        $this->assertTrue($managerClock->get('tasks')->contains('id', $task1->id));
        $this->assertTrue($managerClock->get('tasks')->contains('id', $task2->id));
    }

    public function test_admin_can_assign_jobs_and_tasks_in_employee_modal()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee']);

        $job1 = Assignment::create(['name' => 'Commercial Site', 'status' => 'active']);
        $job2 = Assignment::create(['name' => 'Residential Complex', 'status' => 'active']);
        $task1 = Task::create(['name' => 'Plumbing', 'status' => 'active']);
        $task2 = Task::create(['name' => 'HVAC', 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::test(Employees::class)
            ->call('openEditModal', $employee->id)
            ->set('assigned_jobs', [$job1->id])
            ->set('assigned_tasks', [$task2->id])
            ->call('save')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertEquals([$job1->id], $employee->permissions['assigned_jobs']);
        $this->assertEquals([$task2->id], $employee->permissions['assigned_tasks']);

        $assignedJobs = $employee->getAssignedJobs();
        $this->assertCount(1, $assignedJobs);
        $this->assertEquals($job1->id, $assignedJobs->first()->id);

        $assignedTasks = $employee->getAssignedTasks();
        $this->assertCount(1, $assignedTasks);
        $this->assertEquals($task2->id, $assignedTasks->first()->id);
    }
}
