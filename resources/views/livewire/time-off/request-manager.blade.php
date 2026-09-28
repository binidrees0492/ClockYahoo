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

            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isManager())
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
                @endphp
                <tr class="hover:bg-gray-50 transition {{ $isOver ? 'bg-amber-50/30' : '' }}">
                    <td class="p-3">
                        <span class="font-medium text-gray-900 block">{{ $req->employee->name }}</span>
                        <span class="text-[11px] text-gray-400">{{ $req->employee->email }}</span>
                    </td>
                    <td class="p-3 text-gray-700">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 border border-gray-200">
                                {{ $req->policy->name ?? 'Policy' }}
                            </span>
                    </td>
                    <td class="p-3 text-xs text-gray-600">
                        {{ $req->start_at->format('M d, Y') }} - {{ $req->end_at->format('M d, Y') }}
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
                            <span class="block text-[10px] text-red-600 font-bold uppercase tracking-tight">Over Credit</span>
                        @endif
                    </td>
                    <td class="p-3 text-right font-medium {{ $avail <= 0 ? 'text-red-500' : 'text-gray-600' }}">
                        {{ number_format($avail, 1) }}h
                    </td>
                    <td class="p-3 text-xs text-gray-600">
                        <div><strong>Mgr:</strong> {{ $req->approver ? $req->approver->name : 'Unassigned' }}</div>
                        <div class="text-[11px] text-gray-400">HR Managers</div>
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

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->id() === $req->approver_id || auth()->user()->canManageEmployee($req->employee_id))
                                <button wire:click="openDecisionModal({{ $req->id }}, 'approved')" class="text-green-600 hover:underline text-xs font-semibold">Approve</button>
                                <button wire:click="openDecisionModal({{ $req->id }}, 'denied')" class="text-red-500 hover:underline text-xs font-semibold">Deny</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-gray-400">No time off requests found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $requests->links() }}
        </div>
    </div>

    {{-- Employee Request Modal with Inline Days Table Options --}}
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-medium text-gray-800">Request Time Off</h2>
                    <button wire:click="closeRequestModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 text-sm flex-1">
                    @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->user()->isManager())
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Employee</label>
                            <select wire:model.live="selected_employee_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
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
                        <select wire:model.live="policy_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                            @if(count($empPolicies) > 0)
                                <option value="">Select an enrolled policy</option>
                                @foreach($empPolicies as $item)
                                    <option value="{{ $item->policy_id }}">{{ $item->policy->name }} (Available: {{ $item->hours_remaining }} hrs)</option>
                                @endforeach
                            @else
                                <option value="">No time off policies implemented for this employee</option>
                            @endif
                        </select>
                        @error('policy_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date *</label>
                            <input type="date" wire:model.live="start_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">End Date *</label>
                            <input type="date" wire:model.live="end_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                        </div>
                    </div>

                    {{-- Day Breakdown Matrix with Duration Controls & Unpaid Toggles --}}
                    @if(count($days_breakdown) > 0)
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Dates & Hours Breakdown</label>
                                <span class="text-[11px] text-gray-500">Select shift or toggle unpaid hours</span>
                            </div>
                            <div class="border border-gray-200 rounded overflow-hidden max-h-56 overflow-y-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-100 border-b border-gray-200 font-semibold text-gray-600">
                                    <tr>
                                        <th class="p-2.5">Date</th>
                                        <th class="p-2.5">Duration Type</th>
                                        <th class="p-2.5 text-center">Unpaid?</th>
                                        <th class="p-2.5 text-right w-24">Hours</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @foreach($days_breakdown as $index => $day)
                                        <tr class="{{ $day['is_weekend'] ? 'bg-amber-50/40' : 'bg-white' }} hover:bg-gray-50">
                                            <td class="p-2.5 font-medium text-gray-800">
                                                {{ $day['formatted'] }}
                                                <span class="block text-[10px] text-gray-400">{{ $day['day_name'] }}</span>
                                            </td>
                                            <td class="p-2.5">
                                                <div class="inline-flex rounded border border-gray-300 overflow-hidden text-[10px]">
                                                    <button type="button" wire:click="setDayType({{ $index }}, 'full')" class="px-2 py-0.5 {{ $day['type'] === 'full' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                        Full
                                                    </button>
                                                    <button type="button" wire:click="setDayType({{ $index }}, 'partial')" class="px-2 py-0.5 border-l border-r border-gray-300 {{ $day['type'] === 'partial' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                        Partial
                                                    </button>
                                                    <button type="button" wire:click="setDayType({{ $index }}, 'off')" class="px-2 py-0.5 {{ $day['type'] === 'off' ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                                                        Off
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-center">
                                                <input type="checkbox" wire:click="toggleDayUnpaid({{ $index }})" {{ !empty($day['is_unpaid']) ? 'checked' : '' }} class="rounded text-[#005b8f] cursor-pointer" title="Mark as Unpaid Time">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" step="0.5" min="0" max="24"
                                                       wire:model.live.debounce.250ms="days_breakdown.{{ $index }}.hours"
                                                       @disabled($day['type'] === 'off')
                                                       class="border border-gray-300 rounded p-1 text-xs text-right w-20 outline-none focus:border-[#005b8f] disabled:bg-gray-100 disabled:text-gray-400">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="border rounded p-3 text-xs {{ $is_over_balance ? 'bg-red-50/60 border-red-200 text-red-900' : 'bg-blue-50/60 border-blue-200 text-blue-900' }}">
                        <div class="grid grid-cols-4 gap-2 text-center pb-2 border-b {{ $is_over_balance ? 'border-red-200' : 'border-blue-200' }}">
                            <div>
                                <span class="text-gray-500 block">Available Quota</span>
                                <strong class="text-sm">{{ number_format($available_hours, 1) }} hrs</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Paid Request</span>
                                <strong class="text-sm">{{ number_format($paid_hours, 1) }} hrs</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Unpaid Request</span>
                                <strong class="text-sm text-gray-700">{{ number_format($unpaid_hours, 1) }} hrs</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Projected Balance</span>
                                <strong class="text-sm {{ $is_over_balance ? 'text-red-600' : 'text-green-700' }}">
                                    {{ number_format($projected_balance, 1) }} hrs
                                </strong>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-between items-center text-[11px]">
                            <span>Routing Destinations: <strong>{{ $routed_manager_name }}</strong></span>
                            @if($is_over_balance)
                                <span class="px-2 py-0.5 rounded bg-red-600 text-white font-bold">FLAGGED: INSUFFICIENT BALANCE</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Reason (Optional)</label>
                        <textarea wire:model="reason" rows="2" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]" placeholder="State the reason for this leave request..."></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeRequestModal" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="button" wire:click="submitRequest" @disabled(count($empPolicies) === 0) class="bg-[#005b8f] hover:bg-[#004a75] disabled:opacity-50 text-white px-5 py-2 rounded text-sm font-semibold shadow transition">Submit Request</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Request Thread & HR View Drawer Modal with Day Breakdown Editor --}}
    @if($isDetailModalOpen && $selectedRequest)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <div>
                        <h2 class="text-lg font-medium text-gray-800">
                            Time Off Request Review: {{ $selectedRequest->employee->name }}
                        </h2>
                        <span class="text-xs text-gray-500">Submitted {{ $selectedRequest->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6 text-sm flex-1">
                    <div class="grid grid-cols-4 gap-4 p-3 bg-gray-50 rounded border border-gray-200 text-xs">
                        <div>
                            <span class="text-gray-500 block">Requested Period</span>
                            <strong>{{ $selectedRequest->start_at->format('M d, Y') }} - {{ $selectedRequest->end_at->format('M d, Y') }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Total Hours</span>
                            <strong class="text-base text-gray-900">{{ number_format($selectedRequest->hours, 1) }} hrs</strong>
                            @if($selectedRequest->unpaid_hours > 0)
                                <span class="text-[10px] text-amber-700 block">({{ $selectedRequest->paid_hours }}h paid, {{ $selectedRequest->unpaid_hours }}h unpaid)</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-500 block">Policy</span>
                            <strong>{{ $selectedRequest->policy->name }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Approver Routing</span>
                            <strong class="text-[#005b8f]">{{ $selectedRequest->approver ? $selectedRequest->approver->name : 'Admins' }} & HR</strong>
                        </div>
                    </div>

                    {{-- Daywise Breakdown Editor (Admin, HR Manager, or Employee if Pending) --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Scheduled Daily Hours Breakdown</h4>
                            @if($this->canEditDetailBreakdown())
                                <span class="text-[11px] text-gray-500">Editable (Admin, HR Manager, or Requester while Pending)</span>
                            @else
                                <span class="text-[11px] text-gray-400">Locked (Decided or Read-Only for Manager)</span>
                            @endif
                        </div>
                        <div class="border border-gray-200 rounded overflow-hidden max-h-48 overflow-y-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                <tr>
                                    <th class="p-2.5">Date</th>
                                    <th class="p-2.5">Day</th>
                                    <th class="p-2.5">Shift Type</th>
                                    <th class="p-2.5 text-center">Unpaid?</th>
                                    <th class="p-2.5 text-right w-24">Hours</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                @foreach($detail_days_breakdown as $idx => $d)
                                    <tr class="{{ !empty($d['is_weekend']) ? 'bg-amber-50/30' : 'bg-white' }} hover:bg-gray-50">
                                        <td class="p-2 font-medium text-gray-800">{{ $d['formatted'] }}</td>
                                        <td class="p-2 text-gray-500">{{ $d['day_name'] }}</td>
                                        <td class="p-2">
                                            @if($this->canEditDetailBreakdown())
                                                <div class="inline-flex rounded border border-gray-300 overflow-hidden text-[10px]">
                                                    <button type="button" wire:click="setDetailDayType({{ $idx }}, 'full')" class="px-1.5 py-0.5 {{ ($d['type'] ?? '') === 'full' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700' }}">Full</button>
                                                    <button type="button" wire:click="setDetailDayType({{ $idx }}, 'partial')" class="px-1.5 py-0.5 border-l border-r border-gray-300 {{ ($d['type'] ?? '') === 'partial' ? 'bg-[#005b8f] text-white font-semibold' : 'bg-gray-50 text-gray-700' }}">Partial</button>
                                                    <button type="button" wire:click="setDetailDayType({{ $idx }}, 'off')" class="px-1.5 py-0.5 {{ ($d['type'] ?? '') === 'off' ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-50 text-gray-700' }}">Off</button>
                                                </div>
                                            @else
                                                <span class="capitalize text-gray-600">{{ $d['type'] ?? 'Standard' }}</span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center">
                                            @if($this->canEditDetailBreakdown())
                                                <input type="checkbox" wire:click="toggleDetailDayUnpaid({{ $idx }})" {{ !empty($d['is_unpaid']) ? 'checked' : '' }} class="rounded text-[#005b8f] cursor-pointer">
                                            @else
                                                <span class="{{ !empty($d['is_unpaid']) ? 'text-amber-700 font-bold' : 'text-gray-400' }}">{{ !empty($d['is_unpaid']) ? 'Yes' : 'No' }}</span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-right">
                                            @if($this->canEditDetailBreakdown())
                                                <input type="number" step="0.5" min="0" max="24" wire:model.live.debounce.250ms="detail_days_breakdown.{{ $idx }}.hours" @disabled(($d['type'] ?? '') === 'off') class="w-16 border border-gray-300 rounded p-1 text-xs text-right outline-none focus:border-[#005b8f] disabled:bg-gray-100 disabled:text-gray-400">
                                            @else
                                                <span class="font-medium text-gray-800">{{ number_format($d['hours'] ?? 0, 1) }}h</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($is_detail_breakdown_dirty)
                            <div class="flex justify-end mt-2">
                                <button type="button" wire:click="saveDetailBreakdownChanges" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-3 py-1 rounded text-xs font-semibold shadow transition">
                                    Save Daily Breakdown
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- HR Entitled Leave Heads Summary --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Entitled Leave Heads & Current Balances</h4>
                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager())
                                <button type="button" wire:click="openAdjustmentModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-2.5 py-1 rounded text-xs font-semibold transition">
                                    Direct Balance Adjustment
                                </button>
                            @endif
                        </div>
                        <div class="border border-gray-200 rounded overflow-hidden">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                <tr>
                                    <th class="p-2">Policy / Head</th>
                                    <th class="p-2 text-right">Current Available</th>
                                    <th class="p-2 text-right">After Current Request</th>
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
                                        <td class="p-2">
                                            {{ $ent->policy->name }}
                                            @if($isThisPolicy) <span class="text-[10px] text-[#005b8f] font-bold ml-1">(Selected)</span> @endif
                                        </td>
                                        <td class="p-2 text-right">{{ number_format($ent->hours_remaining, 1) }} hrs</td>
                                        <td class="p-2 text-right {{ $proj < 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                            {{ number_format($proj, 1) }} hrs
                                            @if($proj < 0)
                                                <span class="text-[10px] bg-red-100 text-red-700 px-1 py-0.5 rounded ml-1">Over-Credit</span>
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
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Recent Leave History (Past 5)</h4>
                            <div class="border border-gray-200 rounded overflow-hidden">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                    <tr>
                                        <th class="p-2">Dates</th>
                                        <th class="p-2">Policy</th>
                                        <th class="p-2 text-right">Hours</th>
                                        <th class="p-2 text-center">Status</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @foreach($employeeLeaveHistory as $hist)
                                        <tr>
                                            <td class="p-2">{{ $hist->start_at->format('M d, Y') }} - {{ $hist->end_at->format('M d, Y') }}</td>
                                            <td class="p-2">{{ $hist->policy->name }}</td>
                                            <td class="p-2 text-right">{{ number_format($hist->hours, 1) }} hrs</td>
                                            <td class="p-2 text-center">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
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

                    {{-- Activity & Discussion Thread --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Routing & Discussion Thread</h4>
                        <div class="border border-gray-200 rounded p-4 bg-gray-50 space-y-3 max-h-56 overflow-y-auto">
                            @forelse($selectedRequest->threads as $thread)
                                @if($thread->type === 'system_event' || $thread->type === 'status_change')
                                    <div class="text-center my-2">
                                        <span class="inline-block bg-gray-200 text-gray-700 text-[11px] px-3 py-1 rounded-full shadow-2xs">
                                            {{ $thread->message }} &bull; <small class="text-gray-500">{{ $thread->created_at->format('M d, h:i A') }}</small>
                                        </span>
                                    </div>
                                @else
                                    <div class="flex gap-2.5 {{ $thread->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                        <div class="max-w-md p-2.5 rounded-lg text-xs {{ $thread->user_id === auth()->id() ? 'bg-[#005b8f] text-white' : 'bg-white border border-gray-200 text-gray-800 shadow-2xs' }}">
                                            <div class="font-semibold text-[10px] mb-0.5 {{ $thread->user_id === auth()->id() ? 'text-blue-100' : 'text-gray-500' }}">
                                                {{ $thread->user ? $thread->user->name : 'User' }} &bull; {{ $thread->created_at->format('M d, h:i A') }}
                                            </div>
                                            <div>{{ $thread->message }}</div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <p class="text-xs text-gray-400 text-center">No messages or thread updates recorded yet.</p>
                            @endforelse
                        </div>

                        <div class="flex gap-2 mt-2">
                            <input type="text" wire:model="newThreadComment" wire:keydown.enter="addThreadComment" placeholder="Write a comment or routing response..." class="flex-1 border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f]">
                            <button type="button" wire:click="addThreadComment" class="bg-gray-800 hover:bg-black text-white px-3 py-2 rounded text-xs font-semibold">Post</button>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                    <button wire:click="closeViewModal" class="px-4 py-1.5 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100">Close</button>

                    <div class="space-x-2">
                        @if($selectedRequest->status === 'pending')
                            @if(auth()->id() === $selectedRequest->employee_id)
                                <button wire:click="withdrawRequest({{ $selectedRequest->id }}); closeViewModal();" wire:confirm="Withdraw this request?" class="px-3 py-1.5 border border-red-300 rounded text-xs text-red-600 hover:bg-red-50 font-semibold">
                                    Withdraw Request
                                </button>
                            @endif

                            @if(auth()->user()->isAdmin() || auth()->user()->isHrManager() || auth()->id() === $selectedRequest->approver_id || auth()->user()->canManageEmployee($selectedRequest->employee_id))
                                <button wire:click="openDecisionModal({{ $selectedRequest->id }}, 'denied')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded text-xs font-semibold shadow">Deny</button>
                                <button wire:click="openDecisionModal({{ $selectedRequest->id }}, 'approved')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-xs font-semibold shadow">Approve</button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Clean Direct Balance Adjustment Modal (Restricted to Admins and HR Managers) --}}
    @if($isAdjustmentModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Adjust Balances: {{ $adj_employee_name }}</h2>
                        <span class="text-xs text-gray-500">Edit the balance field directly to add or deduct hours[cite: 2].</span>
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
    @endif

    {{-- Decision Confirmation Modal --}}
    @if($isDecisionModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded shadow-2xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <h2 class="text-base font-semibold text-gray-800">Confirm {{ ucfirst($decisionAction) }}</h2>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <p class="text-xs text-gray-500">
                        {{ $decisionAction === 'approved'
                            ? 'Approving this request will immediately deduct the paid requested hours from the employee entitlement balance.'
                            : 'Denying this request will mark it as rejected.' }}
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Decision / Reason Notes (Appended to Thread)</label>
                        <textarea wire:model="decisionNotes" rows="2" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]" placeholder="Provide a reason or routing note..."></textarea>
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
    @endif
</div>
