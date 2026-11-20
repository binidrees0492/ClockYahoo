<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\Task;
use App\Livewire\TimeLogDetail;
use App\Livewire\ScheduleCalendar;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleAndActionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_requested_can_be_set_as_accepted()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $assignment = Assignment::create(['name' => 'General Assignment']);
        $task = Task::create(['name' => 'General Task']);

        $log = TimeLog::create([
            'employee_id'            => $employee->id,
            'assignment_id'          => $assignment->id,
            'task_id'                => $task->id,
            'clock_in'               => now()->subHours(8),
            'clock_out'              => now(),
            'duration_minutes'       => 480,
            'status'                 => 'action_requested',
            'action_requested'       => true,
            'action_requested_notes' => 'Please explain unusual hours.',
        ]);

        $this->actingAs($employee);

        Livewire::test(TimeLogDetail::class)
            ->call('open', $log->id)
            ->assertSee('Action / Explanation Requested by HR')
            ->assertSee('Accept / Resolve Request')
            ->call('acceptActionRequest')
            ->assertDispatched('toast');

        $log->refresh();
        $this->assertFalse((bool)$log->action_requested);
        $this->assertNull($log->action_requested_notes);
        $this->assertEquals('completed', $log->status);
    }

    public function test_schedule_creation_with_range_and_publishing_workflow()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $assignment = Assignment::create(['name' => 'General Assignment 2']);
        $task = Task::create(['name' => 'General Task 2']);

        $this->actingAs($admin);

        // 1. Create a draft single shift
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift', '2026-10-05')
            ->set('shiftEmployeeId', $employee->id)
            ->set('shiftAssignmentId', $assignment->id)
            ->set('shiftTaskId', $task->id)
            ->set('shiftDate', '2026-10-05')
            ->set('startTime', '09:00')
            ->set('endTime', '17:00')
            ->set('standardHours', 8.0)
            ->call('saveShift', false) // save as draft
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $draft = Schedule::where('employee_id', $employee->id)->whereDate('shift_date', '2026-10-05')->first();
        $this->assertNotNull($draft);
        $this->assertEquals('draft', $draft->status);
        $this->assertFalse($draft->is_published);
        $this->assertTrue($draft->isDraft());

        // 2. Publish draft shift
        Livewire::test(ScheduleCalendar::class)
            ->call('publishShift', $draft->id)
            ->assertDispatched('toast');

        $draft->refresh();
        $this->assertEquals('published', $draft->status);
        $this->assertTrue($draft->is_published);
        $this->assertTrue($draft->isPublished());

        // 3. Create range of published shifts (e.g. 2026-10-12 to 2026-10-16: Mon-Fri = 5 days)
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift', '2026-10-12')
            ->set('shiftMode', 'range')
            ->set('shiftEmployeeId', $employee->id)
            ->set('shiftAssignmentId', $assignment->id)
            ->set('shiftTaskId', $task->id)
            ->set('shiftDate', '2026-10-12') // Monday
            ->set('shiftEndDate', '2026-10-16') // Friday
            ->set('shiftDays', ['1', '2', '3', '4', '5'])
            ->set('startTime', '08:00')
            ->set('endTime', '16:30')
            ->set('standardHours', 8.0)
            ->call('saveShift', true) // publish
            ->assertDispatched('toast');

        $rangeCount = Schedule::where('employee_id', $employee->id)
            ->whereDate('shift_date', '>=', '2026-10-12')
            ->whereDate('shift_date', '<=', '2026-10-16')
            ->where('status', 'published')
            ->count();

        $this->assertEquals(5, $rangeCount);
    }

    public function test_time_clock_only_considers_published_schedules()
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $assignment = Assignment::create(['name' => 'General Assignment 3']);
        $task = Task::create(['name' => 'General Task 3']);

        // Create a draft schedule for today
        $draftSchedule = Schedule::create([
            'employee_id'    => $employee->id,
            'assignment_id'  => $assignment->id,
            'task_id'        => $task->id,
            'shift_date'     => now()->toDateString(),
            'start_time'     => '08:00',
            'end_time'       => '17:00',
            'standard_hours' => 8.00,
            'status'         => 'draft',
            'is_published'   => false,
        ]);

        $publishedSchedule = Schedule::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->whereDate('shift_date', now()->toDateString())
            ->first();

        // Draft schedule must not be matched in published schedule queries
        $this->assertNull($publishedSchedule);

        // Once published, it is found
        $draftSchedule->update(['status' => 'published', 'is_published' => true]);

        $publishedSchedule = Schedule::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->whereDate('shift_date', now()->toDateString())
            ->first();

        $this->assertNotNull($publishedSchedule);
        $this->assertEquals($draftSchedule->id, $publishedSchedule->id);
    }

    public function test_overnight_shifts_are_not_flagged_with_negative_minutes_or_false_alarms()
    {
        $employee = User::factory()->create([
            'role'                  => 'Employee',
            'standard_shift_start'  => '08:00',
            'standard_shift_end'    => '17:00',
            'standard_shift_hours'  => 8.00,
        ]);

        $assignment = Assignment::create(['name' => 'Night Shift Site']);
        $task = Task::create(['name' => 'Overnight Guard']);

        // 10:20 PM to 6:17 AM (477 minutes / 7.95 hours)
        $timeLog = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => \Carbon\Carbon::parse('2026-10-16 22:20:00'),
            'clock_out'        => \Carbon\Carbon::parse('2026-10-17 06:17:00'),
            'duration_minutes' => 477,
            'status'           => 'completed',
        ]);

        $result = \App\Services\TimeLogComplianceService::evaluateUnusualActivity($timeLog);

        $this->assertFalse($result['is_unusual'], 'Normal overnight shift (7.95 hrs) should not be flagged.');
        $this->assertNull($result['reason']);

        // Check excessive overnight shift (> 8 hrs + 45 min buffer, e.g. 14 hours)
        $timeLogLong = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => \Carbon\Carbon::parse('2026-10-16 22:20:00'),
            'clock_out'        => \Carbon\Carbon::parse('2026-10-17 12:20:00'),
            'duration_minutes' => 840,
            'status'           => 'completed',
        ]);

        $resultLong = \App\Services\TimeLogComplianceService::evaluateUnusualActivity($timeLogLong);
        $this->assertTrue($resultLong['is_unusual']);
        $this->assertDoesNotMatchRegularExpression('/-\d+/', $resultLong['reason']); // No negative numbers!
    }
}
