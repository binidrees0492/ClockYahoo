<div class="space-y-4">
    {{-- Header & Quick Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Notifications Log</h2>
                    <p class="text-xs text-gray-500">History of notifications strictly configured for your role and profile.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('notification.settings', ['tab' => 'personal']) }}"
               class="px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-[#005a9c] hover:bg-blue-100 text-xs font-bold transition inline-flex items-center gap-1.5 shadow-xs"
               title="Configure what notifications you receive and your preferred channels">
                <i class="fa-solid fa-sliders text-xs"></i> Notification Preferences
            </a>

            @if($unreadCount > 0)
                <button wire:click="markAllAsRead"
                        class="px-3 py-1.5 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold hover:bg-sky-100 transition inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-check-double text-sky-600"></i> Mark All as Read ({{ $unreadCount }})
                </button>
            @endif

            <button wire:click="archiveAllRead"
                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition inline-flex items-center gap-1.5"
                    title="Archive all read notifications (not deleted)">
                <i class="fa-solid fa-box-archive text-gray-500"></i> Archive Read
            </button>
        </div>
    </div>

    {{-- Toast --}}
    @if($toastMessage)
        <div class="p-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs {{ $toastType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
            <span>{{ $toastType === 'success' ? '✓' : '⚠️' }} {{ $toastMessage }}</span>
            <button wire:click="dismissToast" class="text-gray-400 hover:text-gray-700 font-bold">&times;</button>
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button wire:click="setFilter('all')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'all' ? 'bg-[#005a9c] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Active ({{ $activeCount }})
                </button>
                <button wire:click="setFilter('unread')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'unread' ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Unread ({{ $unreadCount }})
                </button>
                <button wire:click="setFilter('read')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'read' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Read ({{ $readCount }})
                </button>
                <button wire:click="setFilter('archived')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center gap-1.5 {{ $filter === 'archived' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fa-solid fa-box-archive text-xs"></i> Archived ({{ $archivedCount }})
                </button>
            </div>

            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search notification text..."
                       class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:ring-1 focus:ring-sky-500">
            </div>
        </div>

        {{-- Category Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-100">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mr-1.5">Category:</span>
            <button type="button" wire:click="setCategory('all')"
                    class="px-2.5 py-1 rounded text-[11px] font-semibold transition {{ $selectedCategory === 'all' ? 'bg-[#005a9c] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                All Categories
            </button>
            @foreach($categories as $cat)
                <button type="button" wire:click="setCategory('{{ $cat }}')"
                        class="px-2.5 py-1 rounded text-[11px] font-semibold transition {{ $selectedCategory === $cat ? 'bg-[#005a9c] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $cat }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs divide-y divide-gray-100 overflow-hidden">
        @forelse($notifications as $notif)
            @php
                $data = $notif->data ?? [];
                $type = $data['type'] ?? 'info';
                $eventKey = $data['event_key'] ?? $type;
                $title = $data['title'] ?? ($data['message'] ?? 'Notification');
                $message = $data['message'] ?? '';
                $category = $data['category'] ?? null;
                $isRead = !is_null($notif->read_at);
                $isArchived = !is_null($notif->archived_at);
                $tz = auth()->user()->getTimezone();
            @endphp
            <div class="p-4 hover:bg-gray-50/70 transition flex items-start justify-between gap-3 {{ !$isRead && !$isArchived ? 'bg-blue-50/30' : '' }}">
                <div class="flex items-start gap-3 flex-1 min-w-0 cursor-pointer" wire:click="viewDetails('{{ $notif->id }}')">
                    {{-- Icon by Type --}}
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5
                        {{ in_array($type, ['unusual_activity', 'geofence_breach']) ? 'bg-amber-100 text-amber-800' :
                           (in_array($type, ['time_approval', 'timesheet_approved', 'clock_in']) ? 'bg-emerald-100 text-emerald-800' :
                           (in_array($type, ['shift_assigned', 'shift_updated', 'shift_published']) ? 'bg-sky-100 text-sky-800' :
                           ($type === 'timesheet_disapproved' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'))) }}">
                        <i class="{{ \App\Services\NotificationRoutingService::getEventIcon($eventKey) }}"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-gray-900 truncate">{{ $title }}</span>
                            @if($category)
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-gray-100 text-gray-600">{{ $category }}</span>
                            @endif
                            @if(!$isRead && !$isArchived)
                                <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0" title="Unread"></span>
                            @endif
                            @if($isArchived)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">Archived</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5 leading-snug line-clamp-2">
                            {{ $message }}
                        </p>
                        <div class="text-[10px] text-gray-400 mt-1">
                            {{ $notif->created_at->diffForHumans() }} &bull; {{ $notif->created_at->setTimezone($tz)->format('M d, Y H:i') }}
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-1.5 shrink-0">
                    @if(!$isArchived)
                        @if(!$isRead)
                            <button wire:click="markAsRead('{{ $notif->id }}')"
                                    class="px-2.5 py-1 text-sky-700 hover:bg-sky-100 rounded text-[11px] font-semibold transition" title="Mark as Read">
                                Mark Read
                            </button>
                        @else
                            <button wire:click="markAsUnread('{{ $notif->id }}')"
                                    class="px-2 py-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded text-[11px]" title="Mark as Unread">
                                Unread
                            </button>
                        @endif

                        <button wire:click="archiveNotification('{{ $notif->id }}')"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded text-xs transition" title="Archive Notification">
                            <i class="fa-solid fa-box-archive"></i>
                        </button>
                    @else
                        <button wire:click="unarchiveNotification('{{ $notif->id }}')"
                                class="px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-[11px] font-semibold transition inline-flex items-center gap-1"
                                title="Restore notification to active inbox">
                            <i class="fa-solid fa-arrow-rotate-left text-[10px]"></i> Restore
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-400 text-xs italic">
                No notifications found in {{ $filter }} matching selected criteria.
            </div>
        @endforelse

        @if($notifications->hasPages())
            <div class="p-3 bg-gray-50 border-t border-gray-100">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    {{-- View Detail Modal --}}
    @if($selectedNotification)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-5 space-y-4">
                <div class="flex justify-between items-start border-b border-gray-200 pb-2">
                    <div>
                        <h4 class="font-bold text-sm text-gray-900">
                            {{ $selectedNotification->data['title'] ?? 'Notification Details' }}
                        </h4>
                        <span class="text-[11px] text-gray-400">
                            {{ $selectedNotification->created_at->setTimezone(auth()->user()->getTimezone())->format('M d, Y H:i') }}
                        </span>
                    </div>
                    <button wire:click="closeDetails" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
                </div>

                <div class="text-xs text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200 leading-relaxed whitespace-pre-wrap">
                    {{ $selectedNotification->data['message'] ?? 'No message body.' }}
                </div>

                <div class="flex justify-between items-center pt-2">
                    @if($selectedNotification->archived_at)
                        <button wire:click="unarchiveNotification('{{ $selectedNotification->id }}')"
                                class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-xs font-semibold inline-flex items-center gap-1">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Restore to Active
                        </button>
                    @else
                        <button wire:click="archiveNotification('{{ $selectedNotification->id }}')"
                                class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-xs font-semibold inline-flex items-center gap-1">
                            <i class="fa-solid fa-box-archive"></i> Archive
                        </button>
                    @endif

                    <button wire:click="closeDetails"
                            class="px-4 py-1.5 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded text-xs font-semibold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
