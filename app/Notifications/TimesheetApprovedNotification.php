<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetApprovedNotification extends Notification
{
    use Queueable;

    public $date;
    public $approverName;
    public $totalHours;

    public function __construct(string $date, string $approverName, float $totalHours)
    {
        $this->date         = $date;
        $this->approverName = $approverName;
        $this->totalHours   = $totalHours;
    }

    public function via($notifiable): array
    {
        if (!NotificationRule::isAllowedForUser('timesheet_approved', $notifiable, true)) {
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
        $hrs = number_format($this->totalHours, 2);

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject("Timesheet Approved for {$this->date}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your timesheet for {$this->date} ({$hrs} hours) has been approved by {$this->approverName}.")
            ->action('View Timesheets', url('/timesheets/view'))
            ->line('Keep up the great work!');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        $hrs = number_format($this->totalHours, 2);

        return [
            'type'        => 'timesheet_approved',
            'event_key'   => 'timesheet_approved',
            'title'       => 'Timesheet Approved',
            'message'     => "Your timesheet for {$this->date} ({$hrs} hrs) was approved by {$this->approverName}.",
            'link'        => '/timesheets/view',
            'date'        => $this->date,
            'hours'       => $hrs,
            'toast'       => true,
            'created_at'  => now()->toISOString(),
        ];
    }
}
