<div class="relative" x-data="{ closeDropdowns() { $wire.showViewByDropdown = false; $wire.showDatePicker = false; } }">
    {{-- TOOLBAR / HEADER --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Navigation Arrows --}}
            <button wire:click="previousWeek"
                    title="Previous Week"
                    class="w-7 h-7 flex items-center justify-center text-[#003d6b] hover:bg-gray-100 rounded text-base font-bold transition">
                &#9664;
            </button>
            <button wire:click="nextWeek"
                    title="Next Week"
                    class="w-7 h-7 flex items-center justify-center text-[#003d6b] hover:bg-gray-100 rounded text-base font-bold transition">
                &#9654;
            </button>

            {{-- Preset & Date Range Trigger Button (Matching ClockShark: e.g. "2 Weeks Ago Sep 17 – 23, 2026 ▾") --}}
            <div class="relative">
                <button wire:click="toggleDatePicker"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-[#003d6b] rounded text-sm font-bold shadow-2xs transition">
                    <span>{{ $presetLabel }}</span>
                    <span class="font-semibold text-gray-700">{{ $dateRangeString }}</span>
                    <span class="text-xs text-[#003d6b]">&#9662;</span>
                </button>

                {{-- DUAL-PANEL DATE PICKER POPUP (Matching ClockShark media_1791324649305.jpg) --}}
                @if($showDatePicker)
                    <div class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-xl p-4 flex flex-col md:flex-row gap-4 min-w-[320px] md:min-w-[490px]"
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
                        </div>

                        {{-- RIGHT PANEL: MINI CALENDAR --}}
                        <div class="flex-1">
                            {{-- Calendar Header --}}
                            <div class="flex items-center justify-between mb-3 px-1">
                                <button wire:click="prevCalendarMonth" class="text-gray-500 hover:text-gray-800 text-sm font-bold px-1.5 py-0.5 rounded hover:bg-gray-100">
                                    &lt;
                                </button>
                                <span class="text-xs font-bold text-gray-700 tracking-wider">
                                    {{ $calMonthTitle }}
                                </span>
                                <button wire:click="nextCalendarMonth" class="text-gray-500 hover:text-gray-800 text-sm font-bold px-1.5 py-0.5 rounded hover:bg-gray-100">
                                    &gt;
                                </button>
                            </div>

                            {{-- Calendar Days Grid --}}
                            <div class="grid grid-cols-7 gap-y-1 text-center text-xs">
                                @foreach($calendarWeeks[0] ?? [] as $headerDay)
                                    @php
                                        $hDate = \Carbon\Carbon::parse($headerDay['date']);
                                    @endphp
                                    <div class="text-[11px] font-bold text-gray-500 py-1">
                                        {{ substr($hDate->format('D'), 0, 1) }}
                                    </div>
                                @endforeach

                                @foreach($calendarWeeks as $week)
                                    @foreach($week as $dayItem)
                                        <div wire:click="selectCalendarDate('{{ $dayItem['date'] }}')"
                                             class="cursor-pointer py-1.5 text-xs transition relative
                                                {{ $dayItem['is_selected'] ? 'bg-[#d0ebfc] text-[#0077c8] font-bold first:rounded-l-full last:rounded-r-full' : '' }}
                                                {{ !$dayItem['is_current_month'] ? 'text-gray-300' : ($dayItem['is_selected'] ? 'text-[#0077c8]' : 'text-gray-700 hover:bg-gray-100 rounded-full') }}">
                                            @if($dayItem['is_today'])
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-gray-400">
                                                    {{ $dayItem['day'] }}
                                                </span>
                                            @else
                                                <span>{{ $dayItem['day'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#0077c8] border border-blue-200">
                Total: {{ \App\Services\ReportDataService::formatDurationHM($totalMinutes) }} ({{ floor($totalMinutes / 60) }}:{{ str_pad($totalMinutes % 60, 2, '0', STR_PAD_LEFT) }} h:m &bull; {{ number_format($totalMinutes / 60, 2) }} hrs)
            </span>

            {{-- Employee Filter for Managers/HR --}}
            @if($isManagerOrHr && $employees->count() > 1)
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-300 rounded px-2.5 py-1 ml-2">
                    <span class="text-xs text-gray-500 font-semibold">User:</span>
                    <select wire:model.live="selectedEmployeeId"
                            class="text-xs font-bold text-gray-800 bg-transparent focus:outline-none cursor-pointer">
                        <option value="all">👥 All Accessible ({{ $employees->count() }})</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if($copiedLogId)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    📋 Copied
                    <button wire:click="clearCopied" class="ml-0.5 text-emerald-600 hover:text-red-500">&times;</button>
                </span>
                <button wire:click="openPasteModal"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-2xs inline-flex items-center gap-1 transition">
                    📋 Paste to Days
                </button>
            @endif

            @if(auth()->user()?->isSuperAdmin())
                <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer select-none bg-gray-100 hover:bg-gray-200 px-2.5 py-1.5 rounded-md border border-gray-300">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-gray-800">🗑️ Include Deleted Records</span>
                </label>
            @endif

            <button wire:click="openAdd"
                    class="bg-[#0077c8] hover:bg-sky-700 text-white px-3.5 py-1.5 rounded-md text-xs font-semibold shadow-2xs inline-flex items-center gap-1 transition">
                <span>+</span> Add Manual Time
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- TIMESHEET TABLE (EXACT CLOCKSHARK DESIGN: media_1791324649218.jpg) --}}
    {{-- ========================================================================= --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse">
                <colgroup>
                    <col style="width: 200px;">{{-- VIEW BY / JOB / TASK --}}
                    @foreach($days as $d)
                        <col style="width: 110px;">{{-- 7 DAYS --}}
                    @endforeach
                    <col style="width: 90px;">{{-- TOTAL --}}
                </colgroup>

                <thead>
                {{-- HEADER ROW 1: APPROVAL STATS & DAY HEADERS --}}
                <tr class="border-b border-gray-200 bg-white">
                    {{-- 0 of 7 days Approved with Progress Bar --}}
                    <th class="text-left px-3 pt-2.5 pb-2 align-bottom border-r border-gray-200">
                        <div class="text-xs font-semibold text-gray-700">
                            {{ $approvedDaysCount }} of 7 days Approved
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1 mt-1.5 overflow-hidden">
                            <div class="bg-[#0077c8] h-1 rounded-full transition-all duration-300"
                                 style="width: {{ ($approvedDaysCount / 7) * 100 }}%"></div>
                        </div>
                    </th>

                    {{-- 7 Day Headers: THU 17, FRI 18, etc. --}}
                    @foreach($days as $d)
                        @php
                            $isDayApproved = $logs->filter(fn ($l) => $l->clock_in->toDateString() === $d->toDateString())->isNotEmpty()
                                && $logs->filter(fn ($l) => $l->clock_in->toDateString() === $d->toDateString())->every(fn ($l) => (bool)$l->approved);
                        @endphp
                        <th class="text-center px-1 pt-2 pb-1.5 align-bottom border-r border-gray-200">
                            <div class="text-[11px] font-bold text-[#003d6b] uppercase">
                                {{ strtoupper($d->format('D')) }}
                            </div>
                            <div class="text-xs font-bold text-gray-800">
                                {{ $d->format('j') }}
                            </div>
                            <div class="w-full h-1 mt-1 rounded-full {{ $isDayApproved ? 'bg-emerald-500' : 'bg-gray-200' }}"></div>
                        </th>
                    @endforeach

                    {{-- TOTAL COLUMN HEADER --}}
                    <th class="text-center px-2 pt-2.5 pb-2 align-bottom">
                        <div class="text-xs font-bold text-[#003d6b] uppercase tracking-wider">
                            TOTAL
                        </div>
                    </th>
                </tr>

                {{-- HEADER ROW 2: VIEW BY DROPDOWN BUTTON (JOB/TASK ▾) --}}
                <tr class="border-b border-gray-200 bg-gray-50/60">
                    <th class="text-left px-3 py-1.5 border-r border-gray-200 relative">
                        <div class="relative inline-block">
                            <button wire:click="toggleViewByDropdown"
                                    title="View by Job / Task"
                                    class="inline-flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-300 rounded text-xs font-bold text-[#003d6b] hover:bg-gray-50 shadow-2xs transition">
                                <span>{{ $viewBy === 'job_task' ? 'Job / Task' : $viewByLabel }}</span>
                                <span class="text-[10px]">&#9662;</span>
                            </button>

                            {{-- Tooltip & Dropdown Menu (media_1791324649218.jpg) --}}
                            @if($showViewByDropdown)
                                <div class="absolute left-0 top-full mt-1 z-50 bg-white border border-gray-200 rounded-md shadow-lg py-1 w-32 text-xs"
                                     @click.away="$wire.showViewByDropdown = false">
                                    <button wire:click="setViewBy('job_task')"
                                            class="w-full text-left px-3 py-1.5 hover:bg-gray-100 font-medium {{ $viewBy === 'job_task' ? 'text-[#0077c8] font-bold' : 'text-gray-700' }}">
                                        Job / Task
                                    </button>
                                    <button wire:click="setViewBy('task_job')"
                                            class="w-full text-left px-3 py-1.5 hover:bg-gray-100 font-medium {{ $viewBy === 'task_job' ? 'text-[#0077c8] font-bold' : 'text-gray-700' }}">
                                        Task / Job
                                    </button>
                                    <button wire:click="setViewBy('job')"
                                            class="w-full text-left px-3 py-1.5 hover:bg-gray-100 font-medium {{ $viewBy === 'job' ? 'text-[#0077c8] font-bold' : 'text-gray-700' }}">
                                        Job
                                    </button>
                                    <button wire:click="setViewBy('task')"
                                            class="w-full text-left px-3 py-1.5 hover:bg-gray-100 font-medium {{ $viewBy === 'task' ? 'text-[#0077c8] font-bold' : 'text-gray-700' }}">
                                        Task
                                    </button>
                                </div>
                            @endif
                        </div>
                    </th>

                    @foreach($days as $d)
                        <th class="border-r border-gray-200 bg-gray-50/60"></th>
                    @endforeach
                    <th class="bg-gray-50/60"></th>
                </tr>
                </thead>

                <tbody>
                @php
                    $lastGroupTitle = null;
                @endphp

                @forelse($groupedRows as $row)
                    @php
                        $groupTitle = match($viewBy) {
                            'task_job' => $row['task']?->name ?? 'General Tasks',
                            'task'     => null,
                            default    => $row['job']?->name ?? 'General Hours',
                        };

                        $subTitle = match($viewBy) {
                            'task_job' => $row['job']?->name ?? 'General Hours',
                            'job'      => $row['job']?->name ?? 'General Hours',
                            'task'     => $row['task']?->name ?? 'General Tasks',
                            default    => $row['task']?->name ?? 'General Tasks',
                        };
                    @endphp

                    {{-- Group Header Row (e.g. Job Name Header) --}}
                    @if($groupTitle && $groupTitle !== $lastGroupTitle)
                        @php $lastGroupTitle = $groupTitle; @endphp
                        <tr class="bg-gray-100/70 border-b border-gray-200">
                            <td colspan="{{ count($days) + 2 }}" class="px-3 py-1.5 text-xs font-bold text-gray-700">
                                {{ $groupTitle }}
                            </td>
                        </tr>
                    @endif

                    {{-- Data Row --}}
                    <tr class="border-b border-gray-200 hover:bg-gray-50/80 transition text-xs">
                        {{-- Task / Job Name Column --}}
                        <td class="px-3 py-2 border-r border-gray-200 align-middle">
                            <div class="font-medium text-gray-800 truncate" title="{{ $subTitle }}">
                                {{ $subTitle }}
                            </div>
                            @if($row['employee'] && $selectedEmployeeId === 'all')
                                <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                    {{ $row['employee']->name }}
                                </div>
                            @endif
                        </td>

                        {{-- 7 Day Columns (Formatted as H:MM e.g. 8:33, 8:04) --}}
                        @foreach($days as $d)
                            @php
                                $dayKey = $d->toDateString();
                                $dayEntries = $row['logs'][$dayKey] ?? collect();
                                $dayMins = $dayEntries->sum(fn ($l) => $l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60));
                                $firstLog = $dayEntries->first();
                            @endphp
                            <td class="px-1 py-1.5 border-r border-gray-200 align-middle text-center relative group">
                                @if($dayMins > 0)
                                    <div class="cursor-pointer"
                                         @if($firstLog) wire:click="openDetail({{ $firstLog->id }})" @endif>
                                        {{-- ClockShark H:MM Value --}}
                                        <div class="text-xs font-semibold text-gray-800">
                                            {{ \App\Livewire\Timesheets::formatHMM($dayMins) }}
                                        </div>

                                        @if($firstLog && $firstLog->is_archived)
                                            <div class="mt-0.5">
                                                <span class="inline-block px-1 py-0.2 text-[8px] font-bold bg-gray-200 text-gray-700 rounded border border-gray-400">
                                                    Deleted
                                                </span>
                                            </div>
                                            @if(auth()->user()?->isSuperAdmin())
                                                <div class="flex items-center justify-center gap-1.5 mt-0.5">
                                                    <button type="button" wire:click.stop="restoreLog({{ $firstLog->id }})" title="Restore entry" class="text-blue-600 hover:text-blue-800 text-[10px]">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                    </button>
                                                    <button type="button" wire:click.stop="permanentlyDeleteLog({{ $firstLog->id }})" wire:confirm="Permanently delete this record and all child records?" title="Permanently Delete" class="text-red-600 hover:text-red-800 text-[10px]">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Indicators: GPS, Flags, Approval --}}
                                        <div class="flex items-center justify-center gap-1 mt-0.5">
                                            @if($firstLog)
                                                @php
                                                    $policy = $firstLog->task?->gps_policy ?? 'none';
                                                    $hasCoords = !empty($firstLog->clock_in_latitude) && !empty($firstLog->clock_in_longitude);
                                                    $isFlagged = !empty($firstLog->location_flagged);
                                                @endphp

                                                @if($policy !== 'none' && $hasCoords && !$isFlagged)
                                                    <i class="fa-solid fa-location-dot text-emerald-500 text-[9px]" title="GPS Verified"></i>
                                                @elseif($policy !== 'none' && (!$hasCoords || $isFlagged))
                                                    <i class="fa-solid fa-location-dot text-rose-500 text-[9px]" title="GPS missing/flagged"></i>
                                                @endif

                                                @if($firstLog->approved)
                                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[9px]" title="Approved"></i>
                                                @elseif($firstLog->status === 'rejected')
                                                    <i class="fa-solid fa-circle-xmark text-rose-500 text-[9px]" title="Rejected"></i>
                                                @else
                                                    <i class="fa-solid fa-clock text-amber-400 text-[9px]" title="Pending"></i>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Quick Copy Button on Hover --}}
                                    @if($firstLog)
                                        <button wire:click="copyCell({{ $firstLog->id }})"
                                                title="Copy entry"
                                                class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-[#0077c8] text-[9px] transition">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-transparent group-hover:text-gray-300 text-xs select-none">&mdash;</span>
                                @endif

                                {{-- Quick Add button on cell hover --}}
                                <button type="button"
                                        wire:click="openAdd('{{ $dayKey }}', {{ $row['job_id'] ? $row['job_id'] : 'null' }}, {{ $row['task_id'] ? $row['task_id'] : 'null' }}, {{ $row['employee_id'] }})"
                                        title="Add entry"
                                        class="absolute bottom-0.5 right-0.5 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-[#0077c8] text-[10px] font-bold px-1 transition">
                                    +
                                </button>
                            </td>
                        @endforeach

                        {{-- Total Column --}}
                        <td class="px-2 py-2 align-middle text-center font-bold text-gray-800">
                            <div>{{ \App\Services\ReportDataService::formatDurationHM($row['total_minutes']) }}</div>
                            <div class="text-[9px] text-gray-400 font-normal">({{ $row['formatted_total'] }} &bull; {{ number_format($row['total_hours'], 1) }}h)</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 2 }}" class="p-8 text-center text-xs text-gray-400 italic">
                            No time entries logged for this pay period.
                        </td>
                    </tr>
                @endforelse

                {{-- ========================================================================= --}}
                {{-- APPROVED TIME OFF ROWS (ClockShark media_1791324649305.jpg) --}}
                {{-- ========================================================================= --}}
                @if(!empty($timeOffRows))
                    <tr class="bg-purple-50/60 border-t-2 border-purple-200 border-b border-purple-200">
                        <td colspan="{{ count($days) + 2 }}" class="px-3 py-1.5 text-xs font-bold text-purple-900 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🏖️</span> Approved Time Off
                        </td>
                    </tr>
                    @foreach($timeOffRows as $toRow)
                        <tr class="border-b border-gray-200 hover:bg-gray-50/80 transition text-xs">
                            <td class="px-3 py-2 border-r border-gray-200 align-middle">
                                <div class="font-medium text-purple-950 truncate flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 inline-block"></span>
                                    <span>{{ $toRow['policy'] }}</span>
                                </div>
                            </td>
                            @foreach($days as $d)
                                @php
                                    $toM = $toRow['days'][$d->toDateString()] ?? 0;
                                @endphp
                                <td class="px-1 py-1.5 border-r border-gray-200 align-middle text-center">
                                    @if($toM > 0)
                                        <div class="text-xs font-semibold text-purple-700">
                                            {{ \App\Livewire\Timesheets::formatHMM($toM) }}
                                        </div>
                                    @else
                                        <span class="text-gray-300 select-none">&mdash;</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-2 py-2 align-middle text-center font-bold text-purple-900">
                                <div>{{ \App\Services\ReportDataService::formatDurationHM($toRow['total_minutes']) }}</div>
                                <div class="text-[9px] text-gray-400 font-normal">({{ \App\Livewire\Timesheets::formatHMM($toRow['total_minutes'], true) }} &bull; {{ number_format($toRow['total_minutes'] / 60, 1) }}h)</div>
                            </td>
                        </tr>
                    @endforeach
                @endif

                {{-- ========================================================================= --}}
                {{-- SUMMARY ROWS (MATCHING CLOCKSHARK: media_1791324649218.jpg) --}}
                {{-- ========================================================================= --}}
                {{-- 1. REGULAR TIME --}}
                <tr class="border-t-2 border-gray-300 bg-white font-bold text-[#003d6b] text-xs">
                    <td class="px-3 py-2 border-r border-gray-200 uppercase tracking-wider">
                        REGULAR TIME
                    </td>
                    @foreach($days as $d)
                        @php
                            $dKey = $d->toDateString();
                            $m = $regularMinutesByDay[$dKey] ?? 0;
                        @endphp
                        <td class="px-1 py-2 border-r border-gray-200 text-center">
                            @if($m > 0)
                                <span class="font-bold text-[#003d6b]">{{ \App\Livewire\Timesheets::formatHMM($m) }}</span>
                            @else
                                <span class="text-gray-300 font-normal select-none">&mdash;</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-2 py-2 text-center bg-sky-50/40">
                        <div class="font-bold text-[#003d6b]">{{ \App\Services\ReportDataService::formatDurationHM($totalRegularMins) }}</div>
                        <div class="text-[9px] text-gray-500 font-normal">({{ \App\Livewire\Timesheets::formatHMM($totalRegularMins, true) }} &bull; {{ number_format($totalRegularMins / 60, 1) }}h)</div>
                    </td>
                </tr>

                {{-- 2. PAID TIME OFF --}}
                <tr class="border-t border-gray-200 bg-white font-bold text-[#003d6b] text-xs">
                    <td class="px-3 py-2 border-r border-gray-200 uppercase tracking-wider">
                        PAID TIME OFF
                    </td>
                    @foreach($days as $d)
                        @php
                            $dKey = $d->toDateString();
                            $m = $ptoMinutesByDay[$dKey] ?? 0;
                        @endphp
                        <td class="px-1 py-2 border-r border-gray-200 text-center">
                            @if($m > 0)
                                <span class="font-bold text-purple-700">{{ \App\Livewire\Timesheets::formatHMM($m) }}</span>
                            @else
                                <span class="text-gray-300 font-normal select-none">&mdash;</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-2 py-2 text-center bg-purple-50/40">
                        <div class="font-bold text-purple-800">{{ \App\Services\ReportDataService::formatDurationHM($totalPtoMins) }}</div>
                        <div class="text-[9px] text-gray-500 font-normal">({{ \App\Livewire\Timesheets::formatHMM($totalPtoMins, true) }} &bull; {{ number_format($totalPtoMins / 60, 1) }}h)</div>
                    </td>
                </tr>

                {{-- 3. SUBTOTAL --}}
                <tr class="border-t border-gray-200 bg-white font-bold text-[#003d6b] text-xs">
                    <td class="px-3 py-2.5 border-r border-gray-200 uppercase tracking-wider">
                        SUBTOTAL
                    </td>
                    @foreach($days as $d)
                        @php
                            $dKey = $d->toDateString();
                            $m = $subtotalMinutesByDay[$dKey] ?? 0;
                        @endphp
                        <td class="px-1 py-2.5 border-r border-gray-200 text-center">
                            @if($m > 0)
                                <span class="font-bold text-[#0077c8]">{{ \App\Livewire\Timesheets::formatHMM($m) }}</span>
                            @else
                                <span class="text-gray-300 font-normal select-none">&mdash;</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-2 py-2.5 text-center bg-sky-50/60">
                        <div class="font-bold text-[#0077c8]">{{ \App\Services\ReportDataService::formatDurationHM($totalSubtotalMins) }}</div>
                        <div class="text-[9px] text-gray-500 font-normal">({{ \App\Livewire\Timesheets::formatHMM($totalSubtotalMins, true) }} &bull; {{ number_format($totalSubtotalMins / 60, 1) }}h)</div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- PASTE TO DAYS MODAL --}}
    @if($showPasteModal)
        @teleport('body')
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-5 my-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-gray-800">📋 Paste Entry to Days</h3>
                    <button wire:click="cancelPaste" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <p class="text-[11px] text-gray-500 mb-3">
                    The copied time entry will be pasted <strong>individually</strong> for each day in the selected range.
                </p>

                <form wire:submit.prevent="confirmPaste" class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-700 block mb-1">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="pasteStartDate" class="w-full border border-gray-300 rounded p-1.5 text-xs">
                        @error('pasteStartDate') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-700 block mb-1">End Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="pasteEndDate" class="w-full border border-gray-300 rounded p-1.5 text-xs">
                        @error('pasteEndDate') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="cancelPaste" class="px-3 py-1.5 border rounded text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold">Paste</button>
                    </div>
                </form>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ADD MANUAL TIME MODAL (with Right Sidebar Day-by-Day Split & Week-by-Week Navigation) --}}
    @if($showAddModal)
        @teleport('body')
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <span>⏱️</span> Add Manual Time Entry
                        </h3>
                        <p class="text-xs text-gray-500">Record manual work shifts with day-by-day breakdown and break deductions.</p>
                    </div>
                    <button wire:click="cancelAdd" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto text-xs flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        {{-- Left Column: Employee (if manager/hr), Job, Task, Dates & Notes --}}
                        <div class="md:col-span-5 space-y-3.5">
                            @if($isManagerOrHr)
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Employee <span class="text-red-500">*</span></label>
                                    <select wire:model.live="selectedEmployeeId" class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                        @foreach($employees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Job <span class="text-red-500">*</span></label>
                                <select wire:model.live="addAssignment" class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                    <option value="">{{ count($this->modalAssignments) === 0 ? 'No assigned jobs available' : 'Select Job' }}</option>
                                    @foreach($this->modalAssignments as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                                @error('addAssignment') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Task <span class="text-red-500">*</span></label>
                                <select wire:model.live="addTask" class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                    <option value="">{{ !$addAssignment ? 'Select a Job first' : (count($this->modalTasks) === 0 ? 'No assigned tasks for this job' : 'Select Task') }}</option>
                                    @foreach($this->modalTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('addTask') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Start Date <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model.live="addStartDate" class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                    @error('addStartDate') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">End Date <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model.live="addEndDate" class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                    @error('addEndDate') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Standard Shift Hours</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" step="0.5" min="0.5" max="24" wire:model.live.debounce.300ms="addStandardShiftHours"
                                               class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                        <span class="text-gray-500 text-[11px] font-medium">hrs</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 block mb-1">Shift Start Time</label>
                                    <input type="time" wire:model.live="addDefaultStartTime"
                                           class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8] bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">
                                    Reason / Notes <span class="text-red-500">*</span>
                                </label>
                                <textarea wire:model="addNotes" rows="2" placeholder="Explain reason for manual entry..."
                                          class="w-full border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#0077c8]"></textarea>
                                @error('addNotes') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Attachments (Optional)</label>
                                <input type="file" wire:model="addAttachments" multiple class="w-full text-xs text-gray-500 mt-1 block">
                                <span class="text-[10px] text-gray-400">Photos, timesheet slips, or client sign-off (max 10MB each).</span>
                                @error('addAttachments.*') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
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
                                        $totalNetMins += $hMins;
                                        if(!empty($d['has_break'])) {
                                            $totalBreakMins += (int)($d['break_minutes'] ?? 0);
                                        }
                                    }
                                }
                            @endphp

                            <div class="flex justify-between items-center mb-1">
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">Dates & Hours Breakdown</label>
                                    <span class="text-[11px] text-gray-500">Day by day split with standard shift hours & break options</span>
                                </div>
                                @if($totalWeeks > 1)
                                    <div class="text-[11px] font-bold text-[#0077c8] bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
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
                                                    class="px-2 py-0.5 rounded text-[11px] transition {{ $wIdx === $currentPage ? 'bg-[#0077c8] text-white font-bold shadow-2xs' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' }}">
                                                W{{ $wIdx + 1 }} ({{ $w['short_label'] }})
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($currentWeek)
                                <div class="bg-slate-50 border border-gray-200 rounded-t-lg px-3 py-1.5 flex justify-between items-center text-xs text-gray-700">
                                    <span class="font-semibold text-[#0077c8]">{{ $currentWeek['label'] }}</span>
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
                                            $netMins = $isEnabled ? max(0, (int)round($dayHrs * 60)) : 0;
                                        @endphp
                                        <tr class="{{ $day['is_weekend'] ? 'bg-amber-50/40' : 'bg-white' }} {{ !$isEnabled ? 'opacity-60 bg-gray-50/50' : 'hover:bg-blue-50/20' }} transition">
                                            <td class="p-2 text-center align-middle">
                                                <input type="checkbox"
                                                       wire:click="toggleAddDayEnabled({{ $index }})"
                                                       @checked($isEnabled)
                                                       class="rounded text-[#0077c8] cursor-pointer">
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
                                                                class="px-2 py-0.5 rounded text-[10px] border border-dashed border-gray-300 text-gray-500 hover:border-[#0077c8] hover:text-[#0077c8] transition">
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
                                        <strong class="text-xs text-[#0077c8] font-bold">
                                            {{ \App\Services\ReportDataService::formatDurationHM($totalNetMins) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                    <button type="button" wire:click="cancelAdd" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 bg-white border border-gray-300 rounded-lg">Cancel</button>
                    <button type="button" wire:click="saveAdd" class="px-4 py-2 bg-[#0077c8] hover:bg-sky-700 text-white rounded-lg text-xs font-semibold shadow-sm">
                        Submit for Approval
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
