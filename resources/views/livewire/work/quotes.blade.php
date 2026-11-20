<div class="max-w-7xl mx-auto p-4 sm:p-6" wire:poll.10s>
    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-light text-gray-800">Quotes</h1>
            <p class="text-xs text-gray-500 mt-1">Estimate work, send quotes to customers, and convert accepted quotes into invoices.</p>
        </div>
        @if(auth()->user()->canEdit('quotes'))
            <button wire:click="openModal" class="bg-[#005a9c] hover:bg-[#003d6b] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition flex items-center gap-1.5">
                <span class="text-base leading-none">+</span> New Quote
            </button>
        @else
            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded font-medium border border-gray-200">
                Read-Only Access
            </span>
        @endif
    </div>

    @if (session()->has('message'))
        <div x-data x-init="window.showToast(@js(session('message')), 'success')" class="hidden"></div>
    @endif

    @if (session()->has('error'))
        <div x-data x-init="window.showToast(@js(session('error')), 'danger')" class="hidden"></div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded p-4 mb-4 shadow-sm flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-3 items-center flex-1 max-w-2xl">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search quotes, customer, #" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] w-64 bg-white">

            <select wire:model.live="status_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] bg-white">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
                <option value="invoiced">Invoiced</option>
            </select>

            <select wire:model.live="customer_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] bg-white">
                <option value="">All Customers</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="$set('search', ''); $set('status_filter', ''); $set('customer_filter', '');" class="text-xs text-[#005a9c] hover:underline font-medium">
            Clear Filters
        </button>
    </div>

    {{-- Quotes Table --}}
    <div class="bg-white border border-gray-200 rounded shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                    <tr>
                        <th class="p-3">Quote #</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Job</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($quotes as $q)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 font-semibold text-[#005a9c]">
                                {{ $q->quote_number }}
                                @if($q->title)
                                    <div class="text-[11px] text-gray-400 font-normal">{{ $q->title }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-gray-800">
                                {{ $q->customer ? $q->customer->name : '—' }}
                            </td>
                            <td class="p-3 text-xs text-gray-600">
                                {{ $q->job ? $q->job->name : '—' }}
                            </td>
                            <td class="p-3 text-xs text-gray-600 whitespace-nowrap">
                                <div>{{ $q->issue_date ? $q->issue_date->format('M d, Y') : '—' }}</div>
                                @if($q->expiration_date)
                                    <div class="text-[10px] text-gray-400">Exp: {{ $q->expiration_date->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-right font-bold text-gray-800 whitespace-nowrap">
                                ${{ number_format($q->total_amount, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                @php
                                    $badgeClasses = match($q->status) {
                                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                                        'sent'     => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'invoiced' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'declined' => 'bg-red-100 text-red-800 border-red-200',
                                        default    => 'bg-gray-100 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $badgeClasses }}">
                                    {{ ucfirst($q->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right space-x-2 whitespace-nowrap text-xs">
                                @if(auth()->user()->canEdit('quotes'))
                                    <button wire:click="openModal({{ $q->id }})" class="text-[#005a9c] hover:underline font-semibold">Edit</button>

                                    @if($q->status === 'draft')
                                        <button wire:click="updateStatus({{ $q->id }}, 'sent')" class="text-blue-600 hover:underline">Mark Sent</button>
                                    @elseif($q->status === 'sent')
                                        <button wire:click="updateStatus({{ $q->id }}, 'approved')" class="text-green-600 hover:underline font-semibold">Approve</button>
                                        <button wire:click="updateStatus({{ $q->id }}, 'declined')" class="text-red-500 hover:underline">Decline</button>
                                    @endif

                                    @if($q->status === 'approved')
                                        <button wire:click="convertToInvoice({{ $q->id }})" class="text-purple-600 hover:underline font-semibold">+ Invoice</button>
                                    @endif

                                    <button wire:click="delete({{ $q->id }})" wire:confirm="Delete quote {{ $q->quote_number }}?" class="text-gray-400 hover:text-red-600">&times;</button>
                                @else
                                    <span class="text-gray-400 italic">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400">
                                <em>No quotes found. Create a new quote to get started.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">
            {{ $quotes->links() }}
        </div>
    </div>

    {{-- Add / Edit Quote Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-[#005a9c] text-white">
                    <h2 class="text-base font-bold">
                        {{ $editingId ? 'Edit Quote: ' . $quote_number : 'New Quote' }}
                    </h2>
                    <button wire:click="closeModal" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 text-sm flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Quote Number *</label>
                            <input type="text" wire:model="quote_number" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                            @error('quote_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Customer *</label>
                            <select wire:model="customer_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] bg-white">
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Associated Job</label>
                            <select wire:model="job_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] bg-white">
                                <option value="">Select Job (Optional)</option>
                                @foreach($jobs as $j)
                                    <option value="{{ $j->id }}">{{ $j->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Title / Summary</label>
                            <input type="text" wire:model="title" placeholder="e.g. Backyard Renovation & Landscaping" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Issue Date *</label>
                            <input type="date" wire:model="issue_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Expiration Date</label>
                            <input type="date" wire:model="expiration_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>
                    </div>

                    {{-- Line Items Table --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Line Items</label>
                            <button type="button" wire:click="addItem" class="text-xs text-[#005a9c] hover:underline font-semibold">+ Add Item</button>
                        </div>

                        <div class="border border-gray-200 rounded overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-100 border-b border-gray-200 text-gray-600 font-semibold">
                                    <tr>
                                        <th class="p-2 text-left">Description</th>
                                        <th class="p-2 text-right w-24">Qty / Hrs</th>
                                        <th class="p-2 text-right w-28">Rate ($)</th>
                                        <th class="p-2 text-right w-28">Amount ($)</th>
                                        <th class="p-2 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($items as $idx => $item)
                                        <tr>
                                            <td class="p-2">
                                                <input type="text" wire:model.live.debounce.300ms="items.{{ $idx }}.description" placeholder="Item description" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005a9c]">
                                            </td>
                                            <td class="p-2">
                                                <input type="number" step="0.25" min="0" wire:model.live="items.{{ $idx }}.quantity" class="w-full border border-gray-300 rounded p-1.5 text-xs text-right outline-none focus:border-[#005a9c]">
                                            </td>
                                            <td class="p-2">
                                                <input type="number" step="0.01" min="0" wire:model.live="items.{{ $idx }}.rate" class="w-full border border-gray-300 rounded p-1.5 text-xs text-right outline-none focus:border-[#005a9c]">
                                            </td>
                                            <td class="p-2 text-right font-semibold text-gray-700">
                                                ${{ number_format($item['amount'] ?? 0, 2) }}
                                            </td>
                                            <td class="p-2 text-center">
                                                @if(count($items) > 1)
                                                    <button type="button" wire:click="removeItem({{ $idx }})" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Totals and Tax --}}
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pt-2">
                        <div class="w-full sm:w-1/2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Notes / Terms</label>
                            <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005a9c]" placeholder="Customer-facing notes or payment conditions..."></textarea>
                        </div>

                        <div class="w-full sm:w-72 bg-gray-50 border border-gray-200 rounded p-3 text-xs space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal:</span>
                                <span class="font-semibold text-gray-800">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600">
                                <span class="flex items-center gap-1">
                                    Tax (%):
                                    <input type="number" step="0.25" min="0" max="100" wire:model.live="tax_rate" class="w-16 border border-gray-300 rounded px-1.5 py-0.5 text-xs text-right outline-none focus:border-[#005a9c]">
                                </span>
                                <span class="font-semibold text-gray-800">${{ number_format($tax_amount, 2) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-2 flex justify-between text-sm font-bold text-gray-900">
                                <span>Total:</span>
                                <span class="text-[#005a9c]">${{ number_format($total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                    <select wire:model="status" class="border border-gray-300 rounded px-2.5 py-1 text-xs outline-none focus:border-[#005a9c] bg-white">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="invoiced">Invoiced</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100">Cancel</button>
                        <button type="button" wire:click="save" class="bg-[#005a9c] hover:bg-[#003d6b] text-white px-5 py-2 rounded text-xs font-semibold shadow transition">Save Quote</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
