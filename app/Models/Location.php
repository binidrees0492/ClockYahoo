<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'tbl_locations';
    protected $guarded = [];

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class, 'location_id');
    }
}
