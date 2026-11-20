<?php

namespace App\Livewire;

use App\Services\NotificationRoutingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public $isOpen = false;
    public $activeTab = 'active'; // 'active' | 'archived'

    // Real-Time Pop-Up Toasts in bottom right corner
    public $activeToasts = [];
    public $poppedNotificationIds = [];

    protected $listeners = [
        'notification-toast'   => 'handleIncomingToast',
        'refreshNotifications' => '$refresh',
    ];

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function closeDropdown()
    {
        $this->isOpen = false;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function markAsRead($id, $targetUrl = null)
    {
        $user = Auth::user();
        if (!$user) return;

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        $this->dismissToast($id);

        if ($targetUrl && $targetUrl !== '#') {
            $this->isOpen = false;
            return redirect()->to($targetUrl);
        }
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) return;

        $user->unreadNotifications()->whereNull('archived_at')->update(['read_at' => now()]);
        $this->activeToasts = [];
    }

    public function archiveNotification($id)
    {
        $user = Auth::user();
        if (!$user) return;

        $user->notifications()->where('id', $id)->update(['archived_at' => now()]);
        $this->dismissToast($id);
    }

    public function unarchiveNotification($id)
    {
        $user = Auth::user();
        if (!$user) return;

        $user->notifications()->where('id', $id)->update(['archived_at' => null]);
    }

    public function archiveAll()
    {
        $user = Auth::user();
        if (!$user) return;

        $user->notifications()->whereNull('archived_at')->update(['archived_at' => now()]);
        $this->activeToasts = [];
    }

    public function deleteNotification($id)
    {
        $user = Auth::user();
        if (!$user) return;

        $user->notifications()->where('id', $id)->delete();
        $this->dismissToast($id);
    }

    public function dismissToast($id)
    {
        $this->activeToasts = array_values(array_filter(
            $this->activeToasts,
            fn($t) => ($t['id'] ?? null) !== $id
        ));
    }

    public function handleIncomingToast(array $data)
    {
        $id = $data['id'] ?? uniqid('toast_');
        if (!in_array($id, $this->poppedNotificationIds)) {
            $this->poppedNotificationIds[] = $id;
            $this->activeToasts[] = [
                'id'       => $id,
                'title'    => $data['title'] ?? 'Notification',
                'message'  => $data['message'] ?? '',
                'link'     => $data['link'] ?? '#',
                'type'     => $data['type'] ?? 'info',
                'icon'     => $data['icon'] ?? NotificationRoutingService::getEventIcon($data['type'] ?? ''),
                'color'    => $data['color'] ?? NotificationRoutingService::getEventColor($data['type'] ?? ''),
                'time'     => 'Just now',
                'duration' => $data['duration'] ?? 5500,
            ];
        }
    }

    public function render()
    {
        $user = Auth::user();
        if (!$user) {
            return view('livewire.notification-bell', [
                'unreadCount'   => 0,
                'notifications' => collect(),
                'archivedCount' => 0,
            ]);
        }

        // Only count unarchived unread notifications
        $unreadCount = $user->unreadNotifications()->whereNull('archived_at')->count();
        $archivedCount = $user->notifications()->whereNotNull('archived_at')->count();

        // Detect recent unread notifications with toast capability (if user has toasts enabled)
        if ($user->prefersNotificationChannel('global', 'toast')) {
            $recentToasts = $user->unreadNotifications()
                ->whereNull('archived_at')
                ->where('created_at', '>=', now()->subSeconds(45))
                ->latest()
                ->take(4)
                ->get();

            foreach ($recentToasts as $notif) {
                $id = $notif->id;
                if (!in_array($id, $this->poppedNotificationIds)) {
                    $data = $notif->data ?? [];
                    $eventKey = $data['event_key'] ?? $data['type'] ?? 'generic';
                    $shouldToast = (!isset($data['toast']) || !empty($data['toast']))
                        && $user->prefersNotificationChannel($eventKey, 'toast');

                    $this->poppedNotificationIds[] = $id;

                    if ($shouldToast) {
                        $this->activeToasts[] = [
                            'id'       => $id,
                            'title'    => $data['title'] ?? 'New Notification',
                            'message'  => $data['message'] ?? '',
                            'link'     => $data['link'] ?? '#',
                            'type'     => $data['type'] ?? 'info',
                            'icon'     => $data['icon'] ?? NotificationRoutingService::getEventIcon($eventKey),
                            'color'    => $data['color'] ?? NotificationRoutingService::getEventColor($eventKey),
                            'time'     => 'Just now',
                            'duration' => 5500,
                        ];
                    }
                }
            }
        }

        $query = $user->notifications();
        if ($this->activeTab === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $notifications = $query->take(20)->get();

        return view('livewire.notification-bell', [
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
            'archivedCount' => $archivedCount,
        ]);
    }
}
