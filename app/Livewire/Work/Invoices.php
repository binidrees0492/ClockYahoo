<?php

namespace App\Livewire\Work;

use App\Models\Customer;
use App\Models\Assignment;
use App\Models\Invoice;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public $search = '';
    public $status_filter = '';
    public $customer_filter = '';

    // Modal state
    public $isModalOpen = false;
    public $editingId = null;

    // Payment Modal
    public $isPaymentModalOpen = false;
    public $paymentInvoiceId = null;
    public $paymentAmount = 0.00;
    public $paymentNotes = '';

    // Form fields
    public $invoice_number = '';
    public $customer_id = '';
    public $job_id = '';
    public $title = '';
    public $status = 'draft';
    public $issue_date = '';
    public $due_date = '';
    public $tax_rate = 0.00;
    public $amount_paid = 0.00;
    public $notes = '';
    public $items = [];

    // Computed totals
    public $subtotal = 0.00;
    public $tax_amount = 0.00;
    public $total_amount = 0.00;

    protected $rules = [
        'invoice_number' => 'required|string|max:50',
        'customer_id'    => 'nullable|exists:customers,id',
        'job_id'         => 'nullable|exists:tbl_assignments,id',
        'title'          => 'nullable|string|max:200',
        'status'         => 'required|in:draft,sent,paid,overdue,cancelled',
        'issue_date'     => 'required|date',
        'due_date'       => 'nullable|date|after_or_equal:issue_date',
        'tax_rate'       => 'numeric|min:0|max:100',
        'amount_paid'    => 'numeric|min:0',
        'items'          => 'array|min:1',
        'items.*.description' => 'required|string',
        'items.*.quantity'    => 'required|numeric|min:0.01',
        'items.*.rate'        => 'required|numeric|min:0',
    ];

    public function mount()
    {
        if (!Auth::user()->canView('invoices')) {
            abort(403, 'Unauthorized access to Invoices.');
        }
    }

    public function openModal($id = null)
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'You have read-only access to Invoices.');
            return;
        }

        $this->resetValidation();
        $this->editingId = $id;

        if ($id) {
            $inv = Invoice::findOrFail($id);
            $this->invoice_number = $inv->invoice_number;
            $this->customer_id    = $inv->customer_id;
            $this->job_id         = $inv->job_id;
            $this->title          = $inv->title;
            $this->status         = $inv->status;
            $this->issue_date     = $inv->issue_date ? $inv->issue_date->format('Y-m-d') : '';
            $this->due_date       = $inv->due_date ? $inv->due_date->format('Y-m-d') : '';
            $this->tax_rate       = $inv->tax_rate;
            $this->amount_paid    = $inv->amount_paid;
            $this->notes          = $inv->notes;
            $this->items          = $inv->items ?: [];
        } else {
            $nextNum = Invoice::count() + 1001;
            $this->invoice_number = 'INV-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $this->customer_id    = '';
            $this->job_id         = '';
            $this->title          = '';
            $this->status         = 'draft';
            $this->issue_date     = now()->format('Y-m-d');
            $this->due_date       = now()->addDays(15)->format('Y-m-d');
            $this->tax_rate       = 0.00;
            $this->amount_paid    = 0.00;
            $this->notes          = 'Payment due within 15 days of invoice date. Thank you!';
            $this->items          = [
                ['task_id' => '', 'description' => 'Completed Services', 'quantity' => 1, 'rate' => 0.00, 'amount' => 0.00]
            ];
        }

        $this->recalculateTotals();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['editingId', 'invoice_number', 'customer_id', 'job_id', 'title', 'status', 'issue_date', 'due_date', 'tax_rate', 'amount_paid', 'notes', 'items', 'subtotal', 'tax_amount', 'total_amount']);
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
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $this->validate();
        $this->recalculateTotals();

        $data = [
            'invoice_number' => $this->invoice_number,
            'customer_id'    => $this->customer_id ?: null,
            'job_id'         => $this->job_id ?: null,
            'title'          => $this->title,
            'status'         => $this->status,
            'issue_date'     => $this->issue_date,
            'due_date'       => $this->due_date ?: null,
            'subtotal'       => $this->subtotal,
            'tax_rate'       => (float)$this->tax_rate,
            'tax_amount'     => $this->tax_amount,
            'total_amount'   => $this->total_amount,
            'amount_paid'    => (float)$this->amount_paid,
            'notes'          => $this->notes,
            'items'          => $this->items,
            'created_by'     => Auth::id(),
        ];

        if ($this->editingId) {
            Invoice::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Invoice updated successfully.');
        } else {
            Invoice::create($data);
            session()->flash('message', 'Invoice created successfully.');
        }

        $this->closeModal();
    }

    public function openPaymentModal($id)
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $inv = Invoice::findOrFail($id);
        $this->paymentInvoiceId = $id;
        $this->paymentAmount    = $inv->balance_due;
        $this->paymentNotes     = 'Payment received via Bank / Check';
        $this->isPaymentModalOpen = true;
    }

    public function closePaymentModal()
    {
        $this->isPaymentModalOpen = false;
        $this->reset(['paymentInvoiceId', 'paymentAmount', 'paymentNotes']);
    }

    public function recordPayment()
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
        ]);

        $inv = Invoice::findOrFail($this->paymentInvoiceId);
        $newPaid = (float)$inv->amount_paid + (float)$this->paymentAmount;
        $newStatus = ($newPaid >= (float)$inv->total_amount) ? 'paid' : $inv->status;

        $inv->update([
            'amount_paid' => round($newPaid, 2),
            'status'      => $newStatus,
        ]);

        session()->flash('message', "Payment of \${$this->paymentAmount} recorded for Invoice {$inv->invoice_number}.");
        $this->closePaymentModal();
    }

    public function updateStatus($id, $newStatus)
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $inv = Invoice::findOrFail($id);
        if ($newStatus === 'paid') {
            $inv->update(['status' => 'paid', 'amount_paid' => $inv->total_amount]);
        } else {
            $inv->update(['status' => $newStatus]);
        }

        session()->flash('message', "Invoice status updated to " . ucfirst($newStatus) . ".");
    }

    public function delete($id)
    {
        if (!Auth::user()->canEdit('invoices')) {
            session()->flash('error', 'Unauthorized: Read-only access.');
            return;
        }

        $invoice = Invoice::findOrFail($id);

        if ((float)$invoice->amount_paid > 0 || in_array($invoice->status, ['paid', 'partially_paid'])) {
            $invoice->update(['status' => 'cancelled']);
            session()->flash('message', "Invoice '{$invoice->invoice_number}' has recorded payments and cannot be deleted. Marked as cancelled instead to preserve audit integrity.");
            return;
        }

        $invoice->delete();
        session()->flash('message', 'Invoice deleted.');
    }

    public function render()
    {
        $query = Invoice::with(['customer', 'job', 'creator'])
            ->when($this->search, function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhere('title', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->customer_filter, fn($q) => $q->where('customer_id', $this->customer_filter));

        $invoices = $query->orderBy('id', 'desc')->paginate(15);

        // Calculate summary metrics
        $all = Invoice::all();
        $totalInvoiced = $all->sum('total_amount');
        $totalCollected = $all->sum('amount_paid');
        $totalOutstanding = max(0, $totalInvoiced - $totalCollected);

        return view('livewire.work.invoices', [
            'invoices'         => $invoices,
            'customers'        => Customer::orderBy('name')->get(),
            'jobs'             => Assignment::orderBy('name')->get(),
            'totalInvoiced'    => $totalInvoiced,
            'totalCollected'   => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
        ])->layout('layouts.app');
    }
}
