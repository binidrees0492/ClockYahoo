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
    public $includeDeleted = false;

    public $locationId;
    public $name;
    public $status = 'Active';

    public function mount()
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized access to Locations.');
        }
    }

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

        $msg = $this->locationId ? 'Location updated successfully.' : 'Location created successfully.';
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
        $this->showForm = false;
    }

        public function delete($id)
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $loc = Location::findOrFail($id);
        $hasUsers = \App\Models\User::where('location_id', $id)->exists();
        $hasTasks = \Illuminate\Support\Facades\Schema::hasColumn('tbl_tasks', 'location_id')
            ? \App\Models\Task::where('location_id', $id)->exists()
            : false;
        $hasSchedules = \App\Models\Schedule::where('location_name', $loc->name)->exists();

        if ($hasUsers || $hasTasks || $hasSchedules) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('locations', $id);
                $msg = "Location '{$loc->name}' and linked child references permanently deleted.";
                session()->flash('message', $msg);
                $this->dispatch('toast', message: $msg, type: 'info');
                return;
            }

            $loc->update(['status' => 'Inactive']);
            $msg = "Location '{$loc->name}' has assigned employees, tasks, or schedules and cannot be deleted. Marked as Inactive instead to preserve database integrity.";
            session()->flash('message', $msg);
            $this->dispatch('toast', message: $msg, type: 'warning');
            return;
        }

        $loc->delete();
        session()->flash('message', 'Location deleted successfully.');
        $this->dispatch('toast', message: 'Location deleted successfully.', type: 'success');
    }

    public function restoreLocation($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore records.');
        }
        $loc = Location::findOrFail($id);
        $loc->update(['status' => 'Active']);
        $msg = "Location '{$loc->name}' restored successfully.";
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function permanentlyDeleteLocation($id)
    {
        if (!auth()->user()?->canHardDelete()) {
            abort(403, 'Unauthorized to permanently delete records.');
        }
        $loc = Location::findOrFail($id);
        $name = $loc->name;
        \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('locations', $id);
        $msg = "Location '{$name}' and child references permanently deleted.";
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function render()
    {
        $locations = Location::query()
            ->when(!$this->includeDeleted || !auth()->user()?->isSuperAdmin(), fn($query) => $query->where('status', 'Active'))
            ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.locations', [
            'locations' => $locations,
        ])->layout('layouts.app'); // <--- Essential for full-page Livewire
    }
}
