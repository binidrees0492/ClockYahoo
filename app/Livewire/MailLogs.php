<?php

namespace App\Livewire;

use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MailLogs extends Component
{
    use WithPagination;

    public $statusFilter = 'all'; // all, sent, failed, pending
    public $search = '';
    public $selectedUserId = 'all';

    public $viewingEmail = null;
    public $toastMessage = null;
    public $toastType = 'success';

    public function filterStatus($status)
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function viewDetails($id)
    {
        $this->viewingEmail = EmailLog::findOrFail($id);
    }

    public function closeDetails()
    {
        $this->viewingEmail = null;
    }

    public function resend($id)
    {
        $log = EmailLog::findOrFail($id);

        $targetUser = $log->user ?: Auth::user();
        $res = EmailService::sendUserEmail($targetUser, $log->recipient, $log->subject, $log->body ?: 'Re-sent content');

        if ($res['success']) {
            $log->update([
                'status'          => 'sent',
                'retry_count'     => $log->retry_count + 1,
                'last_attempt_at' => now(),
                'error_message'   => null,
            ]);
            $this->toastMessage = "Email re-sent successfully to {$log->recipient}.";
            $this->toastType = 'success';
        } else {
            $log->update([
                'status'          => 'failed',
                'retry_count'     => $log->retry_count + 1,
                'last_attempt_at' => now(),
                'error_message'   => $res['message'],
            ]);
            $this->toastMessage = "Re-send failed: " . $res['message'];
            $this->toastType = 'error';
        }
    }

    public function deleteLog($id)
    {
        $log = EmailLog::findOrFail($id);
        $log->delete();
        $this->toastMessage = 'Email log removed.';
        $this->toastType = 'success';
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $user = Auth::user();
        $isAdminOrHr = $user->isAdmin() || $user->isHrManager();

        $query = EmailLog::query();

        if (!$isAdminOrHr) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('recipient', $user->email);
            });
        } elseif ($this->selectedUserId !== 'all') {
            $query->where('user_id', $this->selectedUserId);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('recipient', 'like', '%' . $this->search . '%')
                  ->orWhere('sender', 'like', '%' . $this->search . '%');
            });
        }

        $logs = $query->latest()->paginate(20);

        // Stats
        $baseStatQuery = $isAdminOrHr ? EmailLog::query() : EmailLog::where('user_id', $user->id)->orWhere('recipient', $user->email);
        $totalSent = (clone $baseStatQuery)->where('status', 'sent')->count();
        $totalFailed = (clone $baseStatQuery)->where('status', 'failed')->count();
        $totalPending = (clone $baseStatQuery)->where('status', 'pending')->count();

        $employees = $isAdminOrHr ? User::orderBy('name')->get() : collect();

        return view('livewire.mail-logs', [
            'logs'         => $logs,
            'totalSent'    => $totalSent,
            'totalFailed'  => $totalFailed,
            'totalPending' => $totalPending,
            'employees'    => $employees,
            'isAdminOrHr'  => $isAdminOrHr,
        ])->layout('layouts.app');
    }
}
