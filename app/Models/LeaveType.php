<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'tbl_leave_types';

    protected $fillable = [
        'name',
        'type',
        'accrual_rate',
        'max_hours',
        'status',
    ];

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }
}
