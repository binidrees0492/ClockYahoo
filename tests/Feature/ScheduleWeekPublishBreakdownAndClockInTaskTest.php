<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBoard;
use App\Livewire\TimeClock;
use App\Livewire\Timesheets;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleWeekPublishBreakdownAndClockInTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_week_publishes_all_draft_shifts_for_current_week(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Electrical', 'assignment_id' => $job->id, 'status' => 'active']);

        $startOfWeek = Carbon::now()->startOfWeek(CompanySetting::getWeekStartDay());
        $dates = [
            $startOfWeek->copy()->addDays(1)->toDateString(),
            $startOfWeek->copy()->addDays(2)->toDateString(),
            $startOfWeek->copy()->addDays(3)->toDateString(),
        ];

        foreach ($dates as $date) {
            Schedule::create([
                'employee_id'    => $employee->id,
                'assignment_id'  => $job->id,
                'task_id'        => $task->id,
                'shift_date'     => $date,
                'start_time'     => '08:00',
                'end_time'       => '16:00',
                'standard_hours' => 8.00,
                'status'         => 'draft',
                'is_published'   => false,
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->call('publishWeek')
            ->assertDispatched('toast');

        $shifts = Schedule::where('employee_id', $employee->id)->get();
        $this->assertCount(3, $shifts);
        foreach ($shifts as $shift) {
            $this->assertEquals('published', $shift->status);
            $this->assertTrue((bool) $shift->is_published);
            $this->assertTrue($shift->isPublished());
        }
    }

    public function test_revert_week_to_draft_reverts_all_published_shifts_for_current_week(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Electrical', 'assignment_id' => $job->id, 'status' => 'active']);

        $startOfWeek = Carbon::now()->startOfWeek(CompanySetting::getWeekStartDay());
        $dates = [
            $startOfWeek->copy()->addDays(1)->toDateString(),
            $startOfWeek->copy()->addDays(2)->toDateString(),
        ];

        foreach ($dates as $date) {
            Schedule::create([
                'employee_id'    => $employee->id,
                'assignment_id'  => $job->id,
                'task_id'        => $task->id,
                'shift_date'     => $date,
                'start_time'     => '08:00',
                'end_time'       => '16:00',
                'standard_hours' => 8.00,
                'status'         => 'published',
                'is_published'   => true,
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->call('revertWeekToDraft')
            ->assertDispatched('toast');

        $shifts = Schedule::where('employee_id', $employee->id)->get();
        $this->assertCount(2, $shifts);
        foreach ($shifts as $shift) {
            $this->assertEquals('draft', $shift->status);
            $this->assertFalse((bool) $shift->is_published);
            $this->assertTrue($shift->isDraft());
        }
    }

    public function test_revert_individual_shift_to_draft(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Beta', 'status' => 'active']);
        $task = Task::create(['name' => 'Plumbing', 'assignment_id' => $job->id, 'status' => 'active']);

        $shift = Schedule::create([
            'employee_id'    => $employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => Carbon::now()->toDateString(),
            'start_time'     => '09:00',
            'end_time'       => '17:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->call('revertShiftToDraft', $shift->id)
            ->assertDispatched('toast');

        $shift->refresh();
        $this->assertEquals('draft', $shift->status);
        $this->assertFalse((bool) $shift->is_published);
        $this->assertTrue($shift->isDraft());
    }

    public function test_bulk_add_shifts_with_day_breakdown_and_break_deductions(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'HQ Build', 'status' => 'active']);
        $task = Task::create(['name' => 'Framing', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        $component = Livewire::test(ScheduleBoard::class);

        $component->call('openAddShift', $employee->id, '2026-11-10', $job->id, $task->id)
            ->set('shiftDateFrom', '2026-11-10')
            ->set('shiftDateTo', '2026-11-12')
            ->set('shiftEmployeeId', $employee->id)
            ->set('shiftAssignmentId', $job->id)
            ->set('shiftTaskId', $task->id)
            ->set('shiftStart', '08:00')
            ->set('shiftEnd', '16:00')
            ->set('shiftStandardHours', 8.00);

        // Verify 3 breakdown rows
        $breakdown = $component->get('shiftDaysBreakdown');
        $this->assertCount(3, $breakdown);

        // Turn on break for index 0 and set custom break times
        $component->call('toggleShiftDayBreak', 0)
            ->set('shiftDaysBreakdown.0.break_start', '12:00')
            ->set('shiftDaysBreakdown.0.break_end', '12:45');

        // Toggle off index 2 (2026-11-12)
        $component->call('toggleShiftDayEnabled', 2);

        // Save as draft
        $component->call('saveShift', false)
            ->assertDispatched('toast');

        // 2 shifts should be created: 2026-11-10 and 2026-11-11
        $created = Schedule::where('employee_id', $employee->id)->orderBy('shift_date')->get();
        $this->assertCount(2, $created);

        // Day 1 (2026-11-10) has break
        $shift1 = $created->first();
        $this->assertEquals('2026-11-10', $shift1->shift_date->toDateString());
        $this->assertEquals('12:00', substr($shift1->break_start, 0, 5));
        $this->assertEquals('12:45', substr($shift1->break_end, 0, 5));
        $this->assertEquals(45, $shift1->break_minutes);

        // Day 2 (2026-11-11) has no break
        $shift2 = $created->last();
        $this->assertEquals('2026-11-11', $shift2->shift_date->toDateString());
        $this->assertNull($shift2->break_start);

        // Day 3 (2026-11-12) excluded
        $this->assertDatabaseMissing('schedules', [
            'employee_id' => $employee->id,
            'shift_date'  => '2026-11-12',
        ]);
    }

    public function test_clock_in_strictly_fails_without_task(): void
    {
        $job = Assignment::create(['name' => 'Site Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Inspection', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'assigned_jobs' => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        $this->actingAs($employee);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', null)
            ->call('clockIn')
            ->assertHasErrors(['selectedTask' => 'A job cannot be started unless a task is selected too.']);

        $this->assertDatabaseCount('tbl_time_logging', 0);
    }

    public function test_clock_in_fails_if_task_does_not_belong_to_job(): void
    {
        $jobA = Assignment::create(['name' => 'Site Alpha', 'status' => 'active']);
        $jobB = Assignment::create(['name' => 'Site Beta', 'status' => 'active']);
        $taskB = Task::create(['name' => 'Concrete Pouring', 'assignment_id' => $jobB->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'assigned_jobs' => [$jobA->id, $jobB->id],
                'assigned_tasks' => [$taskB->id],
            ],
        ]);

        $this->actingAs($employee);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobA->id)
            ->set('selectedTask', $taskB->id)
            ->call('clockIn')
            ->assertHasErrors(['selectedTask' => 'The selected task does not belong to this job.']);

        $this->assertDatabaseCount('tbl_time_logging', 0);
    }

    public function test_confirm_switch_strictly_fails_without_task(): void
    {
        $jobA = Assignment::create(['name' => 'Site Alpha', 'status' => 'active']);
        $taskA = Task::create(['name' => 'Framing', 'assignment_id' => $jobA->id, 'status' => 'active']);

        $jobB = Assignment::create(['name' => 'Site Beta', 'status' => 'active']);
        $taskB = Task::create(['name' => 'Finishing', 'assignment_id' => $jobB->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'assigned_jobs' => [$jobA->id, $jobB->id],
                'assigned_tasks' => [$taskA->id, $taskB->id],
            ],
        ]);

        // Start initial clock in
        TimeLog::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $jobA->id,
            'task_id'       => $taskA->id,
            'clock_in'      => Carbon::now()->subHour(),
        ]);

        $this->actingAs($employee);

        Livewire::test(TimeClock::class)
            ->call('openSwitchModal')
            ->set('switchAssignment', $jobB->id)
            ->set('switchTask', null)
            ->call('confirmSwitch')
            ->assertHasErrors(['switchTask' => 'A job cannot be started unless a task is selected too.']);
    }

    public function test_employee_cannot_log_time_on_unscheduled_days_or_outside_shift_hours(): void
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Gamma', 'status' => 'active']);
        $task = Task::create(['name' => 'Masonry', 'assignment_id' => $job->id, 'status' => 'active']);

        // Schedule only on 2026-11-25 (Wednesday) from 09:00 to 17:00
        Schedule::create([
            'employee_id'    => $employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => '2026-11-25',
            'start_time'     => '09:00',
            'end_time'       => '17:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        $this->actingAs($employee);

        // 1. Dropdown options on scheduled date (2026-11-25) contain Job & Task
        $tsComponent = Livewire::test(Timesheets::class)
            ->set('addStartDate', '2026-11-25')
            ->set('addEndDate', '2026-11-25');

        $assignmentsOnWed = $tsComponent->get('modalAssignments');
        $this->assertTrue($assignmentsOnWed->contains('id', $job->id));

        // 2. Dropdown options on unscheduled date (2026-11-24) do NOT contain Job
        $tsComponent->set('addStartDate', '2026-11-24')
            ->set('addEndDate', '2026-11-24');

        $assignmentsOnTue = $tsComponent->get('modalAssignments');
        $this->assertFalse($assignmentsOnTue->contains('id', $job->id));

        // 3. Multi-day breakdown defaults only the scheduled day (Wednesday) to enabled
        $tsComponent->set('addStartDate', '2026-11-23')
            ->set('addEndDate', '2026-11-27');

        $breakdown = $tsComponent->get('addDaysBreakdown');
        foreach ($breakdown as $row) {
            if ($row['date'] === '2026-11-25') {
                $this->assertTrue($row['enabled'], "Wednesday 2026-11-25 should be enabled");
                $this->assertEquals(8.00, (float)$row['hours']);
            } else {
                $this->assertFalse($row['enabled'], "Day {$row['date']} should be disabled because no shift exists");
            }
        }

        // 4. Attempting to log single manual time on unscheduled date (2026-11-24) fails
        Livewire::test(Timesheets::class)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addNotes', 'Worked unscheduled day')
            ->set('addDaysBreakdown', [])
            ->set('addClockIn', '2026-11-24T09:00')
            ->set('addClockOut', '2026-11-24T17:00')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'You do not have a scheduled shift for 2026-11-24. You can only log manual time for dates and times where a shift was assigned to you.', type: 'error');

        // 5. Attempting to log manual time on scheduled date outside shift hours (e.g. 19:00 - 22:00) fails
        Livewire::test(Timesheets::class)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addNotes', 'Worked overtime shift')
            ->set('addDaysBreakdown', [])
            ->set('addClockIn', '2026-11-25T19:00')
            ->set('addClockOut', '2026-11-25T22:00')
            ->call('saveAdd')
            ->assertDispatched('toast', type: 'error');

        // 6. Logging manual time within scheduled shift hours (09:00 - 17:00) succeeds
        Livewire::test(Timesheets::class)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addNotes', 'Regular shift completed')
            ->set('addDaysBreakdown', [])
            ->set('addClockIn', '2026-11-25T09:00')
            ->set('addClockOut', '2026-11-25T17:00')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'Manual time entry added. Submitted for approval.');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'is_manual'     => 1,
        ]);
    }

    public function test_time_clock_blocks_clock_in_outside_scheduled_shift_hours(): void
    {
        $employee = User::factory()->create([
            'role'        => 'Employee',
            'permissions' => ['allow_web_clock' => true],
        ]);
        $job = Assignment::create(['name' => 'Project Delta', 'status' => 'active']);
        $task = Task::create(['name' => 'Painting', 'assignment_id' => $job->id, 'status' => 'active']);

        $todayStr = Carbon::today()->toDateString();

        Schedule::create([
            'employee_id'    => $employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => $todayStr,
            'start_time'     => '09:00',
            'end_time'       => '17:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        $this->actingAs($employee);

        // Attempting to clock in at 06:00 (too early) is rejected
        Carbon::setTestNow(Carbon::today()->setTime(6, 0));

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'Your scheduled shift is from 9:00 AM to 5:00 PM. You can only clock in during your scheduled shift date and time.', type: 'error');

        // Clock in at 10:00 (during shift) succeeds
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'Clocked in successfully.');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_out'     => null,
        ]);

        Carbon::setTestNow();
    }
}
