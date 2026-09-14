<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tbl_tasks';

    protected $fillable = [
        'name',
        'status',
    ];

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'task_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'task_id');
    }
}
