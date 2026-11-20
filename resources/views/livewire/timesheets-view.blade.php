<div class="relative" x-data="{ closeDropdowns() { $wire.showDatePicker = false; } }">
    {{-- TOAST --}}
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            <div class="flex justify-between items-center">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" @click="show = false" class="opacity-60 hover:opacity-100 text-lg leading-none">&times;</button>
            </div>
        </div>
    @endif

    {{-- PAGE TITLE + TOP ACTIONS --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-normal text-gray-800">Timesheets</h1>
            @if(!auth()->user()->canEditTimesheets())
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">Read-Only Mode</span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->canEditTimesheets())
                <button wire:click="openAdd()"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold shadow transition">
                    Add Time
                </button>
            @endif

            <a href="{{ route('timeoff.requests') }}"
               class="border border-cs-blue text-cs-blue hover:bg-blue-50 px-4 py-2 rounded text-sm font-semibold flex items-center gap-1 transition">
                + Time Off <span class="text-[9px]">&#9660;</span>
            </a>
        </div>
    </div>

    {{-- TOOLBAR --}}
    <div class="flex justify-between items-center mb-3">
        <div class="flex items-center gap-4">
            <button wire:click="previousWeek" class="text-gray-500 hover:text-gray-800 text-lg font-bold" title="Previous Week">&#9664;</button>
            <button wire:click="nextWeek"     class="text-gray-500 hover:text-gray-800 text-lg font-bold" title="Next Week">&#9654;</button>

            {{-- Date Range Dropdown Trigger --}}
            <div class="relative">
                <button type="button" wire:click="toggleDatePicker"
                        class="text-lg font-semibold text-gray-800 flex items-center gap-1.5 hover:text-[#005a9c] transition focus:outline-none">
                    <span>{{ $weekOffset === 0 ? 'This Week' : ($weekOffset === -1 ? 'Last Week' : ($weekOffset === 1 ? 'Next Week' : abs($weekOffset) . ' Wk ' . ($weekOffset < 0 ? 'Ago' : 'Later'))) }}</span>
                    <span class="font-normal text-gray-500 text-base">
                        {{ ucfirst($startOfWeek->format('M j')) }} &ndash; {{ ucfirst($endOfWeek->format('M j, Y')) }}
                    </span>
                    <span class="text-[10px] text-gray-500">&#9660;</span>
                </button>

                {{-- DUAL-PANEL DATE PICKER POPUP --}}
                @if($showDatePicker)
                    <div class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-2xl p-4 flex flex-col md:flex-row gap-4 min-w-[320px] md:min-w-[490px]"
                         @click.away="$wire.closeDatePicker()">
                        {{-- LEFT PANEL: PRESETS --}}
                        <div class="w-full md:w-36 flex flex-col gap-1 border-b md:border-b-0 md:border-r border-gray-200 pb-3 md:pb-0 md:pr-3 text-xs">
                            <button wire:click="selectPreset(0)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === 0 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                This Week
                            </button>
                            <button wire:click="selectPreset(-1)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -1 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                Last Week
                            </button>
                            <button wire:click="selectPreset(-2)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -2 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                2 Weeks Ago
                            </button>
                            <button wire:click="selectPreset(-3)"
                                    class="text-left px-3 py-2 rounded font-semibold transition {{ $weekOffset === -3 ? 'bg-[#d0ebfc] text-[#0077c8]' : 'text-gray-700 hover:bg-gray-50' }}">
                                3 Weeks Ago
                            </button>
                            <button wire:click="selectPreset(1)"
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

            <button wire:click="thisWeek" class="text-xs text-cs-blue hover:underline">Reset</button>
        </div>

        <div class="flex items-center gap-3">
            {{-- Printer --}}
            <button type="button" onclick="window.print()"
                    class="text-gray-500 hover:text-gray-800 text-lg" title="Print">&#128424;</button>

            {{-- Timezone Trigger (dispatches to global NavbarClock modal) --}}
            <button type="button"
                    wire:click="$dispatch('open-timezone-modal')"
                    @click="$dispatch('open-timezone-modal'); window.Livewire && window.Livewire.dispatch('open-timezone-modal'); window.dispatchEvent(new CustomEvent('open-timezone-modal'))"
                    class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-50 text-xs sm:text-sm text-gray-700 hover:text-[#005a9c] flex items-center gap-1 font-medium transition cursor-pointer shadow-2xs"
                    title="Click to view or switch timezone (Current: {{ $currencyOrTz }})">
                <span class="font-semibold">{{ $currencyOrTz }}</span> <span class="text-[9px] text-gray-500">&#9660;</span>
            </button>

            {{-- Employees filter --}}
            <div class="relative">
                <select wire:model.live="employeeFilter"
                        class="appearance-none bg-transparent text-sm text-gray-700 pr-5 focus:outline-none cursor-pointer">
                    <option value="">All Team Members</option>
                    @foreach($filterEmployees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-gray-500">&#9660;</span>
            </div>

            @if(auth()->user()->isSuperAdmin())
                <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer select-none bg-gray-50 hover:bg-gray-100 px-2.5 py-1 rounded border border-gray-300">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-gray-800">🗑️ Include Deleted Records</span>
                </label>
            @endif

            {{-- Filter hamburger icon (toggles filter drawer) --}}
            <button type="button" wire:click="toggleFilters"
                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 hover:text-[#005a9c] transition shadow-2xs {{ $showFilters ? 'bg-sky-50 border-[#005a9c] text-[#005a9c] font-bold ring-1 ring-[#005a9c]' : '' }}"
                    title="Filters and Options">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- EXPANDABLE FILTERS DRAWER --}}
    @if($showFilters)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3.5 mb-4 shadow-inner grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-gray-600 mb-1">Employee</label>
                <select wire:model.live="employeeFilter" class="w-full border rounded p-1.5 bg-white text-xs">
                    <option value="">All Team Members</option>
                    @foreach($filterEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-600 mb-1">Department</label>
                <select wire:model.live="departmentFilter" class="w-full border rounded p-1.5 bg-white text-xs">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-600 mb-1">Location</label>
                <select wire:model.live="locationFilter" class="w-full border rounded p-1.5 bg-white text-xs">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-600 mb-1">Job</label>
                <select wire:model.live="jobFilter" class="w-full border rounded p-1.5 bg-white text-xs">
                    <option value="">All Jobs</option>
                    @foreach($assignments as $job)
                        <option value="{{ $job->id }}">{{ $job->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-600 mb-1">Approval Status</label>
                <select wire:model.live="statusFilter" class="w-full border rounded p-1.5 bg-white text-xs">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    @if(auth()->user()->isSuperAdmin())
                        <option value="archived">Archived / Hidden</option>
                    @endif
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="$set('departmentFilter', null); $set('locationFilter', null); $set('jobFilter', null); $set('statusFilter', null); $set('employeeFilter', null);"
                        class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-600 hover:bg-white text-xs font-medium text-center">
                    Clear Filters
                </button>
            </div>
        </div>
    @endif

    {{-- GRID --}}
    <div class="border border-gray-300 bg-white overflow-x-auto shadow-sm">
        <table class="w-full table-fixed border-collapse">
            <colgroup>
                <col style="width: 180px;">
                @foreach(range(0,6) as $i)
                    <col style="width: 150px;">
                @endforeach
                <col style="width: 150px;">
            </colgroup>

            <thead>
            <tr class="border-b border-gray-300 bg-gray-50">
                <th class="text-left align-top px-3 py-3 text-xs font-bold text-gray-700 uppercase tracking-wide border-r border-gray-300">
                    Employees
                </th>
                {{-- DYNAMIC DAYS ALIGNED WITH $startOfWeek --}}
                @foreach(range(0,6) as $i)
                    @php
                        $dayDate = $startOfWeek->copy()->addDays($i);
                    @endphp
                    <th class="px-3 py-2 text-xs font-bold text-[#003d6b] uppercase tracking-wide border-r border-gray-300 {{ $dayDate->isToday() ? 'bg-blue-50/60' : 'bg-gray-50' }}">
                        <div class="text-left font-bold text-gray-800">{{ strtoupper($dayDate->format('D')) }}</div>
                        <div class="text-left text-xs font-normal text-gray-500">{{ $dayDate->format('n/j') }}</div>
                    </th>
                @endforeach
                <th class="text-left align-top px-3 py-3 text-xs font-bold text-gray-700 uppercase tracking-wide">
                    <div>Total</div>
                    <div class="text-[10px] text-gray-500 font-normal capitalize">{{ ucfirst($startOfWeek->format('M j')) }} &ndash; {{ ucfirst($endOfWeek->format('M j, Y')) }}</div>
                </th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
            @foreach($employees as $employee)
                <tr class="hover:bg-gray-50/50 transition">
                    {{-- EMPLOYEE COLUMN --}}
                    <td class="align-top px-3 py-3 border-r border-gray-300 bg-white">
                        <div class="flex items-start gap-2.5">
                            @php
                                $empColors = ['bg-[#b85d43]', 'bg-[#0d9488]', 'bg-[#ea580c]', 'bg-[#6366f1]', 'bg-[#9a3412]', 'bg-[#0284c7]', 'bg-[#4f46e5]', 'bg-[#d97706]'];
                                $cIdx = abs(crc32($employee->name)) % count($empColors);
                                $bgColor = $empColors[$cIdx];
                                $parts = preg_split('/\s+/', trim($employee->name));
                                $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr(end($parts) ?: '', 0, 1));
                                $formattedName = \App\Services\ReportDataService::formatEmployeeName($employee);
                                $activeLog = $activeClockIns[$employee->id] ?? null;
                            @endphp
                            <div class="w-8 h-8 rounded-full flex-shrink-0 {{ $bgColor }} text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900 text-sm leading-tight">{{ $formattedName }}</div>
                                @if($activeLog)
                                    <div class="text-[11px] text-gray-500 leading-tight mt-1">
                                        Clocked in for <span class="font-medium text-gray-700">{{ $activeLog->assignment->name ?? 'General Hours' }}</span> doing <span class="font-medium text-gray-700">{{ $activeLog->task->name ?? 'General' }}</span> {{ $activeLog->clock_in->diffForHumans() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- DAY CELLS --}}
                    @foreach(range(0,6) as $i)
                        @php
                            $day     = $startOfWeek->copy()->addDays($i);
                            $dayKey  = $day->toDateString();
                            $entries = $grouped[$employee->id][$dayKey] ?? [];
                        @endphp
                        <td class="align-top p-1.5 border-r border-gray-300">
                            @if(!empty($entries))
                                <div class="space-y-1.5">
                                    @foreach($entries as $entry)
                                        @php
                                            $mins = $entry->clock_out
                                                ? $entry->duration_minutes
                                                : (int) ceil($entry->netSeconds() / 60);
                                            $isManual = $entry->isManual();
                                            $breakMins = (int) ($entry->break_minutes ?: 0);
                                            $inFmt = $entry->clock_in ? $entry->clock_in->format('g:i A') : '—';
                                            $outFmt = $entry->clock_out ? $entry->clock_out->format('g:i A') : 'Running';
                                        @endphp
                                        <div
                                            wire:click="openDetail({{ $entry->id }})"
                                            class="cursor-pointer bg-[#fffdf0] border border-amber-200/80 border-l-[3px] border-l-[#b85d43] rounded p-2 hover:shadow transition group relative">
                                            
                                            {{-- Top Line: Flag + Duration h:m + Actions --}}
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-baseline gap-1">
                                                    @if($entry->is_unusual || $entry->location_flagged)
                                                        <span class="text-rose-600 font-bold text-xs" title="{{ $entry->unusual_reason ?: 'Alert flagged' }}">&#9888;</span>
                                                    @endif
                                                    <span class="text-sm font-bold text-gray-900">
                                                        {{ floor($mins / 60) }}:{{ str_pad($mins % 60, 2, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                    <span class="text-[11px] text-gray-500 font-normal">h:m</span>
                                                </div>

                                                {{-- Quick Action Buttons on hover --}}
                                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                                    @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin())
                                                        @if($entry->approved)
                                                            <button wire:click.stop="disapproveLog({{ $entry->id }})" type="button"
                                                                    class="p-0.5 hover:bg-amber-100 rounded text-[10px] text-amber-700 font-bold" title="Return to Pending (Unapprove)">
                                                                ↺
                                                            </button>
                                                        @else
                                                            <button wire:click.stop="approveLog({{ $entry->id }})" type="button"
                                                                    class="p-0.5 hover:bg-green-100 rounded text-[10px] text-green-700 font-bold" title="Approve Entry">
                                                                ✓
                                                            </button>
                                                        @endif
                                                    @endif

                                                    @if($this->canEditFor($employee->id))
                                                        <button wire:click.stop="openCopyModal({{ $entry->id }})" type="button"
                                                                class="p-0.5 hover:bg-gray-200 rounded text-[10px] text-blue-700" title="Copy to other days">
                                                            &#128203;
                                                        </button>
                                                        @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin() || !$entry->isLocked())
                                                            <button wire:click.stop="startEdit({{ $entry->id }})" type="button"
                                                                    class="p-0.5 hover:bg-gray-200 rounded text-[10px] text-gray-600" title="Edit entry">
                                                                &#9998;
                                                            </button>
                                                        @endif
                                                        @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin() || (!$entry->approved && !$entry->isRejected()))
                                                            <button wire:click.stop="deleteLog({{ $entry->id }})" wire:confirm="Are you sure you want to delete/archive this time entry?" type="button"
                                                                    class="p-0.5 hover:bg-red-100 rounded text-[10px] text-red-600" title="Delete/Archive entry">
                                                                &#128465;
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Timeline: 7:02 AM ------> 6:02 PM --}}
                                            <div class="flex items-center text-[10.5px] text-gray-600 my-0.5 font-sans">
                                                <span>{{ $inFmt }}</span>
                                                <div class="flex-1 flex items-center mx-1">
                                                    <div class="w-full border-t border-gray-400 relative">
                                                        <span class="absolute right-0 -top-[5px] text-[7px] text-gray-500">&#9658;</span>
                                                    </div>
                                                </div>
                                                <span>{{ $outFmt }}</span>
                                            </div>

                                            {{-- Break Duration line if any --}}
                                            @if($breakMins > 0)
                                                <div class="text-[10px] text-gray-600 my-0.5">
                                                    Break ({{ floor($breakMins / 60) }}:{{ str_pad($breakMins % 60, 2, '0', STR_PAD_LEFT) }} h:m)
                                                </div>
                                            @endif

                                            {{-- Icons row: Meal Fork/Knife + Pencil if edited/manual --}}
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if($breakMins > 0 || !empty($entry->breaks))
                                                    <span class="text-amber-500 text-xs" title="Break/Meal taken">&#127860;</span>
                                                @endif
                                                @if($entry->is_manual || $isManual)
                                                    <span class="text-emerald-600 text-xs" title="Manual / Edited">&#9998;</span>
                                                @endif
                                                @if($entry->approved)
                                                    <span class="text-green-700 text-[10px] font-bold" title="Approved">✓</span>
                                                @endif
                                            </div>

                                            {{-- Job Line --}}
                                            <div class="text-[11px] text-gray-700 truncate mt-1 flex items-center gap-1 border-t border-amber-200/60 pt-1">
                                                <span class="text-[#b85d43] font-bold">|</span>
                                                <span class="truncate" title="{{ $entry->assignment->name ?? 'General Hours' }}">{{ $entry->assignment->name ?? 'General Hours' }}</span>
                                            </div>

                                            {{-- Audit Badge if edited --}}
                                            @if($entry->latestEditAudit)
                                                <div class="mt-1 pt-1 border-t border-amber-200/60 text-[9.5px] text-gray-500 leading-tight">
                                                    <span>Edited by: <strong class="text-gray-700">{{ $entry->latestEditAudit->user_name }}</strong> on {{ $entry->latestEditAudit->created_at->format('m/d/Y') }}</span>
                                                    <span class="text-[#005a9c] hover:underline font-semibold ml-0.5">(View Changes)</span>
                                                </div>
                                            @endif

                                            @if($entry->is_archived)
                                                <div class="mt-1 pt-1 border-t border-amber-300 flex items-center justify-between text-[9px] text-amber-900 font-bold">
                                                    <span>📦 ARCHIVED</span>
                                                    @if(auth()->user()->isSuperAdmin())
                                                        <div class="flex items-center gap-1" wire:click.stop>
                                                            <button wire:click="restoreArchivedLog({{ $entry->id }})" class="px-1 py-0.5 rounded bg-emerald-600 text-white font-semibold hover:bg-emerald-700" title="Restore back to active">Restore</button>
                                                            <button wire:click="rejectArchivedLog({{ $entry->id }})" class="px-1 py-0.5 rounded bg-amber-600 text-white font-semibold hover:bg-amber-700" title="Reject record">Reject</button>
                                                            <button wire:click="permanentlyDeleteLog({{ $entry->id }})" wire:confirm="Permanently delete this entry and all child records?" class="px-1 py-0.5 rounded bg-red-600 text-white font-semibold hover:bg-red-700" title="Permanently delete">Delete</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($this->canEditFor($employee->id))
                                        <button wire:click="openAdd({{ $employee->id }}, '{{ $dayKey }}')"
                                                class="w-full text-center text-gray-300 hover:text-cs-blue text-lg leading-none">
                                            +
                                        </button>
                                    @endif
                                </div>
                            @else
                                @if($this->canEditFor($employee->id))
                                    <button wire:click="openAdd({{ $employee->id }}, '{{ $dayKey }}')"
                                            class="w-full h-full min-h-[80px] text-center text-gray-300 hover:text-cs-blue text-lg">
                                        +
                                    </button>
                                @else
                                    <div class="w-full h-full min-h-[80px]"></div>
                                @endif
                            @endif
                        </td>
                    @endforeach

                    {{-- ROW TOTAL --}}
                    <td class="align-top px-3 py-4 text-[13px] text-gray-800 font-semibold relative">
                        @php
                            $empTotal = 0;
                            foreach (range(0, 6) as $i) {
                                $dayKey = $startOfWeek->copy()->addDays($i)->toDateString();
                                foreach (($grouped[$employee->id][$dayKey] ?? []) as $l) {
                                    $empTotal += $l->clock_out
                                        ? $l->duration_minutes
                                        : (int) ceil($l->netSeconds() / 60);
                                }
                            }
                        @endphp
                        {{ \App\Services\ReportDataService::formatDurationColonHM($empTotal) }}

                        @if(!empty($employeeApproved[$employee->id]))
                            <span class="absolute top-0 right-0 text-green-600 text-xl leading-none" title="All logs approved">&#10004;</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- TOTALS ROW --}}
            <tr class="bg-gray-100/70 border-t-2 border-gray-300 font-semibold">
                <td class="px-3 py-3 text-[13px] text-gray-700 border-r border-gray-300">Total</td>
                @foreach(range(0,6) as $i)
                    @php $dayKey = $startOfWeek->copy()->addDays($i)->toDateString(); @endphp
                    <td class="px-3 py-3 border-r border-gray-300 text-[13px] text-gray-700">
                        @php $dm = $dayTotals[$dayKey] ?? 0; @endphp
                        @if($dm > 0)
                            {{ \App\Services\ReportDataService::formatDurationColonHM($dm) }}
                        @endif
                    </td>
                @endforeach
                <td class="px-3 py-3 text-[13px] text-gray-900 font-bold">
                    {{ \App\Services\ReportDataService::formatDurationColonHM($weekTotal) }}
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    {{-- ADD MANUAL TIME MODAL (with Right Sidebar Day-by-Day Split & Week-by-Week Navigation) --}}
    @if($showAddModal)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>⏱️</span> Add Manual Time Entry
                        </h3>
                        <p class="text-xs text-gray-500">Record manual work shifts with day-by-day breakdown and break deductions.</p>
                    </div>
                    <button wire:click="cancelAdd" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto text-xs flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        {{-- Left Column: Employee, Job, Task, Date Range, Default Shift & Notes --}}
                        <div class="md:col-span-5 space-y-3.5">
                            @if(auth()->user()->canAccessAdmin() || auth()->user()->isManager())
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Employee*</label>
                                    <select wire:model.live="addEmployeeId" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                        <option value="">Select Employee</option>
                                        @foreach($filterEmployees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('addEmployeeId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Job*</label>
                                <select wire:model.live="addAssignment" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                    <option value="">{{ count($this->modalAssignments) === 0 ? 'No assigned jobs available' : 'Select Job' }}</option>
                                    @foreach($this->modalAssignments as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                                @error('addAssignment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Task*</label>
                                <select wire:model.live="addTask" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                    <option value="">{{ !$addAssignment ? 'Select a Job first' : (count($this->modalTasks) === 0 ? 'No assigned tasks for this job' : 'Select Task') }}</option>
                                    @foreach($this->modalTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('addTask') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Start Date*</label>
                                    <input type="date" wire:model.live="addStartDate" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                    @error('addStartDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">End Date*</label>
                                    <input type="date" wire:model.live="addEndDate" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                    @error('addEndDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Standard Shift Hours</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" step="0.5" min="0.5" max="24" wire:model.live.debounce.300ms="addStandardShiftHours"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                        <span class="text-gray-500 text-[11px] font-medium">hrs</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Shift Start Time</label>
                                    <input type="time" wire:model.live="addDefaultStartTime"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                </div>
                            </div>

                            {{-- Add Break Section (Deducted from Total Hours) --}}
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/70 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-gray-700 flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" wire:model.live="hasBreak" class="rounded text-[#005a9c]">
                                        <span>☕ Add Break to Shifts</span>
                                    </label>
                                    @if($hasBreak)
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Deducted from shift hours
                                        </span>
                                    @endif
                                </div>

                                @if($hasBreak)
                                    <div class="grid grid-cols-2 gap-3 pt-1">
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Break Start</label>
                                            <input type="time" wire:model.live="addBreakStart" class="w-full border border-gray-300 rounded-lg p-1.5 text-xs bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Break End</label>
                                            <input type="time" wire:model.live="addBreakEnd" class="w-full border border-gray-300 rounded-lg p-1.5 text-xs bg-white">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin())
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Approval Status</label>
                                    <select wire:model.live="addStatus" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c] bg-white">
                                        <option value="approved">Approved (Directly approve on timesheet)</option>
                                        <option value="pending">Pending (Submit for approval)</option>
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Reason / Notes* <span class="text-red-500 text-[10px] font-normal">(Required for manual entries)</span>
                                </label>
                                <textarea wire:model="addNotes" rows="2" placeholder="Explain reason for manual entry (e.g., onsite project without mobile access)..."
                                          class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-1 focus:ring-[#005a9c]"></textarea>
                                @error('addNotes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Attachments (Optional)</label>
                                <input type="file" wire:model="addAttachments" multiple class="w-full text-xs text-gray-500 mt-1 block">
                                <span class="text-[10px] text-gray-400">Photos, timesheet slips, or client sign-off (max 10MB each).</span>
                                @error('addAttachments.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Right Column / Sidebar: Dates & Hours Breakdown Matrix --}}
                        <div class="md:col-span-7 space-y-3 flex flex-col h-auto">
                            @php
                                $weeklyList = $this->addWeeklyBreakdown;
                                $totalWeeks = count($weeklyList);
                                $currentPage = max(0, min($this->addBreakdownWeekPage, max(0, $totalWeeks - 1)));
                                $currentWeek = $weeklyList[$currentPage] ?? null;

                                $totalDaysCount = 0;
                                $totalNetMins = 0;
                                $totalBreakMins = 0;
                                foreach($this->addDaysBreakdown as $d) {
                                    if(!empty($d['enabled']) && (float)($d['hours'] ?? 0) > 0) {
                                        $totalDaysCount++;
                                        $hMins = (int) round(((float)$d['hours']) * 60);
                                        $brk = (!empty($d['has_break'])) ? (int)($d['break_minutes'] ?? 0) : 0;
                                        $totalBreakMins += $brk;
                                        $totalNetMins += max(0, $hMins - $brk);
                                    }
                                }
                            @endphp

                            <div class="flex justify-between items-center mb-1">
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">Dates & Hours Breakdown</label>
                                    <span class="text-[11px] text-gray-500">Day by day split with standard shift hours & break options</span>
                                </div>
                                @if($totalWeeks > 1)
                                    <div class="text-[11px] font-bold text-[#005a9c] bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                        Week {{ $currentPage + 1 }} of {{ $totalWeeks }}
                                    </div>
                                @endif
                            </div>

                            @if($totalWeeks > 1)
                                {{-- Week Pagination Controls --}}
                                <div class="flex items-center justify-between bg-slate-50 border border-gray-200 rounded-t-lg p-2 text-xs">
                                    <button type="button" 
                                            wire:click="prevAddBreakdownWeek"
                                            @disabled($currentPage <= 0)
                                            class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                        <span>&larr; Prev Week</span>
                                    </button>

                                    <div class="text-center px-1">
                                        <div class="font-bold text-gray-800 text-xs">
                                            {{ $currentWeek['label'] ?? '' }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 font-medium">
                                            {{ count($currentWeek['days'] ?? []) }} day(s) &bull; {{ \App\Services\ReportDataService::formatHoursToHM($currentWeek['total_hours'] ?? 0) }}
                                        </div>
                                    </div>

                                    <button type="button" 
                                            wire:click="nextAddBreakdownWeek"
                                            @disabled($currentPage >= $totalWeeks - 1)
                                            class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                        <span>Next Week &rarr;</span>
                                    </button>
                                </div>

                                {{-- Quick Jump Tabs --}}
                                @if($totalWeeks <= 8)
                                    <div class="flex items-center gap-1 px-2 py-1 bg-gray-100/80 border-x border-gray-200 overflow-x-auto text-[11px]">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Weeks:</span>
                                        @foreach($weeklyList as $wIdx => $w)
                                            <button type="button"
                                                    wire:click="setAddBreakdownWeekPage({{ $wIdx }})"
                                                    class="px-2 py-0.5 rounded text-[11px] transition {{ $wIdx === $currentPage ? 'bg-[#005a9c] text-white font-bold shadow-2xs' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' }}">
                                                W{{ $wIdx + 1 }} ({{ $w['short_label'] }})
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($currentWeek)
                                <div class="bg-slate-50 border border-gray-200 rounded-t-lg px-3 py-1.5 flex justify-between items-center text-xs text-gray-700">
                                    <span class="font-semibold text-[#005a9c]">{{ $currentWeek['label'] }}</span>
                                    <span class="text-[11px] text-gray-500 font-medium">{{ count($currentWeek['days']) }} day(s) &bull; {{ \App\Services\ReportDataService::formatHoursToHM($currentWeek['total_hours']) }}</span>
                                </div>
                            @endif

                            {{-- Day-by-Day Table --}}
                            <div class="border {{ $totalWeeks > 0 ? 'border-t-0' : '' }} border-gray-200 rounded-b-lg overflow-hidden h-auto max-h-[340px] overflow-y-auto bg-white">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-600 sticky top-0 z-10">
                                    <tr>
                                        <th class="p-2 w-8 text-center">
                                            <span class="sr-only">Include</span>
                                        </th>
                                        <th class="p-2">Date</th>
                                        <th class="p-2 w-28 text-center">Shift Hours</th>
                                        <th class="p-2">Break</th>
                                        <th class="p-2 text-right">Net</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @forelse($currentWeek['days'] ?? [] as $day)
                                        @php
                                            $index = $day['original_index'];
                                            $isEnabled = !empty($this->addDaysBreakdown[$index]['enabled']);
                                            $hasBreak = !empty($this->addDaysBreakdown[$index]['has_break']);
                                            $dayHrs = (float)($this->addDaysBreakdown[$index]['hours'] ?? 0);
                                            $brkMins = $hasBreak ? (int)($this->addDaysBreakdown[$index]['break_minutes'] ?? 0) : 0;
                                            $netMins = $isEnabled ? max(0, (int)round($dayHrs * 60) - ($hasBreak ? $brkMins : 0)) : 0;
                                        @endphp
                                        <tr class="{{ $day['is_weekend'] ? 'bg-amber-50/40' : 'bg-white' }} {{ !$isEnabled ? 'opacity-60 bg-gray-50/50' : 'hover:bg-blue-50/20' }} transition">
                                            <td class="p-2 text-center align-middle">
                                                <input type="checkbox"
                                                       wire:click="toggleAddDayEnabled({{ $index }})"
                                                       @checked($isEnabled)
                                                       class="rounded text-[#005a9c] cursor-pointer">
                                            </td>
                                            <td class="p-2 align-middle">
                                                <div class="font-medium {{ $isEnabled ? 'text-gray-900' : 'text-gray-400' }}">{{ $day['formatted'] }}</div>
                                                <div class="text-[10px] {{ $day['is_weekend'] ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                                    {{ $day['day_name'] }} {{ $day['is_weekend'] ? '(Weekend)' : '' }}
                                                </div>
                                            </td>
                                            <td class="p-2 align-middle text-center">
                                                <div class="inline-flex items-center gap-1">
                                                    <input type="number" step="0.25" min="0" max="24"
                                                           wire:model.live.debounce.300ms="addDaysBreakdown.{{ $index }}.hours"
                                                           @disabled(!$isEnabled)
                                                           class="w-16 border rounded p-1 text-center font-mono text-xs {{ $isEnabled ? 'border-gray-300 bg-white' : 'border-gray-200 bg-gray-100 text-gray-400' }}">
                                                    <span class="text-[10px] text-gray-500">h</span>
                                                </div>
                                            </td>
                                            <td class="p-2 align-middle">
                                                @if($isEnabled)
                                                    @if($hasBreak)
                                                        <div class="flex items-center gap-1.5">
                                                            <select wire:model.live="addDaysBreakdown.{{ $index }}.break_minutes"
                                                                    class="border border-amber-300 rounded p-1 text-[11px] bg-amber-50 font-medium text-amber-900">
                                                                <option value="15">15m</option>
                                                                <option value="30">30m</option>
                                                                <option value="45">45m</option>
                                                                <option value="60">60m (1h)</option>
                                                                <option value="90">90m</option>
                                                            </select>
                                                            <button type="button" wire:click="toggleAddDayBreak({{ $index }})"
                                                                    title="Remove break"
                                                                    class="text-red-400 hover:text-red-600 font-bold text-xs leading-none">&times;</button>
                                                        </div>
                                                    @else
                                                        <button type="button" wire:click="toggleAddDayBreak({{ $index }})"
                                                                class="px-2 py-0.5 rounded text-[10px] border border-dashed border-gray-300 text-gray-500 hover:border-[#005a9c] hover:text-[#005a9c] transition">
                                                            + Add Break
                                                        </button>
                                                    @endif
                                                @else
                                                    <span class="text-gray-300 text-[10px]">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-right align-middle font-mono font-semibold {{ $isEnabled ? 'text-gray-800' : 'text-gray-300' }}">
                                                {{ \App\Services\ReportDataService::formatDurationHM($netMins) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="p-6 text-center text-gray-400">
                                                Select a valid date range to view the day-by-day split.
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Summary Metrics Box --}}
                            <div class="bg-blue-50/60 border border-blue-200 rounded-lg p-3 text-xs">
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div>
                                        <span class="text-gray-500 block text-[11px]">Days Selected</span>
                                        <strong class="text-xs text-gray-800">{{ $totalDaysCount }} day(s)</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[11px]">Total Breaks</span>
                                        <strong class="text-xs text-amber-800">{{ $totalBreakMins }} min(s)</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[11px]">Total Work Time</span>
                                        <strong class="text-xs text-[#005a9c] font-bold">
                                            {{ \App\Services\ReportDataService::formatDurationHM($totalNetMins) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50">
                    <button type="button" wire:click="cancelAdd" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 bg-white border border-gray-300 rounded-lg">Cancel</button>
                    <button type="button" wire:click="saveAdd" class="px-4 py-2 text-xs bg-[#005a9c] hover:bg-sky-800 text-white font-bold rounded-lg shadow-sm">
                        {{ (auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin()) && $addStatus === 'approved' ? 'Add & Approve Time' : 'Submit for Approval' }}
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- QUICK EDIT MODAL (with Add/Edit Break) --}}
    @if($editingLogId)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Edit Time Entry</h3>
                    <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-700">Job*</label>
                            <select wire:model="editAssignment" class="w-full border rounded-lg p-2 text-xs mt-1">
                                @foreach($assignments as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            @error('editAssignment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-700">Task*</label>
                            <select wire:model="editTask" class="w-full border rounded-lg p-2 text-xs mt-1">
                                @foreach($tasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('editTask') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Clock In*</label>
                                <input type="datetime-local" wire:model="editClockIn" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                @error('editClockIn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Clock Out</label>
                                <input type="datetime-local" wire:model="editClockOut" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                @error('editClockOut') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- EDIT BREAK SECTION --}}
                        <div class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-700">
                                <input type="checkbox" wire:model.live="editHasBreak" class="rounded text-[#005a9c]">
                                <span>Break Time (Deducted from payable hours)</span>
                            </label>

                            @if($editHasBreak)
                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-200">
                                    <div>
                                        <label class="text-[11px] font-medium text-gray-600">Break Start</label>
                                        <input type="time" wire:model.live="editBreakStart" class="w-full border rounded p-1 text-xs mt-0.5">
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-medium text-gray-600">Break End</label>
                                        <input type="time" wire:model.live="editBreakEnd" class="w-full border rounded p-1 text-xs mt-0.5">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-700">
                                Reason / Edit Notes* <span class="text-red-500 text-[10px] font-normal">(Required)</span>
                            </label>
                            <textarea wire:model="editNotes" rows="4" class="w-full border rounded-lg p-2 text-xs mt-1"
                                      placeholder="Explain edits made..."></textarea>
                            @error('editNotes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-6 pt-3 border-t border-gray-100">
                    <button wire:click="deleteLog({{ $editingLogId }})"
                            wire:confirm="Are you sure you want to permanently delete this time entry?"
                            class="px-3 py-1.5 text-xs text-red-600 hover:text-red-800 border border-red-200 hover:bg-red-50 rounded-lg font-semibold">
                        Delete Entry
                    </button>
                    <div class="flex gap-2">
                        <button wire:click="cancelEdit" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">Cancel</button>
                        <button wire:click="saveEdit" class="px-4 py-2 text-xs bg-cs-blue hover:bg-blue-800 text-white font-bold rounded-lg shadow">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- COPY TO OTHER DAYS MODAL (Requirement 6) --}}
    @if($showCopyModal)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl p-6 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">&#128203;</span>
                        <h3 class="text-base font-bold text-gray-900">Copy Time Entry to Other Days</h3>
                    </div>
                    <button wire:click="cancelCopy" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    {{-- Left Column: Target Days & Times --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Select Target Days to Copy Into*
                            </label>
                            <div class="grid grid-cols-2 gap-1.5 max-h-48 overflow-y-auto p-1 border rounded-lg bg-gray-50/50">
                                @foreach($availableCopyDays as $dayOption)
                                    <label class="flex items-center gap-1.5 p-1.5 border rounded cursor-pointer text-xs transition
                                        {{ in_array($dayOption['date'], $copyTargetDates) ? 'border-[#005a9c] bg-blue-50/80 font-bold text-[#005a9c]' : 'border-gray-200 hover:bg-white text-gray-700 bg-white' }}">
                                        <input type="checkbox" wire:model="copyTargetDates" value="{{ $dayOption['date'] }}" class="rounded text-[#005a9c] text-xs">
                                        <span class="truncate">{{ $dayOption['label'] }}</span>
                                        @if($dayOption['is_today'])
                                            <span class="text-[9px] bg-amber-100 text-amber-800 px-1 rounded ml-auto">Today</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            @error('copyTargetDates') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Times --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Start Time*</label>
                                <input type="time" wire:model="copyStartTime" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                @error('copyStartTime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">End Time*</label>
                                <input type="time" wire:model="copyEndTime" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                @error('copyEndTime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Job/Task, Notes & Attachments --}}
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Job*</label>
                                <select wire:model="copyAssignmentId" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                    @foreach($assignments as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                                @error('copyAssignmentId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700">Task*</label>
                                <select wire:model="copyTaskId" class="w-full border rounded-lg p-1.5 text-xs mt-1">
                                    @foreach($tasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('copyTaskId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-700">
                                Reason / Notes* <span class="text-red-500 text-[10px] font-normal">(Required)</span>
                            </label>
                            <textarea wire:model="copyNotes" rows="2" placeholder="State reasons for copying..."
                                      class="w-full border rounded-lg p-1.5 text-xs mt-1 focus:ring-1 focus:ring-[#005a9c]"></textarea>
                            @error('copyNotes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-2.5">
                            <label class="text-xs font-semibold text-gray-700 flex items-center justify-between">
                                <span>Proof Attachments</span>
                                <span class="text-[10px] text-gray-400 font-normal">Optional</span>
                            </label>
                            <input type="file" wire:model="copyAttachments" multiple class="w-full text-xs text-gray-500 mt-1 block">
                            @error('copyAttachments.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 text-[11px] text-amber-800 flex items-start gap-1.5">
                            <span class="text-sm leading-none">ℹ️</span>
                            <span>Copied entries are flagged as <strong>Manual Time</strong>, marked unapproved, and sent to HR for verification.</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5 pt-3 border-t border-gray-100">
                    <button wire:click="cancelCopy" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveCopy" class="px-5 py-2 text-xs bg-cs-blue hover:bg-blue-800 text-white font-bold rounded-lg shadow">
                        {{ (auth()->user()->isAdmin() || auth()->user()->isHrManager()) ? 'Copy Directly' : 'Copy & Submit for HR Approval' }}
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
