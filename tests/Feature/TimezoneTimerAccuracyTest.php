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

    public function test_events_and_timestamps_are_stored_in_canonical_utc_and_displayed_in_user_timezone()
    {
        // 1. Setup company and user in Asia/Karachi (+05:00)
        CompanySetting::set('server_timezone', 'UTC');

        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 25.00,
            'timezone' => 'Asia/Karachi',
        ]);
        $this->actingAs($employee);

        $job = Assignment::create(['name' => 'Alpha Tower', 'status' => 'active']);
        $task = Task::create(['name' => 'Diagnostics', 'status' => 'active', 'hourly_rate' => 25.00]);

        $employee->update([
            'permissions' => [
                'assigned_jobs'  => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        // 2. Set current time to 15:00:00 Asia/Karachi (which is 10:00:00 UTC)
        $tLocal = Carbon::parse('2026-10-20 15:00:00', 'Asia/Karachi');
        Carbon::setTestNow($tLocal);

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $log = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log);

        // 3. Raw database timestamp MUST be stored in canonical UTC (10:00:00)
        $rawIn = \Illuminate\Support\Facades\DB::table('tbl_time_logging')->where('id', $log->id)->value('clock_in');
        $this->assertStringContainsString('10:00:00', $rawIn);

        // 4. TimeLog Eloquent helper returns correct local formatted time for Asia/Karachi (3:00 PM)
        $this->assertEquals('3:00 PM', $log->formatIn('Asia/Karachi', 'g:i A'));
        // And for an admin viewing from America/New_York (06:00 AM)
        $this->assertEquals('6:00 AM', $log->formatIn('America/New_York', 'g:i A'));

        // 5. Advance time by 45 minutes physical time (10:45:00 UTC)
        $tLocal2 = Carbon::parse('2026-10-20 15:45:00', 'Asia/Karachi');
        Carbon::setTestNow($tLocal2);

        // Elapsed duration is counted directly from UTC timestamps
        $this->assertEquals(2700, $log->grossSeconds());
        $this->assertEquals(2700, $log->netSeconds());

        // 6. Clock out
        Livewire::test(TimeClock::class)->call('clockOut');
        $log->refresh();

        $rawOut = \Illuminate\Support\Facades\DB::table('tbl_time_logging')->where('id', $log->id)->value('clock_out');
        $this->assertStringContainsString('10:45:00', $rawOut);
        $this->assertEquals('3:45 PM', $log->formatOut('Asia/Karachi', 'g:i A'));
    }

    public function test_schedule_time_locking_respects_schedule_and_user_timezone()
    {
        CompanySetting::set('server_timezone', 'UTC');
        CompanySetting::set('require_published_schedule', true);

        // Employee operates in America/New_York (-04:00)
        $employee = User::factory()->create([
            'role'     => 'Employee',
            'timezone' => 'America/New_York',
        ]);
        $this->actingAs($employee);

        $job = Assignment::create(['name' => 'Downtown Project', 'status' => 'active']);
        $task = Task::create(['name' => 'Site Audit', 'status' => 'active', 'hourly_rate' => 40.00]);

        $employee->update([
            'permissions' => [
                'assigned_jobs'  => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        // Scheduled shift: 2026-10-25 from 09:00:00 to 17:00:00 (New York time = 13:00:00 to 21:00:00 UTC)
        $schedule = \App\Models\Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => '2026-10-25',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'status'        => 'published',
            'is_published'  => true,
        ]);

        // Case A: Employee attempts to clock in at 07:00:00 NY time (11:00:00 UTC)
        // Outside 30 min grace period -> Should be locked!
        $tooEarly = Carbon::parse('2026-10-25 07:00:00', 'America/New_York');
        Carbon::setTestNow($tooEarly);

        $this->assertFalse($schedule->coversTime(Carbon::now('UTC'), 30, 30));

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $this->assertDatabaseMissing('tbl_time_logging', ['employee_id' => $employee->id]);

        // Case B: Employee attempts to clock in at 08:35:00 NY time (12:35:00 UTC)
        // Within 30 min grace period before shift -> Should be allowed!
        $withinGrace = Carbon::parse('2026-10-25 08:35:00', 'America/New_York');
        Carbon::setTestNow($withinGrace);

        $this->assertTrue($schedule->coversTime(Carbon::now('UTC'), 30, 30));

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
        ]);

        $log = TimeLog::where('employee_id', $employee->id)->first();
        // Clock in in UTC should be 12:35:00
        $this->assertEquals('12:35:00', $log->clock_in->toTimeString());
        // Clock in in NY should be 08:35:00
        $this->assertEquals('08:35:00', $log->getClockInInTimezone('America/New_York')->toTimeString());
    }
}

