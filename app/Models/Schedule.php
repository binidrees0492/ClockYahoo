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

    public function isPublished(): bool
    {
        return $this->status === 'published' || (bool)$this->is_published;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft' || !(bool)$this->is_published;
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
