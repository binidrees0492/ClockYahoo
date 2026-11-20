<div wire:poll.15s="refreshState" class="w-full flex flex-col items-center"
     x-data="{
         isClocking: false,
         isActive: @js($isActive),
         isOnBreak: @js($isOnBreak),
         hours: @js((string)$hours),
         minutes: @js((string)$minutes),
         seconds: @js((string)$seconds),
         hoursPercent: @js($hoursPercent),
         minutesPercent: @js($minutesPercent),
         secondsPercent: @js($secondsPercent),
         breakHours: @js((string)$breakHours),
         breakMinutes: @js((string)$breakMinutes),
         breakSeconds: @js((string)$breakSeconds),
         clockInEpoch: @js($clockInEpoch),
         shiftClockInEpoch: @js($shiftClockInEpoch),
         shiftPriorNetSeconds: @js((int)$shiftPriorNetSeconds),
         completedBreakSeconds: @js((int)$completedBreakSeconds),
         breakStartEpoch: @js($breakStartEpoch),
         serverEpoch: @js($serverEpoch),
         serverOffset: 0,
         circ: 238.76,

         pad(v) { return String(v).padStart(2, '0'); },

         init() {
             if (this.serverEpoch) {
                 this.serverOffset = Date.now() - this.serverEpoch;
             }

             if (this.$wire && typeof this.$watch === 'function') {
                 this.$watch('$wire.isActive', v => { this.isActive = v; this.updateClock(); });
                 this.$watch('$wire.isOnBreak', v => { this.isOnBreak = v; this.updateClock(); });
                 this.$watch('$wire.clockInEpoch', v => { this.clockInEpoch = v; this.updateClock(); });
                 this.$watch('$wire.shiftClockInEpoch', v => { this.shiftClockInEpoch = v; this.updateClock(); });
                 this.$watch('$wire.shiftPriorNetSeconds', v => { this.shiftPriorNetSeconds = parseInt(v) || 0; this.updateClock(); });
                 this.$watch('$wire.completedBreakSeconds', v => { this.completedBreakSeconds = parseInt(v) || 0; this.updateClock(); });
                 this.$watch('$wire.breakStartEpoch', v => { this.breakStartEpoch = v; this.updateClock(); });
                 this.$watch('$wire.serverEpoch', v => {
                     if (v) {
                         this.serverEpoch = v;
                         this.serverOffset = Date.now() - v;
                     }
                     this.updateClock();
                 });
             }

             this.updateClock();

             setInterval(() => {
                 this.updateClock();
             }, 1000);
         },

         updateClock() {
             if (!this.isActive || !this.clockInEpoch) {
                 if (!this.isActive) {
                     this.hours = '00';
                     this.minutes = '00';
                     this.seconds = '00';
                     this.hoursPercent = 0;
                     this.minutesPercent = 0;
                     this.secondsPercent = 0;
                     this.breakHours = '00';
                     this.breakMinutes = '00';
                     this.breakSeconds = '00';
                 }
                 return;
             }

             const now = Date.now() - this.serverOffset;

             // Live current break seconds if actively on break
             let currentBreakSec = 0;
             if (this.isOnBreak && this.breakStartEpoch) {
                 currentBreakSec = Math.max(0, Math.floor((now - this.breakStartEpoch) / 1000));
                 const bH = Math.floor(currentBreakSec / 3600);
                 const bM = Math.floor((currentBreakSec % 3600) / 60);
                 const bS = currentBreakSec % 60;
                 this.breakHours = this.pad(bH);
                 this.breakMinutes = this.pad(bM);
                 this.breakSeconds = this.pad(bS);
             }

             // Gross elapsed seconds since current segment's clock in
             const currentGrossSec = Math.max(0, Math.floor((now - this.clockInEpoch) / 1000));
             // Net work seconds for current segment strictly excludes current segment's completed & live breaks
             const currentNetSec = Math.max(0, currentGrossSec - (this.completedBreakSeconds + currentBreakSec));
             // Total shift net seconds = prior switched segments + current segment net seconds (timer does NOT reset!)
             const totalShiftNetSec = this.shiftPriorNetSeconds + currentNetSec;

             const wH = Math.floor(totalShiftNetSec / 3600);
             const wM = Math.floor((totalShiftNetSec % 3600) / 60);
             const wS = totalShiftNetSec % 60;

             if (this.isOnBreak) {
                 // When on break, the dials show the live ticking break time
                 const bTotal = currentBreakSec;
                 const bHVal = Math.floor(bTotal / 3600);
                 const bMVal = Math.floor((bTotal % 3600) / 60);
                 const bSVal = bTotal % 60;
                 this.hours = this.pad(bHVal);
                 this.minutes = this.pad(bMVal);
                 this.seconds = this.pad(bSVal);
                 this.secondsPercent = Math.round((bSVal / 60) * 100);
                 this.minutesPercent = Math.round((bMVal / 60) * 100);
                 this.hoursPercent = Math.min(100, Math.round(((bHVal % 12) / 12) * 100));
             } else {
                 // Regular active shift work time (continuous across job/task switches)
                 this.hours = this.pad(wH);
                 this.minutes = this.pad(wM);
                 this.seconds = this.pad(wS);
                 this.secondsPercent = Math.round((wS / 60) * 100);
                 this.minutesPercent = Math.round((wM / 60) * 100);
                 this.hoursPercent = Math.min(100, Math.round(((wH % 12) / 12) * 100));
             }
         },

         clockInWithGPS() {
             if (this.isClocking) return;
             this.isClocking = true;
             let $c = this.$wire || (window.Livewire && this.$el ? window.Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id')) : null);
             if (!$c) {
                 this.isClocking = false;
                 console.warn('Livewire component context not ready yet');
                 return;
             }
             if ('geolocation' in navigator) {
                 if (window.showToast) {
                     window.showToast('Requesting GPS location permissions...', 'info');
                 }
                 navigator.geolocation.getCurrentPosition(
                     (pos) => {
                         $c.clockIn(pos.coords.latitude, pos.coords.longitude).finally(() => { this.isClocking = false; });
                     },
                     (err) => {
                         console.warn('Geolocation unavailable/denied:', err);
                         if (window.showToast) {
                             window.showToast('GPS unavailable or permission denied (' + err.message + '). Logging time as GPS N/A.', 'error');
                         }
                         $c.clockIn(null, null).finally(() => { this.isClocking = false; });
                     },
                     { timeout: 10000, enableHighAccuracy: true, maximumAge: 30000 }
                 );
             } else {
                 if (window.showToast) {
                     window.showToast('GPS is not supported by your browser. Logging time as GPS N/A.', 'error');
                 }
                 $c.clockIn(null, null).finally(() => { this.isClocking = false; });
             }
         },
         clockOutWithGPS() {
             if (this.isClocking) return;
             const now = Date.now() - this.serverOffset;
             const elapsedSec = this.clockInEpoch ? Math.max(0, Math.floor((now - this.clockInEpoch) / 1000)) : 999;
             if (elapsedSec < 60) {
                 const rem = 60 - elapsedSec;
                 if (window.showToast) {
                     window.showToast('Please wait at least 1 minute after clocking in before clocking out to avoid accidental double-clicks (' + rem + 's remaining).', 'warning');
                 }
                 return;
             }
             this.isClocking = true;
             let $c = this.$wire || (window.Livewire && this.$el ? window.Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id')) : null);
             if (!$c) {
                 this.isClocking = false;
                 console.warn('Livewire component context not ready yet');
                 return;
             }
             if ('geolocation' in navigator) {
                 if (window.showToast) {
                     window.showToast('Requesting GPS location permissions...', 'info');
                 }
                 navigator.geolocation.getCurrentPosition(
                     (pos) => {
                         $c.clockOut(pos.coords.latitude, pos.coords.longitude).finally(() => { this.isClocking = false; });
                     },
                     (err) => {
                         console.warn('Geolocation unavailable/denied:', err);
                         if (window.showToast) {
                             window.showToast('GPS unavailable or permission denied (' + err.message + '). Logging time as GPS N/A.', 'error');
                         }
                         $c.clockOut(null, null).finally(() => { this.isClocking = false; });
                     },
                     { timeout: 10000, enableHighAccuracy: true, maximumAge: 30000 }
                 );
             } else {
                 if (window.showToast) {
                     window.showToast('GPS is not supported by your browser. Logging time as GPS N/A.', 'error');
                 }
                 $c.clockOut(null, null).finally(() => { this.isClocking = false; });
             }
         }
     }">

    {{-- CIRCULAR GAUGES (HOURS, MINUTES, SECONDS) --}}
    <div class="flex justify-center items-center gap-3 sm:gap-4 mb-2 text-center w-full">
        {{-- HOURS GAUGE --}}
        <div class="flex flex-col items-center">
            <span class="text-[10px] font-semibold uppercase tracking-wider mb-1.5"
                  :class="isOnBreak ? 'text-amber-600 font-bold' : 'text-gray-500'"
                  x-text="isOnBreak ? 'BRK HRS' : 'HOURS'">HOURS</span>
            <div class="relative w-20 h-20 flex items-center justify-center">
                <svg viewBox="0 0 100 100" class="w-20 h-20 -rotate-90">
                    <circle cx="50" cy="50" r="38" stroke="#e5e7eb" stroke-width="3" fill="none" />
                    <circle cx="50" cy="50" r="38"
                            :stroke="isOnBreak ? '#f59e0b' : '#0077c8'"
                            stroke-width="3.5" stroke-linecap="round" fill="none"
                            stroke-dasharray="238.76"
                            :stroke-dashoffset="circ - (circ * hoursPercent / 100)"
                            :style="{ transition: hoursPercent === 0 ? 'none' : 'stroke-dashoffset 0.25s linear' }" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-light"
                          :class="isOnBreak ? 'text-amber-700 font-normal' : 'text-gray-800'"
                          x-text="hours">{{ $hours }}</span>
                </div>
            </div>
        </div>

        {{-- MINUTES GAUGE --}}
        <div class="flex flex-col items-center">
            <span class="text-[10px] font-semibold uppercase tracking-wider mb-1.5"
                  :class="isOnBreak ? 'text-amber-600 font-bold' : 'text-gray-500'"
                  x-text="isOnBreak ? 'BRK MIN' : 'MINUTES'">MINUTES</span>
            <div class="relative w-20 h-20 flex items-center justify-center">
                <svg viewBox="0 0 100 100" class="w-20 h-20 -rotate-90">
                    <circle cx="50" cy="50" r="38" stroke="#e5e7eb" stroke-width="3" fill="none" />
                    <circle cx="50" cy="50" r="38"
                            :stroke="isOnBreak ? '#f59e0b' : '#0077c8'"
                            stroke-width="3.5" stroke-linecap="round" fill="none"
                            stroke-dasharray="238.76"
                            :stroke-dashoffset="circ - (circ * minutesPercent / 100)"
                            :style="{ transition: minutes === '00' ? 'none' : 'stroke-dashoffset 0.25s linear' }" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-light"
                          :class="isOnBreak ? 'text-amber-700 font-normal' : 'text-gray-800'"
                          x-text="minutes">{{ $minutes }}</span>
                </div>
            </div>
        </div>

        {{-- SECONDS GAUGE --}}
        <div class="flex flex-col items-center">
            <span class="text-[10px] font-semibold uppercase tracking-wider mb-1.5"
                  :class="isOnBreak ? 'text-amber-600 font-bold' : 'text-gray-500'"
                  x-text="isOnBreak ? 'BRK SEC' : 'SECONDS'">SECONDS</span>
            <div class="relative w-20 h-20 flex items-center justify-center">
                <svg viewBox="0 0 100 100" class="w-20 h-20 -rotate-90">
                    <circle cx="50" cy="50" r="38" stroke="#e5e7eb" stroke-width="3" fill="none" />
                    <circle cx="50" cy="50" r="38"
                            :stroke="isOnBreak ? '#f59e0b' : '#0077c8'"
                            stroke-width="3.5" stroke-linecap="round" fill="none"
                            stroke-dasharray="238.76"
                            :stroke-dashoffset="circ - (circ * secondsPercent / 100)"
                            :style="{ transition: seconds === '00' ? 'none' : 'stroke-dashoffset 0.25s linear' }" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-light"
                          :class="isOnBreak ? 'text-amber-700 font-normal' : 'text-gray-800'"
                          x-text="seconds">{{ $seconds }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- STARTED TIMESTAMP --}}
    <div class="text-xs text-gray-500 font-medium text-center mt-1">
        {{ $startedAtText }}
        @if($shiftStartedAtText)
            <div class="text-[11px] text-gray-400 mt-0.5">
                {{ $shiftStartedAtText }}
            </div>
        @endif
    </div>

    {{-- JOB & TASK LABELS (WHEN CLOCKED IN) --}}
    @if($isActive)
        <div class="w-full text-center mt-2.5 space-y-0.5">
            <div class="text-xs text-gray-700 font-medium truncate" title="{{ $activeJobName }}">
                <strong class="text-gray-500 font-bold uppercase text-[11px]">JOB:</strong>
                <span class="text-gray-700 ml-1">{{ $activeJobName }}</span>
            </div>
            <div class="text-xs text-gray-700 font-medium truncate" title="{{ $activeTaskName }}">
                <strong class="text-gray-500 font-bold uppercase text-[11px]">TASK:</strong>
                <span class="text-gray-700 ml-1">{{ $activeTaskName }}</span>
            </div>
        </div>

        @if($isOnBreak)
            <div class="mt-2.5 px-3 py-2 bg-amber-50 border border-amber-300 rounded-md text-center text-xs text-amber-900 shadow-xs">
                <div class="flex items-center justify-center gap-1.5 font-bold text-amber-800">
                    <span class="inline-block animate-pulse text-amber-600">☕</span>
                    <span>ON BREAK</span>
                    <span class="text-[11px] font-semibold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">Ticking Above</span>
                </div>
                <div class="text-[11px] text-amber-700 mt-1">
                    Work time is paused &middot; Break time is excluded from payable hours
                </div>
            </div>
        @endif
    @endif

    {{-- WEB CLOCK DISABLED BANNER (MATCHING SCREENSHOT) --}}
    @if(!auth()->user()->canWebClock())
        <div class="w-full mt-3 bg-white border-l-4 border-[#0077c8] p-3 shadow-xs text-xs text-gray-600 rounded-r">
            Your employer has disabled the web clock, please use the mobile app.
        </div>
    @endif

    {{-- CLOCK CONTROLS --}}
    @if(auth()->user()->canWebClock())
        @if(!$isActive)
            <div class="w-full space-y-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Job*
                    </label>
                    <select wire:model.live="selectedAssignment"
                            class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0077c8]">
                        <option value="">{{ count($assignments) === 0 ? 'No Assigned Jobs Available' : 'Select Job' }}</option>
                        @foreach($assignments as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedAssignment') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Task*
                    </label>
                    <select wire:model.live="selectedTask"
                            class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0077c8]">
                        <option value="">{{ !$selectedAssignment ? 'Select a Job first' : (count($tasks) === 0 ? 'No tasks available for this job' : 'Select Task') }}</option>
                        @foreach($tasks as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    @if($selectedAssignment && !$selectedTask)
                        <p class="text-[11px] text-amber-700 font-medium mt-1">⚠️ A job cannot be started unless a task is selected too.</p>
                    @endif
                    @error('selectedTask') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                </div>

                @if(count($assignments) === 0 || count($tasks) === 0)
                    <div class="p-2.5 bg-amber-50 border border-amber-200 rounded text-[11px] text-amber-800 flex items-start gap-1.5">
                        <i class="fa-solid fa-circle-info mt-0.5 text-amber-600"></i>
                        <span>You can only clock in to jobs & tasks assigned to you. Contact your supervisor to assign scheduled shifts or work assignments.</span>
                    </div>
                @endif

                <button type="button"
                        @click="clockInWithGPS()"
                        :disabled="isClocking"
                        class="w-full bg-[#0077c8] hover:bg-sky-700 text-white font-semibold py-2.5 rounded shadow-xs transition flex items-center justify-center text-xs disabled:opacity-60">
                    <template x-if="isClocking">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Clocking In...
                        </span>
                    </template>
                    <template x-if="!isClocking">
                        <span class="flex items-center"><span class="mr-1.5 text-[10px]">&#9654;</span> Clock In</span>
                    </template>
                </button>
            </div>
        @else
            {{-- INLINE CONTROL BUTTONS: BREAK, SWITCH, CLOCK OUT --}}
            <div class="flex items-center gap-2 w-full mt-3">
                @if($isOnBreak)
                    <button wire:click="endBreak"
                            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-2 rounded shadow-xs transition flex items-center justify-center text-xs whitespace-nowrap">
                        <span class="mr-1">&#9654;</span> End Break
                    </button>
                @else
                    <button wire:click="startBreak"
                            class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-2 rounded shadow-xs transition flex items-center justify-center text-xs whitespace-nowrap">
                        <span class="mr-1">&#10074;&#10074;</span> Start Break
                    </button>
                @endif

                <button wire:click="openSwitchModal"
                        class="flex-1 bg-[#0077c8] hover:bg-sky-700 text-white font-semibold py-2 px-2 rounded shadow-xs transition flex items-center justify-center text-xs whitespace-nowrap">
                    <span class="mr-1">&#8646;</span> Switch
                </button>

                <button type="button"
                        @click="clockOutWithGPS()"
                        :disabled="isClocking"
                        class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-2 rounded shadow-xs transition flex items-center justify-center text-xs whitespace-nowrap disabled:opacity-60">
                    <template x-if="isClocking">
                        <span class="flex items-center gap-1">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Out...
                        </span>
                    </template>
                    <template x-if="!isClocking">
                        <span class="flex items-center"><span class="mr-1">&#9632;</span> Clock Out</span>
                    </template>
                </button>
            </div>
        @endif
    @endif

    {{-- NOTE & ATTACHMENT SECTION (MATCHING SCREENSHOT) --}}
    <div class="w-full mt-4 space-y-3">
        <div>
            <div class="text-xs text-gray-700 font-medium">Note</div>
            <button type="button" wire:click="toggleAddNote" class="text-xs text-[#0077c8] hover:underline font-medium">
                +Add Note
            </button>
            @if($showAddNote)
                <div class="mt-2 flex gap-1.5">
                    <input type="text" wire:model="noteDraft" wire:keydown.enter="addNote"
                           placeholder="Write a note..."
                           class="flex-1 border border-gray-300 rounded p-1.5 text-xs focus:outline-none focus:border-[#0077c8]">
                    <button wire:click="addNote"
                            class="bg-[#0077c8] hover:bg-sky-700 text-white px-2.5 py-1.5 rounded text-xs">Add</button>
                </div>
            @endif

            @if(count($activeNotes))
                <ul class="space-y-1.5 mt-2">
                    @foreach($activeNotes as $note)
                        <li class="bg-gray-50 border border-gray-200 rounded p-1.5 text-[11px]">
                            <div class="flex justify-between text-gray-400">
                                <span>{{ $note['user'] }} &middot; {{ $note['created_at'] }}</span>
                                <button wire:click="deleteNote({{ $note['id'] }})" class="hover:text-red-500">&times;</button>
                            </div>
                            <div class="text-gray-800 mt-0.5">{{ $note['body'] }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <div class="text-xs text-gray-700 font-medium">Attachment</div>
            <button type="button" wire:click="toggleAddAttachment" class="text-xs text-[#0077c8] hover:underline font-medium">
                +Add Attachment
            </button>
            @if($showAddAttachment)
                <div class="mt-2 space-y-1.5">
                    <input type="file" wire:model="attachmentDrafts" multiple
                           class="w-full text-[11px] text-gray-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-[#0077c8] file:text-white">
                    @error('attachmentDrafts.*') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                    <button wire:click="uploadAttachments" wire:loading.attr="disabled"
                            class="bg-gray-700 hover:bg-gray-800 text-white py-1 px-2.5 rounded text-[11px]">
                        Upload
                    </button>
                </div>
            @endif

            @if(count($activeAttachments))
                <ul class="space-y-1.5 mt-2">
                    @foreach($activeAttachments as $att)
                        <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-1.5 text-[11px]">
                            <a href="{{ $att['url'] }}" target="_blank" class="text-[#0077c8] hover:underline truncate">
                                {{ $att['name'] }}
                            </a>
                            <button wire:click="deleteAttachment({{ $att['id'] }})" class="text-red-400 hover:text-red-600 ml-1">&times;</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- MY RECENT ACTIVITY CARD (MATCHING SCREENSHOT media_1791324649294.jpg) --}}
    <div class="w-full mt-6 bg-white border border-gray-200 rounded shadow-xs overflow-hidden">
        <div class="px-3.5 py-2.5 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-800">My Recent Activity</h4>
        </div>

        <div class="px-2 py-1">
            <table class="w-full text-xs">
                <thead>
                <tr class="text-[#003d6b] font-bold border-b border-gray-200 text-[11px]">
                    <th class="text-left py-2 px-1.5">IN</th>
                    <th class="text-left py-2 px-1.5">OUT</th>
                    <th class="text-right py-2 px-1.5"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentActivity as $row)
                    <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50/80 transition"
                        wire:click="openDetail({{ $row['id'] }})">
                        <td class="py-2.5 px-1.5 text-gray-700 whitespace-nowrap text-[11px]">
                            <div>{{ $row['in_formatted'] }}</div>
                            <div class="text-[10px] text-gray-400 truncate max-w-[140px]" title="{{ $row['job_name'] }} / {{ $row['task_name'] }}">
                                {{ $row['job_name'] }} &middot; {{ $row['task_name'] }}
                            </div>
                        </td>
                        <td class="py-2.5 px-1.5 whitespace-nowrap text-[11px] {{ $row['is_active'] ? 'italic text-gray-500' : 'text-gray-700' }}">
                            <div>{{ $row['out_formatted'] }}</div>
                            @if(!empty($row['is_switched']))
                                <span class="text-[9px] font-semibold text-sky-700 bg-sky-50 border border-sky-200 rounded px-1 py-0.2">Switched &#8646;</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-1.5 text-right whitespace-nowrap" wire:click.stop>
                            @php
                                $policy = $row['task_gps_policy'] ?? 'none';
                                $hasCoords = !empty($row['clock_in_latitude']) && !empty($row['clock_in_longitude']);
                                $isFlagged = !empty($row['location_flagged']);
                            @endphp
                            <div class="inline-flex items-center gap-1.5">
                                {{-- GPS Pin --}}
                                @if($policy !== 'none' && $hasCoords && !$isFlagged)
                                    <i class="fa-solid fa-location-dot text-emerald-500 text-[11px]" title="GPS Recorded"></i>
                                @elseif($policy !== 'none' && (!$hasCoords || $isFlagged))
                                    <i class="fa-solid fa-location-dot text-rose-500 text-[11px]" title="{{ $row['location_flag_reason'] ?? 'GPS missing' }}"></i>
                                @else
                                    <i class="fa-solid fa-location-dot text-gray-300 text-[11px]" title="GPS Disabled"></i>
                                @endif

                                {{-- Flags --}}
                                @if(!empty($row['is_manual']))
                                    <span class="text-amber-700 bg-amber-100 rounded px-1 text-[8px] font-bold">M</span>
                                @endif
                                @if(!empty($row['is_unusual']))
                                    <i class="fa-solid fa-flag text-amber-500 text-[10px]" title="{{ $row['unusual_reason'] }}"></i>
                                @endif

                                {{-- Approval --}}
                                @if(!empty($row['approved']))
                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[11px]" title="Approved"></i>
                                @elseif(!empty($row['is_rejected']))
                                    <i class="fa-solid fa-circle-xmark text-rose-500 text-[11px]" title="Rejected"></i>
                                @elseif($row['action_requested'])
                                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-[11px]" title="Action Requested"></i>
                                @else
                                    <i class="fa-solid fa-clock text-amber-400 text-[11px]" title="Pending"></i>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-4 text-center text-gray-400 text-xs">No recent activity.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SWITCH MODAL --}}
    @if($showSwitchModal)
        @teleport('body')
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-5 my-auto">
                <h3 class="text-sm font-bold text-gray-800 mb-3">Switch Job / Task</h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Job*</label>
                        <select wire:model.live="switchAssignment" class="w-full border border-gray-300 rounded p-1.5 text-xs">
                            <option value="">{{ count($assignments) === 0 ? 'No Assigned Jobs Available' : 'Select Job' }}</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('switchAssignment') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Task*</label>
                        <select wire:model.live="switchTask" class="w-full border border-gray-300 rounded p-1.5 text-xs">
                            <option value="">{{ !$switchAssignment ? 'Select a Job first' : (count($tasks) === 0 ? 'No tasks available for this job' : 'Select Task') }}</option>
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @if($switchAssignment && !$switchTask)
                            <p class="text-[11px] text-amber-700 font-medium mt-1">⚠️ A job cannot be started unless a task is selected too.</p>
                        @endif
                        @error('switchTask') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-2 border-t">
                    <button wire:click="cancelSwitch" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="confirmSwitch" class="px-3 py-1.5 text-xs bg-[#0077c8] hover:bg-sky-700 text-white rounded font-semibold">Switch</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
