<div class="max-w-7xl mx-auto space-y-6 pb-16">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#00487c] to-[#0070ba] text-white flex items-center justify-center shadow-md">
                    <i class="fa-solid fa-bell-concierge text-base"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">Notification Management</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Configure role-based notification routing, manager employee activity alerts, and sending email credentials.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.log') }}"
               class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-list-ul text-gray-500"></i> View Notification History
            </a>
            @if($isAdmin)
                <button wire:click="resetDefaultRules"
                        wire:confirm="Are you sure you want to restore notification routing rules to application defaults?"
                        class="px-3 py-1.5 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5"
                        title="Reset all rules to system default settings">
                    <i class="fa-solid fa-arrow-rotate-left text-xs"></i> Reset Defaults
                </button>
            @endif
        </div>
    </div>

    {{-- Toast Notification Banner --}}
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="p-3.5 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs transition {{ $toastType === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
            <div class="flex items-center gap-2">
                <span>{{ $toastType === 'error' ? '⚠️' : '✓' }}</span>
                <span>{{ $toastMessage }}</span>
            </div>
            <button wire:click="dismissToast" @click="show = false" class="text-gray-400 hover:text-gray-700 font-bold text-base leading-none">&times;</button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex flex-wrap border-b border-gray-200 gap-2 text-xs font-bold">
        @if($isAdmin)
            <button type="button" wire:click="setTab('rules')"
                    class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'rules' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                <i class="fa-solid fa-sliders text-xs"></i> Role Routing Matrix
            </button>
        @endif

        {{-- My Personal Preferences Tab - Visible to EVERYBODY --}}
        <button type="button" wire:click="setTab('personal')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'personal' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <i class="fa-solid fa-user-gear text-xs"></i> My Preferences
        </button>

        @if($isManager || $isAdmin)
            <button type="button" wire:click="setTab('manager')"
                    class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'manager' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                <i class="fa-solid fa-user-tie text-xs"></i> Manager Activity Alerts
            </button>
        @endif

        @if($isAdmin)
            <button type="button" wire:click="setTab('email')"
                    class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'email' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                <i class="fa-solid fa-envelope-circle-check text-xs"></i> Notification Email Sender
            </button>
        @endif
    </div>

    {{-- TAB: PERSONAL PREFERENCES (Available to EVERYBODY) --}}
    @if($activeTab === 'personal')
        <div class="space-y-6">
            {{-- Personal Header Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-user-gear text-[#005a9c]"></i> My Notification Preferences
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 max-w-2xl leading-relaxed">
                        Customize what notifications you want to receive and how you want them delivered. Choose between in-app notifications on the bell icon, real-time pop-up toast alerts in the bottom-right corner, and direct emails.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="restoreDefaultPersonalPreferences"
                            class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-gray-400"></i> Defaults
                    </button>
                    <button type="button" wire:click="enableAllPersonalChannels"
                            class="px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-check-double text-emerald-600"></i> Enable All
                    </button>
                    <button type="button" wire:click="muteAllPersonalNotifications"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-bell-slash text-slate-500"></i> Mute All
                    </button>
                    <button type="button" wire:click="savePersonalPreferences"
                            class="px-4 py-1.5 rounded-lg bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Save Preferences
                    </button>
                </div>
            </div>

            {{-- Master Delivery Channels Card --}}
            <div class="bg-gradient-to-br from-slate-50 to-blue-50/40 rounded-2xl border border-blue-100 p-5 shadow-xs space-y-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Primary Delivery Channels</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Toggle master notification delivery methods across your entire account.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Channel 1: In-App Bell --}}
                    <div wire:click="togglePersonalGlobalChannel('in_app')"
                         class="p-4 rounded-xl border-2 transition cursor-pointer select-none bg-white {{ !empty($personalChannels['in_app']) ? 'border-[#005a9c] ring-2 ring-[#005a9c]/20 shadow-xs' : 'border-gray-200 opacity-60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#005a9c] flex items-center justify-center font-bold text-sm">
                                    <i class="fa-solid fa-bell"></i>
                                </span>
                                <span class="text-xs font-bold text-gray-900">In-App Bell</span>
                            </div>
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ !empty($personalChannels['in_app']) ? 'bg-[#005a9c] text-white' : 'bg-gray-200 text-gray-500' }}">
                                {{ !empty($personalChannels['in_app']) ? '✓' : '✕' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            Shows badge counts and activity feed inside the top navigation bell icon and saves to your notifications inbox.
                        </p>
                    </div>

                    {{-- Channel 2: Real-Time Toast Popups --}}
                    <div wire:click="togglePersonalGlobalChannel('toast')"
                         class="p-4 rounded-xl border-2 transition cursor-pointer select-none bg-white {{ !empty($personalChannels['toast']) ? 'border-sky-500 ring-2 ring-sky-500/20 shadow-xs' : 'border-gray-200 opacity-60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-sm">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </span>
                                <span class="text-xs font-bold text-gray-900">Real-Time Toast Pop-Up</span>
                            </div>
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ !empty($personalChannels['toast']) ? 'bg-sky-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                                {{ !empty($personalChannels['toast']) ? '✓' : '✕' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            Pops up a floating alert card in the bottom-right corner of your screen in real time that auto-dismisses after a few seconds.
                        </p>
                    </div>

                    {{-- Channel 3: Email Alerts --}}
                    <div wire:click="togglePersonalGlobalChannel('email')"
                         class="p-4 rounded-xl border-2 transition cursor-pointer select-none bg-white {{ !empty($personalChannels['email']) ? 'border-emerald-600 ring-2 ring-emerald-600/20 shadow-xs' : 'border-gray-200 opacity-60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <span class="text-xs font-bold text-gray-900">Email Notifications</span>
                            </div>
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ !empty($personalChannels['email']) ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                                {{ !empty($personalChannels['email']) ? '✓' : '✕' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            Sends email digests and critical alerts straight to <strong>{{ Auth::user()->email }}</strong>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Granular Activity & Events Categories --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Activity & Alert Preferences</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Control which specific activities notify you and select delivery channels for each.</p>
                    </div>
                </div>

                @foreach($eventsByCategory as $categoryName => $catEvents)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                        {{-- Category Banner --}}
                        <div class="px-5 py-3 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#005a9c]"></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-800">{{ $categoryName }}</span>
                                <span class="text-[10px] text-gray-500">({{ count($catEvents) }} alerts)</span>
                            </div>
                        </div>

                        {{-- Events list --}}
                        <div class="divide-y divide-gray-100">
                            @foreach($catEvents as $evtKey => $meta)
                                @php
                                    $currInApp = !empty($personalEvents[$evtKey]['in_app']);
                                    $currToast = !empty($personalEvents[$evtKey]['toast']);
                                    $currEmail = !empty($personalEvents[$evtKey]['email']);
                                    $isAnyOn   = $currInApp || $currToast || $currEmail;
                                @endphp
                                <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50/50 transition {{ !$isAnyOn ? 'opacity-50' : '' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 text-white shadow-xs"
                                             style="background-color: {{ $meta['color'] }};">
                                            <i class="fa-solid {{ $meta['icon'] }} text-xs"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-900 flex items-center gap-2">
                                                <span>{{ $meta['name'] }}</span>
                                                @if(!$isAnyOn)
                                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-gray-100 text-gray-500 font-semibold">Muted</span>
                                                @endif
                                            </h4>
                                            <p class="text-[11px] text-gray-500 mt-0.5">{{ $meta['desc'] }}</p>
                                        </div>
                                    </div>

                                    {{-- Channel Checkboxes for this Event --}}
                                    <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0">
                                        {{-- In-App Bell --}}
                                        <button type="button" wire:click="togglePersonalEventChannel('{{ $evtKey }}', 'in_app')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 {{ $currInApp ? 'bg-blue-50 border-blue-300 text-[#005a9c] font-bold shadow-xs' : 'bg-gray-100 border-gray-200 text-gray-400 hover:text-gray-600' }}"
                                                title="Toggle In-App Bell Notification">
                                            <i class="fa-solid fa-bell text-[10px]"></i>
                                            <span class="text-[11px]">Bell</span>
                                            <span>{{ $currInApp ? '✓' : '✕' }}</span>
                                        </button>

                                        {{-- Toast Popup --}}
                                        <button type="button" wire:click="togglePersonalEventChannel('{{ $evtKey }}', 'toast')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 {{ $currToast ? 'bg-sky-50 border-sky-300 text-sky-700 font-bold shadow-xs' : 'bg-gray-100 border-gray-200 text-gray-400 hover:text-gray-600' }}"
                                                title="Toggle Real-Time Bottom-Right Toast Pop-Up">
                                            <i class="fa-solid fa-comment-dots text-[10px]"></i>
                                            <span class="text-[11px]">Toast</span>
                                            <span>{{ $currToast ? '✓' : '✕' }}</span>
                                        </button>

                                        {{-- Email --}}
                                        <button type="button" wire:click="togglePersonalEventChannel('{{ $evtKey }}', 'email')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 {{ $currEmail ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold shadow-xs' : 'bg-gray-100 border-gray-200 text-gray-400 hover:text-gray-600' }}"
                                                title="Toggle Email Notification">
                                            <i class="fa-solid fa-envelope text-[10px]"></i>
                                            <span class="text-[11px]">Email</span>
                                            <span>{{ $currEmail ? '✓' : '✕' }}</span>
                                        </button>

                                        {{-- Quick toggle all --}}
                                        <button type="button" wire:click="togglePersonalEventAll('{{ $evtKey }}')"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold text-gray-500 hover:bg-gray-200 bg-gray-100 transition"
                                                title="Turn all channels on or off for this event">
                                            {{ $isAnyOn ? 'Mute' : 'All On' }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Bottom Save Bar --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs flex items-center justify-between">
                <span class="text-xs text-gray-500">Preferences are saved instantly to your personal user account.</span>
                <button type="button" wire:click="savePersonalPreferences"
                        class="px-5 py-2.5 rounded-xl bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save My Preferences
                </button>
            </div>
        </div>
    @endif

    {{-- TAB 1: ROLE ROUTING MATRIX --}}
    @if($activeTab === 'rules' && $isAdmin)
        <div class="space-y-4">
            {{-- Category Filter Bar --}}
            <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mr-2">Category:</span>
                    <button type="button" wire:click="setCategory('all')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $selectedCategory === 'all' ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        All ({{ count($rules) }})
                    </button>
                    @foreach($categories as $cat)
                        @php $count = collect($rules)->where('category', $cat)->count(); @endphp
                        <button type="button" wire:click="setCategory('{{ $cat }}')"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $selectedCategory === $cat ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $cat }} ({{ $count }})
                        </button>
                    @endforeach
                </div>

                <div>
                    <button type="button" wire:click="saveRules"
                            class="px-4 py-1.5 rounded-lg bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Save Routing Matrix
                    </button>
                </div>
            </div>

            {{-- Rules List --}}
            <div class="space-y-4">
                @forelse($filteredRules as $r)
                    @php $ruleId = $r['id']; @endphp
                    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden transition hover:border-gray-300 {{ !$r['is_active'] ? 'opacity-60 bg-gray-50/50' : '' }}">
                        {{-- Rule Top Banner --}}
                        <div class="p-4 bg-gray-50/80 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 text-[#005a9c] flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="{{ \App\Services\NotificationRoutingService::getEventIcon($r['event_key']) }} text-sm"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-gray-900">{{ $r['name'] }}</h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-700">
                                            {{ $r['category'] }}
                                        </span>
                                        @if(!$r['is_active'])
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Disabled</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $r['description'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-700">
                                    <span>Active</span>
                                    <input type="checkbox" wire:click="toggleRuleActive({{ $ruleId }})"
                                           {{ $r['is_active'] ? 'checked' : '' }}
                                           class="rounded text-[#005a9c] focus:ring-[#005a9c] w-4 h-4">
                                </label>
                            </div>
                        </div>

                        {{-- Rule Body Grid --}}
                        <div class="p-4 grid grid-cols-1 lg:grid-cols-12 gap-6">
                            {{-- Left: Direct Person Involved Box --}}
                            <div class="lg:col-span-4 bg-blue-50/40 rounded-xl p-3.5 border border-blue-100">
                                <div class="flex items-center justify-between pb-2 border-b border-blue-100">
                                    <span class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user-check text-blue-600"></i> Direct Person Involved
                                    </span>
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-blue-900">
                                        <span>Notify</span>
                                        <input type="checkbox" wire:click="togglePersonInvolved({{ $ruleId }})"
                                               {{ $r['person_involved_enabled'] ? 'checked' : '' }}
                                               class="rounded text-[#005a9c] focus:ring-[#005a9c] w-3.5 h-3.5">
                                    </label>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">
                                    When an action directly affects an employee (e.g. their assigned shift or approved timesheet), deliver to them via:
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    <label class="inline-flex items-center gap-1.5 px-2 py-1 bg-white rounded border border-blue-200 cursor-pointer text-gray-700 hover:bg-blue-50 transition">
                                        <input type="checkbox" wire:click="togglePersonChannel({{ $ruleId }}, 'in_app')"
                                               {{ in_array('in_app', $r['person_involved_channels']) ? 'checked' : '' }}
                                               class="rounded text-[#005a9c] w-3.5 h-3.5">
                                        <span>🔔 Bell Icon</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 px-2 py-1 bg-white rounded border border-blue-200 cursor-pointer text-gray-700 hover:bg-blue-50 transition">
                                        <input type="checkbox" wire:click="togglePersonChannel({{ $ruleId }}, 'toast')"
                                               {{ in_array('toast', $r['person_involved_channels']) ? 'checked' : '' }}
                                               class="rounded text-[#005a9c] w-3.5 h-3.5">
                                        <span>💬 Real-Time Toast</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 px-2 py-1 bg-white rounded border border-blue-200 cursor-pointer text-gray-700 hover:bg-blue-50 transition">
                                        <input type="checkbox" wire:click="togglePersonChannel({{ $ruleId }}, 'email')"
                                               {{ in_array('email', $r['person_involved_channels']) ? 'checked' : '' }}
                                               class="rounded text-[#005a9c] w-3.5 h-3.5">
                                        <span>✉️ Email</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Right: Roles Matrix Table --}}
                            <div class="lg:col-span-8 overflow-x-auto">
                                <div class="text-xs font-bold text-gray-800 mb-2 flex items-center justify-between">
                                    <span>Roles Serving & Delivery Channels</span>
                                    <span class="text-[11px] text-gray-400 font-normal">Bell = In-App &bull; Toast = Real-Time Popup &bull; Email = Notification Mail</span>
                                </div>
                                <table class="w-full text-left text-xs border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-50 text-gray-600 text-[11px] uppercase border-b border-gray-200">
                                        <tr>
                                            <th class="p-2.5 font-bold">Role</th>
                                            <th class="p-2.5 font-bold text-center">Enable</th>
                                            <th class="p-2.5 font-bold text-center">In-App Bell 🔔</th>
                                            <th class="p-2.5 font-bold text-center">Real-Time Toast 💬</th>
                                            <th class="p-2.5 font-bold text-center">Email ✉️</th>
                                            <th class="p-2.5 font-bold">Scope / Target</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($roles as $role)
                                            @php
                                                $cfg = $r['roles_config'][$role] ?? ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'all'];
                                                $isEnabled = !empty($cfg['enabled']);
                                            @endphp
                                            <tr class="hover:bg-gray-50/60 transition {{ !$isEnabled ? 'text-gray-400 bg-gray-50/30' : 'text-gray-800' }}">
                                                <td class="p-2.5 font-semibold flex items-center gap-1.5">
                                                    @if($role === 'Super Admin')
                                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                    @elseif($role === 'Administrator')
                                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                    @elseif($role === 'HR Manager')
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    @elseif($role === 'Manager')
                                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                    @else
                                                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                                    @endif
                                                    {{ $role }}
                                                </td>
                                                <td class="p-2.5 text-center">
                                                    <input type="checkbox" wire:click="toggleRoleEnabled({{ $ruleId }}, '{{ $role }}')"
                                                           {{ $isEnabled ? 'checked' : '' }}
                                                           class="rounded text-[#005a9c] w-3.5 h-3.5">
                                                </td>
                                                <td class="p-2.5 text-center">
                                                    <input type="checkbox" wire:click="toggleRoleChannel({{ $ruleId }}, '{{ $role }}', 'in_app')"
                                                           {{ !empty($cfg['in_app']) ? 'checked' : '' }}
                                                           class="rounded text-[#005a9c] w-3.5 h-3.5">
                                                </td>
                                                <td class="p-2.5 text-center">
                                                    <input type="checkbox" wire:click="toggleRoleChannel({{ $ruleId }}, '{{ $role }}', 'toast')"
                                                           {{ !empty($cfg['toast']) ? 'checked' : '' }}
                                                           class="rounded text-[#005a9c] w-3.5 h-3.5">
                                                </td>
                                                <td class="p-2.5 text-center">
                                                    <input type="checkbox" wire:click="toggleRoleChannel({{ $ruleId }}, '{{ $role }}', 'email')"
                                                           {{ !empty($cfg['email']) ? 'checked' : '' }}
                                                           class="rounded text-[#005a9c] w-3.5 h-3.5">
                                                </td>
                                                <td class="p-2.5 text-[11px]">
                                                    @if($role === 'Manager')
                                                        <select wire:model="rules.{{ $ruleId }}.manager_scope"
                                                                class="text-[11px] py-0.5 px-2 rounded border-gray-300 text-gray-700 bg-white shadow-xs focus:ring-1 focus:ring-[#005a9c]">
                                                            <option value="department">Department Members</option>
                                                            <option value="direct_reports">Direct Reports Only</option>
                                                            <option value="all">All Employees</option>
                                                            <option value="none">Disabled</option>
                                                        </select>
                                                    @elseif(in_array($role, ['Super Admin', 'Administrator', 'HR Manager']))
                                                        <span class="text-gray-500">All Company</span>
                                                    @else
                                                        <span class="text-gray-500">Own Activity</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-400 text-xs italic bg-white rounded-xl border border-gray-200">
                        No notification rules found matching this category.
                    </div>
                @endforelse
            </div>

            {{-- Bottom Save Floating Action Bar --}}
            <div class="flex justify-end p-3 bg-white rounded-xl border border-gray-200 shadow-xs">
                <button type="button" wire:click="saveRules"
                        class="px-6 py-2 rounded-lg bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Routing Matrix Changes
                </button>
            </div>
        </div>
    @endif

    {{-- TAB 2: MANAGER EMPLOYEE ACTIVITY ALERTS --}}
    @if($activeTab === 'manager')
        <div class="space-y-6">
            {{-- Header Card --}}
            <div class="bg-gradient-to-r from-blue-900 to-[#005a9c] text-white p-5 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold flex items-center gap-2">
                        <i class="fa-solid fa-user-tie text-sky-300"></i> Manager Employee Activity Alerts
                    </h2>
                    <p class="text-xs text-blue-100 mt-1 max-w-2xl leading-relaxed">
                        Choose exactly which employee work activities trigger notifications for managers. Configure delivery channels including in-app alerts, real-time bottom-right popups, and email notifications.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="saveManagerSettings"
                            class="px-5 py-2 rounded-xl bg-white text-[#005a9c] hover:bg-blue-50 font-bold text-xs shadow-md transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Preferences
                    </button>
                </div>
            </div>

            {{-- Team Scope Selector --}}
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
                <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider mb-1">
                    Employee Surveillance Scope
                </label>
                <p class="text-xs text-gray-500 mb-3">Define which employees' actions should trigger activity alerts for the manager.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition {{ $managerScope === 'department' ? 'border-[#005a9c] bg-blue-50/50 shadow-xs ring-1 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="managerScope" value="department" class="mt-0.5 text-[#005a9c]">
                        <div>
                            <div class="text-xs font-bold text-gray-900">Department Members</div>
                            <div class="text-[11px] text-gray-500 mt-0.5 leading-snug">Notify for employees belonging to the manager's assigned department.</div>
                        </div>
                    </label>

                    <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition {{ $managerScope === 'direct_reports' ? 'border-[#005a9c] bg-blue-50/50 shadow-xs ring-1 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="managerScope" value="direct_reports" class="mt-0.5 text-[#005a9c]">
                        <div>
                            <div class="text-xs font-bold text-gray-900">Direct Reports Only</div>
                            <div class="text-[11px] text-gray-500 mt-0.5 leading-snug">Notify only for employees who have this manager explicitly assigned.</div>
                        </div>
                    </label>

                    <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition {{ $managerScope === 'all' ? 'border-[#005a9c] bg-blue-50/50 shadow-xs ring-1 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="managerScope" value="all" class="mt-0.5 text-[#005a9c]">
                        <div>
                            <div class="text-xs font-bold text-gray-900">All Company Employees</div>
                            <div class="text-[11px] text-gray-500 mt-0.5 leading-snug">Notify for all employees across the company regardless of department.</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Activity Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $activityDefinitions = [
                        'notify_clock_in' => [
                            'title' => 'Employee Clock-In',
                            'desc'  => 'Instant notification whenever an assigned team member clocks in to start work.',
                            'icon'  => 'fa-solid fa-right-to-bracket',
                            'badge' => 'Time Clock',
                            'color' => 'emerald',
                        ],
                        'notify_clock_out' => [
                            'title' => 'Employee Clock-Out',
                            'desc'  => 'Notification when a team member clocks out, showing total hours logged for the shift.',
                            'icon'  => 'fa-solid fa-right-from-bracket',
                            'badge' => 'Time Clock',
                            'color' => 'amber',
                        ],
                        'notify_time_submitted' => [
                            'title' => 'Timesheet / Manual Time Submission',
                            'desc'  => 'Alert when an employee logs manual hours or submits daily timesheets for approval.',
                            'icon'  => 'fa-solid fa-file-invoice',
                            'badge' => 'Timesheets',
                            'color' => 'indigo',
                        ],
                        'notify_time_off' => [
                            'title' => 'Time-Off & Leave Requests',
                            'desc'  => 'Alert when an employee requests vacation, PTO, or medical leave for review.',
                            'icon'  => 'fa-solid fa-plane-departure',
                            'badge' => 'Time Off',
                            'color' => 'purple',
                        ],
                        'notify_shift_assigned' => [
                            'title' => 'Shift Scheduled / Rescheduled',
                            'desc'  => 'Notice when shifts are created, modified, or assigned to employees in the department.',
                            'icon'  => 'fa-solid fa-calendar-day',
                            'badge' => 'Schedules',
                            'color' => 'sky',
                        ],
                        'notify_unusual' => [
                            'title' => 'Overtime & Unusual Activity Flag',
                            'desc'  => 'High-priority alert when an employee works beyond shift hours or logs overtime exceptions.',
                            'icon'  => 'fa-solid fa-triangle-exclamation',
                            'badge' => 'Compliance',
                            'color' => 'rose',
                        ],
                        'notify_geofence' => [
                            'title' => 'Geofence & GPS Discrepancy Alert',
                            'desc'  => 'Immediate alert when an employee clocks in outside authorized location radius or GPS is missing.',
                            'icon'  => 'fa-solid fa-location-crosshairs',
                            'badge' => 'Compliance',
                            'color' => 'amber',
                        ],
                    ];
                @endphp

                @foreach($activityDefinitions as $key => $meta)
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs flex flex-col justify-between space-y-3 hover:border-gray-300 transition">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center shrink-0">
                                        <i class="{{ $meta['icon'] }} text-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900">{{ $meta['title'] }}</h4>
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase">{{ $meta['badge'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                {{ $meta['desc'] }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="text-[11px] font-bold text-gray-400">Deliver via:</span>
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-gray-700">
                                    <input type="checkbox" wire:model="managerActivities.{{ $key }}.in_app"
                                           class="rounded text-[#005a9c] focus:ring-[#005a9c] w-3.5 h-3.5">
                                    <span class="text-xs">🔔 Bell</span>
                                </label>

                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-gray-700">
                                    <input type="checkbox" wire:model="managerActivities.{{ $key }}.toast"
                                           class="rounded text-[#005a9c] focus:ring-[#005a9c] w-3.5 h-3.5">
                                    <span class="text-xs">💬 Toast</span>
                                </label>

                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-gray-700">
                                    <input type="checkbox" wire:model="managerActivities.{{ $key }}.email"
                                           class="rounded text-[#005a9c] focus:ring-[#005a9c] w-3.5 h-3.5">
                                    <span class="text-xs">✉️ Email</span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end p-3 bg-white rounded-xl border border-gray-200 shadow-xs">
                <button type="button" wire:click="saveManagerSettings"
                        class="px-6 py-2 rounded-lg bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Manager Preferences
                </button>
            </div>
        </div>
    @endif

    {{-- TAB 3: NOTIFICATION EMAIL SENDER CONFIGURATION --}}
    @if($activeTab === 'email' && $isAdmin)
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-envelope-circle-check text-blue-600"></i> Notification Email Sender Configuration
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Configure the dedicated sender address and credentials used for outgoing automated notifications (shift alerts, approval notices, time off requests).
                    </p>
                </div>

                {{-- Global Email Notification Toggle --}}
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-900">Enable Email Notification Delivery</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Master toggle for sending email messages alongside in-app alerts.</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="notificationEmailEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#005a9c]"></div>
                    </label>
                </div>

                {{-- Sender Credentials Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Notification Sender Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="notificationFromEmail"
                               placeholder="notifications@yourdomain.com"
                               class="w-full text-xs rounded-lg border-gray-300 focus:border-[#005a9c] focus:ring-[#005a9c]">
                        @error('notificationFromEmail') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">This email address will appear in the "From" header of notification emails.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Notification Sender Display Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="notificationFromName"
                               placeholder="ClockVerse Notifications"
                               class="w-full text-xs rounded-lg border-gray-300 focus:border-[#005a9c] focus:ring-[#005a9c]">
                        @error('notificationFromName') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">The sender display name shown in employee email inboxes.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Reply-To Email Address (Optional)
                        </label>
                        <input type="email" wire:model="notificationReplyTo"
                               placeholder="support@yourdomain.com"
                               class="w-full text-xs rounded-lg border-gray-300 focus:border-[#005a9c] focus:ring-[#005a9c]">
                        @error('notificationReplyTo') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">Replies sent by employees to notification emails will be directed here.</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" wire:click="saveEmailSettings"
                            class="px-5 py-2 rounded-lg bg-[#005a9c] hover:bg-[#00487c] text-white text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Email Sender Settings
                    </button>
                </div>
            </div>

            {{-- Test Email Section --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-emerald-600"></i> Send Test Notification Email
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Verify that outgoing notification emails are properly delivered using the configured sender credentials.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="flex-1 w-full">
                        <input type="email" wire:model="testEmailRecipient"
                               placeholder="Enter recipient email address..."
                               class="w-full text-xs rounded-lg border-gray-300 focus:border-[#005a9c] focus:ring-[#005a9c]">
                        @error('testEmailRecipient') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" wire:click="sendTestEmail"
                            class="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition inline-flex items-center justify-center gap-2 shrink-0">
                        <i class="fa-solid fa-paper-plane"></i> Send Test Email
                    </button>
                </div>

                @if($testEmailResult)
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition
                         class="p-3 rounded-xl text-xs font-semibold flex items-center justify-between {{ $testEmailResult['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                        <span>{{ $testEmailResult['message'] }}</span>
                        <button type="button" @click="show = false; $wire.set('testEmailResult', null)" class="text-gray-400 hover:text-gray-700 font-bold text-base ml-2">&times;</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
