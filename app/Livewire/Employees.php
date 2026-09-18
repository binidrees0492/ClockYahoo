<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Location;
use App\Models\Designation;
use Illuminate\Support\Facades\Hash;

class Employees extends Component
{
    use WithPagination;

    public $search = '';
    public $department_id = '';
    public $status = 'Active';

    public $isModalOpen = false;
    public $modalMode = 'add';

    public $employeeId;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $password;
    public $display_id;
    public $designation_id;
    public $department_selection;
    public $location_selection;
    public $base_pay;

    public $departments;
    public $locations;
    public $designations;

    public function mount()
    {
        $this->loadDropdownData();
    }

    public function loadDropdownData()
    {
        $this->departments = Department::where('status', 'Active')->get();
        $this->locations = Location::where('status', 'Active')->get();
        $this->designations = Designation::where('status', 'Active')->get();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDepartmentId() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->reset(['search', 'department_id', 'status']);
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->reset(['employeeId', 'first_name', 'last_name', 'email', 'phone', 'password', 'display_id', 'designation_id', 'base_pay']);
        $this->password = 'password';
        $this->modalMode = 'add';
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $employee = Employee::findOrFail($id);

        $this->employeeId = $employee->id;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->phone = $employee->phone ?? '';
        $this->display_id = $employee->display_id ?? '';
        $this->designation_id = $employee->designation_id;
        $this->department_id = $employee->department_id ?? '';
        $this->status = $employee->status ?? 'Active';

        $this->modalMode = 'edit';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function save()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->employeeId,
        ]);

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'display_id' => $this->display_id,
            'designation_id' => $this->designation_id,
            'department_id' => $this->department_id,
            'status' => $this->status,
        ];

        if (!$this->employeeId) {
            $data['password'] = Hash::make($this->password ?: 'password');
        }

        Employee::updateOrCreate(['id' => $this->employeeId], $data);

        session()->flash('message', $this->employeeId ? 'Employee updated successfully.' : 'Employee created successfully.');
        $this->closeModal();
    }

    public function render()
    {
        $employees = Employee::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('display_id', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->department_id, fn($query) => $query->where('department_id', $this->department_id))
            ->when($this->status, fn($query) => $query->where('status', $this->status))
            ->paginate(15);

        return view('livewire.employees', [
            'employees' => $employees,
        ])->layout('layouts.app'); // Forces full-page layout binding
    }
}
