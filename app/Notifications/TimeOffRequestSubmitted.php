<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffRequestSubmitted extends Notification
{
    use Queueable;

    public $timeOffRequest;

    public function __construct(TimeOffRequest $timeOffRequest)
    {
        $this->timeOffRequest = $timeOffRequest;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $req = $this->timeOffRequest;
        $employeeName = $req->employee ? $req->employee->name : 'An employee';
        $policyName = $req->policy ? $req->policy->name : 'Time Off';
        $dates = $req->start_at->format('M d, Y') . ' to ' . $req->end_at->format('M d, Y');
        $hours = number_format($req->hours, 1);

        return (new MailMessage)
            ->subject("New Time Off Request: {$employeeName} ({$hours} hrs)")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} has submitted a new {$policyName} request for {$hours} hours.")
            ->line("Dates: {$dates}")
            ->when($req->reason, fn($m) => $m->line("Reason: {$req->reason}"))
            ->action('Review Request', url('/time-off/requests'))
            ->line('Please review and approve or deny this request in ClockVerse.');
    }

    public function toDatabase($notifiable): array
    {
        $req = $this->timeOffRequest;
        $employeeName = $req->employee ? $req->employee->name : 'An employee';
        $policyName = $req->policy ? $req->policy->name : 'Time Off';

        return [
            'type'        => 'time_off_submitted',
            'title'       => 'New Time Off Request',
            'message'     => "{$employeeName} requested {$req->hours}h for {$policyName}.",
            'link'        => '/time-off/requests',
            'request_id'  => $req->id,
            'employee_id' => $req->employee_id,
            'created_at'  => now()->toISOString(),
        ];
    }
}
