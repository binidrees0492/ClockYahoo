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
    public $includeDeleted = false;

    public $departmentId;
    public $name;
    public $status = 'Active';

    public function mount()
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized access to Departments.');
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

        $msg = $this->departmentId ? 'Department updated successfully.' : 'Department created successfully.';
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
        $this->showForm = false;
    }

    public function delete($id)
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $dept = Department::findOrFail($id);
        $hasEmployees = \App\Models\User::where('department_id', $id)->exists();
        $hasSchedules = \App\Models\Schedule::where('department_id', $id)->exists();

        if ($hasEmployees || $hasSchedules) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('departments', $id);
                $msg = "Department '{$dept->name}' and linked child records permanently deleted.";
                session()->flash('message', $msg);
                $this->dispatch('toast', message: $msg, type: 'info');
                return;
            }

            $dept->update(['status' => 'Inactive']);
            $msg = "Department '{$dept->name}' has assigned employees or schedules and cannot be deleted. Marked as Inactive instead to preserve database integrity.";
            session()->flash('message', $msg);
            $this->dispatch('toast', message: $msg, type: 'warning');
            return;
        }

        $dept->delete();
        session()->flash('message', 'Department deleted successfully.');
        $this->dispatch('toast', message: 'Department deleted successfully.', type: 'success');
    }

    public function restoreDepartment($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore records.');
        }
        $dept = Department::findOrFail($id);
        $dept->update(['status' => 'Active']);
        $msg = "Department '{$dept->name}' restored successfully.";
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function permanentlyDeleteDepartment($id)
    {
        if (!auth()->user()?->canHardDelete()) {
            abort(403, 'Unauthorized to permanently delete records.');
        }
        $dept = Department::findOrFail($id);
        $name = $dept->name;
        \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('departments', $id);
        $msg = "Department '{$name}' and child references permanently deleted.";
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function render()
    {
        $departments = Department::query()
            ->when(!$this->includeDeleted || !auth()->user()?->isSuperAdmin(), fn($query) => $query->where('status', 'Active'))
            ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.departments', [
            'departments' => $departments,
        ])->layout('layouts.app'); // <--- Essential for full-page Livewire
    }
}
