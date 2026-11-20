<?php

namespace App\Livewire;

use App\Models\NotificationRule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsLog extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, unread, read, archived
    public $search = '';
    public $selectedCategory = 'all';

    public $selectedNotification = null;
    public $toastMessage = null;
    public $toastType = 'success';

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function setCategory($category)
    {
        $this->selectedCategory = $category;
        $this->resetPage();
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            $this->toastMessage = 'Notification marked as read.';
            $this->toastType = 'success';
        }
    }

    public function markAsUnread($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['read_at' => null]);
            $this->toastMessage = 'Notification marked as unread.';
            $this->toastType = 'success';
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->notifications()
            ->whereNull('archived_at')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->toastMessage = 'All active notifications marked as read.';
        $this->toastType = 'success';
    }

    public function archiveNotification($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['archived_at' => now()]);
            $this->selectedNotification = null;
            $this->toastMessage = 'Notification archived successfully.';
            $this->toastType = 'success';
        }
    }

    public function unarchiveNotification($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['archived_at' => null]);
            $this->selectedNotification = null;
            $this->toastMessage = 'Notification restored from archive.';
            $this->toastType = 'success';
        }
    }

    public function archiveAllRead()
    {
        $count = Auth::user()->notifications()
            ->whereNull('archived_at')
            ->whereNotNull('read_at')
            ->update(['archived_at' => now()]);

        $this->toastMessage = "Archived {$count} read notifications.";
        $this->toastType = 'success';
    }

    public function viewDetails($id)
    {
        $this->selectedNotification = Auth::user()->notifications()->find($id);
        if ($this->selectedNotification && !$this->selectedNotification->read_at) {
            $this->selectedNotification->markAsRead();
        }
    }

    public function closeDetails()
    {
        $this->selectedNotification = null;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $user = Auth::user();
        $query = $user->notifications();

        // Enforce that user only sees notifications configured for them
        // 1. If an event type is disabled for their role, exclude it
        $role = $user->role ?: 'Employee';
        $disabledEvents = NotificationRule::getDisabledEventsForRole($role);

        // 2. If user personally muted in_app for an event, also exclude it
        $personalPrefs = $user->getPersonalNotificationPreferences();
        $personalDisabledEvents = [];
        if (empty($personalPrefs['channels']['in_app'])) {
            // User globally disabled in-app
            $personalDisabledEvents = array_keys($personalPrefs['events'] ?? []);
        } else {
            foreach (($personalPrefs['events'] ?? []) as $evtKey => $chans) {
                if (empty($chans['in_app'])) {
                    $personalDisabledEvents[] = $evtKey;
                }
            }
        }
        $allExcludedEvents = array_unique(array_merge($disabledEvents, $personalDisabledEvents));

        if (!empty($allExcludedEvents)) {
            foreach ($allExcludedEvents as $eventKey) {
                $query->where(function ($q) use ($eventKey) {
                    $q->whereNull('data->event_key')
                      ->orWhere('data->event_key', '!=', $eventKey);
                });
            }
        }

        if ($this->filter === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');

            if ($this->filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($this->filter === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        if (!empty($this->search)) {
            $query->where('data', 'like', '%' . $this->search . '%');
        }

        if ($this->selectedCategory !== 'all') {
            $query->where('data->category', $this->selectedCategory);
        }

        $notifications = $query->latest()->paginate(20);

        // Counts strictly according to allowed notifications
        $baseQuery = $user->notifications();
        if (!empty($allExcludedEvents)) {
            foreach ($allExcludedEvents as $eventKey) {
                $baseQuery->where(function ($q) use ($eventKey) {
                    $q->whereNull('data->event_key')
                      ->orWhere('data->event_key', '!=', $eventKey);
                });
            }
        }

        $activeCount   = (clone $baseQuery)->whereNull('archived_at')->count();
        $unreadCount   = (clone $baseQuery)->whereNull('archived_at')->whereNull('read_at')->count();
        $readCount     = (clone $baseQuery)->whereNull('archived_at')->whereNotNull('read_at')->count();
        $archivedCount = (clone $baseQuery)->whereNotNull('archived_at')->count();

        $categories = NotificationRule::CATEGORIES;

        return view('livewire.notifications-log', [
            'notifications' => $notifications,
            'activeCount'   => $activeCount,
            'unreadCount'   => $unreadCount,
            'readCount'     => $readCount,
            'archivedCount' => $archivedCount,
            'categories'    => $categories,
            'canManage'     => $user->canAccessAdmin() || $user->isManager(),
        ])->layout('layouts.app');
    }
}
