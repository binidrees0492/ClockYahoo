<div>
    @if($show && $log)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden my-auto border border-gray-100">

                {{-- MODAL HEADER --}}
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ $log['assignment'] }} &ndash; {{ $log['task'] }}
                            </h3>

                            {{-- Status Badges --}}
                            @if($log['approved'])
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 border border-green-200 inline-flex items-center gap-1">
                                    <span>✓</span> Approved
                                </span>
                            @elseif($log['status'] === 'rejected')
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 border border-red-200 inline-flex items-center gap-1">
                                    <span>✕</span> Rejected
                                </span>
                            @elseif($log['action_requested'])
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center gap-1">
                                    <span>💬</span> Action Requested by HR
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                    <span>⏳</span> Pending Approval
                                </span>
                            @endif

                            @if($log['is_manual'])
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                    Manual Entry
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500 mt-1 flex flex-wrap items-center gap-2">
                            <span><strong>{{ $log['employee'] }}</strong></span>
                            <span>&bull;</span>
                            <span>Log #{{ $log['id'] }}</span>
                            <span>&bull;</span>
                            <span>{{ $log['clock_in'] ?? 'Not started' }}</span>
                        </p>
                    </div>

                    <button wire:click="close" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none p-1">&times;</button>
                </div>

                {{-- MODAL BODY (2-COLUMN LAYOUT) --}}
                <div class="grid grid-cols-1 md:grid-cols-12 overflow-y-auto flex-1 divide-y md:divide-y-0 md:divide-x divide-gray-200">

                    {{-- LEFT COLUMN: TIME & LOCATION DETAILS --}}
                    <div class="md:col-span-5 p-6 space-y-4 overflow-y-auto">

                        {{-- Discrepancies & HR Alerts --}}
                        @if($log['status'] === 'rejected')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-red-700">
                                    <span>✕</span> Disapproved / Rejected by Supervisor
                                </div>
                                <p class="text-gray-800">{{ $log['rejection_reason'] ?: 'No specific reason provided.' }}</p>
                                @if($log['rejected_by'])
                                    <div class="text-[11px] text-red-600">By {{ $log['rejected_by'] }} &middot; {{ $log['rejected_at'] }}</div>
                                @endif
                            </div>
                        @endif

                        @if($log['action_requested'])
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 text-xs text-purple-900 space-y-2">
                                <div class="font-bold flex items-center justify-between text-purple-700">
                                    <span class="flex items-center gap-1.5">💬 Action / Explanation Requested by HR</span>
                                    <button type="button" wire:click="acceptActionRequest"
                                            class="px-2.5 py-1 bg-purple-700 hover:bg-purple-800 text-white rounded text-[11px] font-bold shadow-xs transition flex items-center gap-1">
                                        <span>✓</span> Accept / Resolve Request
                                    </button>
                                </div>
                                <p class="text-gray-800 font-medium">{{ $log['action_requested_notes'] ?: 'Please review this time log and post explanation notes.' }}</p>
                                <p class="text-[11px] text-purple-600">Click &ldquo;Accept / Resolve Request&rdquo; after providing necessary explanations or edits.</p>
                            </div>
                        @endif

                        @if($log['is_unusual'])
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-amber-700">
                                    <span>⚠️</span> Unusual Activity Flagged
                                </div>
                                <p class="text-gray-800">{{ $log['unusual_reason'] ?: 'Logged beyond standard shift hours.' }}</p>
                            </div>
                        @endif

                        @if($log['location_flagged'])
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-xs text-orange-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-orange-700">
                                    <span>📍</span> GPS Discrepancy / Policy Flag
                                </div>
                                <p class="text-gray-800">{{ $log['location_flag_reason'] ?: 'Location recorded outside designated task geofence or GPS N/A.' }}</p>
                            </div>
                        @endif

                        {{-- Work Time Details Card --}}
                        <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-4 space-y-3">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                <span>⏱️</span> Time Breakdown
                            </h4>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Clock In</div>
                                    <div class="font-bold text-gray-800 mt-0.5">{{ $log['clock_in'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Clock Out</div>
                                    <div class="font-bold text-gray-800 mt-0.5">{{ $log['clock_out'] ?? 'In progress...' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Break Time</div>
                                    <div class="text-gray-700 mt-0.5 font-medium">{{ $log['break_minutes'] }} min <span class="text-[10px] text-orange-600 font-normal">(Excluded from hours)</span></div>
                                    @if(!empty($log['breaks']) && count($log['breaks']) > 0)
                                        <div class="mt-1 space-y-0.5">
                                            @foreach($log['breaks'] as $b)
                                                <div class="text-[10px] text-gray-500">
                                                    ☕ {{ $b['start_formatted'] ?? (\Carbon\Carbon::parse($b['start'])->format('g:i A')) }} &ndash; {{ $b['end_formatted'] ?? (\Carbon\Carbon::parse($b['end'])->format('g:i A')) }} ({{ $b['minutes'] }}m)
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Payable Duration</div>
                                    @php
                                        $durVal = (float) ($log['duration'] ?? 0);
                                        $durH = floor($durVal);
                                        $durM = (int) round(($durVal - $durH) * 60);
                                    @endphp
                                    <div class="text-base font-extrabold text-cs-blue mt-0.5">{{ $durH }} hrs {{ $durM }} mins <span class="text-xs text-gray-400 font-normal">({{ $log['duration'] }}h)</span></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Rate / hr</div>
                                    <div class="text-xs font-bold text-gray-800 mt-0.5">
                                        @if(!empty($log['hourly_rate']))
                                            ${{ number_format($log['hourly_rate'], 2) }}/hr
                                        @else
                                            <span class="text-gray-400 font-normal italic">Default</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Estimated Gross Pay</div>
                                    <div class="text-xs font-bold text-emerald-700 mt-0.5">
                                        @if(!empty($log['hourly_rate']))
                                            ${{ number_format(((float)$log['duration']) * ((float)$log['hourly_rate']), 2) }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Location & GPS Verification Card --}}
                        <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-4 space-y-2.5">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                <span>📍</span> Location & GPS Verification
                            </h4>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Task GPS Policy:</span>
                                    <span class="font-semibold text-gray-800">{{ ucfirst($log['task_gps_policy']) }} (Radius: {{ $log['task_radius'] }}m)</span>
                                </div>

                                <div class="flex justify-between items-start">
                                    <span class="text-gray-500">Clock-In GPS:</span>
                                    <div class="text-right">
                                        @if($log['clock_in_latitude'])
                                            <span class="font-mono font-medium text-gray-800">
                                                {{ number_format($log['clock_in_latitude'], 5) }}, {{ number_format($log['clock_in_longitude'], 5) }}
                                            </span>
                                            <div>
                                                <a href="https://www.google.com/maps?q={{ $log['clock_in_latitude'] }},{{ $log['clock_in_longitude'] }}" target="_blank" class="text-blue-600 hover:underline text-[11px]">
                                                    View on Maps ↗
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">No GPS coordinates</span>
                                        @endif
                                    </div>
                                </div>

                                @if($log['clock_out_latitude'])
                                    <div class="flex justify-between items-start">
                                        <span class="text-gray-500">Clock-Out GPS:</span>
                                        <div class="text-right">
                                            <span class="font-mono font-medium text-gray-800">
                                                {{ number_format($log['clock_out_latitude'], 5) }}, {{ number_format($log['clock_out_longitude'], 5) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Attachments & Proofs --}}
                        <div class="border border-gray-200 rounded-lg p-4 space-y-2.5">
                            <div class="flex justify-between items-center">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📎</span> Attachments & Proofs
                                </h4>
                                <span class="text-[11px] text-gray-400">{{ $attachments->count() }} file(s)</span>
                            </div>

                            @if($attachments->count())
                                <ul class="space-y-1.5">
                                    @foreach($attachments as $att)
                                        <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 text-xs">
                                            <a href="{{ $att['url'] }}" target="_blank" class="text-cs-blue hover:underline truncate max-w-[200px]">
                                                {{ $att['name'] }}
                                            </a>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-400 text-[10px]">{{ $att['size'] }}</span>
                                                @if($att['user_id'] === auth()->id() || auth()->user()->canAccessAdmin())
                                                    <button wire:click="deleteAttachment({{ $att['id'] }})"
                                                            wire:confirm="Delete this attachment?"
                                                            class="text-red-400 hover:text-red-600 font-bold">&times;</button>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-gray-400 italic">No attachments uploaded yet.</p>
                            @endif

                            <div class="pt-1">
                                <input type="file" wire:model="attachmentDrafts" multiple class="w-full text-xs text-gray-500">
                                <button type="button" wire:click="uploadAttachments" wire:loading.attr="disabled"
                                        class="mt-2 w-full px-3 py-1 bg-gray-800 hover:bg-black text-white text-xs font-semibold rounded shadow-xs transition">
                                    Upload Supporting Files
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: ACTIONS, HR WORKFLOW & THREAD --}}
                    <div class="md:col-span-7 p-6 space-y-5 overflow-y-auto">

                        {{-- ENTRY OPERATIONS TOOLBAR (Copy, Edit, Delete) --}}
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Entry Actions
                                </h4>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="openCopy"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 inline-flex items-center gap-1 shadow-xs transition">
                                        <span>📋</span> Copy to Other Day
                                    </button>

                                    @if(!empty($log['is_archived']))
                                        <span class="px-2.5 py-1 text-xs font-bold rounded bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-box-archive"></i> Archived Record
                                        </span>
                                        @if(auth()->user()->isSuperAdmin())
                                            <button type="button" wire:click="restoreArchivedLog"
                                                    class="px-3 py-1 text-xs font-semibold rounded bg-emerald-600 hover:bg-emerald-700 text-white inline-flex items-center gap-1 shadow-xs transition">
                                                <span>↺</span> Restore
                                            </button>
                                            <button type="button" wire:click="rejectArchivedLog"
                                                    class="px-3 py-1 text-xs font-semibold rounded bg-amber-600 hover:bg-amber-700 text-white inline-flex items-center gap-1 shadow-xs transition">
                                                <span>✕</span> Reject
                                            </button>
                                            <button type="button" wire:click="permanentlyDeleteLog"
                                                    wire:confirm="Permanently delete this entry and any linked child records? This cannot be undone."
                                                    class="px-3 py-1 text-xs font-semibold rounded bg-red-600 hover:bg-red-700 text-white inline-flex items-center gap-1 shadow-xs transition">
                                                <span>🗑️</span> Delete Permanently
                                            </button>
                                        @endif
                                    @elseif(!($log['is_locked'] ?? false) || auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isSuperAdmin())
                                        <button type="button" wire:click="openEdit"
                                                class="px-3 py-1 text-xs font-semibold rounded bg-white border border-gray-300 hover:bg-gray-100 text-cs-blue inline-flex items-center gap-1 shadow-xs transition">
                                            <span>✏️</span> Edit
                                        </button>
                                        <button type="button" wire:click="deleteLog"
                                                wire:confirm="Are you sure you want to delete/archive this timesheet entry?"
                                                class="px-3 py-1 text-xs font-semibold rounded bg-white border border-red-200 hover:bg-red-50 text-red-600 inline-flex items-center gap-1 shadow-xs transition">
                                            <span>🗑️</span> Delete
                                        </button>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-500 border border-gray-200 inline-flex items-center gap-1" title="Approved, withdrawn, or rejected timesheets are locked and cannot be edited.">
                                            <i class="fa-solid fa-lock text-gray-400"></i> Locked ({{ ucfirst($log['status'] ?? ($log['approved'] ? 'Approved' : 'Locked')) }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- INLINE COPY FORM --}}
                            @if($showCopyForm)
                                <div class="bg-white border border-blue-200 rounded-lg p-3 space-y-3 mt-2 shadow-xs">
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-1.5">
                                        <span class="text-xs font-bold text-cs-blue">Copy Shift to Another Day</span>
                                        <button type="button" wire:click="cancelCopy" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Target Date *</label>
                                        <input type="date" wire:model="copyTargetDate" class="w-full border border-gray-300 rounded p-1.5 text-xs">
                                        @error('copyTargetDate') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Reason / Mandatory Notes *</label>
                                        <textarea wire:model="copyNotes" rows="2" placeholder="e.g. Copied schedule hours, same project scope..." class="w-full border border-gray-300 rounded p-1.5 text-xs"></textarea>
                                        @error('copyNotes') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" wire:click="cancelCopy" class="px-3 py-1 border rounded text-xs text-gray-600">Cancel</button>
                                        <button type="button" wire:click="confirmCopy" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded text-xs">
                                            Confirm Copy
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- INLINE EDIT FORM (with Break Support) --}}
                            @if($showEditForm)
                                <div class="bg-white border border-blue-200 rounded-lg p-3 space-y-3 mt-2 shadow-xs">
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-1.5">
                                        <span class="text-xs font-bold text-cs-blue">Edit Time Entry</span>
                                        <button type="button" wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Job *</label>
                                            <select wire:model="editAssignment" class="w-full border rounded p-1.5 text-xs">
                                                @foreach($assignments as $a)
                                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Task *</label>
                                            <select wire:model="editTask" class="w-full border rounded p-1.5 text-xs">
                                                @foreach($tasks as $t)
                                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Clock In *</label>
                                            <input type="datetime-local" wire:model="editClockIn" class="w-full border rounded p-1.5 text-xs">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Clock Out</label>
                                            <input type="datetime-local" wire:model="editClockOut" class="w-full border rounded p-1.5 text-xs">
                                        </div>
                                    </div>

                                    {{-- Edit Break Section --}}
                                    <div class="p-2 bg-gray-50 border border-gray-200 rounded space-y-2">
                                        <label class="flex items-center gap-2 cursor-pointer text-[11px] font-semibold text-gray-700">
                                            <input type="checkbox" wire:model.live="editHasBreak" class="rounded text-[#005a9c]">
                                            <span>Add Break (Deducted from payable hours)</span>
                                        </label>
                                        @if($editHasBreak)
                                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-200">
                                                <div>
                                                    <label class="text-[10px] font-medium text-gray-600">Break Start</label>
                                                    <input type="time" wire:model.live="editBreakStart" class="w-full border rounded p-1 text-xs mt-0.5">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] font-medium text-gray-600">Break End</label>
                                                    <input type="time" wire:model.live="editBreakEnd" class="w-full border rounded p-1 text-xs mt-0.5">
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-semibold text-gray-700 block mb-0.5">Notes</label>
                                        <textarea wire:model="editNotes" rows="2" class="w-full border rounded p-1.5 text-xs"></textarea>
                                    </div>

                                    @if(auth()->user()->isAdmin() || auth()->user()->isHrManager())
                                        <div class="text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-200 p-2 rounded flex items-center gap-1.5">
                                            <span>✓</span>
                                            <span>As HR/Admin, saving changes will keep this entry directly approved.</span>
                                        </div>
                                    @endif

                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" wire:click="cancelEdit" class="px-3 py-1 border rounded text-xs text-gray-600">Cancel</button>
                                        <button type="button" wire:click="saveEdit" class="px-3 py-1 bg-cs-blue hover:bg-blue-800 text-white font-semibold rounded text-xs">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- EDIT HISTORY / VIEW CHANGES BADGE --}}
                            @if(!empty($editAudits))
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2 mt-2">
                                    <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                        <span class="flex items-center gap-1"><span>📝</span> Modification History ({{ count($editAudits) }})</span>
                                    </div>
                                    <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                        @foreach($editAudits as $ea)
                                            <div class="bg-white border border-gray-200 rounded p-2 text-[11px]">
                                                <div class="flex justify-between items-center text-gray-700 font-semibold mb-1">
                                                    <span>Edited by: <strong class="text-gray-900">{{ $ea['user_name'] }}</strong></span>
                                                    <span class="text-[10px] text-gray-400">{{ $ea['created_at'] }}</span>
                                                </div>
                                                @if(!empty($ea['changes']))
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-[10px] text-gray-600 bg-gray-50 p-1.5 rounded">
                                                        @foreach($ea['changes'] as $fld => $val)
                                                            <div>
                                                                <span class="font-medium text-gray-500">{{ $fld }}:</span>
                                                                <span class="line-through text-red-500">{{ is_array($val['old'] ?? null) ? json_encode($val['old']) : ($val['old'] ?? 'None') }}</span>
                                                                &rarr;
                                                                <span class="text-green-600 font-semibold">{{ is_array($val['new'] ?? null) ? json_encode($val['new']) : ($val['new'] ?? 'None') }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- HR ACTIONS SECTION --}}
                        @if(auth()->user()->canApproveTimesheets())
                            <div class="border border-blue-200 bg-blue-50/40 rounded-lg p-3.5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>🛡️</span> Supervisor / HR Actions
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        @if($log['approved'])
                                            <button type="button" wire:click="openDisapprove"
                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                ✕ Disapprove
                                            </button>
                                            <button type="button" wire:click="unapprove"
                                                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold rounded shadow-xs transition">
                                                ↩ Reset to Pending
                                            </button>
                                        @elseif($log['status'] === 'rejected')
                                            <button type="button" wire:click="approve"
                                                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                ✓ Approve
                                            </button>
                                            <button type="button" wire:click="unapprove"
                                                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold rounded shadow-xs transition">
                                                ↩ Reset to Pending
                                            </button>
                                        @else
                                            @if($log['action_requested'])
                                                <button type="button" wire:click="acceptActionRequest"
                                                        class="px-3 py-1 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold rounded shadow-xs transition">
                                                    ✓ Accept Request
                                                </button>
                                            @else
                                                <button type="button" wire:click="openRequestAction"
                                                        class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                    💬 Request Action
                                                </button>
                                            @endif
                                            <button type="button" wire:click="openDisapprove"
                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                ✕ Reject
                                            </button>
                                            <button type="button" wire:click="approve"
                                                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                ✓ Approve
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Disapprove Reason Form --}}
                                @if($showDisapproveInput)
                                    <div class="bg-white border border-red-200 rounded p-2.5 space-y-2 mt-2">
                                        <label class="text-[11px] font-bold text-red-800 block">Reason for Rejection *</label>
                                        <textarea wire:model="disapprovalReason" rows="2" placeholder="Explain rejection reason..." class="w-full border border-red-300 rounded p-1.5 text-xs"></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="cancelDisapprove" class="px-2.5 py-1 text-xs border rounded">Cancel</button>
                                            <button type="button" wire:click="confirmDisapprove" class="px-3 py-1 text-xs bg-red-600 text-white font-semibold rounded">Confirm Rejection</button>
                                        </div>
                                    </div>
                                @endif

                                {{-- Request Action Form --}}
                                @if($showActionRequestInput)
                                    <div class="bg-white border border-purple-200 rounded p-2.5 space-y-2 mt-2">
                                        <label class="text-[11px] font-bold text-purple-800 block">Action / Explanation Requested from Employee *</label>
                                        <textarea wire:model="actionRequestReason" rows="2" placeholder="Specify what the employee needs to correct or explain..." class="w-full border border-purple-300 rounded p-1.5 text-xs"></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="cancelRequestAction" class="px-2.5 py-1 text-xs border rounded">Cancel</button>
                                            <button type="button" wire:click="confirmRequestAction" class="px-3 py-1 text-xs bg-purple-600 text-white font-semibold rounded">Send Request to Employee</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- THREADED DISCUSSION & NOTES --}}
                        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>💬</span> Thread & Discussion
                                </h4>
                                <span class="text-[11px] text-gray-400">{{ $notes->count() }} comment(s)</span>
                            </div>

                            {{-- Post note input --}}
                            <div class="flex gap-2">
                                <input type="text" wire:model="noteDraft" wire:keydown.enter="addNote"
                                       placeholder="Write a message or response..."
                                       class="flex-1 border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-cs-blue focus:border-cs-blue">
                                <button type="button" wire:click="addNote"
                                        class="px-3.5 py-1.5 bg-cs-blue hover:bg-blue-800 text-white text-xs font-semibold rounded shadow-xs transition">
                                    Send
                                </button>
                            </div>
                            @error('noteDraft') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror

                            {{-- Message Thread List --}}
                            <div class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                                @forelse($notes as $n)
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs space-y-1.5">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-800">{{ $n['user'] }}</span>
                                                <span class="text-gray-400 text-[10px]">&bull; {{ $n['created_at'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" wire:click="startReply({{ $n['id'] }})" class="text-cs-blue hover:underline text-[11px] font-semibold">Reply</button>
                                                @if($n['user_id'] === auth()->id() || auth()->user()->canAccessAdmin())
                                                    <button type="button" wire:click="deleteNote({{ $n['id'] }})" title="Delete" class="text-gray-400 hover:text-red-600 font-bold">&times;</button>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="text-gray-800 whitespace-pre-wrap">{{ $n['body'] }}</p>

                                        {{-- Inline reply box if active --}}
                                        @if($replyingToNoteId === $n['id'])
                                            <div class="mt-2 pt-2 border-t border-gray-200 flex gap-2">
                                                <input type="text" wire:model="replyDraft" wire:keydown.enter="addReply" placeholder="Write reply..." class="flex-1 border rounded px-2 py-1 text-xs">
                                                <button type="button" wire:click="cancelReply" class="px-2 py-1 text-xs border rounded">Cancel</button>
                                                <button type="button" wire:click="addReply" class="px-2 py-1 text-xs bg-cs-blue text-white rounded font-semibold">Reply</button>
                                            </div>
                                        @endif

                                        {{-- Nested Replies --}}
                                        @if(!empty($n['replies']))
                                            <div class="ml-3 pl-2.5 border-l-2 border-blue-200 space-y-1.5 mt-2">
                                                @foreach($n['replies'] as $reply)
                                                    <div class="bg-white rounded p-1.5 border border-gray-100">
                                                        <div class="flex justify-between items-center text-[10px] text-gray-500">
                                                            <span class="font-semibold text-gray-700">{{ $reply['user'] }}</span>
                                                            <span>{{ $reply['created_at'] }}</span>
                                                        </div>
                                                        <p class="text-gray-800 mt-0.5 text-xs">{{ $reply['body'] }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-center text-gray-400 text-xs py-4">No comments in this thread yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end shrink-0">
                    <button type="button" wire:click="close" class="px-4 py-1.5 border border-gray-300 text-xs font-semibold text-gray-700 rounded hover:bg-gray-100 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
