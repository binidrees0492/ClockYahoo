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

class BreakTimeExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_break_time_is_logged_inside_shift_and_excluded_from_worked_hours()
    {
        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 30.00,
        ]);
        $this->actingAs($employee);

        $assignment = Assignment::create(['name' => 'Field Project', 'status' => 'active']);
        $task = Task::create(['name' => 'Inspection', 'status' => 'active', 'hourly_rate' => 30.00]);
        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$assignment->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        // 1. Clock in at 08:00
        $t0 = Carbon::today()->setTime(8, 0, 0);
        Carbon::setTestNow($t0);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $assignment->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $log = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log);
        $this->assertEquals(0, $log->break_minutes);
        $this->assertFalse($log->isOnBreak());

        // 2. Work for 2 hours (08:00 to 10:00)
        $t1 = Carbon::today()->setTime(10, 0, 0);
        Carbon::setTestNow($t1);

        $this->assertEquals(2 * 3600, $log->netSeconds());

        // 3. Start a break at 10:00
        Livewire::test(TimeClock::class)
            ->call('startBreak');

        $log->refresh();
        $this->assertTrue($log->isOnBreak());
        $this->assertEquals('on_break', $log->status);

        // 4. Advance time by 30 minutes (10:00 to 10:30) while on break
        $t2 = Carbon::today()->setTime(10, 30, 0);
        Carbon::setTestNow($t2);

        // Net seconds (worked time) MUST freeze during the break at exactly 2 hours (7200s)!
        $this->assertEquals(2 * 3600, $log->netSeconds());
        $this->assertEquals(30 * 60, $log->currentBreakSeconds());

        // 5. End break at 10:30
        Livewire::test(TimeClock::class)
            ->call('endBreak');

        $log->refresh();
        $this->assertFalse($log->isOnBreak());
        $this->assertEquals('active', $log->status);
        $this->assertEquals(30, $log->break_minutes);
        $this->assertIsArray($log->breaks);
        $this->assertCount(1, $log->breaks);
        $this->assertEquals(30, $log->breaks[0]['minutes']);

        // Verify NO separate fake 'break' row was created
        $breakLogsCount = TimeLog::where('status', 'break')->count();
        $this->assertEquals(0, $breakLogsCount);
        $this->assertEquals(1, TimeLog::count());

        // 6. Work for another 1.5 hours (10:30 to 12:00) and clock out
        $t3 = Carbon::today()->setTime(12, 0, 0);
        Carbon::setTestNow($t3);

        Livewire::test(TimeClock::class)
            ->call('clockOut');

        $log->refresh();
        $this->assertNotNull($log->clock_out);
        $this->assertEquals('completed', $log->status);

        // Gross time elapsed = 4 hours (240 min).
        // Break time = 30 min.
        // Net worked duration = 210 min (3.5 hours).
        $this->assertEquals(210, $log->duration_minutes);
        $this->assertEquals(30, $log->break_minutes);

        // Payable amount for 3.5 hrs @ $30/hr = $105.00 (NOT 4 hrs * $30 = $120)
        $this->assertEquals(105.00, $log->getPayableAmount());

        Carbon::setTestNow(); // Reset Mockery
    }
}
