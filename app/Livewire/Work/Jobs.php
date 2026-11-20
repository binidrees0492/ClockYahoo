<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Location;

class Jobs extends Component
{
    public $jobId = null;
    public $name = '';
    public $locationId = null;
    public $location = '';
    public $gpsPolicy = 'none';
    public $geofenceRadius = 200;
    public $latitude = null;
    public $longitude = null;
    public $status = 'active';
    public $statusFilter = 'all';
    public $includeDeleted = false;
    public $isModalOpen = false;

    protected function rules()
    {
        return [
            'name'           => 'required|string|max:255',
            'locationId'     => 'nullable|exists:tbl_locations,id',
            'location'       => 'nullable|string|max:255',
            'gpsPolicy'      => 'required|in:none,flag,mandatory,required',
            'geofenceRadius' => 'required|integer|min:10|max:10000',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'status'         => 'required|in:scheduled,in progress,completed,on hold,cancelled,active',
        ];
    }

    public function updatedLocationId($value)
    {
        if ($value) {
            $preset = Location::find($value);
            if ($preset) {
                $this->location       = $preset->name;
                $this->latitude       = $preset->latitude;
                $this->longitude      = $preset->longitude;
                $this->geofenceRadius = $preset->geofence_radius ?: 200;

                // Set suggested GPS policy based on preset tracking policy
                if ($preset->tracking_policy === 'disabled') {
                    $this->gpsPolicy = 'none';
                } elseif ($preset->tracking_policy === 'required') {
                    $this->gpsPolicy = 'mandatory';
                } elseif ($preset->tracking_policy === 'optional') {
                    $this->gpsPolicy = 'flag';
                }

                $this->dispatch('job-location-preset-selected', [
                    'lat'    => (float) $this->latitude,
                    'lng'    => (float) $this->longitude,
                    'radius' => (int) $this->geofenceRadius,
                    'name'   => $preset->display_name,
                ]);
                return;
            }
        }

        $this->location = '';
        $this->latitude = null;
        $this->longitude = null;
        $this->geofenceRadius = 200;
        $this->gpsPolicy = 'none';
        $this->dispatch('job-location-preset-cleared');
    }

    public function getGpsPolicyWarningProperty(): ?string
    {
        if (!$this->locationId) {
            return null;
        }

        $preset = Location::find($this->locationId);
        if (!$preset) {
            return null;
        }

        $normalizedPolicy = $this->gpsPolicy === 'required' ? 'mandatory' : $this->gpsPolicy;

        if ($preset->tracking_policy === 'required' && $normalizedPolicy !== 'mandatory') {
            $policyLabel = $normalizedPolicy === 'none' ? 'Off (Untracked)' : 'Flag Discrepancy';
            return "Selected GPS policy '{$policyLabel}' is more lenient than the location's policy (Required). This may violate the parent policy for this location, but you can still create and save this job.";
        }

        if ($preset->tracking_policy === 'disabled' && $normalizedPolicy !== 'none') {
            $policyLabel = $normalizedPolicy === 'mandatory' ? 'Mandatory GPS' : 'Flag Discrepancy';
            return "Selected GPS policy '{$policyLabel}' differs from the location's policy (Disabled/Untracked). You can still create and save this job.";
        }

        return null;
    }

    public function mount()
    {
        if (!auth()->user()->canView('jobs')) {
            abort(403, 'Unauthorized access to Jobs.');
        }
    }

    public function openModal()
    {
        if (!auth()->user()->canEdit('jobs')) {
            return;
        }

        $this->resetValidation();
        $this->reset(['jobId', 'name', 'locationId', 'location', 'latitude', 'longitude']);
        $this->gpsPolicy = 'none';
        $this->geofenceRadius = 200;
        $this->status = 'active';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($id)
    {
        if (!auth()->user()->canEdit('jobs')) {
            return;
        }

        $this->resetValidation();
        $job = Assignment::findOrFail($id);
        $this->jobId = $job->id;
        $this->name = $job->name;
        $this->locationId = $job->location_id;
        $this->location = $job->location ?? '';
        $this->gpsPolicy = $job->gps_policy ?? 'none';
        $this->geofenceRadius = $job->geofence_radius ?? 200;
        $this->latitude = $job->latitude;
        $this->longitude = $job->longitude;
        $this->status = $job->status;

        if (!$this->locationId && !empty($this->location)) {
            $preset = Location::where('name', $this->location)->first();
            if ($preset) {
                $this->locationId = $preset->id;
                if ($this->latitude === null) $this->latitude = $preset->latitude;
                if ($this->longitude === null) $this->longitude = $preset->longitude;
                if (!$this->geofenceRadius) $this->geofenceRadius = $preset->geofence_radius ?: 200;
            }
        } elseif (!$this->locationId && $this->latitude && $this->longitude) {
            $preset = Location::where('latitude', $this->latitude)->where('longitude', $this->longitude)->first();
            if ($preset) {
                $this->locationId = $preset->id;
            }
        }

        $this->isModalOpen = true;

        if ($this->latitude && $this->longitude) {
            $this->dispatch('job-location-preset-selected', [
                'lat'    => (float) $this->latitude,
                'lng'    => (float) $this->longitude,
                'radius' => (int) $this->geofenceRadius,
                'name'   => $this->location,
            ]);
        }
    }

    public function save()
    {
        if (!auth()->user()->canEdit('jobs')) {
            abort(403, 'Unauthorized to modify jobs.');
        }

        if ($this->gpsPolicy === 'required') {
            $this->gpsPolicy = 'mandatory';
        }

        if ($this->locationId) {
            $preset = Location::find($this->locationId);
            if ($preset) {
                $this->location = $preset->name;
                $this->latitude = $preset->latitude;
                $this->longitude = $preset->longitude;
                if (!$this->geofenceRadius) {
                    $this->geofenceRadius = $preset->geofence_radius ?: 200;
                }
            }
        } else {
            $this->latitude = null;
            $this->longitude = null;
        }

        $this->validate();

        Assignment::updateOrCreate(
            ['id' => $this->jobId],
            [
                'name'            => $this->name,
                'location_id'     => $this->locationId ?: null,
                'location'        => $this->location,
                'gps_policy'      => $this->gpsPolicy,
                'geofence_radius' => $this->geofenceRadius ?: 200,
                'latitude'        => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude'       => $this->longitude !== null ? (float) $this->longitude : null,
                'status'          => $this->status,
            ]
        );

        $msg = $this->jobId ? 'Job updated successfully.' : 'Job created successfully.';
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
        $this->closeModal();
    }

    public function delete($id)
    {
        if (!auth()->user()->canEdit('jobs')) {
            abort(403, 'Unauthorized to delete jobs.');
        }

        $job = Assignment::findOrFail($id);

        $hasTimeLogs  = \App\Models\TimeLog::where('assignment_id', $id)->exists();
        $hasSchedules = \App\Models\Schedule::where('assignment_id', $id)->exists();
        $hasQuotes    = class_exists(\App\Models\Quote::class) ? \App\Models\Quote::where('assignment_id', $id)->exists() : false;
        $hasInvoices  = class_exists(\App\Models\Invoice::class) ? \App\Models\Invoice::where('assignment_id', $id)->exists() : false;
        $hasTasks     = \App\Models\Task::where('assignment_id', $id)->exists();

        if ($hasTimeLogs || $hasSchedules || $hasQuotes || $hasInvoices || $hasTasks) {
            if (auth()->user()?->canHardDelete()) {
                \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('jobs', $id);
                $msg = "Job '{$job->name}' and all linked child records permanently deleted.";
                session()->flash('message', $msg);
                $this->dispatch('toast', message: $msg, type: 'info');
                return;
            }

            $job->update(['status' => 'inactive']);
            $msg = "Job '{$job->name}' has existing child records and cannot be deleted. Marked as inactive instead to preserve data integrity.";
            session()->flash('message', $msg);
            $this->dispatch('toast', message: $msg, type: 'warning');
            return;
        }

        $job->delete();
        session()->flash('message', 'Job deleted successfully.');
        $this->dispatch('toast', message: 'Job deleted successfully.', type: 'success');
    }

    public function restoreJob($id)
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore deleted records.');
        }

        $job = Assignment::findOrFail($id);
        $job->update(['status' => 'active']);

        // Restore child tasks
        \App\Models\Task::where('assignment_id', $id)
            ->where('status', 'inactive')
            ->update(['status' => 'active']);

        // Unarchive child time logs
        \App\Models\TimeLog::where('assignment_id', $id)
            ->where('is_archived', true)
            ->update([
                'is_archived' => false,
                'archived_at' => null,
                'archived_by' => null,
            ]);

        $this->dispatch('toast', message: "Job '{$job->name}' and child tasks/records restored successfully.", type: 'success');
    }

    public function permanentlyDeleteJob($id)
    {
        if (!auth()->user()?->canHardDelete()) {
            abort(403, 'Unauthorized to permanently delete records.');
        }

        $job = Assignment::findOrFail($id);
        $name = $job->name;

        \App\Services\InactiveRecordsHubService::permanentlyDeleteRecord('jobs', $id);

        $this->dispatch('toast', message: "Job '{$name}' and all child tasks/records permanently deleted.", type: 'info');
    }

    public function render()
    {
        $query = Assignment::with('locationPreset');

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
            $assignedJobIds = $user->getAssignedJobs()->pluck('id')->toArray();
            $query->whereIn('id', $assignedJobIds);
        }

        $jobs = $query->orderBy('name')->get();
        $locationPresets = Location::where('status', '!=', 'inactive')->orderBy('name')->get();

        return view('livewire.work.jobs', [
            'jobs'            => $jobs,
            'allStatuses'     => Assignment::allStatuses(),
            'locationPresets' => $locationPresets,
            'gpsPolicyWarning'=> $this->gpsPolicyWarning,
        ])->layout('layouts.app');
    }
}
