<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Reports</h1>

    {{-- STATE 1: Report Index Grid (Shown when no active tab selected) --}}
    @if(!$currentTab)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Time Tracking Group --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Time Tracking</h3>
                <ul class="space-y-4">
                    <li>
                        <button wire:click="setTab('QuickSummary')" class="text-left font-semibold text-cs-blue hover:underline">Quick Summary</button>
                        <p class="text-xs text-gray-600 mt-1">Daily hours and weekly totals per employee within a selected week.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('JobDetail')" class="text-left font-semibold text-cs-blue hover:underline">Job Details</button>
                        <p class="text-xs text-gray-600 mt-1">Clock in, clock out, and break times for employees categorized by job.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('JobSummary')" class="text-left font-semibold text-cs-blue hover:underline">Job Summary</button>
                        <p class="text-xs text-gray-600 mt-1">Daily total hours for each job within a selected week.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('EmployeeDetail')" class="text-left font-semibold text-cs-blue hover:underline">Employee Details</button>
                        <p class="text-xs text-gray-600 mt-1">Clock in, clock out, and break times for employees categorized by employee.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('EmployeeSummary')" class="text-left font-semibold text-cs-blue hover:underline">Employee Summary</button>
                        <p class="text-xs text-gray-600 mt-1">Daily hours and weekly totals per employee.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('TaskDetail')" class="text-left font-semibold text-cs-blue hover:underline">Task Details</button>
                        <p class="text-xs text-gray-600 mt-1">Clock in, clock out, and break times for employees categorized by task.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('TaskSummary')" class="text-left font-semibold text-cs-blue hover:underline">Task Summary</button>
                        <p class="text-xs text-gray-600 mt-1">Daily total hours for each task within a selected week.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('TimeSheet')" class="text-left font-semibold text-cs-blue hover:underline">Timesheets</button>
                        <p class="text-xs text-gray-600 mt-1">Employee hours and Paid Time Off broken down by day and week.</p>
                    </li>
                </ul>
            </div>

            {{-- Time Off & Compliance Group --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Time Off & Exceptions</h3>
                <ul class="space-y-4">
                    <li>
                        <button wire:click="setTab('Pto')" class="text-left font-semibold text-cs-blue hover:underline">Time Off</button>
                        <p class="text-xs text-gray-600 mt-1">Request details and policy totals grouped by employee.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('TimeSheetAlerts')" class="text-left font-semibold text-cs-blue hover:underline">Timesheet Alerts</button>
                        <p class="text-xs text-gray-600 mt-1">A summary of Timesheet Alerts including detailed breakdowns.</p>
                    </li>
                </ul>
            </div>

            {{-- Financial Group --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Financial</h3>
                <ul class="space-y-4">
                    <li>
                        <button wire:click="setTab('Quote')" class="text-left font-semibold text-cs-blue hover:underline">Quotes</button>
                        <p class="text-xs text-gray-600 mt-1">Contact information and amounts due for quotes.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('Invoice')" class="text-left font-semibold text-cs-blue hover:underline">Invoices</button>
                        <p class="text-xs text-gray-600 mt-1">Contact information and amounts due for invoices.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('Collection')" class="text-left font-semibold text-cs-blue hover:underline">Collection</button>
                        <p class="text-xs text-gray-600 mt-1">Contact information and amounts due for past due invoices.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('Tax')" class="text-left font-semibold text-cs-blue hover:underline">Tax Accruals</button>
                        <p class="text-xs text-gray-600 mt-1">Total taxable income and total taxed amount for each tax rate.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('Payment')" class="text-left font-semibold text-cs-blue hover:underline">Payments</button>
                        <p class="text-xs text-gray-600 mt-1">Details of all payments received in a selected date range.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('InvoiceLabor')" class="text-left font-semibold text-cs-blue hover:underline">Profit vs. Labor</button>
                        <p class="text-xs text-gray-600 mt-1">Total income and income by customer calculated by invoiced amount.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('PayRate')" class="text-left font-semibold text-cs-blue hover:underline">Pay Rates</button>
                        <p class="text-xs text-gray-600 mt-1">Labor costs per shift broken down by Regular, Overtime, and Double Time.</p>
                    </li>
                </ul>
            </div>

            {{-- Export & Misc Group --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm md:col-span-2 lg:col-span-3">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Export & Misc</h3>
                <ul class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <li>
                        <button wire:click="setTab('CsvExport')" class="text-left font-semibold text-cs-blue hover:underline">CSV Export</button>
                        <p class="text-xs text-gray-600 mt-1">Details every data point collected for each shift.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('EmployeeMileage')" class="text-left font-semibold text-cs-blue hover:underline">Employee Mileage</button>
                        <p class="text-xs text-gray-600 mt-1">A breakdown of miles driven by each employee.</p>
                    </li>
                    <li>
                        <button wire:click="setTab('JobMileage')" class="text-left font-semibold text-cs-blue hover:underline">Job Mileage</button>
                        <p class="text-xs text-gray-600 mt-1">Total miles logged against specific jobs.</p>
                    </li>
                </ul>
            </div>

        </div>
    @else
        {{-- STATE 2: Active Filter / Parameter Panel (When a report is selected) --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">

            <div class="flex items-center justify-between mb-6 border-b pb-3">
                <button wire:click="$set('currentTab', null)" class="text-sm text-cs-blue hover:underline flex items-center font-semibold">
                    &larr; Back to All Reports
                </button>
                <h2 class="text-xl font-bold text-gray-900">
                    {{ preg_replace('/(?<!\ )[A-Z]/', ' $0', $currentTab) }} Report
                </h2>
            </div>

            @if (session()->has('message'))
                <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="space-y-6 max-w-2xl">
                <!-- Standard Date Range -->
                @if($currentTab !== 'Collection')
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md border border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="startDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date (Max 365 Days)</label>
                            <input type="date" wire:model="endDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                @endif

                <!-- Quick Summary Specifics -->
                @if($currentTab === 'QuickSummary')
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Summary Of</label>
                            <select wire:model="summaryOf" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Select Summary Type...</option>
                                <option value="jobs">Jobs</option>
                                <option value="employees">Employees</option>
                                <option value="tasks">Tasks</option>
                            </select>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" wire:model.live="includeBreakdown" class="rounded border-gray-300 text-blue-600">
                            <label class="ml-2 block text-sm text-gray-900 font-medium">Include Breakdown</label>
                        </div>
                        @if($includeBreakdown)
                            <select wire:model="breakdownSelect" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Select Breakdown...</option>
                                <option value="day">By Day</option>
                                <option value="week">By Week</option>
                            </select>
                        @endif
                    </div>
                @endif

                <!-- Employee Filter Block -->
                @if(in_array($currentTab, ['QuickSummary', 'JobDetail', 'JobSummary', 'EmployeeDetail', 'EmployeeSummary', 'TaskDetail', 'TaskSummary', 'TimeSheet', 'Pto', 'TimeSheetAlerts', 'PayRate', 'CsvExport']))
                    <div class="border-t pt-4">
                        <div class="flex items-center mb-2">
                            <input type="checkbox" wire:model.live="filterEmployees" class="rounded border-gray-300 text-blue-600">
                            <label class="ml-2 block text-sm font-medium text-gray-900">Filter Employees</label>
                        </div>
                        @if($filterEmployees)
                            <select multiple wire:model="selectedEmployees" class="block w-full border-gray-300 rounded-md shadow-sm text-sm min-h-[100px]">
                                @foreach($employeesList as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                        @endif
                    </div>
                @endif

                <!-- Job Filter Block -->
                @if(in_array($currentTab, ['QuickSummary', 'JobDetail', 'TimeSheetAlerts', 'PayRate']))
                    <div class="border-t pt-4">
                        <div class="flex items-center mb-2">
                            <input type="checkbox" wire:model.live="filterJobs" class="rounded border-gray-300 text-blue-600">
                            <label class="ml-2 block text-sm font-medium text-gray-900">Filter Jobs</label>
                        </div>
                        @if($filterJobs)
                            <select multiple wire:model="selectedJobs" class="block w-full border-gray-300 rounded-md shadow-sm text-sm min-h-[100px]">
                                @foreach($jobsList as $job)
                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                        @endif
                    </div>
                @endif

                <!-- Task Filter Block -->
                @if(in_array($currentTab, ['QuickSummary', 'TaskDetail', 'PayRate']))
                    <div class="border-t pt-4">
                        <div class="flex items-center mb-2">
                            <input type="checkbox" wire:model.live="filterTasks" class="rounded border-gray-300 text-blue-600">
                            <label class="ml-2 block text-sm font-medium text-gray-900">Filter Tasks</label>
                        </div>
                        @if($filterTasks)
                            <select multiple wire:model="selectedTasks" class="block w-full border-gray-300 rounded-md shadow-sm text-sm min-h-[100px]">
                                @foreach($tasksList as $task)
                                    <option value="{{ $task->id }}">{{ $task->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                        @endif
                    </div>
                @endif

                <!-- Submit Actions -->
                <div class="pt-6 border-t flex space-x-3">
                    @if($currentTab === 'CsvExport')
                        <button wire:click="downloadCSV" class="px-4 py-2 bg-cs-blue text-white rounded-md hover:bg-blue-800 shadow-sm text-sm font-semibold">
                            Download CSV
                        </button>
                    @else
                        <button wire:click="viewReportPortrait" class="px-4 py-2 bg-cs-blue text-white rounded-md hover:bg-blue-800 shadow-sm text-sm font-semibold">
                            View PDF Portrait
                        </button>
                        <button wire:click="viewReportLandscape" class="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-200 text-sm font-semibold">
                            View PDF Landscape
                        </button>
                        <button wire:click="downloadCSV" class="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-200 text-sm font-semibold">
                            Download CSV
                        </button>
                    @endif
                </div>
            </div>

        </div>
    @endif
</div>
