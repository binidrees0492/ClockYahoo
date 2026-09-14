<div>
    @if($show && $log)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                {{-- HEADER --}}
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            {{ $log['assignment'] }} / {{ $log['task'] }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $log['employee'] }} &middot;
                            {{ $log['is_manual'] ? 'Manual entry' : 'Clocked entry' }}
                            &middot; Status: {{ ucfirst($log['status']) }}
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                {{-- TOAST --}}
                @if($toastMessage)
                    <div class="mx-6 mt-4 px-3 py-2 rounded text-xs
                        {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        {{ $toastMessage }}
                    </div>
                @endif

                {{-- DETAILS --}}
                <div class="px-6 py-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Clock In</div>
                        <div class="text-gray-800">{{ $log['clock_in'] ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Clock Out</div>
                        <div class="text-gray-800">{{ $log['clock_out'] ?? 'In progress' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Break</div>
                        <div class="text-gray-800">{{ $log['break_minutes'] }} min</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Duration</div>
                        <div class="text-gray-800">{{ $log['duration'] }} hrs</div>
                    </div>

                    <div class="col-span-2">
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Approval</div>
                        @if($log['approved'])
                            <div class="text-green-700 text-sm">
                                Approved
                                @if($log['approved_by']) by <strong>{{ $log['approved_by'] }}</strong> @endif
                                @if($log['approved_at']) on {{ $log['approved_at'] }} @endif
                            </div>
                        @else
                            <div class="text-orange-600 text-sm">Pending approval</div>
                        @endif
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="px-6 pb-4 flex justify-end gap-2">
                    @if($log['approved'])
                        <button wire:click="unapprove"
                                class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded">
                            Unapprove
                        </button>
                    @else
                        <button wire:click="approve"
                                class="px-4 py-2 text-sm bg-cs-blue hover:bg-blue-800 text-white rounded">
                            Approve
                        </button>
                    @endif
                </div>

                {{-- NOTES --}}
                <div class="px-6 py-4 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Notes</h4>

                    <div class="flex gap-2 mb-3">
                        <input type="text" wire:model="noteDraft" wire:keydown.enter="addNote"
                               placeholder="Add a note..."
                               class="flex-1 border border-gray-300 rounded p-2 text-sm">
                        <button wire:click="addNote"
                                class="bg-cs-blue hover:bg-blue-800 text-white px-3 py-2 rounded text-sm">Add</button>
                    </div>

                    @error('noteDraft') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                    @if($notes->count())
                        <ul class="space-y-2">
                            @foreach($notes as $n)
                                <li class="bg-gray-50 border border-gray-200 rounded p-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">{{ $n['user'] }} &middot; {{ $n['created_at'] }}</span>
                                        @if($n['user_id'] === auth()->id())
                                            <button wire:click="deleteNote({{ $n['id'] }})"
                                                    wire:confirm="Delete this note?"
                                                    class="text-red-400 hover:text-red-600">&times;</button>
                                        @endif
                                    </div>
                                    <div class="text-gray-800 mt-1">{{ $n['body'] }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-gray-400">No notes yet.</p>
                    @endif
                </div>

                {{-- ATTACHMENTS --}}
                <div class="px-6 py-4 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Attachments</h4>

                    <input type="file" wire:model="attachmentDrafts" multiple
                           class="w-full text-xs text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:bg-cs-blue file:text-white hover:file:bg-blue-800">

                    @error('attachmentDrafts.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                    <button wire:click="uploadAttachments" wire:loading.attr="disabled"
                            class="mt-2 bg-gray-700 hover:bg-gray-800 text-white px-3 py-1 rounded text-xs">
                        <span wire:loading.remove wire:target="uploadAttachments,attachmentDrafts">Upload Files</span>
                        <span wire:loading wire:target="uploadAttachments,attachmentDrafts">Uploading...</span>
                    </button>

                    @if($attachments->count())
                        <ul class="space-y-2 mt-3">
                            @foreach($attachments as $a)
                                <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-2 text-xs">
                                    <a href="{{ $a['url'] }}" target="_blank"
                                       class="text-cs-blue hover:underline truncate">{{ $a['name'] }}</a>
                                    <span class="text-gray-400 ml-2 shrink-0">{{ $a['size'] }}</span>
                                    @if($a['user_id'] === auth()->id())
                                        <button wire:click="deleteAttachment({{ $a['id'] }})"
                                                wire:confirm="Delete this attachment?"
                                                class="text-red-400 hover:text-red-600 ml-2">&times;</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-gray-400 mt-2">No attachments yet.</p>
                    @endif
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button wire:click="close"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
