<div class="bg-white shadow-sm sm:rounded-lg p-6">
    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('groupType', 'departments')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $groupType === 'departments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Departments
            </button>
            <button wire:click="$set('groupType', 'locations')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $groupType === 'locations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Locations
            </button>
        </nav>
    </div>

    <!-- Header & Search -->
    <div class="flex justify-between items-center mb-6">
        <div class="w-1/3">
            <input type="text" wire:model.live.debounce.300ms="searchCriteria" placeholder="Search {{ ucfirst($groupType) }}..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex space-x-2">
            <button wire:click="bulkDelete" wire:confirm="Are you sure you want to delete the selected items?" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50" @if(empty($selectedGroups)) disabled @endif>
                Delete Selected
            </button>
            <button wire:click="create" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                + Add {{ $groupType === 'departments' ? 'Department' : 'Location' }}
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left w-12"><input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"></th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            @forelse($groups as $group)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap"><input type="checkbox" wire:model.live="selectedGroups" value="{{ $group->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $group->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="edit({{ $group->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <button wire:click="deleteGroup({{ $group->id }})" wire:confirm="Are you sure you want to delete this {{ rtrim($groupType, 's') }}?" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No {{ $groupType }} found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $groups->links() }}</div>

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-1/3">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-medium">{{ $editingId ? 'Edit' : 'Add' }} {{ $groupType === 'departments' ? 'Department' : 'Location' }}</h3>
                </div>
                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3 rounded-b-lg">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                    <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
