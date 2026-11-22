<div>
    @if($toastMessage)
        <div x-data x-init="window.showToast(@js($toastMessage), @js($toastType ?? 'success'))" class="hidden"></div>
    @endif

    {{-- PAY PERIOD NAVIGATION & VIEW MODE BAR --}}
    @php
        $weekStartDay = \App\Models\CompanySetting::getWeekStartDay();
        $anchor = \Carbon\Carbon::now()->startOfWeek($weekStartDay)->addWeeks($payPeriodOffset * 2);
        $ppLabel = $payPeriodOffset === 0 ? 'Current Pay Period' : ($payPeriodOffset === -1 ? 'Last Pay Period' : ($payPeriodOffset < 0 ? abs($payPeriodOffset) . ' Pay Periods Ago' : $payPeriodOffset . ' Pay Periods Later'));
        $ppDates = ucfirst($anchor->format('M j')) . ' – ' . ucfirst($anchor->copy()->addDays(13)->format('M j, Y'));
    @endphp
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        {{-- Pay Period Navigator matching ClockShark --}}
        <div class="flex items-center gap-3" x-data="{ openPPDropdown: false }">
            <button wire:click="previousPayPeriod" type="button" class="text-gray-500 hover:text-gray-800 text-base font-bold px-2 py-1 rounded hover:bg-gray-100" title="Previous Pay Period">&#9664;</button>
            <button wire:click="nextPayPeriod"     type="button" class="text-gray-500 hover:text-gray-800 text-base font-bold px-2 py-1 rounded hover:bg-gray-100" title="Next Pay Period">&#9654;</button>

            <div class="relative">
                <button type="button" @click="openPPDropdown = !openPPDropdown"
                        class="text-base font-semibold text-gray-800 flex items-center gap-2 hover:text-[#005a9c] transition focus:outline-none cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-gray-300 shadow-2xs">
                    <span class="font-bold text-gray-900">{{ $ppLabel }}</span>
                    <span class="font-normal text-gray-500 text-sm">{{ $ppDates }}</span>
                    <span class="text-[9px] text-gray-400">&#9660;</span>
                </button>

                <div x-show="openPPDropdown"
                     x-cloak
                     @click.away="openPPDropdown = false"
                     class="absolute left-0 top-full mt-1.5 z-50 bg-white border border-gray-200 rounded-lg shadow-xl py-1.5 w-64 text-xs">
                    <div class="px-3 py-1 text-[10px] uppercase font-bold text-gray-400 border-b border-gray-100">Select Pay Period</div>
                    <button type="button" wire:click="selectPayPeriodPreset(0)" @click="openPPDropdown = false"
                            class="w-full text-left px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-[#005a9c] flex items-center justify-between {{ $payPeriodOffset === 0 ? 'bg-blue-50 font-bold text-[#005a9c]' : '' }}">
                        <span>Current Pay Period</span>
                    </button>
                    <button type="button" wire:click="selectPayPeriodPreset(-1)" @click="openPPDropdown = false"
                            class="w-full text-left px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-[#005a9c] flex items-center justify-between {{ $payPeriodOffset === -1 ? 'bg-blue-50 font-bold text-[#005a9c]' : '' }}">
                        <span>Last Pay Period</span>
                    </button>
                    <button type="button" wire:click="selectPayPeriodPreset(-2)" @click="openPPDropdown = false"
                            class="w-full text-left px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-[#005a9c] flex items-center justify-between {{ $payPeriodOffset === -2 ? 'bg-blue-50 font-bold text-[#005a9c]' : '' }}">
                        <span>Two Pay Periods Ago</span>
                    </button>
                    <button type="button" wire:click="selectPayPeriodPreset(-3)" @click="openPPDropdown = false"
                            class="w-full text-left px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-[#005a9c] flex items-center justify-between {{ $payPeriodOffset === -3 ? 'bg-blue-50 font-bold text-[#005a9c]' : '' }}">
                        <span>Three Pay Periods Ago</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm text-xs">
            <button type="button" wire:click="setViewMode('summary')"
                    class="px-4 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 {{ $viewMode === 'summary' ? 'bg-[#005a9c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <span>📊 Pay Period Summary</span>
            </button>
            <button type="button" wire:click="setViewMode('entries')"
                    class="px-4 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 {{ $viewMode === 'entries' ? 'bg-[#005a9c] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                <span>📋 Time Entries Detail</span>
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select wire:model.live="filter"
                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Disapproved / Rejected</option>
                    <option value="all">All Entries</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="fromDate"
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="toDate"
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Job</label>
                <select wire:model.live="assignmentFilter"
                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
                    <option value="">All Jobs</option>
                    @foreach($assignments as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Employee</label>
                <select wire:model.live="employeeFilter"
                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Job / Task / Employee"
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-[#005a9c]">
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-center gap-3 mt-4 pt-3 border-t border-gray-100">
            <button wire:click="clearFilters"
                    class="text-xs text-gray-500 hover:text-gray-800 underline">
                Reset filters
            </button>

            <div class="flex flex-wrap gap-2">
                {{-- Approve Selected --}}
                <button wire:click="approveSelected"
                        wire:loading.attr="disabled"
                        @if(empty($selected)) disabled @endif
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1
                        {{ empty($selected)
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-green-600 hover:bg-green-700 text-white shadow' }}">
                    <span>✓</span> Approve Selected
                    @if(!empty($selected))
                        <span class="ml-1 text-[11px] bg-green-800 text-white px-1.5 rounded-full">{{ count($selected) }}</span>
                    @endif
                </button>

                {{-- Disapprove Selected (Requirement 2) --}}
                <button wire:click="openDisapproveModal()"
                        wire:loading.attr="disabled"
                        @if(empty($selected)) disabled @endif
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1
                        {{ empty($selected)
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-red-600 hover:bg-red-700 text-white shadow' }}">
                    <span>✕</span> Disapprove Selected
                    @if(!empty($selected))
                        <span class="ml-1 text-[11px] bg-red-800 text-white px-1.5 rounded-full">{{ count($selected) }}</span>
                    @endif
                </button>

                <button wire:click="unapproveSelected"
                        wire:loading.attr="disabled"
                        @if(empty($selected)) disabled @endif
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition
                        {{ empty($selected)
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 shadow-sm' }}">
                    Reset Status
                </button>

                <button wire:click="deleteSelected"
                        wire:loading.attr="disabled"
                        wire:confirm="Are you sure you want to permanently delete the selected entries?"
                        @if(empty($selected)) disabled @endif
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1
                        {{ empty($selected)
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 shadow-sm' }}">
                    <span>🗑️</span> Delete Selected
                </button>
            </div>
        </div>
    </div>

    @if($viewMode === 'summary')
        {{-- PAY PERIOD SUMMARY TABLE --}}
        <div class="border border-gray-200 rounded-xl bg-white shadow-sm overflow-x-auto">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Pay Period Summary</h3>
                    <p class="text-xs text-gray-500">Totals aggregated by employee for the selected window ({{ $fromDate ?: 'Start' }} to {{ $toDate ?: 'End' }}).</p>
                </div>
            </div>
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                <tr>
                    <th class="p-3">Last Name</th>
                    <th class="p-3">First Name</th>
                    <th class="p-3 text-right">Reg</th>
                    <th class="p-3 text-right">OT</th>
                    <th class="p-3 text-right">DT</th>
                    <th class="p-3 text-right">Paid Off</th>
                    <th class="p-3 text-right">Unpaid Off</th>
                    <th class="p-3 text-right">Total</th>
                    <th class="p-3 text-center">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($payPeriodSummaries as $sum)
                    <tr class="hover:bg-blue-50/30 transition">
                        <td class="p-3 font-semibold text-gray-900">{{ $sum['last_name'] }}</td>
                        <td class="p-3 text-gray-700">{{ $sum['first_name'] }}</td>
                        <td class="p-3 text-right font-mono">{{ $sum['reg'] }}h</td>
                        <td class="p-3 text-right font-mono {{ (float)$sum['ot'] > 0 ? 'text-amber-700 font-bold' : 'text-gray-400' }}">{{ $sum['ot'] }}h</td>
                        <td class="p-3 text-right font-mono {{ (float)$sum['dt'] > 0 ? 'text-rose-700 font-bold' : 'text-gray-400' }}">{{ $sum['dt'] }}h</td>
                        <td class="p-3 text-right font-mono {{ (float)$sum['paid_off'] > 0 ? 'text-blue-700 font-bold' : 'text-gray-400' }}">{{ $sum['paid_off'] }}h</td>
                        <td class="p-3 text-right font-mono {{ (float)$sum['unpaid_off'] > 0 ? 'text-gray-600 font-bold' : 'text-gray-400' }}">{{ $sum['unpaid_off'] }}h</td>
                        <td class="p-3 text-right font-bold text-gray-900 text-sm font-mono">{{ \App\Services\ReportDataService::formatHoursToHM($sum['total']) }}</td>
                        <td class="p-3 text-center">
                            @if($sum['status_color'] === 'emerald')
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                    ✓ All Approved
                                </span>
                            @elseif($sum['status_color'] === 'rose')
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                    ✕ Disapproved
                                </span>
                            @elseif($sum['status_color'] === 'blue')
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                    Mixed
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                    Pending Review
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if(!$sum['all_approved'])
                                    <button wire:click="approveEmployeePayPeriod({{ $sum['employee_id'] }})"
                                            class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold shadow-xs transition">
                                        ✓ Approve All
                                    </button>
                                @else
                                    <button wire:click="unapproveEmployeePayPeriod({{ $sum['employee_id'] }})"
                                            class="px-2.5 py-1 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded text-xs transition">
                                        ↩ Reset
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-10 text-center text-gray-400">
                            No pay period time entries found for the selected dates.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- TABLE: TIME ENTRIES DETAIL --}}
        <div class="border border-gray-200 rounded-xl bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                <tr class="bg-gray-50/80 border-b border-gray-200 text-left">
                    <th class="p-3 w-8">
                        <input type="checkbox" wire:model.live="selectAll" class="rounded text-[#005a9c]">
                    </th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Employee</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Job & Task</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Time Window</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Hours</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Shift Indicators</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px]">Approval Status</th>
                    <th class="p-3 text-gray-600 font-bold uppercase tracking-wider text-[11px] text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    @php
                        $mins = $log->duration_minutes ?: (int) ceil($log->netSeconds() / 60);
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition cursor-pointer"
                        wire:click="openDetail({{ $log->id }})"
                        wire:key="log-{{ $log->id }}">

                        <td class="p-3" wire:click.stop>
                            <input type="checkbox"
                                   wire:model.live="selected"
                                   value="{{ $log->id }}"
                                   class="rounded text-[#005a9c]">
                        </td>

                        <td class="p-3 font-semibold text-gray-900">
                            {{ $log->employee->name ?? '—' }}
                            @if($log->employee?->role)
                                <div class="text-[10px] text-gray-400 font-normal">{{ $log->employee->role }}</div>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="font-medium text-gray-800">{{ $log->assignment->name ?? 'General Work' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $log->task->name ?? 'General Task' }}</div>
                        </td>

                        <td class="p-3 text-gray-600">
                            <div>{{ $log->clock_in?->format('m/d/Y g:i A') }}</div>
                            <div class="text-[11px] text-gray-400">&rarr; {{ $log->clock_out?->format('g:i A') ?? 'Running' }}</div>
                        </td>

                        <td class="p-3 font-bold text-gray-900 text-sm">
                            {{ floor($mins / 60) }}h {{ $mins % 60 }}m
                            <span class="text-[11px] text-gray-400 font-normal">({{ number_format($mins / 60, 2) }}h)</span>
                        </td>

                        {{-- Shift Indicators (Requirement 5 & 6) --}}
                        <td class="p-3">
                            <div class="flex flex-wrap items-center gap-1">
                                @if($log->is_manual || $log->isManual())
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="Manual Time Entry">
                                        <i class="fa-solid fa-pen-to-square text-[9px]"></i> Manual
                                    </span>
                                @endif
                                @if($log->is_unusual)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 cursor-help"
                                          title="{{ $log->unusual_reason }}">
                                        ⚠️ Unusual Activity
                                    </span>
                                @endif
                                @if($log->location_flagged)
                                    @if(str_contains($log->location_flag_reason ?? '', 'GPS N/A'))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 cursor-help"
                                              title="{{ $log->location_flag_reason }}">
                                            📍 GPS N/A
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 cursor-help"
                                              title="{{ $log->location_flag_reason }}">
                                            📍 GPS Flag
                                        </span>
                                    @endif
                                @endif
                                @if(!$log->is_manual && !$log->is_unusual && !$log->location_flagged)
                                    <span class="text-gray-400 text-[11px]">Normal</span>
                                @endif
                            </div>
                        </td>

                        {{-- Approval Status --}}
                        <td class="p-3">
                            @if($log->approved)
                                <div class="inline-flex items-center gap-1 text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                                    <span>✓</span> Approved
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">By {{ $log->approver->name ?? 'Admin' }}</div>
                            @elseif($log->isRejected())
                                <div class="inline-flex items-center gap-1 text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded-full border border-red-200"
                                     title="{{ $log->rejection_reason }}">
                                    <span>✕</span> Disapproved
                                </div>
                                <div class="text-[10px] text-red-500 mt-0.5 truncate max-w-[140px]">{{ $log->rejection_reason }}</div>
                            @else
                                <span class="inline-flex items-center text-amber-700 font-semibold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    Pending HR Review
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="p-3 text-right" wire:click.stop>
                            <div class="flex items-center justify-end gap-1.5">
                                <button wire:click="openDetail({{ $log->id }})" title="View Discussion & Details"
                                        class="px-2 py-1 text-gray-500 hover:text-blue-600 rounded text-xs flex items-center gap-1 border border-gray-200 hover:border-blue-300">
                                    <span>💬</span>
                                    @if($log->logNotes->count() > 0)
                                        <span class="font-bold text-[10px] text-blue-600">{{ $log->logNotes->count() }}</span>
                                    @endif
                                </button>
                                @if($log->approved)
                                    <button wire:click="unapproveOne({{ $log->id }})"
                                            class="px-2.5 py-1 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded text-xs">
                                        Unapprove
                                    </button>
                                @else
                                    <button wire:click="approveOne({{ $log->id }})"
                                            class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold shadow-sm">
                                        Approve
                                    </button>
                                    <button wire:click="openDisapproveModal({{ $log->id }})"
                                            class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 rounded text-xs font-semibold border border-red-200">
                                        Disapprove
                                    </button>
                                @endif
                                <button wire:click="deleteOne({{ $log->id }})"
                                        wire:confirm="Are you sure you want to permanently delete this timesheet entry?"
                                        title="Delete Entry"
                                        class="px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded text-xs transition">
                                    <span>🗑️</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-gray-400">
                            <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            No timesheet entries found for this filter.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- DISAPPROVE MODAL (Requirement 2 & 3) --}}
    @if($showDisapproveModal)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-[90] p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-base font-bold text-red-700 flex items-center gap-2">
                        <span>✕</span> Disapprove Timesheet Entry
                    </h3>
                    <button wire:click="cancelDisapprove" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <div class="mt-4 space-y-3">
                    <p class="text-xs text-gray-600">
                        @if($isBulkDisapprove)
                            You are about to disapprove <strong>{{ count($selected) }}</strong> selected time entries.
                        @else
                            You are disapproving this time entry.
                        @endif
                        The employee will be notified with your feedback reason.
                    </p>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Disapproval Reason / Feedback
                        </label>
                        <textarea wire:model="disapprovalReason" rows="3"
                                  placeholder="Explain why this entry was rejected (e.g., incorrect job selected, unverified overtime, shift discrepancy)..."
                                  class="w-full border rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-red-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-3 border-t border-gray-100">
                    <button wire:click="cancelDisapprove" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button wire:click="confirmDisapprove" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold shadow">
                        Confirm Disapproval & Notify
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
