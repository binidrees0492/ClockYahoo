<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\EmailLog;
use App\Models\NotificationRule;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationRoutingService
{
    /**
     * Get configured sender credentials for system notifications.
     */
    public static function getNotificationSender(): array
    {
        $serverFromAddress = CompanySetting::get('mail_from_address', config('mail.from.address', 'noreply@clockverse.com'));
        $serverFromName    = CompanySetting::get('mail_from_name', config('mail.from.name', 'ClockVerse'));

        $fromAddress = CompanySetting::get('notification_from_email') ?: $serverFromAddress;
        $fromName    = CompanySetting::get('notification_from_name') ?: ($serverFromName . ' Notifications');
        $replyTo     = CompanySetting::get('notification_reply_to') ?: null;

        return [
            'address'  => trim($fromAddress ?: 'notifications@clockverse.com'),
            'name'     => trim($fromName ?: 'ClockVerse Notifications'),
            'reply_to' => $replyTo ? trim($replyTo) : null,
        ];
    }

    /**
     * Check if global email notification delivery is enabled.
     */
    public static function isEmailDeliveryEnabled(): bool
    {
        return (bool) CompanySetting::get('notification_email_enabled', true);
    }

    /**
     * Route and dispatch a notification according to system rules and manager preferences.
     *
     * @param string $eventKey E.g. 'shift_assigned', 'time_submitted', 'unusual_activity', etc.
     * @param array $data ['title' => ..., 'message' => ..., 'link' => ..., 'type' => ...]
     * @param User|null $personInvolved The direct employee or user involved
     * @param array $extraRecipients Optional extra User models
     * @return array Summary of dispatched notifications
     */
    public static function send(string $eventKey, array $data, ?User $personInvolved = null, array $extraRecipients = []): array
    {
        $rule = NotificationRule::getRule($eventKey);
        if (!$rule || !$rule->is_active) {
            return [
                'dispatched' => 0,
                'recipients' => [],
                'status'     => 'inactive_or_missing_rule',
            ];
        }

        $recipientsMap = [];

        // 1. Person Involved Evaluation
        if ($personInvolved && $rule->person_involved_enabled) {
            $channels = $rule->person_involved_channels ?: ['in_app', 'toast', 'email'];

            // Check person's personal notification channel preferences if present
            $pref = $personInvolved->notification_channel_preference ?? 'both';
            if ($pref === 'bell_only') {
                $channels = array_values(array_filter($channels, fn($c) => $c !== 'email'));
            } elseif ($pref === 'email_only') {
                $channels = array_values(array_filter($channels, fn($c) => $c === 'email'));
            }

            if (!empty($channels)) {
                $recipientsMap[$personInvolved->id] = [
                    'user'      => $personInvolved,
                    'channels'  => $channels,
                    'role_type' => 'person_involved',
                ];
            }
        }

        // 2. Managers Evaluation
        if ($rule->isRoleEnabled('Manager')) {
            $managerScope = $rule->getManagerScope();
            $managerCandidates = collect();

            if ($personInvolved) {
                if ($managerScope === 'direct_reports') {
                    if ($personInvolved->manager_id) {
                        $mgr = User::find($personInvolved->manager_id);
                        if ($mgr && $mgr->id !== $personInvolved->id) {
                            $managerCandidates->push($mgr);
                        }
                    }
                } elseif ($managerScope === 'department') {
                    // Direct manager first
                    if ($personInvolved->manager_id) {
                        $mgr = User::find($personInvolved->manager_id);
                        if ($mgr && $mgr->id !== $personInvolved->id) {
                            $managerCandidates->push($mgr);
                        }
                    }
                    // Managers in employee's department
                    if ($personInvolved->department_id) {
                        $deptManagers = User::where('role', 'Manager')
                            ->where('department_id', $personInvolved->department_id)
                            ->where('id', '!=', $personInvolved->id)
                            ->get();
                        foreach ($deptManagers as $dm) {
                            if (!$managerCandidates->contains('id', $dm->id)) {
                                $managerCandidates->push($dm);
                            }
                        }
                    }
                } elseif ($managerScope === 'all') {
                    $allManagers = User::where('role', 'Manager')
                        ->where('id', '!=', $personInvolved->id)
                        ->get();
                    foreach ($allManagers as $am) {
                        $managerCandidates->push($am);
                    }
                }
            } else {
                // Global event without direct person involved
                $allManagers = User::where('role', 'Manager')->get();
                foreach ($allManagers as $am) {
                    $managerCandidates->push($am);
                }
            }

            $defaultManagerChannels = $rule->getRoleChannels('Manager');

            foreach ($managerCandidates as $mgr) {
                // If manager is already direct person involved, skip duplicate manager dispatch
                if (isset($recipientsMap[$mgr->id])) {
                    continue;
                }

                // Check manager's individual activity preferences
                $mgrSettings = $mgr->getManagerNotificationSettings();
                $settingKey = self::mapEventToManagerSettingKey($eventKey);

                $mgrChannels = $defaultManagerChannels;
                if ($settingKey && isset($mgrSettings[$settingKey]) && is_array($mgrSettings[$settingKey])) {
                    $cfg = $mgrSettings[$settingKey];
                    $customChannels = [];
                    if (!empty($cfg['in_app'])) $customChannels[] = 'in_app';
                    if (!empty($cfg['toast']))  $customChannels[] = 'toast';
                    if (!empty($cfg['email']))  $customChannels[] = 'email';
                    $mgrChannels = $customChannels;
                }

                if (!empty($mgrChannels)) {
                    $recipientsMap[$mgr->id] = [
                        'user'      => $mgr,
                        'channels'  => $mgrChannels,
                        'role_type' => 'manager',
                    ];
                }
            }
        }

        // 3. Admin & HR Roles Evaluation
        $adminRoles = ['Super Admin', 'Administrator', 'HR Manager'];
        foreach ($adminRoles as $role) {
            if ($rule->isRoleEnabled($role)) {
                $roleChannels = $rule->getRoleChannels($role);
                if (empty($roleChannels)) continue;

                $users = User::where('role', $role)->get();
                foreach ($users as $adminUser) {
                    if ($personInvolved && $adminUser->id === $personInvolved->id) {
                        continue;
                    }
                    if (isset($recipientsMap[$adminUser->id])) {
                        continue;
                    }

                    $recipientsMap[$adminUser->id] = [
                        'user'      => $adminUser,
                        'channels'  => $roleChannels,
                        'role_type' => strtolower(str_replace(' ', '_', $role)),
                    ];
                }
            }
        }

        // 4. Extra Recipients
        foreach ($extraRecipients as $extraUser) {
            if ($extraUser instanceof User && !isset($recipientsMap[$extraUser->id])) {
                $recipientsMap[$extraUser->id] = [
                    'user'      => $extraUser,
                    'channels'  => ['in_app', 'toast'],
                    'role_type' => 'custom',
                ];
            }
        }

        // 4.5. Enforce Individual Personal Notification Preferences
        $filteredRecipientsMap = [];
        foreach ($recipientsMap as $userId => $entry) {
            /** @var User $targetUser */
            $targetUser = $entry['user'];
            $channels   = $entry['channels'];

            $allowedChannels = [];
            foreach ($channels as $ch) {
                if ($targetUser->prefersNotificationChannel($eventKey, $ch)) {
                    $allowedChannels[] = $ch;
                }
            }

            if (!empty($allowedChannels)) {
                $entry['channels'] = $allowedChannels;
                $filteredRecipientsMap[$userId] = $entry;
            }
        }
        $recipientsMap = $filteredRecipientsMap;

        // 5. Execute Delivery
        $dispatchedCount = 0;
        $sender = self::getNotificationSender();
        $emailEnabled = self::isEmailDeliveryEnabled();

        $icon  = $data['icon'] ?? self::getEventIcon($eventKey);
        $color = $data['color'] ?? self::getEventColor($eventKey);

        foreach ($recipientsMap as $entry) {
            /** @var User $targetUser */
            $targetUser = $entry['user'];
            $channels   = $entry['channels'];
            $roleType   = $entry['role_type'];

            $hasInApp = in_array('in_app', $channels) || in_array('toast', $channels);
            $hasToast = in_array('toast', $channels);
            $hasEmail = in_array('email', $channels) && $emailEnabled;

            if ($hasInApp) {
                try {
                    $targetUser->notifications()->create([
                        'id'           => (string) Str::uuid(),
                        'type'         => 'App\\Notifications\\GenericNotification',
                        'data'         => [
                            'event_key'      => $eventKey,
                            'type'           => $data['type'] ?? $eventKey,
                            'title'          => $data['title'] ?? $rule->name,
                            'message'        => $data['message'] ?? '',
                            'link'           => $data['link'] ?? '#',
                            'category'       => $rule->category,
                            'icon'           => $icon,
                            'color'          => $color,
                            'toast'          => $hasToast,
                            'recipient_role' => $roleType,
                            'created_at'     => now()->toISOString(),
                        ],
                        'read_at'      => null,
                        'archived_at'  => null,
                    ]);
                    $dispatchedCount++;
                } catch (\Throwable $e) {
                    Log::warning("Failed to store notification for user {$targetUser->id}: " . $e->getMessage());
                }
            }

            if ($hasEmail && !empty($targetUser->email)) {
                try {
                    self::sendEmailNotification(
                        recipientEmail: $targetUser->email,
                        recipientName:  $targetUser->name,
                        subject:        $data['title'] ?? $rule->name,
                        body:           $data['message'] ?? '',
                        link:           $data['link'] ?? url('/dashboard'),
                        sender:         $sender,
                        userId:         $targetUser->id
                    );
                } catch (\Throwable $e) {
                    Log::warning("Failed to send notification email to {$targetUser->email}: " . $e->getMessage());
                }
            }
        }

        return [
            'dispatched' => $dispatchedCount,
            'recipients' => array_keys($recipientsMap),
            'status'     => 'success',
        ];
    }

    /**
     * Dispatch email using configured notification sender credentials.
     */
    public static function sendEmailNotification(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $body,
        ?string $link = null,
        ?array $sender = null,
        ?int $userId = null
    ): void {
        $sender = $sender ?: self::getNotificationSender();
        $appName = CompanySetting::get('company_name', config('app.name', 'ClockVerse'));

        $fullSubject = "[$appName] " . $subject;

        $content = "Hello {$recipientName},\n\n";
        $content .= "{$body}\n\n";
        if ($link && $link !== '#') {
            $content .= "You can view details here:\n" . (str_starts_with($link, 'http') ? $link : url($link)) . "\n\n";
        }
        $content .= "Best regards,\n{$sender['name']}";

        try {
            \App\Services\EmailService::applyRuntimeMailConfig();
            Mail::raw($content, function ($message) use ($recipientEmail, $recipientName, $fullSubject, $sender) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($fullSubject)
                    ->from($sender['address'], $sender['name']);

                if (!empty($sender['reply_to'])) {
                    $message->replyTo($sender['reply_to']);
                }
            });

            EmailLog::create([
                'user_id'         => $userId,
                'recipient'       => $recipientEmail,
                'sender'          => $sender['address'],
                'subject'         => $fullSubject,
                'body'            => $content,
                'folder'          => 'sent',
                'status'          => 'sent',
                'mailer_type'     => 'notification_smtp',
                'sent_at'         => now(),
                'last_attempt_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'user_id'         => $userId,
                'recipient'       => $recipientEmail,
                'sender'          => $sender['address'],
                'subject'         => $fullSubject,
                'body'            => $content,
                'folder'          => 'failed',
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
                'mailer_type'     => 'notification_smtp',
                'sent_at'         => null,
                'last_attempt_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Test notification sending email.
     */
    public static function sendTestEmail(string $recipientEmail): array
    {
        $sender = self::getNotificationSender();
        $appName = CompanySetting::get('company_name', config('app.name', 'ClockVerse'));

        $subject = "[$appName] Notification System Test Email";
        $body = "This is a test notification verifying that the configured sender email ({$sender['address']}) is working properly in ClockVerse.";

        try {
            self::sendEmailNotification(
                recipientEmail: $recipientEmail,
                recipientName:  'Test Recipient',
                subject:        'Notification System Test Email',
                body:           $body,
                link:           url('/dashboard'),
                sender:         $sender
            );

            return [
                'success' => true,
                'message' => "Test email successfully sent to {$recipientEmail} from {$sender['address']}.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Failed sending test email: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Map notification event to manager setting key.
     */
    public static function mapEventToManagerSettingKey(string $eventKey): ?string
    {
        return match ($eventKey) {
            'clock_in'                                => 'notify_clock_in',
            'clock_out'                               => 'notify_clock_out',
            'time_submitted'                          => 'notify_time_submitted',
            'time_off_submitted', 'time_off_decided'  => 'notify_time_off',
            'shift_assigned', 'shift_updated', 'shift_published' => 'notify_shift_assigned',
            'unusual_activity'                        => 'notify_unusual',
            'geofence_breach'                         => 'notify_geofence',
            default                                   => null,
        };
    }

    /**
     * Get aesthetic FontAwesome icon by event key.
     */
    public static function getEventIcon(string $eventKey): string
    {
        return match ($eventKey) {
            'shift_assigned', 'shift_updated', 'shift_published' => 'fa-solid fa-calendar-day',
            'clock_in'                 => 'fa-solid fa-right-to-bracket',
            'clock_out'                => 'fa-solid fa-right-from-bracket',
            'time_submitted'           => 'fa-solid fa-file-invoice',
            'timesheet_approved'       => 'fa-solid fa-circle-check',
            'timesheet_disapproved'    => 'fa-solid fa-circle-xmark',
            'time_off_submitted', 'time_off_decided' => 'fa-solid fa-plane-departure',
            'unusual_activity'         => 'fa-solid fa-triangle-exclamation',
            'geofence_breach'          => 'fa-solid fa-location-crosshairs',
            'system_alert'             => 'fa-solid fa-bullhorn',
            default                    => 'fa-solid fa-bell',
        };
    }

    /**
     * Get theme color by event key.
     */
    public static function getEventColor(string $eventKey): string
    {
        return match ($eventKey) {
            'shift_assigned', 'shift_updated', 'shift_published' => 'sky',
            'clock_in', 'timesheet_approved' => 'emerald',
            'clock_out', 'unusual_activity', 'geofence_breach' => 'amber',
            'timesheet_disapproved'    => 'rose',
            'time_off_submitted', 'time_off_decided' => 'purple',
            'time_submitted'           => 'indigo',
            default                    => 'slate',
        };
    }

    // --- Specialized Helpers for Common Events ---

    public static function notifyClockActivity(TimeLog $log, string $type = 'clock_in'): array
    {
        $employee = $log->employee;
        if (!$employee) return ['dispatched' => 0];

        $jobName  = $log->assignment?->name ?? 'General Work';
        $taskName = $log->task?->name ?? 'General Task';
        $timeStr  = now()->setTimezone($employee->getTimezone())->format('h:i A');

        $isClockIn = ($type === 'clock_in');
        $eventKey = $isClockIn ? 'clock_in' : 'clock_out';

        $title = $isClockIn
            ? "Clock In: {$employee->name}"
            : "Clock Out: {$employee->name}";

        $message = $isClockIn
            ? "{$employee->name} clocked in at {$timeStr} for {$jobName} ({$taskName})."
            : "{$employee->name} clocked out at {$timeStr}. Total logged: " . number_format(($log->duration_minutes ?: 0) / 60, 2) . " hrs.";

        return self::send(
            eventKey: $eventKey,
            data: [
                'title'   => $title,
                'message' => $message,
                'link'    => '/timesheets',
                'type'    => $eventKey,
            ],
            personInvolved: $employee
        );
    }

    public static function notifyShiftScheduled(Schedule $schedule): array
    {
        $employee = $schedule->employee;
        if (!$employee) return ['dispatched' => 0];

        $jobName   = $schedule->assignment?->name ?? 'Assigned Job';
        $dateStr   = $schedule->shift_date ? $schedule->shift_date->format('M d, Y') : 'Upcoming';
        $timeRange = substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5);

        return self::send(
            eventKey: 'shift_assigned',
            data: [
                'title'   => "Shift Assigned: {$dateStr}",
                'message' => "Shift scheduled for {$employee->name} on {$dateStr} ({$timeRange}) at {$jobName}.",
                'link'    => '/schedules',
                'type'    => 'shift_assigned',
            ],
            personInvolved: $employee
        );
    }
}
