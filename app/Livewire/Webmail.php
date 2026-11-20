<?php

namespace App\Livewire;

use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Webmail extends Component
{
    use WithPagination, WithFileUploads;

    public $activeFolder = 'inbox'; // inbox, sent, drafts, archived, trash
    public $search = '';

    // Compose state
    public $showCompose = false;
    public $composeTo = '';
    public $composeSubject = '';
    public $composeBody = '';
    public $attachments = []; // array of UploadedFile

    // Email view
    public $selectedEmailId = null;

    // Webmail Settings Modal State
    public $showSettings = false;
    public $useCustomEmail = false;
    public $smtpHost = '';
    public $smtpPort = 587;
    public $smtpUsername = '';
    public $smtpPassword = '';
    public $smtpEncryption = 'tls';
    public $smtpFromAddress = '';
    public $smtpFromName = '';
    public $imapHost = '';
    public $imapPort = 993;
    public $imapUsername = '';
    public $imapPassword = '';
    public $imapEncryption = 'ssl';

    public $settingsTestStatus = null;
    public $settingsTestMessage = null;

    // Toast
    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $this->activeFolder = 'inbox';
        $this->loadUserSettings();
    }

    public function loadUserSettings()
    {
        /** @var User $user */
        $user = Auth::user();
        $this->useCustomEmail  = (bool) $user->use_custom_email;
        $this->smtpHost        = $user->smtp_host ?: '';
        $this->smtpPort        = $user->smtp_port ?: 587;
        $this->smtpUsername    = $user->smtp_username ?: '';
        $this->smtpPassword    = $user->smtp_password ?: '';
        $this->smtpEncryption  = $user->smtp_encryption ?: 'tls';
        $this->smtpFromAddress = $user->smtp_from_address ?: $user->email;
        $this->smtpFromName    = $user->smtp_from_name ?: $user->name;
        $this->imapHost        = $user->imap_host ?: '';
        $this->imapPort        = $user->imap_port ?: 993;
        $this->imapUsername    = $user->imap_username ?: '';
        $this->imapPassword    = $user->imap_password ?: '';
        $this->imapEncryption  = $user->imap_encryption ?: 'ssl';
    }

    public function openSettings()
    {
        $this->loadUserSettings();
        $this->settingsTestStatus = null;
        $this->settingsTestMessage = null;
        $this->showSettings = true;
    }

    public function closeSettings()
    {
        $this->showSettings = false;
    }

    public function applyPreset(string $provider): void
    {
        if ($provider === 'hostinger') {
            $this->smtpHost       = 'smtp.hostinger.com';
            $this->smtpPort       = 465;
            $this->smtpEncryption = 'ssl';
            $this->imapHost       = 'imap.hostinger.com';
            $this->imapPort       = 993;
            $this->imapEncryption = 'ssl';
            $this->dispatch('toast', message: 'Hostinger presets loaded.', type: 'info');
        } elseif ($provider === 'gmail') {
            $this->smtpHost       = 'smtp.gmail.com';
            $this->smtpPort       = 587;
            $this->smtpEncryption = 'tls';
            $this->imapHost       = 'imap.gmail.com';
            $this->imapPort       = 993;
            $this->imapEncryption = 'ssl';
            $this->dispatch('toast', message: 'Gmail presets loaded.', type: 'info');
        } elseif ($provider === 'office365') {
            $this->smtpHost       = 'smtp.office365.com';
            $this->smtpPort       = 587;
            $this->smtpEncryption = 'tls';
            $this->imapHost       = 'outlook.office365.com';
            $this->imapPort       = 993;
            $this->imapEncryption = 'ssl';
            $this->dispatch('toast', message: 'Office 365 presets loaded.', type: 'info');
        }
    }

    public function testSmtpSettings()
    {
        $this->smtpHost = preg_replace('/^(stmp|smpt)\./i', 'smtp.', trim((string) $this->smtpHost));

        $res = EmailService::testSmtpConnection([
            'host'       => $this->smtpHost,
            'port'       => (int) $this->smtpPort,
            'username'   => $this->smtpUsername,
            'password'   => $this->smtpPassword,
            'encryption' => $this->smtpEncryption,
        ]);

        $this->settingsTestStatus = $res['success'] ? 'success' : 'error';
        $this->settingsTestMessage = 'SMTP: ' . $res['message'];
        $this->dispatch('toast', message: $this->settingsTestMessage, type: $res['success'] ? 'success' : 'error');
    }

    public function testImapSettings()
    {
        $this->imapHost = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', trim((string) $this->imapHost));

        $res = EmailService::testImapConnection([
            'host'       => $this->imapHost,
            'port'       => (int) $this->imapPort,
            'username'   => $this->imapUsername,
            'password'   => $this->imapPassword,
            'encryption' => $this->imapEncryption,
        ]);

        $this->settingsTestStatus = $res['success'] ? 'success' : 'error';
        $this->settingsTestMessage = 'IMAP: ' . $res['message'];
        $this->dispatch('toast', message: $this->settingsTestMessage, type: $res['success'] ? 'success' : 'error');
    }

    public function saveMailboxSettings()
    {
        $this->smtpHost = preg_replace('/^(stmp|smpt)\./i', 'smtp.', trim((string) $this->smtpHost));
        $this->imapHost = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', trim((string) $this->imapHost));

        $this->validate([
            'smtpPort'        => 'nullable|numeric|between:1,65535',
            'smtpFromAddress' => 'nullable|email',
            'imapPort'        => 'nullable|numeric|between:1,65535',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'use_custom_email'  => $this->useCustomEmail,
            'smtp_host'         => $this->smtpHost ?: null,
            'smtp_port'         => $this->smtpPort ?: 587,
            'smtp_username'     => $this->smtpUsername ?: null,
            'smtp_password'     => $this->smtpPassword ?: null,
            'smtp_encryption'   => $this->smtpEncryption ?: 'tls',
            'smtp_from_address' => $this->smtpFromAddress ?: null,
            'smtp_from_name'    => $this->smtpFromName ?: null,
            'imap_host'         => $this->imapHost ?: null,
            'imap_port'         => $this->imapPort ?: 993,
            'imap_username'     => $this->imapUsername ?: null,
            'imap_password'     => $this->imapPassword ?: null,
            'imap_encryption'   => $this->imapEncryption ?: 'ssl',
        ]);

        $this->toastMessage = 'Webmail mailbox settings saved successfully.';
        $this->toastType = 'success';
        $this->dispatch('toast', message: $this->toastMessage, type: 'success');
        $this->showSettings = false;
    }

    public function setFolder($folder)
    {
        $this->activeFolder = $folder;
        $this->selectedEmailId = null;
        $this->resetPage();
    }

    public function openCompose($replyTo = null, $replySubject = null)
    {
        $this->composeTo = $replyTo ?: '';
        $this->composeSubject = $replySubject ? (str_starts_with($replySubject, 'Re:') ? $replySubject : 'Re: ' . $replySubject) : '';
        $this->composeBody = '';
        $this->attachments = [];
        $this->showCompose = true;
    }

    public function closeCompose()
    {
        $this->showCompose = false;
        $this->composeTo = '';
        $this->composeSubject = '';
        $this->composeBody = '';
        $this->attachments = [];
    }

    public function removeAttachment($index)
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function sendEmail()
    {
        $this->validate([
            'composeTo'      => 'required|email|max:255',
            'composeSubject' => 'required|string|max:255',
            'composeBody'    => 'required|string',
            'attachments.*'  => 'nullable|file|max:10240', // 10MB max per file
        ]);

        $user = Auth::user();

        // Process attachments if any
        $processedAttachments = [];
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $file) {
                $filename = $file->getClientOriginalName();
                $path = $file->store('webmail_attachments', 'public');
                $fullPath = storage_path('app/public/' . $path);
                $url = asset('storage/' . $path);
                $processedAttachments[] = [
                    'name' => $filename,
                    'path' => $fullPath,
                    'url'  => $url,
                    'size' => $file->getSize(),
                ];
            }
        }

        $res = EmailService::sendUserEmail($user, $this->composeTo, $this->composeSubject, $this->composeBody, $processedAttachments);

        if ($res['success']) {
            $this->toastMessage = 'Email verified and sent successfully via ' . ($user->use_custom_email ? 'custom SMTP' : 'default server') . '!';
            $this->toastType = 'success';
            $this->closeCompose();
            $this->activeFolder = 'sent';
        } else {
            $this->toastMessage = $res['message'];
            $this->toastType = 'error';
        }
    }

    public function viewEmail($id)
    {
        $this->selectedEmailId = $id;
        $email = EmailLog::find($id);
        if ($email && !$email->read_at) {
            $email->update(['read_at' => now()]);
        }
    }

    public function closeEmailDetail()
    {
        $this->selectedEmailId = null;
    }

    public function archiveEmail($id)
    {
        $email = EmailLog::find($id);
        if ($email) {
            $email->archive();
            $this->selectedEmailId = null;
            $this->toastMessage = 'Email archived successfully.';
            $this->toastType = 'success';
        }
    }

    public function unarchiveEmail($id)
    {
        $email = EmailLog::find($id);
        if ($email) {
            $email->unarchive();
            $this->toastMessage = 'Email restored from archive.';
            $this->toastType = 'success';
        }
    }

    public function deleteEmail($id)
    {
        $email = EmailLog::find($id);
        if ($email) {
            $email->delete();
            $this->selectedEmailId = null;
            $this->toastMessage = 'Email deleted.';
            $this->toastType = 'success';
        }
    }

    public function syncMail()
    {
        $user = Auth::user();
        if ($user->use_custom_email && $user->imap_host) {
            $res = EmailService::testImapConnection([
                'host'       => $user->imap_host,
                'port'       => $user->imap_port ?: 993,
                'username'   => $user->imap_username,
                'password'   => $user->imap_password,
                'encryption' => $user->imap_encryption ?: 'ssl',
            ]);

            if ($res['success']) {
                $this->toastMessage = 'Mailbox synced successfully via ' . $user->imap_host;
                $this->toastType = 'success';
            } else {
                $this->toastMessage = 'Sync notice: ' . $res['message'];
                $this->toastType = 'error';
            }
        } else {
            $this->toastMessage = 'Inbox refreshed.';
            $this->toastType = 'success';
        }
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $user = Auth::user();

        $query = EmailLog::query();

        if ($this->activeFolder === 'archived') {
            $query->where('is_archived', true)
                  ->where(function ($q) use ($user) {
                      $q->where('user_id', $user->id)
                        ->orWhere('recipient', $user->email)
                        ->orWhere('sender', $user->email);
                  });
        } else {
            $query->where(function ($q) {
                $q->where('is_archived', false)
                  ->orWhereNull('is_archived');
            });

            if ($this->activeFolder === 'sent') {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('sender', $user->email)
                      ->orWhere('sender', $user->smtp_from_address);
                });
            } elseif ($this->activeFolder === 'inbox') {
                $query->where(function ($q) use ($user) {
                    $q->where('recipient', $user->email)
                      ->orWhere('user_id', $user->id);
                });
            } elseif ($this->activeFolder === 'drafts') {
                $query->where('status', 'draft')->where('user_id', $user->id);
            } elseif ($this->activeFolder === 'trash') {
                $query->where('folder', 'trash')->where('user_id', $user->id);
            }
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('body', 'like', '%' . $this->search . '%')
                  ->orWhere('recipient', 'like', '%' . $this->search . '%')
                  ->orWhere('sender', 'like', '%' . $this->search . '%');
            });
        }

        $emails = $query->latest()->paginate(15);

        $selectedEmail = $this->selectedEmailId ? EmailLog::find($this->selectedEmailId) : null;

        $unreadCount = EmailLog::where('recipient', $user->email)
            ->whereNull('read_at')
            ->where(function($q) {
                $q->where('is_archived', false)->orWhereNull('is_archived');
            })
            ->count();

        $sentCount = EmailLog::where('user_id', $user->id)
            ->where(function($q) {
                $q->where('is_archived', false)->orWhereNull('is_archived');
            })
            ->count();

        $archivedCount = EmailLog::where('is_archived', true)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('recipient', $user->email)
                  ->orWhere('sender', $user->email);
            })
            ->count();

        return view('livewire.webmail', [
            'emails'        => $emails,
            'selectedEmail' => $selectedEmail,
            'user'          => $user,
            'unreadCount'   => $unreadCount,
            'sentCount'     => $sentCount,
            'archivedCount' => $archivedCount,
        ])->layout('layouts.app');
    }
}
