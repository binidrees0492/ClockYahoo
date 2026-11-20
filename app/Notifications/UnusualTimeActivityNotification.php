<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnusualTimeActivityNotification extends Notification
{
    use Queueable;

    public $employeeName;
    public $date;
    public $reason;
    public $timeLogId;
    public $isHrView;

    public function __construct(
        string $employeeName,
        string $date,
        string $reason,
        int $timeLogId,
        bool $isHrView = false
    ) {
        $this->employeeName = $employeeName;
        $this->date         = $date;
        $this->reason       = $reason;
        $this->timeLogId    = $timeLogId;
        $this->isHrView     = $isHrView;
    }

    public function via($notifiable): array
    {
        $asPersonInvolved = !$this->isHrView;
        if (!NotificationRule::isAllowedForUser('unusual_activity', $notifiable, $asPersonInvolved)) {
            return [];
        }

        $channels = method_exists($notifiable, 'getNotificationChannelsFor')
            ? $notifiable->getNotificationChannelsFor('unusual_activity')
            : (method_exists($notifiable, 'getNotificationChannels') ? $notifiable->getNotificationChannels() : ['database', 'mail']);

        if (!NotificationRoutingService::isEmailDeliveryEnabled()) {
            $channels = array_values(array_filter($channels, fn($c) => $c !== 'mail'));
        }

        return !empty($channels) ? $channels : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = NotificationRoutingService::getNotificationSender();

        $subject = $this->isHrView
            ? "⚠️ Unusual Activity Alert: {$this->employeeName} on {$this->date}"
            : "⚠️ Unusual Activity Notice on {$this->date}";

        $greeting = "Hello {$notifiable->name},";
        $line1 = $this->isHrView
            ? "Unusual time activity was detected for {$this->employeeName} on {$this->date}."
            : "An unusual activity flag was recorded for your time log on {$this->date}.";

        $url = $this->isHrView ? url('/timesheets/approve') : url('/timesheets/view');

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line("Discrepancy Details: {$this->reason}")
            ->action('Review Timesheet', $url)
            ->line('Please review the discrepancy.');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        $title = $this->isHrView
            ? "⚠️ Unusual Activity: {$this->employeeName}"
            : "⚠️ Unusual Activity Flagged";

        $message = $this->isHrView
            ? "Discrepancy on {$this->date} for {$this->employeeName}: {$this->reason}"
            : "Shift discrepancy detected on {$this->date}: {$this->reason}";

        $link = $this->isHrView ? '/timesheets/approve' : '/timesheets/view';

        return [
            'type'        => 'unusual_activity',
            'event_key'   => 'unusual_activity',
            'title'       => $title,
            'message'     => $message,
            'link'        => $link,
            'employee'    => $this->employeeName,
            'date'        => $this->date,
            'reason'      => $this->reason,
            'time_log_id' => $this->timeLogId,
            'toast'       => true,
            'created_at'  => now()->toISOString(),
        ];
    }
}
