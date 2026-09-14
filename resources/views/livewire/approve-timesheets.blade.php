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

    {{-- FILTERS --}}
    <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select wire:model.live="filter"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="all">All</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="fromDate"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="toDate"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Job</label>
                <select wire:model.live="assignmentFilter"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="">All Jobs</option>
                    @foreach($assignments as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Employee</label>
                <select wire:model.live="employeeFilter"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Job / Task / Employee"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
            </div>
        </div>

        <div class="flex justify-between items-center mt-3">
            <button wire:click="clearFilters"
                    class="text-xs text-gray-500 hover:text-gray-800 underline">
                Reset filters
            </button>

            <div class="flex gap-2">
                <button wire:click="approveSelected"
                        wire:loading.attr="disabled"
                        @if(empty($selected)) disabled @endif
                        class="px-4 py-2 rounded text-sm font-semibold transition
                        {{ empty($selected)
                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                            : 'bg-cs-blue hover:bg-blue-800 text-white' }}">
                    Approve Selected
                    @if(!empty($selected))
                        <span class="ml-1 text-xs">({{ count($selected) }})</span>
                    @endif
                </button>

                <button wire:click="unapproveSelected"
                        wire:loading.attr="disabled"
                        @if(empty($selected)) disabled @endif
                        class="px-4 py-2 rounded text-sm font-semibold transition
                        {{ empty($selected)
                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                            : 'bg-white border border-gray-300 hover:bg-gray-100 text-gray-800' }}">
                    Unapprove Selected
                </button>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="border border-gray-200 rounded bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="p-3 w-8">
                    <input type="checkbox" wire:model.live="selectAll">
                </th>
                <th class="p-3 text-gray-600 font-semibold">Employee</th>
                <th class="p-3 text-gray-600 font-semibold">Job / Task</th>
                <th class="p-3 text-gray-600 font-semibold">Clock In</th>
                <th class="p-3 text-gray-600 font-semibold">Clock Out</th>
                <th class="p-3 text-gray-600 font-semibold">Hours</th>
                <th class="p-3 text-gray-600 font-semibold">Status</th>
                <th class="p-3 text-gray-600 font-semibold">Approved By</th>
                <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $mins = $log->duration_minutes ?: (int) ceil($log->netSeconds() / 60);
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                    wire:click="openDetail({{ $log->id }})"
                    wire:key="log-{{ $log->id }}">

                    <td class="p-3" wire:click.stop>
                        <input type="checkbox"
                               wire:model.live="selected"
                               value="{{ $log->id }}">
                    </td>

                    <td class="p-3 text-gray-800">{{ $log->employee->name ?? '—' }}</td>

                    <td class="p-3 {{ $log->isManual() ? 'text-blue-600 font-semibold' : 'text-cs-blue' }}"
                        title="{{ $log->isManual() ? 'Manual entry' : '' }}">
                        {{ $log->assignment->name ?? '—' }} / {{ $log->task->name ?? '—' }}
                        @if($log->isManual())
                            <div class="text-[10px] text-blue-500 font-normal">Manual</div>
                        @endif
                    </td>

                    <td class="p-3 text-gray-700">{{ $log->clock_in?->format('M j, g:i A') }}</td>
                    <td class="p-3 text-gray-700">{{ $log->clock_out?->format('M j, g:i A') ?? '—' }}</td>
                    <td class="p-3 text-gray-800 font-semibold">{{ number_format($mins / 60, 2) }}</td>

                    <td class="p-3">
                        @if($log->approved)
                            <span class="text-green-700 text-xs font-semibold">Approved</span>
                        @else
                            <span class="text-orange-600 text-xs font-semibold">Pending</span>
                        @endif
                    </td>

                    <td class="p-3 text-gray-700 text-xs">{{ $log->approver->name ?? '—' }}</td>

                    <td class="p-3 text-right" wire:click.stop>
                        @if($log->approved)
                            <button wire:click="unapproveOne({{ $log->id }})"
                                    class="text-gray-500 hover:text-gray-800 text-xs">
                                Unapprove
                            </button>
                        @else
                            <button wire:click="approveOne({{ $log->id }})"
                                    class="text-cs-blue hover:text-blue-800 text-xs font-semibold">
                                Approve
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="p-8 text-center text-gray-400">
                        No entries match the current filters.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500 mt-2">
        Click any row to open details, notes, and attachments.
    </p>
</div>
