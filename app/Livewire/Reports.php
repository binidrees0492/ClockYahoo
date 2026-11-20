<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\TimeLog;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Reports extends Component
{
    public $currentTab = null;

    // Global Filters
    public $startDate;
    public $endDate;

    // Toggles for enabling specific filter dropdowns
    public $filterEmployees = false;
    public $filterJobs = false;
    public $filterCustomers = false;
    public $filterTasks = false;
    public $filterQuestions = false;
    public $filterChoices = false;
    public $filterMethods = false;

    // Selected array states for multi-selects
    public $selectedEmployees = [];
    public $selectedJobs = [];
    public $selectedCustomers = [];
    public $selectedTasks = [];
    public $selectedQuestions = [];
    public $selectedChoices = [];
    public $selectedMethods = [];

    // Specific Report Options
    public $includeNotes = false;
    public $summaryOf = '';
    public $includeBreakdown = false;
    public $breakdownSelect = '';
    public $qipGroupBy = 'ByDateIssued';
    public $choicesAlert = false;
    public $timeOffStatus = 'All';
    public $collectionsDueDate = '';
    public $pastDueByAtLeast = 30;
    public $taxReportType = 'SalesTaxCash';
    public $payRateGroupBy = '';

    public function mount()
    {
        if (!auth()->user()->canViewReports()) {
            abort(403, 'Unauthorized access to Reports.');
        }

        $weekStartDay = CompanySetting::getWeekStartDay();
        $this->startDate = now()->startOfWeek($weekStartDay)->format('Y-m-d');
        $this->endDate = now()->startOfWeek($weekStartDay)->addDays(6)->format('Y-m-d');
        $this->collectionsDueDate = now()->format('Y-m-d');
    }

    public function setTab($tab)
    {
        $this->currentTab = $tab;
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->reset([
            'filterEmployees', 'filterJobs', 'filterCustomers', 'filterTasks',
            'filterQuestions', 'filterChoices', 'filterMethods',
            'selectedEmployees', 'selectedJobs', 'selectedCustomers', 'selectedTasks',
            'selectedQuestions', 'selectedChoices', 'selectedMethods',
            'includeNotes', 'summaryOf', 'includeBreakdown', 'breakdownSelect',
            'choicesAlert'
        ]);
    }

    public function viewReportPortrait()
    {
        $this->validateDates();
        $params = [
            'tab' => $this->currentTab,
            'orientation' => 'portrait',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
        if ($this->filterEmployees && !empty($this->selectedEmployees)) {
            $params['employees'] = implode(',', $this->selectedEmployees);
        }
        if ($this->filterJobs && !empty($this->selectedJobs)) {
            $params['jobs'] = implode(',', $this->selectedJobs);
        }
        if ($this->filterTasks && !empty($this->selectedTasks)) {
            $params['tasks'] = implode(',', $this->selectedTasks);
        }

        $url = route('reports.print', $params);
        $this->js("window.open('{$url}', '_blank');");
    }

    public function viewReportLandscape()
    {
        $this->validateDates();
        $params = [
            'tab' => $this->currentTab,
            'orientation' => 'landscape',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
        if ($this->filterEmployees && !empty($this->selectedEmployees)) {
            $params['employees'] = implode(',', $this->selectedEmployees);
        }
        if ($this->filterJobs && !empty($this->selectedJobs)) {
            $params['jobs'] = implode(',', $this->selectedJobs);
        }
        if ($this->filterTasks && !empty($this->selectedTasks)) {
            $params['tasks'] = implode(',', $this->selectedTasks);
        }

        $url = route('reports.print', $params);
        $this->js("window.open('{$url}', '_blank');");
    }

    public function downloadCSV()
    {
        $this->validateDates();

        $filename = strtolower($this->currentTab ?: 'report') . '-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            $companyName = CompanySetting::get('company_name', "Alison's Montessori");
            $timezone = CompanySetting::getServerTimezone();

            if (in_array($this->currentTab, ['QuickSummary', 'JobDetail', 'EmployeeDetail', 'CsvExport', 'TimeSheet', 'TaskSummary', 'TaskDetail', 'TimeSheetAlerts', null])) {
                // Exact ClockShark CSV Export columns
                fputcsv($file, [
                    'EmployeeFName',
                    'EmployeeLName',
                    'EmployeeID',
                    'EmployeeEmail',
                    'Customer: Job',
                    'JobNumber',
                    'Task',
                    'TaskCode',
                    'Start',
                    'End',
                    'MinutesBreak',
                    'MinutesBillable',
                    'MinutesTotal',
                    'StartLat',
                    'StartLong',
                    'EndLat',
                    'EndLong',
                    'Hours:Minutes',
                    'Notes',
                    'Regular (Mins)',
                    'OverTime (Mins)',
                    'DoubleTime (Mins)',
                    'Time Off (Mins)',
                    'Unpaid Time Off (Mins)',
                    'Approved'
                ]);

                $accessibleIds = auth()->user()->getAccessibleEmployeeIds();

                $query = TimeLog::with(['employee', 'assignment', 'task'])
                    ->whereNotNull('clock_out');

                if ($accessibleIds !== null) {
                    $query->whereIn('employee_id', $accessibleIds);
                }

                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('clock_in', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay()
                    ]);
                }

                if ($this->filterEmployees && !empty($this->selectedEmployees)) {
                    $query->whereIn('employee_id', $this->selectedEmployees);
                }

                if ($this->filterJobs && !empty($this->selectedJobs)) {
                    $query->whereIn('assignment_id', $this->selectedJobs);
                }

                if ($this->filterTasks && !empty($this->selectedTasks)) {
                    $query->whereIn('task_id', $this->selectedTasks);
                }

                $query->orderBy('clock_in', 'asc')->chunk(100, function($logs) use ($file, $companyName, $timezone) {
                    foreach ($logs as $log) {
                        $emp = $log->employee;
                        $first = trim($emp->first_name ?? '');
                        $last  = trim($emp->last_name ?? '');
                        if ($first === '' && $last === '' && $emp) {
                            $parts = preg_split('/\s+/', trim($emp->name));
                            $first = $parts[0] ?? '';
                            $last  = count($parts) > 1 ? end($parts) : '';
                        }

                        $jobLabel = \App\Services\ReportDataService::getJobLabel($log->assignment, $companyName);
                        $taskName = $log->task ? $log->task->name : 'General';
                        $taskCode = $log->task ? ($log->task->code ?? '') : '';

                        $clockIn = $log->clock_in ? $log->clock_in->setTimezone($timezone)->format('m/d/Y h:i:s A') : '';
                        $clockOut = $log->clock_out ? $log->clock_out->setTimezone($timezone)->format('m/d/Y h:i:s A') : '';

                        $breakMins = (int) ($log->break_minutes ?: 0);
                        $netMins = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
                        $totalMins = $netMins + $breakMins;

                        $regMins = min($netMins, 480);
                        $otMins = max(0, $netMins - 480);

                        fputcsv($file, [
                            $first,
                            $last,
                            $emp->employee_id ?? ($emp->id ?? ''),
                            $emp->email ?? '',
                            $jobLabel,
                            $log->assignment->job_number ?? ($log->assignment->id ?? ''),
                            $taskName,
                            $taskCode,
                            $clockIn,
                            $clockOut,
                            $breakMins,
                            $netMins,
                            $totalMins,
                            $log->clock_in_latitude ?? '',
                            $log->clock_in_longitude ?? '',
                            $log->clock_out_latitude ?? '',
                            $log->clock_out_longitude ?? '',
                            \App\Services\ReportDataService::formatMinutes($netMins, false),
                            $log->notes ?? '',
                            $regMins,
                            $otMins,
                            0,
                            0,
                            0,
                            $log->approved ? 'Yes' : 'No'
                        ]);
                    }
                });
            } elseif ($this->currentTab === 'Pto') {
                fputcsv($file, ['EmployeeFName', 'EmployeeLName', 'Policy', 'Start Date', 'End Date', 'Hours', 'Status', 'Notes']);
                $ptoQuery = \App\Models\TimeOffRequest::with(['employee', 'policy'])
                    ->whereBetween('start_at', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay()
                    ])
                    ->orderBy('start_at', 'asc');

                $accessibleIds = auth()->user()->getAccessibleEmployeeIds();
                if ($accessibleIds !== null) {
                    $ptoQuery->whereIn('employee_id', $accessibleIds);
                }
                if ($this->filterEmployees && !empty($this->selectedEmployees)) {
                    $ptoQuery->whereIn('employee_id', $this->selectedEmployees);
                }

                foreach ($ptoQuery->get() as $req) {
                    $emp = $req->employee;
                    $first = trim($emp->first_name ?? '');
                    $last  = trim($emp->last_name ?? '');
                    if ($first === '' && $last === '' && $emp) {
                        $parts = preg_split('/\s+/', trim($emp->name));
                        $first = $parts[0] ?? '';
                        $last  = count($parts) > 1 ? end($parts) : '';
                    }

                    fputcsv($file, [
                        $first,
                        $last,
                        $req->policy->name ?? 'Paid Time Off',
                        $req->start_at ? $req->start_at->setTimezone($timezone)->format('m/d/Y h:i A') : '',
                        $req->end_at ? $req->end_at->setTimezone($timezone)->format('m/d/Y h:i A') : '',
                        number_format((float)$req->hours, 2),
                        ucfirst($req->status ?? 'pending'),
                        $req->reason ?? ''
                    ]);
                }
            } elseif ($this->currentTab === 'PayrollReport') {
                // Exact columns from "Payroll report 2025" (media_1792141170260.png)
                fputcsv($file, [
                    'FIRST NAME',
                    'LAST NAME',
                    'PAYABLE TO',
                    'CHECK#',
                    'CHECK DATE',
                    'AMOUNT',
                    'PAYPAL TRANSACTION',
                    'PAYPAL NOTES',
                    'Total Paid',
                    'Paid Regular Hours in Minutes',
                    'Overtime Hours in Minutes',
                    'SICK HOURS',
                    'VACATION HOURS',
                    'Extra Hours from AM',
                    'TOTAL HOURS',
                    'COMMENTS',
                    '2024 Pay Rate',
                    '2025 Pay Rate',
                    'TRAVEL REIMBURSEMENT',
                    'CONFERENCE'
                ]);

                $query = TimeLog::with(['employee', 'assignment', 'task'])
                    ->whereNotNull('clock_out');

                $accessibleIds = auth()->user()->getAccessibleEmployeeIds();
                if ($accessibleIds !== null) {
                    $query->whereIn('employee_id', $accessibleIds);
                }
                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('clock_in', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay()
                    ]);
                }
                if ($this->filterEmployees && !empty($this->selectedEmployees)) {
                    $query->whereIn('employee_id', $this->selectedEmployees);
                }

                $logs = $query->orderBy('clock_in', 'asc')->get();

                $ptoRequests = \App\Models\TimeOffRequest::with(['employee', 'policy'])
                    ->where('status', 'approved')
                    ->where(function ($q) {
                        $q->whereBetween('start_at', [
                            Carbon::parse($this->startDate)->startOfDay(),
                            Carbon::parse($this->endDate)->endOfDay()
                        ]);
                    })
                    ->get();

                $ptoByEmp = [];
                foreach ($ptoRequests as $pto) {
                    $eId = $pto->employee_id ?: 0;
                    $pType = strtolower($pto->policy->name ?? '');
                    $h = (float) $pto->hours;
                    if (!isset($ptoByEmp[$eId])) {
                        $ptoByEmp[$eId] = ['sick' => 0, 'vacation' => 0];
                    }
                    if (str_contains($pType, 'sick')) {
                        $ptoByEmp[$eId]['sick'] += $h;
                    } else {
                        $ptoByEmp[$eId]['vacation'] += $h;
                    }
                }

                $empData = [];
                foreach ($logs as $log) {
                    $emp = $log->employee;
                    if (!$emp) continue;
                    $empId = $emp->id;

                    if (!isset($empData[$empId])) {
                        $first = trim($emp->first_name ?? '');
                        $last  = trim($emp->last_name ?? '');
                        if ($first === '' && $last === '') {
                            $parts = preg_split('/\s+/', trim($emp->name));
                            $first = $parts[0] ?? '';
                            $last  = count($parts) > 1 ? end($parts) : '';
                        }
                        $rate2025 = (float) ($emp->base_pay ?: 15.49);
                        $rate2024 = $rate2025 > 0 ? round($rate2025 * 0.95, 2) : 15.00;

                        $empData[$empId] = [
                            'first'    => $first,
                            'last'     => $last,
                            'rate2024' => $rate2024,
                            'rate2025' => $rate2025,
                            'regMin'   => 0,
                            'otMin'    => 0,
                        ];
                    }

                    $mins = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
                    $empData[$empId]['regMin'] += min($mins, 480);
                    $empData[$empId]['otMin']  += max(0, $mins - 480);
                }

                foreach ($empData as $eId => $d) {
                    $regHours = $d['regMin'] / 60;
                    $otHours  = $d['otMin'] / 60;
                    $sick     = $ptoByEmp[$eId]['sick'] ?? 0;
                    $vacation = $ptoByEmp[$eId]['vacation'] ?? 0;
                    $totalHrs = $regHours + $otHours + $sick + $vacation;
                    $totalPay = ($regHours * $d['rate2025']) + ($otHours * $d['rate2025'] * 1.5) + (($sick + $vacation) * $d['rate2025']);

                    fputcsv($file, [
                        $d['first'],
                        $d['last'],
                        $d['first'] . ' ' . $d['last'],
                        '',
                        '',
                        '$' . number_format($totalPay, 2),
                        '',
                        '',
                        '$' . number_format($totalPay, 2),
                        $d['regMin'],
                        $d['otMin'],
                        number_format($sick, 2),
                        number_format($vacation, 2),
                        '0',
                        number_format($totalHrs, 2),
                        '',
                        '$' . number_format($d['rate2024'], 2) . '/hour',
                        '$' . number_format($d['rate2025'], 2) . '/hour',
                        '$0.00',
                        '$0.00'
                    ]);
                }
            } else {
                fputcsv($file, ['Report Type', 'Start Date', 'End Date', 'Generated At']);
                fputcsv($file, [$this->currentTab, $this->startDate, $this->endDate, now()->toDateTimeString()]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function validateDates()
    {
        if ($this->currentTab !== 'Collection') {
            $this->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
            ]);
        }
    }

    public function render()
    {
        $user = auth()->user();
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $employeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }

        return view('livewire.reports', [
            'employeesList' => $employeesQuery->get(),
            'jobsList' => DB::table('tbl_assignments')->orderBy('name')->get(),
            'customersList' => Schema::hasTable('customers') ? DB::table('customers')->orderBy('name')->get() : [],
            'tasksList' => DB::table('tbl_tasks')->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
