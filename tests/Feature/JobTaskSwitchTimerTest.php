<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Livewire\TimeClock;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobTaskSwitchTimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_job_or_task_does_not_reset_timers_and_logs_both_activities()
    {
        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 25.00,
        ]);
        $this->actingAs($employee);

        $jobA = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $jobB = Assignment::create(['name' => 'Project Beta', 'status' => 'active']);

        $task1 = Task::create(['name' => 'Framing', 'status' => 'active', 'hourly_rate' => 25.00]);
        $task2 = Task::create(['name' => 'Drywall', 'status' => 'active', 'hourly_rate' => 30.00]);

        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$jobA->id, $jobB->id],
                'assigned_tasks' => [$task1->id, $task2->id],
            ],
        ]);

        // 1. Clock in at 08:00 to Job A / Task 1
        $t0 = Carbon::today()->setTime(8, 0, 0);
        Carbon::setTestNow($t0);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobA->id)
            ->set('selectedTask', $task1->id)
            ->call('clockIn');

        $log1 = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log1);
        $this->assertNotEmpty($log1->shift_id);
        $this->assertEquals($jobA->id, $log1->assignment_id);
        $this->assertEquals($task1->id, $log1->task_id);

        // 2. Work on Job A / Task 1 for 1 hour (08:00 to 09:00)
        $t1 = Carbon::today()->setTime(9, 0, 0);
        Carbon::setTestNow($t1);

        $clockComponent = Livewire::test(TimeClock::class);
        $this->assertEquals('01', $clockComponent->get('hours'));
        $this->assertEquals('00', $clockComponent->get('minutes'));
        $this->assertEquals('00', $clockComponent->get('seconds'));

        // 3. Switch to Job B / Task 2 at 09:00
        $clockComponent->set('switchAssignment', $jobB->id)
            ->set('switchTask', $task2->id)
            ->call('confirmSwitch');

        // Both activities must be logged in database
        $this->assertEquals(2, TimeLog::where('employee_id', $employee->id)->count());

        $log1->refresh();
        $this->assertEquals('switched', $log1->status);
        $this->assertNotNull($log1->clock_out);
        $this->assertEquals(60, $log1->duration_minutes); // 1 hr logged for Job A / Task 1

        $log2 = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log2);
        $this->assertEquals($log1->shift_id, $log2->shift_id, 'Both logs must share the same shift sequence ID');
        $this->assertEquals($log1->id, $log2->parent_log_id, 'Log 2 must link to Log 1 as parent');
        $this->assertEquals($jobB->id, $log2->assignment_id);
        $this->assertEquals($task2->id, $log2->task_id);

        // 4. Timer MUST NOT reset to 00:00:00! At 09:00, timer continues at 01:00:00
        $clockAfterSwitch = Livewire::test(TimeClock::class);
        $this->assertEquals('01', $clockAfterSwitch->get('hours'));
        $this->assertEquals('00', $clockAfterSwitch->get('minutes'));
        $this->assertEquals('00', $clockAfterSwitch->get('seconds'));
        $this->assertEquals(3600, $clockAfterSwitch->get('shiftPriorNetSeconds'));
        $this->assertTrue($clockAfterSwitch->get('hasSwitched'));

        // 5. Work another 45 minutes on Job B / Task 2 (09:00 to 09:45)
        $t2 = Carbon::today()->setTime(9, 45, 0);
        Carbon::setTestNow($t2);

        $clockAt945 = Livewire::test(TimeClock::class);
        // Total timer continues: 1 hour (from Job A) + 45 minutes (Job B) = 01:45:00
        $this->assertEquals('01', $clockAt945->get('hours'));
        $this->assertEquals('45', $clockAt945->get('minutes'));
        $this->assertEquals('00', $clockAt945->get('seconds'));

        // 6. Switch again to Job B / Task 1 at 09:45
        $clockAt945->set('switchAssignment', $jobB->id)
            ->set('switchTask', $task1->id)
            ->call('confirmSwitch');

        // Now 3 activities logged under the same shift
        $this->assertEquals(3, TimeLog::where('employee_id', $employee->id)->count());
        $log2->refresh();
        $this->assertEquals('switched', $log2->status);
        $this->assertEquals(45, $log2->duration_minutes); // 45 min logged for Job B / Task 2

        $log3 = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log3);
        $this->assertEquals($log1->shift_id, $log3->shift_id);
        $this->assertEquals($log2->id, $log3->parent_log_id);

        // 7. Work 15 minutes on Job B / Task 1 (09:45 to 10:00) and clock out
        $t3 = Carbon::today()->setTime(10, 0, 0);
        Carbon::setTestNow($t3);

        $clockAt10 = Livewire::test(TimeClock::class);
        // Total shift timer is 2 hours: 1 hr (Log 1) + 45 min (Log 2) + 15 min (Log 3)
        $this->assertEquals('02', $clockAt10->get('hours'));
        $this->assertEquals('00', $clockAt10->get('minutes'));
        $this->assertEquals('00', $clockAt10->get('seconds'));

        // Clock out
        $clockAt10->call('clockOut');

        $log3->refresh();
        $this->assertEquals('completed', $log3->status);
        $this->assertEquals(15, $log3->duration_minutes);

        // Verify each distinct task/job activity was logged with its own accurate duration
        $allLogs = TimeLog::where('employee_id', $employee->id)->orderBy('clock_in')->get();
        $this->assertCount(3, $allLogs);
        $this->assertEquals(60, $allLogs[0]->duration_minutes); // Job A / Task 1
        $this->assertEquals(45, $allLogs[1]->duration_minutes); // Job B / Task 2
        $this->assertEquals(15, $allLogs[2]->duration_minutes); // Job B / Task 1

        $totalShiftMinutes = $allLogs->sum('duration_minutes');
        $this->assertEquals(120, $totalShiftMinutes, 'Total logged minutes across the entire shift must equal 2 hours (120m)');

        Carbon::setTestNow();
    }
}
