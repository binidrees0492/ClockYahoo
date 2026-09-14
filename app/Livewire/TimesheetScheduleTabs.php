<?php

namespace App\Livewire;

use Livewire\Component;

class TimesheetScheduleTabs extends Component
{
    public $tab = 'timesheet';

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function render()
    {
        return view('livewire.timesheet-schedule-tabs');
    }
}
