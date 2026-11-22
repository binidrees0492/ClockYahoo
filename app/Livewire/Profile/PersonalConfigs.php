<?php

namespace App\Livewire\Profile;

use App\Models\CompanySetting;
use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PersonalConfigs extends Component
{
    // Regional & Notifications
    public $timezone = 'Asia/Karachi';
    public $notification_channel_preference = 'both';
    public $detailed_notification_settings = [];

    // Theme & Font
    public $theme_preference = 'light';
    public $font_preference = 'opensans';

    // Email Configs
    public $use_custom_email = false;
    public $smtp_host = '';
    public $smtp_port = 587;
    public $smtp_encryption = 'tls';
    public $smtp_username = '';
    public $smtp_password = '';
    public $smtp_from_address = '';
    public $smtp_from_name = '';

    public $imap_host = '';
    public $imap_port = 993;
    public $imap_encryption = 'ssl';
    public $imap_username = '';
    public $imap_password = '';

    // Test & Validation State
    public $validationStatus = null;
    public $validationMessage = null;

    // Toast
    public $toastMessage = null;
    public $toastType = 'success';

    // Active tab in personal configs
    public $activeTab = 'theme'; // 'theme', 'email', 'security', 'all'

    public function mount($activeTab = 'theme')
    {
        $this->activeTab = $activeTab;
        $user = Auth::user();

        $this->timezone = $user->timezone ?: CompanySetting::getServerTimezone();
        $this->notification_channel_preference = $user->notification_channel_preference ?: 'both';
        $this->detailed_notification_settings = $user->detailed_notification_settings ?: [
            'shift_assigned'   => ['bell' => 1, 'email' => 1],
            'time_approval'    => ['bell' => 1, 'email' => 1],
            'unusual_activity' => ['bell' => 1, 'email' => 1],
            'time_submitted'   => ['bell' => 1, 'email' => 0],
        ];

        $this->theme_preference = $user->theme_preference ?: CompanySetting::get('company_theme', 'light');
        $this->font_preference  = $user->font_preference ?: CompanySetting::get('company_font', 'opensans');

        $this->use_custom_email  = (bool) $user->use_custom_email;
        $this->smtp_host         = $user->smtp_host ?: CompanySetting::get('mail_host', '');
        $this->smtp_port         = $user->smtp_port ?: CompanySetting::get('mail_port', 587);
        $this->smtp_encryption   = $user->smtp_encryption ?: CompanySetting::get('mail_encryption', 'tls');
        $this->smtp_username     = $user->smtp_username ?: $user->email;
        $this->smtp_password     = $user->smtp_password ?: '';
        $this->smtp_from_address = $user->smtp_from_address ?: $user->email;
        $this->smtp_from_name    = $user->smtp_from_name ?: $user->name;

        $this->imap_host         = $user->imap_host ?: CompanySetting::get('imap_host', '');
        $this->imap_port         = $user->imap_port ?: CompanySetting::get('imap_port', 993);
        $this->imap_encryption   = $user->imap_encryption ?: CompanySetting::get('imap_encryption', 'ssl');
        $this->imap_username     = $user->imap_username ?: $user->email;
        $this->imap_password     = $user->imap_password ?: '';
    }

    public function applyPreset(string $provider): void
    {
        if ($provider === 'hostinger') {
            $this->smtp_host       = 'smtp.hostinger.com';
            $this->smtp_port       = 465;
            $this->smtp_encryption = 'ssl';
            $this->imap_host       = 'imap.hostinger.com';
            $this->imap_port       = 993;
            $this->imap_encryption = 'ssl';
            $this->dispatch('toast', message: 'Hostinger presets applied! Enter your Hostinger email address as username.', type: 'info');
        } elseif ($provider === 'gmail') {
            $this->smtp_host       = 'smtp.gmail.com';
            $this->smtp_port       = 587;
            $this->smtp_encryption = 'tls';
            $this->imap_host       = 'imap.gmail.com';
            $this->imap_port       = 993;
            $this->imap_encryption = 'ssl';
            $this->dispatch('toast', message: 'Gmail presets applied! Use a 16-character App Password.', type: 'info');
        } elseif ($provider === 'office365') {
            $this->smtp_host       = 'smtp.office365.com';
            $this->smtp_port       = 587;
            $this->smtp_encryption = 'tls';
            $this->imap_host       = 'outlook.office365.com';
            $this->imap_port       = 993;
            $this->imap_encryption = 'ssl';
            $this->dispatch('toast', message: 'Office 365 presets applied.', type: 'info');
        }
    }

    public function testAndValidateSmtp()
    {
        $this->smtp_host = preg_replace('/^(stmp|smpt)\./i', 'smtp.', trim($this->smtp_host));

        $this->validate([
            'smtp_host'       => 'required|string|max:255',
            'smtp_port'       => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:none,tls,ssl',
            'smtp_username'   => 'nullable|string|max:255',
            'smtp_password'   => 'nullable|string|max:255',
        ]);

        $result = EmailService::testSmtpConnection([
            'host'       => $this->smtp_host,
            'port'       => $this->smtp_port,
            'username'   => $this->smtp_username,
            'password'   => $this->smtp_password,
            'encryption' => $this->smtp_encryption,
        ]);

        $this->validationStatus = $result['success'] ? 'success' : 'error';
        $this->validationMessage = 'SMTP Validation: ' . $result['message'];
        $this->dispatch('toast', message: $this->validationMessage, type: $result['success'] ? 'success' : 'error');
    }

    public function testAndValidateImap()
    {
        $this->imap_host = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', trim($this->imap_host));

        $this->validate([
            'imap_host'       => 'required|string|max:255',
            'imap_port'       => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:none,tls,ssl',
            'imap_username'   => 'nullable|string|max:255',
            'imap_password'   => 'nullable|string|max:255',
        ]);

        $result = EmailService::testImapConnection([
            'host'       => $this->imap_host,
            'port'       => $this->imap_port,
            'username'   => $this->imap_username,
            'password'   => $this->imap_password,
            'encryption' => $this->imap_encryption,
        ]);

        $this->validationStatus = $result['success'] ? 'success' : 'error';
        $this->validationMessage = 'IMAP Validation: ' . $result['message'];
        $this->dispatch('toast', message: $this->validationMessage, type: $result['success'] ? 'success' : 'error');
    }

    public function saveTheme()
    {
        $this->validate([
            'timezone'                        => 'required|string|max:100',
            'theme_preference'                => 'required|in:light,dark,gradient,sage dark green',
            'font_preference'                 => 'nullable|string|max:50',
            'notification_channel_preference' => 'required|in:both,bell_only,email_only',
        ]);

        Auth::user()->update([
            'timezone'                        => $this->timezone,
            'theme_preference'                => $this->theme_preference,
            'font_preference'                 => $this->font_preference,
            'notification_channel_preference' => $this->notification_channel_preference,
            'detailed_notification_settings'  => $this->detailed_notification_settings,
        ]);

        $this->toastMessage = 'Theme, font & notification preferences saved successfully.';
        $this->toastType = 'success';
        $this->dispatch('form-saved');
    }

    public function saveEmail()
    {
        $this->smtp_host = preg_replace('/^(stmp|smpt)\./i', 'smtp.', trim($this->smtp_host));
        $this->imap_host = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', trim($this->imap_host));

        $this->validate([
            'smtp_port'         => 'nullable|integer|min:1|max:65535',
            'imap_port'         => 'nullable|integer|min:1|max:65535',
            'smtp_from_address' => 'nullable|email|max:255',
        ]);

        Auth::user()->update([
            'use_custom_email'  => $this->use_custom_email,
            'smtp_host'         => $this->smtp_host,
            'smtp_port'         => $this->smtp_port,
            'smtp_encryption'   => $this->smtp_encryption,
            'smtp_username'     => $this->smtp_username,
            'smtp_password'     => $this->smtp_password,
            'smtp_from_address' => $this->smtp_from_address,
            'smtp_from_name'    => $this->smtp_from_name,
            'imap_host'         => $this->imap_host,
            'imap_port'         => $this->imap_port,
            'imap_encryption'   => $this->imap_encryption,
            'imap_username'     => $this->imap_username,
            'imap_password'     => $this->imap_password,
        ]);

        $this->toastMessage = 'Email and mailbox credentials saved successfully.';
        $this->toastType = 'success';
        $this->dispatch('toast', message: $this->toastMessage, type: 'success');
        $this->dispatch('form-saved');
    }

    public function deletePasskey($id)
    {
        Auth::user()->webAuthnCredentials()->where('id', $id)->delete();
        $this->toastMessage = 'Passkey removed.';
        $this->toastType = 'success';
        $this->dispatch('toast', message: $this->toastMessage, type: 'success');
    }

    public function save()
    {
        $this->smtp_host = preg_replace('/^(stmp|smpt)\./i', 'smtp.', trim($this->smtp_host));
        $this->imap_host = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', trim($this->imap_host));

        $this->validate([
            'timezone'                        => 'required|string|max:100',
            'theme_preference'                => 'required|in:light,dark,gradient,sage dark green',
            'notification_channel_preference' => 'required|in:both,bell_only,email_only',
            'smtp_port'                       => 'nullable|integer|min:1|max:65535',
            'imap_port'                       => 'nullable|integer|min:1|max:65535',
            'smtp_from_address'               => 'nullable|email|max:255',
        ]);

        $user = Auth::user();

        $user->update([
            'timezone'                        => $this->timezone,
            'theme_preference'                => $this->theme_preference,
            'font_preference'                 => $this->font_preference,
            'notification_channel_preference' => $this->notification_channel_preference,
            'detailed_notification_settings'  => $this->detailed_notification_settings,
            'use_custom_email'                => $this->use_custom_email,
            'smtp_host'                       => $this->smtp_host,
            'smtp_port'                       => $this->smtp_port,
            'smtp_encryption'                 => $this->smtp_encryption,
            'smtp_username'                   => $this->smtp_username,
            'smtp_password'                   => $this->smtp_password,
            'smtp_from_address'               => $this->smtp_from_address,
            'smtp_from_name'                  => $this->smtp_from_name,
            'imap_host'                       => $this->imap_host,
            'imap_port'                       => $this->imap_port,
            'imap_encryption'                 => $this->imap_encryption,
            'imap_username'                   => $this->imap_username,
            'imap_password'                   => $this->imap_password,
        ]);

        $this->toastMessage = 'Personal configurations, email settings & theme saved successfully.';
        $this->toastType = 'success';
        $this->dispatch('toast', message: $this->toastMessage, type: 'success');
        $this->dispatch('form-saved');
    }

    public function markNotificationRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllNotificationsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->toastMessage = 'All notifications marked as read.';
    }

    public function viewEmailDetails($logId)
    {
        $this->viewingEmail = EmailLog::where(function ($q) {
            $q->where('user_id', Auth::id())
              ->orWhere('recipient', Auth::user()->email);
        })->find($logId);
    }

    public function closeViewEmail()
    {
        $this->viewingEmail = null;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $user = Auth::user();

        $timezones = [
            'Asia/Karachi'     => 'PKT - Pakistan Time (UTC+05:00)',
            'America/New_York' => 'EST/EDT - US Eastern Time',
            'America/Chicago'  => 'CST/CDT - US Central Time',
            'America/Denver'   => 'MST/MDT - US Mountain Time',
            'America/Los_Angeles' => 'PST/PDT - US Pacific Time',
            'UTC'              => 'UTC - Universal Coordinated Time',
            'Europe/London'    => 'GMT/BST - London, UK',
            'Europe/Berlin'    => 'CET/CEST - Berlin / Paris / Amsterdam',
            'Asia/Dubai'       => 'GST - Dubai, UAE (UTC+04:00)',
            'Asia/Kolkata'     => 'IST - India Standard Time (UTC+05:30)',
            'Asia/Singapore'   => 'SGT - Singapore / Hong Kong (UTC+08:00)',
            'Asia/Tokyo'       => 'JST - Tokyo, Japan (UTC+09:00)',
            'Australia/Sydney' => 'AEST/AEDT - Sydney, Australia',
        ];

        $notifications = $user->notifications()->latest()->take(20)->get();

        $userEmailLogs = EmailLog::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('recipient', $user->email);
        })->latest()->take(20)->get();

        $passkeys = $user->webAuthnCredentials()->latest()->get();

        return view('livewire.profile.personal-configs', [
            'timezones'      => $timezones,
            'availableFonts' => CompanySetting::getAvailableFonts(),
            'notifications'  => $notifications,
            'userEmailLogs'  => $userEmailLogs,
            'passkeys'       => $passkeys,
        ]);
    }
}
