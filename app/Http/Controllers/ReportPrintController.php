<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeLog;
use App\Services\ReportDataService;
use Carbon\Carbon;

class ReportPrintController extends Controller
{
    public function generatePdf(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->canViewReports()) {
            abort(403, 'Unauthorized to view reports.');
        }

        $tab = $request->query('tab', 'QuickSummary');
        $orientation = $request->query('orientation', 'portrait');
        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');

        $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : now()->startOfWeek()->startOfDay();
        $endDate   = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : now()->endOfWeek()->endOfDay();

        $accessibleIds = $user->getAccessibleEmployeeIds();

        // Filters
        $employeeFilter = $request->query('employees');
        $jobFilter      = $request->query('jobs');
        $taskFilter     = $request->query('tasks');

        if ($tab === 'Pto') {
            $ptoQuery = \App\Models\TimeOffRequest::with(['employee', 'policy'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_at', [$startDate, $endDate])
                      ->orWhereBetween('end_at', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_at', '<=', $startDate)
                             ->where('end_at', '>=', $endDate);
                      });
                })
                ->orderBy('start_at', 'asc');

            if ($accessibleIds !== null) {
                $ptoQuery->whereIn('employee_id', $accessibleIds);
            }

            if ($employeeFilter) {
                $empIds = is_array($employeeFilter) ? $employeeFilter : explode(',', $employeeFilter);
                $ptoQuery->whereIn('employee_id', array_filter($empIds));
            }

            $requests = $ptoQuery->get();
            $reportData = ReportDataService::build($tab, $requests, $startDate, $endDate);
        } elseif ($tab === 'TimeSheetAlerts') {
            $alertQuery = TimeLog::with(['employee', 'assignment', 'task'])
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->whereNull('clock_out')
                      ->orWhere('is_unusual', true)
                      ->orWhere('location_flagged', true)
                      ->orWhere('action_requested', true);
                })
                ->orderBy('clock_in', 'asc');

            if ($accessibleIds !== null) {
                $alertQuery->whereIn('employee_id', $accessibleIds);
            }

            if ($employeeFilter) {
                $empIds = is_array($employeeFilter) ? $employeeFilter : explode(',', $employeeFilter);
                $alertQuery->whereIn('employee_id', array_filter($empIds));
            }

            if ($jobFilter) {
                $jobIds = is_array($jobFilter) ? $jobFilter : explode(',', $jobFilter);
                $alertQuery->whereIn('assignment_id', array_filter($jobIds));
            }

            if ($taskFilter) {
                $taskIds = is_array($taskFilter) ? $taskFilter : explode(',', $taskFilter);
                $alertQuery->whereIn('task_id', array_filter($taskIds));
            }

            $logs = $alertQuery->get();
            $reportData = ReportDataService::build($tab, $logs, $startDate, $endDate);
        } else {
            $query = TimeLog::with(['employee', 'assignment', 'task'])
                ->whereNotNull('clock_out')
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->orderBy('clock_in', 'asc');

            if ($accessibleIds !== null) {
                $query->whereIn('employee_id', $accessibleIds);
            }

            if ($employeeFilter) {
                $empIds = is_array($employeeFilter) ? $employeeFilter : explode(',', $employeeFilter);
                $query->whereIn('employee_id', array_filter($empIds));
            }

            if ($jobFilter) {
                $jobIds = is_array($jobFilter) ? $jobFilter : explode(',', $jobFilter);
                $query->whereIn('assignment_id', array_filter($jobIds));
            }

            if ($taskFilter) {
                $taskIds = is_array($taskFilter) ? $taskFilter : explode(',', $taskFilter);
                $query->whereIn('task_id', array_filter($taskIds));
            }

            $logs = $query->get();
            $reportData = ReportDataService::build($tab, $logs, $startDate, $endDate);
        }
        $reportData['orientation'] = $orientation;

        return view('reports.print-template', $reportData);
    }
}
