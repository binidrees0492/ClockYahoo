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
        'status',
    ];

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'assignment_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'assignment_id');
    }
}
