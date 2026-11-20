<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'tbl_departments';
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
