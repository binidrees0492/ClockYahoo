<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTimeOffPolicy extends Model
{
    use HasFactory;

    protected $table = 'employee_time_off_policies';

    protected $fillable = [
        'user_id',
        'policy_name',
        'hire_date',
        'balance_hours',
    ];

    protected $casts = [
        'hire_date'     => 'date',
        'balance_hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
