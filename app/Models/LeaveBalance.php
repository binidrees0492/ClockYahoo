<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $table = 'tbl_leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'hire_date',
        'balance_hours',
    ];

    protected $casts = [
        'hire_date'     => 'date',
        'balance_hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
