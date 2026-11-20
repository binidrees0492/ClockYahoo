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
        'policy_type'         => 'integer',
        'accrual_method'      => 'integer',
        'is_time_off_limit'   => 'boolean',
        'accrual_hours'       => 'decimal:5',
        'is_waiting_period'   => 'boolean',
        'waiting_period_days' => 'integer',
        'is_carryover_limit'  => 'boolean',
        'max_carryover_hours' => 'integer',
        'max_balance_hours'   => 'integer',
        'active'              => 'boolean',
    ];

    public function policyEmployees()
    {
        return $this->hasMany(TimeOffPolicyEmployee::class, 'policy_id');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'time_off_policy_employees', 'policy_id', 'employee_id')
            ->withPivot(['hire_date', 'hours_remaining'])
            ->withTimestamps();
    }

    // Method version: $policy->policyTypeLabel()
    public function policyTypeLabel(): string
    {
        return match ($this->policy_type) {
            0 => 'PTO',
            1 => 'Sick',
            2 => 'Unpaid',
            default => 'Other',
        };
    }

    // Attribute accessor version: $policy->policy_type_name
    public function getPolicyTypeNameAttribute(): string
    {
        return $this->policyTypeLabel();
    }
}
