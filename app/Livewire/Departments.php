<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Department;

class Departments extends Component
{
    use WithPagination;

    public $search = '';
    public $showForm = false;

    public $departmentId;
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
        $this->reset(['departmentId', 'name']);
        $this->status = 'Active';
        $this->showForm = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $department = Department::findOrFail($id);

        $this->departmentId = $department->id;
        $this->name = $department->name;
        $this->status = $department->status ?? 'Active';

        $this->showForm = true;
    }

    public function cancel()
    {
        $this->showForm = false;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:tbl_departments,name,' . $this->departmentId,
            'status' => 'required|string|in:Active,Inactive',
        ]);

        Department::updateOrCreate(
            ['id' => $this->departmentId],
            [
                'name' => $this->name,
                'status' => $this->status,
            ]
        );

        session()->flash('message', $this->departmentId ? 'Department updated successfully.' : 'Department created successfully.');
        $this->showForm = false;
    }

    public function render()
    {
        $departments = Department::query()
            ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.departments', [
            'departments' => $departments,
        ])->layout('layouts.app'); // <--- Essential for full-page Livewire
    }
}
