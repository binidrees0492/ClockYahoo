<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetDisapprovedNotification extends Notification
{
    use Queueable;

    public $date;
    public $reviewerName;
    public $reason;

    public function __construct(string $date, string $reviewerName, ?string $reason = null)
    {
        $this->date         = $date;
        $this->reviewerName = $reviewerName;
        $this->reason       = $reason ?: 'Not specified';
    }

    public function via($notifiable): array
    {
        if (!NotificationRule::isAllowedForUser('timesheet_disapproved', $notifiable, true)) {
            return [];
        }

        $channels = method_exists($notifiable, 'getNotificationChannelsFor')
            ? $notifiable->getNotificationChannelsFor('time_approval')
            : (method_exists($notifiable, 'getNotificationChannels') ? $notifiable->getNotificationChannels() : ['database', 'mail']);

        if (!NotificationRoutingService::isEmailDeliveryEnabled()) {
            $channels = array_values(array_filter($channels, fn($c) => $c !== 'mail'));
        }

        return !empty($channels) ? $channels : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = NotificationRoutingService::getNotificationSender();

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject("Timesheet Disapproved for {$this->date}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your timesheet entry for {$this->date} has been disapproved/rejected by {$this->reviewerName}.")
            ->line("Reason: {$this->reason}")
            ->action('Review Timesheets', url('/timesheets/view'))
            ->line('Please review your timesheet details or speak with your supervisor.');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'timesheet_disapproved',
            'event_key'   => 'timesheet_disapproved',
            'title'       => 'Timesheet Disapproved',
            'message'     => "Timesheet for {$this->date} was rejected by {$this->reviewerName}. Reason: {$this->reason}",
            'link'        => '/timesheets/view',
            'date'        => $this->date,
            'reason'      => $this->reason,
            'toast'       => true,
            'created_at'  => now()->toISOString(),
        ];
    }
}
