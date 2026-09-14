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

    <h1 class="text-3xl font-normal text-gray-800 mb-4">Time Off</h1>

    {{-- TABS --}}
    <div class="border-b border-gray-200 mb-4">
        <nav class="-mb-px flex gap-6">
            <button wire:click="setTab('requests')"
                    class="py-2 px-1 text-sm font-semibold border-b-2
                    {{ $tab === 'requests'
                        ? 'border-cs-blue text-cs-blue'
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Requests
            </button>
            <button wire:click="setTab('policies')"
                    class="py-2 px-1 text-sm font-semibold border-b-2
                    {{ $tab === 'policies'
                        ? 'border-cs-blue text-cs-blue'
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Policies
            </button>
        </nav>
    </div>

    {{-- ============ REQUESTS TAB ============ --}}
    @if($tab === 'requests')
        <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select wire:model.live="requestFilter" class="border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Employee</label>
                <select wire:model.live="requestEmployeeFilter" class="border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="requestSearch"
                       placeholder="Reason / Employee"
                       class="border border-gray-300 rounded px-2 py-1 text-sm">
            </div>

            <div class="ml-auto">
                <button wire:click="openRequest"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
                    + Add Time Off
                </button>
            </div>
        </div>

        <div class="border border-gray-200 rounded bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="p-3 text-gray-600 font-semibold">Employee</th>
                    <th class="p-3 text-gray-600 font-semibold">Policy</th>
                    <th class="p-3 text-gray-600 font-semibold">Start</th>
                    <th class="p-3 text-gray-600 font-semibold">End</th>
                    <th class="p-3 text-gray-600 font-semibold">Hours</th>
                    <th class="p-3 text-gray-600 font-semibold">Reason</th>
                    <th class="p-3 text-gray-600 font-semibold">Status</th>
                    <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $r)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="p-3 text-gray-800">{{ $r->employee->name ?? '—' }}</td>
                        <td class="p-3 text-cs-blue">{{ $r->policy->name ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $r->start_at?->format('M j, Y g:i A') }}</td>
                        <td class="p-3 text-gray-700">{{ $r->end_at?->format('M j, Y g:i A') }}</td>
                        <td class="p-3 text-gray-800">{{ number_format($r->hours, 2) }}</td>
                        <td class="p-3 text-gray-600 text-xs">{{ $r->reason ?? '—' }}</td>
                        <td class="p-3">
                            @if($r->status === 'approved')
                                <span class="text-green-700 text-xs font-semibold">Approved</span>
                            @elseif($r->status === 'denied')
                                <span class="text-red-600 text-xs font-semibold">Denied</span>
                            @elseif($r->status === 'cancelled')
                                <span class="text-gray-500 text-xs font-semibold">Cancelled</span>
                            @else
                                <span class="text-orange-600 text-xs font-semibold">Pending</span>
                            @endif
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            @if($r->status === 'pending')
                                <button wire:click="approveRequest({{ $r->id }})"
                                        class="text-cs-blue text-xs font-semibold mr-2 hover:text-blue-800">Approve</button>
                                <button wire:click="denyRequest({{ $r->id }})"
                                        class="text-red-500 text-xs hover:text-red-700">Deny</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400">No requests.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ============ POLICIES TAB ============ --}}
    @if($tab === 'policies')
        <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="policySearch"
                       placeholder="Policy name"
                       class="border border-gray-300 rounded px-2 py-1 text-sm">
            </div>

            <div class="ml-auto">
                <a href="{{ route('timeoff.policies.add') }}"
                   class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold inline-block">
                    + Add Policy
                </a>
            </div>
        </div>

        <div class="border border-gray-200 rounded bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="p-3 text-gray-600 font-semibold">Type</th>
                    <th class="p-3 text-gray-600 font-semibold">Policy</th>
                    <th class="p-3 text-gray-600 font-semibold">Enrolled</th>
                    <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($policies as $p)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="p-3 text-gray-700">{{ $p->policyTypeLabel() }}</td>
                        <td class="p-3 text-cs-blue font-semibold">
                            <a href="{{ route('timeoff.policies.edit', $p->id) }}" class="hover:underline">
                                {{ $p->name }}
                            </a>
                        </td>
                        <td class="p-3 text-gray-700">{{ $p->employees_count }}</td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <a href="{{ route('timeoff.policies.edit', $p->id) }}"
                               class="text-cs-blue text-xs font-semibold mr-3 hover:text-blue-800">Edit</a>
                            <button wire:click="deletePolicy({{ $p->id }})"
                                    wire:confirm="Delete this policy?"
                                    class="text-red-500 text-xs hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400">
                            <em>You have not yet configured a time off policy. Choose "Add Policy" to get started.</em>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ============ ADD REQUEST MODAL ============ --}}
    @if($showRequestModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">Add Time Off</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Employee*</label>
                        <select wire:model="rqEmployeeId" class="w-full border rounded p-2 text-sm">
                            <option value="">Choose an employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                            @endforeach
                        </select>
                        @error('rqEmployeeId') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Policy</label>
                        <select wire:model="rqPolicyId" class="w-full border rounded p-2 text-sm">
                            <option value="">— none —</option>
                            @foreach($policies as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Start*</label>
                            <input type="datetime-local" wire:model="rqStart" class="w-full border rounded p-2 text-sm">
                            @error('rqStart') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">End*</label>
                            <input type="datetime-local" wire:model="rqEnd" class="w-full border rounded p-2 text-sm">
                            @error('rqEnd') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Reason</label>
                        <textarea wire:model="rqReason" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="cancelRequest" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="saveRequest" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">Submit</button>
                </div>
            </div>
        </div>
    @endif
</div>
