<div>
    {{-- TOAST --}}
    @if($toastMessage)
        <div class="mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            <div class="flex justify-between">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" class="opacity-60">&times;</button>
            </div>
        </div>
    @endif

    {{-- PAGE TITLE + TOP ACTIONS --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal text-gray-800">Timesheets</h1>

        <div class="flex items-center gap-2">
            <button wire:click="openAdd({{ auth()->id() }}, '{{ \Carbon\Carbon::now()->toDateString() }}')"
                    class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold">
                Add Time
            </button>

            <button type="button"
                    class="border border-cs-blue text-cs-blue hover:bg-blue-50 px-4 py-2 rounded text-sm font-semibold flex items-center gap-1">
                + Time Off <span class="text-[9px]">&#9660;</span>
            </button>
        </div>
    </div>

    {{-- TOOLBAR --}}
    <div class="flex justify-between items-center mb-3">
        <div class="flex items-center gap-4">
            <button wire:click="previousWeek" class="text-gray-500 hover:text-gray-800 text-lg">&#9664;</button>
            <button wire:click="nextWeek"     class="text-gray-500 hover:text-gray-800 text-lg">&#9654;</button>

            <h2 class="text-lg font-semibold text-gray-800">
                This Week
                <span class="font-normal text-gray-500 ml-1">{{ $startOfWeek->format('M j') }} &ndash; {{ $endOfWeek->format('M j, Y') }}</span>
                <span class="text-[10px] text-gray-500">&#9660;</span>
            </h2>

            <button wire:click="thisWeek" class="text-xs text-cs-blue underline">Reset</button>
        </div>

        <div class="flex items-center gap-3">
            {{-- Printer --}}
            <button type="button" onclick="window.print()"
                    class="text-gray-500 hover:text-gray-800 text-lg" title="Print">&#128424;</button>

            {{-- Timezone --}}
            <button type="button"
                    class="text-sm text-gray-700 flex items-center gap-1 hover:text-gray-900">
                {{ $currencyOrTz }} <span class="text-[9px]">&#9660;</span>
            </button>

            {{-- Employees filter --}}
            <div class="relative">
                <select wire:model.live="employeeFilter"
                        class="appearance-none bg-transparent text-sm text-gray-700 pr-5 focus:outline-none cursor-pointer">
                    <option value="">Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-gray-500">&#9660;</span>
            </div>

            {{-- Filter icon --}}
            <button type="button" class="text-gray-500 hover:text-gray-800 text-lg" title="More filters">
                &#9776;
            </button>
        </div>
    </div>

    {{-- GRID --}}
    <div class="border border-gray-300 bg-white overflow-x-auto">
        <table class="w-full table-fixed border-collapse">
            <colgroup>
                <col style="width: 180px;">
                @foreach(range(0,6) as $i)
                    <col style="width: 150px;">
                @endforeach
                <col style="width: 150px;">
            </colgroup>

            <thead>
            <tr class="border-b border-gray-300">
                <th class="text-left align-top px-3 py-3 text-xs font-bold text-gray-700 uppercase tracking-wide border-r border-gray-300">
                    Employees
                </th>
                @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $i => $day)
                    @php $d = $startOfWeek->copy()->addDays($i); @endphp
                    <th class="text-left align-top px-3 py-3 text-xs font-bold text-gray-700 uppercase tracking-wide border-r border-gray-300">
                        {{ $day }}
                        <div class="text-[11px] font-normal text-gray-500 lowercase">{{ $d->month }}/{{ $d->day }}</div>
                    </th>
                @endforeach
                <th class="text-left align-top px-3 py-3 text-xs font-bold text-gray-700 uppercase tracking-wide">
                    Total
                    <div class="text-[11px] font-normal text-gray-500 lowercase">{{ $startOfWeek->format('M j') }} &ndash; {{ $endOfWeek->format('M j, Y') }}</div>
                </th>
            </tr>
            </thead>

            <tbody>
            @foreach($employees as $employee)
                <tr class="border-b border-gray-300">
                    {{-- EMPLOYEE COLUMN --}}
                    <td class="align-top px-3 py-4 border-r border-gray-300">
                        <div class="flex items-start gap-2">
                            <div class="w-7 h-7 rounded-full bg-cs-blue text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                {{ strtoupper(substr($employee->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-[13px] text-gray-800 uppercase tracking-wide truncate">
                                    {{ $employee->name }}
                                </div>
                                @php
                                    $activeLog = \App\Models\TimeLog::where('employee_id', $employee->id)
                                        ->whereNull('clock_out')
                                        ->latest()
                                        ->first();
                                @endphp
                                @if($activeLog)
                                    <div class="text-[11px] text-gray-500 leading-tight mt-1">
                                        Clocked in for <span class="italic">{{ $activeLog->assignment->name ?? '—' }}</span>
                                        doing <span class="italic">{{ $activeLog->task->name ?? '—' }}</span>
                                        {{ $activeLog->clock_in->diffForHumans() }}
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
                        <td class="align-top p-1 border-r border-gray-300">
                            @if(!empty($entries))
                                <div class="space-y-1">
                                    @foreach($entries as $entry)
                                        @php
                                            $mins = $entry->clock_out
                                                ? $entry->duration_minutes
                                                : (int) ceil($entry->netSeconds() / 60);
                                            $isManual = $entry->isManual();
                                        @endphp
                                        <div
                                            wire:click="openDetail({{ $entry->id }})"
                                            class="cursor-pointer border {{ $isManual ? 'bg-yellow-50 border-yellow-200' : 'bg-white border-gray-200' }} rounded p-2 hover:shadow">
                                            <div class="text-[13px] text-gray-800 mb-1">
                                                {{ number_format($mins / 60, 0) }} h:m
                                            </div>
                                            <div class="text-[11px] text-gray-700 flex items-center gap-1">
                                                <span>{{ $entry->clock_in->format('g:i') }}</span>
                                                <span class="flex-1 border-t border-dashed border-gray-400"></span>
                                                <span>{{ $entry->clock_out?->format('g:ia') ?? 'running' }}</span>
                                            </div>
                                            <div class="text-[11px] mt-1 pl-2 border-l-2 border-cs-blue text-gray-800 truncate">
                                                {{ $entry->assignment->name ?? '—' }}
                                            </div>
                                        </div>
                                    @endforeach

                                    <button wire:click="openAdd({{ $employee->id }}, '{{ $dayKey }}')"
                                            class="w-full text-center text-gray-300 hover:text-cs-blue text-lg leading-none">
                                        +
                                    </button>
                                </div>
                            @else
                                <button wire:click="openAdd({{ $employee->id }}, '{{ $dayKey }}')"
                                        class="w-full h-full min-h-[80px] text-center text-gray-300 hover:text-cs-blue text-lg">
                                    +
                                </button>
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
                        {{ floor($empTotal / 60) }} h:{{ str_pad($empTotal % 60, 2, '0', STR_PAD_LEFT) }}

                        @if(!empty($employeeApproved[$employee->id]))
                            <span class="absolute top-0 right-0 text-green-600 text-xl leading-none">&#10004;</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- TOTALS ROW --}}
            <tr>
                <td class="px-3 py-3 text-[13px] text-gray-700 border-r border-gray-300 font-semibold">Total</td>
                @foreach(range(0,6) as $i)
                    @php $dayKey = $startOfWeek->copy()->addDays($i)->toDateString(); @endphp
                    <td class="px-3 py-3 border-r border-gray-300 text-[13px] text-gray-700">
                        @php $dm = $dayTotals[$dayKey] ?? 0; @endphp
                        @if($dm > 0)
                            {{ floor($dm / 60) }} h:{{ str_pad($dm % 60, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>
                @endforeach
                <td class="px-3 py-3 text-[13px] text-gray-800 font-semibold">
                    {{ floor($weekTotal / 60) }} h:{{ str_pad($weekTotal % 60, 2, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    {{-- ADD MODAL --}}
    @if($showAddModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">Add Time</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Job*</label>
                        <select wire:model="addAssignment" class="w-full border rounded p-2 text-sm">
                            <option value="">Select Job</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('addAssignment') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Task*</label>
                        <select wire:model="addTask" class="w-full border rounded p-2 text-sm">
                            <option value="">Select Task</option>
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('addTask') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Clock In*</label>
                            <input type="datetime-local" wire:model="addClockIn" class="w-full border rounded p-2 text-sm">
                            @error('addClockIn') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Clock Out*</label>
                            <input type="datetime-local" wire:model="addClockOut" class="w-full border rounded p-2 text-sm">
                            @error('addClockOut') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notes</label>
                        <textarea wire:model="addNotes" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelAdd" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveAdd" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
