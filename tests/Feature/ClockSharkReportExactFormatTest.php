<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockSharkReportExactFormatTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $job;
    protected $task;

    protected function setUp(): void
    {
        parent::setUp();

        CompanySetting::set('company_name', "Alison's Montessori");
        CompanySetting::set('server_timezone', 'UTC');
        CompanySetting::set('week_start_day', Carbon::THURSDAY);

        $this->admin = User::factory()->create([
            'name'       => 'Admin Manager',
            'first_name' => 'Admin',
            'last_name'  => 'Manager',
            'role'       => 'Administrator',
            'base_pay'   => 25.00,
        ]);

        $this->employee = User::factory()->create([
            'name'       => 'Heshaam Afzal',
            'first_name' => 'Heshaam',
            'last_name'  => 'Afzal',
            'role'       => 'Employee',
            'base_pay'   => 15.49,
        ]);

        $this->job = Assignment::create([
            'name'     => 'Warehouse Hours',
            'location' => 'Main Warehouse',
            'status'   => 'in progress',
        ]);

        $this->task = Task::create([
            'name'        => 'General',
            'status'      => 'ongoing',
            'hourly_rate' => 15.49,
        ]);
    }

    public function test_unauthorized_user_cannot_access_reports()
    {
        $response = $this->actingAs($this->employee)
            ->get(route('reports.print', ['tab' => 'QuickSummary']));

        $response->assertStatus(403);
    }

    public function test_job_summary_report_renders_exact_clockshark_format()
    {
        // Create logs for Heshaam Afzal
        // Thursday 2025-11-13 (10:03 = 603 min)
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-11-13 07:00:00'),
            'clock_out'        => Carbon::parse('2025-11-13 17:03:00'),
            'duration_minutes' => 603,
            'status'           => 'completed',
        ]);

        // Monday 2025-11-17 (10:10 = 610 min)
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-11-17 07:00:00'),
            'clock_out'        => Carbon::parse('2025-11-17 17:10:00'),
            'duration_minutes' => 610,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'JobSummary',
            'start_date' => '2025-11-13',
            'end_date'   => '2025-11-19',
        ]));

        $response->assertStatus(200);
        $response->assertSee("Alison&#039;s Montessori", false);
        $response->assertSee('Job Summary Report');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('Warehouse Hours');
        $response->assertSee('10:03');
        $response->assertSee('10:10');
        $response->assertSee('20:13');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_job_detail_report_renders_exact_clockshark_format()
    {
        // Thursday 2025-10-30: In 7:06 AM, Break 12:27 PM - 1:59 PM (92m = 1:32), Out 5:58 PM -> Net 9:20 (560 min)
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-30 07:06:00'),
            'clock_out'        => Carbon::parse('2025-10-30 17:58:00'),
            'duration_minutes' => 560,
            'break_minutes'    => 92,
            'breaks'           => [
                [
                    'start'   => '2025-10-30 12:27:00',
                    'end'     => '2025-10-30 13:59:00',
                    'minutes' => 92,
                ]
            ],
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'JobDetail',
            'start_date' => '2025-10-30',
            'end_date'   => '2025-11-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Job Detail Report');
        $response->assertSee('Warehouse Hours');
        $response->assertSee('Afzal');
        $response->assertSee('Heshaam');
        $response->assertSee('7:06 AM');
        $response->assertSee('12:27 PM');
        $response->assertSee('1:59 PM');
        $response->assertSee('1:32');
        $response->assertSee('5:58 PM');
        $response->assertSee('9:20');
        $response->assertSee('Daily Total');
        $response->assertSee('Grand Total');
        $response->assertSee('Employee Total');
        $response->assertSee('Task Total');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_quick_summary_report_renders_exact_clockshark_format()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-30 07:00:00'),
            'clock_out'        => Carbon::parse('2025-10-30 17:00:00'),
            'duration_minutes' => 600,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'QuickSummary',
            'start_date' => '2025-10-30',
            'end_date'   => '2025-11-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Quick Summary Report of Employees');
        $response->assertSee('SUMMARY');
        $response->assertSee('BREAKDOWN');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('10:00');
        $response->assertSee('Grand Total');
        $response->assertSee('Time Off');
        $response->assertSee('Work');
        $response->assertSee('Jobs');
        $response->assertSee('Tasks');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_employee_pay_rate_report_renders_exact_clockshark_format()
    {
        // 11 hours 19 minutes = 679 minutes at $15.49/hr => $175.30
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'hourly_rate'      => 15.49,
            'clock_in'         => Carbon::parse('2025-11-06 07:00:00'),
            'clock_out'        => Carbon::parse('2025-11-06 18:19:00'),
            'duration_minutes' => 679,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'PayRate',
            'start_date' => '2025-11-06',
            'end_date'   => '2025-11-19',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Employee Pay Rate Report');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('Regular ($)');
        $response->assertSee('Overtime x1.5 ($)');
        $response->assertSee('Double Time x2 ($)');
        $response->assertSee('15.49');
        $response->assertSee('23.24');
        $response->assertSee('30.98');
        $response->assertSee('11:19');
        $response->assertSee('175.30');
        $response->assertSee('Job Totals for Employee');
        $response->assertSee('Task Totals for Employee');
        $response->assertSee('Employee Grand Total Cost');
        $response->assertSee('Company Total Cost');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_employee_timesheet_report_renders_exact_clockshark_format()
    {
        // Thursday 2025-10-30: 9:20 (560m)
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-30 07:00:00'),
            'clock_out'        => Carbon::parse('2025-10-30 16:20:00'),
            'duration_minutes' => 560,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'TimeSheet',
            'start_date' => '2025-10-30',
            'end_date'   => '2025-11-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Employee Timesheet');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('Customer:<br>Job', false);
        $response->assertSee('Warehouse Hours');
        $response->assertSee('General');
        $response->assertSee('Regular');
        $response->assertSee('Overtime');
        $response->assertSee('Doubletime');
        $response->assertSee('Time Off');
        $response->assertSee('Holiday');
        $response->assertSee('Week Total Regular');
        $response->assertSee('Week Total Overtime');
        $response->assertSee('Grand Total Regular');
        $response->assertSee('Grand Total Overtime');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_task_summary_report_renders_exact_clockshark_format()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-11-13 07:00:00'),
            'clock_out'        => Carbon::parse('2025-11-13 17:00:00'),
            'duration_minutes' => 600,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'TaskSummary',
            'start_date' => '2025-11-13',
            'end_date'   => '2025-11-19',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Task Summary Report');
        $response->assertSee('General');
        $response->assertSee('10:00');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_task_detail_report_renders_exact_clockshark_format()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-30 06:56:00'),
            'clock_out'        => Carbon::parse('2025-10-30 18:00:00'),
            'duration_minutes' => 604,
            'breaks'           => [
                ['start' => '2025-10-30 13:02:00', 'end' => '2025-10-30 14:02:00', 'minutes' => 60]
            ],
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'TaskDetail',
            'start_date' => '2025-10-30',
            'end_date'   => '2025-11-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Task Detail Report');
        $response->assertSee('General');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('Warehouse Hours');
        $response->assertSee('6:56 AM');
        $response->assertSee('1:02 PM');
        $response->assertSee('2:02 PM');
        $response->assertSee('1:00');
        $response->assertSee('6:00 PM');
        $response->assertSee('10:04');
        $response->assertSee('Daily Total');
        $response->assertSee('Grand Total');
        $response->assertSee('Employee Total');
        $response->assertSee('Customer: Job Total');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_time_off_report_renders_exact_clockshark_format()
    {
        $policy = TimeOffPolicy::create([
            'name'           => 'Vacation Leave',
            'policy_type'    => 0,
            'accrual_method' => 0,
            'active'         => true,
        ]);

        TimeOffRequest::create([
            'employee_id' => $this->employee->id,
            'policy_id'   => $policy->id,
            'start_at'    => Carbon::parse('2025-09-29 08:00:00'),
            'end_at'      => Carbon::parse('2025-10-03 10:15:00'),
            'hours'       => 10.25,
            'paid_hours'  => 10.25,
            'status'      => 'approved',
            'reason'      => 'Used 10.25 hours for Umrah vacation leave.',
            'daily_breakdown' => [
                ['date' => '2025-09-29', 'start' => '8:00 AM', 'end' => '10:00 AM', 'hours' => 2.0],
                ['date' => '2025-09-30', 'start' => '8:00 AM', 'end' => '10:00 AM', 'hours' => 2.0],
                ['date' => '2025-10-01', 'start' => '8:00 AM', 'end' => '10:00 AM', 'hours' => 2.0],
                ['date' => '2025-10-02', 'start' => '8:00 AM', 'end' => '10:00 AM', 'hours' => 2.0],
                ['date' => '2025-10-03', 'start' => '8:00 AM', 'end' => '10:15 AM', 'hours' => 2.25],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'Pto',
            'start_date' => '2025-09-25',
            'end_date'   => '2025-10-08',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Time Off');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('SUMMARY');
        $response->assertSee('1 Requests');
        $response->assertSee('Paid Time Off');
        $response->assertSee('Vacation Leave');
        $response->assertSee('Requested');
        $response->assertSee('Approved');
        $response->assertSee('10:15');
        $response->assertSee('DETAILS');
        $response->assertSee('Mon 09/29/2025');
        $response->assertSee('Taken');
        $response->assertSee('Notes');
        $response->assertSee('Used 10.25 hours for Umrah');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_timesheet_alerts_report_renders_exact_clockshark_format()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-24 07:30:00'),
            'clock_out'        => null,
            'is_unusual'       => true,
            'unusual_reason'   => 'Missing Out',
            'status'           => 'running',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'TimeSheetAlerts',
            'start_date' => '2025-10-23',
            'end_date'   => '2025-11-05',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Timesheet Alerts Report');
        $response->assertSee('SUMMARY');
        $response->assertSee('1 Alerts');
        $response->assertSee('Missing Out');
        $response->assertSee('DETAILS');
        $response->assertSee('Afzal, Heshaam');
        $response->assertSee('10/24/2025');
        $response->assertSee('Powered by ClockShark');
    }

    public function test_clockshark_csv_export_format_and_headers()
    {
        TimeLog::create([
            'employee_id'         => $this->employee->id,
            'assignment_id'       => $this->job->id,
            'task_id'             => $this->task->id,
            'clock_in'            => Carbon::parse('2025-09-21 22:09:35'),
            'clock_out'           => Carbon::parse('2025-09-21 22:56:50'),
            'duration_minutes'    => 47,
            'break_minutes'       => 0,
            'clock_in_latitude'   => 41.3144719,
            'clock_in_longitude'  => -74.1432557,
            'clock_out_latitude'  => 41.3144719,
            'clock_out_longitude' => -74.1432557,
            'approved'            => false,
            'status'              => 'completed',
        ]);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Reports::class)
            ->set('currentTab', 'CsvExport')
            ->set('startDate', '2025-09-20')
            ->set('endDate', '2025-09-22')
            ->call('downloadCSV')
            ->assertSuccessful();
    }

    public function test_payroll_report_renders_exact_format()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-15 08:00:00'),
            'clock_out'        => Carbon::parse('2025-10-15 16:30:00'),
            'duration_minutes' => 510, // 8.5 hrs = 480 reg + 30 OT
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.print', [
            'tab'        => 'PayrollReport',
            'start_date' => '2025-10-10',
            'end_date'   => '2025-10-20',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Payroll Report 2025');
        $response->assertSee('Heshaam');
        $response->assertSee('Afzal');
        $response->assertSee('First Name');
        $response->assertSee('Last Name');
        $response->assertSee('Payable To');
        $response->assertSee('480'); // 480 regular minutes
        $response->assertSee('30');  // 30 overtime minutes
    }

    public function test_payroll_report_csv_download()
    {
        TimeLog::create([
            'employee_id'      => $this->employee->id,
            'assignment_id'    => $this->job->id,
            'task_id'          => $this->task->id,
            'clock_in'         => Carbon::parse('2025-10-15 08:00:00'),
            'clock_out'        => Carbon::parse('2025-10-15 16:30:00'),
            'duration_minutes' => 510,
            'status'           => 'completed',
        ]);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Reports::class)
            ->set('currentTab', 'PayrollReport')
            ->set('startDate', '2025-10-10')
            ->set('endDate', '2025-10-20')
            ->call('downloadCSV')
            ->assertSuccessful();
    }
}
