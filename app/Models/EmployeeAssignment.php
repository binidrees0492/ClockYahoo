<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAssignment extends Model
{
    protected $table = 'tbl_employee_assignments';
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
