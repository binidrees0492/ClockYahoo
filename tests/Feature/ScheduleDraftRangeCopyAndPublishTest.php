<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBoard;
use App\Livewire\TimeClock;
use App\Livewire\Timesheets;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleDraftRangeCopyAndPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_shifts_are_drafted_by_default_and_support_date_range_with_day_checklist(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'HQ Build', 'status' => 'active']);
        $task = Task::create(['name' => 'Framing', 'assignment_id' => $job->id, 'status' => 'active']);

        $this->actingAs($admin);

        // Mount ScheduleBoard
        $component = Livewire::test(ScheduleBoard::class);

        // Verify default Date From and Date To are today
        $today = Carbon::now()->toDateString();
        $component->assertSet('shiftDateFrom', $today)
            ->assertSet('shiftDateTo', $today);

        // Open add shift modal
        $component->call('openAddShift', $employee->id, $today, $job->id, $task->id)
            ->assertSet('shiftStatus', 'draft')
            ->assertSet('shiftIsPublished', false);

        // Set date range spanning 4 days: 2026-11-10 to 2026-11-13 (Tue - Fri)
        $component->set('shiftDateFrom', '2026-11-10')
            ->set('shiftDateTo', '2026-11-13')
            ->set('shiftEmployeeId', $employee->id)
            ->set('shiftAssignmentId', $job->id)
            ->set('shiftTaskId', $task->id)
            ->set('shiftStart', '08:00')
            ->set('shiftEnd', '16:00')
            ->set('shiftStandardHours', 8.00);

        // All 4 days selected
        $selectedDays = $component->get('selectedShiftDays');
        $this->assertCount(4, $selectedDays);
        $this->assertContains('2026-11-10', $selectedDays);
        $this->assertContains('2026-11-11', $selectedDays);
        $this->assertContains('2026-11-12', $selectedDays);
        $this->assertContains('2026-11-13', $selectedDays);

        // Toggle off one day (e.g. 2026-11-12)
        $component->call('toggleShiftDay', '2026-11-12');
        $this->assertNotContains('2026-11-12', $component->get('selectedShiftDays'));
        $this->assertCount(3, $component->get('selectedShiftDays'));

        // Save as draft (default behavior: saveShift(false))
        $component->call('saveShift', false)
            ->assertDispatched('toast');

        // Check created shifts are all drafted
        $created = Schedule::where('employee_id', $employee->id)->get();
        $this->assertCount(3, $created);

        foreach ($created as $shift) {
            $this->assertEquals('draft', $shift->status);
            $this->assertFalse((bool) $shift->is_published);
            $this->assertTrue($shift->isDraft());
            $this->assertFalse($shift->isPublished());
            $this->assertEquals($job->id, $shift->assignment_id);
            $this->assertEquals($task->id, $shift->task_id);
        }

        // Verify 2026-11-12 was excluded
        $this->assertDatabaseMissing('schedules', [
            'employee_id' => $employee->id,
            'shift_date'  => '2026-11-12',
        ]);
    }

    public function test_weekend_exclusion_toggle_properly_filters_days_in_range(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $this->actingAs($admin);

        $component = Livewire::test(ScheduleBoard::class);

        // Date range covering Friday 2026-11-13 to Monday 2026-11-16 (Fri, Sat, Sun, Mon)
        $component->set('shiftDateFrom', '2026-11-13')
            ->set('shiftDateTo', '2026-11-16');

        // By default without excludeWeekends, all 4 days are included
        $this->assertCount(4, $component->get('selectedShiftDays'));

        // Toggle exclude weekends
        $component->set('excludeWeekends', true);
        $selected = $component->get('selectedShiftDays');
        $this->assertCount(2, $selected);
        $this->assertContains('2026-11-13', $selected); // Friday
        $this->assertContains('2026-11-16', $selected); // Monday
        $this->assertNotContains('2026-11-14', $selected); // Saturday excluded
        $this->assertNotContains('2026-11-15', $selected); // Sunday excluded
    }

    public function test_copy_shift_clones_exact_shift_across_single_or_multiple_days_as_draft_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employeeA = User::factory()->create(['role' => 'Employee', 'name' => 'Employee A']);
        $employeeB = User::factory()->create(['role' => 'Employee', 'name' => 'Employee B']);
        $job = Assignment::create(['name' => 'Renovation', 'status' => 'active']);
        $task = Task::create(['name' => 'Plumbing', 'assignment_id' => $job->id, 'status' => 'active']);

        $sourceShift = Schedule::create([
            'employee_id'     => $employeeA->id,
            'assignment_id'   => $job->id,
            'task_id'         => $task->id,
            'shift_date'      => '2026-11-09',
            'start_time'      => '09:00',
            'end_time'        => '17:00',
            'standard_hours'  => 8.00,
            'location_name'   => 'Site Delta',
            'latitude'        => 34.0522,
            'longitude'       => -118.2437,
            'geofence_radius' => 250,
            'notes'           => 'Handle main pipes',
            'status'          => 'published',
            'is_published'    => true,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ScheduleBoard::class);

        // Open copy modal on source shift
        $component->call('openCopyShift', $sourceShift->id)
            ->assertSet('showCopyShift', true)
            ->assertSet('copySourceShiftId', $sourceShift->id)
            ->assertSet('copyTargetEmployeeId', $employeeA->id);

        // Clone to Employee B over 2 days (2026-11-17 and 2026-11-18)
        $component->set('copyTargetEmployeeId', $employeeB->id)
            ->set('copyDateFrom', '2026-11-17')
            ->set('copyDateTo', '2026-11-18')
            ->call('executeCopyShift', false); // Cloned as Draft by default

        $component->assertSet('showCopyShift', false);

        // Verify cloned shifts for Employee B are drafts with same parameters
        $cloned = Schedule::where('employee_id', $employeeB->id)->orderBy('shift_date')->get();
        $this->assertCount(2, $cloned);

        foreach ($cloned as $c) {
            $this->assertEquals('draft', $c->status);
            $this->assertFalse((bool) $c->is_published);
            $this->assertEquals($job->id, $c->assignment_id);
            $this->assertEquals($task->id, $c->task_id);
            $this->assertEquals('09:00', substr($c->start_time, 0, 5));
            $this->assertEquals('17:00', substr($c->end_time, 0, 5));
            $this->assertEquals(8.00, (float) $c->standard_hours);
            $this->assertEquals('Site Delta', $c->location_name);
            $this->assertEquals(250, $c->geofence_radius);
            $this->assertEquals('Handle main pipes', $c->notes);
        }
    }

    public function test_publish_week_and_publish_shift(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Painting Job', 'status' => 'active']);
        $task = Task::create(['name' => 'Walls', 'assignment_id' => $job->id, 'status' => 'active']);

        $weekStartDay = \App\Models\CompanySetting::getWeekStartDay();
        $weekStart = Carbon::now()->startOfWeek($weekStartDay);

        // Create 2 draft shifts during this week
        $shift1 = Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => $weekStart->copy()->toDateString(),
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'status'        => 'draft',
            'is_published'  => false,
        ]);

        $shift2 = Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => $weekStart->copy()->addDays(2)->toDateString(),
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'status'        => 'draft',
            'is_published'  => false,
        ]);

        $this->actingAs($admin);

        // 1. Test single publishShift
        Livewire::test(ScheduleBoard::class)
            ->call('publishShift', $shift1->id)
            ->assertDispatched('toast');

        $this->assertTrue($shift1->fresh()->isPublished());
        $this->assertFalse($shift2->fresh()->isPublished());

        // 2. Test publishWeek
        Livewire::test(ScheduleBoard::class)
            ->call('publishWeek')
            ->assertDispatched('toast');

        $this->assertTrue($shift1->fresh()->isPublished());
        $this->assertTrue($shift2->fresh()->isPublished());
    }

    public function test_employees_only_see_published_shifts_on_schedule_board(): void
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Retail', 'status' => 'active']);
        $task = Task::create(['name' => 'Cashier', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        $today = Carbon::now()->toDateString();

        $draftShift = Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => $today,
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'status'        => 'draft',
            'is_published'  => false,
        ]);

        $this->actingAs($employee);

        // Standard employee mounting ScheduleBoard
        $component = Livewire::test(ScheduleBoard::class);
        $grouped = $component->viewData('groupedRows');

        // Since shift is drafted, employee should see 0 shifts rendered for today
        if (!empty($grouped)) {
            $shiftsToday = $grouped[0]['shifts'][$today] ?? collect();
            $this->assertCount(0, $shiftsToday);
        }

        // Now publish the shift
        $draftShift->update(['status' => 'published', 'is_published' => true]);

        // Re-mount ScheduleBoard as employee
        $component = Livewire::test(ScheduleBoard::class);
        $grouped = $component->viewData('groupedRows');
        $this->assertNotEmpty($grouped);
        $shiftsToday = $grouped[0]['shifts'][$today] ?? collect();
        $this->assertCount(1, $shiftsToday);
        $this->assertEquals($draftShift->id, $shiftsToday->first()->id);
    }

    public function test_employee_cannot_clock_in_when_shift_for_today_is_draft_until_published(): void
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Warehouse', 'status' => 'active']);
        $task = Task::create(['name' => 'Packing', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        $today = Carbon::today()->toDateString();

        // 1. Create a draft shift for today
        $shift = Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => $today,
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'status'        => 'draft',
            'is_published'  => false,
        ]);

        $this->actingAs($employee);

        // Attempting to clock in should be rejected because schedule is in draft
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'Your scheduled shift for today is currently in draft. You can only log time once your schedule has been published.', type: 'error');

        $this->assertDatabaseMissing('tbl_time_logging', [
            'employee_id' => $employee->id,
        ]);

        // 2. Publish the shift
        $shift->update([
            'status'       => 'published',
            'is_published' => true,
        ]);

        // Clock in now succeeds
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
        ]);
    }

    public function test_employee_cannot_manually_log_timesheet_when_scheduled_shift_is_draft_until_published(): void
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Logistics', 'status' => 'active']);
        $task = Task::create(['name' => 'Sorting', 'assignment_id' => $job->id, 'status' => 'active']);

        $shiftDate = Carbon::today()->toDateString();

        // Create draft shift
        $shift = Schedule::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'shift_date'    => $shiftDate,
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'status'        => 'draft',
            'is_published'  => false,
        ]);

        $this->actingAs($employee);

        // Attempting manual timesheet entry on that date is rejected while shift is drafted
        Livewire::test(Timesheets::class)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addClockIn', "{$shiftDate} 08:00:00")
            ->set('addClockOut', "{$shiftDate} 16:00:00")
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'The scheduled shift for this date is currently in draft. You can only log time once the schedule has been published.', type: 'error');

        $this->assertDatabaseMissing('tbl_time_logging', [
            'employee_id' => $employee->id,
        ]);

        // Publish the shift
        $shift->update([
            'status'       => 'published',
            'is_published' => true,
        ]);

        // Manual timesheet entry now succeeds
        Livewire::test(Timesheets::class)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addClockIn', "{$shiftDate} 08:00:00")
            ->set('addClockOut', "{$shiftDate} 16:00:00")
            ->call('saveAdd')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
        ]);
    }
}
