<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';
    protected $guarded = [];

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class, 'employee_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
    }

    public function history()
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id')->orderBy('created_at', 'desc');
    }
}
