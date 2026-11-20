<?php

namespace App\Notifications;

use App\Models\NotificationRule;
use App\Services\NotificationRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftAssignedNotification extends Notification
{
    use Queueable;

    public $shiftDate;
    public $jobName;
    public $timeRange;
    public $forStakeholder;
    public $employeeName;

    public function __construct(string $shiftDate, string $jobName, string $timeRange, bool $forStakeholder = false, ?string $employeeName = null)
    {
        $this->shiftDate      = $shiftDate;
        $this->jobName        = $jobName;
        $this->timeRange      = $timeRange;
        $this->forStakeholder = $forStakeholder;
        $this->employeeName   = $employeeName;
    }

    public function via($notifiable): array
    {
        $asPersonInvolved = !$this->forStakeholder;
        if (!NotificationRule::isAllowedForUser('shift_assigned', $notifiable, $asPersonInvolved)) {
            return [];
        }

        $channels = method_exists($notifiable, 'getNotificationChannelsFor')
            ? $notifiable->getNotificationChannelsFor('shift_assigned')
            : (method_exists($notifiable, 'getNotificationChannels') ? $notifiable->getNotificationChannels() : ['database', 'mail']);

        if (!NotificationRoutingService::isEmailDeliveryEnabled()) {
            $channels = array_values(array_filter($channels, fn($c) => $c !== 'mail'));
        }

        return !empty($channels) ? $channels : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $sender = NotificationRoutingService::getNotificationSender();

        $subject = $this->forStakeholder
            ? "Shift Scheduled for {$this->employeeName}: {$this->shiftDate}"
            : "New Shift Assigned: {$this->shiftDate}";

        $greeting = "Hello {$notifiable->name},";
        $line1 = $this->forStakeholder
            ? "A shift has been scheduled for {$this->employeeName} on {$this->shiftDate}."
            : "You have been scheduled for a shift on {$this->shiftDate}.";

        $mail = (new MailMessage)
            ->from($sender['address'], $sender['name'])
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line("Job / Location: {$this->jobName}")
            ->line("Hours: {$this->timeRange}")
            ->action('View Schedules', url('/schedules'))
            ->line('Please check the schedule in ClockVerse.');

        if (!empty($sender['reply_to'])) {
            $mail->replyTo($sender['reply_to']);
        }

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        $title = $this->forStakeholder
            ? "Shift Scheduled: {$this->employeeName}"
            : "New Shift Scheduled";

        $message = $this->forStakeholder
            ? "Shift for {$this->employeeName} on {$this->shiftDate} ({$this->timeRange}) at {$this->jobName}."
            : "Shift on {$this->shiftDate} ({$this->timeRange}) at {$this->jobName}.";

        return [
            'type'        => 'shift_assigned',
            'event_key'   => 'shift_assigned',
            'title'       => $title,
            'message'     => $message,
            'link'        => '/schedules',
            'shift_date'  => $this->shiftDate,
            'job'         => $this->jobName,
            'toast'       => true,
            'created_at'  => now()->toISOString(),
        ];
    }
}
