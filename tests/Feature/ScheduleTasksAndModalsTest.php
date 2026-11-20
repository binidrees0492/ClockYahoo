<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Livewire\ScheduleCalendar;
use App\Livewire\ScheduleBoard;
use App\Livewire\TimeClock;
use App\Livewire\Work\Jobs;
use App\Livewire\Work\Tasks;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleTasksAndModalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_modal_and_wire_confirm_are_integrated_in_layout()
    {
        $user = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Alert modal dialog for transforming window.alert into clean UI modal
        $response->assertSee('alert-modal.window', false);
        $response->assertSee('window.alertModal', false);

        // Confirmation modal and Livewire directive confirm integration
        $response->assertSee('confirm-modal.window', false);
        $response->assertSee("window.Livewire.directive('confirm'", false);
    }

    public function test_dashboard_my_schedule_strictly_scopes_to_current_employee_and_hides_switcher()
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'role' => 'Administrator']);
        $emp1 = User::factory()->create(['name' => 'Alice Employee', 'role' => 'Employee']);
        $emp2 = User::factory()->create(['name' => 'Bob Employee', 'role' => 'Employee']);

        $assignment = Assignment::create(['name' => 'Paint Job', 'status' => 'active']);
        $task = Task::create(['name' => 'Wall Prep', 'status' => 'active']);

        // Schedule shift for Alice and Bob
        Schedule::create([
            'employee_id'    => $emp1->id,
            'assignment_id'  => $assignment->id,
            'task_id'        => $task->id,
            'shift_date'     => now()->toDateString(),
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        Schedule::create([
            'employee_id'    => $emp2->id,
            'assignment_id'  => $assignment->id,
            'task_id'        => $task->id,
            'shift_date'     => now()->toDateString(),
            'start_time'     => '09:00',
            'end_time'       => '17:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        // Alice viewing My Schedule
        $this->actingAs($emp1);

        Livewire::test(ScheduleCalendar::class, ['isMySchedule' => true, 'selectedEmployeeId' => $emp1->id])
            ->assertSet('isMySchedule', true)
            ->assertSet('selectedEmployeeId', $emp1->id)
            ->call('setEmployee', $emp2->id) // should be ignored
            ->assertSet('selectedEmployeeId', $emp1->id)
            ->assertDontSee('User:') // employee switcher hidden
            ->assertSee('08:00')
            ->assertDontSee('09:00'); // Bob's shift not visible

        // Even if an Admin views My Schedule on dashboard, it strictly locks to the admin's personal schedule
        $this->actingAs($admin);

        Livewire::test(ScheduleCalendar::class, ['isMySchedule' => true, 'selectedEmployeeId' => $admin->id])
            ->assertSet('isMySchedule', true)
            ->assertSet('selectedEmployeeId', $admin->id)
            ->call('setEmployee', $emp1->id) // Admin cannot switch away on My Schedule
            ->assertSet('selectedEmployeeId', $admin->id)
            ->assertDontSee('User:');
    }

    public function test_admin_schedule_board_supports_tasks_and_jobs_with_status_filtering()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['name' => 'Charlie Worker', 'role' => 'Employee']);

        $activeJob = Assignment::create(['name' => 'Commercial Tower', 'status' => 'active']);
        $completedJob = Assignment::create(['name' => 'Old House', 'status' => 'completed']);

        $ongoingTask = Task::create(['name' => 'HVAC Installation', 'status' => 'in progress']);
        $cancelledTask = Task::create(['name' => 'Roofing', 'status' => 'cancelled']);

        $this->actingAs($admin);

        // ScheduleBoard defaults to ongoing filter
        Livewire::test(ScheduleBoard::class)
            ->assertSee('Commercial Tower')
            ->assertSee('HVAC Installation')
            ->assertDontSee('Old House')
            ->assertDontSee('Roofing')
            // Switch filter to 'completed'
            ->set('statusFilter', 'completed')
            ->assertSee('Old House')
            ->assertDontSee('Commercial Tower')
            // Switch filter to 'all'
            ->set('statusFilter', 'all')
            ->assertSee('Commercial Tower')
            ->assertSee('Old House')
            ->assertSee('HVAC Installation')
            ->assertSee('Roofing')
            // Add a shift assigning employee to active job and ongoing task
            ->call('openAddShift', $emp->id, now()->toDateString())
            ->set('shiftAssignmentId', $activeJob->id)
            ->set('shiftTaskId', $ongoingTask->id)
            ->set('shiftStart', '08:00')
            ->set('shiftEnd', '16:00')
            ->set('shiftStandardHours', 8.00)
            ->call('saveShift')
            ->assertSee('Shift added.');

        $this->assertDatabaseHas('schedules', [
            'employee_id'   => $emp->id,
            'assignment_id' => $activeJob->id,
            'task_id'       => $ongoingTask->id,
        ]);
    }

    public function test_users_can_only_clock_in_and_work_on_ongoing_tasks_and_jobs()
    {
        $employee = User::factory()->create(['role' => 'Employee']);
        $this->actingAs($employee);

        $activeJob = Assignment::create(['name' => 'Job Alpha', 'status' => 'active']);
        $completedJob = Assignment::create(['name' => 'Job Beta', 'status' => 'completed']);

        $ongoingTask = Task::create(['name' => 'Task Active', 'status' => 'in progress']);
        $onHoldTask = Task::create(['name' => 'Task On Hold', 'status' => 'on hold']);

        $employee->update([
            'permissions' => [
                'assigned_jobs' => [$activeJob->id, $completedJob->id],
                'assigned_tasks' => [$ongoingTask->id, $onHoldTask->id],
            ],
        ]);

        // TimeClock initial mount lists only ongoing jobs and tasks
        $component = Livewire::test(TimeClock::class);
        $assignments = $component->get('assignments');
        $tasks = $component->get('tasks');

        $this->assertTrue($assignments->contains('id', $activeJob->id));
        $this->assertFalse($assignments->contains('id', $completedJob->id));

        $this->assertTrue($tasks->contains('id', $ongoingTask->id));
        $this->assertFalse($tasks->contains('id', $onHoldTask->id));

        // Attempting to clock in to a completed job / on hold task is rejected
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $completedJob->id)
            ->set('selectedTask', $ongoingTask->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'You can only clock in to ongoing tasks and jobs.', type: 'error');

        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $activeJob->id)
            ->set('selectedTask', $onHoldTask->id)
            ->call('clockIn')
            ->assertDispatched('toast', message: 'You can only clock in to ongoing tasks and jobs.', type: 'error');

        // Clocking in to ongoing task & job succeeds
        Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $activeJob->id)
            ->set('selectedTask', $ongoingTask->id)
            ->call('clockIn')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('tbl_time_logging', [
            'employee_id'   => $employee->id,
            'assignment_id' => $activeJob->id,
            'task_id'       => $ongoingTask->id,
        ]);
    }

    public function test_jobs_and_tasks_management_supports_new_statuses_and_filters()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        // Test Jobs component with statuses
        Livewire::test(Jobs::class)
            ->call('openModal')
            ->set('name', 'Scheduled Job 1')
            ->set('status', 'scheduled')
            ->call('save');

        $this->assertDatabaseHas('tbl_assignments', [
            'name'   => 'Scheduled Job 1',
            'status' => 'scheduled',
        ]);

        $job = Assignment::where('name', 'Scheduled Job 1')->first();

        // Test Tasks component with statuses
        Livewire::test(Tasks::class)
            ->call('openModal')
            ->set('assignmentId', $job->id)
            ->set('name', 'In Progress Task 1')
            ->set('status', 'in progress')
            ->call('save');

        $this->assertDatabaseHas('tbl_tasks', [
            'name'   => 'In Progress Task 1',
            'status' => 'in progress',
        ]);
    }

    public function test_task_coordinates_auto_populate_in_schedule_board_and_calendar()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['name' => 'Field Worker', 'role' => 'Employee']);

        $taskWithGps = Task::create([
            'name'            => 'Tower Maintenance',
            'status'          => 'active',
            'latitude'        => 37.774929,
            'longitude'       => -122.419416,
            'geofence_radius' => 350,
        ]);

        $this->actingAs($admin);

        // In ScheduleBoard, selecting task triggers updatedShiftTaskId
        Livewire::test(ScheduleBoard::class)
            ->call('openAddShift', $emp->id, now()->toDateString())
            ->set('shiftTaskId', $taskWithGps->id)
            ->assertSet('shiftLatitude', 37.774929)
            ->assertSet('shiftLongitude', -122.419416)
            ->assertSet('shiftGeofenceRadius', 350)
            ->assertSet('shiftLocationName', 'Tower Maintenance')
            ->assertDispatched('coordinates-updated', function ($event, $params) {
                $payload = isset($params['lat']) ? $params : ($params[0] ?? []);
                return $payload['lat'] == 37.774929 && $payload['lng'] == -122.419416 && $payload['radius'] == 350;
            });

        // In ScheduleCalendar, selecting task triggers updatedShiftTaskId
        Livewire::test(ScheduleCalendar::class)
            ->call('openAddShift', now()->toDateString())
            ->set('shiftTaskId', $taskWithGps->id)
            ->assertSet('latitude', 37.774929)
            ->assertSet('longitude', -122.419416)
            ->assertSet('geofenceRadius', 350)
            ->assertSet('locationName', 'Tower Maintenance')
            ->assertDispatched('coordinates-updated', function ($event, $params) {
                $payload = isset($params['lat']) ? $params : ($params[0] ?? []);
                return $payload['lat'] == 37.774929 && $payload['lng'] == -122.419416 && $payload['radius'] == 350;
            });

        // Edit Shift auto populates from Task if shift coordinates are null
        $schedule = Schedule::create([
            'employee_id'    => $emp->id,
            'task_id'        => $taskWithGps->id,
            'shift_date'     => now()->toDateString(),
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'standard_hours' => 8.00,
            'latitude'       => null,
            'longitude'      => null,
            'geofence_radius'=> 200,
        ]);

        Livewire::test(ScheduleBoard::class)
            ->call('openEditShift', $schedule->id)
            ->assertSet('shiftLatitude', 37.774929)
            ->assertSet('shiftLongitude', -122.419416)
            ->assertSet('shiftGeofenceRadius', 350)
            ->assertDispatched('coordinates-updated');

        Livewire::test(ScheduleCalendar::class)
            ->call('openEditShift', $schedule->id)
            ->assertSet('latitude', 37.774929)
            ->assertSet('longitude', -122.419416)
            ->assertSet('geofenceRadius', 350)
            ->assertDispatched('coordinates-updated');
    }

    public function test_navigation_guards_and_wire_confirm_interception_in_layout()
    {
        $user = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Check window._isNavigating and livewire:navigating event listeners
        $response->assertSee('window._isNavigating', false);
        $response->assertSee('livewire:navigating', false);
        $response->assertSee('livewire:navigated', false);
        $response->assertSee('close-all-modals', false);

        // Check capturing wire:confirm click interception
        $response->assertSee("closest('[wire\\\\:confirm]')", false);
        $response->assertSee('window.confirmModal', false);
        $response->assertSee('window.promptModal', false);
    }

    public function test_schedule_board_renders_task_job_column_and_groups_entries_by_job_and_task()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $emp = User::factory()->create(['name' => 'Derek Planner', 'role' => 'Employee']);

        $job = Assignment::create(['name' => 'Metro Station', 'status' => 'active']);
        $task = Task::create(['name' => 'Track Inspection', 'status' => 'active']);

        $schedule = Schedule::create([
            'employee_id'    => $emp->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => now()->startOfWeek()->toDateString(),
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            // Verify table header includes 'Task / Job' column
            ->assertSee('Task / Job')
            ->assertSee('Employee')
            // Verify Job and Task are displayed in the group row cell
            ->assertSee('Metro Station')
            ->assertSee('Track Inspection')
            ->assertSee('Derek Planner')
            // Verify openAddShift pre-populates job and task when clicked from cell
            ->call('openAddShift', $emp->id, now()->startOfWeek()->toDateString(), $job->id, $task->id)
            ->assertSet('shiftEmployeeId', $emp->id)
            ->assertSet('shiftAssignmentId', $job->id)
            ->assertSet('shiftTaskId', $task->id)
            ->assertSet('showAddShift', true);
    }

    public function test_schedule_board_arrow_and_dropdown_navigation_between_weeks()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->assertSet('weekOffset', 0)
            ->assertSee('This Week')
            // Navigate via arrows
            ->call('previousWeek')
            ->assertSet('weekOffset', -1)
            ->assertSee('Last Week')
            ->call('nextWeek')
            ->assertSet('weekOffset', 0)
            ->assertSee('This Week')
            // Toggle dropdown date picker via down arrow
            ->call('toggleDatePicker')
            ->assertSet('showDatePicker', true)
            ->assertSee('2 Weeks Ago')
            ->assertSee('3 Weeks Ago')
            ->assertSee('Next Week')
            // Select preset
            ->call('selectPreset', -2)
            ->assertSet('weekOffset', -2)
            ->assertSet('showDatePicker', false)
            // Select specific date from mini calendar
            ->call('toggleDatePicker')
            ->call('selectDateFromCalendar', now()->subWeeks(3)->toDateString())
            ->assertSet('weekOffset', -3)
            // Reset to this week
            ->call('thisWeek')
            ->assertSet('weekOffset', 0)
            ->assertSee('This Week');
    }
}
