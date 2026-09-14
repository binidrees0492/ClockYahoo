<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class Schedules extends Component
{
    public $weekOffset = 0;

    public $showModal = false;
    public $editingId = null;
    public $employeeId = null;
    public $assignmentId = null;
    public $taskId = null;
    public $shiftDate = null;
    public $startTime = '08:00';
    public $endTime = '17:00';
    public $notes = null;

    public $toastMessage = null;
    public $toastType = 'success';

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function openAdd($date = null)
    {
        $this->reset(['editingId','employeeId','assignmentId','taskId','notes']);
        $this->shiftDate = $date ?: Carbon::now()->toDateString();
        $this->startTime = '08:00';
        $this->endTime   = '17:00';
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        $s = Schedule::findOrFail($id);
        $this->editingId    = $s->id;
        $this->employeeId   = $s->employee_id;
        $this->assignmentId = $s->assignment_id;
        $this->taskId       = $s->task_id;
        $this->shiftDate    = $s->shift_date->toDateString();
        $this->startTime    = substr($s->start_time, 0, 5);
        $this->endTime      = substr($s->end_time, 0, 5);
        $this->notes        = $s->notes;
        $this->showModal    = true;
    }

    public function save()
    {
        $this->validate([
            'employeeId'   => 'required|exists:users,id',
            'assignmentId' => 'nullable|exists:tbl_assignments,id',
            'taskId'       => 'nullable|exists:tbl_tasks,id',
            'shiftDate'    => 'required|date',
            'startTime'    => 'required',
            'endTime'      => 'required|after:startTime',
        ]);

        $data = [
            'employee_id'   => $this->employeeId,
            'assignment_id' => $this->assignmentId,
            'task_id'       => $this->taskId,
            'shift_date'    => $this->shiftDate,
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'notes'         => $this->notes,
        ];

        if ($this->editingId) {
            Schedule::findOrFail($this->editingId)->update($data);
            $this->toast('Shift updated.');
        } else {
            Schedule::create($data);
            $this->toast('Shift added.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        Schedule::findOrFail($id)->delete();
        $this->toast('Shift deleted.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['editingId','employeeId','assignmentId','taskId','shiftDate','notes']);
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function dismissToast() { $this->toastMessage = null; }

    public function render()
    {
        $start = Carbon::now()->startOfWeek()->addWeeks($this->weekOffset);
        $days  = collect(range(0,6))->map(fn ($i) => $start->copy()->addDays($i));

        $schedules = Schedule::with(['employee','assignment','task'])
            ->whereBetween('shift_date', [$start->toDateString(), $start->copy()->endOfWeek()->toDateString()])
            ->get()
            ->groupBy(fn ($s) => $s->shift_date->toDateString());

        return view('livewire.schedules', [
            'startOfWeek' => $start,
            'days'        => $days,
            'schedules'   => $schedules,
            'employees'   => User::orderBy('name')->get(),
            'assignments' => Assignment::orderBy('name')->get(),
            'tasks'       => Task::orderBy('name')->get(),
        ]);
    }
}
