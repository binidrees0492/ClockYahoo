<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Department;
use App\Models\Location;
use App\Models\CompanySetting;
use App\Livewire\TimesheetsView;
use App\Livewire\Timesheets;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimesheetFiltersAndPendingApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_manual_time_is_directly_approved_by_default_or_pending_if_selected()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee', 'name' => 'Mohammed Javed']);

        $assignment = Assignment::create([
            'name'   => 'ERP Solution Developer',
            'status' => 'active',
        ]);

        $task = Task::create([
            'name'   => 'Development Task',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $nowDate = Carbon::now()->toDateString();

        // 1. Direct approval by HR/Admin by default (Requirement 5)
        Livewire::test(TimesheetsView::class)
            ->call('openAdd', $employee->id, $nowDate)
            ->set('addEmployeeId', $employee->id)
            ->set('addAssignment', $assignment->id)
            ->set('addTask', $task->id)
            ->set('addClockIn', $nowDate . 'T09:00')
            ->set('addClockOut', $nowDate . 'T17:00')
            ->set('addNotes', 'Direct HR manual entry.')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'Manual time entry added and approved.');

        $directLog = TimeLog::where('employee_id', $employee->id)->latest('id')->first();
        $this->assertNotNull($directLog);
        $this->assertTrue((bool) $directLog->approved);
        $this->assertEquals('approved', $directLog->status);
        $this->assertEquals($admin->id, $directLog->approved_by);
        $this->assertNotNull($directLog->approved_at);

        // 2. Can also select pending approval if desired
        Livewire::test(TimesheetsView::class)
            ->call('openAdd', $employee->id, $nowDate)
            ->set('addEmployeeId', $employee->id)
            ->set('addAssignment', $assignment->id)
            ->set('addTask', $task->id)
            ->set('addClockIn', $nowDate . 'T09:00')
            ->set('addClockOut', $nowDate . 'T17:00')
            ->set('addNotes', 'Requires manager review.')
            ->set('addStatus', 'pending')
            ->call('saveAdd')
            ->assertDispatched('toast', message: 'Manual time entry added. Submitted for approval.');

        $pendingLog = TimeLog::where('employee_id', $employee->id)->latest('id')->first();
        $this->assertNotNull($pendingLog);
        $this->assertFalse((bool) $pendingLog->approved);
        $this->assertEquals('pending', $pendingLog->status);
    }

    public function test_timesheets_view_filters_and_dropdowns_render_database_values()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee', 'name' => 'Mohammed Javed']);

        $dept = Department::create(['name' => 'Engineering', 'status' => 'Active']);
        $loc = Location::create(['name' => 'Bahria Nasheman', 'status' => 'active']);

        $employee->update([
            'department_id' => $dept->id,
            'location_id'   => $loc->id,
        ]);

        $job = Assignment::create([
            'name'   => 'ERP Solution Developer',
            'status' => 'active',
        ]);

        $task = Task::create([
            'name'   => 'Module Coding',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        // Open TimesheetsView and toggle filters
        $component = Livewire::test(TimesheetsView::class)
            ->set('showFilters', true)
            ->assertSee('Mohammed Javed')
            ->assertSee('ERP Solution Developer')
            ->assertSee('Engineering')
            ->assertSee('Bahria Nasheman')
            ->assertSee('All Team Members')
            ->assertSee('All Departments')
            ->assertSee('All Locations')
            ->assertSee('All Jobs')
            ->assertSee('All Statuses');

        // Test filtering by job
        $component->set('jobFilter', (string) $job->id)
            ->assertSet('jobFilter', (string) $job->id);

        // Test filtering by status
        $component->set('statusFilter', 'pending')
            ->assertSet('statusFilter', 'pending');

        // Test filtering by department
        $component->set('departmentFilter', (string) $dept->id)
            ->assertSet('departmentFilter', (string) $dept->id);

        // Test filtering by location
        $component->set('locationFilter', (string) $loc->id)
            ->assertSet('locationFilter', (string) $loc->id);
    }

    public function test_add_time_modal_renders_employee_dropdown()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $employee = User::factory()->create(['role' => 'Employee', 'name' => 'Mohammed Javed']);

        Assignment::create(['name' => 'ERP Solution Developer', 'status' => 'active']);
        Task::create(['name' => 'Module Coding', 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::test(TimesheetsView::class)
            ->call('openAdd')
            ->assertSet('showAddModal', true)
            ->assertSee('Mohammed Javed');
    }
}
