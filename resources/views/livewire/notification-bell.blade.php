<div class="relative" wire:poll.6s>
    {{-- Bell Button with ClockShark Orange Dot Badge --}}
    <button wire:click="toggleDropdown" type="button"
            class="relative p-2 text-gray-500 hover:text-gray-800 rounded-full hover:bg-gray-100 transition focus:outline-none flex items-center justify-center"
            title="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- ClockShark Orange Badge Dot --}}
        @if($unreadCount > 0)
            <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-[#f26522] border-2 border-white rounded-full shadow animate-pulse"></span>
        @endif
    </button>

    @if($isOpen)
        {{-- Backdrop to close --}}
        <div wire:click="closeDropdown" class="fixed inset-0 z-40"></div>

        {{-- Dropdown Card --}}
        <div class="absolute right-0 mt-2 w-84 sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden text-gray-800 animate-in fade-in zoom-in duration-100">
            {{-- Header --}}
            <div class="px-4 py-3 bg-[#005088] text-white flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-xs uppercase tracking-wider">Notifications</span>
                    @if($unreadCount > 0)
                        <span class="px-1.5 py-0.5 bg-sky-500 text-white rounded-full text-[10px] font-bold">
                            {{ $unreadCount }} new
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if($unreadCount > 0)
                        <button wire:click="markAllAsRead" type="button" class="text-[11px] text-blue-200 hover:text-white underline">
                            Mark all read
                        </button>
                    @endif
                    @if($activeTab === 'active' && $notifications->count() > 0)
                        <button wire:click="archiveAll" type="button" class="text-[11px] text-blue-200 hover:text-white underline" title="Archive all notifications">
                            Archive all
                        </button>
                    @endif
                    <a href="{{ route('notification.settings') }}"
                       class="text-blue-200 hover:text-white text-xs" title="Notification Settings">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                </div>
            </div>

            {{-- Tabs: Active vs Archived --}}
            <div class="flex border-b border-gray-200 bg-gray-50 text-xs font-semibold">
                <button wire:click="setTab('active')" type="button"
                        class="flex-1 py-2 text-center border-b-2 transition {{ $activeTab === 'active' ? 'border-[#005a9c] text-[#005a9c] bg-white' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Active Inbox @if($unreadCount > 0) <span class="ml-1 text-[10px] bg-sky-100 text-sky-800 px-1.5 py-0.2 rounded-full font-bold">{{ $unreadCount }}</span> @endif
                </button>
                <button wire:click="setTab('archived')" type="button"
                        class="flex-1 py-2 text-center border-b-2 transition {{ $activeTab === 'archived' ? 'border-[#005a9c] text-[#005a9c] bg-white' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Archived @if($archivedCount > 0) <span class="ml-1 text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.2 rounded-full font-medium">{{ $archivedCount }}</span> @endif
                </button>
            </div>

            {{-- Notifications List --}}
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                @forelse($notifications as $n)
                    @php
                        $data = $n->data;
                        $isUnread = $n->unread();
                        $targetLink = $data['link'] ?? '#';
                        $type = $data['type'] ?? '';
                        $eventKey = $data['event_key'] ?? $type;
                    @endphp
                    <div class="p-3 hover:bg-blue-50/50 transition flex items-start gap-3 group {{ $isUnread ? 'bg-sky-50/40' : '' }}">
                        {{-- Icon badge by type --}}
                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs shadow-xs
                            {{ in_array($type, ['unusual_activity', 'geofence_breach']) ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                               ($type === 'timesheet_approved' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' :
                               ($type === 'timesheet_disapproved' ? 'bg-red-100 text-red-700 border border-red-300' :
                               (in_array($type, ['shift_assigned', 'shift_updated', 'shift_published']) ? 'bg-blue-100 text-[#005a9c] border border-blue-300' :
                               ($type === 'clock_in' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
                               ($type === 'clock_out' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                               (in_array($type, ['time_submitted', 'time_log_submitted']) ? 'bg-indigo-100 text-indigo-700 border border-indigo-300' : 'bg-gray-100 text-gray-600')))))) }}">
                            <i class="{{ \App\Services\NotificationRoutingService::getEventIcon($eventKey) }}"></i>
                        </div>

                        {{-- Notification body --}}
                        <div class="flex-1 min-w-0 text-xs">
                            <div class="flex justify-between items-start gap-1">
                                <span class="font-semibold text-gray-900 truncate">{{ $data['title'] ?? 'Notification' }}</span>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $n->created_at->diffForHumans(null, true) }}</span>
                            </div>
                            <p class="text-gray-600 text-[11px] mt-0.5 leading-snug line-clamp-2">
                                {{ $data['message'] ?? '' }}
                            </p>

                            <div class="mt-2 flex items-center gap-3 text-[11px]">
                                @if($targetLink && $targetLink !== '#')
                                    <button wire:click="markAsRead('{{ $n->id }}', '{{ $targetLink }}')" type="button"
                                            class="text-[#005a9c] hover:underline font-semibold inline-flex items-center gap-1">
                                        View details &rarr;
                                    </button>
                                @endif

                                @if($activeTab === 'active')
                                    <button wire:click.stop="archiveNotification('{{ $n->id }}')" type="button"
                                            class="text-gray-400 hover:text-gray-700 transition" title="Move to archive">
                                        Archive
                                    </button>
                                @else
                                    <button wire:click.stop="unarchiveNotification('{{ $n->id }}')" type="button"
                                            class="text-blue-600 hover:text-blue-800 transition" title="Restore to active inbox">
                                        Unarchive
                                    </button>
                                    <button wire:click.stop="deleteNotification('{{ $n->id }}')" type="button"
                                            class="text-red-500 hover:text-red-700 transition" title="Delete permanently">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($isUnread)
                            <span class="w-2 h-2 rounded-full bg-sky-500 flex-shrink-0 self-center" title="Unread"></span>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 text-xs">
                        <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if($activeTab === 'active')
                            No new notifications.<br>You're all caught up!
                        @else
                            No archived notifications.
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="p-2 border-t border-gray-100 bg-gray-50 flex justify-between items-center text-[11px] px-3">
                <div class="flex items-center gap-2">
                    <a href="{{ route('notifications.log') }}" class="text-[#005a9c] hover:underline font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-list-ul text-[10px]"></i> View History Log
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('notification.settings') }}" class="text-gray-600 hover:text-[#005a9c] hover:underline flex items-center gap-1" title="Notification Settings & Rules">
                        <i class="fa-solid fa-gear text-[10px]"></i> Manage
                    </a>
                </div>
                <button wire:click="closeDropdown" type="button" class="text-gray-500 hover:text-gray-800">
                    Close
                </button>
            </div>
        </div>
    @endif

    {{-- Real-Time Pop-Up Toasts (Bottom Right Corner) --}}
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm w-full"
         @notification-toast.window="$wire.handleIncomingToast($event.detail)">
        @foreach($activeToasts as $t)
            <div wire:key="toast-{{ $t['id'] }}"
                 x-data="{ show: true }"
                 x-init="setTimeout(() => { show = false; setTimeout(() => $wire.dismissToast('{{ $t['id'] }}'), 350); }, {{ $t['duration'] ?? 5500 }})"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="pointer-events-auto bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-gray-200 dark:border-gray-700 shadow-2xl rounded-2xl p-3.5 flex items-start gap-3 transition">

                {{-- Left Icon Badge --}}
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-white shadow-xs
                    {{ ($t['color'] ?? '') === 'sky' ? 'bg-sky-500' :
                       (($t['color'] ?? '') === 'emerald' ? 'bg-emerald-500' :
                       (($t['color'] ?? '') === 'amber' ? 'bg-amber-500' :
                       (($t['color'] ?? '') === 'rose' ? 'bg-rose-500' :
                       (($t['color'] ?? '') === 'purple' ? 'bg-purple-500' :
                       (($t['color'] ?? '') === 'indigo' ? 'bg-indigo-500' : 'bg-slate-700'))))) }}">
                    <i class="{{ $t['icon'] ?? 'fa-solid fa-bell' }} text-xs"></i>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <h5 class="text-xs font-bold text-gray-900 truncate">{{ $t['title'] }}</h5>
                        <span class="text-[10px] text-gray-400 shrink-0">{{ $t['time'] ?? 'Just now' }}</span>
                    </div>
                    <p class="text-[11px] text-gray-600 mt-0.5 line-clamp-2 leading-snug">{{ $t['message'] }}</p>

                    @if(!empty($t['link']) && $t['link'] !== '#')
                        <div class="mt-2 flex items-center gap-3">
                            <button type="button" wire:click="markAsRead('{{ $t['id'] }}', '{{ $t['link'] }}')"
                                    class="text-[11px] text-[#005a9c] hover:underline font-bold inline-flex items-center gap-1">
                                <span>View details</span>
                                <span>&rarr;</span>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Close Button --}}
                <button type="button" wire:click="dismissToast('{{ $t['id'] }}')"
                        class="text-gray-400 hover:text-gray-600 text-sm leading-none shrink-0 p-0.5" title="Dismiss">
                    &times;
                </button>
            </div>
        @endforeach
    </div>
</div>
