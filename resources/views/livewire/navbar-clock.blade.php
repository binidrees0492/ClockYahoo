<div class="relative flex items-center"
     x-data="{
        timeStr: '{{ $initialTimeStr }}',
        activeTz: '{{ $activeTz }}',
        tzAbbr: '{{ $tzAbbr }}',
        updateClock() {
            try {
                const now = new Date();
                const timeFormatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.activeTz,
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                let tz = this.tzAbbr;
                try {
                    const parts = new Intl.DateTimeFormat('en-US', { timeZone: this.activeTz, timeZoneName: 'short' }).formatToParts(now);
                    const tzPart = parts.find(p => p.type === 'timeZoneName');
                    if (tzPart && tzPart.value) {
                        tz = tzPart.value;
                    }
                } catch(err) {}
                this.timeStr = timeFormatter.format(now) + ' (' + tz + ')';
            } catch(e) {
                const now = new Date();
                const m = String(now.getMinutes()).padStart(2, '0');
                const h = now.getHours();
                const ampm = h >= 12 ? 'PM' : 'AM';
                const h12 = h % 12 || 12;
                this.timeStr = `${h12}:${m} ${ampm} (${this.tzAbbr})`;
            }
        }
     }"
     x-init="updateClock(); setInterval(() => updateClock(), 1000)"
     @timezone-changed.window="activeTz = $event.detail.timezone; updateClock()"
     @open-timezone-modal.window="$wire.openModal()">

    {{-- RIGHT-MOST NAVBAR TIME CLOCK (CLICKABLE TO OPEN MODAL) --}}
    <button wire:click="openModal" type="button"
            class="flex items-center px-2.5 py-1 rounded-xl border {{ $isTemp ? 'border-amber-400 bg-amber-950/80 text-amber-200' : 'border-white/30 bg-white/10 hover:bg-white/20 text-white' }} shadow-xs backdrop-blur-xs transition cursor-pointer select-none group"
            title="Analog Clock (White) - Click to adjust timezone ({{ $activeTz }}{{ $isTemp ? ' - Temporary Session' : ' - Permanent' }})">

        {{-- Only show H:MM AM/PM (EST) --}}
        <span class="font-bold text-xs sm:text-sm tracking-tight font-mono text-white whitespace-nowrap" x-text="timeStr">{{ $initialTimeStr }}</span>
    </button>

    {{-- TIME CLOCK & TIMEZONE MODAL --}}
    @if($showModal)
        @teleport('body')
        <div class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>

            <div class="min-h-screen px-4 text-center flex items-center justify-center p-0">
                <div class="inline-block bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-8 max-w-xl w-full border border-gray-200 dark:border-gray-700 max-h-[580px] flex flex-col">

                    {{-- Modal Header --}}
                    <div class="p-4 bg-gradient-to-r from-blue-900 to-[#005a9c] text-white flex items-center justify-between shrink-0 shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white">
                                <i class="fa-solid fa-clock text-base"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold leading-tight">Time Clock & Timezone Selector</h3>
                                <p class="text-[11px] text-sky-200">Switch session display timezone or update personal profile.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModal" class="p-1 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    {{-- Modal Body (Scrollable, strictly under 580px height) --}}
                    <div class="p-4 overflow-y-auto space-y-4 flex-1 text-xs">

                        {{-- Current Active Status & Preview Banner --}}
                        <div class="p-3 rounded-xl border {{ $isTemp ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-blue-50/80 border-blue-200 text-blue-900' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                            <div>
                                <div class="flex items-center gap-1.5 font-bold text-xs">
                                    @if($isTemp)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] bg-amber-200 text-amber-900 font-extrabold">⚡ Temporary Session Timezone</span>
                                    @else
                                        <span class="px-1.5 py-0.2 rounded text-[10px] bg-emerald-100 text-emerald-800 font-extrabold">✓ Permanent Profile Timezone</span>
                                    @endif
                                    <span class="text-gray-500 font-normal">({{ $activeTz }})</span>
                                </div>
                                <div class="text-[11px] text-gray-600 mt-1">
                                    Permanent config: <strong>{{ $profileTz }}</strong>
                                </div>
                            </div>

                            {{-- Live Clock in Target Timezone --}}
                            <div class="bg-white dark:bg-gray-900 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-right shrink-0">
                                <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Preview Time</div>
                                <div class="text-sm font-black font-mono text-[#005a9c] dark:text-sky-400">{{ $previewTime }} {{ $previewAbbr }}</div>
                                <div class="text-[10px] text-gray-500 font-medium">{{ $previewDate }}</div>
                            </div>
                        </div>

                        {{-- SELECTION MODE: TEMPORARY VS PERMANENT (Core user requirement) --}}
                        <div>
                            <label class="block font-bold text-gray-800 dark:text-gray-200 mb-1.5">
                                Select Change Type:
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                {{-- Option 1: Temporary --}}
                                <label wire:click="$set('changeType', 'temp')"
                                       class="relative flex flex-col p-3 rounded-xl border-2 cursor-pointer transition select-none {{ $changeType === 'temp' ? 'border-amber-500 bg-amber-50/60 shadow-xs' : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700' }}">
                                    <input type="radio" wire:model.live="changeType" value="temp" class="sr-only">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-amber-900 flex items-center gap-1.5">
                                            <span>⚡</span> Temporary Change
                                        </span>
                                        @if($changeType === 'temp')
                                            <span class="w-4 h-4 rounded-full bg-amber-500 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-600 dark:text-gray-400 mt-1 leading-snug">
                                        Applies immediately to timesheets, schedules & clocks for <strong>this session only</strong>. Leaves personal profile untouched.
                                    </p>
                                </label>

                                {{-- Option 2: Permanent --}}
                                <label wire:click="$set('changeType', 'permanent')"
                                       class="relative flex flex-col p-3 rounded-xl border-2 cursor-pointer transition select-none {{ $changeType === 'permanent' ? 'border-[#005a9c] bg-blue-50/60 shadow-xs' : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700' }}">
                                    <input type="radio" wire:model.live="changeType" value="permanent" class="sr-only">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-[#005a9c] flex items-center gap-1.5">
                                            <span>💾</span> Permanent Change
                                        </span>
                                        @if($changeType === 'permanent')
                                            <span class="w-4 h-4 rounded-full bg-[#005a9c] text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-600 dark:text-gray-400 mt-1 leading-snug">
                                        Updates your user profile in <strong>Personal Configs</strong>. Persists permanently across all devices and future logins.
                                    </p>
                                </label>
                            </div>
                        </div>

                        {{-- TIMEZONE SEARCH & SELECTOR --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-bold text-gray-800 dark:text-gray-200">
                                    Choose Target Timezone:
                                </label>
                                <span class="text-[11px] text-gray-500 font-mono">Selected: <strong>{{ $selectedTimezone }}</strong></span>
                            </div>

                            {{-- Search Filter --}}
                            <div class="relative mb-2">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                                <input type="text" wire:model.live.debounce.200ms="searchTimezone"
                                       placeholder="Filter by city, region or abbreviation (e.g. New York, London, Tokyo, PKT, EST)..."
                                       class="w-full pl-8 pr-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs bg-white dark:bg-gray-900 focus:ring-1 focus:ring-sky-500 focus:border-sky-500">
                            </div>

                            {{-- Categorized Timezones List --}}
                            <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                @forelse($timezones as $region => $regionTzs)
                                    @if(count($regionTzs) > 0)
                                        <div class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800/60 text-[10px] font-bold text-gray-500 uppercase tracking-wider sticky top-0">
                                            {{ $region }}
                                        </div>
                                        @foreach($regionTzs as $tzKey => $tzLabel)
                                            <button type="button" wire:click="selectTimezone('{{ $tzKey }}')"
                                                    class="w-full px-3 py-2 text-left flex items-center justify-between text-xs transition {{ $selectedTimezone === $tzKey ? 'bg-sky-50 dark:bg-sky-950/40 text-[#005a9c] dark:text-sky-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-3.5 text-center text-xs">
                                                        {{ $selectedTimezone === $tzKey ? '✓' : '•' }}
                                                    </span>
                                                    <span>{{ $tzLabel }}</span>
                                                </div>
                                                <span class="text-[10px] text-gray-400 font-mono font-normal">
                                                    {{ $tzKey }}
                                                </span>
                                            </button>
                                        @endforeach
                                    @endif
                                @empty
                                    <div class="p-4 text-center text-gray-400 italic text-xs">
                                        No matching timezones found for "{{ $searchTimezone }}".
                                    </div>
                                @endforelse
                            </div>
                        </div>

                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2 shrink-0">
                        <div>
                            @if($isTemp)
                                <button type="button" wire:click="revertToProfile"
                                        class="px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Revert to Profile Default</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="closeModal"
                                    class="px-3.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-xs font-semibold transition">
                                Cancel
                            </button>
                            <button type="button" wire:click="applyTimezone"
                                    class="px-4 py-1.5 rounded-lg text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 {{ $changeType === 'temp' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-[#005a9c] hover:bg-blue-800' }}">
                                <i class="fa-solid {{ $changeType === 'temp' ? 'fa-bolt' : 'fa-floppy-disk' }}"></i>
                                <span>{{ $changeType === 'temp' ? 'Apply Temporary Change' : 'Save Permanent Change' }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
