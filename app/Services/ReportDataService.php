<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\TimeLog;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportDataService
{
    /**
     * Format an employee name into "Last, First" (e.g., "Afzal, Heshaam").
     */
    public static function formatEmployeeName($employee): string
    {
        if (!$employee) {
            return 'Unknown';
        }

        $first = trim($employee->first_name ?? '');
        $last  = trim($employee->last_name ?? '');

        if ($first !== '' && $last !== '') {
            return $last . ', ' . $first;
        }

        $rawName = trim($employee->name ?? 'User');
        $parts = preg_split('/\s+/', $rawName);
        if (count($parts) > 1) {
            $lastName = array_pop($parts);
            return $lastName . ', ' . implode(' ', $parts);
        }

        return $rawName;
    }

    /**
     * Format duration in minutes into H:MM (e.g., 560 -> "9:20", 0 -> "0:00" or "—").
     */
    public static function formatMinutes(int $minutes, bool $showDashIfZero = false): string
    {
        if ($minutes <= 0) {
            return $showDashIfZero ? '—' : '0:00';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    /**
     * Format duration in minutes into "0h 0m" format (e.g., 510 -> "8h 30m", 0 -> "0h 0m").
     */
    public static function formatDurationHM(int|float|null $minutes): string
    {
        if ($minutes === null) {
            return '0h 0m';
        }

        $m = (int) round($minutes);
        if ($m <= 0) {
            return '0h 0m';
        }

        $h = intdiv($m, 60);
        $rem = $m % 60;
        return "{$h}h {$rem}m";
    }

    /**
     * Format duration in hours into "0h 0m" format (e.g., 8.5 -> "8h 30m", 0 -> "0h 0m").
     */
    public static function formatHoursToHM(int|float|null $hours): string
    {
        if ($hours === null || $hours <= 0) {
            return '0h 0m';
        }

        return self::formatDurationHM($hours * 60);
    }

    /**
     * Format duration in minutes into "HH:MM h:m" format (e.g., 9048 -> "150:48 h:m").
     */
    public static function formatDurationColonHM(int|float|null $minutes): string
    {
        if ($minutes === null || $minutes <= 0) {
            return '0:00 h:m';
        }

        $m = (int) round($minutes);
        $h = intdiv($m, 60);
        $rem = $m % 60;
        return sprintf('%d:%02d h:m', $h, $rem);
    }

    /**
     * Parse break intervals from a TimeLog model.
     * Returns array of ['start_formatted', 'end_formatted', 'total_formatted', 'minutes'].
     */
    public static function extractBreaks(TimeLog $log, ?string $timezone = null): array
    {
        $timezone = $timezone ?: (auth()->check() ? auth()->user()->getTimezone() : config('app.timezone', 'America/New_York'));
        $results = [];

        if (!empty($log->breaks) && is_array($log->breaks)) {
            foreach ($log->breaks as $b) {
                $start = isset($b['start']) ? Carbon::parse($b['start'])->setTimezone($timezone) : null;
                $end   = isset($b['end']) ? Carbon::parse($b['end'])->setTimezone($timezone) : null;
                $minutes = 0;

                if ($start && $end) {
                    $minutes = (int) round(abs($end->getTimestamp() - $start->getTimestamp()) / 60);
                } elseif (isset($b['minutes'])) {
                    $minutes = (int) $b['minutes'];
                } elseif (isset($b['seconds'])) {
                    $minutes = (int) round($b['seconds'] / 60);
                }

                if ($start || $end || $minutes > 0) {
                    $results[] = [
                        'start_formatted' => $start ? $start->format('g:i A') : '—',
                        'end_formatted'   => $end ? $end->format('g:i A') : '—',
                        'total_formatted' => self::formatMinutes($minutes, true),
                        'minutes'         => $minutes,
                    ];
                }
            }
        }

        if (empty($results) && $log->break_minutes && $log->break_minutes > 0) {
            $start = $log->break_started_at ? $log->break_started_at->setTimezone($timezone)->format('g:i A') : '—';
            $results[] = [
                'start_formatted' => $start,
                'end_formatted'   => '—',
                'total_formatted' => self::formatMinutes((int) $log->break_minutes, true),
                'minutes'         => (int) $log->break_minutes,
            ];
        }

        return $results;
    }

    /**
     * Get Job display label (e.g. "Alison's Montessori - Warehouse Hours").
     */
    public static function getJobLabel($assignment, string $companyName): string
    {
        if (!$assignment) {
            return 'General Hours';
        }

        $name = trim($assignment->name);
        if (str_contains($name, ' - ') || str_starts_with(strtolower($name), strtolower($companyName))) {
            return $name;
        }

        return $companyName . ' - ' . $name;
    }

    /**
     * Split a date range into payroll weeks based on the company's week start day.
     */
    public static function splitIntoWeeks(Carbon $start, Carbon $end, int $weekStartDay): array
    {
        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            // Find current week's start day matching $weekStartDay
            $wStart = $cursor->copy()->startOfDay();
            // Move cursor to week start if not already there, but do not go before overall start
            $wEnd = $wStart->copy()->addDays(6)->endOfDay();
            if ($wEnd->gt($end)) {
                $wEnd = $end->copy()->endOfDay();
            }

            // Generate 7 days for the week table headers
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $dayDate = $wStart->copy()->addDays($i);
                $days[] = [
                    'date_str'    => $dayDate->format('Y-m-d'),
                    'day_name'    => $dayDate->format('D'),
                    'day_date'    => $dayDate->format('n/j'),
                    'in_range'    => $dayDate->lte($end),
                    'carbon'      => $dayDate,
                ];
            }

            $weeks[] = [
                'start' => $wStart,
                'end'   => $wEnd,
                'days'  => $days,
            ];

            // Next week starts after 7 days
            $cursor = $wStart->copy()->addDays(7);
        }

        return $weeks;
    }

    /**
     * Build report data based on tab.
     */
    public static function build(string $tab, $logs, Carbon $startDate, Carbon $endDate): array
    {
        $timezone = CompanySetting::getServerTimezone();
        $companyName = CompanySetting::get('company_name', "Alison's Montessori");
        $weekStartDay = CompanySetting::getWeekStartDay();

        $meta = [
            'company_name'       => $companyName,
            'title'              => self::getReportTitle($tab),
            'tab'                => $tab,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'date_range_text'    => $startDate->format('l, F j, Y') . ' - ' . $endDate->format('l, F j, Y'),
            'generated_at_text'  => now()->setTimezone($timezone)->format('l, F j, Y g:i A'),
            'timezone'           => $timezone,
        ];

        return match ($tab) {
            'JobSummary'       => self::buildJobSummaryData($logs, $startDate, $endDate, $meta, $weekStartDay),
            'JobDetail'        => self::buildJobDetailData($logs, $startDate, $endDate, $meta),
            'QuickSummary'     => self::buildQuickSummaryData($logs, $startDate, $endDate, $meta),
            'PayRate'          => self::buildPayRateData($logs, $startDate, $endDate, $meta),
            'TimeSheet'        => self::buildTimesheetData($logs, $startDate, $endDate, $meta, $weekStartDay),
            'EmployeeSummary'  => self::buildJobSummaryData($logs, $startDate, $endDate, $meta, $weekStartDay, true),
            'EmployeeDetail'   => self::buildJobDetailData($logs, $startDate, $endDate, $meta, true),
            'TaskSummary'      => self::buildTaskSummaryData($logs, $startDate, $endDate, $meta, $weekStartDay),
            'TaskDetail'       => self::buildTaskDetailData($logs, $startDate, $endDate, $meta),
            'Pto'              => self::buildTimeOffData($logs, $startDate, $endDate, $meta),
            'TimeSheetAlerts'  => self::buildTimesheetAlertsData($logs, $startDate, $endDate, $meta),
            'PayrollReport'    => self::buildPayrollReportData($logs, $startDate, $endDate, $meta),
            default            => self::buildGenericData($logs, $startDate, $endDate, $meta),
        };
    }

    public static function getReportTitle(string $tab): string
    {
        return match ($tab) {
            'JobSummary'       => 'Job Summary Report',
            'JobDetail'        => 'Job Detail Report',
            'QuickSummary'     => 'Quick Summary Report of Employees',
            'PayRate'          => 'Employee Pay Rate Report',
            'TimeSheet'        => 'Employee Timesheet',
            'EmployeeSummary'  => 'Employee Summary Report',
            'EmployeeDetail'   => 'Employee Detail Report',
            'TaskSummary'      => 'Task Summary Report',
            'TaskDetail'       => 'Task Detail Report',
            'Pto'              => 'Time Off',
            'TimeSheetAlerts'  => 'Timesheet Alerts Report',
            'PayrollReport'    => 'Payroll Report 2025',
            default            => ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $tab)) . ' Report',
        };
    }

    // =========================================================================
    // 1. JOB SUMMARY REPORT (PDF 1)
    // =========================================================================
    private static function buildJobSummaryData($logs, Carbon $startDate, Carbon $endDate, array $meta, int $weekStartDay, bool $groupByEmp = false): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        // Determine days in the range (typically 7 days)
        $days = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $dt) {
            $days[] = [
                'date_str' => $dt->format('Y-m-d'),
                'day_name' => $dt->format('D'),
                'day_date' => $dt->format('n/j'),
            ];
        }

        // Group logs by Employee
        $grouped = [];
        foreach ($logs as $log) {
            $empId = $log->employee_id ?: 0;
            $empName = self::formatEmployeeName($log->employee);
            $jobName = self::getJobLabel($log->assignment, $company);
            $dateStr = $log->clock_in ? $log->clock_in->setTimezone($timezone)->format('Y-m-d') : '';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));

            if (!isset($grouped[$empId])) {
                $grouped[$empId] = [
                    'employee_name' => $empName,
                    'jobs' => [],
                    'day_totals' => array_fill_keys(array_column($days, 'date_str'), 0),
                    'total_minutes' => 0,
                ];
            }

            if (!isset($grouped[$empId]['jobs'][$jobName])) {
                $grouped[$empId]['jobs'][$jobName] = [
                    'job_name' => $jobName,
                    'days' => array_fill_keys(array_column($days, 'date_str'), 0),
                    'total_minutes' => 0,
                ];
            }

            if (isset($grouped[$empId]['jobs'][$jobName]['days'][$dateStr])) {
                $grouped[$empId]['jobs'][$jobName]['days'][$dateStr] += $duration;
                $grouped[$empId]['jobs'][$jobName]['total_minutes'] += $duration;
                $grouped[$empId]['day_totals'][$dateStr] += $duration;
                $grouped[$empId]['total_minutes'] += $duration;
            }
        }

        uasort($grouped, fn($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return array_merge($meta, [
            'days'      => $days,
            'employees' => $grouped,
        ]);
    }

    // =========================================================================
    // 2. JOB DETAIL REPORT (PDF 2)
    // =========================================================================
    private static function buildJobDetailData($logs, Carbon $startDate, Carbon $endDate, array $meta, bool $groupByEmp = false): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        // Group by Job (or by Employee if $groupByEmp)
        $jobs = [];

        foreach ($logs as $log) {
            $jobName = self::getJobLabel($log->assignment, $company);
            $empName = self::formatEmployeeName($log->employee);
            $taskName = $log->task ? $log->task->name : 'General';
            $inDate = $log->clock_in ? $log->clock_in->setTimezone($timezone) : null;
            $outDate = $log->clock_out ? $log->clock_out->setTimezone($timezone) : null;
            $dateKey = $inDate ? $inDate->format('Y-m-d') : 'unknown';
            $dayOfWeek = $inDate ? $inDate->format('D') : '';
            $dateFormatted = $inDate ? $inDate->format('m/d/Y') : '';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
            $breaks = self::extractBreaks($log, $timezone);

            if (!isset($jobs[$jobName])) {
                $jobs[$jobName] = [
                    'job_name'        => $jobName,
                    'dates'           => [],
                    'grand_total_min' => 0,
                    'emp_totals'      => [],
                    'task_totals'     => [],
                ];
            }

            if (!isset($jobs[$jobName]['dates'][$dateKey])) {
                $jobs[$jobName]['dates'][$dateKey] = [
                    'date_key'       => $dateKey,
                    'day_of_week'    => $dayOfWeek,
                    'date_formatted' => $dateFormatted,
                    'entries'        => [],
                    'daily_total_min'=> 0,
                ];
            }

            $jobs[$jobName]['dates'][$dateKey]['entries'][] = [
                'log_id'          => $log->id,
                'employee_name'   => $empName,
                'task_name'       => $taskName,
                'in_formatted'    => $inDate ? $inDate->format('g:i A') : '—',
                'out_formatted'   => $outDate ? $outDate->format('g:i A') : '—',
                'breaks'          => $breaks,
                'duration_min'    => $duration,
                'total_formatted' => self::formatMinutes($duration, false),
            ];

            $jobs[$jobName]['dates'][$dateKey]['daily_total_min'] += $duration;
            $jobs[$jobName]['grand_total_min'] += $duration;

            // Summaries
            $jobs[$jobName]['emp_totals'][$empName] = ($jobs[$jobName]['emp_totals'][$empName] ?? 0) + $duration;
            $jobs[$jobName]['task_totals'][$taskName] = ($jobs[$jobName]['task_totals'][$taskName] ?? 0) + $duration;
        }

        ksort($jobs);

        return array_merge($meta, [
            'jobs' => $jobs,
        ]);
    }

    // =========================================================================
    // 3. QUICK SUMMARY REPORT OF EMPLOYEES (PDF 3)
    // =========================================================================
    private static function buildQuickSummaryData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        $employees = [];
        $grandTotalMin = 0;

        foreach ($logs as $log) {
            $empId = $log->employee_id ?: 0;
            $empName = self::formatEmployeeName($log->employee);
            $jobName = self::getJobLabel($log->assignment, $company);
            $taskName = $log->task ? $log->task->name : 'General';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));

            if (!isset($employees[$empId])) {
                $employees[$empId] = [
                    'id'            => $empId,
                    'name'          => $empName,
                    'total_min'     => 0,
                    'reg_min'       => 0,
                    'ot_min'        => 0,
                    'dt_min'        => 0,
                    'jobs'          => [],
                    'tasks'         => [],
                ];
            }

            $employees[$empId]['total_min'] += $duration;
            $grandTotalMin += $duration;

            // Jobs breakdown
            $employees[$empId]['jobs'][$jobName] = ($employees[$empId]['jobs'][$jobName] ?? 0) + $duration;
            // Tasks breakdown
            $employees[$empId]['tasks'][$taskName] = ($employees[$empId]['tasks'][$taskName] ?? 0) + $duration;
        }

        // Calculate Reg / OT per employee (standard 40h regular per week)
        foreach ($employees as &$emp) {
            $emp['reg_min'] = $emp['total_min'];
            $emp['ot_min']  = 0;
            $emp['dt_min']  = 0;
        }
        unset($emp);

        uasort($employees, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_merge($meta, [
            'employees'       => $employees,
            'grand_total_min' => $grandTotalMin,
        ]);
    }

    // =========================================================================
    // 4. EMPLOYEE PAY RATE REPORT (PDF 4)
    // =========================================================================
    private static function buildPayRateData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        $employees = [];
        $companyTotalCost = 0.0;

        foreach ($logs as $log) {
            $empId = $log->employee_id ?: 0;
            $employee = $log->employee;
            $empName = self::formatEmployeeName($employee);
            $jobName = self::getJobLabel($log->assignment, $company);
            $taskName = $log->task ? $log->task->name : 'General';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));

            $rate = (float) $log->getEffectiveRate();
            $cost = round(($duration / 60) * $rate, 2);

            $key = $jobName . '||' . $taskName;

            if (!isset($employees[$empId])) {
                $employees[$empId] = [
                    'id'               => $empId,
                    'name'             => $empName,
                    'rows'             => [],
                    'total_min'        => 0,
                    'total_cost'       => 0.0,
                    'job_totals'       => [],
                    'task_totals'      => [],
                ];
            }

            if (!isset($employees[$empId]['rows'][$key])) {
                $employees[$empId]['rows'][$key] = [
                    'job_name'        => $jobName,
                    'task_name'       => $taskName,
                    'rate'            => $rate,
                    'ot_rate'         => round($rate * 1.5, 2),
                    'dt_rate'         => round($rate * 2.0, 2),
                    'reg_min'         => 0,
                    'reg_cost'        => 0.0,
                    'ot_min'          => 0,
                    'ot_cost'         => 0.0,
                    'dt_min'          => 0,
                    'dt_cost'         => 0.0,
                    'total_cost'      => 0.0,
                ];
            }

            $employees[$empId]['rows'][$key]['reg_min'] += $duration;
            $employees[$empId]['rows'][$key]['reg_cost'] += $cost;
            $employees[$empId]['rows'][$key]['total_cost'] += $cost;

            $employees[$empId]['total_min'] += $duration;
            $employees[$empId]['total_cost'] += $cost;

            $employees[$empId]['job_totals'][$jobName] = ($employees[$empId]['job_totals'][$jobName] ?? 0.0) + $cost;
            $employees[$empId]['task_totals'][$taskName] = ($employees[$empId]['task_totals'][$taskName] ?? 0.0) + $cost;

            $companyTotalCost += $cost;
        }

        uasort($employees, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_merge($meta, [
            'employees'          => $employees,
            'company_total_cost' => $companyTotalCost,
        ]);
    }

    // =========================================================================
    // 5. EMPLOYEE TIMESHEET (PDF 5)
    // =========================================================================
    private static function buildTimesheetData($logs, Carbon $startDate, Carbon $endDate, array $meta, int $weekStartDay): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        // Split overall date range into weekly segments starting on $weekStartDay
        $weeks = self::splitIntoWeeks($startDate, $endDate, $weekStartDay);

        // Group logs by Employee
        $employees = [];

        foreach ($logs as $log) {
            $empId = $log->employee_id ?: 0;
            $employee = $log->employee;
            $empName = self::formatEmployeeName($employee);
            $jobName = self::getJobLabel($log->assignment, $company);
            $taskName = $log->task ? $log->task->name : 'General';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
            $inDate = $log->clock_in ? $log->clock_in->setTimezone($timezone) : null;
            $dateStr = $inDate ? $inDate->format('Y-m-d') : '';

            if (!isset($employees[$empId])) {
                $employees[$empId] = [
                    'id'               => $empId,
                    'name'             => $empName,
                    'grand_total_min'  => 0,
                    'grand_total_ot'   => 0,
                    'weeks'            => [],
                ];

                // Initialize weeks for this employee
                foreach ($weeks as $wIndex => $w) {
                    $employees[$empId]['weeks'][$wIndex] = [
                        'days'        => $w['days'],
                        'start'       => $w['start'],
                        'end'         => $w['end'],
                        'jobs'        => [],
                        'day_totals'  => array_fill_keys(array_column($w['days'], 'date_str'), 0),
                        'week_total'  => 0,
                        'week_ot'     => 0,
                    ];
                }
            }

            $employees[$empId]['grand_total_min'] += $duration;

            // Find which week this log belongs to
            foreach ($employees[$empId]['weeks'] as $wIndex => &$wData) {
                if (isset($wData['day_totals'][$dateStr])) {
                    $jobKey = $jobName . '||' . $taskName;
                    if (!isset($wData['jobs'][$jobKey])) {
                        $wData['jobs'][$jobKey] = [
                            'job_name'      => $jobName,
                            'task_name'     => $taskName,
                            'days'          => array_fill_keys(array_column($wData['days'], 'date_str'), 0),
                            'total_minutes' => 0,
                        ];
                    }

                    $wData['jobs'][$jobKey]['days'][$dateStr] += $duration;
                    $wData['jobs'][$jobKey]['total_minutes'] += $duration;
                    $wData['day_totals'][$dateStr] += $duration;
                    $wData['week_total'] += $duration;
                    break;
                }
            }
            unset($wData);
        }

        uasort($employees, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_merge($meta, [
            'employees' => $employees,
            'weeks'     => $weeks,
        ]);
    }

    // =========================================================================
    // 6. TASK SUMMARY REPORT
    // =========================================================================
    private static function buildTaskSummaryData($logs, Carbon $startDate, Carbon $endDate, array $meta, int $weekStartDay): array
    {
        $timezone = $meta['timezone'];

        // Generate days in the range (typically 7 days)
        $days = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $dt) {
            $days[] = [
                'date_str' => $dt->format('Y-m-d'),
                'day_name' => $dt->format('D'),
                'day_date' => $dt->format('n/j'),
            ];
        }

        $dayTotals = array_fill_keys(array_column($days, 'date_str'), 0);
        $tasks = [];
        $grandTotal = 0;

        foreach ($logs as $log) {
            $taskName = $log->task ? $log->task->name : 'General';
            $dateStr = $log->clock_in ? $log->clock_in->setTimezone($timezone)->format('Y-m-d') : '';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));

            if (!isset($tasks[$taskName])) {
                $tasks[$taskName] = [
                    'task_name'     => $taskName,
                    'days'          => array_fill_keys(array_column($days, 'date_str'), 0),
                    'total_minutes' => 0,
                ];
            }

            if (isset($tasks[$taskName]['days'][$dateStr])) {
                $tasks[$taskName]['days'][$dateStr] += $duration;
                $tasks[$taskName]['total_minutes'] += $duration;
                $dayTotals[$dateStr] += $duration;
                $grandTotal += $duration;
            }
        }

        ksort($tasks);

        return array_merge($meta, [
            'days'        => $days,
            'tasks'       => $tasks,
            'day_totals'  => $dayTotals,
            'grand_total' => $grandTotal,
        ]);
    }

    // =========================================================================
    // 7. TASK DETAIL REPORT
    // =========================================================================
    private static function buildTaskDetailData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        $tasks = [];
        $grandEmployeeTotals = [];
        $grandJobTotals = [];
        $overallGrandTotal = 0;

        foreach ($logs as $log) {
            $taskName = $log->task ? $log->task->name : 'General';
            $jobName = self::getJobLabel($log->assignment, $company);
            $empName = self::formatEmployeeName($log->employee);
            $inDate = $log->clock_in ? $log->clock_in->setTimezone($timezone) : null;
            $outDate = $log->clock_out ? $log->clock_out->setTimezone($timezone) : null;
            $dateKey = $inDate ? $inDate->format('Y-m-d') : 'unknown';
            $dayOfWeek = $inDate ? $inDate->format('D') : '';
            $dateFormatted = $inDate ? $inDate->format('m/d/Y') : '';
            $duration = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
            $breaks = self::extractBreaks($log, $timezone);

            if (!isset($tasks[$taskName])) {
                $tasks[$taskName] = [
                    'task_name'        => $taskName,
                    'dates'            => [],
                    'grand_total_min'  => 0,
                    'emp_totals'       => [],
                    'job_totals'       => [],
                ];
            }

            if (!isset($tasks[$taskName]['dates'][$dateKey])) {
                $tasks[$taskName]['dates'][$dateKey] = [
                    'date_key'       => $dateKey,
                    'day_of_week'    => $dayOfWeek,
                    'date_formatted' => $dateFormatted,
                    'display_date'   => $dayOfWeek . ' ' . $dateFormatted,
                    'shifts'         => [],
                    'daily_total'    => 0,
                ];
            }

            $firstBreak = $breaks[0] ?? null;
            $extraBreaks = count($breaks) > 1 ? array_slice($breaks, 1) : [];

            $shiftItem = [
                'employee_name' => $empName,
                'job_name'      => $jobName,
                'clock_in'      => $inDate ? $inDate->format('g:i A') : '—',
                'clock_out'     => $outDate ? $outDate->format('g:i A') : '—',
                'duration'      => $duration,
                'total_fmt'     => self::formatMinutes($duration, false),
                'first_break'   => $firstBreak,
                'extra_breaks'  => $extraBreaks,
            ];

            $tasks[$taskName]['dates'][$dateKey]['shifts'][] = $shiftItem;
            $tasks[$taskName]['dates'][$dateKey]['daily_total'] += $duration;
            $tasks[$taskName]['grand_total_min'] += $duration;

            $tasks[$taskName]['emp_totals'][$empName] = ($tasks[$taskName]['emp_totals'][$empName] ?? 0) + $duration;
            $tasks[$taskName]['job_totals'][$jobName] = ($tasks[$taskName]['job_totals'][$jobName] ?? 0) + $duration;

            $grandEmployeeTotals[$empName] = ($grandEmployeeTotals[$empName] ?? 0) + $duration;
            $grandJobTotals[$jobName] = ($grandJobTotals[$jobName] ?? 0) + $duration;
            $overallGrandTotal += $duration;
        }

        ksort($tasks);
        foreach ($tasks as &$taskItem) {
            ksort($taskItem['dates']);
            ksort($taskItem['emp_totals']);
            ksort($taskItem['job_totals']);
        }
        unset($taskItem);

        ksort($grandEmployeeTotals);
        ksort($grandJobTotals);

        return array_merge($meta, [
            'tasks'                  => $tasks,
            'grand_employee_totals'  => $grandEmployeeTotals,
            'grand_job_totals'       => $grandJobTotals,
            'overall_grand_total'    => $overallGrandTotal,
        ]);
    }

    // =========================================================================
    // 8. TIME OFF REPORT (PTO)
    // =========================================================================
    private static function buildTimeOffData($requests, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];

        // If requests is empty or not TimeOffRequest instances, fetch from database
        if (empty($requests) || $requests->isEmpty() || !($requests->first() instanceof TimeOffRequest)) {
            $requests = TimeOffRequest::with(['employee', 'policy'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_at', [$startDate, $endDate])
                      ->orWhereBetween('end_at', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_at', '<=', $startDate)
                             ->where('end_at', '>=', $endDate);
                      });
                })
                ->orderBy('start_at', 'asc')
                ->get();
        }

        $allPolicies = TimeOffPolicy::where('active', true)->get();
        if ($allPolicies->isEmpty()) {
            $allPolicies = collect([
                (object)['id' => 1, 'name' => 'Sick Leave'],
                (object)['id' => 2, 'name' => 'Vacation Leave'],
            ]);
        }

        $employees = [];

        foreach ($requests as $req) {
            $emp = $req->employee;
            $empId = $req->employee_id ?: 0;
            $empName = self::formatEmployeeName($emp);

            if (!isset($employees[$empId])) {
                $policyMap = [];
                foreach ($allPolicies as $pol) {
                    $policyMap[$pol->name] = 0;
                }

                $employees[$empId] = [
                    'employee_name'  => $empName,
                    'requests_count' => 0,
                    'policies'       => $policyMap,
                    'requested_min'  => 0,
                    'declined_min'   => 0,
                    'pending_min'    => 0,
                    'approved_min'   => 0,
                    'requests'       => [],
                ];
            }

            $employees[$empId]['requests_count']++;

            $totalMin = (int) round(((float)$req->hours) * 60);
            $employees[$empId]['requested_min'] += $totalMin;

            $status = strtolower($req->status ?? 'pending');
            $polName = $req->policy ? $req->policy->name : 'Paid Time Off';

            if ($status === 'approved') {
                $employees[$empId]['approved_min'] += $totalMin;
                $employees[$empId]['policies'][$polName] = ($employees[$empId]['policies'][$polName] ?? 0) + $totalMin;
            } elseif ($status === 'denied' || $status === 'declined') {
                $employees[$empId]['declined_min'] += $totalMin;
            } else {
                $employees[$empId]['pending_min'] += $totalMin;
            }

            // Parse daily breakdown
            $days = [];
            if (!empty($req->daily_breakdown) && is_array($req->daily_breakdown)) {
                foreach ($req->daily_breakdown as $db) {
                    $dCarbon = isset($db['date']) ? Carbon::parse($db['date']) : null;
                    $dHours = isset($db['hours']) ? (float)$db['hours'] : ((float)$req->hours / max(1, count($req->daily_breakdown)));
                    $dMin = (int) round($dHours * 60);
                    $days[] = [
                        'date_formatted'  => $dCarbon ? $dCarbon->format('D m/d/Y') : '—',
                        'start_formatted' => $db['start'] ?? ($req->start_at ? $req->start_at->setTimezone($timezone)->format('g:i A') : '8:00 AM'),
                        'end_formatted'   => $db['end'] ?? ($req->end_at ? $req->end_at->setTimezone($timezone)->format('g:i A') : '5:00 PM'),
                        'total_formatted' => self::formatMinutes($dMin, false),
                        'minutes'         => $dMin,
                    ];
                }
            } else {
                $startAt = $req->start_at ? $req->start_at->setTimezone($timezone) : $startDate;
                $endAt = $req->end_at ? $req->end_at->setTimezone($timezone) : $startAt;
                $period = CarbonPeriod::create($startAt->copy()->startOfDay(), $endAt->copy()->startOfDay());
                $numDays = max(1, count($period));
                $perDayMin = (int) round($totalMin / $numDays);

                foreach ($period as $idx => $dt) {
                    $isFirst = ($idx === 0);
                    $isLast = ($idx === ($numDays - 1));
                    $startFmt = ($isFirst && $req->start_at) ? $req->start_at->setTimezone($timezone)->format('g:i A') : '8:00 AM';
                    $endFmt = ($isLast && $req->end_at) ? $req->end_at->setTimezone($timezone)->format('g:i A') : '5:00 PM';
                    if ($numDays === 1) {
                        $startFmt = $req->start_at ? $req->start_at->setTimezone($timezone)->format('g:i A') : '8:00 AM';
                        $endFmt = $req->end_at ? $req->end_at->setTimezone($timezone)->format('g:i A') : '5:00 PM';
                    }

                    $days[] = [
                        'date_formatted'  => $dt->format('D m/d/Y'),
                        'start_formatted' => $startFmt,
                        'end_formatted'   => $endFmt,
                        'total_formatted' => self::formatMinutes($perDayMin, false),
                        'minutes'         => $perDayMin,
                    ];
                }
            }

            $statusLabel = match($status) {
                'approved' => 'Taken',
                'denied', 'declined' => 'Declined',
                default => 'Pending',
            };

            $employees[$empId]['requests'][] = [
                'id'           => $req->id,
                'policy_name'  => $polName,
                'status'       => $status,
                'status_label' => $statusLabel,
                'total_fmt'    => self::formatMinutes($totalMin, false),
                'total_min'    => $totalMin,
                'notes'        => $req->reason ?: ($req->decision_notes ?: ''),
                'days'         => $days,
            ];
        }

        uasort($employees, fn($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return array_merge($meta, [
            'employees'          => $employees,
            'summary_range_text' => $startDate->format('F d, Y') . ' - ' . $endDate->format('F d, Y'),
        ]);
    }

    // =========================================================================
    // 9. TIMESHEET ALERTS REPORT
    // =========================================================================
    private static function buildTimesheetAlertsData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];
        $company = $meta['company_name'];

        $alertCounts = [
            'Missing Out'      => 0,
            'Unusual Activity' => 0,
            'Location Flag'    => 0,
            'Action Requested' => 0,
        ];

        $employees = [];
        $totalAlerts = 0;

        foreach ($logs as $log) {
            $alerts = [];
            if (!$log->clock_out || (isset($log->unusual_reason) && str_contains(strtolower($log->unusual_reason), 'missing out'))) {
                $alerts[] = 'Missing Out';
                $alertCounts['Missing Out']++;
            }
            if ($log->is_unusual && !in_array('Missing Out', $alerts)) {
                $alerts[] = 'Unusual Activity';
                $alertCounts['Unusual Activity']++;
            }
            if ($log->location_flagged) {
                $alerts[] = 'Location Flag';
                $alertCounts['Location Flag']++;
            }
            if ($log->action_requested) {
                $alerts[] = 'Action Requested';
                $alertCounts['Action Requested']++;
            }

            if (empty($alerts) && ($log->is_unusual || !$log->clock_out)) {
                $alerts[] = 'Missing Out';
                $alertCounts['Missing Out']++;
            }

            if (empty($alerts)) {
                continue;
            }

            $empId = $log->employee_id ?: 0;
            $empName = self::formatEmployeeName($log->employee);
            $jobName = self::getJobLabel($log->assignment, $company);
            $inTime = $log->clock_in ? $log->clock_in->setTimezone($timezone) : null;
            $outTime = $log->clock_out ? $log->clock_out->setTimezone($timezone) : null;

            $dateStr = $inTime ? $inTime->format('m/d/Y') : '—';
            $timeRangeStr = ($inTime ? $inTime->format('h:i A') : '—') . ' - ' . ($outTime ? $outTime->format('h:i A') : 'Missing Out');

            if (!isset($employees[$empId])) {
                $employees[$empId] = [
                    'employee_name'   => $empName,
                    'alerts_count'    => 0,
                    'alert_breakdown' => [],
                    'rows'            => [],
                ];
            }

            foreach ($alerts as $aType) {
                $employees[$empId]['alert_breakdown'][$aType] = ($employees[$empId]['alert_breakdown'][$aType] ?? 0) + 1;
                $employees[$empId]['alerts_count']++;
                $totalAlerts++;
            }

            $employees[$empId]['rows'][] = [
                'date'       => $dateStr,
                'time_range' => $timeRangeStr,
                'job_name'   => $jobName,
                'alerts_str' => implode(', ', $alerts),
            ];
        }

        uasort($employees, fn($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return array_merge($meta, [
            'total_alerts'       => $totalAlerts,
            'alert_counts'       => array_filter($alertCounts, fn($v) => $v > 0) ?: ['Missing Out' => 0],
            'employees'          => $employees,
            'summary_range_text' => $startDate->format('F j') . ' - ' . $endDate->format('F j, Y'),
        ]);
    }

    // =========================================================================
    // 10. PAYROLL REPORT 2025
    // =========================================================================
    private static function buildPayrollReportData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        $timezone = $meta['timezone'];
        $employees = [];

        // Fetch PTO for this period as well
        $ptoRequests = TimeOffRequest::with(['employee', 'policy'])
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate, $endDate])
                  ->orWhereBetween('end_at', [$startDate, $endDate]);
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

        foreach ($logs as $log) {
            $emp = $log->employee;
            if (!$emp) continue;
            $empId = $emp->id;

            if (!isset($employees[$empId])) {
                $first = trim($emp->first_name ?? '');
                $last  = trim($emp->last_name ?? '');
                if ($first === '' && $last === '') {
                    $parts = preg_split('/\s+/', trim($emp->name));
                    $first = $parts[0] ?? '';
                    $last  = count($parts) > 1 ? end($parts) : '';
                }

                $rate2025 = (float) ($emp->base_pay ?: 15.49);
                $rate2024 = $rate2025 > 0 ? round($rate2025 * 0.95, 2) : 15.00;

                $employees[$empId] = [
                    'employee_id'    => $empId,
                    'first_name'     => $first,
                    'last_name'      => $last,
                    'payable_to'     => $first . ' ' . $last,
                    'rate_2024'      => $rate2024,
                    'rate_2025'      => $rate2025,
                    'reg_minutes'    => 0,
                    'ot_minutes'     => 0,
                    'sick_hours'     => $ptoByEmp[$empId]['sick'] ?? 0,
                    'vacation_hours' => $ptoByEmp[$empId]['vacation'] ?? 0,
                    'total_paid'     => 0,
                ];
            }

            $mins = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
            $regM = min($mins, 480);
            $otM  = max(0, $mins - 480);

            $employees[$empId]['reg_minutes'] += $regM;
            $employees[$empId]['ot_minutes'] += $otM;

            $regCost = ($regM / 60) * $employees[$empId]['rate_2025'];
            $otCost  = ($otM / 60) * ($employees[$empId]['rate_2025'] * 1.5);
            $employees[$empId]['total_paid'] += ($regCost + $otCost);
        }

        uasort($employees, fn($a, $b) => strcmp($a['last_name'], $b['last_name']));

        return array_merge($meta, [
            'employees' => $employees,
        ]);
    }

    // =========================================================================
    // Fallback Generic
    // =========================================================================
    private static function buildGenericData($logs, Carbon $startDate, Carbon $endDate, array $meta): array
    {
        return array_merge($meta, [
            'logs' => $logs,
        ]);
    }
}
