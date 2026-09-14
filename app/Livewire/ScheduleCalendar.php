<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Schedule;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleCalendar extends Component
{
    public $monthOffset = 0;
    public $viewMode = 'month';

    public function previousMonth() { $this->monthOffset--; }
    public function nextMonth()     { $this->monthOffset++; }
    public function thisMonth()     { $this->monthOffset = 0; }

    public function setView($mode)
    {
        $this->viewMode = $mode;
    }

    public function render()
    {
        $anchor = Carbon::now()->startOfMonth()->addMonths($this->monthOffset);

        $gridStart = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd   = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $days = collect();
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        $schedules = Schedule::with(['assignment','task'])
            ->where('employee_id', Auth::id())
            ->whereBetween('shift_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get()
            ->groupBy(fn ($s) => $s->shift_date->toDateString());

        $timeLogs = TimeLog::with(['assignment','task'])
            ->where('employee_id', Auth::id())
            ->whereBetween('clock_in', [$gridStart, $gridEnd->copy()->endOfDay()])
            ->get()
            ->groupBy(fn ($l) => $l->clock_in->toDateString());

        return view('livewire.schedule-calendar', [
            'anchor'     => $anchor,
            'days'       => $days,
            'schedules'  => $schedules,
            'timeLogs'   => $timeLogs,
            'todayKey'   => Carbon::now()->toDateString(),
        ]);
    }
}
