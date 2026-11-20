<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Location;

class Tasks extends Component
{
    public $taskId = null;
    public $assignmentId = null;
    public $name = '';
    public $hourlyRate = null;
    public $status = 'active';
    public $statusFilter = 'all';
    public $includeDeleted = false;
    public $gpsPolicy = 'none';
    public $geofenceRadius = 200;
    public $latitude = null;
    public $longitude = null;
    public $locationPresetId = null;
    public $isModalOpen = false;

    protected function rules()
    {
        return [
            'assignmentId'     => 'required|exists:tbl_assignments,id',
            'name'             => 'required|string|max:255',
            'hourlyRate'       => 'nullable|numeric|min:0|max:9999.99',
            'status'           => 'required|in:scheduled,in progress,completed,on hold,cancelled,active',
            'gpsPolicy'        => 'required|in:none,flag,mandatory,required',
            'geofenceRadius'   => 'required|integer|min:10|max:10000',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'locationPresetId' => 'nullable|exists:tbl_locations,id',
        ];
    }

    protected $messages = [
        'assignmentId.required' => 'A parent job is required. Tasks cannot be created without a parent job.',
        'assignmentId.exists'   => 'The selected parent job is invalid.',
        'gpsPolicy.in'          => 'The selected GPS policy is invalid.',
    ];

    public function updatedAssignmentId($value)
    {
        if ($value) {
            $job = Assignment::find($value);
            if ($job) {
                // Inherit location from parent job if available
                $targetPresetId = $job->location_id;
                if (!$targetPresetId && !empty($job->location)) {
                    $foundPreset = Location::where('name', $job->location)->first();
                    $targetPresetId = $foundPreset?->id;
                }

                if ($targetPresetId) {
                    $this->locationPresetId = $targetPresetId;
                    $preset = Location::find($targetPresetId);
                    if ($preset) {
                        $this->latitude       = $preset->latitude;
                        $this->longitude      = $preset->longitude;
                        $this->geofenceRadius = $job->geofence_radius ?: ($preset->geofence_radius ?: 200);

                        if (!empty($job->gps_policy) && $job->gps_policy !== 'none') {
                            $this->gpsPolicy = $job->gps_policy;
                        } else {
                            $this->gpsPolicy = match($preset->tracking_policy) {
                                'disabled' => 'none',
                                'required' => 'mandatory',
                                'optional' => 'flag',
                                default    => 'none',
                            };
                        }

                        $this->dispatch('task-location-preset-selected', [
                            'lat'    => (float) $this->latitude,
                            'lng'    => (float) $this->longitude,
                            'radius' => (int) $this->geofenceRadius,
                            'name'   => $preset->display_name,
                        ]);
                        return;
                    }
                } elseif ($job->latitude && $job->longitude) {
                    $this->latitude       = $job->latitude;
                    $this->longitude      = $job->longitude;
                    $this->geofenceRadius = $job->geofence_radius ?: 200;
                    if (!empty($job->gps_policy)) {
                        $this->gpsPolicy = $job->gps_policy;
                    }
                    $this->dispatch('task-location-preset-selected', [
                        'lat'    => (float) $this->latitude,
                        'lng'    => (float) $this->longitude,
                        'radius' => (int) $this->geofenceRadius,
                        'name'   => $job->name,
                    ]);
                    return;
                }
            }
        }
    }

    public function updatedLocationPresetId($value)
    {
        if ($value) {
            $preset = Location::find($value);
            if ($preset) {
                $this->latitude       = $preset->latitude;
                $this->longitude      = $preset->longitude;
                $this->geofenceRadius = $preset->geofence_radius ?: 200;
                $this->gpsPolicy      = match($preset->tracking_policy) {
                    'disabled' => 'none',
                    'required' => 'mandatory',
                    'optional' => 'flag',
                    default    => 'none',
                };

                $this->dispatch('task-location-preset-selected', [
                    'lat'    => (float) $this->latitude,
                    'lng'    => (float) $this->longitude,
                    'radius' => (int) $this->geofenceRadius,
                    'name'   => $preset->display_name,
                ]);
                return;
            }
        }
        $this->latitude = null;
        $this->longitude = null;
        $this->geofenceRadius = 200;
        $this->gpsPolicy = 'none';
        $this->dispatch('task-location-preset-cleared');
    }

    public function getGpsPolicyWarningProperty(): ?string
    {
        if (!$this->locationPresetId) {
            return null;
        }

        $preset = Location::find($this->locationPresetId);
        if (!$preset) {
            return null;
        }

        $normalizedPolicy = $this->gpsPolicy === 'required' ? 'mandatory' : $this->gpsPolicy;

        if ($preset->tracking_policy === 'required' && $normalizedPolicy !== 'mandatory') {
            $policyLabel = $normalizedPolicy === 'none' ? 'Off (Untracked)' : 'Flag Discrepancy';
            return "Selected GPS policy '{$policyLabel}' is more lenient than the location's policy (Required). This may violate the parent policy for this location, but you can still create and save this task.";
        }

        if ($preset->tracking_policy === 'disabled' && $normalizedPolicy !== 'none') {
            $policyLabel = $normalizedPolicy === 'mandatory' ? 'Mandatory GPS' : 'Flag Discrepancy';
            return "Selected GPS policy '{$policyLabel}' differs from the location's policy (Disabled/Untracked). You can still create and save this task.";
        }

        return null;
    }

    public function mount()
    {
        if (!auth()->user()->canView('tasks')) {
            abort(403, 'Unauthorized access to Tasks.');
        }
    }

    public function openModal()
    {
        if (!auth()->user()->canEdit('tasks')) {
            return;
        }

        $this->resetValidation();
        $this->reset(['taskId', 'assignmentId', 'name', 'latitude', 'longitude', 'locationPresetId']);
        $this->hourlyRate = auth()->user()->base_pay ?: null;
        $this->status = 'active';
        $this->gpsPolicy = 'none';
        $this->geofenceRadius = 200;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->locationPresetId = null;
    }

    public function edit($id)
    {
        if (!auth()->user()->canEdit('tasks')) {
            return;
        }

        $this->resetValidation();
        $task = Task::findOrFail($id);
        $this->taskId = $task->id;
        $this->assignmentId = $task->assignment_id;
        $this->name = $task->name;
        $this->hourlyRate = $task->hourly_rate !== null ? (float)$task->hourly_rate : (auth()->user()->base_pay ?: null);
        $this->status = $task->status;
        $this->gpsPolicy = $task->gps_policy ?? 'none';
        $this->geofenceRadius = $task->geofence_radius ?? 200;
        $this->latitude = $task->latitude;
        $this->longitude = $task->longitude;
        $this->locationPresetId = $task->location_id;

        if (!$this->locationPresetId && $task->latitude && $task->longitude) {
            $preset = Location::where('latitude', $task->latitude)
                ->where('longitude', $task->longitude)
                ->first();
            $this->locationPresetId = $preset?->id;
        }

        $this->isModalOpen = true;

        if ($this->latitude && $this->longitude) {
            $this->dispatch('task-location-preset-selected', [
                'lat'    => (float) $this->latitude,
                'lng'    => (float) $this->longitude,
                'radius' => (int) $this->geofenceRadius,
                'name'   => $task->name,
            ]);
        }
    }

    public function save()
    {
        if (!auth()->user()->canEdit('tasks')) {
            abort(403, 'Unauthorized to modify tasks.');
        }

        if ($this->gpsPolicy === 'required') {
            $this->gpsPolicy = 'mandatory';
        }

        // Coordinates strictly derive from Location Presets if chosen
        if ($this->locationPresetId) {
            $preset = Location::find($this->locationPresetId);
            if ($preset) {
                $this->latitude = $preset->latitude;
                $this->longitude = $preset->longitude;
                if (!$this->geofenceRadius) {
                    $this->geofenceRadius = $preset->geofence_radius ?? 200;
                }
            }
        } else {
            $this->latitude = null;
            $this->longitude = null;
        }

        if ($this->latitude === '') {
            $this->latitude = null;
        }
        if ($this->longitude === '') {
            $this->longitude = null;
        }

        $this->validate();

        Task::updateOrCreate(
            ['id' => $this->taskId],
            [
                'assignment_id'   => $this->assignmentId,
                'location_id'     => $this->locationPresetId ?: null,
                'name'            => $this->name,
                'hourly_rate'     => ($this->hourlyRate !== '' && $this->hourlyRate !== null) ? (float) $this->hourlyRate : (auth()->user()->base_pay ?: null),
                'status'          => $this->status,
                'gps_policy'      => $this->gpsPolicy,
                'geofence_radius' => $this->geofenceRadius ?: 200,
                'latitude'        => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude'       => $this->longitude !== null ? (float) $this->longitude : null,
            ]
        );

        $this->closeModal();
        $this->dispatch('toast', message: 'Task saved successfully.', type: 'success');
    }

    public function delete($id)
    {
        if (!auth()->user()->canEdit('tasks')) {
            abort(403, 'Unauthorized to delete tasks.');
        }

        $task = Task::findOrFail($id);

        $hasTimeLogs  = \App\Models\TimeLog::where('task_id', $id)->exists();
        $hasSchedules = \App\Models\Schedule::where('task_id', $id)->exists();

        if ($hasTimeLogs || $hasSchedules) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('tasks', $id);
                $this->dispatch('toast', message: "Task '{$task->name}' and all linked child records permanently deleted.", type: 'info');
                return;
            }

            $task->update(['status' => 'inactive']);
            $this->dispatch('toast', message: "Task '{$task->name}' has existing child records (timesheets or schedules) and cannot be deleted. Marked as inactive instead to preserve data integrity.", type: 'warning');
            return;
        }

        $task->delete();
        $this->dispatch('toast', message: 'Task deleted successfully.', type: 'success');
    }

    public function restoreTask($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore deleted records.');
        }

        $task = Task::findOrFail($id);
        $task->update(['status' => 'active']);

        // Unarchive child time logs for this task
        \App\Models\TimeLog::where('task_id', $id)
            ->where('is_archived', true)
            ->update([
                'is_archived' => false,
                'archived_at' => null,
                'archived_by' => null,
            ]);

        $this->dispatch('toast', message: "Task '{$task->name}' and child records restored successfully.", type: 'success');
    }

    public function permanentlyDeleteTask($id)
    {
        if (!auth()->user()?->canHardDelete()) {
            abort(403, 'Unauthorized to permanently delete records.');
        }

        $task = Task::findOrFail($id);
        $name = $task->name;

        \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('tasks', $id);

        $this->dispatch('toast', message: "Task '{$name}' and all linked child records permanently deleted.", type: 'info');
    }

    public function render()
    {
        $query = Task::with(['assignment', 'locationPreset']);

        if (!$this->includeDeleted || !auth()->user()?->isSuperAdmin()) {
            $query->whereNotIn('status', ['inactive', 'cancelled', 'deleted']);
        }

        if ($this->statusFilter === 'ongoing') {
            $query->ongoing();
        } elseif ($this->statusFilter && !in_array($this->statusFilter, ['all', 'inactive'])) {
            $query->where('status', $this->statusFilter);
        }

        $user = auth()->user();
        if ($user && $user->isEmployee() && !$user->canAccessAdmin()) {
            $assignedTaskIds = $user->getAssignedTasks()->pluck('id')->toArray();
            $query->whereIn('id', $assignedTaskIds);
        }

        $tasks = $query->orderBy('name')->get();

        return view('livewire.work.tasks', [
            'tasks'            => $tasks,
            'allStatuses'      => Task::allStatuses(),
            'availableJobs'    => Assignment::where('status', '!=', 'inactive')->ongoing()->orderBy('name')->get(),
            'locationPresets'  => Location::where('status', '!=', 'inactive')->orderBy('name')->get(),
            'gpsPolicyWarning' => $this->gpsPolicyWarning,
        ])->layout('layouts.app');
    }
}
