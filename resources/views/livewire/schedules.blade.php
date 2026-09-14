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

    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-4">
            <button wire:click="previousWeek" class="text-gray-500 hover:text-gray-800">&#9664;</button>
            <button wire:click="nextWeek"     class="text-gray-500 hover:text-gray-800">&#9654;</button>
            <h2 class="text-lg font-bold text-gray-800">
                Week of {{ $startOfWeek->format('M d, Y') }}
            </h2>
            <button wire:click="thisWeek" class="text-xs text-cs-blue underline">This Week</button>
        </div>
        <button wire:click="openAdd"
                class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
            + Add Shift
        </button>
    </div>

    <div class="grid grid-cols-7 gap-2">
        @foreach($days as $day)
            @php
                $key = $day->toDateString();
                $daySchedules = $schedules[$key] ?? collect();
            @endphp
            <div class="border border-gray-200 rounded bg-white">
                <div class="p-2 text-center border-b bg-gray-50">
                    <div class="text-xs font-bold text-gray-600">{{ strtoupper($day->format('D')) }}</div>
                    <div class="text-lg font-semibold text-gray-800">{{ $day->format('d') }}</div>
                </div>

                <div class="p-2 space-y-2 min-h-[120px]">
                    @foreach($daySchedules as $s)
                        <div class="bg-blue-50 border border-blue-200 rounded p-2 text-xs">
                            <div class="font-semibold text-cs-blue truncate">
                                {{ $s->employee->name ?? '&mdash;' }}
                            </div>
                            <div class="text-gray-600">
                                {{ substr($s->start_time, 0, 5) }} &ndash; {{ substr($s->end_time, 0, 5) }}
                            </div>
                            @if($s->assignment)
                                <div class="text-gray-500 truncate">{{ $s->assignment->name }}</div>
                            @endif
                            <div class="mt-1 flex gap-1">
                                <button wire:click="openEdit({{ $s->id }})" class="text-cs-blue">edit</button>
                                <button wire:click="delete({{ $s->id }})"
                                        wire:confirm="Delete this shift?" class="text-red-500">del</button>
                            </div>
                        </div>
                    @endforeach

                    <button wire:click="openAdd('{{ $key }}')"
                            class="w-full text-xs text-gray-400 hover:text-cs-blue py-1">
                        + add
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">
                    {{ $editingId ? 'Edit Shift' : 'Add Shift' }}
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Employee*</label>
                        <select wire:model="employeeId" class="w-full border rounded p-2 text-sm">
                            <option value="">Select Employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                        @error('employeeId') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Job</label>
                        <select wire:model="assignmentId" class="w-full border rounded p-2 text-sm">
                            <option value="">&mdash; none &mdash;</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Task</label>
                        <select wire:model="taskId" class="w-full border rounded p-2 text-sm">
                            <option value="">&mdash; none &mdash;</option>
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
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
                            <input type="time" wire:model="startTime" class="w-full border rounded p-2 text-sm">
                            @error('startTime') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">End*</label>
                            <input type="time" wire:model="endTime" class="w-full border rounded p-2 text-sm">
                            @error('endTime') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="save" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
