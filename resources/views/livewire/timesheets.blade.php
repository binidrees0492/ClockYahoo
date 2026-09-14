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
                {{ $startOfWeek->format('M d') }} &ndash; {{ $endOfWeek->format('M d, Y') }}
            </h2>
            <button wire:click="thisWeek" class="text-xs text-cs-blue underline">This Week</button>
        </div>
        <button wire:click="openAdd"
                class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
            + Add Manual Time
        </button>
    </div>

    <div class="border border-gray-200 rounded bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm table-fixed">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="p-3 text-left text-gray-600 font-semibold w-[240px]">Job / Task</th>
                @foreach(range(0,6) as $i)
                    @php $d = $startOfWeek->copy()->addDays($i); @endphp
                    <th class="p-3 text-center text-gray-600 font-semibold w-[102px]">
                        {{ strtoupper($d->format('D')) }}<br>
                        <span class="text-xs font-normal text-gray-500">{{ $d->format('d') }}</span>
                    </th>
                @endforeach
                <th class="p-3 text-center text-gray-800 font-bold w-[102px]">Hours</th>
                <th class="p-3 text-center text-gray-600 font-semibold w-[140px]">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                    wire:click="openDetail({{ $log->id }})">

                    @if($editingLogId === $log->id)
                        <td class="p-2" wire:click.stop>
                            <select wire:model="editAssignment" class="w-full border rounded p-1 text-xs mb-1">
                                @foreach($assignments as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model="editTask" class="w-full border rounded p-1 text-xs">
                                @foreach($tasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td colspan="7" class="p-2" wire:click.stop>
                            <div class="flex items-center gap-2 text-xs">
                                <input type="datetime-local" wire:model="editClockIn" class="border rounded p-1">
                                <span>&rarr;</span>
                                <input type="datetime-local" wire:model="editClockOut" class="border rounded p-1">
                                <input type="text" wire:model="editNotes" placeholder="Notes" class="border rounded p-1 flex-1">
                            </div>
                        </td>
                        <td class="p-2 text-center" wire:click.stop>&mdash;</td>
                        <td class="p-2 text-center whitespace-nowrap" wire:click.stop>
                            <button wire:click="saveEdit" class="text-green-600 text-xs font-semibold mr-2">Save</button>
                            <button wire:click="cancelEdit" class="text-gray-500 text-xs">Cancel</button>
                        </td>
                    @else
                        <td class="p-3 {{ $log->isManual() ? 'text-blue-600 font-semibold' : 'text-cs-blue font-semibold' }}"
                            title="{{ $log->isManual() ? 'Manual entry — added by ' . ($log->employee->name ?? '—') : '' }}">
                            {{ $log->assignment->name ?? '&mdash;' }} / {{ $log->task->name ?? '&mdash;' }}
                            @if($log->isManual())
                                <div class="text-[10px] text-blue-500 font-normal">Manual</div>
                            @endif
                            @if($log->logNotes->count() > 0)
                                <div class="text-[10px] text-gray-400">{{ $log->logNotes->count() }} note(s)</div>
                            @endif
                            @if($log->logAttachments->count() > 0)
                                <div class="text-[10px] text-gray-400">{{ $log->logAttachments->count() }} attachment(s)</div>
                            @endif
                            @if($log->approved)
                                <div class="text-[10px] text-green-600 font-normal">Approved</div>
                            @endif
                        </td>
                        @foreach(range(0,6) as $i)
                            @php
                                $day = $startOfWeek->copy()->addDays($i);
                                $sameDay = $log->clock_in->isSameDay($day);
                            @endphp
                            <td class="p-3 text-center text-gray-700 text-xs">
                                @if($sameDay)
                                    {{ $log->clock_in->format('h:i A') }}
                                    @if($log->clock_out)
                                        <br>&ndash; {{ $log->clock_out->format('h:i A') }}
                                    @else
                                        <br><em class="text-green-600">running</em>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                        <td class="p-3 text-center font-semibold text-gray-800">
                            @php
                                $mins = $log->clock_out
                                    ? $log->duration_minutes
                                    : (int) ceil($log->netSeconds() / 60);
                            @endphp
                            {{ number_format($mins / 60, 2) }}
                        </td>
                        <td class="p-3 text-center whitespace-nowrap" wire:click.stop>
                            <button wire:click="startEdit({{ $log->id }})"
                                    class="text-cs-blue text-xs font-semibold mr-2">Edit</button>
                            <button wire:click="deleteLog({{ $log->id }})"
                                    wire:confirm="Delete this entry?"
                                    class="text-red-500 text-xs">Delete</button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="p-8 text-center text-gray-400">No entries this week.</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr class="bg-white border-t border-gray-200">
                <td colspan="8" class="p-4 text-right font-bold text-gray-800 text-lg">Total Time</td>
                <td class="p-4 text-center font-bold text-gray-800 text-lg">
                    {{ number_format($totalMinutes / 60, 2) }}
                </td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>

    @if($showAddModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">Add Manual Time</h3>

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
