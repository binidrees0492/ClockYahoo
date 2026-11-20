<div class="max-w-7xl mx-auto p-4 sm:p-6" wire:poll.10s>
    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-light text-gray-800">Invoices</h1>
            <p class="text-xs text-gray-500 mt-1">Bill clients, track collections, record payments, and manage balances.</p>
        </div>
        @if(auth()->user()->canEdit('invoices'))
            <button wire:click="openModal" class="bg-[#005a9c] hover:bg-[#003d6b] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition flex items-center gap-1.5">
                <span class="text-base leading-none">+</span> New Invoice
            </button>
        @else
            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded font-medium border border-gray-200">
                Read-Only Access
            </span>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded shadow-sm flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded shadow-sm flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">&times;</button>
        </div>
    @endif

    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded p-4 shadow-xs">
            <span class="text-xs uppercase tracking-wider text-gray-500 font-semibold block">Total Invoiced</span>
            <div class="text-2xl font-bold text-gray-800 mt-1">${{ number_format($totalInvoiced, 2) }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded p-4 shadow-xs">
            <span class="text-xs uppercase tracking-wider text-gray-500 font-semibold block">Total Collected</span>
            <div class="text-2xl font-bold text-green-700 mt-1">${{ number_format($totalCollected, 2) }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded p-4 shadow-xs">
            <span class="text-xs uppercase tracking-wider text-gray-500 font-semibold block">Outstanding Balance</span>
            <div class="text-2xl font-bold text-amber-600 mt-1">${{ number_format($totalOutstanding, 2) }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded p-4 mb-4 shadow-sm flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-3 items-center flex-1 max-w-2xl">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoices, customer, #" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] w-64 bg-white">

            <select wire:model.live="status_filter" class="border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c] bg-white">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
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

    {{-- Invoices Table --}}
    <div class="bg-white border border-gray-200 rounded shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                    <tr>
                        <th class="p-3">Invoice #</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Job</th>
                        <th class="p-3">Due Date</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-right">Balance Due</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 font-semibold text-[#005a9c]">
                                {{ $inv->invoice_number }}
                                @if($inv->title)
                                    <div class="text-[11px] text-gray-400 font-normal">{{ $inv->title }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-gray-800">
                                {{ $inv->customer ? $inv->customer->name : '—' }}
                            </td>
                            <td class="p-3 text-xs text-gray-600">
                                {{ $inv->job ? $inv->job->name : '—' }}
                            </td>
                            <td class="p-3 text-xs text-gray-600 whitespace-nowrap">
                                <div>{{ $inv->due_date ? $inv->due_date->format('M d, Y') : '—' }}</div>
                                <div class="text-[10px] text-gray-400">Issued: {{ $inv->issue_date ? $inv->issue_date->format('M d, Y') : '—' }}</div>
                            </td>
                            <td class="p-3 text-right font-bold text-gray-800 whitespace-nowrap">
                                ${{ number_format($inv->total_amount, 2) }}
                            </td>
                            <td class="p-3 text-right font-semibold whitespace-nowrap {{ $inv->balance_due > 0 ? 'text-amber-700' : 'text-green-700' }}">
                                ${{ number_format($inv->balance_due, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                @php
                                    $badgeClasses = match($inv->status) {
                                        'paid'      => 'bg-green-100 text-green-800 border-green-200',
                                        'sent'      => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'overdue'   => 'bg-red-100 text-red-800 border-red-200',
                                        'cancelled' => 'bg-gray-100 text-gray-700 border-gray-200',
                                        default     => 'bg-gray-100 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $badgeClasses }}">
                                    {{ ucfirst($inv->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right space-x-2 whitespace-nowrap text-xs">
                                @if(auth()->user()->canEdit('invoices'))
                                    <button wire:click="openModal({{ $inv->id }})" class="text-[#005a9c] hover:underline font-semibold">Edit</button>

                                    @if($inv->balance_due > 0)
                                        <button wire:click="openPaymentModal({{ $inv->id }})" class="text-green-600 hover:underline font-semibold">+ Payment</button>
                                    @endif

                                    @if($inv->status === 'draft')
                                        <button wire:click="updateStatus({{ $inv->id }}, 'sent')" class="text-blue-600 hover:underline">Mark Sent</button>
                                    @endif

                                    <button wire:click="delete({{ $inv->id }})" wire:confirm="Delete invoice {{ $inv->invoice_number }}?" class="text-gray-400 hover:text-red-600">&times;</button>
                                @else
                                    <span class="text-gray-400 italic">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">
                                <em>No invoices found. Create a new invoice to start billing.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">
            {{ $invoices->links() }}
        </div>
    </div>

    {{-- Add / Edit Invoice Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-[#005a9c] text-white">
                    <h2 class="text-base font-bold">
                        {{ $editingId ? 'Edit Invoice: ' . $invoice_number : 'New Invoice' }}
                    </h2>
                    <button wire:click="closeModal" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 text-sm flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Invoice Number *</label>
                            <input type="text" wire:model="invoice_number" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                            @error('invoice_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                            <input type="text" wire:model="title" placeholder="e.g. Electrical wiring phase 1" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Issue Date *</label>
                            <input type="date" wire:model="issue_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Due Date</label>
                            <input type="date" wire:model="due_date" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        </div>
                    </div>

                    {{-- Line Items Table --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Invoice Items</label>
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
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Notes / Payment Terms</label>
                            <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005a9c]" placeholder="Remittance instructions, bank details, or terms..."></textarea>
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
                                <span>Total Amount:</span>
                                <span class="text-[#005a9c]">${{ number_format($total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 pt-1">
                                <span>Amount Paid:</span>
                                <input type="number" step="0.01" min="0" wire:model.live="amount_paid" class="w-24 border border-gray-300 rounded px-1.5 py-0.5 text-xs text-right outline-none focus:border-[#005a9c]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                    <select wire:model="status" class="border border-gray-300 rounded px-2.5 py-1 text-xs outline-none focus:border-[#005a9c] bg-white">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-100">Cancel</button>
                        <button type="button" wire:click="save" class="bg-[#005a9c] hover:bg-[#003d6b] text-white px-5 py-2 rounded text-xs font-semibold shadow transition">Save Invoice</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Record Payment Modal --}}
    @if($isPaymentModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-6">
                <h3 class="text-base font-bold text-gray-800 mb-4">Record Payment</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Payment Amount ($) *</label>
                        <input type="number" step="0.01" min="0.01" wire:model="paymentAmount" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]">
                        @error('paymentAmount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Payment Method / Notes</label>
                        <input type="text" wire:model="paymentNotes" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005a9c]" placeholder="Check #, wire transfer, cash, etc.">
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closePaymentModal" class="px-4 py-2 text-xs border border-gray-300 rounded text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button wire:click="recordPayment" class="px-4 py-2 text-xs bg-green-600 hover:bg-green-700 text-white rounded font-semibold shadow">Confirm Payment</button>
                </div>
            </div>
        </div>
    @endif
</div>
