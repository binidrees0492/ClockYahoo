<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tbl_tasks';

    protected $fillable = [
        'assignment_id',
        'location_id',
        'name',
        'hourly_rate',
        'status',
        'gps_policy',
        'geofence_radius',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius' => 'integer',
    ];

    public function locationPreset()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function getHourlyRateFor(?User $employee = null): float
    {
        if ($this->hourly_rate !== null && (float) $this->hourly_rate > 0) {
            return (float) $this->hourly_rate;
        }

        return (float) ($employee?->base_pay ?? auth()->user()?->base_pay ?? 0.00);
    }

    public static function ongoingStatuses(): array
    {
        return ['in progress', 'active', 'scheduled'];
    }

    public static function allStatuses(): array
    {
        return ['scheduled', 'in progress', 'completed', 'on hold', 'cancelled', 'active'];
    }

    public function scopeOngoing($query)
    {
        return $query->whereIn('status', self::ongoingStatuses());
    }

    public function isOngoing(): bool
    {
        return in_array(strtolower((string) $this->status), self::ongoingStatuses(), true);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'task_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'task_id');
    }
}
