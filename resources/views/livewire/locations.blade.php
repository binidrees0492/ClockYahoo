<div class="container mx-auto px-4 py-6" style="max-width: 1200px;">

    @if (session()->has('message'))
        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-200">
            {{ session('message') }}
        </div>
    @endif

    @if(!$showForm)
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-light text-gray-800">Locations</h1>
            <button wire:click="create" class="bg-sky-700 hover:bg-sky-800 text-white px-4 py-2 rounded text-sm font-medium transition shadow-sm">
                Add Location
            </button>
        </div>

        <div class="flex items-center mb-4 bg-white p-4 rounded shadow-sm border border-gray-200">
            <div class="flex items-center space-x-2 w-full max-w-md">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search locations..." class="form-input w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-sky-700 focus:border-sky-700" />
                <button wire:click="clearFilters" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">Clear</button>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                @forelse($locations as $location)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $location->name }}</td>
                        <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ strtolower($location->status) === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($location->status) }}
                                </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $location->id }})" class="text-sky-700 hover:text-sky-900 font-medium text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded transition">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">No locations found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $locations->links() }}
            </div>
        </div>
    @else
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-light text-gray-800">
                {{ $locationId ? 'Edit Location' : 'Add New Location' }}
            </h1>
            <button wire:click="cancel" class="text-sky-700 hover:underline text-sm font-medium">&larr; Back to List</button>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 max-w-3xl p-6 md:p-8">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Name *</label>
                    <input type="text" wire:model="name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" wire:click="cancel" class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="bg-sky-700 hover:bg-sky-800 text-white px-6 py-2 rounded text-sm font-medium transition">Save Location</button>
                </div>
            </form>
        </div>
    @endif
</div>
