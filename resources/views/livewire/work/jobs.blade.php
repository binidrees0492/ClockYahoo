<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal text-gray-800">Jobs</h1>
        <button wire:click="openModal" class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold">
            + Add Job
        </button>
    </div>

    <div class="border border-gray-200 rounded bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="p-3 text-gray-600 font-semibold">Job Name</th>
                <th class="p-3 text-gray-600 font-semibold">Location</th>
                <th class="p-3 text-gray-600 font-semibold">Status</th>
                <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($jobs as $job)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="p-3 text-gray-800 font-medium">{{ $job->name }}</td>
                    <td class="p-3 text-gray-600">{{ $job->location ?? '—' }}</td>
                    <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $job->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($job->status) }}
                            </span>
                    </td>
                    <td class="p-3 text-right">
                        <button wire:click="edit({{ $job->id }})" class="text-cs-blue text-xs font-semibold mr-3 hover:underline">Edit</button>
                        <button wire:click="delete({{ $job->id }})" wire:confirm="Are you sure you want to delete this job?" class="text-red-500 text-xs hover:underline">Delete</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-gray-400">No jobs found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold mb-4">{{ $jobId ? 'Edit Job' : 'Add Job' }}</h3>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="text-xs text-gray-600 font-medium block mb-1">Job Name*</label>
                        <input type="text" wire:model="name" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600 font-medium block mb-1">Location</label>
                        <input type="text" wire:model="location" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                        @error('location') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600 font-medium block mb-1">Status</label>
                        <select wire:model="status" class="w-full border border-gray-300 rounded p-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 text-sm text-gray-700 rounded hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-cs-blue text-white rounded hover:bg-blue-800">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
