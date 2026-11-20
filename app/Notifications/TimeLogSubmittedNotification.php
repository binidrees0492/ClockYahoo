<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeLogSubmittedNotification extends Notification
{
    use Queueable;

    public $employeeName;
    public $date;
    public $hours;
    public $jobName;
    public $timeLogId;
    public $isManual;

    public function __construct(string $employeeName, string $date, string $hours, string $jobName, int $timeLogId, bool $isManual = false)
    {
        $this->employeeName = $employeeName;
        $this->date         = $date;
        $this->hours        = $hours;
        $this->jobName      = $jobName;
        $this->timeLogId    = $timeLogId;
        $this->isManual     = $isManual;
    }

    public function via($notifiable): array
    {
        if (!NotificationRule::isAllowedForUser('time_submitted', $notifiable)) {
            return [];
        }

        $channels = method_exists($notifiable, 'getNotificationChannelsFor')
            ? $notifiable->getNotificationChannelsFor('time_submitted')
            : (method_exists($notifiable, 'getNotificationChannels') ? $notifiable->getNotificationChannels() : ['database', 'mail']);

        if (!NotificationRoutingService::isEmailDeliveryEnabled()) {
            $channels = array_values(array_filter($channels, fn($c) => $c !== 'mail'));
        }

        return !empty($channels) ? $channels : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = NotificationRoutingService::getNotificationSender();
        $typeLabel = $this->isManual ? 'Manual Time Entry' : 'Time Log';

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject("New {$typeLabel} Submitted: {$this->employeeName} ({$this->hours}h)")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->employeeName} has logged {$this->hours} hours for {$this->jobName} on {$this->date}.")
            ->when($this->isManual, fn ($m) => $m->line('Note: This is a manual / copied entry requiring verification.'))
            ->action('Approve or Disapprove', url('/timesheets/approve'))
            ->line('Please review and take action in ClockVerse.');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        $typeLabel = $this->isManual ? 'Manual Time Submitted' : 'Time Logged for Review';

        return [
            'type'         => 'time_log_submitted',
            'event_key'    => 'time_submitted',
            'title'        => $typeLabel,
            'message'      => "{$this->employeeName} logged {$this->hours} hrs on {$this->date} ({$this->jobName}).",
            'link'         => '/timesheets/approve',
            'employee'     => $this->employeeName,
            'date'         => $this->date,
            'hours'        => $this->hours,
            'time_log_id'  => $this->timeLogId,
            'toast'        => true,
            'created_at'   => now()->toISOString(),
        ];
    }
}
