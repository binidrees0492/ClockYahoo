<div class="max-w-7xl mx-auto p-6" wire:poll.3s>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-light text-gray-800">Time Off Requests</h1>
            <p class="text-xs text-gray-500 mt-1">Live tracking for employee leave credits, approvals, and daily schedules.</p>
        </div>
        <button wire:click="openRequestModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition">
            + Request Time Off
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 border border-green-200 text-green-800 text-sm rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-200 text-red-800 text-sm rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded p-4 mb-4 shadow-sm flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4 items-center flex-1 max-w-md">
            <select wire:model.live="status_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="denied">Denied</option>
                <option value="withdrawn">Withdrawn</option>
            </select>

            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || (auth()->user()->isManager() && auth()->user()->canManagerDo('manage_time_off')))
                <select wire:model.live="employee_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                    <option value="">All Employees</option>
                    @foreach($requestableEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <button wire:click="$set('status_filter', ''); $set('employee_filter', '');" class="text-xs text-[#005b8f] hover:underline font-medium">
            Clear Filters
        </button>
    </div>

    {{-- Requests Table with Realtime Status Badges --}}
    <div class="bg-white border border-gray-200 rounded shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
            <tr>
                <th class="p-3">Submitted</th>
                <th class="p-3">Employee</th>
                <th class="p-3">Policy</th>
                <th class="p-3">Dates</th>
                <th class="p-3 text-right">Requested</th>
                <th class="p-3 text-right">Available</th>
                <th class="p-3">Routing</th>
                <th class="p-3 text-center">Status</th>
                <th class="p-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($requests as $req)
                @php
                    $userBalance = \App\Models\TimeOffPolicyEmployee::where('employee_id', $req->employee_id)
                        ->where('policy_id', $req->policy_id)
                        ->first();
                    $avail = $userBalance ? (float)$userBalance->hours_remaining : 0.00;
                    $charge = $req->paid_hours > 0 ? (float)$req->paid_hours : (float)$req->hours;
                    $isOver = $req->status === 'pending' && ($avail < $charge);
                    $exceeded = max(0, $charge - $avail);
                    $projRemaining = max(0, $avail - $charge);
                @endphp
                <tr class="hover:bg-gray-50 transition {{ $isOver ? 'bg-amber-50/30' : '' }}">
                    <td class="p-3 text-xs text-gray-500 whitespace-nowrap">
                        <span class="font-medium text-gray-800">{{ $req->created_at->format('m/d/Y') }}</span>
                        <span class="block text-[11px] text-gray-400">{{ $req->created_at->format('g:i A') }}</span>
                    </td>
                    <td class="p-3">
                        <span class="font-medium text-gray-900 block">{{ $req->employee?->name ?? 'Unknown Employee' }}</span>
                        <span class="text-[11px] text-gray-400">{{ $req->employee?->email ?? '' }}</span>
                    </td>
                    <td class="p-3 text-gray-700">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 border border-gray-200">
                                {{ $req->policy?->name ?? 'Standard Policy' }}
                            </span>
                    </td>
                    <td class="p-3 text-xs text-gray-600">
                        {{ $req->start_at->format('m/d/Y') }} - {{ $req->end_at->format('m/d/Y') }}
                        <div class="text-[10px] text-gray-400">
                            @if($req->unpaid_hours > 0)
                                <span class="text-amber-700 font-semibold">{{ $req->paid_hours }}h Paid / {{ $req->unpaid_hours }}h Unpaid</span>
                            @else
                                {{ $req->is_full_day ? 'Standard Shift' : 'Custom Hours' }}
                            @endif
                        </div>
                    </td>
                    <td class="p-3 text-right font-semibold text-gray-800">
                        {{ number_format($req->hours, 1) }}h
                        @if($isOver)
                            <span class="block text-[10px] text-red-600 font-bold uppercase tracking-tight">Over Credit (+{{ number_format($exceeded, 1) }}h)</span>
                        @endif
                    </td>
                    <td class="p-3 text-right font-medium {{ $avail <= 0 ? 'text-red-500' : 'text-gray-600' }}">
                        <span>{{ number_format($avail, 1) }}h</span>
                        <span class="block text-[10px] {{ $isOver ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                            Proj: {{ number_format($projRemaining, 1) }}h
                        </span>
                    </td>
                    <td class="p-3 text-xs text-gray-600">
                        <div><strong>Routing:</strong> {{ $req->approver?->name ?? 'Admins / HR' }}</div>
                        <div class="text-[11px] text-gray-400">Routes to: {{ $req->approver?->name ? $req->approver?->name.' & HR Team' : 'HR Manager Team' }}</div>
                    </td>
                    <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $req->status === 'approved' ? 'bg-green-100 text-green-800' : ($req->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($req->status === 'withdrawn' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($req->status) }}
                            </span>
                    </td>
                    <td class="p-3 text-right space-x-2 whitespace-nowrap">
                        <button wire:click="openViewModal({{ $req->id }})" class="text-[#005b8f] hover:underline text-xs font-semibold">
                            View & Thread
                        </button>

                        @if($req->status === 'pending')
                            @if(auth()->id() === $req->employee_id)
                                <button wire:click="withdrawRequest({{ $req->id }})" wire:confirm="Withdraw this pending request?" class="text-gray-500 hover:text-red-600 hover:underline text-xs font-medium">
                                    Withdraw
                                </button>
                            @endif

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || (auth()->user()->isManager() && auth()->user()->canManagerDo('manage_time_off') && (auth()->id() === $req->approver_id || auth()->user()->canManageEmployee($req->employee_id))))
                                <button wire:click="openDecisionModal({{ $req->id }}, 'approved')" class="text-green-600 hover:underline text-xs font-semibold">Approve</button>
                                <button wire:click="openDecisionModal({{ $req->id }}, 'denied')" class="text-red-500 hover:underline text-xs font-semibold">Deny</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="p-8 text-center text-gray-400">No time off requests found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $requests->links() }}
        </div>
    </div>

    {{-- Employee Request Modal --}}
    @if($isModalOpen)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h2 class="text-base font-bold text-gray-800">Request Time Off</h2>
                    <button wire:click="closeRequestModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto text-xs flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Left Column: Request Configuration & Balance --}}
                        <div class="space-y-3.5">
                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || (auth()->user()->isManager() && auth()->user()->canManagerDo('manage_time_off')))
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Employee</label>
                                    <select wire:model.live="selected_employee_id" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                        @foreach($requestableEmployees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-semibold text-gray-600">Entitled Leave Head / Policy *</label>
                                    <span class="text-xs text-gray-500">Available: <strong class="text-gray-800">{{ $available_hours }} hrs</strong></span>
                                </div>
                                <select wire:model.live="policy_id" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                    @if(count($empPolicies) > 0)
                                        <option value="">Select an enrolled policy</option>
                                        @foreach($empPolicies as $item)
                                            <option value="{{ $item->policy_id }}">{{ $item->policy?->name ?? 'Policy' }} (Available: {{ $item->hours_remaining }} hrs)</option>
                                        @endforeach
                                    @else
                                        <option value="">No time off policies implemented for this employee</option>
                                    @endif
                                </select>
                                @error('policy_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date *</label>
                                    <input type="date" wire:model.live="start_date" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">End Date *</label>
                                    <input type="date" wire:model.live="end_date" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                </div>
                            </div>

                            {{-- Full Days vs Partial Days Selector --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Off Type</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" wire:click="setDayTypeSelection(true)"
                                            class="py-1.5 px-3 rounded text-xs font-medium border flex items-center justify-center gap-1.5 transition {{ $is_full_day ? 'bg-[#005b8f] text-white border-[#005b8f]' : 'bg-gray-50 text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                                        <span>Full Day(s)</span>
                                    </button>
                                    <button type="button" wire:click="setDayTypeSelection(false)"
                                            class="py-1.5 px-3 rounded text-xs font-medium border flex items-center justify-center gap-1.5 transition {{ !$is_full_day ? 'bg-[#005b8f] text-white border-[#005b8f]' : 'bg-gray-50 text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                                        <span>Partial Day(s)</span>
                                    </button>
                                </div>
                            </div>

                            @if(!$is_full_day)
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Hours Per Partial Day</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="0.5" min="0.5" max="24" wire:model.live.debounce.300ms="default_daily_hours"
                                               class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                        <span class="text-xs text-gray-500 font-medium">hrs/day</span>
                                    </div>
                                </div>
                            @endif

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager())
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Initial Status</label>
                                    <select wire:model.live="submission_status" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                        <option value="approved">Approved (Apply directly without pending)</option>
                                        <option value="pending">Pending (Require manager/HR approval)</option>
                                    </select>
                                </div>
                            @endif

                            <div class="border rounded p-3 text-xs {{ $is_over_balance ? 'bg-red-50/60 border-red-200 text-red-900' : 'bg-blue-50/60 border-blue-200 text-blue-900' }}">
                                <div class="grid grid-cols-4 gap-2 text-center pb-2 border-b {{ $is_over_balance ? 'border-red-200' : 'border-blue-200' }}">
                                    <div>
                                        <span class="text-gray-500 block">Available</span>
                                        <strong class="text-xs">{{ number_format($available_hours, 1) }}h</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block">Paid</span>
                                        <strong class="text-xs">{{ number_format($paid_hours, 1) }}h</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block">Unpaid</span>
                                        <strong class="text-xs text-gray-700">{{ number_format($unpaid_hours, 1) }}h</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block">Projected</span>
                                        <strong class="text-xs {{ $is_over_balance ? 'text-red-600' : 'text-green-700' }}">
                                            {{ number_format($projected_balance, 1) }}h
                                        </strong>
                                    </div>
                                </div>

                                <div class="pt-2 flex justify-between items-center text-[10px]">
                                    <span>Approver: <strong>{{ $routed_manager_name }}</strong></span>
                                    @if($is_over_balance)
                                        <span class="px-1.5 py-0.2 rounded bg-red-600 text-white font-bold">FLAGGED: OVER BALANCE</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Reason (Optional)</label>
                                <textarea wire:model="reason" rows="2" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f]" placeholder="State the reason for this leave request..."></textarea>
                            </div>
                        </div>

                        {{-- Right Column: Dates & Hours Breakdown Matrix --}}
                        <div class="space-y-3.5 flex flex-col h-auto">
                            @if(count($days_breakdown) > 0)
                                @php
                                    $weeklyList = $this->weeklyBreakdown;
                                    $totalWeeks = count($weeklyList);
                                    $currentPage = max(0, min($this->breakdown_week_page, max(0, $totalWeeks - 1)));
                                    $currentWeek = $weeklyList[$currentPage] ?? null;
                                @endphp

                                <div>
                                    <div class="flex justify-between items-center mb-1.5">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Dates & Hours Breakdown</label>
                                            <span class="text-[10px] text-gray-500">Toggle unpaid or adjust hours</span>
                                        </div>
                                        @if($totalWeeks > 1)
                                            <div class="text-[11px] font-bold text-[#005b8f] bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                                Week {{ $currentPage + 1 }} of {{ $totalWeeks }}
                                            </div>
                                        @endif
                                    </div>

                                    @if($totalWeeks > 1)
                                        {{-- Weekly Pagination Navigation Controls --}}
                                        <div class="flex items-center justify-between bg-slate-50 border border-gray-200 rounded-t p-2 text-xs">
                                            <button type="button" 
                                                    wire:click="prevBreakdownWeek"
                                                    @disabled($currentPage <= 0)
                                                    class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                                <i class="fa-solid fa-chevron-left text-[9px]"></i> Prev Week
                                            </button>

                                            <div class="text-center px-1">
                                                <div class="font-bold text-gray-800 text-xs">
                                                    {{ $currentWeek['label'] ?? '' }}
                                                </div>
                                                <div class="text-[10px] text-gray-500 font-medium">
                                                    {{ count($currentWeek['days'] ?? []) }} day(s) &bull; {{ number_format($currentWeek['total_hours'] ?? 0, 1) }}h (Paid: {{ number_format($currentWeek['paid_hours'] ?? 0, 1) }}h, Unpaid: {{ number_format($currentWeek['unpaid_hours'] ?? 0, 1) }}h)
                                                </div>
                                            </div>

                                            <button type="button" 
                                                    wire:click="nextBreakdownWeek"
                                                    @disabled($currentPage >= $totalWeeks - 1)
                                                    class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs transition">
                                                Next Week <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                            </button>
                                        </div>

                                        {{-- Week Quick Jump Tabs if more than 1 week --}}
                                        @if($totalWeeks <= 8)
                                            <div class="flex items-center gap-1 px-2 py-1 bg-gray-100/80 border-x border-gray-200 overflow-x-auto text-[11px]">
                                                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Weeks:</span>
                                                @foreach($weeklyList as $wIdx => $w)
                                                    <button type="button"
                                                            wire:click="setBreakdownWeekPage({{ $wIdx }})"
                                                            class="px-2 py-0.5 rounded text-[11px] transition {{ $wIdx === $currentPage ? 'bg-[#005b8f] text-white font-bold shadow-2xs' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' }}">
                                                        W{{ $wIdx + 1 }} ({{ $w['short_label'] }})
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @elseif($currentWeek)
                                        <div class="bg-slate-50 border border-gray-200 rounded-t px-3 py-1.5 flex justify-between items-center text-xs text-gray-700">
                                            <span class="font-semibold text-[#005b8f]">{{ $currentWeek['label'] }}</span>
                                            <span class="text-[11px] text-gray-500 font-medium">{{ count($currentWeek['days']) }} day(s) &bull; {{ number_format($currentWeek['total_hours'], 1) }} hrs</span>
                                        </div>
                                    @endif

                                    {{-- Breakdown Table (h-auto and max-h so it never overflows modal) --}}
                                    <div class="border {{ $totalWeeks > 0 ? 'border-t-0' : '' }} border-gray-200 rounded-b overflow-hidden h-auto max-h-[360px] overflow-y-auto">
                                        <table class="w-full text-xs text-left">
                                            <thead class="bg-gray-100 border-b border-gray-200 font-semibold text-gray-600 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-2">Date</th>
                                                <th class="p-2">Shift Type</th>
                                                <th class="p-2 text-center">Unpaid?</th>
                                                <th class="p-2 text-right w-20">Hours</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                            @foreach($currentWeek['days'] ?? [] as $day)
                                                @php $index = $day['original_index']; @endphp
                                                <tr class="{{ $day['is_weekend'] ? 'bg-amber-50/40' : 'bg-white' }} hover:bg-gray-50">
                                                    <td class="p-2 font-medium text-gray-800">
                                                        {{ $day['formatted'] }}
                                                        <span class="block text-[10px] text-gray-400">{{ $day['day_name'] }}</span>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="inline-flex rounded border border-gray-300 overflow-hidden text-[10px]">
                                                            <button type="button" wire:click="setDayType({{ $index }}, 'full')" class="px-1.5 py-0.5 {{ ($days_breakdown[$index]['type'] ?? '') === 'full' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                                Full
                                                            </button>
                                                            <button type="button" wire:click="setDayType({{ $index }}, 'partial')" class="px-1.5 py-0.5 border-l border-r border-gray-300 {{ ($days_breakdown[$index]['type'] ?? '') === 'partial' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                                Partial
                                                            </button>
                                                            <button type="button" wire:click="setDayType({{ $index }}, 'off')" class="px-1.5 py-0.5 {{ ($days_breakdown[$index]['type'] ?? '') === 'off' ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                                Off
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2 text-center">
                                                        <input type="checkbox" wire:click="toggleDayUnpaid({{ $index }})" {{ !empty($days_breakdown[$index]['is_unpaid']) ? 'checked' : '' }} class="rounded text-[#005b8f] cursor-pointer" title="Mark as Unpaid Time">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number" step="0.5" min="0" max="24"
                                                               wire:model.live.debounce.250ms="days_breakdown.{{ $index }}.hours"
                                                               @disabled(($days_breakdown[$index]['type'] ?? '') === 'off')
                                                               class="border border-gray-300 rounded p-1 text-xs text-right w-16 outline-none focus:border-[#005b8f] disabled:bg-gray-100 disabled:text-gray-400">
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            @if($totalWeeks > 1)
                                                <tfoot class="bg-gray-50 border-t border-gray-200 text-gray-600 font-semibold text-[11px]">
                                                    <tr>
                                                        <td colspan="3" class="p-2 text-right text-gray-500 font-normal">Week Subtotal:</td>
                                                        <td class="p-2 text-right font-bold text-gray-800">{{ number_format($currentWeek['total_hours'] ?? 0, 1) }}h</td>
                                                    </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                    @if($totalWeeks > 1)
                                        <div class="flex justify-between items-center text-[10px] text-gray-500 pt-1.5 px-0.5">
                                            <span>Showing week {{ $currentPage + 1 }} of {{ $totalWeeks }} ({{ count($days_breakdown) }} days total)</span>
                                            <span>Grand Total: <strong class="text-gray-800">{{ number_format($total_requested_hours, 1) }}h</strong> (Paid: {{ number_format($paid_hours, 1) }}h)</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="h-full flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-lg text-center text-gray-400">
                                    <span class="text-3xl mb-2">📅</span>
                                    <p class="font-medium">Dates & Breakdown</p>
                                    <p class="text-[11px] mt-1">Select a valid Start Date and End Date on the left to configure individual daily shifts.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeRequestModal" class="px-4 py-2 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100 font-semibold">Cancel</button>
                    <button type="button" wire:click="submitRequest" @disabled(count($empPolicies) === 0) class="bg-[#005b8f] hover:bg-[#004a75] disabled:opacity-50 text-white px-5 py-2 rounded text-xs font-bold shadow transition">Submit Request</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Request Thread & HR View Drawer Modal with Strict Permission Rules --}}
    @if($isDetailModalOpen && $selectedRequest)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-6xl max-h-[600px] h-[600px] flex flex-col overflow-hidden my-auto">
                <div class="px-5 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                    <div>
                        <h2 class="text-base font-bold text-gray-800">
                            Time Off Request Review: {{ $selectedRequest->employee?->name ?? 'Employee' }}
                        </h2>
                        <span class="text-[11px] text-gray-500">Submitted {{ $selectedRequest->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-4 flex-1 min-h-0 overflow-y-auto md:overflow-hidden text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 h-full min-h-0">
                        {{-- COLUMN 1: Request Overview, Range Adjuster, Entitlements & Recent History --}}
                        <div class="flex flex-col h-full min-h-0 overflow-y-auto space-y-3 pr-1">
                            {{-- Summary Overview Card --}}
                            <div class="p-2.5 bg-gray-50 rounded border border-gray-200 text-xs space-y-2 shrink-0">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-gray-500 block text-[10px] font-semibold uppercase tracking-wider">Requested Period</span>
                                        <strong class="text-gray-900 text-xs">{{ $selectedRequest->start_at->format('m/d/Y') }} - {{ $selectedRequest->end_at->format('m/d/Y') }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[10px] font-semibold uppercase tracking-wider">Total Hours</span>
                                        <strong class="text-xs text-gray-900">{{ number_format($selectedRequest->hours, 1) }} hrs</strong>
                                        @if($selectedRequest->unpaid_hours > 0)
                                            <span class="text-[10px] text-amber-700 block leading-tight">({{ $selectedRequest->paid_hours }}h paid, {{ $selectedRequest->unpaid_hours }}h unpaid)</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[10px] font-semibold uppercase tracking-wider">Policy</span>
                                        <strong class="text-gray-900 text-xs truncate block" title="{{ $selectedRequest->policy?->name ?? 'Standard Policy' }}">{{ $selectedRequest->policy?->name ?? 'Standard Policy' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[10px] font-semibold uppercase tracking-wider">Approver Routing</span>
                                        <strong class="text-[#005b8f] text-xs truncate block">{{ $selectedRequest->approver?->name ?? 'Admins' }} & HR</strong>
                                    </div>
                                </div>
                                @if(!empty($selectedRequest->reason))
                                    <div class="pt-1.5 border-t border-gray-200 text-[11px]">
                                        <span class="text-gray-500 font-semibold">Reason:</span>
                                        <span class="text-gray-700 italic">{{ $selectedRequest->reason }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Date Range Adjuster (Admin & HR Manager Only) --}}
                            @if($this->canEditDateRanges())
                                <div class="p-2.5 bg-indigo-50/50 border border-indigo-200 rounded text-xs shrink-0">
                                    <div class="flex justify-between items-center mb-1.5">
                                        <h4 class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider">Modify Range (Admin/HR)</h4>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-600 mb-0.5">Start Date</label>
                                            <input type="date" wire:model.live="detail_start_date" class="w-full border border-gray-300 rounded p-1 text-xs outline-none focus:border-[#005b8f] bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-600 mb-0.5">End Date</label>
                                            <input type="date" wire:model.live="detail_end_date" class="w-full border border-gray-300 rounded p-1 text-xs outline-none focus:border-[#005b8f] bg-white">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Entitled Leave Heads Summary --}}
                            <div class="shrink-0">
                                <div class="flex justify-between items-center mb-1">
                                    <h4 class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Leave Balances</h4>
                                    @if(auth()->user()->isAdmin() || auth()->user()->isHrManager())
                                        <button type="button" wire:click="openAdjustmentModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-2 py-0.5 rounded text-[10px] font-semibold transition">
                                            Direct Adjust
                                        </button>
                                    @endif
                                </div>
                                <div class="border border-gray-200 rounded overflow-hidden">
                                    <table class="w-full text-[11px] text-left">
                                        <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                        <tr>
                                            <th class="p-1.5">Policy / Head</th>
                                            <th class="p-1.5 text-right">Available</th>
                                            <th class="p-1.5 text-right">After Req</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @foreach($employeeEntitlements as $ent)
                                            @php
                                                $isThisPolicy = $ent->policy_id === $selectedRequest->policy_id;
                                                $deduct = $selectedRequest->paid_hours > 0 ? (float)$selectedRequest->paid_hours : (float)$selectedRequest->hours;
                                                $proj = $isThisPolicy ? ((float)$ent->hours_remaining - $deduct) : (float)$ent->hours_remaining;
                                            @endphp
                                            <tr class="{{ $isThisPolicy ? 'bg-blue-50/50 font-medium' : '' }}">
                                                <td class="p-1.5 truncate max-w-[110px]" title="{{ $ent->policy?->name ?? 'Policy' }}">
                                                    {{ $ent->policy?->name ?? 'Policy' }}
                                                    @if($isThisPolicy) <span class="text-[9px] text-[#005b8f] font-bold">(Sel)</span> @endif
                                                </td>
                                                <td class="p-1.5 text-right whitespace-nowrap">{{ number_format($ent->hours_remaining, 1) }}h</td>
                                                <td class="p-1.5 text-right whitespace-nowrap {{ $proj < 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                                    {{ number_format($proj, 1) }}h
                                                    @if($proj < 0)
                                                         <span class="text-[9px] bg-red-100 text-red-700 px-1 py-0.2 rounded ml-0.5">Over</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Leave History --}}
                            @if(count($employeeLeaveHistory) > 0)
                                <div>
                                    <h4 class="text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1">Recent Leave History</h4>
                                    <div class="border border-gray-200 rounded overflow-hidden max-h-36 overflow-y-auto">
                                        <table class="w-full text-[11px] text-left">
                                            <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-1.5">Dates</th>
                                                <th class="p-1.5">Policy</th>
                                                <th class="p-1.5 text-right">Hours</th>
                                                <th class="p-1.5 text-center">Status</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                            @foreach($employeeLeaveHistory as $hist)
                                                <tr>
                                                    <td class="p-1.5 whitespace-nowrap">{{ $hist->start_at->format('m/d') }} - {{ $hist->end_at->format('m/d') }}</td>
                                                    <td class="p-1.5 truncate max-w-[80px]" title="{{ $hist->policy?->name ?? 'Standard' }}">{{ $hist->policy?->name ?? 'Standard' }}</td>
                                                    <td class="p-1.5 text-right whitespace-nowrap">{{ number_format($hist->hours, 1) }}h</td>
                                                    <td class="p-1.5 text-center whitespace-nowrap">
                                                        <span class="px-1 py-0.2 rounded text-[9px] font-semibold
                                                            {{ $hist->status === 'approved' ? 'bg-green-100 text-green-800' : ($hist->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                            {{ ucfirst($hist->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- COLUMN 2: Scheduled Daily Hours Breakdown --}}
                        <div class="flex flex-col h-full min-h-0 space-y-2">
                            <div class="flex justify-between items-center shrink-0">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Scheduled Daily Hours Breakdown</h4>
                                @if($this->canEditDetailBreakdown())
                                    <span class="text-[10px] text-gray-500 font-medium">Editable</span>
                                @else
                                    <span class="text-[10px] text-gray-400">Locked / Read-Only</span>
                                @endif
                            </div>

                            @php
                                $detailWeeklyList = $this->detailWeeklyBreakdown;
                                $detailTotalWeeks = count($detailWeeklyList);
                                $detailCurrentPage = max(0, min($this->detail_breakdown_week_page, max(0, $detailTotalWeeks - 1)));
                                $detailCurrentWeek = $detailWeeklyList[$detailCurrentPage] ?? null;
                            @endphp

                            @if($detailTotalWeeks > 1)
                                {{-- Weekly Pagination Navigation Controls for Detail Modal --}}
                                <div class="flex items-center justify-between bg-slate-50 border border-gray-200 rounded p-1.5 text-xs shrink-0">
                                    <button type="button" 
                                            wire:click="prevDetailBreakdownWeek"
                                            @disabled($detailCurrentPage <= 0)
                                            class="px-2 py-0.5 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs text-[11px] transition">
                                        <i class="fa-solid fa-chevron-left text-[9px]"></i> Prev
                                    </button>

                                    <div class="text-center px-1">
                                        <div class="font-bold text-gray-800 text-[11px]">
                                            {{ $detailCurrentWeek['label'] ?? '' }}
                                        </div>
                                        <div class="text-[9px] text-gray-500">
                                            Week {{ $detailCurrentPage + 1 }} of {{ $detailTotalWeeks }} &bull; {{ number_format($detailCurrentWeek['total_hours'] ?? 0, 1) }}h (Paid: {{ number_format($detailCurrentWeek['paid_hours'] ?? 0, 1) }}h)
                                        </div>
                                    </div>

                                    <button type="button" 
                                            wire:click="nextDetailBreakdownWeek"
                                            @disabled($detailCurrentPage >= $detailTotalWeeks - 1)
                                            class="px-2 py-0.5 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed font-medium text-gray-700 flex items-center gap-1 shadow-2xs text-[11px] transition">
                                        Next <i class="fa-solid fa-chevron-right text-[9px]"></i>
                                    </button>
                                </div>
                            @elseif($detailCurrentWeek)
                                <div class="bg-slate-50 border border-gray-200 rounded px-2.5 py-1 flex justify-between items-center text-xs text-gray-700 shrink-0">
                                    <span class="font-semibold text-[#005b8f] text-[11px]">{{ $detailCurrentWeek['label'] }}</span>
                                    <span class="text-[10px] text-gray-500 font-medium">{{ count($detailCurrentWeek['days']) }} day(s) &bull; {{ number_format($detailCurrentWeek['total_hours'], 1) }} hrs</span>
                                </div>
                            @endif

                            <div class="border border-gray-200 rounded overflow-hidden flex-1 min-h-0 overflow-y-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="p-2">Date</th>
                                        <th class="p-2">Shift Type</th>
                                        <th class="p-2 text-center">Unpaid?</th>
                                        <th class="p-2 text-right w-16">Hours</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @foreach(($detailCurrentWeek['days'] ?? $detail_days_breakdown) as $d)
                                        @php $idx = $d['original_index'] ?? $loop->index; @endphp
                                        <tr class="{{ !empty($d['is_weekend']) ? 'bg-amber-50/30' : 'bg-white' }} hover:bg-gray-50">
                                            <td class="p-2 font-medium text-gray-800 text-[11px] whitespace-nowrap">
                                                {{ $d['formatted'] }}
                                                <span class="block text-[10px] text-gray-400 font-normal">{{ $d['day_name'] }}</span>
                                            </td>
                                            <td class="p-2">
                                                @if($this->canEditDetailBreakdown())
                                                    <div class="inline-flex rounded border border-gray-300 overflow-hidden text-[9px]">
                                                        <button type="button" wire:click="setDetailDayType({{ $idx }}, 'full')" class="px-1.5 py-0.5 {{ ($d['type'] ?? '') === 'full' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">Full</button>
                                                        <button type="button" wire:click="setDetailDayType({{ $idx }}, 'partial')" class="px-1.5 py-0.5 border-l border-r border-gray-300 {{ ($d['type'] ?? '') === 'partial' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">Part</button>
                                                        <button type="button" wire:click="setDetailDayType({{ $idx }}, 'off')" class="px-1.5 py-0.5 {{ ($d['type'] ?? '') === 'off' ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">Off</button>
                                                    </div>
                                                @else
                                                    <span class="capitalize text-gray-600 text-[11px] font-medium">{{ $d['type'] ?? 'Standard' }}</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-center">
                                                @if($this->canEditDetailBreakdown())
                                                    <input type="checkbox" wire:click="toggleDetailDayUnpaid({{ $idx }})" {{ !empty($d['is_unpaid']) ? 'checked' : '' }} class="rounded text-[#005b8f] cursor-pointer" title="Mark as Unpaid">
                                                @else
                                                    <span class="{{ !empty($d['is_unpaid']) ? 'text-amber-700 font-bold text-[10px]' : 'text-gray-400 text-[10px]' }}">{{ !empty($d['is_unpaid']) ? 'Yes' : 'No' }}</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-right">
                                                @if($this->canEditDetailBreakdown())
                                                    <input type="number" step="0.5" min="0" max="24" wire:model.live.debounce.250ms="detail_days_breakdown.{{ $idx }}.hours" @disabled(($d['type'] ?? '') === 'off') class="w-14 border border-gray-300 rounded p-1 text-xs text-right outline-none focus:border-[#005b8f] disabled:bg-gray-100 disabled:text-gray-400">
                                                @else
                                                    <span class="font-medium text-gray-800 text-xs">{{ number_format($d['hours'] ?? 0, 1) }}h</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    @if($detailTotalWeeks > 1)
                                        <tfoot class="bg-gray-50 border-t border-gray-200 text-gray-600 font-semibold text-[11px]">
                                            <tr>
                                                <td colspan="3" class="p-2 text-right text-gray-500 font-normal">Week Subtotal:</td>
                                                <td class="p-2 text-right font-bold text-gray-800">{{ number_format($detailCurrentWeek['total_hours'] ?? 0, 1) }}h</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>

                            @if($is_detail_breakdown_dirty && $this->canEditDetailBreakdown())
                                <div class="flex justify-end pt-1 shrink-0">
                                    <button type="button" wire:click="saveDetailBreakdownChanges" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-3 py-1 rounded text-xs font-semibold shadow transition">
                                        Save Changes
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- COLUMN 3: Routing & Discussion Thread --}}
                        <div class="flex flex-col h-full min-h-0 space-y-2">
                            <div class="flex justify-between items-center shrink-0">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Routing & Discussion Thread</h4>
                                <span class="text-[10px] text-gray-400">{{ count($selectedRequest->threads) }} update(s)</span>
                            </div>

                            <div class="border border-gray-200 rounded p-3 bg-gray-50 flex-1 min-h-0 overflow-y-auto space-y-2.5">
                                @forelse($selectedRequest->threads as $thread)
                                    @if($thread->type === 'system_event' || $thread->type === 'status_change')
                                        <div class="text-center my-1">
                                            <span class="inline-block bg-gray-200 text-gray-700 text-[10px] px-2.5 py-0.5 rounded-full shadow-2xs">
                                                {{ $thread->message }} &bull; <small class="text-gray-500">{{ $thread->created_at->format('M d, h:i A') }}</small>
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex gap-2 {{ $thread->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                            <div class="max-w-[85%] p-2 rounded-lg text-xs {{ $thread->user_id === auth()->id() ? 'bg-[#005b8f] text-white' : 'bg-white border border-gray-200 text-gray-800 shadow-2xs' }}">
                                                <div class="font-semibold text-[10px] mb-0.5 {{ $thread->user_id === auth()->id() ? 'text-blue-100' : 'text-gray-500' }}">
                                                    {{ $thread->user?->name ?? 'User' }} &bull; {{ $thread->created_at->format('M d, h:i A') }}
                                                </div>
                                                <div class="break-words leading-relaxed">{{ $thread->message }}</div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="h-full flex flex-col items-center justify-center text-center text-gray-400 py-8">
                                        <span class="text-2xl mb-1">💬</span>
                                        <p class="text-xs">No messages or thread updates recorded yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="flex gap-1.5 pt-1 shrink-0">
                                <input type="text" wire:model="newThreadComment" wire:keydown.enter="addThreadComment" placeholder="Write a comment or response..." class="flex-1 border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f]">
                                <button type="button" wire:click="addThreadComment" class="bg-gray-800 hover:bg-black text-white px-3 py-1.5 rounded text-xs font-semibold shrink-0">Post</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Drawer Footer: Manager can Approve / Deny or Employee can Withdraw --}}
                <div class="px-5 py-2.5 border-t border-gray-200 bg-gray-50 flex justify-between items-center shrink-0">
                    <button wire:click="closeViewModal" class="px-4 py-1.5 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100 font-semibold">Close</button>

                    <div class="space-x-2">
                        @if($selectedRequest->status === 'pending')
                            @if(auth()->id() === $selectedRequest->employee_id)
                                <button wire:click="withdrawRequest({{ $selectedRequest->id }}); closeViewModal();" wire:confirm="Withdraw this request?" class="px-3 py-1.5 border border-red-300 rounded text-xs text-red-600 hover:bg-red-50 font-semibold">
                                    Withdraw Request
                                </button>
                            @endif

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || (auth()->user()->isManager() && auth()->user()->canManagerDo('manage_time_off') && (auth()->id() === $selectedRequest->approver_id || auth()->user()->canManageEmployee($selectedRequest->employee_id))))
                                <button wire:click="openDecisionModal({{ $selectedRequest->id }}, 'denied')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded text-xs font-semibold shadow">Deny</button>
                                <button wire:click="openDecisionModal({{ $selectedRequest->id }}, 'approved')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-xs font-semibold shadow">Approve</button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Clean Direct Balance Adjustment Modal (Restricted strictly to Admins and HR Managers) --}}
    @if($isAdjustmentModalOpen)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded shadow-2xl w-full max-w-lg overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Adjust Balances: {{ $adj_employee_name }}</h2>
                        <span class="text-xs text-gray-500">Edit the balance field directly to add or deduct hours.</span>
                    </div>
                    <button wire:click="closeAdjustmentModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-sm">
                    <div class="border border-gray-200 rounded overflow-hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-100 text-gray-600 font-semibold border-b">
                            <tr>
                                <th class="p-2.5">Policy Head</th>
                                <th class="p-2.5 text-right">Current</th>
                                <th class="p-2.5 text-right w-36">New Balance (hrs)</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($adj_policy_allocations as $idx => $alloc)
                                @php
                                    $diff = (float)$alloc['new_hours'] - (float)$alloc['original_hours'];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="p-2.5 font-medium text-gray-800">{{ $alloc['name'] }}</td>
                                    <td class="p-2.5 text-right text-gray-500">{{ number_format($alloc['original_hours'], 1) }}h</td>
                                    <td class="p-2 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($diff != 0)
                                                <span class="text-[10px] font-bold {{ $diff > 0 ? 'text-green-600' : 'text-red-500' }}">
                                                        ({{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }})
                                                    </span>
                                            @endif
                                            <input type="number" step="0.5" wire:model.live.debounce.250ms="adj_policy_allocations.{{ $idx }}.new_hours" class="w-20 border border-gray-300 rounded p-1 text-xs text-right outline-none focus:border-[#005b8f]">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Reason for Adjustment *</label>
                        <textarea wire:model="adj_reason" rows="2" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f]" placeholder="e.g. Granted compensatory time, manual correction, emergency policy override..."></textarea>
                        @error('adj_reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="closeAdjustmentModal" class="px-3 py-1.5 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button wire:click="saveAdjustment" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-4 py-1.5 rounded text-xs font-semibold shadow">
                        Save Balances
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Decision Confirmation Modal --}}
    @if($isDecisionModalOpen)
        @teleport('body')
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded shadow-2xl w-full max-w-md overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <h2 class="text-base font-semibold text-gray-800">Confirm {{ ucfirst($decisionAction) }}</h2>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    @php
                        $decReq = \App\Models\TimeOffRequest::with(['employee', 'policy'])->find($activeRequestId);
                        $decBal = $decReq ? \App\Models\TimeOffPolicyEmployee::where('employee_id', $decReq->employee_id)->where('policy_id', $decReq->policy_id)->first() : null;
                        $decAvail = $decBal ? (float)$decBal->hours_remaining : 0.00;
                        $decHours = $decReq ? ($decReq->paid_hours > 0 ? (float)$decReq->paid_hours : (float)$decReq->hours) : 0.00;
                        $decRem = $decAvail - $decHours;
                    @endphp
                    @if($decReq)
                        <div class="bg-gray-50 border border-gray-200 rounded p-3 text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Employee:</span>
                                <strong class="text-gray-900">{{ $decReq->employee?->name ?? 'Employee' }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Policy:</span>
                                <strong class="text-gray-900">{{ $decReq->policy?->name ?? 'Standard' }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Current Balance:</span>
                                <strong>{{ number_format($decAvail, 1) }} hrs</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Requested Time:</span>
                                <strong class="text-amber-700">{{ number_format($decHours, 1) }} hrs</strong>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-1">
                                <span class="text-gray-500">Remaining After Approval:</span>
                                <strong class="{{ $decRem < 0 ? 'text-red-600' : 'text-green-700' }} font-bold">
                                    {{ number_format($decRem, 1) }} hrs {{ $decRem < 0 ? '(Over Credit)' : '' }}
                                </strong>
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-gray-500">
                        {{ $decisionAction === 'approved'
                            ? 'Approving this request will deduct the paid hours from the balance and notify the employee.'
                            : 'Denying this request will mark it as denied and send your note/reason to the employee.' }}
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">HR Note to Employee (e.g. reason for denial / notes)</label>
                        <textarea wire:model="decisionNotes" rows="2" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]" placeholder="Provide a reason or note for the employee..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="closeDecisionModal" class="px-3 py-1.5 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button wire:click="processDecision" class="px-4 py-1.5 rounded text-xs font-semibold text-white {{ $decisionAction === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                        Confirm {{ ucfirst($decisionAction) }}
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
