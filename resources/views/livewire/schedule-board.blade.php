<div>
    @if($toastMessage)
        <div class="mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            <div class="flex justify-between">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" class="opacity-60">&times;</button>
            </div>
        </div>
    @endif

    {{-- TOP ROW: TITLE + BUTTONS --}}
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-4xl font-light text-gray-800">Schedules</h1>

        <div class="flex items-center gap-2">
            <button wire:click="openAddShift"
                    class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold">
                Add Shift
            </button>
            <button disabled
                    class="border border-gray-300 text-gray-400 px-5 py-2 rounded text-sm font-semibold cursor-not-allowed">
                Publish
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
                <span class="font-normal text-gray-500 ml-1">
                    {{ $startOfWeek->format('M j') }} &ndash; {{ $endOfWeek->format('M j, Y') }}
                </span>
                <span class="text-[10px] text-gray-500">&#9660;</span>
            </h2>

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

            {{-- Employee dropdown --}}
            <div class="relative">
                <select wire:model.live="employeeFilter"
                        class="appearance-none bg-transparent text-sm text-gray-700 pr-5 focus:outline-none cursor-pointer">
                    <option value="">Employee</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-gray-500">&#9660;</span>
            </div>

            {{-- Filter icon --}}
            <button type="button" class="text-gray-500 hover:text-gray-800 text-lg" title="More filters">&#9776;</button>
        </div>
    </div>

    {{-- MAIN GRID --}}
    <div class="border border-gray-300 bg-white">
        <div class="flex">

            {{-- LEFT SIDEBAR — JOBS --}}
            <div class="w-[200px] shrink-0 border-r border-gray-300">
                <div class="px-3 py-2 border-b border-gray-300 text-xs font-bold uppercase tracking-wide text-gray-700">
                    Jobs
                </div>

                <div class="p-2 space-y-2">
                    @foreach($jobs as $j)
                        <div class="flex items-center gap-2 text-sm text-gray-800">
                            <span class="inline-block w-3 h-3 bg-cs-blue"></span>
                            <span class="truncate">{{ $j->name }}</span>
                        </div>
                    @endforeach

                    <button wire:click="openAddJob"
                            class="text-sm text-cs-blue hover:text-blue-800 mt-2">
                        + Add Job
                    </button>
                </div>
            </div>

            {{-- RIGHT: EMPLOYEE + DAYS GRID --}}
            <div class="flex-1 overflow-x-auto">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 220px;">
                        @foreach(range(0,6) as $i)
                            <col style="width: 150px;">
                        @endforeach
                    </colgroup>

                    <thead>
                    <tr class="border-b border-gray-300">
                        <th class="text-left px-3 py-2 text-xs font-bold uppercase tracking-wide text-gray-700 border-r border-gray-300">
                            Employees
                        </th>
                        @foreach($days as $i => $d)
                            <th class="text-left px-3 py-2 text-xs font-bold uppercase tracking-wide text-gray-700 border-r border-gray-300
                                    {{ $d->toDateString() === $todayKey ? 'bg-cs-today' : '' }}">
                                {{ strtoupper($d->format('D')) }}
                                <span class="font-normal lowercase">{{ $d->month }}/{{ $d->day }}</span>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($employees as $employee)
                        <tr class="border-b border-gray-300">
                            <td class="px-3 py-2 border-r border-gray-300">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block w-2 h-2 rounded-full bg-cs-blue"></span>
                                    <span class="text-sm text-gray-800 uppercase tracking-wide truncate">
                                            {{ $employee->name }}
                                        </span>
                                    <span class="text-xs text-gray-500">({{ $employeeHours[$employee->id] ?? 0 }} hrs)</span>
                                </div>
                            </td>

                            @foreach($days as $i => $d)
                                @php
                                    $dayKey = $d->toDateString();
                                    $entries = $schedules[$dayKey][$employee->id] ?? collect();
                                @endphp
                                <td class="px-2 py-2 border-r border-gray-300 align-top
                                        {{ $dayKey === $todayKey ? 'bg-cs-today' : '' }}">
                                    @foreach($entries as $s)
                                        <div class="bg-blue-50 border border-blue-200 rounded p-2 text-xs mb-1 cursor-pointer"
                                             wire:click="openEditShift({{ $s->id }})">
                                            <div class="text-gray-700">
                                                {{ substr($s->start_time, 0, 5) }} &ndash; {{ substr($s->end_time, 0, 5) }}
                                            </div>
                                            @if($s->assignment)
                                                <div class="text-gray-500 truncate">{{ $s->assignment->name }}</div>
                                            @endif
                                        </div>
                                    @endforeach

                                    <button wire:click="openAddShift({{ $employee->id }}, '{{ $dayKey }}')"
                                            class="w-full text-center text-gray-300 hover:text-cs-blue text-sm">
                                        +
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- ADD EMPLOYEE ROW --}}
                    <tr class="border-b border-gray-300">
                        <td class="px-3 py-2 border-r border-gray-300">
                            <button wire:click="openAddEmployee"
                                    class="text-sm text-cs-blue hover:text-blue-800">
                                + Add Employee
                            </button>
                        </td>
                        @foreach(range(0,6) as $i)
                            <td class="border-r border-gray-300"></td>
                        @endforeach
                    </tr>

                    {{-- EMPTY FILLER ROWS to match ClockShark look --}}
                    @foreach(range(1,4) as $x)
                        <tr class="border-b border-gray-300">
                            <td class="px-3 py-6 border-r border-gray-300"></td>
                            @foreach(range(0,6) as $i)
                                <td class="border-r border-gray-300"></td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ADD / EDIT SHIFT MODAL --}}
    @if($showAddShift)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">
                    {{ $editingShiftId ? 'Edit Shift' : 'Add Shift' }}
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Employee*</label>
                        <select wire:model="shiftEmployeeId" class="w-full border rounded p-2 text-sm">
                            <option value="">Select Employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                        @error('shiftEmployeeId') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Job</label>
                        <select wire:model="shiftAssignmentId" class="w-full border rounded p-2 text-sm">
                            <option value="">— none —</option>
                            @foreach($jobs as $j)
                                <option value="{{ $j->id }}">{{ $j->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Date*</label>
                        <input type="date" wire:model="shiftDate" class="w-full border rounded p-2 text-sm">
                        @error('shiftDate') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Start*</label>
                            <input type="time" wire:model="shiftStart" class="w-full border rounded p-2 text-sm">
                            @error('shiftStart') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">End*</label>
                            <input type="time" wire:model="shiftEnd" class="w-full border rounded p-2 text-sm">
                            @error('shiftEnd') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Notes</label>
                        <textarea wire:model="shiftNotes" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <div>
                        @if($editingShiftId)
                            <button wire:click="deleteShift({{ $editingShiftId }})"
                                    wire:confirm="Delete this shift?"
                                    class="text-red-500 text-sm hover:text-red-700">Delete</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="cancelShift" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                        <button wire:click="saveShift"
                                class="px-4 py-2 text-sm bg-cs-blue text-white rounded">
                            {{ $editingShiftId ? 'Save' : 'Add Shift' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ADD JOB MODAL --}}
    @if($showAddJob)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
                <h3 class="text-lg font-bold mb-4">Add Job</h3>

                <input type="text" wire:model="newJobName" placeholder="Job name"
                       class="w-full border rounded p-2 text-sm">
                @error('newJobName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelJob" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveJob" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Add</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ADD EMPLOYEE MODAL --}}
    @if($showAddEmployee)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
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
    @endif
</div>
