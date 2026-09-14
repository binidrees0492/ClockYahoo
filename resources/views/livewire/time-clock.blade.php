<div wire:poll.1s="refreshState" class="w-full flex flex-col items-center">

    @if($toastMessage)
        <div class="w-full mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-green-100 text-green-800 border border-green-300' }}">
            <div class="flex justify-between items-center">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" class="ml-3 text-xs opacity-60 hover:opacity-100">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex justify-center gap-6 mb-2 text-center">
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Hours</div>
            <div class="w-20 h-20 rounded-full border-2 {{ $isActive ? 'border-cs-blue' : 'border-gray-200' }} flex items-center justify-center text-2xl font-light bg-white">
                {{ $hours }}
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Minutes</div>
            <div class="w-20 h-20 rounded-full border-2 {{ $isActive ? 'border-cs-blue' : 'border-gray-200' }} flex items-center justify-center text-2xl font-light bg-white">
                {{ $minutes }}
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Seconds</div>
            <div class="w-20 h-20 rounded-full border-2 {{ $isActive ? 'border-cs-blue' : 'border-gray-200' }} flex items-center justify-center text-2xl font-light bg-white">
                {{ $seconds }}
            </div>
        </div>
    </div>

    @if($isOnBreak || $breakHours !== '00' || $breakMinutes !== '00' || $breakSeconds !== '00')
        <div class="text-xs text-orange-600 font-semibold mb-4">
            Break Time: {{ $breakHours }}:{{ $breakMinutes }}:{{ $breakSeconds }}
        </div>
    @else
        <div class="mb-4"></div>
    @endif

    @if(!$isActive)
        <div class="w-full space-y-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">
                    Job* <span class="text-gray-400 cursor-help" title="Select your job">&#9432;</span>
                </label>
                <select wire:model="selectedAssignment"
                        class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:outline-none focus:border-blue-500">
                    <option value="">Select Job</option>
                    @foreach($assignments as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
                @error('selectedAssignment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">
                    Task* <span class="text-gray-400 cursor-help" title="Select your task">&#9432;</span>
                </label>
                <select wire:model="selectedTask"
                        class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:outline-none focus:border-blue-500">
                    <option value="">Select Task</option>
                    @foreach($tasks as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('selectedTask') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button wire:click="clockIn" wire:loading.attr="disabled"
                    class="w-full bg-cs-blue hover:bg-blue-800 text-white font-bold py-3 rounded shadow transition flex items-center justify-center disabled:opacity-60">
                <span class="mr-2 text-xs">&#9654;</span> Clock In
            </button>
        </div>
    @else
        <div class="w-full space-y-3">
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm">
                <p><strong>Status:</strong> {{ $isOnBreak ? 'On Break' : 'Working' }}</p>
            </div>

            @if($isOnBreak)
                <button wire:click="endBreak"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded shadow transition flex items-center justify-center">
                    <span class="mr-2">&#9654;</span> End Break
                </button>
            @else
                <button wire:click="startBreak"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded shadow transition flex items-center justify-center">
                    <span class="mr-2">&#10074;&#10074;</span> Start Break
                </button>
            @endif

            <button wire:click="openSwitchModal"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded shadow transition flex items-center justify-center">
                <span class="mr-2">&#8646;</span> Switch Job/Task
            </button>

            <button wire:click="clockOut"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded shadow transition flex items-center justify-center">
                <span class="mr-2">&#9632;</span> Clock Out
            </button>
        </div>

        <div class="w-full mt-6 border-t border-gray-200 pt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Notes</h4>

            <div class="flex gap-2 mb-3">
                <input type="text" wire:model="noteDraft" wire:keydown.enter="addNote"
                       placeholder="Add a note..."
                       class="flex-1 border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500">
                <button wire:click="addNote"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-3 py-2 rounded text-sm">Add</button>
            </div>

            @if(count($activeNotes))
                <ul class="space-y-2 mb-4">
                    @foreach($activeNotes as $note)
                        <li class="bg-gray-50 border border-gray-200 rounded p-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ $note['user'] }} &middot; {{ $note['created_at'] }}</span>
                                <button wire:click="deleteNote({{ $note['id'] }})"
                                        wire:confirm="Delete this note?"
                                        class="text-red-400 hover:text-red-600">&times;</button>
                            </div>
                            <div class="text-gray-800 mt-1">{{ $note['body'] }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h4 class="text-sm font-semibold text-gray-700 mb-2 mt-4">Attachments</h4>

            <input type="file" wire:model="attachmentDrafts" multiple
                   class="w-full text-xs text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:bg-cs-blue file:text-white hover:file:bg-blue-800">

            @error('attachmentDrafts.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            <button wire:click="uploadAttachments" wire:loading.attr="disabled"
                    class="w-full mt-2 bg-gray-700 hover:bg-gray-800 text-white py-2 rounded text-xs">
                <span wire:loading.remove wire:target="uploadAttachments,attachmentDrafts">Upload Files</span>
                <span wire:loading wire:target="uploadAttachments,attachmentDrafts">Uploading...</span>
            </button>

            @if(count($activeAttachments))
                <ul class="space-y-2 mt-3">
                    @foreach($activeAttachments as $att)
                        <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-2 text-xs">
                            <a href="{{ $att['url'] }}" target="_blank"
                               class="text-cs-blue hover:underline truncate">
                                {{ $att['name'] }}
                            </a>
                            <span class="text-gray-400 ml-2 shrink-0">{{ $att['size'] }}</span>
                            <button wire:click="deleteAttachment({{ $att['id'] }})"
                                    wire:confirm="Delete this attachment?"
                                    class="text-red-400 hover:text-red-600 ml-2">&times;</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- MY RECENT ACTIVITY --}}
    <div class="w-full mt-6 bg-white border border-gray-200 rounded shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h4 class="text-base font-semibold text-gray-700">My Recent Activity</h4>
        </div>

        <div class="px-4 py-2">
            <table class="w-full text-sm">
                <thead>
                <tr class="text-cs-blue font-semibold border-b border-gray-200">
                    <th class="text-left py-2">IN</th>
                    <th class="text-left py-2">OUT</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentActivity as $row)
                    <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                        wire:click="openDetail({{ $row['id'] }})">
                        <td class="py-2 {{ $row['is_manual'] ? 'text-blue-600 font-semibold' : 'text-gray-700' }}"
                            title="{{ $row['is_manual'] ? 'Manual entry' : '' }}">
                            {{ \Carbon\Carbon::parse($row['in'])->format('D n/j @ g:i A') }}
                        </td>
                        <td class="py-2 {{ $row['is_manual'] ? 'text-blue-600 font-semibold' : 'text-gray-700' }}">
                            {{ $row['out'] ? \Carbon\Carbon::parse($row['out'])->format('D n/j @ g:i A') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="py-3 text-center text-gray-400 text-xs">No recent activity.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($showSwitchModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Switch Job / Task</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Job*</label>
                        <select wire:model="switchAssignment" class="w-full border border-gray-300 rounded p-2 text-sm">
                            <option value="">Select Job</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('switchAssignment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Task*</label>
                        <select wire:model="switchTask" class="w-full border border-gray-300 rounded p-2 text-sm">
                            <option value="">Select Task</option>
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('switchTask') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelSwitch" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="confirmSwitch" class="px-4 py-2 text-sm bg-cs-blue text-white rounded hover:bg-blue-800">Switch</button>
                </div>
            </div>
        </div>
    @endif
</div>
