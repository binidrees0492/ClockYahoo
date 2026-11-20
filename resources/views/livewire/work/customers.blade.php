<div>
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            <div class="flex justify-between items-center">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" @click="show = false" class="opacity-60 hover:opacity-100 font-bold">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-4xl font-light text-gray-800">Customers</h1>
            @if(!auth()->user()->canEdit('customers'))
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">Read-Only Mode</span>
            @endif
        </div>
        @if(auth()->user()->canEdit('customers'))
            <button wire:click="openAdd"
                    class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold">
                Add Customer
            </button>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 bg-white p-3 rounded shadow-sm border border-gray-200">
        <div class="w-full max-w-xs">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search customers..." class="form-input w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-cs-blue focus:border-cs-blue" />
        </div>
        @if(auth()->user()?->isSuperAdmin())
            <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer select-none bg-gray-50 border border-gray-300 px-3 py-1.5 rounded">
                <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span>🗑️ Include Deleted Records</span>
            </label>
        @endif
    </div>

    @if($customers->count() === 0)
        {{-- EMPTY STATE --}}
        <div class="py-24 text-center">
            <p class="text-base text-gray-700 mb-1">Add your Customers to ClockShark</p>
            @if(auth()->user()->canEdit('customers'))
                <p class="text-sm text-gray-500">Use the Add Customer button to get started</p>
            @else
                <p class="text-sm text-gray-500">No customers found.</p>
            @endif
        </div>
    @else
        <div class="border border-gray-200 rounded bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="p-3 text-gray-600 font-semibold">Name</th>
                    <th class="p-3 text-gray-600 font-semibold">Contact</th>
                    <th class="p-3 text-gray-600 font-semibold">Email</th>
                    <th class="p-3 text-gray-600 font-semibold">Phone</th>
                    @if(auth()->user()->canEdit('customers'))
                        <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($customers as $c)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="p-3 text-cs-blue font-semibold">{{ $c->name }}</td>
                        <td class="p-3 text-gray-700">{{ $c->contact_name ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $c->email ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $c->phone ?? '—' }}</td>
                        @if(auth()->user()->canEdit('customers'))
                            <td class="p-3 text-right whitespace-nowrap">
                                @if($c->active)
                                    <button wire:click="openEdit({{ $c->id }})"
                                            class="text-cs-blue text-xs font-semibold mr-3 hover:text-blue-800">Edit</button>
                                    <button wire:click="delete({{ $c->id }})"
                                            wire:confirm="Delete this customer?"
                                            class="text-red-500 text-xs hover:text-red-700">Delete</button>
                                @else
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold bg-gray-200 text-gray-700 rounded mr-2">Inactive</span>
                                    @if(auth()->user()?->isSuperAdmin())
                                        <button wire:click="restoreCustomer({{ $c->id }})"
                                                class="text-emerald-600 text-xs font-semibold mr-3 hover:text-emerald-800">Restore</button>
                                    @endif
                                    @if(auth()->user()?->canHardDelete())
                                        <button wire:click="permanentlyDeleteCustomer({{ $c->id }})"
                                                wire:confirm="Permanently delete this customer and child records?"
                                                class="text-red-700 text-xs font-semibold hover:text-red-900">Delete Permanently</button>
                                    @endif
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90] p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-base font-bold text-gray-900">
                        {{ $editingId ? 'Edit Customer' : 'Add Customer' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    {{-- Left Column: Contact Details --}}
                    <div class="space-y-3">
                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Company / Customer Name*</label>
                            <input type="text" wire:model="name" class="w-full border rounded p-2 text-xs focus:ring-1 focus:ring-cs-blue">
                            @error('name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Primary Contact Person</label>
                            <input type="text" wire:model="contactName" class="w-full border rounded p-2 text-xs focus:ring-1 focus:ring-cs-blue">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="font-semibold text-gray-700 block mb-1">Email</label>
                                <input type="email" wire:model="email" class="w-full border rounded p-1.5 text-xs focus:ring-1 focus:ring-cs-blue">
                                @error('email') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700 block mb-1">Phone</label>
                                <input type="text" wire:model="phone" class="w-full border rounded p-1.5 text-xs focus:ring-1 focus:ring-cs-blue">
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Address & Notes --}}
                    <div class="space-y-3">
                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Billing / Site Address</label>
                            <textarea wire:model="address" rows="3" placeholder="Street, City, State, ZIP..." class="w-full border rounded p-2 text-xs focus:ring-1 focus:ring-cs-blue"></textarea>
                        </div>
                        <div>
                            <label class="font-semibold text-gray-700 block mb-1">Notes / Instructions</label>
                            <textarea wire:model="notes" rows="3" placeholder="Additional customer notes..." class="w-full border rounded p-2 text-xs focus:ring-1 focus:ring-cs-blue"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-3 border-t border-gray-100">
                    <button wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="save" class="px-5 py-2 text-xs bg-cs-blue hover:bg-blue-800 text-white font-bold rounded-lg shadow-xs transition">
                        {{ $editingId ? 'Save Changes' : 'Add Customer' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
