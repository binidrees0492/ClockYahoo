<?php

namespace Tests\Feature;

use App\Livewire\TimeClock;
use App\Livewire\TimeLogDetail;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\ReportDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimezoneTimerAccuracyTest extends TestCase
{
    use RefreshDatabase;

    public function test_timezone_change_does_not_distort_elapsed_work_or_break_durations()
    {
        // 1. Setup company in America/New_York (-04:00 / -05:00)
        CompanySetting::set('timezone', 'America/New_York');

        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 30.00,
            'timezone' => 'America/New_York',
        ]);
        $this->actingAs($employee);

        $job = Assignment::create(['name' => 'Metro Site', 'status' => 'active']);
        $task = Task::create(['name' => 'Electrical Inspection', 'status' => 'active', 'hourly_rate' => 30.00]);

        $employee->update([
            'permissions' => [
                'assigned_jobs'  => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        // 2. Clock in at 05:00 AM New York time (09:00 UTC)
        $t0 = Carbon::parse('2026-10-17 05:00:00', 'America/New_York');
        Carbon::setTestNow($t0);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $log = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log);
        $this->assertEquals('America/New_York', $log->getLogTimezone());

        // 3. Work for 55 minutes (05:00 to 05:55 New York) -> 3300s
        $t1 = Carbon::parse('2026-10-17 05:55:00', 'America/New_York');
        Carbon::setTestNow($t1);
        $this->assertEquals(3300, $log->grossSeconds());
        $this->assertEquals(3300, $log->netSeconds());

        // 4. Start break at 05:55 New York time
        Livewire::test(TimeClock::class)->call('startBreak');
        $log->refresh();
        $this->assertTrue($log->isOnBreak());

        // 5. Advance time by exactly 15 minutes physical time (05:55 to 06:10 New York time)
        // Meanwhile, User changes their timezone to Asia/Karachi (UTC+5, a +9 hours difference)
        $t2 = Carbon::parse('2026-10-17 06:10:00', 'America/New_York');
        Carbon::setTestNow($t2);

        $employee->update(['timezone' => 'Asia/Karachi']);
        CompanySetting::set('timezone', 'Asia/Karachi');

        // Verify current break seconds is 15 minutes (900s), NOT 9 hours + 15 minutes (33300s)!
        $this->assertEquals(900, $log->currentBreakSeconds());

        // 6. End break at this point
        Livewire::test(TimeClock::class)->call('endBreak');
        $log->refresh();
        $this->assertFalse($log->isOnBreak());
        $this->assertEquals(15, $log->break_minutes);

        // 7. Advance time by another 45 minutes of work (physical elapsed total = 100m work + 15m break = 115m gross)
        $t3 = Carbon::parse('2026-10-17 06:55:00', 'America/New_York');
        Carbon::setTestNow($t3);

        $this->assertEquals(6900, $log->grossSeconds()); // 115 min = 6900s
        $this->assertEquals(6000, $log->netSeconds());   // 100 min = 6000s net
        $this->assertEquals(15, $log->break_minutes);

        // 8. Clock out
        Livewire::test(TimeClock::class)->call('clockOut');
        $log->refresh();
        $this->assertNotNull($log->clock_out);
        $this->assertEquals(15, $log->break_minutes);
        $this->assertEquals(6000, $log->netSeconds());

        // 9. Verify ReportDataService extractBreaks calculates 15 minutes accurately
        $reportService = new ReportDataService();
        $breaks = $reportService->extractBreaks($log);
        $this->assertCount(1, $breaks);
        $this->assertEquals(15, $breaks[0]['minutes']);

        // 10. Verify TimeLogDetail modal loads timestamps formatted in the viewer's active timezone (Asia/Karachi)
        $detailComponent = Livewire::test(TimeLogDetail::class)
            ->call('open', $log->id);

        $loadedLog = $detailComponent->get('log');
        $this->assertNotNull($loadedLog);
        $this->assertEquals(15, $loadedLog['break_minutes']);
        // 05:00 NY = 09:00 UTC = 14:00 (2:00 PM) Karachi
        $this->assertStringContainsString('2:00 PM', $loadedLog['clock_in']);
    }
}
