<div>
    @if($toastMessage)
        <div x-data x-init="window.showToast(@js($toastMessage), @js($toastType ?? 'success'))" class="hidden"></div>
    @endif

    {{-- TOP ROW: TITLE + BUTTONS --}}
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-4xl font-light text-gray-800">Schedules</h1>
            @if(!auth()->user()->canEditSchedules())
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">Read-Only Mode</span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->canEditSchedules())
                <button wire:click="openAddShift"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
                    Add Shift
                </button>
                <button wire:click="publishWeek"
                        onclick="return confirm('Publish all draft shifts for this week? Once published, employees will be able to view their schedules and log time.') || event.stopImmediatePropagation()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm font-semibold flex items-center gap-1.5 shadow-sm transition"
                        title="Publish all draft shifts for this week">
                    <span>🚀 Publish Entire Week Schedule</span>
                    @if($draftShiftsCount > 0)
                        <span class="bg-emerald-800/80 text-white text-[11px] px-1.5 py-0.2 rounded-full font-bold">({{ $draftShiftsCount }})</span>
                    @endif
                </button>
                @if($publishedShiftsCount > 0)
                    <button wire:click="revertWeekToDraft"
                            onclick="return confirm('Revert all published shifts for this week back to draft? Shifts will become hidden from standard employees.') || event.stopImmediatePropagation()"
                            class="bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 px-3 py-2 rounded text-sm font-semibold flex items-center gap-1.5 shadow-sm transition"
                            title="Revert published shifts for this week back to draft">
                        <span>↩ Revert Week to Draft</span>
                        <span class="bg-amber-200 text-amber-900 text-[11px] px-1.5 py-0.2 rounded-full font-bold">({{ $publishedShiftsCount }})</span>
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- TOOLBAR --}}
    <div class="flex justify-between items-center mb-3">
        <div class="flex items-center gap-4">
            <button wire:click="previousWeek" class="text-gray-500 hover:text-gray-800 text-lg">&#9664;</button>
            <button wire:click="nextWeek"     class="text-gray-500 hover:text-gray-800 text-lg">&#9654;</button>

            {{-- Date Range Dropdown Trigger --}}
            <div class="relative">
                <button type="button" wire:click="toggleDatePicker"
                        class="text-lg font-semibold text-gray-800 flex items-center gap-1.5 hover:text-[#005a9c] transition focus:outline-none cursor-pointer">
                    <span>{{ $weekOffset === 0 ? 'This Week' : ($weekOffset === -1 ? 'Last Week' : ($weekOffset === 1 ? 'Next Week' : abs($weekOffset) . ' Wk ' . ($weekOffset < 0 ? 'Ago' : 'Later'))) }}</span>
                    <span class="font-normal text-gray-500 text-base">
                        {{ ucfirst($startOfWeek->format('M j')) }} &ndash; {{ ucfirst($endOfWeek->format('M j, Y')) }}
                    </span>
                    <span class="text-[10px] text-gray-500 hover:text-gray-800 transition">&#9660;</span>
                </button>

                {{-- DUAL-PANEL DATE PICKER POPUP --}}
                @if($showDatePicker)
                    <div class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-2xl p-4 flex flex-col md:flex-row gap-4 min-w-[320px] md:min-w-[490px]"
                         @click.away="$wire.closeDatePicker()">
                        {{-- LEFT PANEL: PRESETS --}}
                        <div class="w-full md:w-36 flex flex-col gap-1 border-b md:border-b-0 md:border-r border-gray-200 pb-3 md:pb-0 md:pr-3 text-xs">
                            <button type="button" wire:click="selectPreset(0)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === 0 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                This Week
                            </button>
                            <button type="button" wire:click="selectPreset(-1)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -1 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                Last Week
                            </button>
                            <button type="button" wire:click="selectPreset(-2)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -2 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                2 Weeks Ago
                            </button>
                            <button type="button" wire:click="selectPreset(-3)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -3 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                3 Weeks Ago
                            </button>
                            <button type="button" wire:click="selectPreset(1)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === 1 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                Next Week
                            </button>
                        </div>

                        {{-- RIGHT PANEL: MINI CALENDAR --}}
                        <div class="flex-1 text-xs">
                            <div class="flex items-center justify-between mb-2">
                                <button type="button" wire:click="prevMonth" class="px-2 py-1 text-gray-500 hover:text-black font-bold">&lsaquo;</button>
                                <span class="font-bold text-gray-700">{{ $calMonthTitle }}</span>
                                <button type="button" wire:click="nextMonth" class="px-2 py-1 text-gray-500 hover:text-black font-bold">&rsaquo;</button>
                            </div>

                            <table class="w-full text-center">
                                <thead>
                                <tr class="text-gray-400 font-semibold text-[10px]">
                                    @foreach(range(0,6) as $dIdx)
                                        <th class="py-1">{{ strtoupper($startOfWeek->copy()->addDays($dIdx)->format('D')[0]) }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($calendarWeeks as $week)
                                    <tr class="cursor-pointer hover:bg-blue-50/50">
                                        @foreach($week as $dayItem)
                                            <td wire:click="selectDateFromCalendar('{{ $dayItem['date'] }}')"
                                                class="py-1.5 rounded transition text-center
                                                    {{ $dayItem['is_selected'] ? 'bg-[#0077c8] text-white font-bold' : ($dayItem['is_current_month'] ? 'text-gray-700 hover:bg-blue-100' : 'text-gray-300') }}
                                                    {{ $dayItem['is_today'] && !$dayItem['is_selected'] ? 'ring-1 ring-[#0077c8] font-bold' : '' }}">
                                                {{ $dayItem['day'] }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <button wire:click="thisWeek" class="text-xs text-cs-blue underline">Reset</button>
        </div>

        <div class="flex items-center gap-3">
            {{-- Share --}}
            <button type="button" class="text-gray-500 hover:text-gray-800 text-lg" title="Share">&#128257;</button>
            {{-- Calendar icon --}}
            <button type="button" class="text-gray-500 hover:text-gray-800 text-lg" title="Calendar">&#128197;</button>
            {{-- Print --}}
            <button type="button" onclick="window.print()" class="text-gray-500 hover:text-gray-800 text-lg" title="Print">&#128424;</button>

            {{-- Week dropdown (label only for now) --}}
            <button type="button" class="text-sm text-gray-700 flex items-center gap-1">
                Week <span class="text-[9px]">&#9660;</span>
            </button>

            {{-- Employee Filter for HR, Managers (their dept & own view), and Super Admins --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isManager())
                <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded px-2.5 py-1 text-xs">
                    <span class="text-gray-500 font-semibold">Employee:</span>
                    <select wire:model.live="employeeFilter"
                            class="bg-transparent font-bold text-gray-800 focus:outline-none cursor-pointer text-xs">
                        <option value="">All Team Members</option>
                        @foreach($filterEmployees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Status Filter for Admin --}}
            <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded px-2.5 py-1 text-xs">
                <span class="text-gray-500 font-semibold">Status:</span>
                <select wire:model.live="statusFilter" class="bg-transparent font-bold text-gray-800 focus:outline-none cursor-pointer">
                    <option value="ongoing">⚡ Ongoing Only</option>
                    <option value="all">All Statuses</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="in progress">In Progress</option>
                    <option value="active">Active</option>
                    <option value="on hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    {{-- MAIN GRID --}}
    <div class="border border-gray-300 bg-white">
        <div class="flex">

            {{-- LEFT SIDEBAR — ACTIVE JOBS & TASKS --}}
            <div class="w-[230px] shrink-0 border-r border-gray-300 divide-y divide-gray-200 bg-gray-50/40">
                {{-- Jobs Section --}}
                <div class="p-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-gray-700">Jobs ({{ $jobs->count() }})</span>
                        @if(auth()->user()->canEdit('jobs'))
                            <a href="{{ route('work.jobs') }}" wire:navigate class="text-xs text-cs-blue hover:text-blue-800 font-bold" title="Manage Jobs in Jobs module">+ Add Job</a>
                        @endif
                    </div>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        @forelse($jobs as $j)
                            <div class="flex items-center justify-between text-xs text-gray-800 p-1 rounded hover:bg-white transition border border-transparent hover:border-gray-200">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="inline-block w-2 h-2 rounded-full bg-cs-blue shrink-0"></span>
                                    <span class="font-medium truncate" title="{{ $j->name }}">{{ $j->name }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-[11px] text-gray-400 italic">No jobs matching filter.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Tasks Section --}}
                <div class="p-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-gray-700">Tasks ({{ $tasks->count() }})</span>
                        @if(auth()->user()->canEdit('tasks'))
                            <a href="{{ route('work.tasks') }}" wire:navigate class="text-xs text-cs-blue hover:text-blue-800 font-bold" title="Manage Tasks in Tasks module">+ Add Task</a>
                        @endif
                    </div>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        @forelse($tasks as $t)
                            <div class="flex items-center justify-between text-xs text-gray-800 p-1 rounded hover:bg-white transition border border-transparent hover:border-gray-200">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="font-medium truncate" title="{{ $t->name }}">{{ $t->name }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-[11px] text-gray-400 italic">No tasks matching filter.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- RIGHT: JOB/TASK + EMPLOYEE + DAYS GRID --}}
            <div class="flex-1 overflow-x-auto">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 210px;">
                        <col style="width: 170px;">
                        @foreach(range(0,6) as $i)
                            <col style="width: 140px;">
                        @endforeach
                    </colgroup>

                    <thead>
                    <tr class="border-b border-gray-300 bg-gray-50/80">
                        <th class="text-left px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-gray-700 border-r border-gray-300">
                            Task / Job
                        </th>
                        <th class="text-left px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-gray-700 border-r border-gray-300">
                            Employee
                        </th>
                        @foreach($days as $i => $d)
                            <th class="text-left px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-gray-700 border-r border-gray-300
                                    {{ $d->toDateString() === $todayKey ? 'bg-cs-today text-[#005a9c]' : '' }}">
                                <div class="font-bold">{{ strtoupper($d->format('D')) }}</div>
                                <div class="text-[10px] font-normal text-gray-500 lowercase">{{ $d->month }}/{{ $d->day }}</div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($groupedRows as $row)
                        <tr class="border-b border-gray-300 transition {{ $loop->even ? 'bg-slate-50/70 hover:bg-sky-50/40' : 'bg-white hover:bg-sky-50/30' }}">
                            {{-- 1. LEFT-MOST COLUMN: TASK / JOB --}}
                            <td class="px-3 py-2.5 border-r border-gray-300 align-top {{ $loop->even ? 'bg-slate-50/70' : 'bg-white' }}">
                                <div class="space-y-1">
                                    @php
                                        $jobTitle = $row['job'] ? $row['job']->name : 'No Job Assigned';
                                        $taskTitle = $row['task'] ? $row['task']->name : 'General Tasks';
                                    @endphp
                                    {{-- Job Title (Line 1) --}}
                                    <div class="flex items-center gap-1.5 min-w-0" title="{{ $jobTitle }}">
                                        <span class="text-sm shrink-0">💼</span>
                                        <span class="font-bold text-gray-900 leading-snug">{{ $jobTitle }}</span>
                                    </div>

                                    {{-- Task Sub-tagline (Line 2) --}}
                                    <div class="pl-5 text-xs text-cs-blue font-semibold leading-tight" title="{{ $taskTitle }}">
                                        ({{ $taskTitle }})
                                        @if($row['task'] && $row['task']->hourly_rate)
                                            <span class="text-[10px] text-gray-400 font-normal ml-1">(${{ number_format($row['task']->hourly_rate, 2) }}/h)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- 2. EMPLOYEE COLUMN --}}
                            <td class="px-2 py-2 border-r border-gray-300 align-middle text-center {{ $loop->even ? 'bg-slate-100/60' : 'bg-gray-50/50' }}">
                                <div class="flex flex-col items-center justify-center gap-1 w-full max-w-[120px] mx-auto overflow-hidden">
                                    @if($row['employee']->avatar_url)
                                        <img src="{{ $row['employee']->avatar_url }}" alt="{{ $row['employee']->name }}" class="w-7 h-7 rounded-full object-cover ring-1 ring-sky-400 shadow-2xs shrink-0" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-slate-700 to-slate-900 text-white items-center justify-center text-[9px] font-bold ring-1 ring-sky-400/40 shadow-2xs shrink-0 hidden">
                                            {{ $row['employee']->initials }}
                                        </div>
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-slate-700 to-slate-900 text-white flex items-center justify-center text-[9px] font-bold ring-1 ring-sky-400/40 shadow-2xs shrink-0">
                                            {{ $row['employee']->initials }}
                                        </div>
                                    @endif
                                    <span class="text-[10px] font-medium text-gray-800 truncate block w-full text-center leading-tight capitalize" title="{{ $row['employee']->name }}">
                                        {{ ucwords(strtolower($row['employee']->name)) }}
                                    </span>
                                    <span class="inline-block px-1.5 py-0.2 bg-blue-50 border border-blue-200 text-blue-700 rounded text-[9px] font-bold">
                                        {{ \App\Services\ReportDataService::formatHoursToHM($row['total_hours']) }}
                                    </span>
                                </div>
                            </td>

                            {{-- 3-9. DAY COLUMNS (MON - SUN) --}}
                            @foreach($days as $i => $d)
                                @php
                                    $dayKey = $d->toDateString();
                                    $entries = $row['shifts'][$dayKey] ?? collect();
                                @endphp
                                <td class="px-2 py-2 border-r border-gray-300 align-top {{ $dayKey === $todayKey ? 'bg-cs-today/70' : ($loop->parent->even ? 'bg-slate-50/40' : 'bg-white') }}">
                                    @foreach($entries as $s)
                                        <div class="relative group bg-blue-50/90 border {{ $s->isDraft() ? 'border-dashed border-amber-300 bg-amber-50/50' : 'border-blue-200' }} rounded p-2 text-xs mb-1.5 shadow-2xs {{ $this->canEditScheduleFor($row['employee']->id) ? 'cursor-pointer hover:shadow-xs hover:border-cs-blue transition' : '' }}"
                                             @if($this->canEditScheduleFor($row['employee']->id)) wire:click="openEditShift({{ $s->id }})" @endif>
                                            <div class="text-gray-800 font-bold flex justify-between items-center">
                                                <span>⏱️ {{ substr($s->start_time, 0, 5) }} &ndash; {{ substr($s->end_time, 0, 5) }}</span>
                                                <div class="flex items-center gap-1">
                                                    @if($s->isDraft())
                                                        <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Draft shift (hidden from employee)">
                                                            Draft
                                                        </span>
                                                    @endif
                                                    @if($s->break_minutes)
                                                        <span class="text-[9px] text-amber-700 font-semibold bg-amber-50 px-1 py-0.2 rounded border border-amber-200" title="Break {{ $s->break_minutes }}m ({{ $s->break_start ? substr($s->break_start, 0, 5) : '' }} - {{ $s->break_end ? substr($s->break_end, 0, 5) : '' }})">
                                                            ☕ {{ $s->break_minutes }}m
                                                        </span>
                                                    @endif
                                                    <span class="text-[9px] text-gray-500 font-semibold">{{ $s->standard_hours ?: 8 }}h</span>
                                                </div>
                                            </div>
                                            @if($s->location_name)
                                                <div class="text-[10px] text-gray-600 truncate mt-0.5" title="{{ $s->location_name }}">
                                                    📍 {{ $s->location_name }}
                                                </div>
                                            @endif
                                            @if($s->notes)
                                                <div class="text-[10px] text-gray-500 truncate italic mt-0.5" title="{{ $s->notes }}">
                                                    📝 {{ $s->notes }}
                                                </div>
                                            @endif

                                            @if($this->canEditScheduleFor($row['employee']->id))
                                                <div class="mt-1.5 pt-1 border-t border-gray-200/60 flex items-center justify-between text-[10px] opacity-80 group-hover:opacity-100 transition">
                                                    <button type="button"
                                                            wire:click.stop="openCopyShift({{ $s->id }})"
                                                            class="text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-0.5 hover:underline"
                                                            title="Clone / Copy this shift to single day or date range">
                                                        <span>📋 Copy</span>
                                                    </button>
                                                    @if($s->isDraft())
                                                        <button type="button"
                                                                wire:click.stop="publishShift({{ $s->id }})"
                                                                class="text-emerald-600 hover:text-emerald-800 font-semibold flex items-center gap-0.5 hover:underline"
                                                                title="Publish this draft shift">
                                                            <span>🚀 Publish</span>
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                                wire:click.stop="revertShiftToDraft({{ $s->id }})"
                                                                class="text-amber-700 hover:text-amber-900 font-semibold flex items-center gap-0.5 hover:underline"
                                                                title="Revert this shift back to draft (hidden from employee)">
                                                            <span>↩ Revert to Draft</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($this->canEditScheduleFor($row['employee']->id))
                                        <button wire:click="openAddShift({{ $row['employee']->id }}, '{{ $dayKey }}', {{ $row['job_id'] ? $row['job_id'] : 'null' }}, {{ $row['task_id'] ? $row['task_id'] : 'null' }})"
                                                title="Add shift for {{ $row['employee']->name }} on {{ $d->format('M d') }}"
                                                class="w-full text-center text-gray-300 hover:text-cs-blue hover:bg-blue-50 rounded py-1 text-sm font-bold transition">
                                            +
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-xs text-gray-400 italic">
                                No employees or schedules found matching the current criteria.
                            </td>
                        </tr>
                    @endforelse

                    {{-- ADD EMPLOYEE ROW --}}
                    @if(auth()->user()->canAccessAdmin())
                        <tr class="border-b border-gray-300 bg-gray-50/60">
                            <td colspan="9" class="px-4 py-2.5">
                                <a href="{{ route('admin.employees') }}" wire:navigate
                                   class="text-xs font-bold text-cs-blue hover:text-blue-800 flex items-center gap-1.5 transition">
                                    <span>➕</span> Add New Employee (Manage in Staff Directory)
                                </a>
                            </td>
                        </tr>
                    @endif
                    {{-- EMPTY FILLER ROWS to match ClockShark look --}}
                    @if(count($groupedRows) < 5)
                        @foreach(range(1, max(1, 5 - count($groupedRows))) as $x)
                            <tr class="border-b border-gray-200">
                                <td class="px-3 py-6 border-r border-gray-200"></td>
                                <td class="px-3 py-6 border-r border-gray-200"></td>
                                @foreach(range(0,6) as $i)
                                    <td class="border-r border-gray-200"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ADD / EDIT SHIFT MODAL --}}
    @if($showAddShift)
        @php
            $isMultiDay = !$editingShiftId && count($this->shiftDaysBreakdown) > 1;
        @endphp
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-xl w-full {{ $isMultiDay ? 'max-w-6xl' : 'max-w-2xl' }} p-5 sm:p-6 max-h-[92vh] overflow-hidden flex flex-col my-auto transition-all duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingShiftId ? 'Edit Shift' : 'Add Shift' }}
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                            Drafted by default
                        </span>
                    </div>
                    <button wire:click="cancelShift" class="text-gray-400 hover:text-gray-700 text-xl font-bold leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-1 {{ $isMultiDay ? 'lg:grid-cols-12' : '' }} gap-5 overflow-y-auto flex-1 pr-1 text-xs">
                    {{-- Left Column: Shift Details & Location (col-span-5 if multi-day, full width if single day) --}}
                    <div class="{{ $isMultiDay ? 'lg:col-span-5' : '' }} space-y-3">
                        {{-- Employee Selection (Multi-select for new shifts, single for edit) --}}
                        <div>
                            @if($editingShiftId)
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Employee*</label>
                                <select wire:model="shiftEmployeeId" class="w-full border rounded p-2 text-xs bg-white">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-semibold text-gray-700">Assigned Employees* ({{ count($shiftEmployeeIds) }} selected)</label>
                                    <div class="flex items-center gap-2 text-[10px]">
                                        <button type="button" wire:click="selectAllEmployees" class="text-cs-blue hover:underline font-semibold">Select All</button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="clearEmployees" class="text-gray-500 hover:underline">Clear</button>
                                    </div>
                                </div>
                                <div class="max-h-28 overflow-y-auto border border-gray-300 rounded p-1.5 space-y-1 bg-white">
                                    @foreach($employees as $e)
                                        <label class="flex items-center gap-2 px-1.5 py-0.5 rounded hover:bg-blue-50 cursor-pointer text-xs">
                                            <input type="checkbox" wire:model.live="shiftEmployeeIds" value="{{ $e->id }}" class="rounded text-[#005a9c]">
                                            <span class="text-gray-800 font-medium">{{ $e->name }}</span>
                                            <span class="text-[10px] text-gray-400">({{ $e->role }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @error('shiftEmployeeIds') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            @error('shiftEmployeeId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Job & Task (Multi-select for new shifts, single for edit) --}}
                        @if($editingShiftId)
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Job (Active/Ongoing)*</label>
                                <select wire:model="shiftAssignmentId" class="w-full border rounded p-2 text-xs bg-white">
                                    <option value="">— Select Ongoing Job —</option>
                                    @foreach($assignableJobs as $j)
                                        <option value="{{ $j->id }}">{{ $j->name }} ({{ ucfirst($j->status) }})</option>
                                    @endforeach
                                </select>
                                @error('shiftAssignmentId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Task (Active/Ongoing)*</label>
                                <select wire:model.live="shiftTaskId" class="w-full border rounded p-2 text-xs bg-white">
                                    <option value="">— Select Ongoing Task —</option>
                                    @foreach($assignableTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->status) }})</option>
                                    @endforeach
                                </select>
                                @error('shiftTaskId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="space-y-2">
                                {{-- Multi-Jobs Selector --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-semibold text-gray-700">Jobs* ({{ count($shiftAssignmentIds) }} selected)</label>
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <button type="button" wire:click="selectAllJobs" class="text-cs-blue hover:underline font-semibold">Select All</button>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" wire:click="clearJobs" class="text-gray-500 hover:underline">Clear</button>
                                        </div>
                                    </div>
                                    <div class="max-h-24 overflow-y-auto border border-gray-300 rounded p-1.5 space-y-1 bg-white">
                                        @foreach($assignableJobs as $j)
                                            <label class="flex items-center gap-2 px-1.5 py-0.5 rounded hover:bg-blue-50 cursor-pointer text-xs">
                                                <input type="checkbox" wire:model.live="shiftAssignmentIds" value="{{ $j->id }}" class="rounded text-[#005a9c]">
                                                <span class="text-gray-800 font-medium truncate">{{ $j->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('shiftAssignmentIds') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    @error('shiftAssignmentId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                </div>

                                {{-- Multi-Tasks Grouped by Selected Jobs --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-semibold text-gray-700">Tasks* ({{ count($shiftTaskIds) }} selected)</label>
                                        @if(count($shiftAssignmentIds) > 0)
                                            <div class="flex items-center gap-2 text-[10px]">
                                                <button type="button" wire:click="selectAllTasks" class="text-cs-blue hover:underline font-semibold">Select All Tasks</button>
                                                <span class="text-gray-300">|</span>
                                                <button type="button" wire:click="clearTasks" class="text-gray-500 hover:underline">Clear</button>
                                            </div>
                                        @endif
                                    </div>

                                    @php
                                        $relevantJobs = count($shiftAssignmentIds) > 0 ? $assignableJobs->whereIn('id', $shiftAssignmentIds) : $assignableJobs;
                                    @endphp

                                    <div class="max-h-32 overflow-y-auto border border-gray-300 rounded p-1.5 space-y-2 bg-white">
                                        @forelse($relevantJobs as $job)
                                            @php
                                                $jobTasks = $assignableTasks->where('assignment_id', $job->id);
                                            @endphp
                                            <div class="border-b border-gray-100 last:border-0 pb-1.5 last:pb-0">
                                                <div class="flex items-center justify-between text-[11px] font-bold text-gray-700 mb-1 bg-gray-50 px-1.5 py-0.5 rounded">
                                                    <span class="truncate">📁 {{ $job->name }}</span>
                                                    @if($jobTasks->count() > 0)
                                                        <button type="button" wire:click="toggleJobTasks({{ $job->id }})" class="text-[10px] text-cs-blue hover:underline font-normal">
                                                            Toggle All
                                                        </button>
                                                    @endif
                                                </div>
                                                @forelse($jobTasks as $t)
                                                    <label class="flex items-center gap-2 px-2 py-0.5 rounded hover:bg-blue-50 cursor-pointer text-xs">
                                                        <input type="checkbox" wire:model.live="shiftTaskIds" value="{{ $t->id }}" class="rounded text-[#005a9c]">
                                                        <span class="text-gray-700 truncate">{{ $t->name }}</span>
                                                    </label>
                                                @empty
                                                    <div class="text-[10px] text-gray-400 italic px-2">No sub-tasks configured for this job</div>
                                                @endforelse
                                            </div>
                                        @empty
                                            <div class="text-gray-400 text-center py-2 text-[11px]">Select a job above to view and assign tasks</div>
                                        @endforelse
                                    </div>
                                    @error('shiftTaskIds') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    @error('shiftTaskId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Date / Date Range --}}
                        @if($editingShiftId)
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Shift Date*</label>
                                <input type="date" wire:model.live="shiftDateFrom" class="w-full border rounded p-2 text-xs bg-white">
                                @error('shiftDateFrom') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-700 block mb-1">Date From*</label>
                                        <input type="date" wire:model.live="shiftDateFrom" class="w-full border rounded p-2 text-xs bg-white">
                                        @error('shiftDateFrom') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-700 block mb-1">Date To*</label>
                                        <input type="date" wire:model.live="shiftDateTo" class="w-full border rounded p-2 text-xs bg-white">
                                        @error('shiftDateTo') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-1">Multi-dates are automatically broken down day-by-day in the right sidebar for bulk adding.</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Start*</label>
                                <input type="time" wire:model.live="shiftStart" class="w-full border rounded p-2 text-xs bg-white">
                                @error('shiftStart') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">End*</label>
                                <input type="time" wire:model.live="shiftEnd" class="w-full border rounded p-2 text-xs bg-white">
                                @error('shiftEnd') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Std Hours*</label>
                                <input type="number" step="0.25" min="0.5" max="24" wire:model.live.debounce.300ms="shiftStandardHours" class="w-full border rounded p-2 text-xs bg-white" placeholder="8.00">
                                @error('shiftStandardHours') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Location & GPS Geofencing --}}
                        <div x-data="shiftMapPicker()" @coordinates-updated.window="handleCoordinatesUpdated($event.detail)" class="border border-sky-200 bg-sky-50/60 rounded-xl p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                    <span>📍</span> Location & Geofencing (Site Preset)
                                </span>
                                @if($shiftLocationPresetId)
                                    <span class="text-[9px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">
                                        🔒 Preset Locked (locked to preset)
                                    </span>
                                @endif
                            </div>

                            <div>
                                <select wire:model.live="shiftLocationPresetId" class="w-full border rounded p-1.5 text-xs bg-white cursor-pointer font-medium focus:ring-1 focus:ring-blue-500">
                                    <option value="">-- No Location / Remote Shift --</option>
                                    @foreach($locationPresets as $preset)
                                        <option value="{{ $preset->id }}">{{ $preset->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="hidden" wire:model="shiftLocationName">
                            <input type="hidden" wire:model="shiftLatitude">
                            <input type="hidden" wire:model="shiftLongitude">
                            <input type="hidden" wire:model="shiftGeofenceRadius">

                            @if($shiftLocationPresetId && $shiftLatitude && $shiftLongitude)
                                <div class="bg-white border border-sky-200 rounded p-2 text-xs flex justify-between items-center text-gray-700 shadow-2xs">
                                    <span class="font-bold truncate max-w-[200px]" title="{{ $shiftLocationName }}">🏢 {{ $shiftLocationName }}</span>
                                    <span class="text-[10px] bg-blue-50 text-blue-700 font-semibold px-1.5 py-0.2 rounded border border-blue-100">Radius: {{ $shiftGeofenceRadius ?: 200 }}m</span>
                                </div>
                            @endif

                            <div wire:ignore class="rounded-lg overflow-hidden border border-gray-300 relative shadow-inner bg-slate-100">
                                <div x-ref="shiftMapContainer" style="height: 130px; width: 100%;" class="z-0"></div>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-700 block mb-1">Shift Notes / Instructions</label>
                            <textarea wire:model="shiftNotes" rows="2" placeholder="Optional notes for shift..." class="w-full border rounded p-2 text-xs bg-white"></textarea>
                        </div>
                    </div>

                    @if($isMultiDay)
                        {{-- Right Column / Sidebar: Dates & Shifts Breakdown (col-span-7) --}}
                        <div class="lg:col-span-7 space-y-3 flex flex-col h-auto">
                        @php
                            $weeklyList = $this->shiftWeeklyBreakdown;
                            $totalWeeks = count($weeklyList);
                            $currentPage = max(0, min($this->shiftBreakdownWeekPage, max(0, $totalWeeks - 1)));
                            $currentWeek = $weeklyList[$currentPage] ?? null;

                            $totalDaysCount = 0;
                            $totalNetMins = 0;
                            $totalBreakMins = 0;
                            foreach($this->shiftDaysBreakdown as $d) {
                                if(!empty($d['enabled']) && (float)($d['hours'] ?? 0) > 0) {
                                    $totalDaysCount++;
                                    $hMins = (int) round(((float)$d['hours']) * 60);
                                    $totalNetMins += $hMins;
                                    if(!empty($d['has_break'])) {
                                        $totalBreakMins += (int)($d['break_minutes'] ?? 0);
                                    }
                                }
                            }
                        @endphp

                        <div class="flex justify-between items-center mb-1">
                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">Dates & Shifts Breakdown (Bulk Adding)</label>
                                <span class="text-[11px] text-gray-500">Day by day split with hours, check/uncheck & break to/from</span>
                            </div>
                            @if($totalWeeks > 1)
                                <div class="text-[11px] font-bold text-[#005a9c] bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                    Week {{ $currentPage + 1 }} of {{ $totalWeeks }}
                                </div>
                            @endif
                        </div>

                        @if(!$editingShiftId)
                            <div class="flex items-center justify-between bg-gray-50 p-2 rounded border border-gray-200 text-xs">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-gray-700 select-none">
                                    <input type="checkbox" wire:model.live="excludeWeekends" class="rounded text-blue-600 focus:ring-blue-500">
                                    <span>Exclude Weekends</span>
                                </label>
                                <div class="flex items-center gap-2 text-[11px] text-blue-600 font-medium">
                                    <button type="button" wire:click="selectAllShiftDays" class="hover:underline">Select All</button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" wire:click="deselectAllShiftDays" class="hover:underline text-gray-500">Deselect All</button>
                                </div>
                            </div>
                        @endif

                        @if($totalWeeks > 1)
                            <div class="flex items-center justify-between bg-slate-50 border border-gray-200 rounded-t-lg p-2 text-xs">
                                <button type="button" wire:click="prevShiftBreakdownWeek" @disabled($currentPage <= 0)
                                        class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                    <span>&larr; Prev Week</span>
                                </button>
                                <div class="text-center px-1">
                                    <div class="font-bold text-gray-800 text-xs">{{ $currentWeek['label'] ?? '' }}</div>
                                    <div class="text-[10px] text-gray-500 font-medium">
                                        {{ count($currentWeek['days'] ?? []) }} day(s) &bull; {{ \App\Services\ReportDataService::formatHoursToHM($currentWeek['total_hours'] ?? 0) }}
                                    </div>
                                </div>
                                <button type="button" wire:click="nextShiftBreakdownWeek" @disabled($currentPage >= $totalWeeks - 1)
                                        class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                    <span>Next Week &rarr;</span>
                                </button>
                            </div>
                        @endif

                        <div class="border {{ $totalWeeks > 1 ? 'border-t-0' : '' }} border-gray-200 rounded-b-lg overflow-hidden h-auto max-h-[360px] overflow-y-auto bg-white">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-600 sticky top-0 z-10 text-[11px]">
                                <tr>
                                    <th class="p-2 w-8 text-center">Include</th>
                                    <th class="p-2">Date</th>
                                    <th class="p-2 w-24 text-center">Shift Hours</th>
                                    <th class="p-2 text-right">Daily Total</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                @php
                                    $daysToDisplay = $totalWeeks > 0 ? ($currentWeek['days'] ?? []) : $this->shiftDaysBreakdown;
                                @endphp
                                @forelse($daysToDisplay as $dayItem)
                                    @php
                                        $index = $dayItem['original_index'] ?? $loop->index;
                                        $isEnabled = !empty($this->shiftDaysBreakdown[$index]['enabled']);
                                        $dayHrs = (float)($this->shiftDaysBreakdown[$index]['hours'] ?? 0);
                                        $netMins = $isEnabled ? max(0, (int)round($dayHrs * 60)) : 0;
                                    @endphp
                                    <tr class="{{ $dayItem['is_weekend'] ? 'bg-amber-50/30' : 'bg-white' }} {{ !$isEnabled ? 'opacity-50 bg-gray-50/50' : 'hover:bg-blue-50/20' }} transition">
                                        <td class="p-2 text-center align-middle">
                                            <input type="checkbox"
                                                   wire:click="toggleShiftDayEnabled({{ $index }})"
                                                   @checked($isEnabled)
                                                   class="rounded text-[#005a9c] cursor-pointer">
                                        </td>
                                        <td class="p-2 align-middle">
                                            <div class="font-bold {{ $isEnabled ? 'text-gray-900' : 'text-gray-400' }}">{{ $dayItem['formatted'] }}</div>
                                            <div class="text-[10px] {{ $dayItem['is_weekend'] ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                                {{ $dayItem['day_name'] }} {{ $dayItem['is_weekend'] ? '(Weekend)' : '' }}
                                            </div>
                                        </td>
                                        <td class="p-2 align-middle text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <input type="number" step="0.25" min="0" max="24"
                                                       wire:model.live.debounce.300ms="shiftDaysBreakdown.{{ $index }}.hours"
                                                       @disabled(!$isEnabled)
                                                       class="w-16 border rounded p-1 text-center font-mono text-xs {{ $isEnabled ? 'border-gray-300 bg-white' : 'border-gray-200 bg-gray-100 text-gray-400' }}">
                                                <span class="text-[10px] text-gray-500">h</span>
                                            </div>
                                        </td>
                                        <td class="p-2 text-right align-middle font-mono font-semibold {{ $isEnabled ? 'text-gray-800' : 'text-gray-300' }}">
                                            {{ \App\Services\ReportDataService::formatDurationHM($netMins) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-400">
                                            Select a valid date range to view the day-by-day split.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Summary Metrics Box --}}
                        @php
                            $empCount = count($shiftEmployeeIds) ?: 1;
                            $taskCount = count($shiftTaskIds) ?: (count($shiftAssignmentIds) ?: 1);
                            $totalShifts = $totalDaysCount * $empCount * $taskCount;
                            $grandTotalMins = $totalNetMins * $empCount * $taskCount;
                        @endphp
                        <div class="bg-blue-50/60 border border-blue-200 rounded-lg p-3 text-xs mt-auto">
                            <div class="grid grid-cols-4 gap-2 text-center">
                                <div>
                                    <span class="text-gray-500 block text-[10px]">Dates</span>
                                    <strong class="text-xs text-gray-800">{{ $totalDaysCount }} day(s)</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px]">Employees</span>
                                    <strong class="text-xs text-indigo-700">{{ $empCount }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px]">Jobs / Tasks</span>
                                    <strong class="text-xs text-emerald-700">{{ $taskCount }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px]">Total Shifts (Work)</span>
                                    <strong class="text-xs text-[#005a9c] font-bold">
                                        {{ $totalShifts }} ({{ \App\Services\ReportDataService::formatDurationHM($grandTotalMins) }})
                                    </strong>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200">
                    <div>
                        @if($editingShiftId)
                            <button wire:click="deleteShift({{ $editingShiftId }})"
                                    wire:confirm="Delete this shift?"
                                    class="text-red-500 text-xs font-semibold hover:text-red-700">Delete Shift</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="cancelShift" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">Cancel</button>
                        <button wire:click="saveShift(false)"
                                class="px-4 py-2 text-xs font-bold bg-gray-800 hover:bg-black text-white rounded-lg shadow-xs transition">
                            💾 Save as Draft (Default)
                        </button>
                        <button wire:click="saveShift(true)"
                                class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-xs transition">
                            🚀 {{ $editingShiftId ? 'Save & Publish' : 'Publish Shift(s)' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- COPY / CLONE SHIFT MODAL --}}
    @if($showCopyShift && $copySourceShift)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-hidden flex flex-col text-xs my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-1.5">
                            <span>📋</span> Copy / Clone Shift to Other Days
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                            Drafted by default
                        </span>
                    </div>
                    <button wire:click="closeCopyShift" class="text-gray-400 hover:text-gray-700 text-xl font-bold leading-none">&times;</button>
                </div>

                <div class="overflow-y-auto flex-1 space-y-4 pr-1">
                    {{-- Source Shift Details --}}
                    <div class="bg-blue-50/70 border border-blue-200 rounded-lg p-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 block mb-1">Source Shift Details</span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 block text-[10px]">Job:</span>
                                <span class="font-bold text-gray-800">{{ $copySourceShift->assignment?->name ?: 'No Job Assigned' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-[10px]">Task:</span>
                                <span class="font-bold text-gray-800">{{ $copySourceShift->task?->name ?: 'General Tasks' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-[10px]">Timing:</span>
                                <span class="font-semibold text-gray-800">{{ substr($copySourceShift->start_time, 0, 5) }} &ndash; {{ substr($copySourceShift->end_time, 0, 5) }} ({{ $copySourceShift->standard_hours ?: 8 }}h)</span>
                            </div>
                            @if($copySourceShift->location_name)
                                <div class="col-span-2">
                                    <span class="text-gray-500 block text-[10px]">Location:</span>
                                    <span class="font-semibold text-gray-700">📍 {{ $copySourceShift->location_name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Target Employee --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-700 block mb-1">Target Employee*</label>
                        <select wire:model="copyTargetEmployeeId" class="w-full border rounded p-2 text-xs bg-white">
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Target Date Range & Exclude Weekends --}}
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/60 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Copy Date From*</label>
                                <input type="date" wire:model.live="copyDateFrom" class="w-full border rounded p-2 text-xs bg-white">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Copy Date To*</label>
                                <input type="date" wire:model.live="copyDateTo" class="w-full border rounded p-2 text-xs bg-white">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-medium text-gray-700 select-none">
                                <input type="checkbox" wire:model.live="copyExcludeWeekends" class="rounded text-blue-600 focus:ring-blue-500">
                                <span>Exclude Weekends</span>
                            </label>
                            <div class="flex items-center gap-2 text-[11px] text-blue-600 font-medium">
                                <button type="button" wire:click="selectAllCopyDays" class="hover:underline">Select All</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" wire:click="deselectAllCopyDays" class="hover:underline text-gray-500">Deselect All</button>
                            </div>
                        </div>
                    </div>

                    {{-- Target Days Breakdown Checklist (similar to Time Off section) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-700">
                                Target Days ({{ count($copySelectedDays) }} selected)
                            </label>
                            <span class="text-[10px] text-gray-400">Check/uncheck days to clone shift to</span>
                        </div>

                        <div class="border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto bg-white">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 border-b border-gray-200 font-semibold text-gray-600 sticky top-0 z-10 text-[11px]">
                                <tr>
                                    <th class="p-2 w-10 text-center">Include</th>
                                    <th class="p-2">Date</th>
                                    <th class="p-2">Day</th>
                                    <th class="p-2 text-right">Type</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-[11px]">
                                @forelse($this->copyRangeDays as $day)
                                    <tr class="{{ $day['is_weekend'] ? 'bg-amber-50/40' : 'bg-white' }} hover:bg-blue-50/40 cursor-pointer"
                                        wire:click="toggleCopyDay('{{ $day['date'] }}')">
                                        <td class="p-2 text-center" onclick="event.stopPropagation()">
                                            <input type="checkbox"
                                                   value="{{ $day['date'] }}"
                                                   wire:click="toggleCopyDay('{{ $day['date'] }}')"
                                                   {{ $day['is_checked'] ? 'checked' : '' }}
                                                   class="rounded text-blue-600 cursor-pointer">
                                        </td>
                                        <td class="p-2 font-medium text-gray-800">{{ $day['formatted'] }}</td>
                                        <td class="p-2 text-gray-600">{{ $day['day_name'] }}</td>
                                        <td class="p-2 text-right">
                                            @if($day['is_weekend'])
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Weekend</span>
                                            @else
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">Weekday</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-3 text-center text-gray-400 italic">No dates in selected range</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-200">
                    <button type="button" wire:click="closeCopyShift" class="px-4 py-2 border border-gray-300 rounded text-gray-700 font-semibold hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="executeCopyShift(false)"
                                class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded font-semibold transition shadow-xs">
                            Clone as Draft (Default)
                        </button>
                        <button type="button" wire:click="executeCopyShift(true)"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold transition shadow-xs">
                            Clone & Publish
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ADD JOB MODAL --}}
    @if($showAddJob)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 my-auto">
                <h3 class="text-lg font-bold mb-4">Add Job</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600 font-medium">Job Name*</label>
                        <input type="text" wire:model="newJobName" placeholder="e.g. Office Renovation"
                               class="w-full border rounded p-2 text-sm">
                        @error('newJobName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 font-medium">Initial Status</label>
                        <select wire:model="newJobStatus" class="w-full border rounded p-2 text-sm bg-white">
                            @foreach($allStatuses as $st)
                                <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelJob" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveJob" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Add Job</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ADD TASK MODAL --}}
    @if($showAddTask)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 my-auto">
                <h3 class="text-lg font-bold mb-4">Add Task</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600 font-medium">Task Name*</label>
                        <input type="text" wire:model="newTaskName" placeholder="e.g. Painting, Plumbing, Electrical"
                               class="w-full border rounded p-2 text-sm">
                        @error('newTaskName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 font-medium">Initial Status</label>
                        <select wire:model="newTaskStatus" class="w-full border rounded p-2 text-sm bg-white">
                            @foreach($allStatuses as $st)
                                <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelTask" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveTask" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Add Task</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ADD EMPLOYEE MODAL --}}
    @if($showAddEmployee)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 my-auto">
                <h3 class="text-lg font-bold mb-4">Add Employee</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Name*</label>
                        <input type="text" wire:model="newEmployeeName"
                               class="w-full border rounded p-2 text-sm">
                        @error('newEmployeeName') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Email*</label>
                        <input type="email" wire:model="newEmployeeEmail"
                               class="w-full border rounded p-2 text-sm">
                        @error('newEmployeeEmail') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelEmployee" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveEmployee" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Add</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    <script>
    function shiftMapPicker() {
        return {
            map: null,
            marker: null,
            circle: null,
            resizeObserver: null,

            init() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.initMap();
                    }, 150);
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 400);
                });
            },

            getPinIcon() {
                if (!window.L) return null;
                return L.divIcon({
                    className: 'custom-preset-marker',
                    html: `<div style="display:flex;align-items:center;justify-content:center;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.35));transform:translate(-50%,-100%);">
                            <svg width="26" height="34" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 0C5.373 0 0 5.373 0 12C0 21 12 32 12 32C12 32 24 21 24 12C24 5.373 18.627 0 12 0Z" fill="#005a9c"/>
                                <circle cx="12" cy="11" r="4.5" fill="#ffffff"/>
                            </svg>
                           </div>`,
                    iconSize: [26, 34],
                    iconAnchor: [13, 34]
                });
            },

            initMap() {
                const container = this.$refs.shiftMapContainer;
                if (!container || !window.L) return;

                if (this.map) {
                    try { this.map.remove(); } catch (e) {}
                    this.map = null;
                }
                if (container._leaflet_id) {
                    delete container._leaflet_id;
                }
                container.innerHTML = '';

                let initialLat = parseFloat(this.$wire.shiftLatitude);
                let initialLng = parseFloat(this.$wire.shiftLongitude);
                let hasCoords = !isNaN(initialLat) && !isNaN(initialLng) && (initialLat !== 0 || initialLng !== 0);

                let center = hasCoords ? [initialLat, initialLng] : [37.0902, -95.7129];
                let zoom = hasCoords ? 15 : 4;

                this.map = L.map(container, {
                    attributionControl: false,
                    zoomControl: true,
                    dragging: true,
                    scrollWheelZoom: true
                }).setView(center, zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(this.map);

                if (hasCoords) {
                    this.placeMarkerAndCircle(initialLat, initialLng);
                }

                if (window.ResizeObserver && !this.resizeObserver) {
                    this.resizeObserver = new ResizeObserver(() => {
                        if (this.map) this.map.invalidateSize();
                    });
                    this.resizeObserver.observe(container);
                }

                setTimeout(() => {
                    if (this.map) this.map.invalidateSize();
                }, 250);
                setTimeout(() => {
                    if (this.map) this.map.invalidateSize();
                }, 600);
            },

            placeMarkerAndCircle(lat, lng, customRadius = null) {
                if (!this.map || !window.L) return;
                const radius = customRadius !== null ? parseInt(customRadius) : (parseInt(this.$wire.shiftGeofenceRadius) || 200);
                const icon = this.getPinIcon();

                if (this.marker) {
                    this.marker.setLatLng([lat, lng]);
                } else {
                    this.marker = L.marker([lat, lng], {
                        icon: icon || undefined,
                        interactive: false
                    }).addTo(this.map);
                }

                if (this.circle) {
                    this.circle.setLatLng([lat, lng]);
                    this.circle.setRadius(radius);
                } else {
                    this.circle = L.circle([lat, lng], {
                        radius: radius,
                        color: '#005a9c',
                        weight: 2,
                        fillColor: '#005a9c',
                        fillOpacity: 0.16
                    }).addTo(this.map);
                }
            },

            clearMapVisuals() {
                if (this.marker && this.map) {
                    this.map.removeLayer(this.marker);
                    this.marker = null;
                }
                if (this.circle && this.map) {
                    this.map.removeLayer(this.circle);
                    this.circle = null;
                }
                if (this.map) {
                    this.map.setView([37.0902, -95.7129], 4);
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 100);
                }
            },

            handleCoordinatesUpdated(detail) {
                if (!detail) {
                    this.clearMapVisuals();
                    return;
                }
                const lat = parseFloat(detail.lat);
                const lng = parseFloat(detail.lng);
                const radius = parseInt(detail.radius) || 200;

                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                    if (!this.map) {
                        this.initMap();
                    }
                    if (this.map) {
                        this.placeMarkerAndCircle(lat, lng, radius);
                        this.map.setView([lat, lng], 15);
                        setTimeout(() => {
                            if (this.map) this.map.invalidateSize();
                        }, 100);
                    }
                } else {
                    this.clearMapVisuals();
                }
            },

            navigateToCoordinates() {
                let lat = parseFloat(this.$wire.shiftLatitude);
                let lng = parseFloat(this.$wire.shiftLongitude);
                let radius = parseInt(this.$wire.shiftGeofenceRadius) || 200;

                if (!isNaN(lat) && !isNaN(lng) && (lat !== 0 || lng !== 0)) {
                    if (!this.map) {
                        this.initMap();
                    }
                    if (this.map) {
                        this.placeMarkerAndCircle(lat, lng, radius);
                        this.map.setView([lat, lng], 16);
                        setTimeout(() => {
                            if (this.map) this.map.invalidateSize();
                        }, 100);
                    }
                } else {
                    alert('No coordinates attached to navigate to. Please select a site preset.');
                }
            }
        };
    }
    </script>
</div>
