<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\CompanySetting;
use App\Livewire\TimeClock;
use App\Livewire\Timesheets;
use App\Livewire\TimesheetsView;
use App\Livewire\Work\Tasks as TasksComponent;
use App\Services\TimeLogComplianceService;
use Livewire\Livewire;
use Carbon\Carbon;

class ShiftsTasksSubsetsAndArchivalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CompanySetting::set('company_name', 'ClockYahoo Inc');
        CompanySetting::set('company_email', 'admin@clockyahoo.test');
        CompanySetting::set('week_start_day', 'thursday');
        CompanySetting::set('server_timezone', 'UTC');
    }

    /**
     * Requirement 1 & 2: 1-minute interval guard between clock-in and clock-out
     */
    public function test_one_minute_interval_guard_between_clock_in_and_clock_out()
    {
        $job = Assignment::create(['name' => 'Project Site Alpha', 'status' => 'active']);
        $task = Task::create(['name' => 'Framing', 'assignment_id' => $job->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role' => 'Employee',
            'timezone' => 'UTC',
            'permissions' => [
                'assigned_jobs' => [$job->id],
                'assigned_tasks' => [$task->id],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:00'));

        $this->actingAs($employee);

        // Clock in at 09:00:00
        $clock = Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn');

        $log = TimeLog::where('employee_id', $employee->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log);

        // Immediate clock out at 09:00:15 (less than 60 seconds) should be blocked
        Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:15'));

        $clock->call('clockOut')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Please wait at least 1 minute')
                    && ($params['type'] ?? '') === 'warning';
            });

        // The session must still be running
        $this->assertNull($log->fresh()->clock_out);
        $this->assertEquals('active', $log->fresh()->status);

        // Advance time to 09:01:05 (65 seconds elapsed) -> clock out must succeed
        Carbon::setTestNow(Carbon::parse('2026-11-08 09:01:05'));

        $clock->call('clockOut')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Clocked out');
            });

        $this->assertNotNull($log->fresh()->clock_out);
        $this->assertEquals('completed', $log->fresh()->status);

        Carbon::setTestNow();
    }

    /**
     * Requirement 3: Task is a subset of Job (Hierarchical Job -> Task link)
     */
    public function test_tasks_are_subsets_of_jobs_in_management_and_clock()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $jobA = Assignment::create(['name' => 'Hospital Wing A', 'status' => 'active']);
        $jobB = Assignment::create(['name' => 'Office Tower B', 'status' => 'active']);

        $taskA1 = Task::create(['name' => 'Electrical Wiring', 'assignment_id' => $jobA->id, 'status' => 'active']);
        $taskA2 = Task::create(['name' => 'Drywall Install', 'assignment_id' => $jobA->id, 'status' => 'active']);
        $taskB1 = Task::create(['name' => 'HVAC Ducting', 'assignment_id' => $jobB->id, 'status' => 'active']);

        $employee = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'assigned_jobs' => [$jobA->id, $jobB->id],
                'assigned_tasks' => [$taskA1->id, $taskA2->id, $taskB1->id],
            ],
        ]);

        // In Task management component, task links to parent job
        $this->actingAs($admin);
        Livewire::test(TasksComponent::class)
            ->call('openModal')
            ->set('name', 'Plumbing Rough-in')
            ->set('assignmentId', $jobA->id)
            ->set('status', 'active')
            ->call('save');

        $createdTask = Task::where('name', 'Plumbing Rough-in')->first();
        $this->assertNotNull($createdTask);
        $this->assertEquals($jobA->id, $createdTask->assignment_id);
        $this->assertEquals('Hospital Wing A', $createdTask->assignment->name);

        // In TimeClock, selecting Job A dynamically restricts tasks to Job A's child tasks
        $this->actingAs($employee);
        $clock = Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $jobA->id);

        $clockTasks = $clock->get('tasks');
        $taskIds = collect($clockTasks)->pluck('id')->toArray();
        $this->assertContains($taskA1->id, $taskIds);
        $this->assertContains($taskA2->id, $taskIds);
        $this->assertNotContains($taskB1->id, $taskIds);

        // Switch to Job B
        $clock->set('selectedAssignment', $jobB->id);
        $clockTasksB = $clock->get('tasks');
        $taskIdsB = collect($clockTasksB)->pluck('id')->toArray();
        $this->assertContains($taskB1->id, $taskIdsB);
        $this->assertNotContains($taskA1->id, $taskIdsB);
    }

    /**
     * Requirement 4: Different shift hours per employee on the same job,
     * with allow_beyond_shift exception and automatic unusual activity flagging
     */
    public function test_per_employee_job_shift_configuration_and_over_hours_flagging()
    {
        $job = Assignment::create(['name' => 'Bridge Construction', 'status' => 'active']);
        $task = Task::create(['name' => 'Steel Welding', 'assignment_id' => $job->id, 'status' => 'active']);

        // Employee 1: Morning Shift 08:00 - 16:00 (8 hours), NO beyond-shift exception
        $empMorning = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'job_shifts' => [
                    $job->id => [
                        'shift_start' => '08:00',
                        'shift_end' => '16:00',
                        'shift_hours' => 8,
                        'allow_beyond_shift' => false,
                    ],
                ],
            ],
        ]);

        // Employee 2: Night Shift 22:00 - 06:00 (8 hours), WITH beyond-shift exception
        $empNight = User::factory()->create([
            'role' => 'Employee',
            'permissions' => [
                'job_shifts' => [
                    $job->id => [
                        'shift_start' => '22:00',
                        'shift_end' => '06:00',
                        'shift_hours' => 8,
                        'allow_beyond_shift' => true,
                    ],
                ],
            ],
        ]);

        // Verify Model helper getJobShiftConfig
        $shiftConfig1 = $empMorning->getJobShiftConfig($job->id);
        $this->assertNotNull($shiftConfig1);
        $this->assertEquals(8, $shiftConfig1['shift_hours']);
        $this->assertFalse($shiftConfig1['allow_beyond_shift']);

        $shiftConfig2 = $empNight->getJobShiftConfig($job->id);
        $this->assertNotNull($shiftConfig2);
        $this->assertTrue($shiftConfig2['allow_beyond_shift']);

        // 1. Morning employee logs 9.5 hours (exceeds 8h shift without exception) -> FLAGGED AS UNUSUAL
        $logMorning = TimeLog::create([
            'employee_id' => $empMorning->id,
            'assignment_id' => $job->id,
            'task_id' => $task->id,
            'clock_in' => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out' => Carbon::parse('2026-11-08 17:30:00'),
            'duration_minutes' => 570, // 9.5 hours
            'status' => 'completed',
            'approved' => false,
        ]);

        TimeLogComplianceService::processTimeLogSubmission($logMorning);
        $logMorning->refresh();
        $this->assertTrue((bool) $logMorning->is_unusual);
        $this->assertStringContainsString('Exceeded assigned shift of 8.0 hrs', (string) $logMorning->unusual_reason);
        $this->assertStringContainsString('Over-hours exception not granted', (string) $logMorning->unusual_reason);

        // 2. Night employee logs 10 hours on the SAME job (has allow_beyond_shift = true) -> NOT FLAGGED for overtime
        $logNight = TimeLog::create([
            'employee_id' => $empNight->id,
            'assignment_id' => $job->id,
            'task_id' => $task->id,
            'clock_in' => Carbon::parse('2026-11-08 22:00:00'),
            'clock_out' => Carbon::parse('2026-11-09 08:00:00'),
            'duration_minutes' => 600, // 10 hours
            'status' => 'completed',
            'approved' => false,
        ]);

        TimeLogComplianceService::processTimeLogSubmission($logNight);
        $logNight->refresh();
        $this->assertFalse((bool) $logNight->is_unusual);
        $this->assertNull($logNight->unusual_reason);
    }

    /**
     * Requirement 5: Soft delete / Archiving by HR / Super Admin,
     * preserving child records void integrity, and Super Admin archive management
     */
    public function test_archival_void_integrity_and_super_admin_management()
    {
        $superAdmin = User::factory()->create([
            'role' => 'Super Admin',
        ]);
        $hrManager = User::factory()->create([
            'role' => 'HR Manager',
        ]);
        $employee = User::factory()->create([
            'role' => 'Employee',
        ]);

        $job = Assignment::create(['name' => 'Solar Farm', 'status' => 'active']);
        $task = Task::create(['name' => 'Panel Installation', 'assignment_id' => $job->id, 'status' => 'active']);

        Carbon::setTestNow(Carbon::parse('2026-11-08 10:00:00'));

        // Create parent time log and child segment (job/task switch)
        $parentLog = TimeLog::create([
            'employee_id' => $employee->id,
            'assignment_id' => $job->id,
            'task_id' => $task->id,
            'clock_in' => Carbon::parse('2026-11-08 08:00:00'),
            'clock_out' => Carbon::parse('2026-11-08 12:00:00'),
            'duration_minutes' => 240,
            'status' => 'completed',
            'approved' => false,
        ]);

        $childLog = TimeLog::create([
            'employee_id' => $employee->id,
            'assignment_id' => $job->id,
            'task_id' => $task->id,
            'parent_log_id' => $parentLog->id,
            'clock_in' => Carbon::parse('2026-11-08 12:00:00'),
            'clock_out' => Carbon::parse('2026-11-08 16:00:00'),
            'duration_minutes' => 240,
            'status' => 'completed',
            'approved' => false,
        ]);

        // 1. HR Manager deletes the parent log -> must ARCHIVE (soft delete), not destroy RDBMS integrity
        $this->actingAs($hrManager);
        Livewire::test(TimesheetsView::class)
            ->call('deleteLog', $parentLog->id)
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'archived');
            });

        $parentLog->refresh();
        $this->assertTrue((bool) $parentLog->is_archived);
        $this->assertNotNull($parentLog->archived_at);
        $this->assertEquals($hrManager->id, $parentLog->archived_by);

        // Both parent and child still exist in DB (integrity preserved!)
        $this->assertDatabaseHas('tbl_time_logging', ['id' => $parentLog->id]);
        $this->assertDatabaseHas('tbl_time_logging', ['id' => $childLog->id, 'parent_log_id' => $parentLog->id]);

        // Normal TimesheetsView hides archived log
        $viewAsHr = Livewire::test(TimesheetsView::class);
        $viewAsHr->assertDontSee('Solar Farm');

        // 2. Super Admin can view archived records
        $this->actingAs($superAdmin);
        $viewAsSuper = Livewire::test(TimesheetsView::class)
            ->set('viewArchived', true);
        $viewAsSuper->assertSee('Solar Farm');

        // 3. Super Admin action A: Restore
        $viewAsSuper->call('restoreArchivedLog', $parentLog->id);
        $this->assertFalse((bool) $parentLog->fresh()->is_archived);
        $this->assertNull($parentLog->fresh()->archived_at);

        // Re-archive for rejection test
        $viewAsSuper->call('deleteLog', $parentLog->id);
        $this->assertTrue((bool) $parentLog->fresh()->is_archived);

        // 4. Super Admin action B: Reject
        $viewAsSuper->call('rejectArchivedLog', $parentLog->id);
        $this->assertEquals('rejected', $parentLog->fresh()->status);
        $this->assertFalse((bool) $parentLog->fresh()->approved);

        // 5. Super Admin action C: Permanently Delete (cascades child records)
        $viewAsSuper->call('permanentlyDeleteLog', $parentLog->id);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $parentLog->id]);
        $this->assertDatabaseMissing('tbl_time_logging', ['id' => $childLog->id]);
    }

    /**
     * Requirement 6: Hours and minutes formatting in Timesheets
     */
    public function test_hours_and_minutes_formatting()
    {
        $this->assertEquals('8:15', Timesheets::formatHMM(495));
        $this->assertEquals('0:05', Timesheets::formatHMM(5));
        $this->assertEquals('14:00', Timesheets::formatHMM(840, true));
        $this->assertEquals('0:00', Timesheets::formatHMM(0, true));
    }
}
