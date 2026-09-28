<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffRequestThread extends Model
{
    use HasFactory;

    protected $table = 'time_off_request_threads';

    protected $fillable = [
        'time_off_request_id',
        'user_id',
        'type',
        'message',
    ];

    public function request()
    {
        return $this->belongsTo(TimeOffRequest::class, 'time_off_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}