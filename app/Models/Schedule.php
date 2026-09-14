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
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

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
