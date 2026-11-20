<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;

class MapLocations extends Component
{
    public $selectedPresetId = null;
    public $name = '';
    public $site_alias = '';
    public $tracking_policy = 'required'; // 'required', 'disabled', 'optional'
    public $latitude = null;
    public $longitude = null;
    public $geofence_radius = 200;
    public $address = '';
    public $status = 'active';

    public $searchQuery = '';
    public $toastMessage = null;
    public $toastType = 'success';

    protected function rules()
    {
        return [
            'name'            => 'required|string|max:100',
            'site_alias'      => 'nullable|string|max:50',
            'tracking_policy' => 'required|in:required,disabled,optional',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'geofence_radius' => 'nullable|integer|min:10|max:10000',
            'address'         => 'nullable|string|max:500',
        ];
    }

    public function resetForm()
    {
        $this->selectedPresetId = null;
        $this->name             = '';
        $this->site_alias       = '';
        $this->tracking_policy  = 'required';
        $this->latitude         = null;
        $this->longitude        = null;
        $this->geofence_radius  = 200;
        $this->address          = '';
        $this->status           = 'active';
        $this->searchQuery      = '';
        $this->resetErrorBag();

        $this->dispatch('map-cleared');
    }

    public function editPreset($id)
    {
        $preset = Location::findOrFail($id);
        $this->selectedPresetId = $preset->id;
        $this->name             = $preset->name;
        $this->site_alias       = $preset->site_alias ?? '';
        $this->tracking_policy  = $preset->tracking_policy ?? 'required';
        $this->latitude         = $preset->latitude;
        $this->longitude        = $preset->longitude;
        $this->geofence_radius  = $preset->geofence_radius ?? 200;
        $this->address          = $preset->address ?? '';
        $this->status           = $preset->status ?? 'active';

        $this->dispatch('preset-selected', [
            'lat'    => $preset->latitude,
            'lng'    => $preset->longitude,
            'radius' => $preset->geofence_radius ?? 200,
            'name'   => $preset->display_name,
        ]);
    }

    public function selectForPreview($id)
    {
        $preset = Location::findOrFail($id);
        $this->dispatch('preset-selected', [
            'lat'    => $preset->latitude,
            'lng'    => $preset->longitude,
            'radius' => $preset->geofence_radius ?? 200,
            'name'   => $preset->display_name,
        ]);
    }

    public function savePreset()
    {
        if (!Auth::user()->canViewMap()) {
            abort(403, 'Unauthorized.');
        }

        $this->validate();

        $data = [
            'name'            => $this->name,
            'site_alias'      => $this->site_alias ?: null,
            'tracking_policy' => $this->tracking_policy,
            'latitude'        => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude'       => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'geofence_radius' => $this->geofence_radius ?: 200,
            'address'         => $this->address ?: null,
            'status'          => $this->status ?: 'active',
        ];

        if ($this->selectedPresetId) {
            $preset = Location::findOrFail($this->selectedPresetId);
            $preset->update($data);
            $this->toast('Location preset updated successfully.');
        } else {
            $preset = Location::create($data);
            $this->toast('New location preset created successfully.');
        }

        $this->resetForm();
    }

    public function deletePreset($id)
    {
        if (!Auth::user()->canViewMap()) {
            abort(403, 'Unauthorized.');
        }

        $preset = Location::findOrFail($id);
        $name = $preset->name;

        $hasSchedules = \App\Models\Schedule::where('location_id', $id)->exists();
        $hasEmployees = \App\Models\User::where('location_id', $id)->exists();
        $hasTimeLogs  = \App\Models\TimeLog::where('location_id', $id)->exists();

        if ($hasSchedules || $hasEmployees || $hasTimeLogs) {
            $preset->update(['status' => 'inactive']);
            if ($this->selectedPresetId === $id) {
                $this->resetForm();
            }
            $this->toast("Location '{$name}' has associated shifts, timelogs, or employees and cannot be deleted. Marked as inactive instead to preserve data integrity.", 'warning');
            return;
        }

        $preset->delete();

        if ($this->selectedPresetId === $id) {
            $this->resetForm();
        }

        $this->toast("Preset '{$name}' deleted.");
    }

    public function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $presets = Location::orderBy('name')->get();

        return view('livewire.map-locations', [
            'presets' => $presets,
        ]);
    }
}
