<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLogNote extends Model
{
    use HasFactory;

    protected $table = 'time_log_notes';

    protected $fillable = ['time_log_id', 'user_id', 'body'];

    public function timeLog()
    {
        return $this->belongsTo(TimeLog::class, 'time_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
