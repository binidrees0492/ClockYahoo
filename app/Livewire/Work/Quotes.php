<?php

namespace App\Livewire\Work;

use App\Models\Customer;
use App\Models\Assignment;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Quotes extends Component
{
    use WithPagination;

    public $search = '';
    public $status_filter = '';
    public $customer_filter = '';

    // Modal state
    public $isModalOpen = false;
    public $editingId = null;

    // Form fields
    public $quote_number = '';
    public $customer_id = '';
    public $job_id = '';
    public $title = '';
    public $status = 'draft';
    public $issue_date = '';
    public $expiration_date = '';
    public $tax_rate = 0.00;
    public $notes = '';
    public $items = [];

    // Computed totals
    public $subtotal = 0.00;
    public $tax_amount = 0.00;
    public $total_amount = 0.00;

    protected $rules = [
        'quote_number' => 'required|string|max:50',
        'customer_id'  => 'nullable|exists:customers,id',
        'job_id'       => 'nullable|exists:tbl_assignments,id',
        'title'        => 'nullable|string|max:200',
        'status'       => 'required|in:draft,sent,approved,declined,invoiced',
        'issue_date'   => 'required|date',
        'expiration_date' => 'nullable|date|after_or_equal:issue_date',
        'tax_rate'     => 'numeric|min:0|max:100',
        'items'        => 'array|min:1',
        'items.*.description' => 'required|string',
        'items.*.quantity'    => 'required|numeric|min:0.01',
        'items.*.rate'        => 'required|numeric|min:0',
    ];

    public function mount()
    {
        if (!Auth::user()->canView('quotes')) {
            abort(403, 'Unauthorized access to Quotes.');
        }
    }

    public function openModal($id = null)
    {
        if (!Auth::user()->canEdit('quotes')) {
            session()->flash('error', 'You have read-only access to Quotes.');
            return;
        }

        $this->resetValidation();
        $this->editingId = $id;

        if ($id) {
            $quote = Quote::findOrFail($id);
            $this->quote_number    = $quote->quote_number;
            $this->customer_id     = $quote->customer_id;
            $this->job_id          = $quote->job_id;
            $this->title           = $quote->title;
            $this->status          = $quote->status;
            $this->issue_date      = $quote->issue_date ? $quote->issue_date->format('Y-m-d') : '';
            $this->expiration_date = $quote->expiration_date ? $quote->expiration_date->format('Y-m-d') : '';
            $this->tax_rate        = $quote->tax_rate;
            $this->notes           = $quote->notes;
            $this->items           = $quote->items ?: [];
        } else {
            $nextNum = Quote::count() + 1001;
            $this->quote_number    = 'QT-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $this->customer_id     = '';
            $this->job_id          = '';
            $this->title           = '';
            $this->status          = 'draft';
            $this->issue_date      = now()->format('Y-m-d');
            $this->expiration_date = now()->addDays(30)->format('Y-m-d');
            $this->tax_rate        = 0.00;
            $this->notes           = 'Thank you for your business. Quote valid for 30 days.';
            $this->items           = [
                ['task_id' => '', 'description' => 'Labor / General Work', 'quantity' => 1, 'rate' => 0.00, 'amount' => 0.00]
            ];
        }

        $this->recalculateTotals();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['editingId', 'quote_number', 'customer_id', 'job_id', 'title', 'status', 'issue_date', 'expiration_date', 'tax_rate', 'notes', 'items', 'subtotal', 'tax_amount', 'total_amount']);
    }

    public function addItem()
    {
        $this->items[] = [
            'task_id'     => '',
            'description' => '',
            'quantity'    => 1,
            'rate'        => 0.00,
            'amount'      => 0.00,
        ];
        $this->recalculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }

    public function updatedItems()
    {
        $this->recalculateTotals();
    }

    public function updatedTaxRate()
    {
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $sub = 0;
        foreach ($this->items as $i => $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            $amt = round($qty * $rate, 2);
            $this->items[$i]['amount'] = $amt;
            $sub += $amt;
        }

        $this->subtotal = round($sub, 2);
        $this->tax_amount = round(($this->subtotal * ((float)$this->tax_rate / 100)), 2);
        $this->total_amount = round($this->subtotal + $this->tax_amount, 2);
    }

    public function save()
    {
        if (!Auth::user()->canEdit('quotes')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $this->validate();
        $this->recalculateTotals();

        $data = [
            'quote_number'    => $this->quote_number,
            'customer_id'     => $this->customer_id ?: null,
            'job_id'          => $this->job_id ?: null,
            'title'           => $this->title,
            'status'          => $this->status,
            'issue_date'      => $this->issue_date,
            'expiration_date' => $this->expiration_date ?: null,
            'subtotal'        => $this->subtotal,
            'tax_rate'        => (float)$this->tax_rate,
            'tax_amount'      => $this->tax_amount,
            'total_amount'    => $this->total_amount,
            'notes'           => $this->notes,
            'items'           => $this->items,
            'created_by'      => Auth::id(),
        ];

        if ($this->editingId) {
            Quote::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Quote updated successfully.');
        } else {
            Quote::create($data);
            session()->flash('message', 'Quote created successfully.');
        }

        $this->closeModal();
    }

    public function convertToInvoice($id)
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized to create invoices.');
            return;
        }

        $quote = Quote::findOrFail($id);
        $nextInvNum = Invoice::count() + 1001;

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . str_pad($nextInvNum, 4, '0', STR_PAD_LEFT),
            'customer_id'    => $quote->customer_id,
            'job_id'         => $quote->job_id,
            'quote_id'       => $quote->id,
            'title'          => $quote->title ?: 'Converted from ' . $quote->quote_number,
            'status'         => 'draft',
            'issue_date'     => now(),
            'due_date'       => now()->addDays(15),
            'subtotal'       => $quote->subtotal,
            'tax_rate'       => $quote->tax_rate,
            'tax_amount'     => $quote->tax_amount,
            'total_amount'   => $quote->total_amount,
            'amount_paid'    => 0,
            'notes'          => $quote->notes,
            'items'          => $quote->items,
            'created_by'     => Auth::id(),
        ]);

        $quote->update(['status' => 'invoiced']);
        session()->flash('message', "Quote converted to Invoice {$invoice->invoice_number}.");
    }

    public function updateStatus($id, $newStatus)
    {
        if (!Auth::user()->canEdit('quotes')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        Quote::findOrFail($id)->update(['status' => $newStatus]);
        session()->flash('message', "Quote status updated to " . ucfirst($newStatus) . ".");
    }

    public function delete($id)
    {
        if (!Auth::user()->canEdit('quotes')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $quote = Quote::findOrFail($id);

        $hasInvoices = Invoice::where('quote_id', $id)->exists();
        if ($hasInvoices) {
            $quote->update(['status' => 'declined']);
            session()->flash('message', "Quote '{$quote->quote_number}' has associated invoices and cannot be deleted. Marked as declined instead to preserve data integrity.");
            return;
        }

        $quote->delete();
        session()->flash('message', 'Quote deleted.');
    }

    public function render()
    {
        $query = Quote::with(['customer', 'job', 'creator'])
            ->when($this->search, function ($q) {
                $q->where('quote_number', 'like', "%{$this->search}%")
                  ->orWhere('title', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->customer_filter, fn($q) => $q->where('customer_id', $this->customer_filter));

        $quotes = $query->orderBy('id', 'desc')->paginate(15);

        return view('livewire.work.quotes', [
            'quotes'    => $quotes,
            'customers' => Customer::orderBy('name')->get(),
            'jobs'      => Assignment::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
