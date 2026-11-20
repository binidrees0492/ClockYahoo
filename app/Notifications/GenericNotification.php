<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericNotification extends Notification
{
    use Queueable;

    public $message;
    public $link;
    public $title;
    public $eventKey;

    public function __construct(string $message, ?string $link = null, string $title = 'Notification', string $eventKey = 'system_alert')
    {
        $this->message  = $message;
        $this->link     = $link ?: url('/dashboard');
        $this->title    = $title;
        $this->eventKey = $eventKey;
    }

    public function via($notifiable): array
    {
        if ($this->eventKey && !NotificationRule::isAllowedForUser($this->eventKey, $notifiable)) {
            return [];
        }

        $rule = NotificationRule::getRule($this->eventKey);
        $role = $notifiable->role ?: 'Employee';
        $channels = $rule ? $rule->getRoleChannels($role) : ['in_app', 'email'];

        $via = [];
        if (in_array('in_app', $channels) || in_array('toast', $channels)) {
            $via[] = 'database';
        }
        if (in_array('email', $channels) && NotificationRoutingService::isEmailDeliveryEnabled()) {
            $via[] = 'mail';
        }

        return !empty($via) ? $via : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = NotificationRoutingService::getNotificationSender();

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject($this->title)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message)
            ->action('View Details', $this->link)
            ->line('Thank you for using ClockVerse.');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'generic_alert',
            'event_key'  => $this->eventKey,
            'title'      => $this->title,
            'message'    => $this->message,
            'link'       => $this->link,
            'toast'      => true,
            'created_at' => now()->toISOString(),
        ];
    }
}
