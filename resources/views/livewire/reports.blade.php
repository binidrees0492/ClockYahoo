<div class="bg-white shadow-sm sm:rounded-lg flex flex-col md:flex-row overflow-hidden min-h-[700px]">
    
    <!-- Left Sidebar: Report Categories -->
    <div class="w-full md:w-1/4 bg-gray-50 border-r border-gray-200 overflow-y-auto">
        <nav class="flex flex-col space-y-1 p-4">
            
            <h3 class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Time & Labor</h3>
            <button wire:click="setTab('QuickSummary')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'QuickSummary' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Quick Summary</button>
            <button wire:click="setTab('JobDetail')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'JobDetail' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Job Detail</button>
            <button wire:click="setTab('JobSummary')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'JobSummary' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Job Summary</button>
            <button wire:click="setTab('EmployeeDetail')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'EmployeeDetail' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Employee Detail</button>
            <button wire:click="setTab('EmployeeSummary')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'EmployeeSummary' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Employee Summary</button>
            <button wire:click="setTab('TaskDetail')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'TaskDetail' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Task Detail</button>
            <button wire:click="setTab('TaskSummary')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'TaskSummary' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Task Summary</button>
            <button wire:click="setTab('TimeSheet')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'TimeSheet' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Timesheets</button>
            <button wire:click="setTab('ShiftWrapUpAndComplianceForms')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'ShiftWrapUpAndComplianceForms' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Attestation</button>

            <h3 class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Time Off & Exceptions</h3>
            <button wire:click="setTab('Pto')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Pto' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Time Off</button>
            <button wire:click="setTab('TimeSheetAlerts')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'TimeSheetAlerts' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Timesheet Alerts</button>
            
            <h3 class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Financial</h3>
            <button wire:click="setTab('Quote')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Quote' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Quotes</button>
            <button wire:click="setTab('Invoice')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Invoice' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Invoices</button>
            <button wire:click="setTab('Collection')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Collection' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Collection</button>
            <button wire:click="setTab('Tax')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Tax' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Tax Accruals</button>
            <button wire:click="setTab('Payment')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Payment' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Payments</button>
            <button wire:click="setTab('InvoiceLabor')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'InvoiceLabor' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Profit vs. Labor</button>
            <button wire:click="setTab('PayRate')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'PayRate' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Pay Rate</button>
            
            <h3 class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Export & Misc</h3>
            <button wire:click="setTab('CsvExport')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'CsvExport' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">CSV Export</button>
            <button wire:click="setTab('EmployeeMileage')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'EmployeeMileage' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Employee Mileage</button>
            <button wire:click="setTab('JobMileage')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'JobMileage' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Job Mileage</button>
            <button wire:click="setTab('Checklists')" class="text-left px-3 py-2 rounded-md text-sm font-medium {{ $currentTab === 'Checklists' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">Checklists</button>

        </nav>
    </div>

    <!-- Right Pane: Dynamic Filters -->
    <div class="w-full md:w-3/4 p-8">
        
        <div>
            @if (session()->has('message'))
                <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2 flex items-center">
            {{ preg_replace('/(?<!\ )[A-Z]/', ' $0', $currentTab) }} Report
        </h2>

        <div class="space-y-6 max-w-3xl">
            
            <!-- Standard Date Range (Hidden only for Collection) -->
            @if($currentTab !== 'Collection')
                <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md border border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" wire:model="startDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date (Max 365 Days)</label>
                        <input type="date" wire:model="endDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            @endif

            <!-- Quick Summary Specifics -->
            @if($currentTab === 'QuickSummary')
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Summary Of</label>
                    <select wire:model="summaryOf" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mb-4">
                        <option value="">Select Summary Type...</option>
                        <option value="jobs">Jobs</option>
                        <option value="employees">Employees</option>
                        <option value="tasks">Tasks</option>
                    </select>

                    <div class="flex items-center mb-2">
                        <input type="checkbox" wire:model.live="includeBreakdown" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 block text-sm text-gray-900 font-medium">Include Breakdown</label>
                    </div>
                    
                    @if($includeBreakdown)
                        <select wire:model="breakdownSelect" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mt-2">
                            <option value="">Select Breakdown...</option>
                            <option value="day">By Day</option>
                            <option value="week">By Week</option>
                        </select>
                    @endif
                </div>
            @endif

            <!-- Financial Specifics (Quotes/Invoices) -->
            @if(in_array($currentTab, ['Quote', 'Invoice']))
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $currentTab }}s By:</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" wire:model="qipGroupBy" value="ByDateIssued" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label class="ml-3 block text-sm font-medium text-gray-700">Date Issued</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" wire:model="qipGroupBy" value="ByDateCreated" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label class="ml-3 block text-sm font-medium text-gray-700">Date Created</label>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Time Off Specifics -->
            @if($currentTab === 'Pto')
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time Off Status</label>
                    <select wire:model="timeOffStatus" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="All">All Statuses</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                        <option value="Denied">Denied</option>
                    </select>
                </div>
            @endif

            <!-- Collection Specifics -->
            @if($currentTab === 'Collection')
                <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md border border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Past Due As Of</label>
                        <input type="date" wire:model="collectionsDueDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">By At Least (Days)</label>
                        <input type="number" wire:model="pastDueByAtLeast" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            @endif

            <!-- Tax Specifics -->
            @if($currentTab === 'Tax')
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" wire:model="taxReportType" value="SalesTaxCash" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label class="ml-3 block text-sm font-medium text-gray-700">Cash</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" wire:model="taxReportType" value="SalesTaxAccrual" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label class="ml-3 block text-sm font-medium text-gray-700">Accrual</label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Cash report includes paid invoices. Accrual includes sent, awaiting, past due, and paid.</p>
                </div>
            @endif

            <!-- Employee Filter Block -->
            @if(in_array($currentTab, ['QuickSummary', 'JobDetail', 'JobSummary', 'EmployeeDetail', 'EmployeeSummary', 'TaskDetail', 'TaskSummary', 'TimeSheet', 'ShiftWrapUpAndComplianceForms', 'Pto', 'TimeSheetAlerts', 'PayRate', 'CsvExport']))
                <div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" wire:model.live="filterEmployees" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 block text-sm font-medium text-gray-900">Filter Employees</label>
                    </div>
                    @if($filterEmployees)
                        <select multiple wire:model="selectedEmployees" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 min-h-[100px]">
                            @foreach($employeesList as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->last_name }}, {{ $emp->first_name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                    @endif
                </div>
            @endif

            <!-- Job Filter Block -->
            @if(in_array($currentTab, ['QuickSummary', 'JobDetail', 'ShiftWrapUpAndComplianceForms', 'TimeSheetAlerts', 'PayRate']))
                <div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" wire:model.live="filterJobs" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 block text-sm font-medium text-gray-900">Filter Jobs</label>
                    </div>
                    @if($filterJobs)
                        <select multiple wire:model="selectedJobs" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 min-h-[100px]">
                            <option disabled>Available Jobs Here</option>
                        </select>
                    @endif
                </div>
            @endif

            <!-- Customer Filter Block -->
            @if(in_array($currentTab, ['Quote', 'Invoice', 'Collection', 'Payment', 'InvoiceLabor']))
                <div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" wire:model.live="filterCustomers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 block text-sm font-medium text-gray-900">Filter Customers</label>
                    </div>
                    @if($filterCustomers)
                        <select multiple wire:model="selectedCustomers" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 min-h-[100px]">
                            <option disabled>Available Customers Here</option>
                        </select>
                    @endif
                </div>
            @endif

            <!-- Task Filter Block -->
            @if(in_array($currentTab, ['QuickSummary', 'TaskDetail', 'ShiftWrapUpAndComplianceForms', 'PayRate']))
                <div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" wire:model.live="filterTasks" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label class="ml-2 block text-sm font-medium text-gray-900">Filter Tasks</label>
                    </div>
                    @if($filterTasks)
                        <select multiple wire:model="selectedTasks" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 min-h-[100px]">
                            <option disabled>Available Tasks Here</option>
                        </select>
                    @endif
                </div>
            @endif

            <!-- Include Notes Option -->
            @if(in_array($currentTab, ['JobDetail', 'EmployeeDetail', 'TaskDetail', 'CsvExport']))
                <div class="flex items-center mt-4">
                    <input type="checkbox" wire:model="includeNotes" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 block text-sm font-medium text-gray-900">Include Timesheet Notes</label>
                </div>
            @endif

            <!-- Submit Actions -->
            <div class="pt-6 border-t flex space-x-3">
                
                @if($currentTab === 'CsvExport')
                    <!-- CSV Download is the primary action for CsvExport tab -->
                    <button wire:click="downloadCSV" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-sm font-medium">
                        Download CSV
                    </button>
                @else
                    <!-- View / Dropdown actions for standard tabs -->
                    <div class="relative group">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-sm font-medium inline-flex items-center">
                            View Report
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden group-hover:block z-10">
                            <div class="py-1">
                                <button wire:click="viewReportPortrait" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">PDF Portrait</button>
                                <button wire:click="viewReportLandscape" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">PDF Landscape</button>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 border border-gray-300 font-medium inline-flex items-center">
                            Download
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden group-hover:block z-10">
                            <div class="py-1">
                                <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">PDF Portrait</button>
                                <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">PDF Landscape</button>
                                <button wire:click="downloadCSV" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t mt-1">CSV Format</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>