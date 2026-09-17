<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Str;

class Employees extends Component
{
    use WithPagination;

    public $searchCriteria = '';
    public $statusFilter = 'active';
    public $selectedEmployees = [];
    public $selectAll = false;

    // Modal Properties
    public $showModal = false;
    public $editingId = null;
    public $first_name, $last_name, $email, $employee_display_id, $role = 'Employee';
    public $is_active = true;

    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingId,
            'employee_display_id' => 'nullable|string|max:50',
            'role' => 'required|string'
        ];
    }

    public function updatedSelectAll($value)
    {
        $this->selectedEmployees = $value ? $this->getEmployeesQuery()->pluck('id')->toArray() : [];
    }

    public function bulkActivate()
    {
        User::whereIn('id', $this->selectedEmployees)->update(['is_active' => true]);
        $this->resetSelected();
    }

    public function bulkDeactivate()
    {
        User::whereIn('id', $this->selectedEmployees)->update(['is_active' => false]);
        $this->resetSelected();
    }

    public function deleteEmployee($id)
    {
        User::findOrFail($id)->delete();
    }

    public function create()
    {
        $this->reset(['first_name', 'last_name', 'email', 'employee_display_id', 'role', 'is_active', 'editingId']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $employee = User::findOrFail($id);
        $this->editingId = $id;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->employee_display_id = $employee->employee_display_id;
        $this->role = $employee->role;
        $this->is_active = $employee->is_active;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        User::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->first_name . ' ' . $this->last_name, // Fallback for standard auth
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'employee_display_id' => $this->employee_display_id,
                'role' => $this->role,
                'is_active' => $this->is_active,
                'password' => $this->editingId ? User::find($this->editingId)->password : bcrypt(Str::random(12))
            ]
        );

        $this->showModal = false;
        $this->resetSelected();
    }

    private function resetSelected()
    {
        $this->selectedEmployees = [];
        $this->selectAll = false;
    }

    private function getEmployeesQuery()
    {
        return User::query()
            ->when($this->searchCriteria, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->searchCriteria . '%')
                        ->orWhere('last_name', 'like', '%' . $this->searchCriteria . '%')
                        ->orWhere('employee_display_id', 'like', '%' . $this->searchCriteria . '%')
                        ->orWhere('email', 'like', '%' . $this->searchCriteria . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('last_name');
    }

    public function render()
    {
        return view('livewire.employees', [
            'employees' => $this->getEmployeesQuery()->paginate(15)
        ]);
    }
}
