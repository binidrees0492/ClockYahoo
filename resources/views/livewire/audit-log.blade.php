<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-5 border-b border-gray-200 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Audit Log</h1>
            <p class="text-xs text-gray-500 mt-1">Full system audit trail of edits, time adjustments, approvals, and configuration changes.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 font-medium">Page Size:</span>
            <select wire:model.live="pageSize" class="text-xs border-gray-300 rounded-md shadow-xs py-1 px-2 focus:ring-[#005a9c]">
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-xs mb-6 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-600 mr-1">Date Shortcuts:</span>
            <button type="button" wire:click="applyPreset('today')"
                    class="px-2.5 py-1 text-xs rounded font-medium transition {{ $rangePreset === 'today' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                Today
            </button>
            <button type="button" wire:click="applyPreset('7days')"
                    class="px-2.5 py-1 text-xs rounded font-medium transition {{ $rangePreset === '7days' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                Last 7 Days
            </button>
            <button type="button" wire:click="applyPreset('30days')"
                    class="px-2.5 py-1 text-xs rounded font-medium transition {{ $rangePreset === '30days' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                Last 30 Days
            </button>
            <button type="button" wire:click="applyPreset('thisMonth')"
                    class="px-2.5 py-1 text-xs rounded font-medium transition {{ $rangePreset === 'thisMonth' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                This Month
            </button>
            <button type="button" wire:click="applyPreset('lastMonth')"
                    class="px-2.5 py-1 text-xs rounded font-medium transition {{ $rangePreset === 'lastMonth' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                Last Month
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-3">
                <label class="block text-[11px] font-medium text-gray-600 mb-1">From Date</label>
                <input type="date" wire:model.live="startDate" class="w-full text-xs rounded-lg border-gray-300 py-1.5 px-2">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-[11px] font-medium text-gray-600 mb-1">To Date</label>
                <input type="date" wire:model.live="endDate" class="w-full text-xs rounded-lg border-gray-300 py-1.5 px-2">
            </div>
            <div class="sm:col-span-6">
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Search Audit Logs</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter by user, action, or description..."
                           class="w-full text-xs rounded-lg border-gray-300 py-1.5 pl-8 pr-3">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">🔍</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Log Table --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-600 uppercase font-bold tracking-wider text-[11px]">
                    <tr>
                        <th scope="col" wire:click="sortBy('created_at')" class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100 select-none">
                            Date / Time
                            @if($sortField === 'created_at')
                                <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th scope="col" wire:click="sortBy('user_name')" class="px-4 py-3 text-left cursor-pointer hover:bg-gray-100 select-none">
                            User
                            @if($sortField === 'user_name')
                                <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th scope="col" class="px-4 py-3 text-left">Action</th>
                        <th scope="col" class="px-4 py-3 text-left">Description</th>
                        <th scope="col" class="px-4 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-blue-50/50 transition">
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                {{ $log->created_at ? $log->created_at->format('D m/d/Y @ h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700 font-semibold">
                                {{ $log->user_name ?: ($log->user->name ?? 'System') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $log->action === 'Delete' ? 'bg-red-100 text-red-800' : ($log->action === 'Create' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-800 max-w-md truncate" title="{{ $log->description }}">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if(!empty($log->changes))
                                    <button type="button" wire:click="viewDetails({{ $log->id }})"
                                            class="px-2.5 py-1 rounded bg-[#005a9c] hover:bg-blue-800 text-white font-semibold text-[11px] shadow-xs transition">
                                        View Changes
                                    </button>
                                @else
                                    <span class="text-gray-400 text-[11px] italic">No delta</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                                <div class="text-2xl mb-1">📜</div>
                                <div class="font-bold">No Audit Log Results Found</div>
                                <div class="text-xs mt-1">Try expanding the date range or clearing your search filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Change Details Modal (Replicates Audit Log - ClockShark.html) --}}
    @if($showDetailsModal && $selectedLog)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100">
                <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Audit Log Details</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $selectedLog->created_at->format('l m/d @ h:i A') }} &bull; {{ $selectedLog->description }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeDetails" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="p-5 max-h-[70vh] overflow-y-auto">
                    <table class="w-full text-xs border border-gray-200 rounded divide-y divide-gray-200">
                        <thead class="bg-gray-100 text-gray-700 font-bold text-[11px]">
                            <tr>
                                <th class="px-3 py-2 text-left">Property</th>
                                <th class="px-3 py-2 text-left">Old Value</th>
                                <th class="px-3 py-2 text-left">New Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($selectedLog->changes ?? [] as $prop => $change)
                                @php
                                    $property = is_numeric($prop) ? ($change['property'] ?? 'Field') : $prop;
                                    $oldVal = is_array($change) ? ($change['old'] ?? ($change['old_value'] ?? '-')) : '-';
                                    $newVal = is_array($change) ? ($change['new'] ?? ($change['new_value'] ?? '-')) : $change;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-semibold text-gray-900">{{ $property }}</td>
                                    <td class="px-3 py-2 text-gray-600 break-all">{{ is_null($oldVal) || $oldVal === '' ? '—' : (is_bool($oldVal) ? ($oldVal ? 'Yes' : 'No') : $oldVal) }}</td>
                                    <td class="px-3 py-2 font-medium text-blue-900 break-all">{{ is_null($newVal) || $newVal === '' ? '—' : (is_bool($newVal) ? ($newVal ? 'Yes' : 'No') : $newVal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="button" wire:click="closeDetails"
                            class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-bold text-xs">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
