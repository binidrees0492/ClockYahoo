<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $table = 'tbl_designations';
    protected $guarded = [];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }
}
