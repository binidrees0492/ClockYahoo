<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffPolicyEmployee extends Model
{
    use HasFactory;

    protected $table = 'time_off_policy_employees';

    protected $fillable = [
        'policy_id',
        'employee_id',
        'hire_date',
        'hours_remaining',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    public function policy()
    {
        return $this->belongsTo(TimeOffPolicy::class, 'policy_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
