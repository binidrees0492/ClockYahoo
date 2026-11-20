<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeOffPolicy;
use App\Services\ReportDataService;
use App\Livewire\TimesheetsView;
use App\Livewire\ApproveTimesheets;
use App\Livewire\TimeOff\PolicyWizard;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimesheetEnhancementsAndRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_duration_colon_hm()
    {
        $this->assertEquals('150:48 h:m', ReportDataService::formatDurationColonHM(9048));
        $this->assertEquals('8:30 h:m', ReportDataService::formatDurationColonHM(510));
        $this->assertEquals('0:00 h:m', ReportDataService::formatDurationColonHM(0));
        $this->assertEquals('0:00 h:m', ReportDataService::formatDurationColonHM(null));
    }

    public function test_manual_time_with_break_deducts_from_total_hours()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee']);

        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Development', 'status' => 'active', 'assignment_id' => $job->id]);

        $this->actingAs($admin);
        $date = Carbon::now()->toDateString();

        // 8 hours span (09:00 to 17:00) with 30 min break (12:00 to 12:30)
        Livewire::test(TimesheetsView::class)
            ->call('openAdd', $employee->id, $date)
            ->set('addEmployeeId', $employee->id)
            ->set('addAssignment', $job->id)
            ->set('addTask', $task->id)
            ->set('addClockIn', $date . 'T09:00')
            ->set('addClockOut', $date . 'T17:00')
            ->set('hasBreak', true)
            ->set('addBreakStart', '12:00')
            ->set('addBreakEnd', '12:30')
            ->set('addNotes', 'Completed 8 hour shift with 30m lunch break.')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'Manual time entry added and approved.');

        $log = TimeLog::where('employee_id', $employee->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertTrue((bool)$log->approved);
        // Gross was 480m, break was 30m, so duration_minutes must be 450m (7.5h)
        $this->assertEquals(450, $log->duration_minutes);
        $this->assertEquals(30, $log->break_minutes);
        $this->assertCount(1, $log->breaks);
    }

    public function test_hr_can_approve_and_disapprove_directly_from_timesheets_view()
    {
        $hr = User::factory()->create(['role' => 'HR Manager']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Development', 'status' => 'active', 'assignment_id' => $job->id]);

        $log = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->subHours(4),
            'clock_out'        => Carbon::now(),
            'duration_minutes' => 240,
            'status'           => 'pending',
            'approved'         => false,
        ]);

        $this->actingAs($hr);

        Livewire::test(TimesheetsView::class)
            ->call('approveLog', $log->id)
            ->assertDispatched('toast', message: 'Time entry approved.');

        $this->assertTrue((bool)$log->fresh()->approved);
        $this->assertEquals('approved', $log->fresh()->status);

        Livewire::test(TimesheetsView::class)
            ->call('disapproveLog', $log->id)
            ->assertDispatched('toast', message: 'Time entry returned to pending.');

        $this->assertFalse((bool)$log->fresh()->approved);
        $this->assertEquals('pending', $log->fresh()->status);
    }

    public function test_approve_timesheets_bulk_delete_deletes_child_records()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee']);
        $job = Assignment::create(['name' => 'Project Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Development', 'status' => 'active', 'assignment_id' => $job->id]);

        $parent = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => Carbon::now()->subHours(8),
            'clock_out'        => Carbon::now(),
            'duration_minutes' => 450,
            'status'           => 'pending',
        ]);

        $child = TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'parent_log_id'    => $parent->id,
            'clock_in'         => Carbon::now()->subHours(4),
            'clock_out'        => Carbon::now()->subHours(3)->subMinutes(30),
            'duration_minutes' => 30,
            'status'           => 'pending',
        ]);

        $this->actingAs($admin);

        Livewire::test(ApproveTimesheets::class)
            ->set('selected', [(string)$parent->id])
            ->call('deleteSelected');

        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $parent->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $child->id]);
    }

    public function test_unpaid_policy_wizard_advances_to_employee_selection()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        Livewire::test(PolicyWizard::class)
            ->set('policyName', 'Unpaid Absence Policy')
            ->set('policyType', 2) // Unpaid
            ->call('saveAndGoToEmployeeSelection')
            ->assertSet('step', 'EmployeeSelection');

        $this->assertDatabaseHas('time_off_policies', [
            'name'        => 'Unpaid Absence Policy',
            'policy_type' => 2,
        ]);
    }
}
