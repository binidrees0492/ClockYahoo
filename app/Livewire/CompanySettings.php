<?php

namespace App\Livewire;

use App\Models\CompanySetting;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CompanySettings extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'general'; // general, branding, email_server, employee_emails, email_logs, navigation
    public $activeNavPages = [];

    // General
    public $companyName = 'ClockVerse';
    public $serverTimezone = 'UTC';
    public $locationPolicy = 'flag'; // 'none' | 'flag' | 'restrict'
    public $defaultGeofenceRadius = 200;
    public $weekStartDay = 0; // 0=Sunday, 1=Monday, 4=Thursday
    public $allowHrHardDelete = false;

    // Branding & Base Theme
    public $applicationBaseTheme = 'classic'; // 'classic' (ClockShark design) | 'modern' (Next-Gen React SaaS)
    public $modernHighContrast = true;
    public $modernRealtimeLoading = true;
    public $modernMicroAnimations = true;
    public $modernCustomIcons = true;

    public $companyTheme = 'light'; // 'light', 'dark', 'gradient', 'sage dark green'
    public $companyFont  = 'aptos'; // 'aptos', 'inter', 'system'
    public $newLogo = null;
    public $currentLogoPath = null;
    public $setAsFavicon = true;

    // Custom Font Creation (Super Admin)
    public $newFontName = '';
    public $newFontCss = '';
    public $newFontGoogleUrl = '';

    // Default Server Email Settings (SMTP)
    public $mailHost = '';
    public $mailPort = 587;
    public $mailEncryption = 'tls';
    public $mailUsername = '';
    public $mailPassword = '';
    public $mailFromAddress = '';
    public $mailFromName = '';

    // Default Server IMAP Settings
    public $imapHost = '';
    public $imapPort = 993;
    public $imapEncryption = 'ssl';
    public $imapUsername = '';
    public $imapPassword = '';

    // Employee Email Configuration
    public $selectedEmployeeId = null;
    public $empUseCustomEmail = false;
    public $empSmtpHost = '';
    public $empSmtpPort = 587;
    public $empSmtpEncryption = 'tls';
    public $empSmtpUsername = '';
    public $empSmtpPassword = '';
    public $empSmtpFromAddress = '';
    public $empSmtpFromName = '';
    public $empImapHost = '';
    public $empImapPort = 993;
    public $empImapEncryption = 'ssl';
    public $empImapUsername = '';
    public $empImapPassword = '';

    // Email Logs filter & viewer
    public $logSearch = '';
    public $logStatus = 'all'; // all, sent, failed
    public $viewingLog = null;

    // Connection test results
    public $testStatus = null;
    public $testMessage = null;

    // Toast
    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        if (!Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized access to Company Settings.');
        }

        // General
        $this->companyName           = CompanySetting::get('company_name', config('app.name', 'ClockVerse'));
        $this->serverTimezone        = CompanySetting::getServerTimezone();
        $this->locationPolicy        = CompanySetting::getLocationTrackingPolicy();
        $this->defaultGeofenceRadius = CompanySetting::getDefaultGeofenceRadius();
        $this->weekStartDay          = CompanySetting::getWeekStartDay();
        $this->allowHrHardDelete     = (bool) CompanySetting::get('allow_hr_hard_delete', false);

        // Branding & Base Theme
        $this->applicationBaseTheme  = CompanySetting::getBaseTheme();
        $this->modernHighContrast    = (bool) CompanySetting::get('modern_high_contrast', true);
        $this->modernRealtimeLoading = (bool) CompanySetting::get('modern_realtime_loading', true);
        $this->modernMicroAnimations = (bool) CompanySetting::get('modern_micro_animations', true);
        $this->modernCustomIcons     = (bool) CompanySetting::get('modern_custom_icons', true);

        $this->companyTheme          = CompanySetting::get('company_theme', 'light');
        $this->companyFont           = CompanySetting::get('company_font', 'aptos');
        $this->currentLogoPath       = CompanySetting::get('company_logo');
        $this->setAsFavicon          = (bool) CompanySetting::get('company_logo_as_favicon', true);

        // Server Email
        $this->mailHost              = CompanySetting::get('mail_host', config('mail.mailers.smtp.host', ''));
        $this->mailPort              = CompanySetting::get('mail_port', config('mail.mailers.smtp.port', 587));
        $this->mailEncryption        = CompanySetting::get('mail_encryption', config('mail.mailers.smtp.encryption', 'tls'));
        $this->mailUsername          = CompanySetting::get('mail_username', config('mail.mailers.smtp.username', ''));
        $this->mailPassword          = CompanySetting::get('mail_password', config('mail.mailers.smtp.password', ''));
        $this->mailFromAddress       = CompanySetting::get('mail_from_address', config('mail.from.address', ''));
        $this->mailFromName          = CompanySetting::get('mail_from_name', config('mail.from.name', 'ClockVerse'));

        $this->imapHost              = CompanySetting::get('imap_host', '');
        $this->imapPort              = CompanySetting::get('imap_port', 993);
        $this->imapEncryption        = CompanySetting::get('imap_encryption', 'ssl');
        $this->imapUsername          = CompanySetting::get('imap_username', '');
        $this->imapPassword          = CompanySetting::get('imap_password', '');

        // Navigation Active Pages
        $this->activeNavPages        = CompanySetting::getActiveNavPages();

        // Select first employee by default if any
        $firstEmployee = User::notSuperAdmin()->where('status', 'Active')->first();
        if ($firstEmployee) {
            $this->selectEmployee($firstEmployee->id);
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->testStatus = null;
        $this->testMessage = null;
    }

    public function saveGeneral()
    {
        $canManage = Auth::user()->isSuperAdmin() || Auth::user()->isAdmin();

        $rules = [
            'locationPolicy'        => 'required|in:none,flag,restrict',
            'defaultGeofenceRadius' => 'required|integer|min:10|max:10000',
            'weekStartDay'          => 'required|integer|in:0,1,2,3,4,5,6',
        ];

        if ($canManage) {
            $rules['companyName']    = 'required|string|max:100';
            $rules['serverTimezone'] = 'required|string|max:100';
        }

        $this->validate($rules);

        if ($canManage) {
            CompanySetting::set('company_name', $this->companyName);
            CompanySetting::set('server_timezone', $this->serverTimezone);
            @date_default_timezone_set($this->serverTimezone);
            config(['app.timezone' => $this->serverTimezone]);
        } else {
            // Keep existing values unchanged if not authorized
            $this->companyName    = CompanySetting::get('company_name', config('app.name', 'ClockVerse'));
            $this->serverTimezone = CompanySetting::getServerTimezone();
        }

        CompanySetting::set('location_tracking_policy', $this->locationPolicy);
        CompanySetting::set('default_geofence_radius', $this->defaultGeofenceRadius);
        CompanySetting::set('week_start_day', (int)$this->weekStartDay);

        if (Auth::user()?->isSuperAdmin()) {
            CompanySetting::set('allow_hr_hard_delete', (bool) $this->allowHrHardDelete);
        }

        $this->showToast('General company settings saved successfully.');
    }

    public function addCustomFont()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Only Administrators can register new fonts.');
        }

        $this->validate([
            'newFontName'      => 'required|string|min:2|max:50',
            'newFontCss'       => 'required|string|min:2|max:255',
            'newFontGoogleUrl' => 'nullable|url|max:500',
        ]);

        CompanySetting::addCustomFont(
            $this->newFontName,
            $this->newFontCss,
            $this->newFontGoogleUrl
        );

        $this->newFontName = '';
        $this->newFontCss = '';
        $this->newFontGoogleUrl = '';

        $this->showToast('Custom font registered successfully.');
    }

    public function removeCustomFont($fontId)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can remove custom fonts.');
        }

        CompanySetting::removeCustomFont($fontId);

        if ($this->companyFont === $fontId) {
            $this->companyFont = 'aptos';
            CompanySetting::set('company_font', 'aptos');
        }

        $this->showToast('Custom font removed successfully.');
    }

    public function switchBaseTheme(string $theme): void
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Only Administrators can alter application base layout theme.');
        }

        $theme = in_array(strtolower($theme), ['classic', 'modern']) ? strtolower($theme) : 'classic';
        $this->applicationBaseTheme = $theme;

        CompanySetting::setBaseTheme($theme);
        CompanySetting::set('modern_high_contrast', (bool) $this->modernHighContrast);
        CompanySetting::set('modern_realtime_loading', (bool) $this->modernRealtimeLoading);
        CompanySetting::set('modern_micro_animations', (bool) $this->modernMicroAnimations);
        CompanySetting::set('modern_custom_icons', (bool) $this->modernCustomIcons);

        $this->showToast("Application Base Theme updated to " . ucfirst($theme) . " layout & experience.");
        $this->dispatch('base-theme-switched', theme: $theme);
    }

    public function saveBranding()
    {
        $this->validate([
            'applicationBaseTheme' => 'required|in:classic,modern',
            'companyTheme'         => 'required|in:light,dark,gradient,sage dark green',
            'companyFont'          => 'nullable|string|max:50',
            'newLogo'              => 'nullable|image|max:3072', // max 3MB
        ]);

        if ($this->newLogo) {
            $path = $this->newLogo->store('company', 'public');
            $this->currentLogoPath = $path;
            CompanySetting::set('company_logo', $path);
            if ($this->setAsFavicon) {
                CompanySetting::set('company_favicon', $path);
            }
            $this->newLogo = null;
        }

        CompanySetting::setBaseTheme($this->applicationBaseTheme);
        CompanySetting::set('modern_high_contrast', (bool) $this->modernHighContrast);
        CompanySetting::set('modern_realtime_loading', (bool) $this->modernRealtimeLoading);
        CompanySetting::set('modern_micro_animations', (bool) $this->modernMicroAnimations);
        CompanySetting::set('modern_custom_icons', (bool) $this->modernCustomIcons);

        CompanySetting::set('company_theme', $this->companyTheme);
        CompanySetting::set('company_font', $this->companyFont);
        CompanySetting::set('company_logo_as_favicon', $this->setAsFavicon);

        $this->showToast('Branding, base theme, layout, and appearance updated successfully.');
    }

    public function saveServerEmail()
    {
        $this->validate([
            'mailHost'        => 'required|string|max:255',
            'mailPort'        => 'required|integer|min:1|max:65535',
            'mailEncryption'  => 'required|in:none,tls,ssl',
            'mailUsername'    => 'nullable|string|max:255',
            'mailPassword'    => 'nullable|string|max:255',
            'mailFromAddress' => 'nullable|email|max:255',
            'mailFromName'    => 'nullable|string|max:255',
            'imapHost'        => 'nullable|string|max:255',
            'imapPort'        => 'nullable|integer|min:1|max:65535',
            'imapEncryption'  => 'nullable|in:none,tls,ssl',
            'imapUsername'    => 'nullable|string|max:255',
            'imapPassword'    => 'nullable|string|max:255',
        ]);

        CompanySetting::set('mail_host', $this->mailHost);
        CompanySetting::set('mail_port', $this->mailPort);
        CompanySetting::set('mail_encryption', $this->mailEncryption);
        CompanySetting::set('mail_username', $this->mailUsername);
        CompanySetting::set('mail_password', $this->mailPassword);
        CompanySetting::set('mail_from_address', $this->mailFromAddress);
        CompanySetting::set('mail_from_name', $this->mailFromName);

        CompanySetting::set('imap_host', $this->imapHost);
        CompanySetting::set('imap_port', $this->imapPort);
        CompanySetting::set('imap_encryption', $this->imapEncryption);
        CompanySetting::set('imap_username', $this->imapUsername);
        CompanySetting::set('imap_password', $this->imapPassword);

        $this->showToast('Default server email & IMAP settings saved successfully.');
    }

    public function testServerSmtp()
    {
        $result = EmailService::testSmtpConnection([
            'host'       => $this->mailHost,
            'port'       => $this->mailPort,
            'username'   => $this->mailUsername,
            'password'   => $this->mailPassword,
            'encryption' => $this->mailEncryption,
        ]);

        $this->testStatus = $result['success'] ? 'success' : 'error';
        $this->testMessage = 'SMTP: ' . $result['message'];
    }

    public function testServerImap()
    {
        $result = EmailService::testImapConnection([
            'host'       => $this->imapHost,
            'port'       => $this->imapPort,
            'username'   => $this->imapUsername,
            'password'   => $this->imapPassword,
            'encryption' => $this->imapEncryption,
        ]);

        $this->testStatus = $result['success'] ? 'success' : 'error';
        $this->testMessage = 'IMAP: ' . $result['message'];
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedEmployeeId = (int) $employeeId;
        $employee = User::find($this->selectedEmployeeId);
        if (!$employee) return;

        $this->empUseCustomEmail  = (bool) $employee->use_custom_email;
        $this->empSmtpHost        = $employee->smtp_host ?: $this->mailHost;
        $this->empSmtpPort        = $employee->smtp_port ?: $this->mailPort;
        $this->empSmtpEncryption  = $employee->smtp_encryption ?: $this->mailEncryption;
        $this->empSmtpUsername    = $employee->smtp_username ?: $employee->email;
        $this->empSmtpPassword    = $employee->smtp_password ?: '';
        $this->empSmtpFromAddress = $employee->smtp_from_address ?: $employee->email;
        $this->empSmtpFromName    = $employee->smtp_from_name ?: $employee->name;
        $this->empImapHost        = $employee->imap_host ?: $this->imapHost;
        $this->empImapPort        = $employee->imap_port ?: $this->imapPort;
        $this->empImapEncryption  = $employee->imap_encryption ?: $this->imapEncryption;
        $this->empImapUsername    = $employee->imap_username ?: $employee->email;
        $this->empImapPassword    = $employee->imap_password ?: '';

        $this->testStatus = null;
        $this->testMessage = null;
    }

    public function saveEmployeeEmail()
    {
        if (!$this->selectedEmployeeId) return;

        $employee = User::findOrFail($this->selectedEmployeeId);

        $employee->update([
            'use_custom_email'  => $this->empUseCustomEmail,
            'smtp_host'         => $this->empSmtpHost,
            'smtp_port'         => $this->empSmtpPort,
            'smtp_encryption'   => $this->empSmtpEncryption,
            'smtp_username'     => $this->empSmtpUsername,
            'smtp_password'     => $this->empSmtpPassword,
            'smtp_from_address' => $this->empSmtpFromAddress,
            'smtp_from_name'    => $this->empSmtpFromName,
            'imap_host'         => $this->empImapHost,
            'imap_port'         => $this->empImapPort,
            'imap_encryption'   => $this->empImapEncryption,
            'imap_username'     => $this->empImapUsername,
            'imap_password'     => $this->empImapPassword,
        ]);

        $this->showToast("Email account credentials updated for {$employee->name}.");
    }

    public function testEmployeeSmtp()
    {
        $result = EmailService::testSmtpConnection([
            'host'       => $this->empSmtpHost,
            'port'       => $this->empSmtpPort,
            'username'   => $this->empSmtpUsername,
            'password'   => $this->empSmtpPassword,
            'encryption' => $this->empSmtpEncryption,
        ]);

        $this->testStatus = $result['success'] ? 'success' : 'error';
        $this->testMessage = "Employee SMTP: " . $result['message'];
    }

    public function testEmployeeImap()
    {
        $result = EmailService::testImapConnection([
            'host'       => $this->empImapHost,
            'port'       => $this->empImapPort,
            'username'   => $this->empImapUsername,
            'password'   => $this->empImapPassword,
            'encryption' => $this->empImapEncryption,
        ]);

        $this->testStatus = $result['success'] ? 'success' : 'error';
        $this->testMessage = "Employee IMAP: " . $result['message'];
    }

    public function resendEmail($logId)
    {
        $log = EmailLog::findOrFail($logId);
        $success = $log->resend();

        if ($success) {
            $this->showToast("Email #{$log->id} resent successfully to {$log->recipient}.");
        } else {
            $this->showToast("Failed to resend Email #{$log->id}: {$log->error_message}", 'error');
        }
    }

    public function viewLog($logId)
    {
        $this->viewingLog = EmailLog::find($logId);
    }

    public function closeViewLog()
    {
        $this->viewingLog = null;
    }

    public function showToast($message, $type = 'success')
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function toggleNavPage(string $key)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized. Only Administrators can manage active navigation pages.');
        }

        $current = $this->activeNavPages[$key] ?? true;
        $this->activeNavPages[$key] = !$current;
        CompanySetting::setActiveNavPages($this->activeNavPages);
        $statusStr = $this->activeNavPages[$key] ? 'enabled' : 'disabled';
        $this->showToast("Page '{$key}' {$statusStr} successfully.");
    }

    public function enableAllNavPages()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $all = CompanySetting::getAllNavPages();
        foreach (array_keys($all) as $key) {
            $this->activeNavPages[$key] = true;
        }
        CompanySetting::setActiveNavPages($this->activeNavPages);
        $this->showToast('All navigation pages and links have been enabled.');
    }

    public function disableAllNavPages()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        // Keep essential root links active (dashboard, employees, company settings)
        $essentials = ['dashboard', 'admin_employees'];
        $all = CompanySetting::getAllNavPages();
        foreach (array_keys($all) as $key) {
            $this->activeNavPages[$key] = in_array($key, $essentials);
        }
        CompanySetting::setActiveNavPages($this->activeNavPages);
        $this->showToast('Non-essential pages disabled. Core dashboard & employee directory remain active.');
    }

    public function saveNavigationPages()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        CompanySetting::setActiveNavPages($this->activeNavPages);
        $this->showToast('Navigation and active page configuration saved successfully.');
    }

    public function render()
    {
        $timezones = [
            'UTC'              => 'UTC - Universal Coordinated Time',
            'Asia/Karachi'     => 'PKT - Pakistan Standard Time (UTC+05:00)',
            'America/New_York' => 'EST/EDT - US Eastern Time',
            'America/Chicago'  => 'CST/CDT - US Central Time',
            'America/Denver'   => 'MST/MDT - US Mountain Time',
            'America/Los_Angeles' => 'PST/PDT - US Pacific Time',
            'Europe/London'    => 'GMT/BST - London, UK',
            'Europe/Berlin'    => 'CET/CEST - Berlin / Amsterdam / Paris',
            'Asia/Dubai'       => 'GST - Dubai, UAE',
            'Asia/Kolkata'     => 'IST - India Standard Time',
            'Asia/Singapore'   => 'SGT - Singapore / Hong Kong',
            'Asia/Tokyo'       => 'JST - Tokyo, Japan',
            'Australia/Sydney' => 'AEST/AEDT - Sydney, Australia',
        ];

        $employees = User::notSuperAdmin()->orderBy('name')->get();

        $logsQuery = EmailLog::query()->latest();

        if ($this->logStatus === 'failed') {
            $logsQuery->where('status', 'failed');
        } elseif ($this->logStatus === 'sent') {
            $logsQuery->where('status', 'sent');
        }

        if (!empty($this->logSearch)) {
            $searchTerm = '%' . $this->logSearch . '%';
            $logsQuery->where(function ($q) use ($searchTerm) {
                $q->where('recipient', 'like', $searchTerm)
                  ->orWhere('subject', 'like', $searchTerm)
                  ->orWhere('sender', 'like', $searchTerm);
            });
        }

        $emailLogs = $logsQuery->paginate(15);

        return view('livewire.company-settings', [
            'timezones'      => $timezones,
            'employees'      => $employees,
            'emailLogs'      => $emailLogs,
            'availableFonts' => CompanySetting::getAvailableFonts(),
            'allNavPages'    => CompanySetting::getAllNavPages(),
            'isSuperAdmin'   => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin(),
        ])->layout('layouts.app');
    }
}
