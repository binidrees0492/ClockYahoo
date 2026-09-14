<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TimeLogAttachment extends Model
{
    use HasFactory;

    protected $table = 'time_log_attachments';

    protected $fillable = [
        'time_log_id',
        'user_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function timeLog()
    {
        return $this->belongsTo(TimeLog::class, 'time_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
