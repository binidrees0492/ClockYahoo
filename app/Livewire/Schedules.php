<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\User;
use App\Models\CompanySetting;
use Carbon\Carbon;

class Schedules extends Component
{
    public $weekOffset = 0;
    public $employeeFilter = null;

    public $showModal = false;
    public $editingId = null;
    public $employeeId = null;
    public $assignmentId = null;
    public $taskId = null;
    public $shiftDate = null;
    public $startTime = '08:00';
    public $endTime = '17:00';
    public $standardHours = 8.00;
    public $latitude = null;
    public $longitude = null;
    public $geofenceRadius = 200;
    public $locationName = null;
    public $notes = null;

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        if (!auth()->user()->canAccessSchedules()) {
            abort(403, 'Unauthorized access to Schedules.');
        }
    }

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function openAdd($date = null)
    {
        if (!auth()->user()->canEditSchedules()) {
            $this->toast('You only have read-only access to Schedules.', 'error');
            return;
        }

        $this->reset(['editingId','employeeId','assignmentId','taskId','notes','latitude','longitude','locationName']);
        $this->shiftDate      = $date ?: Carbon::now()->toDateString();
        $this->startTime      = '08:00';
        $this->endTime        = '17:00';
        $this->standardHours  = 8.00;
        $this->geofenceRadius = 200;
        $this->showModal      = true;
    }

    public function openEdit($id)
    {
        $s = Schedule::findOrFail($id);
        $this->editingId      = $s->id;
        $this->employeeId     = $s->employee_id;
        $this->assignmentId   = $s->assignment_id;
        $this->taskId         = $s->task_id;
        $this->shiftDate      = $s->shift_date->toDateString();
        $this->startTime      = substr($s->start_time, 0, 5);
        $this->endTime        = substr($s->end_time, 0, 5);
        $this->standardHours  = (float) ($s->standard_hours ?: 8.00);
        $this->latitude       = $s->latitude;
        $this->longitude      = $s->longitude;
        $this->geofenceRadius = $s->geofence_radius ?: 200;
        $this->locationName   = $s->location_name;
        $this->notes          = $s->notes;
        $this->showModal      = true;
    }

    public function updatedAssignmentId($val)
    {
        if ($this->taskId) {
            $task = Task::find($this->taskId);
            if (!$task || $task->assignment_id != $val) {
                $this->taskId = null;
            }
        }
    }

    public function updatedTaskId($val)
    {
        if ($val) {
            $task = Task::find($val);
            if ($task) {
                if ($task->assignment_id) {
                    $this->assignmentId = $task->assignment_id;
                }
                $this->latitude       = $task->latitude;
                $this->longitude      = $task->longitude;
                $this->geofenceRadius = $task->geofence_radius ?: 200;
                $this->locationName   = $task->name;
            }
        }
    }

    public function save()
    {
        $this->validate([
            'employeeId'     => 'required|exists:users,id',
            'assignmentId'   => 'required|exists:tbl_assignments,id',
            'taskId'         => 'required|exists:tbl_tasks,id',
            'shiftDate'      => 'required|date',
            'startTime'      => 'required',
            'endTime'        => 'required|after:startTime',
            'standardHours'  => 'required|numeric|min:0.5|max:24',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'geofenceRadius' => 'nullable|integer|min:10|max:10000',
        ], [
            'employeeId.required'   => 'Please select an employee.',
            'assignmentId.required' => 'Please select a job.',
            'taskId.required'       => 'Please select a task.',
        ]);

        $data = [
            'employee_id'     => $this->employeeId,
            'assignment_id'   => $this->assignmentId,
            'task_id'         => $this->taskId,
            'shift_date'      => $this->shiftDate,
            'start_time'      => $this->startTime,
            'end_time'        => $this->endTime,
            'standard_hours'  => $this->standardHours,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'geofence_radius' => $this->geofenceRadius ?: 200,
            'location_name'   => $this->locationName,
            'notes'           => $this->notes,
        ];

        if ($this->editingId) {
            $schedule = Schedule::findOrFail($this->editingId);
            $schedule->update($data);
            $this->toast('Shift updated.');
        } else {
            $schedule = Schedule::create($data);
            $this->toast('Shift added.');
        }

        // Notify stakeholders (employee, manager, HR)
        \App\Services\TimeLogComplianceService::notifyScheduleAssigned($schedule, auth()->id());

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
        $weekStartDay = CompanySetting::getWeekStartDay();
        $start = Carbon::now()->startOfWeek($weekStartDay)->addWeeks($this->weekOffset);
        $end   = $start->copy()->addDays(6);
        $days  = collect(range(0,6))->map(fn ($i) => $start->copy()->addDays($i));

        $user = auth()->user();
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $employeesQuery = User::notSuperAdmin()->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }
        $employees = $employeesQuery->get();

        $scheduleQuery = Schedule::with(['employee','assignment','task'])
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()]);

        if ($this->employeeFilter) {
            $scheduleQuery->where('employee_id', $this->employeeFilter);
        } elseif ($accessibleIds !== null) {
            $scheduleQuery->whereIn('employee_id', $accessibleIds);
        }

        $schedules = $scheduleQuery->get()
            ->groupBy(fn ($s) => $s->shift_date->toDateString());

        return view('livewire.schedules', [
            'startOfWeek' => $start,
            'days'        => $days,
            'schedules'   => $schedules,
            'employees'   => $employees,
            'assignments' => Assignment::orderBy('name')->get(),
            'tasks'       => Task::orderBy('name')->get(),
        ]);
    }
}
