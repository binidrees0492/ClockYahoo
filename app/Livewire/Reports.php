<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Reports extends Component
{
    public $currentTab = 'QuickSummary';
    
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
    public $choicesAlert = false; // Only unexpected responses
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
        session()->flash('message', "Generating Portrait PDF for {$this->currentTab}...");
    }

    public function viewReportLandscape()
    {
        $this->validateDates();
        session()->flash('message', "Generating Landscape PDF for {$this->currentTab}...");
    }

    public function downloadCSV()
    {
        $this->validateDates();
        session()->flash('message', "Exporting CSV for {$this->currentTab}...");
    }

    private function validateDates()
    {
        // Only validate dates if the current tab requires them (most do, but Collection does not use date ranges)
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
            // Fetch live data for the dropdowns
            'employeesList' => User::where('is_active', true)->orderBy('last_name')->get(),
            
            // Placeholders for related entities. Create these models as needed.
            'jobsList' => DB::table('tbl_assignments')->get() ?? [], 
            'customersList' => DB::table('customers')->get() ?? [],
            'tasksList' => DB::table('tbl_tasks')->get() ?? [],
        ]);
    }
}