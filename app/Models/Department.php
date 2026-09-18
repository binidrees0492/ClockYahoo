<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'tbl_departments';
    protected $guarded = [];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
}
