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
        'shift_id',
        'parent_log_id',
        'assignment_id',
        'task_id',
        'timezone',
        'clock_in',
        'clock_out',
        'break_started_at',
        'break_minutes',
        'duration_minutes',
        'hourly_rate',
        'breaks',
        'notes',
        'status',
        'approved',
        'approved_by',
        'approved_at',
        'is_manual',
        'is_unusual',
        'unusual_reason',
        'location_flagged',
        'location_flag_reason',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'action_requested',
        'action_requested_notes',
        'is_archived',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'clock_in'               => 'datetime',
        'clock_out'              => 'datetime',
        'break_started_at'       => 'datetime',
        'hourly_rate'            => 'decimal:2',
        'breaks'                 => 'array',
        'approved'               => 'boolean',
        'approved_at'            => 'datetime',
        'is_manual'              => 'boolean',
        'is_unusual'             => 'boolean',
        'location_flagged'       => 'boolean',
        'clock_in_latitude'      => 'float',
        'clock_in_longitude'     => 'float',
        'clock_out_latitude'     => 'float',
        'clock_out_longitude'    => 'float',
        'rejected_at'            => 'datetime',
        'action_requested'       => 'boolean',
        'is_archived'            => 'boolean',
        'archived_at'            => 'datetime',
    ];

    public function getEffectiveRate(): float
    {
        if ($this->hourly_rate !== null && (float)$this->hourly_rate > 0) {
            return (float)$this->hourly_rate;
        }
        if ($this->task && $this->task->hourly_rate !== null && (float)$this->task->hourly_rate > 0) {
            return (float)$this->task->hourly_rate;
        }
        return (float)($this->employee?->base_pay ?? 0.0);
    }

    public function getPayableAmount(): float
    {
        $rate = $this->getEffectiveRate();
        $hours = ($this->clock_out ? $this->duration_minutes : (int) ceil($this->netSeconds() / 60)) / 60;
        return round($hours * $rate, 2);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isRejected(): bool
    {
        return !is_null($this->rejected_at) || $this->status === 'rejected';
    }

    public function isApproved(): bool
    {
        return (bool) $this->approved;
    }

    public function isPending(): bool
    {
        return !$this->isApproved() && !$this->isRejected() && !$this->isBreak();
    }

    public function isActionRequested(): bool
    {
        return (bool) $this->action_requested || $this->status === 'action_requested';
    }

    public function isBreak(): bool
    {
        return $this->status === 'break';
    }

    public function isLocked(): bool
    {
        return $this->isApproved()
            || $this->isRejected()
            || $this->status === 'approved'
            || $this->status === 'rejected'
            || $this->status === 'withdrawn';
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function childLogs()
    {
        return $this->hasMany(TimeLog::class, 'parent_log_id');
    }

    public function parentLog()
    {
        return $this->belongsTo(TimeLog::class, 'parent_log_id');
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function scopeNotArchived($query)
    {
        return $query->where(function ($q) {
            $q->where('is_archived', false)->orWhereNull('is_archived');
        });
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function logNotes()
    {
        return $this->hasMany(TimeLogNote::class, 'time_log_id')->latest();
    }

    public function logAttachments()
    {
        return $this->hasMany(TimeLogAttachment::class, 'time_log_id')->latest();
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public function latestEditAudit()
    {
        return $this->morphOne(AuditLog::class, 'auditable')->where('action', 'Edit')->latestOfMany();
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

    public function getLogTimezone(): string
    {
        return $this->timezone ?: \App\Models\CompanySetting::getServerTimezone() ?: config('app.timezone', 'America/New_York');
    }

    public function getClockInCarbon(): ?Carbon
    {
        if (!$this->clock_in) return null;
        $raw = $this->getRawOriginal('clock_in') ?: ($this->attributes['clock_in'] ?? null);
        if ($raw) {
            try {
                return Carbon::parse($raw, $this->getLogTimezone());
            } catch (\Throwable $e) {}
        }
        return $this->clock_in;
    }

    public function getClockOutCarbon(): ?Carbon
    {
        if (!$this->clock_out) return null;
        $raw = $this->getRawOriginal('clock_out') ?: ($this->attributes['clock_out'] ?? null);
        if ($raw) {
            try {
                return Carbon::parse($raw, $this->getLogTimezone());
            } catch (\Throwable $e) {}
        }
        return $this->clock_out;
    }

    public function getBreakStartedAtCarbon(): ?Carbon
    {
        if (!$this->break_started_at) return null;
        $raw = $this->getRawOriginal('break_started_at') ?: ($this->attributes['break_started_at'] ?? null);
        if ($raw) {
            try {
                return Carbon::parse($raw, $this->getLogTimezone());
            } catch (\Throwable $e) {}
        }
        return $this->break_started_at;
    }

    public function grossSeconds(): int
    {
        $start = $this->getClockInCarbon();
        if (!$start) return 0;
        $end = $this->clock_out ? $this->getClockOutCarbon() : Carbon::now($this->getLogTimezone());
        if (!$end) return 0;
        return max(0, $end->getTimestamp() - $start->getTimestamp());
    }

    public function currentBreakSeconds(): int
    {
        if (!$this->isOnBreak()) {
            return 0;
        }
        $start = $this->getBreakStartedAtCarbon();
        if (!$start) return 0;
        return max(0, Carbon::now($this->getLogTimezone())->getTimestamp() - $start->getTimestamp());
    }

    public function completedBreakSeconds(): int
    {
        $total = 0;
        if (!empty($this->breaks) && is_array($this->breaks)) {
            foreach ($this->breaks as $b) {
                if (isset($b['start']) && isset($b['end'])) {
                    try {
                        $start = Carbon::parse($b['start']);
                        $end   = Carbon::parse($b['end']);
                        $diff  = abs($end->getTimestamp() - $start->getTimestamp());
                        $total += $diff;
                        continue;
                    } catch (\Throwable $e) {}
                }
                if (isset($b['seconds'])) {
                    $total += (int) $b['seconds'];
                } elseif (isset($b['minutes'])) {
                    $total += (int) round(((float)$b['minutes']) * 60);
                }
            }
        } elseif ($this->break_minutes) {
            $total = ((int) $this->break_minutes) * 60;
        }
        return $total;
    }

    public function totalBreakSeconds(): int
    {
        return $this->completedBreakSeconds() + $this->currentBreakSeconds();
    }

    public function netSeconds(): int
    {
        $gross  = $this->grossSeconds();
        $breaks = $this->totalBreakSeconds();
        return max(0, $gross - $breaks);
    }

    /**
     * Get the root shift log if this log is part of a switched shift sequence.
     */
    public function getRootShiftLog(): self
    {
        if ($this->shift_id) {
            $root = self::where('shift_id', $this->shift_id)
                ->whereNull('parent_log_id')
                ->first();
            if ($root) return $root;
        }

        $current = $this;
        while ($current->parent_log_id) {
            $parent = self::find($current->parent_log_id);
            if (!$parent) break;
            $current = $parent;
        }
        return $current;
    }

    /**
     * Get all logs in the current shift sequence (chronological order).
     */
    public function getShiftLogs()
    {
        if ($this->shift_id) {
            return self::where('shift_id', $this->shift_id)
                ->orderBy('clock_in', 'asc')
                ->get();
        }

        return collect([$this]);
    }

    /**
     * Total net seconds across the entire shift (all switched jobs/tasks in this shift up to now).
     */
    public function shiftNetSeconds(): int
    {
        $shiftLogs = $this->getShiftLogs();
        if ($shiftLogs->isEmpty()) {
            return $this->netSeconds();
        }

        $totalNet = 0;
        foreach ($shiftLogs as $l) {
            $totalNet += $l->netSeconds();
        }
        return $totalNet;
    }

    /**
     * Total completed break seconds across the entire shift sequence.
     */
    public function shiftCompletedBreakSeconds(): int
    {
        $shiftLogs = $this->getShiftLogs();
        if ($shiftLogs->isEmpty()) {
            return $this->completedBreakSeconds();
        }

        return (int) $shiftLogs->sum(fn ($l) => $l->completedBreakSeconds());
    }

    public function switchTo(int $assignmentId, int $taskId): self
    {
        if ($this->isOnBreak()) {
            $breakStart = $this->getBreakStartedAtCarbon();
            $now        = Carbon::now($this->getLogTimezone());
            $diffSecs   = $breakStart ? abs($now->getTimestamp() - $breakStart->getTimestamp()) : 0;
            $breaks     = $this->breaks ?? [];
            $breaks[]   = [
                'start'   => $breakStart ? $breakStart->toIso8601String() : $now->toIso8601String(),
                'end'     => $now->toIso8601String(),
                'seconds' => $diffSecs,
                'minutes' => (int) round($diffSecs / 60),
            ];
            $this->breaks          = $breaks;
            $this->break_minutes   = (int) round($this->completedBreakSeconds() / 60);
            $this->break_started_at = null;
        }

        // Ensure this log has a shift_id before chaining
        if (empty($this->shift_id)) {
            $this->shift_id = 'shift_' . $this->employee_id . '_' . $this->clock_in->timestamp;
        }

        $now = Carbon::now($this->getLogTimezone());
        $this->clock_out        = $now;
        $this->duration_minutes = (int) ceil($this->netSeconds() / 60);
        $this->status           = 'switched';
        $this->save();

        $newTask = Task::find($taskId);
        $hourlyRate = $newTask ? $newTask->getHourlyRateFor($this->employee) : ($this->hourly_rate ?: ($this->employee?->base_pay ?: null));

        return self::create([
            'employee_id'   => $this->employee_id,
            'shift_id'      => $this->shift_id,
            'parent_log_id' => $this->id,
            'assignment_id' => $assignmentId,
            'task_id'       => $taskId,
            'hourly_rate'   => $hourlyRate,
            'timezone'      => $this->getLogTimezone(),
            'clock_in'      => $now,
            'status'        => 'active',
        ]);
    }

    public static function formatDurationHM(int|float|null $minutes): string
    {
        return \App\Services\ReportDataService::formatDurationHM($minutes);
    }

    public static function formatHoursToHM(int|float|null $hours): string
    {
        return \App\Services\ReportDataService::formatHoursToHM($hours);
    }
}
