<section class="space-y-6">
    {{-- Toast and Validation Notifications (Dispatched to Global Bootstrap Alert Container) --}}
    @if($toastMessage)
        <div x-data x-init="window.showToast(@js($toastMessage), @js($toastType ?? 'success'))" class="hidden"></div>
    @endif

    @if($validationMessage)
        <div x-data x-init="window.showToast(@js($validationMessage), @js($validationStatus === 'success' ? 'success' : 'danger'))" class="hidden"></div>
    @endif

    {{-- TAB CONTENT: THEME & REGIONAL --}}
    @if($activeTab === 'theme' || $activeTab === 'all')
        <div class="space-y-6" x-data="{ activeTheme: @entangle('theme_preference').live, activeFont: @entangle('font_preference').live }">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Application Theme</h3>
                <p class="text-xs text-gray-500 mt-0.5">Choose your visual appearance and contrast style for ClockVerse.</p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                    {{-- Light --}}
                    <label @click="activeTheme = 'light'"
                           wire:click="$set('theme_preference', 'light')"
                           :class="activeTheme === 'light' ? 'border-[#005a9c] ring-2 ring-[#005a9c]/50 bg-blue-50/80 shadow-xs' : 'border-gray-200 hover:bg-gray-50'"
                           class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition select-none">
                        <input type="radio" wire:model.live="theme_preference" value="light" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">☀️ Light</span>
                            <span x-show="activeTheme === 'light'" class="w-4 h-4 rounded-full bg-[#005a9c] text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                        </div>
                        <div class="h-10 w-full rounded bg-white border border-gray-200 p-1 flex flex-col justify-between">
                            <div class="h-2 w-10 bg-[#005a9c] rounded-xs"></div>
                            <div class="h-2 w-full bg-gray-100 rounded-xs"></div>
                        </div>
                        <span class="text-[10px] text-gray-500 mt-1.5">Classic clean interface</span>
                    </label>

                    {{-- Dark --}}
                    <label @click="activeTheme = 'dark'"
                           wire:click="$set('theme_preference', 'dark')"
                           :class="activeTheme === 'dark' ? 'border-[#005a9c] ring-2 ring-[#005a9c]/50 bg-blue-50/80 shadow-xs' : 'border-gray-200 hover:bg-gray-50'"
                           class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition select-none">
                        <input type="radio" wire:model.live="theme_preference" value="dark" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">🌙 Dark Mode</span>
                            <span x-show="activeTheme === 'dark'" class="w-4 h-4 rounded-full bg-[#005a9c] text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                        </div>
                        <div class="h-10 w-full rounded bg-slate-900 border border-slate-700 p-1 flex flex-col justify-between">
                            <div class="h-2 w-10 bg-[#005a9c] rounded-xs"></div>
                            <div class="h-2 w-full bg-slate-800 rounded-xs"></div>
                        </div>
                        <span class="text-[10px] text-gray-500 mt-1.5">Midnight dark palette</span>
                    </label>

                    {{-- Gradient --}}
                    <label @click="activeTheme = 'gradient'"
                           wire:click="$set('theme_preference', 'gradient')"
                           :class="activeTheme === 'gradient' ? 'border-[#005a9c] ring-2 ring-[#005a9c]/50 bg-blue-50/80 shadow-xs' : 'border-gray-200 hover:bg-gray-50'"
                           class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition select-none">
                        <input type="radio" wire:model.live="theme_preference" value="gradient" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">✨ Gradient</span>
                            <span x-show="activeTheme === 'gradient'" class="w-4 h-4 rounded-full bg-[#005a9c] text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                        </div>
                        <div class="h-10 w-full rounded bg-gradient-to-tr from-slate-900 via-indigo-950 to-blue-900 border border-indigo-700 p-1 flex flex-col justify-between">
                            <div class="h-2 w-10 bg-indigo-400 rounded-xs"></div>
                            <div class="h-2 w-full bg-indigo-900/60 rounded-xs"></div>
                        </div>
                        <span class="text-[10px] text-gray-500 mt-1.5">Modern indigo & violet</span>
                    </label>

                    {{-- Sage Dark Green --}}
                    <label @click="activeTheme = 'sage dark green'"
                           wire:click="$set('theme_preference', 'sage dark green')"
                           :class="activeTheme === 'sage dark green' ? 'border-[#005a9c] ring-2 ring-[#005a9c]/50 bg-blue-50/80 shadow-xs' : 'border-gray-200 hover:bg-gray-50'"
                           class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition select-none">
                        <input type="radio" wire:model.live="theme_preference" value="sage dark green" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">🌿 Sage Green</span>
                            <span x-show="activeTheme === 'sage dark green'" class="w-4 h-4 rounded-full bg-[#005a9c] text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                        </div>
                        <div class="h-10 w-full rounded bg-[#131c18] border border-[#2d423a] p-1 flex flex-col justify-between">
                            <div class="h-2 w-10 bg-emerald-500 rounded-xs"></div>
                            <div class="h-2 w-full bg-[#27473b] rounded-xs"></div>
                        </div>
                        <span class="text-[10px] text-gray-500 mt-1.5">Deep forest & sage dark</span>
                    </label>
                </div>
            </div>

            {{-- FONT FAMILY SELECTION (Dropdown & Live Preview) --}}
            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Typography & Font Family</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Select your preferred reading typography. Aptos Display provides optimal clarity, or choose from popular web fonts.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-[#005a9c] border border-blue-200">
                        Live Preview Active
                    </span>
                </div>

                {{-- Dropdown Selection --}}
                <div class="max-w-md">
                    <label for="font_preference_select" class="block text-xs font-semibold text-gray-700 mb-1">Select Font</label>
                    <div class="relative">
                        <select id="font_preference_select"
                                wire:model.live="font_preference"
                                class="block w-full pl-3 pr-10 py-2 text-sm border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#005a9c] focus:border-[#005a9c] rounded-lg shadow-sm bg-white font-medium text-gray-800">
                            @foreach($availableFonts as $fId => $font)
                                <option value="{{ $fId }}">{{ $font['name'] }} ({{ $font['category'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Live Typography Preview Card --}}
                @php
                    $activeFontCss = \App\Models\CompanySetting::getFontCss($font_preference ?? 'aptos');
                    $activeFontMeta = $availableFonts[$font_preference ?? 'aptos'] ?? ['name' => ucfirst($font_preference ?? 'aptos'), 'category' => 'Font'];
                @endphp
                <div class="mt-4 p-4 rounded-xl border-2 border-blue-200 bg-gradient-to-br from-white to-blue-50/40 shadow-sm"
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
                                Workforce Management, Department Rostering &amp; Relational Timesheet Approvals
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
                                Primary Button
                            </button>
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Active Status
                            </span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800 border border-gray-200">
                                08:30 - 17:00 (8.5 hrs)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Regional & Notifications Preferences --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-5">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="user_timezone" class="block text-xs font-bold text-gray-700">My Viewing Timezone</label>
                        <span class="text-[10px] font-semibold text-[#005a9c] bg-blue-50 px-2 py-0.5 rounded border border-blue-200">24H Format (H:i) Active</span>
                    </div>
                    <select id="user_timezone" wire:model="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        @foreach($timezones as $tzValue => $tzLabel)
                            <option value="{{ $tzValue }}">{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-1 block">All times across the app are displayed in 24H format according to this timezone.</span>
                </div>

                <div>
                    <label for="user_channel" class="block text-xs font-bold text-gray-700">Primary Delivery Channel</label>
                    <select id="user_channel" wire:model="notification_channel_preference" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        <option value="both">In-App Bell & Email (Both)</option>
                        <option value="bell_only">In-App Bell Icon Only</option>
                        <option value="email_only">Email Notifications Only</option>
                    </select>
                </div>
            </div>

            {{-- Detailed Notification Events --}}
            <div class="border-t border-gray-100 pt-4 space-y-3">
                <span class="block text-xs font-bold uppercase tracking-wider text-gray-700">Notification Alerts</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" wire:model="detailed_notification_settings.shift_assigned.bell" value="1" class="rounded text-[#005a9c]">
                        <span class="text-gray-800 font-medium">Shift assigned or updated</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" wire:model="detailed_notification_settings.time_approval.bell" value="1" class="rounded text-[#005a9c]">
                        <span class="text-gray-800 font-medium">Timesheet approved / rejected</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" wire:model="detailed_notification_settings.unusual_activity.bell" value="1" class="rounded text-[#005a9c]">
                        <span class="text-gray-800 font-medium">Geofence & unusual alerts</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" wire:model="detailed_notification_settings.time_submitted.bell" value="1" class="rounded text-[#005a9c]">
                        <span class="text-gray-800 font-medium">Time submitted for review</span>
                    </label>
                </div>
            </div>

            {{-- Save Button for Theme Tab --}}
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[11px] text-gray-400">Settings apply only to your account.</span>
                <button type="button" wire:click="saveTheme"
                        class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Theme & Preferences</span>
                </button>
            </div>
        </div>
    @endif

    {{-- TAB CONTENT: EMAIL & WEBMAIL ACCOUNTS --}}
    @if($activeTab === 'email' || $activeTab === 'all')
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Personal Mailbox Credentials (SMTP & IMAP)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Send and receive notifications and webmail using your individual mail server credentials.</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg">
                    <input type="checkbox" wire:model.live="use_custom_email" class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                    <span class="text-xs font-bold text-gray-800">Use Custom Email</span>
                </label>
            </div>

            @if($use_custom_email)
                {{-- SMTP OUTGOING SETTINGS --}}
                <div class="bg-gray-50/70 border border-gray-200 rounded-xl p-4 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200 pb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-sky-600"></i> Outgoing Mail Server (SMTP)
                        </h4>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Presets:</span>
                                <button type="button" wire:click="applyPreset('hostinger')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition shadow-xs">
                                    🟣 Hostinger
                                </button>
                                <button type="button" wire:click="applyPreset('gmail')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition shadow-xs">
                                    🔴 Gmail
                                </button>
                                <button type="button" wire:click="applyPreset('office365')" class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition shadow-xs">
                                    🔵 Microsoft 365
                                </button>
                            </div>
                            <button type="button" wire:click="testAndValidateSmtp"
                                    class="px-2.5 py-1 bg-sky-100 hover:bg-sky-200 text-sky-800 rounded text-xs font-bold transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-bolt"></i> Test SMTP
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">SMTP Host</label>
                            <input type="text" wire:model="smtp_host" placeholder="smtp.gmail.com or mail.domain.com"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Port</label>
                            <input type="number" wire:model="smtp_port" placeholder="587"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Encryption</label>
                            <select wire:model="smtp_encryption" class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                                <option value="tls">TLS (Recommended)</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">SMTP Username</label>
                            <input type="text" wire:model="smtp_username" placeholder="user@domain.com"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">SMTP Password</label>
                            <input type="password" wire:model="smtp_password" placeholder="••••••••"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">From Email Address</label>
                            <input type="email" wire:model="smtp_from_address" placeholder="your.name@domain.com"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Sender Name</label>
                            <input type="text" wire:model="smtp_from_name" placeholder="Your Display Name"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>

                {{-- IMAP INCOMING SETTINGS --}}
                <div class="bg-gray-50/70 border border-gray-200 rounded-xl p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-inbox text-indigo-600"></i> Incoming Mail Server (IMAP / Webmail)
                        </h4>
                        <button type="button" wire:click="testAndValidateImap"
                                class="px-3 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded text-xs font-bold transition inline-flex items-center gap-1">
                            <i class="fa-solid fa-bolt"></i> Test & Validate IMAP
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">IMAP Host</label>
                            <input type="text" wire:model="imap_host" placeholder="imap.gmail.com or mail.domain.com"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Port</label>
                            <input type="number" wire:model="imap_port" placeholder="993"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">Encryption</label>
                            <select wire:model="imap_encryption" class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                                <option value="ssl">SSL (Port 993)</option>
                                <option value="tls">TLS</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">IMAP Username</label>
                            <input type="text" wire:model="imap_username" placeholder="user@domain.com"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">IMAP Password</label>
                            <input type="password" wire:model="imap_password" placeholder="••••••••"
                                   class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
            @else
                <div class="p-6 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-2">
                    <i class="fa-solid fa-envelope-circle-check text-slate-400 text-3xl"></i>
                    <h4 class="text-xs font-bold text-gray-800">Using Company Default Mail Server</h4>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Your account currently uses the company's shared mail server. Check "Use Custom Email" above if you wish to configure your personal SMTP/IMAP credentials.
                    </p>
                </div>
            @endif

            {{-- Save Button for Email Tab --}}
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[11px] text-gray-400">Remember to save before switching tabs.</span>
                <button type="button" wire:click="saveEmail"
                        class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Email Settings</span>
                </button>
            </div>
        </div>
    @endif

    {{-- TAB CONTENT: SECURITY & PASSKEYS --}}
    @if($activeTab === 'security')
        <div class="space-y-6">
            {{-- Passkeys Management Card --}}
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white p-5 rounded-xl shadow-xs space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center text-lg font-bold shadow">
                            <i class="fa-solid fa-fingerprint"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">WebAuthn Passkeys & Biometric Quick Login</h3>
                            <p class="text-xs text-slate-300">Log in instantly using Face ID, Touch ID, Windows Hello, or USB security keys.</p>
                        </div>
                    </div>

                    <button type="button" id="btn-register-passkey" onclick="registerUserPasskey()"
                            class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold rounded-lg text-xs shadow transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-key"></i>
                        <span>Register This Device / Add Passkey</span>
                    </button>
                </div>

                <div id="passkey-reg-alert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                {{-- Registered Devices Table --}}
                <div class="bg-white/10 rounded-lg p-3 backdrop-blur-xs">
                    <div class="text-xs font-bold text-slate-200 mb-2 flex items-center justify-between">
                        <span>Your Registered Authenticators</span>
                        <span class="text-[11px] text-slate-400">{{ count($passkeys) }} device(s) registered</span>
                    </div>

                    @if(count($passkeys) > 0)
                        <div class="divide-y divide-white/10 text-xs">
                            @foreach($passkeys as $pk)
                                <div class="py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-shield-halved text-amber-400 text-sm"></i>
                                        <div>
                                            <div class="font-bold text-white">Passkey #{{ substr($pk->id, 0, 12) }}...</div>
                                            <div class="text-[10px] text-slate-400">Added: {{ $pk->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="deletePasskey('{{ $pk->id }}')"
                                            class="px-2.5 py-1 bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white rounded text-[11px] font-semibold transition">
                                        Revoke
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-4 text-center text-slate-400 text-xs italic">
                            No passkeys registered yet. Click "Register This Device / Add Passkey" above to set up quick biometric sign-in!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>
