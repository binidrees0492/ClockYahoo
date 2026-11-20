<div>
    @if($toastMessage)
        <div x-data x-init="window.showToast(@js($toastMessage), @js($toastType ?? 'success'))" class="hidden"></div>
    @endif

    <div class="flex flex-wrap justify-between items-center mb-4 gap-3">
        <div class="flex items-center gap-4">
            <button wire:click="previousWeek" class="text-gray-500 hover:text-gray-800">&#9664;</button>
            <button wire:click="nextWeek"     class="text-gray-500 hover:text-gray-800">&#9654;</button>
            <h2 class="text-lg font-bold text-gray-800">
                Week of {{ $startOfWeek->format('M d, Y') }}
            </h2>
            <button wire:click="thisWeek" class="text-xs text-cs-blue underline">This Week</button>
        </div>

        <div class="flex items-center gap-3">
            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isManager())
                <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded px-2.5 py-1 text-xs">
                    <span class="text-gray-500 font-semibold">Employee:</span>
                    <select wire:model.live="employeeFilter" class="bg-transparent font-bold text-gray-800 focus:outline-none cursor-pointer text-xs">
                        <option value="">All Team Members</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button wire:click="openAdd"
                    class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
                + Add Shift
            </button>
        </div>
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
        @teleport('body')
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-4">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-day text-[#005a9c]"></i> {{ $editingId ? 'Edit Shift' : 'Add Scheduled Shift' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto flex-1 pr-1 text-xs">
                    {{-- Column 1: Shift & Assignment --}}
                    <div class="space-y-3">
                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Employee <span class="text-red-500">*</span></label>
                            <select wire:model="employeeId" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Select Employee</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                                @endforeach
                            </select>
                            @error('employeeId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Job / Assignment <span class="text-red-500">*</span></label>
                            <select wire:model="assignmentId" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Select Job</option>
                                @foreach($assignments as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            @error('assignmentId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Task <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(Sets GPS coordinates)</span></label>
                            <select wire:model.live="taskId" class="w-full border rounded p-2 text-xs bg-white font-medium">
                                <option value="">Select Task</option>
                                @foreach($tasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('taskId') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Shift Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="shiftDate" class="w-full border rounded p-2 text-xs bg-white">
                            @error('shiftDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="font-semibold text-gray-700 block mb-1">Start (24H)*</label>
                                <input type="time" wire:model="startTime" class="w-full border rounded p-1.5 text-xs bg-white">
                                @error('startTime') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700 block mb-1">End (24H)*</label>
                                <input type="time" wire:model="endTime" class="w-full border rounded p-1.5 text-xs bg-white">
                                @error('endTime') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700 block mb-1">Hours*</label>
                                <input type="number" step="0.25" min="0.5" max="24" wire:model="standardHours" class="w-full border rounded p-1.5 text-xs bg-white" placeholder="8.00">
                                @error('standardHours') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: GPS Location & Notes --}}
                    <div class="space-y-3">
                        <div class="border border-sky-200 bg-sky-50/40 rounded-xl p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sky-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot text-sky-600"></i> Task Location GPS
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                                    🔒 Read-Only
                                </span>
                            </div>

                            <p class="text-[11px] text-gray-600 leading-snug">
                                GPS coordinates are linked directly from the selected task. Coordinates can only be modified in the Task Manager modal.
                            </p>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-semibold text-gray-600 block mb-0.5">Latitude</label>
                                    <input type="number" step="any" wire:model="latitude" readonly disabled placeholder="Auto-linked" class="w-full border rounded p-1.5 text-xs bg-gray-100 text-gray-700 cursor-not-allowed font-mono">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-gray-600 block mb-0.5">Longitude</label>
                                    <input type="number" step="any" wire:model="longitude" readonly disabled placeholder="Auto-linked" class="w-full border rounded p-1.5 text-xs bg-gray-100 text-gray-700 cursor-not-allowed font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-semibold text-gray-600 block mb-0.5">Geofence Radius (Meters)</label>
                                <input type="number" wire:model="geofenceRadius" readonly disabled placeholder="200" class="w-full border rounded p-1.5 text-xs bg-gray-100 text-gray-700 cursor-not-allowed font-semibold">
                            </div>

                            @if($latitude && $longitude)
                                <div class="pt-1">
                                    <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-[11px] font-semibold inline-flex items-center gap-1">
                                        <span>🗺️ Preview on Map</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Shift Notes / Instructions</label>
                            <textarea wire:model="notes" rows="3" placeholder="Add any special instructions for the employee..." class="w-full border rounded p-2 text-xs bg-white"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 mt-3">
                    <button wire:click="closeModal" class="px-4 py-2 text-xs border rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Cancel</button>
                    <button wire:click="save" class="px-5 py-2 text-xs bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg font-bold shadow-xs transition">
                        {{ $editingId ? 'Update Shift' : 'Create Shift' }}
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
