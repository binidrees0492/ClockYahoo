<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Customer;

class Customers extends Component
{
    public $search = '';

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

    public function openAdd()
    {
        $this->reset(['editingId', 'name', 'contactName', 'email', 'phone', 'address', 'notes']);
        $this->showModal = true;
    }

    public function openEdit($id)
    {
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
        Customer::find($id)?->delete();
        $this->toast('Customer deleted.');
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
        if (trim($this->search) !== '') {
            $q->where('name', 'like', '%' . trim($this->search) . '%');
        }

        return view('livewire.work.customers', [
            'customers' => $q->orderBy('name')->get(),
        ]);
    }
}
