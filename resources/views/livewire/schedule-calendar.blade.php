<div>

    {{-- HEADER BAR --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            <button wire:click="previousMonth" class="text-gray-500 hover:text-gray-800 text-lg">&#9664;</button>
            <button wire:click="nextMonth"     class="text-gray-500 hover:text-gray-800 text-lg">&#9654;</button>

            <h2 class="text-lg font-bold text-gray-800 ml-2">
                This Month
                <span class="font-normal text-gray-500 text-sm ml-2">
                    {{ $anchor->format('F Y') }}
                </span>
            </h2>

            <button wire:click="thisMonth" class="text-xs text-cs-blue underline ml-2">Reset</button>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-cs-blue text-xl" title="Calendar view">&#128197;</span>

            <select wire:model="viewMode" wire:change="setView($event.target.value)"
                    class="text-sm border border-gray-300 rounded px-2 py-1 bg-white focus:outline-none focus:border-blue-500">
                <option value="month">Month</option>
                <option value="week">Week</option>
            </select>
        </div>
    </div>

    {{-- DAY-NAME ROW --}}
    <div class="grid grid-cols-7 border border-gray-200 bg-white">
        @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dow)
            <div class="text-center text-xs font-bold text-gray-700 py-2 border-b border-gray-200">
                {{ $dow }}
            </div>
        @endforeach
    </div>

    {{-- CALENDAR GRID --}}
    <div class="grid grid-cols-7 border-l border-r border-b border-gray-200 bg-white">
        @foreach($days as $day)
            @php
                $key         = $day->toDateString();
                $isCurrent   = $day->month === $anchor->month && $day->year === $anchor->year;
                $isToday     = $key === $todayKey;
                $daySchedules= $schedules[$key] ?? collect();
                $dayLogs     = $timeLogs[$key] ?? collect();
            @endphp

            <div class="min-h-[140px] border-r border-b border-gray-200 p-2 relative
                        {{ $isToday ? 'bg-cs-today' : 'bg-white' }}
                        {{ !$isCurrent ? 'text-gray-300' : 'text-gray-700' }}">

                <div class="text-right text-xs mb-1">{{ $day->day }}</div>

                @if($isCurrent)
                    @foreach($dayLogs as $log)
                        <div class="text-[10px] leading-tight mb-1 text-gray-700">
                            <span class="font-semibold text-cs-blue">
                                {{ $log->clock_in->format('g:i A') }}
                            </span>
                            @if($log->assignment)
                                <div class="text-gray-500 truncate">{{ $log->assignment->name }}</div>
                            @endif
                        </div>
                    @endforeach

                    @foreach($daySchedules as $s)
                        <div class="text-[10px] leading-tight mb-1 text-gray-700">
                            <span class="font-semibold text-green-700">
                                {{ substr($s->start_time, 0, 5) }}
                            </span>
                            @if($s->assignment)
                                <div class="text-gray-500 truncate">{{ $s->assignment->name }}</div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
</div>
