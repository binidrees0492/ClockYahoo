<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Throwable;

class LogSentMessageListener
{
    /**
     * Handle the MessageSent event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $toAddresses = array_map(function ($addr) {
                return $addr instanceof Address ? $addr->getAddress() : (string) $addr;
            }, $message->getTo() ?: []);

            $recipient = implode(', ', $toAddresses);
            $subject = $message->getSubject() ?: '(No Subject)';
            $body = $message->getHtmlBody() ?: $message->getTextBody() ?: '';

            $fromAddresses = array_map(function ($addr) {
                return $addr instanceof Address ? $addr->getAddress() : (string) $addr;
            }, $message->getFrom() ?: []);
            $sender = implode(', ', $fromAddresses);

            $userId = null;
            if (!empty($toAddresses)) {
                $firstTo = $toAddresses[0];
                $user = User::where('email', $firstTo)->first();
                if ($user) {
                    $userId = $user->id;
                }
            }

            EmailLog::create([
                'user_id'       => $userId,
                'recipient'     => $recipient ?: 'Unknown',
                'sender'        => $sender,
                'subject'       => $subject,
                'body'          => is_resource($body) ? stream_get_contents($body) : (string) $body,
                'status'        => 'sent',
                'mailer_type'   => 'smtp',
                'sent_at'       => now(),
                'last_attempt_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Silently swallow any logging exception so mail delivery is never interrupted
        }
    }
}
