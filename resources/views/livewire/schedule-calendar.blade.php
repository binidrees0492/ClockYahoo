<div>

    {{-- HEADER BAR --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3">
            <button wire:click="previousMonth" class="text-gray-500 hover:text-gray-800 text-lg p-1">&#9664;</button>
            <button wire:click="nextMonth"     class="text-gray-500 hover:text-gray-800 text-lg p-1">&#9654;</button>

            <h2 class="text-lg font-bold text-gray-800 ml-1 flex items-center gap-2">
                <span>{{ $anchor->format('F Y') }}</span>
                <button wire:click="thisMonth" class="text-xs text-cs-blue underline font-normal">Today</button>
            </h2>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Employee Filter for HR, Managers (their dept & own view), and Super Admins --}}
            @if(!$isMySchedule && ($isAllowedToFilter || $isManager))
                <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded px-2.5 py-1">
                    <span class="text-xs text-gray-500 font-semibold">Employee:</span>
                    <select wire:change="setEmployee($event.target.value)"
                            class="text-xs font-bold text-gray-800 bg-transparent focus:outline-none cursor-pointer">
                        <option value="" @selected(empty($selectedEmployeeId) || $selectedEmployeeId === 'all')>All Team Members</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($emp->id == $selectedEmployeeId)>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- View mode selector --}}
            <select wire:model="viewMode" wire:change="setView($event.target.value)"
                    class="text-xs border border-gray-300 rounded px-2.5 py-1.5 bg-white focus:outline-none focus:border-blue-500 font-medium">
                <option value="month">Month View</option>
                <option value="week">Week View</option>
            </select>

            {{-- Add Shift Button --}}
            @if($isManager || auth()->user()->canEditSchedules())
                <button wire:click="openAddShift()"
                        class="px-3 py-1.5 bg-cs-blue hover:bg-blue-800 text-white rounded text-xs font-bold flex items-center gap-1 shadow-xs transition">
                    <span>+</span> Add Shift
                </button>
            @endif
        </div>
    </div>

    {{-- DAY-NAME ROW --}}
    <div class="grid grid-cols-7 border border-gray-200 bg-gray-50/70">
        @foreach($dayHeaders ?? ['THU','FRI','SAT','SUN','MON','TUE','WED'] as $dow)
            <div class="text-center text-xs font-bold text-gray-700 py-2 border-b border-gray-200">
                {{ strtoupper($dow) }}
            </div>
        @endforeach
    </div>

    {{-- CALENDAR GRID --}}
    <div class="grid grid-cols-7 border-l border-r border-b border-gray-200 bg-white">
        @foreach($days as $day)
            @php
                $key         = $day->toDateString();
                $isCurrent   = $day->month === $anchor->month && $day->year === $anchor->year;
                $isToday     = $key === $todayKey;
                $daySchedules= $schedules[$key] ?? collect();
                $dayLogs     = $timeLogs[$key] ?? collect();
            @endphp

            <div class="min-h-[140px] border-r border-b border-gray-200 p-2 relative group flex flex-col justify-between
                        {{ $isToday ? 'bg-cs-today' : 'bg-white' }}
                        {{ !$isCurrent ? 'text-gray-300 bg-gray-50/40' : 'text-gray-700' }}">

                <div>
                    {{-- Cell Header: Day Number & Clickable + Shift --}}
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        @if($isManager || auth()->user()->canEditSchedules())
                            <button type="button" wire:click="openAddShift('{{ $key }}')"
                                    title="Add shift on {{ $day->format('M j') }}"
                                    class="opacity-0 group-hover:opacity-100 text-[10px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-cs-blue hover:bg-cs-blue hover:text-white transition">
                                + Shift
                            </button>
                        @else
                            <span></span>
                        @endif

                        <button type="button"
                                wire:click="$dispatch('navigate-to-timesheet', { date: '{{ $key }}', employeeId: {{ $selectedEmployeeId ? (is_numeric($selectedEmployeeId) ? $selectedEmployeeId : '\'' . $selectedEmployeeId . '\'') : 'null' }} })"
                                title="Click to view {{ $day->format('M j') }} week in My Timesheet"
                                class="text-xs font-bold hover:underline {{ !$isCurrent ? 'text-gray-400' : ($isToday ? 'text-cs-blue font-extrabold' : 'text-gray-700 hover:text-cs-blue') }} flex items-center gap-1">
                            <span>{{ $day->day }}</span>
                            <span class="text-[9px] opacity-0 group-hover:opacity-75 text-cs-blue font-normal" title="Open week in Timesheet">↗</span>
                        </button>
                    </div>

                    @if($isCurrent)
                        {{-- Logged Time Entries (24H Format) --}}
                        @foreach($dayLogs as $log)
                            <div wire:click="$dispatch('open-log-detail', { logId: {{ $log->id }} })"
                                 class="text-[10px] leading-tight mb-1 p-1 bg-blue-50/80 hover:bg-blue-100 rounded border border-blue-200 cursor-pointer transition text-gray-700 shadow-2xs">
                                <div class="font-bold text-cs-blue flex justify-between items-center">
                                    <span>⏱️ {{ $log->clock_in->format('H:i') }}</span>
                                    <span class="text-[9px] text-gray-500 font-semibold">{{ number_format(($log->duration_minutes ?: 0) / 60, 1) }}h</span>
                                </div>
                                @if($log->assignment)
                                    <div class="text-gray-600 truncate font-medium mt-0.5">{{ $log->assignment->name }}</div>
                                @endif
                            </div>
                        @endforeach

                        {{-- Scheduled Shifts (24H Format & Draft/Publish Status) --}}
                        @foreach($daySchedules as $s)
                            @php
                                $isDraft = $s->status === 'draft' || !$s->is_published;
                            @endphp
                            <div wire:click="viewSchedule({{ $s->id }})"
                                 class="text-[10px] leading-tight mb-1 p-1.5 rounded cursor-pointer transition shadow-2xs
                                        {{ $isDraft ? 'bg-amber-50/90 hover:bg-amber-100 border border-dashed border-amber-300 text-amber-900' : 'bg-emerald-50/90 hover:bg-emerald-100 border border-emerald-200 text-emerald-900' }}">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="{{ $isDraft ? 'text-amber-800' : 'text-emerald-800' }}">
                                        📅 {{ substr($s->start_time, 0, 5) }} &ndash; {{ substr($s->end_time, 0, 5) }}
                                    </span>
                                    @if($isDraft)
                                        <span class="text-[9px] font-bold uppercase px-1 rounded bg-amber-200 text-amber-900">Draft</span>
                                    @endif
                                </div>
                                @if(empty($selectedEmployeeId) || $selectedEmployeeId === 'all')
                                    <div class="truncate text-[9px] font-bold text-gray-700 mt-0.5">
                                        👤 {{ $s->employee?->name }}
                                    </div>
                                @endif
                                @if($s->assignment)
                                    <div class="truncate font-semibold mt-0.5 {{ $isDraft ? 'text-amber-800' : 'text-emerald-800' }}">
                                        {{ $s->assignment->name }}
                                    </div>
                                @endif
                                @if($s->task)
                                    <div class="truncate text-[9px] opacity-75">{{ $s->task->name }}</div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Quick Add Cell Bottom Overlay on Hover --}}
                @if($isCurrent && ($isManager || auth()->user()->canEditSchedules()))
                    <div class="opacity-0 group-hover:opacity-100 pt-1 text-center">
                        <button type="button" wire:click="openAddShift('{{ $key }}')"
                                class="text-[10px] text-gray-400 hover:text-cs-blue transition font-semibold">
                            + Add
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- VIEW SCHEDULE DETAILS MODAL --}}
    @if($selectedSchedule)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative">
                <button wire:click="closeScheduleModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>

                <div class="flex items-center gap-2 mb-4">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-lg {{ ($selectedSchedule->status === 'draft' || !$selectedSchedule->is_published) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        📅
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-gray-800">Shift Details</h3>
                            @if($selectedSchedule->status === 'draft' || !$selectedSchedule->is_published)
                                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    Draft (Unpublished)
                                </span>
                            @else
                                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    ✓ Published (Active)
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">{{ $selectedSchedule->shift_date?->format('l, F j, Y') }}</p>
                    </div>
                </div>

                {{-- Status explanation notice --}}
                @if($selectedSchedule->status === 'draft' || !$selectedSchedule->is_published)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs text-amber-900 mb-3 flex items-start gap-2">
                        <span class="text-amber-600 font-bold">⚠️</span>
                        <span>This shift is currently a <strong>Draft</strong>. It is NOT yet available for the employee to clock into. Click &ldquo;Publish Shift&rdquo; to activate it.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs bg-gray-50 p-4 rounded-lg border border-gray-100 mb-4">
                    <div class="flex justify-between border-b pb-1.5">
                        <span class="text-gray-500">Assigned Employee:</span>
                        <span class="font-bold text-gray-800">{{ $selectedSchedule->employee->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-1.5">
                        <span class="text-gray-500">Shift Hours (24H):</span>
                        <span class="font-bold text-gray-900">{{ substr($selectedSchedule->start_time, 0, 5) }} &ndash; {{ substr($selectedSchedule->end_time, 0, 5) }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-1.5">
                        <span class="text-gray-500">Job / Assignment:</span>
                        <span class="font-semibold text-gray-800">{{ $selectedSchedule->assignment->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-1.5">
                        <span class="text-gray-500">Allocated Shift Hours:</span>
                        <span class="font-bold text-emerald-700">{{ number_format($selectedSchedule->standard_hours ?: 8, 2) }} hrs</span>
                    </div>
                    <div class="flex justify-between border-b pb-1.5">
                        <span class="text-gray-500">Task:</span>
                        <span class="font-semibold text-gray-800">{{ $selectedSchedule->task->name ?? '—' }}</span>
                    </div>
                    @if($selectedSchedule->location_name || $selectedSchedule->latitude)
                        <div class="flex justify-between border-b pb-1.5">
                            <span class="text-gray-500">Location / GPS:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $selectedSchedule->location_name ?: 'Designated Coordinates' }}
                                @if($selectedSchedule->latitude)
                                    ({{ number_format($selectedSchedule->latitude, 3) }}, {{ number_format($selectedSchedule->longitude, 3) }})
                                @endif
                            </span>
                        </div>
                    @endif
                    @if($selectedSchedule->notes)
                        <div class="sm:col-span-2 pt-1 border-t border-gray-200">
                            <span class="text-gray-500 block mb-0.5">Notes:</span>
                            <span class="text-gray-700 italic">{{ $selectedSchedule->notes }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        @if($isManager || auth()->user()->canEditSchedules())
                            @if($selectedSchedule->status === 'draft' || !$selectedSchedule->is_published)
                                <button type="button" wire:click="publishShift({{ $selectedSchedule->id }})"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold shadow-xs transition">
                                    🚀 Publish Shift
                                </button>
                            @endif
                            <button type="button" wire:click="openEditShift({{ $selectedSchedule->id }})"
                                        class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 rounded text-xs font-semibold transition">
                                ✏️ Edit
                            </button>
                            <button type="button" wire:click="deleteShift({{ $selectedSchedule->id }})"
                                    wire:confirm="Are you sure you want to permanently delete this shift?"
                                    class="px-3 py-1.5 bg-white border border-red-200 hover:bg-red-50 text-red-600 rounded text-xs font-semibold transition">
                                🗑️ Delete
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="$dispatch('navigate-to-timesheet', { date: '{{ $selectedSchedule->shift_date?->toDateString() }}', employeeId: {{ $selectedSchedule->employee_id }} }); closeScheduleModal()"
                                class="px-3 py-1.5 bg-blue-50 border border-blue-200 hover:bg-blue-100 text-cs-blue rounded text-xs font-semibold inline-flex items-center gap-1 transition">
                            <span>📅</span> Open in Timesheet
                        </button>
                        <button wire:click="closeScheduleModal" class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded text-xs font-semibold">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ADD / EDIT SHIFT MODAL (Identical modal to schedule-board) --}}
    @if($showShiftModal || $showAddShift)
        @php
            $isMultiDay = !($editingScheduleId || $editingShiftId) && count($this->shiftDaysBreakdown) > 1;
        @endphp
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-xl w-full {{ $isMultiDay ? 'max-w-6xl' : 'max-w-2xl' }} p-5 sm:p-6 max-h-[92vh] overflow-hidden flex flex-col my-auto transition-all duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ ($editingScheduleId || $editingShiftId) ? 'Edit Shift' : 'Add Shift' }}
                        </h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                            Drafted by default
                        </span>
                    </div>
                    <button type="button" wire:click="cancelShift" class="text-gray-400 hover:text-gray-700 text-xl font-bold leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-1 {{ $isMultiDay ? 'lg:grid-cols-12' : '' }} gap-5 overflow-y-auto flex-1 pr-1 text-xs">
                    {{-- Left Column: Shift Details & Location (col-span-5 if multi-day, full width if single day) --}}
                    <div class="{{ $isMultiDay ? 'lg:col-span-5' : '' }} space-y-3">
                        {{-- Employee Selection (Multi-select for new shifts, single for edit) --}}
                        <div>
                            @if($editingScheduleId || $editingShiftId)
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Employee*</label>
                                @if($isManager)
                                    <select wire:model="shiftEmployeeId" class="w-full border rounded p-2 text-xs bg-white">
                                        <option value="">Select Employee</option>
                                        @foreach($employees as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" disabled value="{{ auth()->user()->name }}" class="w-full border border-gray-200 bg-gray-50 rounded p-2 text-xs text-gray-600">
                                @endif
                            @else
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-semibold text-gray-700">Assigned Employees* ({{ count($shiftEmployeeIds) }} selected)</label>
                                    @if($isManager)
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <button type="button" wire:click="selectAllEmployees" class="text-cs-blue hover:underline font-semibold">Select All</button>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" wire:click="clearEmployees" class="text-gray-500 hover:underline">Clear</button>
                                        </div>
                                    @endif
                                </div>
                                @if($isManager)
                                    <div class="max-h-28 overflow-y-auto border border-gray-300 rounded p-1.5 space-y-1 bg-white">
                                        @foreach($employees as $e)
                                            <label class="flex items-center gap-2 px-1.5 py-0.5 rounded hover:bg-blue-50 cursor-pointer text-xs">
                                                <input type="checkbox" wire:model.live="shiftEmployeeIds" value="{{ $e->id }}" class="rounded text-[#005a9c]">
                                                <span class="text-gray-800 font-medium">{{ $e->name }}</span>
                                                <span class="text-[10px] text-gray-400">({{ $e->role }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <input type="text" disabled value="{{ auth()->user()->name }}" class="w-full border border-gray-200 bg-gray-50 rounded p-2 text-xs text-gray-600">
                                @endif
                            @endif
                            @error('shiftEmployeeIds') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            @error('shiftEmployeeId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Job & Task (Multi-select for new shifts, single for edit) --}}
                        @if($editingScheduleId || $editingShiftId)
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Job (Active/Ongoing)*</label>
                                <select wire:model.live="shiftAssignmentId" class="w-full border rounded p-2 text-xs bg-white">
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
                        @if($editingScheduleId || $editingShiftId)
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Shift Date*</label>
                                <input type="date" wire:model.live="shiftDateFrom" class="w-full border rounded p-2 text-xs bg-white">
                                @error('shiftDateFrom') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                @error('shiftDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-700 block mb-1">Date From*</label>
                                        <input type="date" wire:model.live="shiftDateFrom" class="w-full border rounded p-2 text-xs bg-white">
                                        @error('shiftDateFrom') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                        @error('shiftDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-700 block mb-1">Date To*</label>
                                        <input type="date" wire:model.live="shiftDateTo" class="w-full border rounded p-2 text-xs bg-white">
                                        @error('shiftDateTo') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                        @error('shiftEndDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
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
                                @error('startTime') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">End*</label>
                                <input type="time" wire:model.live="shiftEnd" class="w-full border rounded p-2 text-xs bg-white">
                                @error('shiftEnd') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                @error('endTime') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-700 block mb-1">Std Hours*</label>
                                <input type="number" step="0.25" min="0.5" max="24" wire:model.live.debounce.300ms="shiftStandardHours" class="w-full border rounded p-2 text-xs bg-white" placeholder="8.00">
                                @error('shiftStandardHours') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                @error('standardHours') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Location & GPS Geofencing (Site Preset & Map) --}}
                        <div x-data="calendarShiftMapPicker()" @coordinates-updated.window="handleCoordinatesUpdated($event.detail)" class="p-3 bg-sky-50/60 border border-sky-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-800 flex items-center gap-1.5 text-xs">
                                    <span>📍</span> Location & Geofencing (Site Preset)
                                </span>
                                @if($locationPresetId || $shiftLocationPresetId)
                                    <span class="text-[9px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">
                                        🔒 Preset Locked (locked to preset)
                                    </span>
                                @endif
                            </div>

                            {{-- Site Preset Dropdown --}}
                            <div>
                                <select wire:model.live="locationPresetId" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white cursor-pointer font-medium focus:ring-1 focus:ring-blue-500">
                                    <option value="">-- No Location / Remote Shift --</option>
                                    @foreach($locationPresets as $preset)
                                        <option value="{{ $preset->id }}">{{ $preset->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="hidden" wire:model="locationName">
                            <input type="hidden" wire:model="latitude">
                            <input type="hidden" wire:model="longitude">
                            <input type="hidden" wire:model="geofenceRadius">

                            @if(($locationPresetId || $shiftLocationPresetId) && ($latitude || $shiftLatitude) && ($longitude || $shiftLongitude))
                                <div class="bg-white border border-sky-200 rounded p-2 text-xs flex justify-between items-center text-gray-700 shadow-2xs">
                                    <span class="font-bold truncate max-w-[200px]" title="{{ $locationName ?: $shiftLocationName }}">🏢 {{ $locationName ?: $shiftLocationName }}</span>
                                    <span class="text-[10px] bg-blue-50 text-blue-700 font-semibold px-1.5 py-0.2 rounded border border-blue-100">Radius: {{ $geofenceRadius ?: ($shiftGeofenceRadius ?: 200) }}m</span>
                                </div>
                            @endif

                            {{-- Leaflet Map Container --}}
                            <div wire:ignore class="rounded-lg overflow-hidden border border-gray-300 relative shadow-inner bg-slate-100">
                                <div x-ref="calendarMapContainer" style="height: 130px; width: 100%;" class="z-0"></div>
                            </div>
                        </div>

                        {{-- Notes --}}
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

                            @if(!($editingScheduleId || $editingShiftId))
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
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200">
                    <div>
                        @if($editingScheduleId || $editingShiftId)
                            <button type="button" wire:click="deleteShift({{ $editingScheduleId ?: $editingShiftId }})"
                                    wire:confirm="Delete this shift?"
                                    class="text-red-500 text-xs font-semibold hover:text-red-700">Delete Shift</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="cancelShift" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">Cancel</button>
                        <button type="button" wire:click="saveShift(false)"
                                class="px-4 py-2 text-xs font-bold bg-gray-800 hover:bg-black text-white rounded-lg shadow-xs transition">
                            💾 Save as Draft (Default)
                        </button>
                        <button type="button" wire:click="saveShift(true)"
                                class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-xs transition">
                            🚀 {{ ($editingScheduleId || $editingShiftId) ? 'Save & Publish' : 'Publish Shift(s)' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    <script>
    function calendarShiftMapPicker() {
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
                const container = this.$refs.calendarMapContainer;
                if (!container || !window.L) return;

                if (this.map) {
                    try { this.map.remove(); } catch (e) {}
                    this.map = null;
                }
                if (container._leaflet_id) {
                    delete container._leaflet_id;
                }
                container.innerHTML = '';

                let initialLat = parseFloat(this.$wire.latitude);
                let initialLng = parseFloat(this.$wire.longitude);
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
                const radius = customRadius !== null ? parseInt(customRadius) : (parseInt(this.$wire.geofenceRadius) || 200);
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
                let lat = parseFloat(this.$wire.latitude);
                let lng = parseFloat(this.$wire.longitude);
                let radius = parseInt(this.$wire.geofenceRadius) || 200;

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
