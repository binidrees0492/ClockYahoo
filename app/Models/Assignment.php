<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'tbl_assignments';

    protected $fillable = [
        'name',
        'location',
        'location_id',
        'gps_policy',
        'latitude',
        'longitude',
        'geofence_radius',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius' => 'integer',
    ];

    public function locationPreset()
    {
        return $this->belongsTo(Location::class, 'location_id');
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

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'assignment_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignment_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'assignment_id');
    }
}
