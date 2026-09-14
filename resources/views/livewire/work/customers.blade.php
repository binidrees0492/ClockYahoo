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

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-light text-gray-800">Customers</h1>
        <button wire:click="openAdd"
                class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold">
            Add Customer
        </button>
    </div>

    @if($customers->count() === 0)
        {{-- EMPTY STATE --}}
        <div class="py-24 text-center">
            <p class="text-base text-gray-700 mb-1">Add your Customers to ClockShark</p>
            <p class="text-sm text-gray-500">Use the Add Customer button to get started</p>
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
                    <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($customers as $c)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="p-3 text-cs-blue font-semibold">{{ $c->name }}</td>
                        <td class="p-3 text-gray-700">{{ $c->contact_name ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $c->email ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $c->phone ?? '—' }}</td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <button wire:click="openEdit({{ $c->id }})"
                                    class="text-cs-blue text-xs font-semibold mr-3 hover:text-blue-800">Edit</button>
                            <button wire:click="delete({{ $c->id }})"
                                    wire:confirm="Delete this customer?"
                                    class="text-red-500 text-xs hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">
                    {{ $editingId ? 'Edit Customer' : 'Add Customer' }}
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-600">Name*</label>
                        <input type="text" wire:model="name" class="w-full border rounded p-2 text-sm">
                        @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Contact Name</label>
                        <input type="text" wire:model="contactName" class="w-full border rounded p-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Email</label>
                            <input type="email" wire:model="email" class="w-full border rounded p-2 text-sm">
                            @error('email') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Phone</label>
                            <input type="text" wire:model="phone" class="w-full border rounded p-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Address</label>
                        <textarea wire:model="address" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button wire:click="save" class="px-4 py-2 text-sm bg-cs-blue text-white rounded">
                        {{ $editingId ? 'Save' : 'Add Customer' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
