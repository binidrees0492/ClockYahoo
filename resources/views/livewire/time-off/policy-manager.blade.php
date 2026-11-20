<div class="max-w-7xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-light text-gray-800">Time Off Policies</h1>
            <p class="text-xs text-gray-500 mt-1">Manage accrual rules, waiting periods, and policy limits.</p>
        </div>
        <button wire:click="openAddModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition">
            + Add Policy
        </button>
    </div>

    @if (session()->has('message'))
        <div x-data x-init="window.showToast(@js(session('message')), 'success')" class="hidden"></div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded p-4 mb-4 shadow-sm flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4 items-center flex-1 max-w-lg">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Policies..." class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
            <select wire:model.live="type_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f] min-w-[140px]">
                <option value="">All Types</option>
                <option value="0">PTO</option>
                <option value="1">Sick</option>
                <option value="2">Unpaid</option>
            </select>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$set('search', ''); $set('type_filter', '');" class="text-xs text-[#005b8f] hover:underline font-medium">
                Clear Filters
            </button>
            @if(auth()->user()?->isSuperAdmin())
                <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer select-none bg-gray-50 border border-gray-300 px-3 py-1.5 rounded">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>🗑️ Include Deleted Records</span>
                </label>
            @endif
        </div>
    </div>

    {{-- Policy Table --}}
    <div class="bg-white border border-gray-200 rounded shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
            <tr>
                <th class="p-3">Type</th>
                <th class="p-3">Policy Name</th>
                <th class="p-3">Accrual Method</th>
                <th class="p-3 text-center">Enrolled</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($policies as $policy)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $policy->policy_type === 0 ? 'bg-blue-100 text-blue-800' : ($policy->policy_type === 1 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">
                                {{ $policy->policy_type_name }}
                            </span>
                    </td>
                    <td class="p-3 font-medium text-gray-900">{{ $policy->name }}</td>
                    <td class="p-3 text-gray-600 text-xs">
                        {{ match($policy->accrual_method) {
                            0 => 'Per Pay Period',
                            1 => 'First of the Year',
                            2 => 'Anniversary Date',
                            3 => 'Hours-based',
                            default => 'Standard'
                        } }}
                    </td>
                    <td class="p-3 text-center font-semibold text-gray-700">
                        {{ $policy->policy_employees_count }}
                    </td>
                    <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $policy->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $policy->active ? 'Active' : 'Inactive' }}
                            </span>
                    </td>
                    <td class="p-3 text-right">
                        @if($policy->active)
                            <button wire:click="openEditModal({{ $policy->id }})" class="text-[#005b8f] hover:underline text-xs font-semibold mr-3">Edit</button>
                            <button wire:click="delete({{ $policy->id }})" wire:confirm="Are you sure you want to delete this policy?" class="text-red-500 hover:underline text-xs">Delete</button>
                        @else
                            <span class="text-xs text-gray-400 mr-2">Inactive</span>
                            @if(auth()->user()?->isSuperAdmin())
                                <button wire:click="restorePolicy({{ $policy->id }})" class="text-emerald-600 hover:underline text-xs font-semibold mr-3">Restore</button>
                                <button wire:click="permanentlyDeletePolicy({{ $policy->id }})" wire:confirm="Permanently delete this policy and remove enrolled employees and time-off requests?" class="text-red-700 hover:underline text-xs font-semibold">Delete Permanently</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-400">No time off policies found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $policies->links() }}
        </div>
    </div>

    {{-- Policy Add/Edit Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h2 class="text-xl font-normal text-gray-800">{{ $modalMode === 'add' ? 'Add Time Off Policy' : 'Edit Time Off Policy' }}</h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto text-sm flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Section 1: Basic Information & Policy Type --}}
                        <div class="space-y-3.5 border-b md:border-b-0 md:border-r border-gray-200 pb-4 md:pb-0 md:pr-5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-lines text-[#005b8f]"></i> 1. Policy Details
                            </h3>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Policy Name *</label>
                                <input type="text" wire:model="name" placeholder="e.g. Annual Leave, Medical Leave" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f]">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Policy Type *</label>
                                <select wire:model="policy_type" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                    <option value="0">PTO (Paid Time Off)</option>
                                    <option value="1">Sick Leave</option>
                                    <option value="2">Unpaid Leave</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Accrual Method *</label>
                                <select wire:model="accrual_method" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                    <option value="0">Per Pay Period</option>
                                    <option value="1">First of Year</option>
                                    <option value="2">Anniversary Date</option>
                                    <option value="3">Hours-based</option>
                                </select>
                            </div>

                            <div class="pt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="active" class="rounded text-[#005b8f]">
                                    <span class="font-bold text-xs text-gray-800">Policy is Active</span>
                                </label>
                            </div>
                        </div>

                        {{-- Section 2: Accrual & Balance Limitations --}}
                        <div class="space-y-3.5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-sliders text-[#005b8f]"></i> 2. Accrual & Balance Limits
                            </h3>

                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="is_time_off_limit" class="rounded text-[#005b8f]">
                                    <span class="font-medium text-xs text-gray-700">Limit annual accrual hours</span>
                                </label>
                                @if($is_time_off_limit)
                                    <div class="pl-6 mt-1">
                                        <input type="number" step="0.00001" wire:model="accrual_hours" placeholder="Hours per year" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f]">
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="is_waiting_period" class="rounded text-[#005b8f]">
                                    <span class="font-medium text-xs text-gray-700">Waiting period before requests</span>
                                </label>
                                @if($is_waiting_period)
                                    <div class="pl-6 mt-1">
                                        <input type="number" wire:model="waiting_period_days" placeholder="Days after hire (e.g. 90)" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f]">
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="is_carryover_limit" class="rounded text-[#005b8f]">
                                    <span class="font-medium text-xs text-gray-700">Limit hours carried over each year</span>
                                </label>
                                @if($is_carryover_limit)
                                    <div class="pl-6 mt-1">
                                        <input type="number" wire:model="max_carryover_hours" placeholder="Max carryover hours" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f]">
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Max Balance Cap (Hours)</label>
                                <input type="number" wire:model="max_balance_hours" placeholder="e.g. 160" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="button" wire:click="save" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-5 py-2 rounded text-sm font-semibold shadow transition">
                        {{ $modalMode === 'add' ? 'Create Policy' : 'Update Policy' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
