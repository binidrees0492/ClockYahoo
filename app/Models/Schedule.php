<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedules';

    protected $fillable = [
        'employee_id',
        'assignment_id',
        'task_id',
        'shift_date',
        'status',
        'is_published',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'break_minutes',
        'standard_hours',
        'latitude',
        'longitude',
        'geofence_radius',
        'location_name',
        'notes',
    ];

    protected $casts = [
        'shift_date'      => 'date',
        'is_published'    => 'boolean',
        'standard_hours'  => 'decimal:2',
        'latitude'        => 'float',
        'longitude'       => 'float',
        'geofence_radius' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['published', 'scheduled'])
              ->orWhere('is_published', true);
        });
    }

    public function scopeDraft($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'draft')
              ->orWhere(function ($q2) {
                  $q2->whereNotIn('status', ['published', 'scheduled'])
                     ->where(function ($q3) {
                         $q3->whereNull('is_published')->orWhere('is_published', false);
                     });
              });
        });
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['published', 'scheduled'], true) || (bool)$this->is_published;
    }

    public function isDraft(): bool
    {
        return !$this->isPublished();
    }

    public function getStartCarbonAttribute(): ?\Carbon\Carbon
    {
        if (!$this->shift_date || !$this->start_time) {
            return null;
        }
        $dateStr = $this->shift_date instanceof \Carbon\Carbon
            ? $this->shift_date->toDateString()
            : (string) $this->shift_date;
        $timeStr = is_string($this->start_time)
            ? substr($this->start_time, 0, 8)
            : (is_object($this->start_time) && method_exists($this->start_time, 'format') ? $this->start_time->format('H:i:s') : (string) $this->start_time);
        try {
            return \Carbon\Carbon::parse($dateStr . ' ' . $timeStr);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getEndCarbonAttribute(): ?\Carbon\Carbon
    {
        if (!$this->shift_date || !$this->end_time) {
            return null;
        }
        $dateStr = $this->shift_date instanceof \Carbon\Carbon
            ? $this->shift_date->toDateString()
            : (string) $this->shift_date;
        $timeStr = is_string($this->end_time)
            ? substr($this->end_time, 0, 8)
            : (is_object($this->end_time) && method_exists($this->end_time, 'format') ? $this->end_time->format('H:i:s') : (string) $this->end_time);
        try {
            $end = \Carbon\Carbon::parse($dateStr . ' ' . $timeStr);
            $start = $this->start_carbon;
            if ($start && $end->lt($start)) {
                $end->addDay();
            }
            return $end;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function coversTime($datetime = null, int $graceBefore = 30, int $graceAfter = 30): bool
    {
        $dt = $datetime
            ? ($datetime instanceof \Carbon\Carbon ? $datetime->copy() : \Carbon\Carbon::parse($datetime))
            : \Carbon\Carbon::now();

        $shiftDateStr = $this->shift_date instanceof \Carbon\Carbon
            ? $this->shift_date->toDateString()
            : (string) $this->shift_date;

        $start = $this->start_carbon;
        $end = $this->end_carbon;

        if (!$start || !$end) {
            return $dt->toDateString() === $shiftDateStr;
        }

        $validStart = $start->copy()->subMinutes($graceBefore);
        $validEnd = $end->copy()->addMinutes($graceAfter);

        return $dt->betweenIncluded($validStart, $validEnd);
    }

    public function coversTimeRange($clockIn, $clockOut, int $graceBeforeMinutes = 30, int $graceAfterMinutes = 60): bool
    {
        $in = $clockIn instanceof \Carbon\Carbon ? $clockIn->copy() : \Carbon\Carbon::parse($clockIn);
        $out = $clockOut instanceof \Carbon\Carbon ? $clockOut->copy() : \Carbon\Carbon::parse($clockOut);

        $shiftDateStr = $this->shift_date instanceof \Carbon\Carbon
            ? $this->shift_date->toDateString()
            : (string) $this->shift_date;

        if ($in->toDateString() !== $shiftDateStr) {
            return false;
        }

        $start = $this->start_carbon;
        $end = $this->end_carbon;

        if (!$start || !$end) {
            return true;
        }

        $validStart = $start->copy()->subMinutes($graceBeforeMinutes);
        $validEnd = $end->copy()->addMinutes($graceAfterMinutes);

        return $in->gte($validStart) && $out->lte($validEnd);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
