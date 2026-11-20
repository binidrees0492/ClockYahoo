<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'tbl_locations';
    
    protected $fillable = [
        'name',
        'site_alias',
        'latitude',
        'longitude',
        'geofence_radius',
        'tracking_policy',
        'address',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'location_id');
    }

    /**
     * Get formatted display name: e.g. "DT-01 - Downtown Branch (Tracked)"
     */
    public function getDisplayNameAttribute(): string
    {
        $aliasPart = !empty($this->site_alias) ? "{$this->site_alias} - " : '';
        $trackedPart = ($this->tracking_policy === 'disabled') ? '(Untracked)' : '(Tracked)';
        return "{$aliasPart}{$this->name} {$trackedPart}";
    }

    /**
     * Check if tracking is disabled for this preset
     */
    public function isUntracked(): bool
    {
        return $this->tracking_policy === 'disabled';
    }
}
