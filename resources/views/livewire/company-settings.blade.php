<div class="max-w-6xl mx-auto space-y-6 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span>⚙️</span> Company Settings & Systems
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">Manage branding, themes, server SMTP/IMAP email, employee email accounts, and view global email logs.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notification.settings') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-[#005a9c] hover:bg-blue-100 transition shadow-xs border border-blue-200">
                <i class="fa-solid fa-bell-concierge text-xs"></i> Notification Rules
            </a>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Super Admin Access
            </span>
        </div>
    </div>

    {{-- Toast Notification (Dispatched to Global Bootstrap Alert Container) --}}
    @if($toastMessage)
        <div x-data x-init="window.showToast(@js($toastMessage), @js($toastType ?? 'success'))" class="hidden"></div>
    @endif

    {{-- Nav Tabs --}}
    <div class="flex flex-wrap border-b border-gray-200 gap-1 text-xs font-bold">
        <button type="button" wire:click="setTab('general')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'general' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>🌐</span> General & Geofencing
        </button>
        <button type="button" wire:click="setTab('branding')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'branding' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>🎨</span> Branding & Themes
        </button>
        <button type="button" wire:click="setTab('email_server')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'email_server' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>✉️</span> Server Email (SMTP / IMAP)
        </button>
        <button type="button" wire:click="setTab('employee_emails')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'employee_emails' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>👥</span> Employee Email Accounts
        </button>
        <button type="button" wire:click="setTab('email_logs')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'email_logs' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>📜</span> Super Admin Email Logs
        </button>
        <button type="button" wire:click="setTab('navigation')"
                class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'navigation' ? 'border-[#005a9c] text-[#005a9c] bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
            <span>🧭</span> Navigation & Active Pages
        </button>
        @if(auth()->user()?->isSuperAdmin())
            <button type="button" wire:click="setTab('inactive_records')"
                    class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'inactive_records' ? 'border-sky-600 text-sky-700 bg-white shadow-xs font-bold' : 'border-transparent text-sky-600 hover:text-sky-800 hover:bg-sky-50' }}">
                <span>📦</span> Inactive Records Hub
            </button>
            <button type="button" wire:click="setTab('production_readiness')"
                    class="px-4 py-2.5 rounded-t-lg transition border-b-2 flex items-center gap-1.5 {{ $activeTab === 'production_readiness' ? 'border-rose-600 text-rose-700 bg-white shadow-xs font-bold' : 'border-transparent text-rose-600 hover:text-rose-800 hover:bg-rose-50' }}">
                <span>🧹</span> Data Shedding (Production Prep)
            </button>
        @endif
    </div>

    {{-- Connection Test Result Banner (Dispatched to Global Bootstrap Alert Container) --}}
    @if($testMessage)
        <div x-data x-init="window.showToast(@js($testMessage), @js($testStatus === 'success' ? 'success' : 'danger'))" class="hidden"></div>
    @endif

    {{-- TAB 1: General & Geofencing --}}
    @if($activeTab === 'general')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6"
             x-data="{ locPolicy: @entangle('locationPolicy').live }">
            <form wire:submit.prevent="saveGeneral" class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">Company / Organization Name</label>
                        @if(!$isSuperAdmin)
                            <span class="inline-flex items-center gap-1 text-[11px] text-amber-800 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                🔒 Managed by Administrator
                            </span>
                        @endif
                    </div>
                    <input type="text"
                           wire:model="companyName"
                           @if(!$isSuperAdmin) disabled readonly @endif
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-sm @if(!$isSuperAdmin) bg-gray-100 text-gray-600 cursor-not-allowed @endif">
                    @if(!$isSuperAdmin)
                        <span class="text-[11px] text-gray-400 mt-1 block">Critical organization settings can only be altered by an Administrator.</span>
                    @endif
                    @error('companyName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">Company Server Timezone</label>
                        @if(!$isSuperAdmin)
                            <span class="inline-flex items-center gap-1 text-[11px] text-amber-800 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                🔒 Managed by Administrator
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Baseline timezone for payroll, database timestamps, and server reporting. Employees can still set their local viewing timezone in Profile & Settings.
                    </p>
                    <select wire:model="serverTimezone"
                            @if(!$isSuperAdmin) disabled readonly @endif
                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-sm @if(!$isSuperAdmin) bg-gray-100 text-gray-600 cursor-not-allowed @endif">
                        @foreach($timezones as $tzKey => $tzLabel)
                            <option value="{{ $tzKey }}">{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    @if(!$isSuperAdmin)
                        <span class="text-[11px] text-gray-400 mt-1 block">Server baseline timezone can only be altered by an Administrator.</span>
                    @endif
                    @error('serverTimezone') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="border-t border-gray-100 pt-5 space-y-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">Payroll Week Start Day</label>
                        <p class="text-xs text-gray-500 mt-0.5">Determines the starting day for timesheets, schedules, and payroll cycle views (e.g., Thursday through Wednesday schedule).</p>
                    </div>
                    <select wire:model="weekStartDay"
                            class="w-full sm:w-80 border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-[#005a9c] focus:outline-none">
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                    @error('weekStartDay') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="border-t border-gray-100 pt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">GPS Location Tracking & Clock In/Out Policy</label>
                        <p class="text-xs text-gray-500 mt-0.5">Determine how ClockVerse handles employee time logs when clocking in/out relative to scheduled GPS coordinates and geofences.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label @click="locPolicy = 'flag'"
                               wire:click="$set('locationPolicy', 'flag')"
                               :class="locPolicy === 'flag' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $locationPolicy === 'flag' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="locationPolicy" value="flag" class="sr-only">
                            <span x-show="locPolicy === 'flag'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                <span>⚠️</span> Flag Entries Only
                            </span>
                            <span class="text-[11px] text-gray-500 mt-1 leading-snug">
                                Allow clock in/out, but flag entries outside the geofence as unusual activity for HR review.
                            </span>
                        </label>

                        <label @click="locPolicy = 'restrict'"
                               wire:click="$set('locationPolicy', 'restrict')"
                               :class="locPolicy === 'restrict' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $locationPolicy === 'restrict' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="locationPolicy" value="restrict" class="sr-only">
                            <span x-show="locPolicy === 'restrict'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                <span>🚫</span> Restrict & Block
                            </span>
                            <span class="text-[11px] text-gray-500 mt-1 leading-snug">
                                Strictly block clocking in or out if the employee is outside the authorized geofence radius or GPS is disabled.
                            </span>
                        </label>

                        <label @click="locPolicy = 'none'"
                               wire:click="$set('locationPolicy', 'none')"
                               :class="locPolicy === 'none' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $locationPolicy === 'none' ? 'border-2 border-[#005a9c] bg-blue-50/80 ring-2 ring-offset-1 ring-[#005a9c] shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="locationPolicy" value="none" class="sr-only">
                            <span x-show="locPolicy === 'none'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                <span>⚪</span> No Enforcement
                            </span>
                            <span class="text-[11px] text-gray-500 mt-1 leading-snug">
                                Do not track or enforce GPS location rules on time clocking.
                            </span>
                        </label>
                    </div>
                    @error('locationPolicy') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror

                    <div class="pt-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">Default Geofence Radius (Meters)</label>
                        <div class="mt-1 flex items-center gap-2 max-w-xs">
                            <input type="number" wire:model="defaultGeofenceRadius" min="10" max="10000" step="10"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-sm">
                            <span class="text-xs text-gray-500 font-semibold">meters</span>
                        </div>
                        <span class="text-[11px] text-gray-400 mt-1 block">Default radius if not explicitly specified on a shift or task (e.g., 200m).</span>
                        @error('defaultGeofenceRadius') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if(auth()->user()?->isSuperAdmin())
                        <div class="border-t border-gray-100 pt-4 mt-2">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" wire:model="allowHrHardDelete" class="rounded text-rose-600 focus:ring-rose-500">
                                <span class="text-xs font-bold text-gray-800">Allow HR Managers to Directly Hard Delete Records</span>
                            </label>
                            <p class="text-[11px] text-gray-500 mt-0.5 ml-6">
                                When enabled, HR Managers are permitted to permanently hard delete records (deleting child records upwards) instead of marking entries as inactive.
                            </p>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-5 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow transition">
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- TAB 2: Branding & Themes --}}
    @if($activeTab === 'branding')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-8"
             x-data="{ companyTheme: @entangle('companyTheme').live, companyFont: @entangle('companyFont').live, baseTheme: @entangle('applicationBaseTheme').live }">
            <form wire:submit.prevent="saveBranding" class="space-y-8">

                {{-- SUPER ADMIN MASTER CONFIGURATION: APPLICATION BASE THEME --}}
                <div class="p-6 rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white shadow-xl border border-slate-700/80 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/80 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-amber-400 text-slate-950 shadow-xs">Super Admin Master Setting</span>
                                <span class="text-xs text-indigo-300 font-semibold">• Core Architecture & Design System</span>
                            </div>
                            <h2 class="text-lg font-black text-white mt-1 flex items-center gap-2">
                                <span>⚡</span> Application Base Theme & Layout
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Switch between Classic ClockShark layout and a clean, high-contrast Modern SaaS layout optimized for maximum speed.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">Current Base:</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $applicationBaseTheme === 'modern' ? 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md' : 'bg-slate-700 text-slate-200' }}">
                                {{ $applicationBaseTheme === 'modern' ? '✨ Modern Layout' : '🏛️ Classic ClockShark' }}
                            </span>
                        </div>
                    </div>

                    {{-- Grid for Classic vs Modern --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- OPTION 1: CLASSIC --}}
                        <div @click="baseTheme = 'classic'; $wire.switchBaseTheme('classic')"
                             :class="baseTheme === 'classic' ? 'ring-2 ring-amber-400 bg-slate-800/90 border-amber-400 shadow-xl' : 'bg-slate-800/40 border-slate-700/60 hover:bg-slate-800/70 hover:border-slate-600'"
                             class="relative p-5 rounded-xl border cursor-pointer transition-all duration-200 flex flex-col justify-between">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">🏛️</span>
                                        <div>
                                            <h3 class="text-sm font-bold text-white">Classic Base Theme</h3>
                                            <span class="text-[10px] text-slate-400 font-medium">ClockShark Traditional Architecture</span>
                                        </div>
                                    </div>
                                    <span x-show="baseTheme === 'classic'" class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-slate-950 text-xs font-bold">✓</span>
                                </div>

                                {{-- Visual Mini Mockup of Classic --}}
                                <div class="h-20 w-full rounded-lg bg-[#f0f2f5] p-2 border border-slate-300 flex flex-col justify-between text-slate-800 shadow-inner overflow-hidden">
                                    <div class="h-4 bg-[#005a9c] rounded-xs flex items-center px-1.5 justify-between">
                                        <div class="h-2 w-12 bg-white/80 rounded-xs"></div>
                                        <div class="flex gap-1">
                                            <div class="h-1.5 w-6 bg-white/60 rounded-xs"></div>
                                            <div class="h-1.5 w-6 bg-white/60 rounded-xs"></div>
                                        </div>
                                    </div>
                                    <div class="flex gap-1.5 h-10">
                                        <div class="flex-1 bg-white border border-gray-300 rounded p-1 flex flex-col justify-around">
                                            <div class="h-1.5 w-14 bg-gray-400 rounded-xs"></div>
                                            <div class="h-2 w-8 bg-[#005a9c] rounded-xs"></div>
                                        </div>
                                        <div class="w-16 bg-white border border-gray-300 rounded p-1 flex flex-col justify-around">
                                            <div class="h-1.5 w-10 bg-gray-400 rounded-xs"></div>
                                            <div class="h-2 w-6 bg-emerald-600 rounded-xs"></div>
                                        </div>
                                    </div>
                                </div>

                                <ul class="text-[11px] text-slate-300 space-y-1 pt-1">
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-check text-slate-400 text-[10px]"></i> Traditional solid navy ClockShark top navigation bar</li>
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-check text-slate-400 text-[10px]"></i> Standard enterprise density & classic table layout</li>
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-check text-slate-400 text-[10px]"></i> Familiar legacy workforce management layout</li>
                                </ul>
                            </div>

                            <button type="button" @click.stop="baseTheme = 'classic'; $wire.switchBaseTheme('classic')"
                                    class="mt-4 w-full py-1.5 px-3 rounded-lg text-xs font-bold text-center transition border {{ $applicationBaseTheme === 'classic' ? 'bg-amber-400 text-slate-950 border-amber-400' : 'bg-slate-700/60 hover:bg-slate-700 text-slate-200 border-slate-600' }}">
                                {{ $applicationBaseTheme === 'classic' ? '✓ Currently Active (Classic)' : 'Select Classic' }}
                            </button>
                        </div>

                        {{-- OPTION 2: MODERN --}}
                        <div @click="baseTheme = 'modern'; $wire.switchBaseTheme('modern')"
                             :class="baseTheme === 'modern' ? 'ring-2 ring-indigo-400 bg-slate-800/90 border-indigo-400 shadow-xl shadow-indigo-950/50' : 'bg-slate-800/40 border-slate-700/60 hover:bg-slate-800/70 hover:border-slate-600'"
                             class="relative p-5 rounded-xl border cursor-pointer transition-all duration-200 flex flex-col justify-between">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">✨</span>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <h3 class="text-sm font-bold text-white">Modern Theme</h3>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-indigo-500 text-white">Non-ClockShark</span>
                                            </div>
                                            <span class="text-[10px] text-indigo-300 font-medium">Next-Gen SaaS Layout & High-Contrast Design</span>
                                        </div>
                                    </div>
                                    <span x-show="baseTheme === 'modern'" class="flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 to-sky-400 text-white text-xs font-bold">✓</span>
                                </div>

                                {{-- Visual Mini Mockup of Modern --}}
                                <div class="h-20 w-full rounded-lg bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-2 border border-indigo-500/40 flex flex-col justify-between text-white shadow-inner overflow-hidden">
                                    <div class="h-4 bg-slate-900 rounded-md flex items-center px-1.5 justify-between border border-slate-700/60">
                                        <div class="flex items-center gap-1">
                                            <div class="h-2 w-2 rounded-full bg-gradient-to-r from-indigo-400 to-sky-400"></div>
                                            <div class="h-1.5 w-10 bg-indigo-200 rounded-full"></div>
                                        </div>
                                        <div class="flex gap-1 items-center">
                                            <div class="h-1.5 w-7 bg-indigo-500/40 rounded-full border border-indigo-400/40"></div>
                                            <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex gap-1.5 h-10">
                                        <div class="flex-1 bg-slate-800 border border-slate-700/60 rounded-md p-1 flex flex-col justify-around shadow-sm">
                                            <div class="h-1.5 w-12 bg-slate-400 rounded-full"></div>
                                            <div class="h-2 w-10 bg-gradient-to-r from-indigo-400 to-sky-400 rounded-full"></div>
                                        </div>
                                        <div class="w-16 bg-slate-800 border border-slate-700/60 rounded-md p-1 flex flex-col justify-around shadow-sm">
                                            <div class="h-1.5 w-8 bg-slate-400 rounded-full"></div>
                                            <div class="h-2 w-8 bg-emerald-400 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>

                                <ul class="text-[11px] text-slate-300 space-y-1 pt-1">
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-palette text-sky-400 text-[10px]"></i> Sleek dark slate navigation header with high-contrast surfaces</li>
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-icons text-amber-400 text-[10px]"></i> Duotone styled icons inside soft pill containers</li>
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-wand-magic-sparkles text-pink-400 text-[10px]"></i> Modern card geometry, elevated borders, and crisp readability</li>
                                    <li class="flex items-center gap-1.5"><i class="fa-solid fa-bolt text-emerald-400 text-[10px]"></i> Ultra-fast page execution with zero render-blocking overhead</li>
                                </ul>
                            </div>

                            <button type="button" @click.stop="baseTheme = 'modern'; $wire.switchBaseTheme('modern')"
                                    class="mt-4 w-full py-1.5 px-3 rounded-lg text-xs font-bold text-center transition border {{ $applicationBaseTheme === 'modern' ? 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-indigo-400 shadow-md' : 'bg-slate-700/60 hover:bg-slate-700 text-slate-200 border-slate-600' }}">
                                {{ $applicationBaseTheme === 'modern' ? '✓ Currently Active (Modern)' : 'Select Modern' }}
                            </button>
                        </div>
                    </div>

                    {{-- Modern Advanced Visual Experience Toggles --}}
                    <div class="pt-3 border-t border-slate-700/80">
                        <div class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>🎛️</span> Modern Styling & Feature Toggles
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <label class="flex items-start gap-2.5 p-3 rounded-lg bg-slate-800/60 border border-slate-700/60 cursor-pointer hover:bg-slate-800 transition">
                                <input type="checkbox" wire:model.live="modernHighContrast" class="mt-0.5 rounded text-indigo-500 focus:ring-indigo-400">
                                <div>
                                    <span class="font-bold text-white block">High Contrast Mode</span>
                                    <span class="text-[10px] text-slate-400">Elevated surface borders and WCAG AAA readability</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-lg bg-slate-800/60 border border-slate-700/60 cursor-pointer hover:bg-slate-800 transition">
                                <input type="checkbox" wire:model.live="modernMicroAnimations" class="mt-0.5 rounded text-indigo-500 focus:ring-indigo-400">
                                <div>
                                    <span class="font-bold text-white block">Micro-Animations</span>
                                    <span class="text-[10px] text-slate-400">Card hover elevation, active button scale, smooth transitions</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-lg bg-slate-800/60 border border-slate-700/60 cursor-pointer hover:bg-slate-800 transition">
                                <input type="checkbox" wire:model.live="modernCustomIcons" class="mt-0.5 rounded text-indigo-500 focus:ring-indigo-400">
                                <div>
                                    <span class="font-bold text-white block">Duotone Styled Icons</span>
                                    <span class="text-[10px] text-slate-400">Modern badges with tinted pastel background pills</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Company Logo Section --}}
                <div class="space-y-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Company Logo & Favicon</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload or replace your organization logo. As configured, the logo doubles as the application favicon.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        {{-- Logo Preview --}}
                        <div class="w-36 h-28 bg-white border border-gray-200 rounded-lg p-2 flex flex-col items-center justify-center relative shadow-2xs">
                            @if ($newLogo)
                                <img src="{{ $newLogo->temporaryUrl() }}" alt="New Logo Preview" class="max-h-20 max-w-full object-contain">
                                <span class="absolute bottom-1 right-1 bg-amber-500 text-white text-[9px] px-1 rounded font-bold">New</span>
                            @elseif ($currentLogoPath)
                                <img src="{{ \App\Models\CompanySetting::getLogoUrl() ?? asset('storage/' . $currentLogoPath) }}" alt="Current Logo" class="max-h-20 max-w-full object-contain" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
                                <div class="text-center text-gray-400 hidden">
                                    <span class="text-3xl block">🏢</span>
                                    <span class="text-[10px] uppercase font-bold">Logo Preview Unavailable</span>
                                </div>
                            @else
                                <div class="text-center text-gray-400">
                                    <span class="text-3xl block">🏢</span>
                                    <span class="text-[10px] uppercase font-bold">No Logo</span>
                                </div>
                            @endif
                        </div>

                        {{-- Pick Logo Controls (No delete button as requested) --}}
                        <div class="space-y-3 flex-1">
                            <div>
                                <label class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 cursor-pointer shadow-xs transition">
                                    <span>📁</span> Pick / Replace Logo
                                    <input type="file" wire:model="newLogo" accept="image/*" class="sr-only">
                                </label>
                                <span wire:loading wire:target="newLogo" class="text-xs text-blue-600 font-semibold ml-2">Uploading image preview...</span>
                                @error('newLogo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="setAsFavicon" wire:model="setAsFavicon" class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                                <label for="setAsFavicon" class="text-xs font-semibold text-gray-700 cursor-pointer">
                                    Also set this logo as the site favicon
                                </label>
                            </div>
                            <p class="text-[11px] text-gray-400">Supported formats: PNG, JPG, SVG, WebP. Max 3MB. There is no delete option; you can replace it anytime with a new image.</p>
                        </div>
                    </div>
                </div>

                {{-- Global Theme Switcher --}}
                <div class="border-t border-gray-100 pt-6 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Application Global Theme</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Choose the default visual appearance for all employees and visitors. Individual users can also select their personal preference in their profile.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Light --}}
                        <label @click="companyTheme = 'light'"
                               wire:click="$set('companyTheme', 'light')"
                               :class="companyTheme === 'light' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $companyTheme === 'light' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="companyTheme" value="light" class="sr-only">
                            <span x-show="companyTheme === 'light'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <div class="h-16 w-full rounded-md border border-gray-200 bg-[#f9fafb] p-2 flex flex-col justify-between mb-3 shadow-xs">
                                <div class="h-3 w-16 bg-[#005a9c] rounded-xs"></div>
                                <div class="flex gap-1.5">
                                    <div class="h-4 flex-1 bg-white border border-gray-200 rounded-xs"></div>
                                    <div class="h-4 w-6 bg-blue-100 rounded-xs"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-900">☀️ Light (Default)</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Classic clean interface with high contrast light background.</span>
                        </label>

                        {{-- Dark --}}
                        <label @click="companyTheme = 'dark'"
                               wire:click="$set('companyTheme', 'dark')"
                               :class="companyTheme === 'dark' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $companyTheme === 'dark' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="companyTheme" value="dark" class="sr-only">
                            <span x-show="companyTheme === 'dark'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <div class="h-16 w-full rounded-md border border-gray-700 bg-[#111827] p-2 flex flex-col justify-between mb-3 shadow-xs">
                                <div class="h-3 w-16 bg-[#005a9c] rounded-xs"></div>
                                <div class="flex gap-1.5">
                                    <div class="h-4 flex-1 bg-[#1f2937] border border-gray-700 rounded-xs"></div>
                                    <div class="h-4 w-6 bg-[#374151] rounded-xs"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-900">🌙 Dark Mode</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Sleek midnight dark background easy on the eyes in low light.</span>
                        </label>

                        {{-- Gradient --}}
                        <label @click="companyTheme = 'gradient'"
                               wire:click="$set('companyTheme', 'gradient')"
                               :class="companyTheme === 'gradient' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $companyTheme === 'gradient' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="companyTheme" value="gradient" class="sr-only">
                            <span x-show="companyTheme === 'gradient'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <div class="h-16 w-full rounded-md border border-indigo-200 bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-50 p-2 flex flex-col justify-between mb-3 shadow-xs">
                                <div class="h-3 w-16 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xs"></div>
                                <div class="flex gap-1.5">
                                    <div class="h-4 flex-1 bg-white/80 border border-indigo-100 rounded-xs"></div>
                                    <div class="h-4 w-6 bg-indigo-200 rounded-xs"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-900">✨ Gradient Modern</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Vibrant, ultra-modern indigo and violet gradient aesthetics.</span>
                        </label>

                        {{-- Sage Dark Green --}}
                        <label @click="companyTheme = 'sage dark green'"
                               wire:click="$set('companyTheme', 'sage dark green')"
                               :class="companyTheme === 'sage dark green' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'"
                               class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none {{ $companyTheme === 'sage dark green' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="companyTheme" value="sage dark green" class="sr-only">
                            <span x-show="companyTheme === 'sage dark green'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                            <div class="h-16 w-full rounded-md border border-[#2b3e34] bg-[#141e1b] p-2 flex flex-col justify-between mb-3 shadow-xs">
                                <div class="h-3 w-16 bg-[#2d5a49] rounded-xs"></div>
                                <div class="flex gap-1.5">
                                    <div class="h-4 flex-1 bg-[#1f2f2a] border border-[#2b3e34] rounded-xs"></div>
                                    <div class="h-4 w-6 bg-[#3b544b] rounded-xs"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-900">🌿 Sage Dark Green</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Earthy forest dark tones with calming sage and emerald accents.</span>
                        </label>
                    </div>
                </div>

                {{-- Global Typography / Font Family (Dropdown & Live Preview) --}}
                <div class="border-t border-gray-100 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Global Typography / Font Family</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Select the default application typography. Aptos Display is recommended, or select from popular web fonts.</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-[#005a9c] border border-blue-200">
                            Live Preview
                        </span>
                    </div>

                    {{-- Font Dropdown Selection --}}
                    <div class="max-w-md">
                        <label for="company_font_select" class="block text-xs font-semibold text-gray-700 mb-1">Company-Wide Font</label>
                        <select id="company_font_select"
                                wire:model.live="companyFont"
                                class="block w-full pl-3 pr-10 py-2 text-sm border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#005a9c] focus:border-[#005a9c] rounded-lg shadow-sm bg-white font-medium text-gray-800">
                            @foreach($availableFonts as $fontId => $font)
                                <option value="{{ $fontId }}">{{ $font['name'] }} ({{ $font['category'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Live Typography Preview Card --}}
                    @php
                        $activeFontCss = \App\Models\CompanySetting::getFontCss($companyFont ?? 'aptos');
                        $activeFontMeta = $availableFonts[$companyFont ?? 'aptos'] ?? ['name' => ucfirst($companyFont ?? 'aptos'), 'category' => 'Font'];
                    @endphp
                    <div class="p-4 rounded-xl border-2 border-blue-200 bg-gradient-to-br from-white to-blue-50/40 shadow-sm"
                         style="font-family: {{ $activeFontCss }};">
                        <div class="flex flex-wrap items-center justify-between pb-3 mb-3 border-b border-blue-100 gap-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-bold text-gray-900">{{ $activeFontMeta['name'] }}</span>
                                <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-0.5 rounded bg-blue-100 text-[#005a9c]">
                                    {{ $activeFontMeta['category'] }}
                                </span>
                                @if(!empty($activeFontMeta['is_custom']))
                                    <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                                        Custom Font
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] text-gray-500 font-mono">
                                {{ $activeFontCss }}
                            </span>
                        </div>

                        <div class="space-y-2.5 text-gray-800">
                            {{-- Regular 400 --}}
                            <div class="flex items-baseline space-x-3">
                                <span class="text-[11px] font-mono text-gray-400 w-16 shrink-0">Normal 400</span>
                                <p class="text-sm leading-relaxed">
                                    The quick brown fox jumps over the lazy dog. 0123456789 (Accurate time tracking &amp; employee shifts)
                                </p>
                            </div>

                            {{-- Bold 700 --}}
                            <div class="flex items-baseline space-x-3">
                                <span class="text-[11px] font-mono text-gray-400 w-16 shrink-0 font-bold">Bold 700</span>
                                <p class="text-sm font-bold leading-relaxed text-gray-900">
                                    Workforce Management, Executive Dashboard &amp; Relational Timesheet Approvals
                                </p>
                            </div>

                            {{-- Italic --}}
                            <div class="flex items-baseline space-x-3">
                                <span class="text-[11px] font-mono text-gray-400 w-16 shrink-0 italic">Italic</span>
                                <p class="text-sm italic leading-relaxed text-gray-700">
                                    "Precision in scheduling and seamless execution leads to superior team performance."
                                </p>
                            </div>

                            {{-- UI Mockup Elements --}}
                            <div class="pt-2 flex flex-wrap items-center gap-2 border-t border-blue-50">
                                <span class="text-[11px] font-mono text-gray-400 w-16 shrink-0">UI Sample</span>
                                <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded bg-[#005a9c] text-white shadow-xs pointer-events-none">
                                    Action Button
                                </button>
                                <span class="px-2 py-0.5 text-xs font-medium rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Approved
                                </span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800 border border-gray-200">
                                    09:00 - 17:30 (8.5h)
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- SUPER ADMIN: Custom Fonts Management Panel --}}
                    @if($isSuperAdmin)
                        <div class="mt-6 p-4 rounded-xl border border-gray-200 bg-gray-50/70 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 flex items-center gap-1.5">
                                        <span>👑</span> Super Admin Font Manager: Add Custom Web Fonts
                                    </h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Register new Google Fonts or custom CSS font families for company and employee selection.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700">Font Name *</label>
                                    <input type="text"
                                           wire:model="newFontName"
                                           placeholder="e.g. Outfit, Nunito Sans"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs bg-white">
                                    @error('newFontName') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700">CSS Font Family *</label>
                                    <input type="text"
                                           wire:model="newFontCss"
                                           placeholder="e.g. 'Outfit', sans-serif"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs bg-white">
                                    @error('newFontCss') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700">Google Font URL (Optional)</label>
                                    <input type="url"
                                           wire:model="newFontGoogleUrl"
                                           placeholder="https://fonts.googleapis.com/css2?family=..."
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs bg-white">
                                    @error('newFontGoogleUrl') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="button"
                                        wire:click="addCustomFont"
                                        class="px-3.5 py-1.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                                    <span>➕</span> Register Font
                                </button>
                            </div>

                            {{-- Registered Custom Fonts List --}}
                            @php
                                $customFonts = array_filter($availableFonts, fn($f) => !empty($f['is_custom']));
                            @endphp
                            @if(count($customFonts) > 0)
                                <div class="pt-3 border-t border-gray-200">
                                    <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-2">Registered Custom Fonts</span>
                                    <div class="space-y-2">
                                        @foreach($customFonts as $cFontId => $cFont)
                                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-gray-200 shadow-2xs">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-900">{{ $cFont['name'] }}</span>
                                                    <span class="text-[11px] text-gray-500 font-mono ml-2">{{ $cFont['css'] }}</span>
                                                    @if(!empty($cFont['google_url']))
                                                        <span class="text-[10px] text-blue-600 ml-2">🌐 Google Font linked</span>
                                                    @endif
                                                </div>
                                                <button type="button"
                                                        wire:click="removeCustomFont('{{ $cFontId }}')"
                                                        wire:confirm="Are you sure you want to remove this custom font?"
                                                        class="text-xs text-red-600 hover:text-red-800 font-semibold px-2 py-1 rounded hover:bg-red-50 transition">
                                                    Remove
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-5 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow transition">
                        Save Branding & Theme
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- TAB 3: Server Email (SMTP / IMAP) --}}
    @if($activeTab === 'email_server')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <form wire:submit.prevent="saveServerEmail" class="space-y-6">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Default Server SMTP Settings</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Used for system notifications, password resets, timesheet approvals, and general outgoing emails.</p>
                </div>

                {{-- Quick Presets --}}
                <div class="flex flex-wrap items-center justify-between gap-3 bg-blue-50/70 border border-blue-200 rounded-xl p-3.5">
                    <div>
                        <h4 class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                            <span>⚡</span> Quick Provider Presets
                        </h4>
                        <p class="text-[11px] text-blue-700 mt-0.5">Autofill recommended host, port, and encryption settings with a single click.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="applyPreset('hostinger')"
                                class="px-3 py-1.5 bg-[#673de6] hover:bg-[#5229cf] text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                            <span>🟣</span> Hostinger (Port 465 SSL)
                        </button>
                        <button type="button" wire:click="applyPreset('gmail')"
                                class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                            <span>🔴</span> Gmail (587 TLS)
                        </button>
                        <button type="button" wire:click="applyPreset('office365')"
                                class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                            <span>🔵</span> Microsoft 365
                        </button>
                    </div>
                </div>

                {{-- Hostinger Setup Tip --}}
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900 flex items-start gap-2.5">
                    <span class="text-base shrink-0">💡</span>
                    <div>
                        <span class="font-bold">Hostinger Email Guidelines:</span>
                        <div class="mt-0.5 text-[11px] text-amber-800 space-y-0.5">
                            <div>&bull; <strong>Host:</strong> <code class="bg-amber-100 px-1 rounded">smtp.hostinger.com</code> &bull; <strong>Port:</strong> <code class="bg-amber-100 px-1 rounded">465</code> &bull; <strong>Encryption:</strong> <code class="bg-amber-100 px-1 rounded">SSL</code></div>
                            <div>&bull; <strong>Username:</strong> Must be your full email account (e.g. <code class="bg-amber-100 px-1 rounded">user@yourdomain.com</code>), not just the username prefix.</div>
                            <div>&bull; <strong>Sender "From" Address:</strong> Must match your Hostinger mailbox account to prevent rejection.</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700">SMTP Host *</label>
                        <input type="text" wire:model="mailHost" placeholder="smtp.mailgun.org or smtp.gmail.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        @error('mailHost') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700">SMTP Port *</label>
                        <input type="number" wire:model="mailPort" placeholder="587" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        @error('mailPort') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700">Encryption</label>
                        <select wire:model="mailEncryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                            <option value="tls">TLS (STARTTLS)</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700">SMTP Username / Email</label>
                        <input type="text" wire:model="mailUsername" placeholder="postmaster@yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700">SMTP Password</label>
                        <input type="password" wire:model="mailPassword" placeholder="••••••••" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700">Sender "From" Address</label>
                        <input type="email" wire:model="mailFromAddress" placeholder="notifications@clockvertex.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700">Sender "From" Name</label>
                        <input type="text" wire:model="mailFromName" placeholder="ClockVerse Notifications" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Default Server IMAP Settings (Incoming Mail)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">For synchronizing and receiving time off emails, employee replies, and server mail logging.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700">IMAP Host</label>
                            <input type="text" wire:model="imapHost" placeholder="imap.gmail.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700">IMAP Port</label>
                            <input type="number" wire:model="imapPort" placeholder="993" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700">Encryption</label>
                            <select wire:model="imapEncryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                                <option value="ssl">SSL</option>
                                <option value="tls">TLS</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700">IMAP Username</label>
                            <input type="text" wire:model="imapUsername" placeholder="inbox@yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700">IMAP Password</label>
                            <input type="password" wire:model="imapPassword" placeholder="••••••••" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        </div>
                    </div>
                </div>

                {{-- Action & Test Buttons --}}
                <div class="border-t border-gray-100 pt-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="testServerSmtp" wire:loading.attr="disabled"
                                class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-gray-300">
                            <span>📡</span>
                            <span wire:loading.remove wire:target="testServerSmtp">Test Server SMTP</span>
                            <span wire:loading wire:target="testServerSmtp">Testing SMTP...</span>
                        </button>

                        <button type="button" wire:click="testServerImap" wire:loading.attr="disabled"
                                class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border border-gray-300">
                            <span>📥</span>
                            <span wire:loading.remove wire:target="testServerImap">Test Server IMAP</span>
                            <span wire:loading wire:target="testServerImap">Testing IMAP...</span>
                        </button>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow transition">
                        Save Server Email Settings
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- TAB 4: Employee Email Accounts --}}
    @if($activeTab === 'employee_emails')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">Employee Email Account Management</h2>
                <p class="text-xs text-gray-500 mt-0.5">Configure individual SMTP and IMAP settings for specific employees, or let them use the default server credentials.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Employee Selector List --}}
                <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/50 space-y-2 max-h-[500px] overflow-y-auto">
                    <span class="text-xs font-bold text-gray-600 block uppercase tracking-wider">Select Employee</span>
                    @foreach($employees as $emp)
                        <button type="button" wire:click="selectEmployee({{ $emp->id }})"
                                class="w-full text-left p-2.5 rounded-lg text-xs transition flex items-center justify-between {{ $selectedEmployeeId === $emp->id ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'bg-white hover:bg-gray-100 text-gray-800 border border-gray-200' }}">
                            <div class="truncate">
                                <div class="font-semibold">{{ $emp->name }}</div>
                                <div class="text-[10px] {{ $selectedEmployeeId === $emp->id ? 'text-blue-100' : 'text-gray-400' }} truncate">{{ $emp->email }}</div>
                            </div>
                            @if($emp->use_custom_email)
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase {{ $selectedEmployeeId === $emp->id ? 'bg-white text-blue-900' : 'bg-amber-100 text-amber-800' }}">Custom</span>
                            @else
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase {{ $selectedEmployeeId === $emp->id ? 'bg-blue-800 text-blue-100' : 'bg-gray-100 text-gray-500' }}">Server</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Selected Employee Configuration Form --}}
                <div class="md:col-span-2 space-y-5">
                    @if($selectedEmployeeId)
                        @php $currentEmp = $employees->firstWhere('id', $selectedEmployeeId); @endphp
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-blue-900">Configuring: {{ $currentEmp->name ?? 'Employee' }}</span>
                                <span class="text-[11px] text-blue-700 block">{{ $currentEmp->email ?? '' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="empUseCustom" wire:model.live="empUseCustomEmail" class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                                <label for="empUseCustom" class="text-xs font-bold text-gray-700 cursor-pointer">
                                    Use Custom SMTP / IMAP
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Employee SMTP Outgoing Settings</h3>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Presets:</span>
                                    <button type="button" wire:click="applyEmployeePreset('hostinger')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition shadow-xs">
                                        🟣 Hostinger
                                    </button>
                                    <button type="button" wire:click="applyEmployeePreset('gmail')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition shadow-xs">
                                        🔴 Gmail
                                    </button>
                                    <button type="button" wire:click="applyEmployeePreset('office365')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition shadow-xs">
                                        🔵 Microsoft 365
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Host</label>
                                    <input type="text" wire:model="empSmtpHost" placeholder="smtp.hostinger.com" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Port</label>
                                    <input type="number" wire:model="empSmtpPort" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Encryption</label>
                                    <select wire:model="empSmtpEncryption" class="w-full border rounded p-1.5 text-xs">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Username / Email</label>
                                    <input type="text" wire:model="empSmtpUsername" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Password</label>
                                    <input type="password" wire:model="empSmtpPassword" placeholder="••••••••" class="w-full border rounded p-1.5 text-xs">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Sender "From" Address</label>
                                    <input type="email" wire:model="empSmtpFromAddress" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Sender "From" Name</label>
                                    <input type="text" wire:model="empSmtpFromName" class="w-full border rounded p-1.5 text-xs">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Employee IMAP Incoming Settings</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">IMAP Host</label>
                                    <input type="text" wire:model="empImapHost" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Port</label>
                                    <input type="number" wire:model="empImapPort" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">Encryption</label>
                                    <select wire:model="empImapEncryption" class="w-full border rounded p-1.5 text-xs">
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">IMAP Username</label>
                                    <input type="text" wire:model="empImapUsername" class="w-full border rounded p-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 block">IMAP Password</label>
                                    <input type="password" wire:model="empImapPassword" placeholder="••••••••" class="w-full border rounded p-1.5 text-xs">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="testEmployeeSmtp" wire:loading.attr="disabled"
                                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold border transition">
                                    <span wire:loading.remove wire:target="testEmployeeSmtp">Test SMTP</span>
                                    <span wire:loading wire:target="testEmployeeSmtp">Testing...</span>
                                </button>
                                <button type="button" wire:click="testEmployeeImap" wire:loading.attr="disabled"
                                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold border transition">
                                    <span wire:loading.remove wire:target="testEmployeeImap">Test IMAP</span>
                                    <span wire:loading wire:target="testEmployeeImap">Testing...</span>
                                </button>
                            </div>

                            <button type="button" wire:click="saveEmployeeEmail"
                                    class="px-4 py-2 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow transition">
                                Save Employee Email Settings
                            </button>
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-400">
                            Please select an employee on the left to configure their email settings.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 5: Super Admin Email Logs --}}
    @if($activeTab === 'email_logs')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">System Outgoing Email Logs</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Central audit trail of all emails sent by ClockVerse. Resend failed messages with a single click.</p>
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="logSearch" placeholder="Search recipient, subject..."
                               class="text-xs rounded-lg border-gray-300 py-1.5 pl-3 pr-8 shadow-xs focus:ring-[#005a9c] focus:border-[#005a9c]">
                    </div>

                    <div class="flex items-center gap-1 bg-gray-100 p-0.5 rounded-lg text-xs font-bold">
                        <button type="button" wire:click="$set('logStatus', 'all')"
                                class="px-2.5 py-1 rounded-md transition {{ $logStatus === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                            All
                        </button>
                        <button type="button" wire:click="$set('logStatus', 'sent')"
                                class="px-2.5 py-1 rounded-md transition {{ $logStatus === 'sent' ? 'bg-white text-emerald-700 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                            Sent
                        </button>
                        <button type="button" wire:click="$set('logStatus', 'failed')"
                                class="px-2.5 py-1 rounded-md transition {{ $logStatus === 'failed' ? 'bg-white text-rose-700 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                            Failed
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-gray-600 font-bold uppercase tracking-wider">
                            <th class="p-3">Sent / Date</th>
                            <th class="p-3">Recipient</th>
                            <th class="p-3">Subject</th>
                            <th class="p-3 w-24 text-center">Status</th>
                            <th class="p-3 text-right w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($emailLogs as $log)
                            <tr class="hover:bg-gray-50/80 transition {{ $log->status === 'failed' ? 'bg-rose-50/30' : '' }}">
                                <td class="p-3 text-gray-500 whitespace-nowrap">
                                    <div class="font-semibold text-gray-800">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $log->created_at->format('h:i:s A') }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-gray-900">{{ $log->recipient }}</div>
                                    @if($log->user)
                                        <div class="text-[10px] text-gray-500">{{ $log->user->name }}</div>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="font-medium text-gray-800">{{ $log->subject }}</div>
                                    @if($log->error_message)
                                        <div class="text-[10px] text-rose-600 mt-0.5 truncate max-w-md font-mono" title="{{ $log->error_message }}">
                                            Error: {{ $log->error_message }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ✓ Sent
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200" title="{{ $log->error_message }}">
                                            ❌ Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            ⏳ {{ ucfirst($log->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <button type="button" wire:click="viewLog({{ $log->id }})"
                                            class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded font-semibold transition">
                                        View
                                    </button>

                                    <button type="button" wire:click="resendEmail({{ $log->id }})" wire:loading.attr="disabled"
                                            class="px-2.5 py-1 text-xs {{ $log->status === 'failed' ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-blue-50 text-[#005a9c] hover:bg-blue-100' }} rounded font-bold transition inline-flex items-center gap-1">
                                        <span>🔄</span>
                                        <span>Resend</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    No email logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $emailLogs->links() }}
            </div>
        </div>

        {{-- Email Detail Modal --}}
        @if($viewingLog)
            <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 space-y-4 max-h-[85vh] flex flex-col">
                    <div class="flex items-start justify-between border-b pb-3">
                        <div>
                            <span class="text-xs uppercase font-bold text-gray-400 tracking-wider">Email Details #{{ $viewingLog->id }}</span>
                            <h3 class="text-base font-bold text-gray-900">{{ $viewingLog->subject }}</h3>
                        </div>
                        <button type="button" wire:click="closeViewLog" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs bg-gray-50 p-3 rounded-lg border">
                        <div><strong class="text-gray-500">To:</strong> {{ $viewingLog->recipient }}</div>
                        <div><strong class="text-gray-500">Status:</strong> <span class="font-bold {{ $viewingLog->status === 'sent' ? 'text-emerald-700' : 'text-rose-700' }}">{{ ucfirst($viewingLog->status) }}</span></div>
                        <div><strong class="text-gray-500">Sent At:</strong> {{ $viewingLog->sent_at ? $viewingLog->sent_at->format('M d, Y h:i A') : 'Not sent' }}</div>
                        <div><strong class="text-gray-500">Retries:</strong> {{ $viewingLog->retry_count }}</div>
                        @if($viewingLog->error_message)
                            <div class="col-span-2 text-rose-700 font-mono text-[11px] bg-rose-50 p-2 rounded border border-rose-200">
                                <strong>Error:</strong> {{ $viewingLog->error_message }}
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 overflow-y-auto border rounded-lg p-3 bg-white text-xs text-gray-800 whitespace-pre-wrap">
                        {!! $viewingLog->body ?: '<span class="text-gray-400 italic">No body content recorded</span>' !!}
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t">
                        <button type="button" wire:click="resendEmail({{ $viewingLog->id }})"
                                class="px-4 py-2 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 transition">
                            <span>🔄</span> Resend This Email
                        </button>
                        <button type="button" wire:click="closeViewLog"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- TAB 6: Navigation & Active Pages --}}
    @if($activeTab === 'inactive_records' && auth()->user()?->isSuperAdmin())
        <div class="mt-4">
            <livewire:inactive-records-manager />
        </div>
    @endif

    @if($activeTab === 'production_readiness' && auth()->user()?->isSuperAdmin())
        <div class="mt-4">
            <livewire:data-shedding />
        </div>
    @endif

    @if($activeTab === 'navigation')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            {{-- Header Actions Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-gray-200">
                <div>
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>🧭</span> Navigation & Active Pages Management
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Choose which pages, application modules, and navigation links are visible throughout the app.
                        Disabled pages are automatically hidden from the top navigation bar, mobile menu drawer, and global search.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="enableAllNavPages"
                            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <span>✓</span> Enable All Pages
                    </button>
                    <button type="button" wire:click="disableAllNavPages"
                            class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
                        <span>⚠️</span> Core Only
                    </button>
                    <button type="button" wire:click="saveNavigationPages"
                            class="px-4 py-1.5 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <span>💾</span> Save Navigation
                    </button>
                </div>
            </div>

            {{-- Categories and Pages Grid --}}
            @php
                $groupedPages = collect($allNavPages)->groupBy('category');
            @endphp

            <div class="space-y-6">
                @foreach($groupedPages as $category => $pages)
                    @php
                        $activeCount = $pages->filter(fn($p, $k) => $activeNavPages[$k] ?? true)->count();
                        $totalCount = $pages->count();
                    @endphp
                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                        <div class="bg-gray-50/80 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $activeCount > 0 ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ $category }}
                            </h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $activeCount === $totalCount ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($activeCount === 0 ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-800 border border-amber-200') }}">
                                {{ $activeCount }} / {{ $totalCount }} Active
                            </span>
                        </div>

                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 bg-white">
                            @foreach($pages as $pageKey => $pageInfo)
                                @php
                                    $isActive = (bool) ($activeNavPages[$pageKey] ?? true);
                                @endphp
                                <div class="p-3.5 rounded-lg border transition flex items-start justify-between gap-3 {{ $isActive ? 'border-blue-200 bg-blue-50/20' : 'border-gray-200 bg-gray-50/40 opacity-70' }}">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 {{ $isActive ? 'bg-[#005a9c] text-white shadow-xs' : 'bg-gray-200 text-gray-500' }}">
                                            <i class="{{ $pageInfo['icon'] }} text-xs"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-900 truncate">{{ $pageInfo['name'] }}</span>
                                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.2 rounded {{ $isActive ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-gray-200 text-gray-600' }}">
                                                    {{ $isActive ? 'Active' : 'Disabled' }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-1 line-clamp-2 leading-relaxed">
                                                {{ $pageInfo['description'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="shrink-0 pt-0.5">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   wire:click="toggleNavPage('{{ $pageKey }}')"
                                                   @checked($isActive)
                                                   class="sr-only peer">
                                            <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#005a9c]"></div>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="button" wire:click="saveNavigationPages"
                        class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-2 shadow-xs">
                    <span>💾</span> Save All Navigation Settings
                </button>
            </div>
        </div>
    @endif
</div>
