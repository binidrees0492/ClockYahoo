<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffPolicy extends Model
{
    use HasFactory;

    protected $table = 'time_off_policies';

    protected $fillable = [
        'name',
        'policy_type',
        'accrual_method',
        'is_time_off_limit',
        'accrual_hours',
        'is_waiting_period',
        'waiting_period_days',
        'is_carryover_limit',
        'max_carryover_hours',
        'max_balance_hours',
        'active',
    ];

    protected $casts = [
        'is_time_off_limit'   => 'boolean',
        'is_waiting_period'   => 'boolean',
        'is_carryover_limit'  => 'boolean',
        'active'              => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(TimeOffPolicyEmployee::class, 'policy_id');
    }

    public function requests()
    {
        return $this->hasMany(TimeOffRequest::class, 'policy_id');
    }

    public function policyTypeLabel(): string
    {
        return match ((int) $this->policy_type) {
            0 => 'PTO',
            1 => 'Sick',
            2 => 'Unpaid',
            default => 'Unknown',
        };
    }
}
