<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\TimeLog;
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
        $this->startDate = now()->startOfWeek()->format('Y-m-d');
        $this->endDate = now()->endOfWeek()->format('Y-m-d');
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
        $url = route('reports.print', [
            'tab' => $this->currentTab,
            'orientation' => 'portrait',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        $this->js("window.open('{$url}', '_blank');");
    }

    public function viewReportLandscape()
    {
        $this->validateDates();
        $url = route('reports.print', [
            'tab' => $this->currentTab,
            'orientation' => 'landscape',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        $this->js("window.open('{$url}', '_blank');");
    }

    public function downloadCSV()
    {
        $this->validateDates();

        $filename = strtolower($this->currentTab) . '-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            if (in_array($this->currentTab, ['QuickSummary', 'JobDetail', 'EmployeeDetail', 'CsvExport', 'TimeSheet'])) {
                fputcsv($file, ['Employee Name', 'Job / Assignment', 'Task', 'Clock In', 'Clock Out', 'Net Hours', 'Status', 'Notes']);

                $query = TimeLog::with(['employee', 'assignment', 'task'])
                    ->whereNotNull('clock_out');

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

                $query->chunk(100, function($logs) use ($file) {
                    foreach ($logs as $log) {
                        $hours = number_format(($log->duration_minutes ?: ceil($log->netSeconds() / 60)) / 60, 2);
                        fputcsv($file, [
                            $log->employee->name ?? 'Unknown',
                            $log->assignment->name ?? 'None',
                            $log->task->name ?? 'None',
                            $log->clock_in?->format('Y-m-d H:i:s'),
                            $log->clock_out?->format('Y-m-d H:i:s'),
                            $hours,
                            ucfirst($log->status),
                            $log->notes ?? ''
                        ]);
                    }
                });
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
        return view('livewire.reports', [
            'employeesList' => User::orderBy('name')->get(),
            'jobsList' => DB::table('tbl_assignments')->orderBy('name')->get(),
            'customersList' => Schema::hasTable('customers') ? DB::table('customers')->orderBy('name')->get() : [],
            'tasksList' => DB::table('tbl_tasks')->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
