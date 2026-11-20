<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Customer;

class Customers extends Component
{
    public $search = '';
    public $includeDeleted = false;

    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $contactName = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $notes = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        if (!auth()->user()->canView('customers')) {
            abort(403, 'Unauthorized access to Customers.');
        }
    }

    public function openAdd()
    {
        if (!auth()->user()->canEdit('customers')) {
            $this->toast('You only have read-only access to Customers.', 'error');
            return;
        }

        $this->reset(['editingId', 'name', 'contactName', 'email', 'phone', 'address', 'notes']);
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        if (!auth()->user()->canEdit('customers')) {
            $this->toast('You only have read-only access to Customers.', 'error');
            return;
        }

        $c = Customer::find($id);
        if (!$c) return;

        $this->editingId   = $c->id;
        $this->name        = $c->name;
        $this->contactName = $c->contact_name;
        $this->email       = $c->email;
        $this->phone       = $c->phone;
        $this->address     = $c->address;
        $this->notes       = $c->notes;
        $this->showModal   = true;
    }

    public function save()
    {
        if (!auth()->user()->canEdit('customers')) {
            abort(403, 'Unauthorized to add or edit customers.');
        }

        $this->validate([
            'name'  => 'required|string|max:150',
            'email' => 'nullable|email',
        ]);

        $data = [
            'name'         => $this->name,
            'contact_name' => $this->contactName,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'notes'        => $this->notes,
        ];

        if ($this->editingId) {
            Customer::find($this->editingId)->update($data);
            $this->toast('Customer updated.');
        } else {
            Customer::create($data);
            $this->toast('Customer added.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        if (!auth()->user()->canEdit('customers')) {
            abort(403, 'Unauthorized to delete customers.');
        }

        $customer = Customer::find($id);
        if (!$customer) return;

        $hasJobs     = \Illuminate\Support\Facades\Schema::hasColumn('tbl_assignments', 'customer_id') ? \App\Models\Assignment::where('customer_id', $id)->exists() : false;
        $hasQuotes   = class_exists(\App\Models\Quote::class) ? \App\Models\Quote::where('customer_id', $id)->exists() : false;
        $hasInvoices = class_exists(\App\Models\Invoice::class) ? \App\Models\Invoice::where('customer_id', $id)->exists() : false;

        if ($hasJobs || $hasQuotes || $hasInvoices) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('customers', $id);
                $this->toast("Customer '{$customer->name}' and all linked records permanently deleted.", 'info');
                return;
            }

            $customer->update(['active' => false]);
            $this->toast("Customer '{$customer->name}' has existing jobs, quotes, or invoices and cannot be deleted. Marked as inactive instead to preserve data integrity.", 'warning');
            return;
        }

        $customer->delete();
        $this->toast('Customer deleted.');
    }

    public function restoreCustomer($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore records.');
        }
        $customer = Customer::findOrFail($id);
        $customer->update(['active' => true]);
        $this->toast("Customer '{$customer->name}' restored successfully.", 'success');
    }

    public function permanentlyDeleteCustomer($id)
    {
        if (!auth()->user()?->canHardDelete()) {
            abort(403, 'Unauthorized to permanently delete records.');
        }
        $customer = Customer::findOrFail($id);
        $name = $customer->name;
        \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('customers', $id);
        $this->toast("Customer '{$name}' and child references permanently deleted.", 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['editingId', 'name', 'contactName', 'email', 'phone', 'address', 'notes']);
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $q = Customer::query();
        if (!$this->includeDeleted || !auth()->user()?->isSuperAdmin()) {
            $q->where('active', true);
        }
        if (trim($this->search) !== '') {
            $q->where('name', 'like', '%' . trim($this->search) . '%');
        }

        return view('livewire.work.customers', [
            'customers' => $q->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
