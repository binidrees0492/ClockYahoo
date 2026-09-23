<div class="container mx-auto px-4 py-6">
    @if (session()->has('message'))
        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-200">
            {{ session('message') }}
        </div>
    @endif

    @if(!$showForm)
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Departments - ClockVertex</h2>
            <button wire:click="create" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Add Department
            </button>
        </div>

        <div class="flex items-center mb-4 bg-white p-4 rounded shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2 w-full max-w-md">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search departments..." class="form-input w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" />
                <button wire:click="clearFilters" class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200">Clear</button>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($departments as $department)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $department->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ strtolower($department->status) === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($department->status ?? 'Active') }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $department->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">No departments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $departments->links() }}
            </div>
        </div>
    @else
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-800">
                {{ $departmentId ? 'Edit Department' : 'Add New Department' }}
            </h2>
            <button wire:click="cancel" class="text-gray-600 hover:text-gray-900 flex items-center">
                &larr; Back to List
            </button>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden max-w-3xl">
            <div class="p-6 md:p-8">
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Department Name</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-200 flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="cancel" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded shadow-sm hover:bg-blue-700">
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
