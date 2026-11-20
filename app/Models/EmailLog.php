<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'user_id',
        'recipient',
        'sender',
        'subject',
        'body',
        'folder',
        'is_archived',
        'attachments',
        'read_at',
        'status',
        'error_message',
        'mailer_type',
        'sent_at',
        'retry_count',
        'last_attempt_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'retry_count' => 'integer',
        'is_archived' => 'boolean',
        'attachments' => 'array',
    ];

    public function archive(): bool
    {
        return $this->update([
            'is_archived' => true,
            'folder' => 'archived',
        ]);
    }

    public function unarchive(string $targetFolder = 'sent'): bool
    {
        return $this->update([
            'is_archived' => false,
            'folder' => $targetFolder,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Resend this email and update the log.
     */
    public function resend(): bool
    {
        $this->increment('retry_count');
        $this->update(['last_attempt_at' => now()]);

        try {
            // Send email using raw html body
            Mail::html($this->body ?: 'No content', function ($message) {
                $message->to($this->recipient)
                    ->subject($this->subject);

                if ($this->sender) {
                    $message->from($this->sender);
                }
            });

            $this->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
