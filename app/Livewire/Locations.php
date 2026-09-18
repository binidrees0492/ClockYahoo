<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Location;

class Locations extends Component
{
    use WithPagination;

    public $search = '';
    public $showForm = false;

    public $locationId;
    public $name;
    public $status = 'Active';

    public function updatedSearch() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function create()
    {
        $this->resetValidation();
        $this->reset(['locationId', 'name']);
        $this->status = 'Active';
        $this->showForm = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $location = Location::findOrFail($id);

        $this->locationId = $location->id;
        $this->name = $location->name;
        $this->status = $location->status ?? 'Active';

        $this->showForm = true;
    }

    public function cancel()
    {
        $this->showForm = false;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:tbl_locations,name,' . $this->locationId,
            'status' => 'required|string|in:Active,Inactive',
        ]);

        Location::updateOrCreate(
            ['id' => $this->locationId],
            [
                'name' => $this->name,
                'status' => $this->status,
            ]
        );

        session()->flash('message', $this->locationId ? 'Location updated successfully.' : 'Location created successfully.');
        $this->showForm = false;
    }

    public function render()
    {
        $locations = Location::query()
            ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.locations', [
            'locations' => $locations,
        ])->layout('layouts.app'); // <--- Essential for full-page Livewire
    }
}
