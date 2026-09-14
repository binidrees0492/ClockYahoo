<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_time_logging';

    protected $fillable = [
        'employee_id',
        'assignment_id',
        'task_id',
        'clock_in',
        'clock_out',
        'break_started_at',
        'break_minutes',
        'duration_minutes',
        'notes',
        'status',
        'approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'clock_in'         => 'datetime',
        'clock_out'        => 'datetime',
        'break_started_at' => 'datetime',
        'approved'         => 'boolean',
        'approved_at'      => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function logNotes()
    {
        return $this->hasMany(TimeLogNote::class, 'time_log_id')->latest();
    }

    public function logAttachments()
    {
        return $this->hasMany(TimeLogAttachment::class, 'time_log_id')->latest();
    }

    public function isOnBreak(): bool
    {
        return !is_null($this->break_started_at) && is_null($this->clock_out);
    }

    public function isActive(): bool
    {
        return is_null($this->clock_out);
    }

    public function isManual(): bool
    {
        return $this->status === 'manual';
    }

    public function grossSeconds(): int
    {
        $end = $this->clock_out ?? Carbon::now();
        return $this->clock_in->diffInSeconds($end);
    }

    public function currentBreakSeconds(): int
    {
        if (!$this->isOnBreak()) {
            return 0;
        }
        return $this->break_started_at->diffInSeconds(Carbon::now());
    }

    public function netSeconds(): int
    {
        $gross  = $this->grossSeconds();
        $breaks = ($this->break_minutes * 60) + $this->currentBreakSeconds();
        return max(0, $gross - $breaks);
    }

    public function switchTo(int $assignmentId, int $taskId): self
    {
        if ($this->isOnBreak()) {
            $this->break_minutes  += (int) ceil($this->break_started_at->diffInMinutes(Carbon::now()));
            $this->break_started_at = null;
        }

        $now = Carbon::now();
        $this->clock_out        = $now;
        $this->duration_minutes = (int) ceil($this->netSeconds() / 60);
        $this->status           = 'switched';
        $this->save();

        return self::create([
            'employee_id'   => $this->employee_id,
            'assignment_id' => $assignmentId,
            'task_id'       => $taskId,
            'clock_in'      => $now,
            'status'        => 'active',
        ]);
    }
}
