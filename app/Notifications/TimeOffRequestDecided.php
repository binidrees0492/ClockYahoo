<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffRequestDecided extends Notification
{
    use Queueable;

    public $timeOffRequest;
    public $decision;

    public function __construct(TimeOffRequest $timeOffRequest, string $decision)
    {
        $this->timeOffRequest = $timeOffRequest;
        $this->decision = strtolower($decision);
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $req = $this->timeOffRequest;
        $statusUpper = strtoupper($this->decision);
        $policyName = $req->policy ? $req->policy->name : 'Time Off';
        $dates = $req->start_at->format('M d, Y') . ' to ' . $req->end_at->format('M d, Y');
        $deciderName = $req->decidedByUser ? $req->decidedByUser->name : 'Your manager';

        $mail = (new MailMessage)
            ->subject("Time Off Request {$statusUpper}: {$policyName} ({$dates})")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$policyName} request for {$dates} ({$req->hours} hours) has been {$statusUpper} by {$deciderName}.");

        if (!empty($req->decision_notes)) {
            $mail->line("Notes from approver: \"{$req->decision_notes}\"");
        }

        return $mail
            ->action('View Time Off Details', url('/time-off/requests'))
            ->line('Thank you for using ClockVerse.');
    }

    public function toDatabase($notifiable): array
    {
        $req = $this->timeOffRequest;
        $statusLabel = ucfirst($this->decision);
        $policyName = $req->policy ? $req->policy->name : 'Time Off';

        return [
            'type'        => 'time_off_decision',
            'title'       => "Time Off Request {$statusLabel}",
            'message'     => "Your {$policyName} request was {$this->decision}.",
            'link'        => '/time-off/requests',
            'request_id'  => $req->id,
            'decision'    => $this->decision,
            'created_at'  => now()->toISOString(),
        ];
    }
}
