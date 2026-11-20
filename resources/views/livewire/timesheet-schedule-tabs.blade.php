<div class="border border-gray-200 rounded-lg bg-white shadow-sm overflow-hidden">

    {{-- TABS HEADER & HR EMPLOYEE FILTER --}}
    <div class="flex flex-wrap items-center justify-between border-b border-gray-200 bg-gray-50/70 px-2">
        <div class="flex">
            <button wire:click="setTab('timesheet')"
                    class="px-5 py-3 text-sm font-semibold transition border-b-2 {{ $tab === 'timesheet' ? 'border-[#0077c8] text-[#0077c8] bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                My Timesheet
            </button>
            <button wire:click="setTab('schedule')"
                    class="px-5 py-3 text-sm font-semibold transition border-b-2 {{ $tab === 'schedule' ? 'border-[#0077c8] text-[#0077c8] bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                My Schedule
            </button>
            <button wire:click="setTab('timeoff')"
                    class="px-5 py-3 text-sm font-semibold transition border-b-2 {{ $tab === 'timeoff' ? 'border-[#0077c8] text-[#0077c8] bg-white' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                My Time Off
            </button>
        </div>

        {{-- Filter Stats Bar: Employee, Department, Job, and Task --}}
        <div class="flex flex-wrap items-center gap-1.5 py-1.5 px-2">
            <span class="text-xs text-gray-500 font-bold flex items-center gap-1">
                <i class="fa-solid fa-filter text-gray-400"></i> Filters:
            </span>

            {{-- Employee Filter --}}
            @if($isManagerOrHr && $employees->count() > 1)
                <select wire:change="setEmployee($event.target.value)"
                        class="text-xs font-semibold text-gray-800 bg-white border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-cs-blue focus:border-cs-blue cursor-pointer">
                    <option value="all" @selected($selectedEmployeeId === 'all')>
                        👥 All Employees
                    </option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected((string)$emp->id === (string)$selectedEmployeeId)>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- Department Filter --}}
            @if($departments->isNotEmpty())
                <select wire:change="setDepartment($event.target.value)"
                        class="text-xs font-semibold text-gray-800 bg-white border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-cs-blue focus:border-cs-blue cursor-pointer">
                    <option value="all" @selected($selectedDepartmentId === 'all')>
                        🏢 All Depts
                    </option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected((string)$dept->id === (string)$selectedDepartmentId)>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- Job Filter --}}
            @if($jobs->isNotEmpty())
                <select wire:change="setJob($event.target.value)"
                        class="text-xs font-semibold text-gray-800 bg-white border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-cs-blue focus:border-cs-blue cursor-pointer">
                    <option value="all" @selected($selectedJobId === 'all')>
                        💼 All Jobs
                    </option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected((string)$j->id === (string)$selectedJobId)>
                            {{ $j->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- Task Filter --}}
            @if($tasks->isNotEmpty())
                <select wire:change="setTask($event.target.value)"
                        class="text-xs font-semibold text-gray-800 bg-white border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-cs-blue focus:border-cs-blue cursor-pointer">
                    <option value="all" @selected($selectedTaskId === 'all')>
                        📋 All Tasks
                    </option>
                    @foreach($tasks as $t)
                        <option value="{{ $t->id }}" @selected((string)$t->id === (string)$selectedTaskId)>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if($selectedDepartmentId !== 'all' || $selectedJobId !== 'all' || $selectedTaskId !== 'all' || ($selectedEmployeeId && $selectedEmployeeId !== Auth::id()))
                <button type="button" wire:click="resetFilters" class="text-[11px] text-rose-600 hover:text-rose-800 font-bold px-1 py-0.5" title="Reset all filters">
                    ✕ Reset
                </button>
            @endif
        </div>
    </div>

    {{-- PANEL CONTENT --}}
    <div class="p-4">
        @if($tab === 'timesheet')
            <livewire:timesheets :selected-employee-id="$selectedEmployeeId" :key="'ts-' . $selectedEmployeeId . '-' . now()->timestamp" />
        @elseif($tab === 'schedule')
            <livewire:schedule-calendar :selected-employee-id="auth()->id()" :is-my-schedule="true" :key="'sc-my-' . auth()->id() . '-' . now()->timestamp" />
        @elseif($tab === 'timeoff')
            {{-- MY TIME OFF CONTENT --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-sky-50/50 border border-sky-200 rounded-lg">
                        <span class="text-xs font-semibold text-sky-800 uppercase tracking-wider">Vacation Leave</span>
                        <div class="text-2xl font-bold text-sky-900 mt-1">40.0 <span class="text-xs font-normal text-gray-500">hrs available</span></div>
                    </div>
                    <div class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-lg">
                        <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Sick Leave</span>
                        <div class="text-2xl font-bold text-emerald-900 mt-1">24.0 <span class="text-xs font-normal text-gray-500">hrs available</span></div>
                    </div>
                    <div class="p-4 bg-purple-50/50 border border-purple-200 rounded-lg">
                        <span class="text-xs font-semibold text-purple-800 uppercase tracking-wider">Paid Time Off (Used)</span>
                        <div class="text-2xl font-bold text-purple-900 mt-1">0.0 <span class="text-xs font-normal text-gray-500">hrs this year</span></div>
                    </div>
                </div>

                <div class="p-6 text-center text-gray-500 text-xs bg-gray-50 border border-dashed border-gray-200 rounded-lg">
                    No time off requests pending for this pay period.
                </div>
            </div>
        @endif
    </div>
</div>
