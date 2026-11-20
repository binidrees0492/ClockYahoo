<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\Task;
use App\Livewire\Timesheets;
use App\Livewire\TimeLogDetail;
use App\Livewire\TimesheetScheduleTabs;
use App\Livewire\TimeClock;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TimesheetScopeAndRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_hourly_rate_defaults_from_employee_info_and_applied_to_timelog()
    {
        $employee = User::factory()->create([
            'role'     => 'Employee',
            'base_pay' => 25.50,
        ]);

        $task = Task::create([
            'name'        => 'Framing',
            'hourly_rate' => null, // not overridden, should default to employee's base_pay
        ]);

        $this->assertEquals(25.50, $task->getHourlyRateFor($employee));

        $assignment = Assignment::create(['name' => 'Downtown Job']);
        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$assignment->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        // Clock in through TimeClock
        $this->actingAs($employee);
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $assignment->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $log = TimeLog::where('employee_id', $employee->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(25.50, (float)$log->hourly_rate);
    }

    public function test_approved_or_rejected_time_log_cannot_be_deleted()
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $assignment = Assignment::create(['name' => 'Site A']);
        $task = Task::create(['name' => 'Painting']);

        // Approved log
        $approvedLog = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => now()->subHours(5),
            'clock_out'        => now(),
            'duration_minutes' => 300,
            'status'           => 'completed',
            'approved'         => true,
        ]);

        // Rejected log
        $rejectedLog = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => now()->subHours(3),
            'clock_out'        => now(),
            'duration_minutes' => 180,
            'status'           => 'rejected',
            'approved'         => false,
        ]);

        $this->actingAs($employee);

        // Attempt deletion via Timesheets component
        Livewire::test(Timesheets::class)
            ->call('deleteLog', $approvedLog->id)
            ->assertDispatched('toast', message: 'Approved or rejected entries cannot be deleted.');

        $this->assertDatabaseHas('tbl_time_logging', ['id' => $approvedLog->id]);

        // Attempt deletion via TimeLogDetail component
        Livewire::test(TimeLogDetail::class)
            ->call('open', $rejectedLog->id)
            ->call('deleteLog')
            ->assertDispatched('toast', message: 'Approved, withdrawn, or rejected entries are locked and cannot be deleted.');

        $this->assertDatabaseHas('tbl_time_logging', ['id' => $rejectedLog->id]);
    }

    public function test_schedule_day_navigation_event_switches_to_timesheet_and_target_week()
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $this->actingAs($employee);

        $nextWeekDate = Carbon::now()->addWeeks(2)->toDateString();

        // TimesheetScheduleTabs handles navigate-to-timesheet
        Livewire::test(TimesheetScheduleTabs::class)
            ->set('tab', 'schedule')
            ->dispatch('navigate-to-timesheet', date: $nextWeekDate, employeeId: $employee->id)
            ->assertSet('tab', 'timesheet');

        // Timesheets component shifts weekOffset
        Livewire::test(Timesheets::class)
            ->dispatch('navigate-to-timesheet', date: $nextWeekDate, employeeId: $employee->id)
            ->assertSet('weekOffset', 2);
    }

    public function test_hr_can_see_all_and_filter_stats_while_department_manager_only_sees_department()
    {
        $dept1Id = 1;
        $dept2Id = 2;

        $hr = User::factory()->create(['role' => 'HR Manager', 'department_id' => $dept1Id]);
        $manager = User::factory()->create([
            'role'                => 'Manager',
            'department_id'       => $dept1Id,
            'managed_team_type'   => 'restricted',
            'managed_employees'   => [['type' => 'dept', 'id' => $dept1Id]],
        ]);

        $emp1 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept1Id, 'name' => 'Alice']);
        $emp2 = User::factory()->create(['role' => 'Employee', 'department_id' => $dept2Id, 'name' => 'Bob']);

        $assignment = Assignment::create(['name' => 'Project Alpha']);
        $task = Task::create(['name' => 'Development']);

        $weekStart = Carbon::now()->startOfWeek(\App\Models\CompanySetting::getWeekStartDay());

        // Log for Alice (Dept 1)
        TimeLog::create([
            'employee_id'      => $emp1->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => $weekStart->copy()->addHours(9),
            'clock_out'        => $weekStart->copy()->addHours(17),
            'duration_minutes' => 480,
            'status'           => 'completed',
            'approved'         => false,
        ]);

        // Log for Bob (Dept 2)
        TimeLog::create([
            'employee_id'      => $emp2->id,
            'assignment_id'    => $assignment->id,
            'task_id'          => $task->id,
            'clock_in'         => $weekStart->copy()->addHours(10),
            'clock_out'        => $weekStart->copy()->addHours(16),
            'duration_minutes' => 360,
            'status'           => 'completed',
            'approved'         => false,
        ]);

        // 1. HR Manager can filter stats by specific employee or view all
        $this->actingAs($hr);
        Livewire::test(TimesheetScheduleTabs::class)
            ->call('setEmployee', $emp1->id)
            ->assertSee('8.0h') // 480 mins
            ->call('setEmployee', $emp2->id)
            ->assertSee('6.0h') // 360 mins
            ->call('setEmployee', 'all')
            ->assertSee('14.00'); // total for all (toolbar shows "14.00 hrs")

        // 2. Department Manager can only see department 1 (Alice), cannot see Bob
        $this->actingAs($manager);
        $accessible = $manager->getAccessibleEmployeeIds();
        $this->assertContains($emp1->id, $accessible);
        $this->assertNotContains($emp2->id, $accessible);

        Livewire::test(TimesheetScheduleTabs::class)
            ->call('setEmployee', 'all')
            ->assertSee('8.0h') // Only Alice's 8 hours, Bob's hours excluded
            ->assertDontSee('Bob');
    }

    public function test_timesheets_summary_rows_show_regular_time_pto_and_subtotal_totals()
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $this->actingAs($employee);

        // 1. When empty, summary rows still show 0:00 totals
        Livewire::test(Timesheets::class)
            ->assertSee('REGULAR TIME')
            ->assertSee('PAID TIME OFF')
            ->assertSee('SUBTOTAL')
            ->assertSee('0:00');

        // 2. With logged hours and approved time off
        $startOfWeek = Carbon::now()->startOfWeek(\App\Models\CompanySetting::getWeekStartDay());
        $job = Assignment::create(['name' => 'HQ Build']);
        $task = Task::create(['name' => 'Design']);

        TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => $startOfWeek->copy()->addDays(1)->setTime(9, 0),
            'clock_out'        => $startOfWeek->copy()->addDays(1)->setTime(17, 30),
            'duration_minutes' => 510, // 8h 30m
            'status'           => 'completed',
        ]);

        $policy = \App\Models\TimeOffPolicy::create([
            'name'   => 'Vacation Leave',
            'type'   => 'paid',
            'active' => true,
        ]);

        \App\Models\TimeOffRequest::create([
            'employee_id'     => $employee->id,
            'policy_id'       => $policy->id,
            'start_at'        => $startOfWeek->copy()->addDays(2)->startOfDay(),
            'end_at'          => $startOfWeek->copy()->addDays(2)->endOfDay(),
            'hours'           => 8.00,
            'paid_hours'      => 8.00,
            'daily_hours'     => 8.00,
            'daily_breakdown' => [
                [
                    'date'      => $startOfWeek->copy()->addDays(2)->toDateString(),
                    'hours'     => 8,
                    'is_unpaid' => false,
                ]
            ],
            'status'          => 'approved',
        ]);

        Livewire::test(Timesheets::class)
            ->assertSee('Vacation Leave')
            ->assertSee('REGULAR TIME')
            ->assertSee('8:30')
            ->assertSee('PAID TIME OFF')
            ->assertSee('8:00')
            ->assertSee('SUBTOTAL')
            ->assertSee('16:30');
    }

    public function test_timesheets_view_renders_shift_card_and_dispatches_detail_modal()
    {
        $employee = User::factory()->create([
            'role'       => 'Employee',
            'first_name' => 'Heshaam',
            'last_name'  => 'Afzal',
            'name'       => 'Heshaam Afzal',
        ]);

        $admin = User::factory()->create([
            'role'       => 'Administrator',
            'first_name' => 'Admin',
            'last_name'  => 'Manager',
            'name'       => 'Admin Manager',
        ]);

        $job = Assignment::create(['name' => 'Warehouse Hours', 'status' => 'in progress']);
        $task = Task::create(['name' => 'General', 'status' => 'ongoing']);

        $weekStartDay = \App\Models\CompanySetting::getWeekStartDay();
        $startOfWeek = Carbon::now()->startOfWeek($weekStartDay);

        $log = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => $startOfWeek->copy()->setTime(7, 2),
            'clock_out'        => $startOfWeek->copy()->setTime(18, 2),
            'duration_minutes' => 599, // 9h 59m
            'break_minutes'    => 60,
            'status'           => 'completed',
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\TimesheetsView::class)
            ->assertSee('Afzal, Heshaam')
            ->assertSee('9:59')
            ->assertSee('Warehouse Hours')
            ->assertSee('Break (1:00 h:m)')
            ->call('openDetail', $log->id)
            ->assertDispatched('open-log-detail', logId: $log->id);
    }
}
