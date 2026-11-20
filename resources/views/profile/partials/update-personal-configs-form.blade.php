<section>
    <header>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Personal Configurations & Regional Settings') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __("Configure your local timezone and granular notification preferences. All clocks and shifts will be displayed in your selected timezone.") }}
                </p>
            </div>
            <div class="hidden sm:block text-right bg-blue-50 border border-blue-200 rounded-lg p-3">
                <span class="text-[10px] uppercase font-bold text-blue-800 tracking-wider block">Company Server Timezone</span>
                <span class="text-xs font-semibold text-gray-900">{{ \App\Models\CompanySetting::getServerTimezone() }}</span>
                <span class="text-[10px] text-gray-500 block mt-0.5">Defined in Company Settings</span>
            </div>
        </div>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
        <input type="hidden" name="email" value="{{ old('email', $user->email) }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Timezone --}}
            <div>
                <x-input-label for="timezone" :value="__('My Timezone')" />
                <select id="timezone" name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @php
                        $userTz = old('timezone', $user->timezone ?: 'Asia/Karachi');
                        $timezones = [
                            'Asia/Karachi' => 'PKT - Pakistan Time (UTC+05:00)',
                            'America/New_York' => 'EST/EDT - US Eastern Time',
                            'America/Chicago' => 'CST/CDT - US Central Time',
                            'America/Denver' => 'MST/MDT - US Mountain Time',
                            'America/Los_Angeles' => 'PST/PDT - US Pacific Time',
                            'UTC' => 'UTC - Universal Coordinated Time',
                            'Europe/London' => 'GMT/BST - London, UK',
                            'Europe/Berlin' => 'CET/CEST - Berlin / Paris / Amsterdam',
                            'Asia/Dubai' => 'GST - Dubai, UAE (UTC+04:00)',
                            'Asia/Kolkata' => 'IST - India Standard Time (UTC+05:30)',
                            'Asia/Singapore' => 'SGT - Singapore / Hong Kong (UTC+08:00)',
                            'Asia/Tokyo' => 'JST - Tokyo, Japan (UTC+09:00)',
                            'Australia/Sydney' => 'AEST/AEDT - Sydney, Australia',
                        ];
                    @endphp
                    @foreach($timezones as $tzValue => $tzLabel)
                        <option value="{{ $tzValue }}" {{ $userTz === $tzValue ? 'selected' : '' }}>
                            {{ $tzLabel }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
                <p class="mt-1 text-xs text-gray-500">Currently active: <strong>{{ $user->getTimezone() }} ({{ $user->getTimezoneAbbr() }})</strong></p>
            </div>

            {{-- Master Notification Delivery Preference --}}
            <div>
                <x-input-label for="notification_channel_preference" :value="__('Primary Delivery Channel')" />
                <select id="notification_channel_preference" name="notification_channel_preference" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @php $pref = old('notification_channel_preference', $user->notification_channel_preference ?: 'both'); @endphp
                    <option value="both" {{ $pref === 'both' ? 'selected' : '' }}>In-App Bell & Email (Both)</option>
                    <option value="bell_only" {{ $pref === 'bell_only' ? 'selected' : '' }}>In-App Bell Icon Only</option>
                    <option value="email_only" {{ $pref === 'email_only' ? 'selected' : '' }}>Email Notifications Only</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('notification_channel_preference')" />
                <p class="mt-1 text-xs text-gray-500">Master default channel for notifications.</p>
            </div>
        </div>

        {{-- Personal Theme Selection --}}
        @php
            $currentTheme = old('theme_preference', $user->theme_preference ?: 'light');
        @endphp
        <div class="border-t border-gray-100 pt-6 space-y-3" x-data="{ activeTheme: '{{ $currentTheme }}' }">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">My Theme Preference</h3>
                <p class="text-xs text-gray-500 mt-0.5">Select your personal display theme for ClockVerse. This overrides the company default for your user account.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Light --}}
                <label @click="activeTheme = 'light'"
                       class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none"
                       :class="activeTheme === 'light' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'">
                    <input type="radio" name="theme_preference" value="light" x-model="activeTheme" class="sr-only">
                    <span x-show="activeTheme === 'light'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                    <div class="h-16 w-full rounded-md border border-gray-200 bg-[#f9fafb] p-2 flex flex-col justify-between mb-3 shadow-xs">
                        <div class="h-3 w-16 bg-[#005a9c] rounded-xs"></div>
                        <div class="flex gap-1.5">
                            <div class="h-4 flex-1 bg-white border border-gray-200 rounded-xs"></div>
                            <div class="h-4 w-6 bg-blue-100 rounded-xs"></div>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-900">☀️ Light (Default)</span>
                    <span class="text-[11px] text-gray-500 mt-0.5">Classic clean interface with light background.</span>
                </label>

                {{-- Dark --}}
                <label @click="activeTheme = 'dark'"
                       class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none"
                       :class="activeTheme === 'dark' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'">
                    <input type="radio" name="theme_preference" value="dark" x-model="activeTheme" class="sr-only">
                    <span x-show="activeTheme === 'dark'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                    <div class="h-16 w-full rounded-md border border-gray-700 bg-[#111827] p-2 flex flex-col justify-between mb-3 shadow-xs">
                        <div class="h-3 w-16 bg-[#005a9c] rounded-xs"></div>
                        <div class="flex gap-1.5">
                            <div class="h-4 flex-1 bg-[#1f2937] border border-gray-700 rounded-xs"></div>
                            <div class="h-4 w-6 bg-[#374151] rounded-xs"></div>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-900">🌙 Dark Mode</span>
                    <span class="text-[11px] text-gray-500 mt-0.5">Sleek midnight dark background easy on eyes.</span>
                </label>

                {{-- Gradient --}}
                <label @click="activeTheme = 'gradient'"
                       class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none"
                       :class="activeTheme === 'gradient' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'">
                    <input type="radio" name="theme_preference" value="gradient" x-model="activeTheme" class="sr-only">
                    <span x-show="activeTheme === 'gradient'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                    <div class="h-16 w-full rounded-md border border-indigo-200 bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-50 p-2 flex flex-col justify-between mb-3 shadow-xs">
                        <div class="h-3 w-16 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xs"></div>
                        <div class="flex gap-1.5">
                            <div class="h-4 flex-1 bg-white/80 border border-indigo-100 rounded-xs"></div>
                            <div class="h-4 w-6 bg-indigo-200 rounded-xs"></div>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-900">✨ Gradient Modern</span>
                    <span class="text-[11px] text-gray-500 mt-0.5">Vibrant indigo and violet aesthetics.</span>
                </label>

                {{-- Sage Dark Green --}}
                <label @click="activeTheme = 'sage dark green'"
                       class="relative flex flex-col p-4 rounded-xl cursor-pointer transition select-none"
                       :class="activeTheme === 'sage dark green' ? 'border-2 border-[#005a9c] ring-2 ring-offset-1 ring-[#005a9c] bg-blue-50/80 shadow-xs' : 'border border-gray-200 hover:bg-gray-50'">
                    <input type="radio" name="theme_preference" value="sage dark green" x-model="activeTheme" class="sr-only">
                    <span x-show="activeTheme === 'sage dark green'" class="absolute top-2.5 right-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#005a9c] text-white text-[11px] font-bold shadow-xs">✓</span>
                    <div class="h-16 w-full rounded-md border border-[#2b3e34] bg-[#141e1b] p-2 flex flex-col justify-between mb-3 shadow-xs">
                        <div class="h-3 w-16 bg-[#2d5a49] rounded-xs"></div>
                        <div class="flex gap-1.5">
                            <div class="h-4 flex-1 bg-[#1f2f2a] border border-[#2b3e34] rounded-xs"></div>
                            <div class="h-4 w-6 bg-[#3b544b] rounded-xs"></div>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-900">🌿 Sage Dark Green</span>
                    <span class="text-[11px] text-gray-500 mt-0.5">Earthy dark tones with calming emerald accents.</span>
                </label>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('theme_preference')" />
        </div>

        {{-- Detailed Notification Settings (Requirement) --}}
        @php
            $detailed = $user->detailed_notification_settings ?: [
                'shift_assigned'   => ['bell' => 1, 'email' => 1],
                'time_approval'    => ['bell' => 1, 'email' => 1],
                'unusual_activity' => ['bell' => 1, 'email' => 1],
                'time_submitted'   => ['bell' => 1, 'email' => 0],
            ];
        @endphp
        <div class="border-t border-gray-100 pt-6">
            <h3 class="text-sm font-semibold text-gray-800 mb-1">Detailed Notification Preferences</h3>
            <p class="text-xs text-gray-500 mb-4">Choose exactly which events trigger in-app bell updates and email notifications.</p>

            <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-gray-600">
                            <th class="p-3 font-semibold">Event Type</th>
                            <th class="p-3 font-semibold text-center w-28">In-App Bell</th>
                            <th class="p-3 font-semibold text-center w-28">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-gray-800">Shift & Schedule Assigned</div>
                                <div class="text-gray-500 text-[11px]">When a shift or task schedule is assigned or updated for you or your team.</div>
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[shift_assigned][bell]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[shift_assigned][bell]" value="1"
                                       {{ !empty($detailed['shift_assigned']['bell']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[shift_assigned][email]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[shift_assigned][email]" value="1"
                                       {{ !empty($detailed['shift_assigned']['email']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                        </tr>
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-gray-800">Timesheet Approval / Disapproval Decision</div>
                                <div class="text-gray-500 text-[11px]">When your logged hours are approved or rejected by a supervisor or HR.</div>
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[time_approval][bell]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[time_approval][bell]" value="1"
                                       {{ !empty($detailed['time_approval']['bell']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[time_approval][email]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[time_approval][email]" value="1"
                                       {{ !empty($detailed['time_approval']['email']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                        </tr>
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-gray-800">Unusual Shift Activity & Location Alerts</div>
                                <div class="text-gray-500 text-[11px]">When time is logged beyond allocated shift hours or outside authorized GPS radius.</div>
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[unusual_activity][bell]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[unusual_activity][bell]" value="1"
                                       {{ !empty($detailed['unusual_activity']['bell']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[unusual_activity][email]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[unusual_activity][email]" value="1"
                                       {{ !empty($detailed['unusual_activity']['email']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                        </tr>
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-gray-800">Time Log Submissions (Supervisors / HR)</div>
                                <div class="text-gray-500 text-[11px]">When an employee clocks out or submits a manual time entry awaiting review.</div>
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[time_submitted][bell]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[time_submitted][bell]" value="1"
                                       {{ !empty($detailed['time_submitted']['bell']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                            <td class="p-3 text-center">
                                <input type="hidden" name="detailed_notification_settings[time_submitted][email]" value="0">
                                <input type="checkbox" name="detailed_notification_settings[time_submitted][email]" value="1"
                                       {{ !empty($detailed['time_submitted']['email']) ? 'checked' : '' }}
                                       class="rounded text-[#005a9c] focus:ring-[#005a9c]">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Shift Hours Policy Note (Requirement 1) --}}
        <div class="border-t border-gray-100 pt-6">
            <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-lg flex items-start gap-3">
                <span class="text-blue-600 text-lg leading-none mt-0.5">ℹ️</span>
                <div class="text-xs text-blue-900 leading-relaxed">
                    <strong class="font-semibold">Shift Hours Policy:</strong>
                    Shift hours and allocated times are defined exclusively by Administrators while assigning tasks and jobs to employees on the
                    <a href="{{ route('schedules') }}" class="underline font-semibold hover:text-blue-700">Schedules Board</a>.
                    The compliance system automatically monitors logging and alerts supervisors if hours exceed the allocated schedule limit for each task and job.
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>{{ __('Save Personal Configurations') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm text-green-600 font-semibold"
                >{{ __('Saved successfully.') }}</p>
            @endif
        </div>
    </form>
</section>
