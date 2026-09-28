<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffRequest extends Model
{
    use HasFactory;

    protected $table = 'time_off_requests';

    protected $fillable = [
        'employee_id',
        'policy_id',
        'approver_id',
        'is_full_day',
        'daily_hours',
        'daily_breakdown',
        'start_at',
        'end_at',
        'hours',
        'paid_hours',
        'unpaid_hours',
        'reason',
        'status', // pending, approved, denied, withdrawn, cancelled
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected $casts = [
        'is_full_day'     => 'boolean',
        'daily_hours'     => 'decimal:2',
        'daily_breakdown' => 'array',
        'hours'           => 'decimal:2',
        'paid_hours'      => 'decimal:2',
        'unpaid_hours'    => 'decimal:2',
        'start_at'        => 'datetime',
        'end_at'          => 'datetime',
        'decided_at'      => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function policy()
    {
        return $this->belongsTo(TimeOffPolicy::class, 'policy_id');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function decidedByUser()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function threads()
    {
        return $this->hasMany(TimeOffRequestThread::class, 'time_off_request_id')->orderBy('created_at', 'asc');
    }
}
