<div class="space-y-4">
    {{-- Header & Stats --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Mail & Delivery Logs</h2>
                    <p class="text-xs text-gray-500">Track sent, pending, and failed outbound emails with delivery audit and resend options.</p>
                </div>
            </div>
        </div>

        {{-- Stats Badges --}}
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold inline-flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Sent: {{ $totalSent }}</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold inline-flex items-center gap-1.5">
                <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                <span>Failed: {{ $totalFailed }}</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold inline-flex items-center gap-1.5">
                <i class="fa-solid fa-clock text-amber-600"></i>
                <span>Pending: {{ $totalPending }}</span>
            </span>
        </div>
    </div>

    {{-- Toast --}}
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="p-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs {{ $toastType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
            <span>{{ $toastType === 'success' ? '✓' : '⚠️' }} {{ $toastMessage }}</span>
            <button wire:click="dismissToast" @click="show = false" class="text-gray-400 hover:text-gray-700 font-bold">&times;</button>
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="filterStatus('all')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-[#005a9c] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All Logs
            </button>
            <button wire:click="filterStatus('sent')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'sent' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Sent
            </button>
            <button wire:click="filterStatus('failed')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'failed' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Failed
            </button>
            <button wire:click="filterStatus('pending')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Pending
            </button>

            @if($isAdminOrHr && $employees->isNotEmpty())
                <div class="flex items-center gap-1.5 ml-2 bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1">
                    <span class="text-xs text-gray-500 font-medium">Employee:</span>
                    <select wire:model.live="selectedUserId" class="text-xs font-bold text-gray-800 bg-transparent focus:outline-none cursor-pointer">
                        <option value="all">👥 All Staff</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="relative w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search recipient, subject..."
                   class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:ring-1 focus:ring-sky-500">
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left text-gray-600 font-semibold">
                        <th class="p-3 w-36">Sent Date</th>
                        <th class="p-3">Recipient</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3 text-center w-24">Status</th>
                        <th class="p-3 text-center w-20">Retries</th>
                        <th class="p-3 text-right w-36">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="p-3 font-semibold text-gray-900 truncate max-w-[180px]" title="{{ $log->recipient }}">
                                {{ $log->recipient }}
                                @if($log->sender)
                                    <div class="text-[10px] text-gray-400 font-normal">From: {{ $log->sender }}</div>
                                @endif
                            </td>
                            <td class="p-3 font-medium text-gray-800">
                                <div class="truncate max-w-md">{{ $log->subject }}</div>
                                @if($log->error_message)
                                    <div class="text-[10px] text-rose-600 truncate max-w-md mt-0.5">
                                        {{ $log->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                    {{ $log->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : ($log->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    @if($log->status === 'sent')
                                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                                    @elseif($log->status === 'failed')
                                        <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                    @else
                                        <i class="fa-solid fa-clock text-[9px]"></i>
                                    @endif
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-center font-bold text-gray-600">
                                {{ $log->retry_count ?: 0 }}
                            </td>
                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                <button type="button" wire:click="viewDetails({{ $log->id }})"
                                        class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-[11px] font-semibold transition">
                                    View
                                </button>

                                @if($log->status === 'failed')
                                    <button type="button" wire:click="resend({{ $log->id }})"
                                            class="px-2.5 py-1 bg-[#005a9c] hover:bg-blue-800 text-white rounded text-[11px] font-bold transition">
                                        <i class="fa-solid fa-rotate-right"></i> Resend
                                    </button>
                                @endif

                                <button type="button" wire:click="deleteLog({{ $log->id }})"
                                        class="px-2 py-1 text-rose-500 hover:text-rose-700 text-[11px]" title="Delete log">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400 italic">
                                No email logs found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Details Modal --}}
    @if($viewingEmail)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-start border-b border-gray-200 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-snug">{{ $viewingEmail->subject }}</h3>
                        <div class="text-xs text-gray-500 mt-1 space-x-2">
                            <span>To: <strong>{{ $viewingEmail->recipient }}</strong></span>
                            <span>&bull;</span>
                            <span>{{ $viewingEmail->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                    <button wire:click="closeDetails" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-500 font-semibold">Status:</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold
                        {{ $viewingEmail->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ ucfirst($viewingEmail->status) }}
                    </span>
                    @if($viewingEmail->sender)
                        <span class="text-gray-500 ml-2">Sender: <strong>{{ $viewingEmail->sender }}</strong></span>
                    @endif
                </div>

                @if($viewingEmail->error_message)
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                        <strong>Error details:</strong> {{ $viewingEmail->error_message }}
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto bg-gray-50 p-4 rounded-lg border border-gray-200 text-xs text-gray-800 leading-relaxed whitespace-pre-wrap">
                    {!! $viewingEmail->body ?: 'No content.' !!}
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                    <div>
                        @if($viewingEmail->status === 'failed')
                            <button type="button" wire:click="resend({{ $viewingEmail->id }})"
                                    class="px-3.5 py-1.5 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-rotate-right mr-1"></i> Resend Email Now
                            </button>
                        @endif
                    </div>
                    <button type="button" wire:click="closeDetails"
                            class="px-4 py-1.5 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg text-xs font-semibold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
