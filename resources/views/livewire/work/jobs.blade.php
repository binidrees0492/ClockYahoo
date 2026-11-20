<div>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-normal text-gray-800">Jobs</h1>
            @if(!auth()->user()->canEdit('jobs'))
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">Read-Only Mode</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            {{-- Status Filter --}}
            <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded px-2.5 py-1.5 shadow-2xs">
                <span class="text-xs text-gray-500 font-semibold">Status:</span>
                <select wire:model.live="statusFilter" class="text-xs font-semibold text-gray-800 bg-transparent focus:outline-none cursor-pointer">
                    <option value="all">All Statuses</option>
                    <option value="ongoing">⚡ Ongoing Only</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="in progress">In Progress</option>
                    <option value="active">Active</option>
                    <option value="on hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            @if(auth()->user()?->isSuperAdmin())
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 cursor-pointer bg-white border border-gray-300 rounded px-2.5 py-1.5 shadow-2xs hover:bg-gray-50">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                    <span>🗑️ Include Deleted Records</span>
                </label>
            @endif

            @if(auth()->user()->canEdit('jobs'))
                <button wire:click="openModal" class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold inline-flex items-center gap-1.5 shadow-2xs">
                    <span>+</span> Add Job
                </button>
            @endif
        </div>
    </div>

    <div class="border border-gray-200 rounded bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="p-3 text-gray-600 font-semibold">Job Name</th>
                <th class="p-3 text-gray-600 font-semibold">Location</th>
                <th class="p-3 text-gray-600 font-semibold w-40">GPS Policy</th>
                <th class="p-3 text-gray-600 font-semibold w-28">Status</th>
                @if(auth()->user()->canEdit('jobs'))
                    <th class="p-3 text-gray-600 font-semibold text-right">Actions</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($jobs as $job)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="p-3 text-gray-800 font-medium whitespace-normal break-words max-w-xs">
                        <div>{{ $job->name }}</div>
                        <div class="text-[11px] text-gray-400">ID: #{{ $job->id }}</div>
                    </td>
                    <td class="p-3 text-gray-600 whitespace-normal break-words max-w-xs">
                        @if($job->locationPreset)
                            <div class="font-medium text-gray-800 flex items-center gap-1">
                                <span>🏢</span> {{ $job->locationPreset->display_name }}
                            </div>
                            @if($job->latitude && $job->longitude)
                                <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                    📍 {{ number_format($job->latitude, 4) }}, {{ number_format($job->longitude, 4) }} ({{ $job->geofence_radius ?? 200 }}m)
                                </div>
                            @endif
                        @elseif($job->location)
                            <div class="text-gray-800">{{ $job->location }}</div>
                        @else
                            <span class="text-gray-400 italic">No location preset</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if(($job->gps_policy ?? 'none') === 'none')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 font-medium">
                                Off (Default)
                            </span>
                        @elseif($job->gps_policy === 'flag')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800 font-medium">
                                ⚠️ Flag Discrepancy
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 font-medium">
                                🛡️ Mandatory GPS
                            </span>
                        @endif
                    </td>
                    <td class="p-3">
                        @php
                            $badgeClass = match(strtolower($job->status)) {
                                'active'      => 'bg-green-100 text-green-800 border-green-200',
                                'in progress' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                'scheduled'   => 'bg-blue-100 text-blue-800 border-blue-200',
                                'completed'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'on hold'     => 'bg-amber-100 text-amber-800 border-amber-200',
                                'cancelled'   => 'bg-red-100 text-red-800 border-red-200',
                                default       => 'bg-gray-100 text-gray-700 border-gray-200',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $badgeClass }}">
                            {{ ucfirst($job->status) }}
                        </span>
                    </td>
                    @if(auth()->user()->canEdit('jobs'))
                        <td class="p-3 text-right">
                            @if(strtolower($job->status) === 'inactive' && auth()->user()?->isSuperAdmin())
                                <button wire:click="restoreJob({{ $job->id }})" class="text-emerald-600 hover:text-emerald-800 hover:underline text-xs font-bold mr-3 inline-flex items-center gap-1">
                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                </button>
                                @if(auth()->user()?->canHardDelete())
                                    <button wire:click="permanentlyDeleteJob({{ $job->id }})" wire:confirm="PERMANENT DELETE: Are you sure you want to permanently delete job '{{ $job->name }}' and ALL linked child records (tasks, time logs, schedules)? This action cannot be undone." class="text-rose-600 hover:text-rose-800 hover:underline text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-trash-can"></i> Delete Permanently
                                    </button>
                                @endif
                            @else
                                <button wire:click="edit({{ $job->id }})" class="text-cs-blue text-xs font-semibold mr-3 hover:underline">Edit</button>
                                <button wire:click="delete({{ $job->id }})" wire:confirm="Are you sure you want to delete this job?" class="text-red-500 text-xs hover:underline">Delete</button>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->canEdit('jobs') ? 5 : 4 }}" class="p-8 text-center text-gray-400">No jobs found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create/Edit Modal with Location Preset Dropdown & Leaflet Map -->
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl my-6 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-gray-800">{{ $jobId ? 'Edit Job' : 'Add Job' }}</h3>
                        <p class="text-xs text-gray-500">Assign site location presets, GPS tracking policies, and job details.</p>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <form id="job-form" wire:submit.prevent="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-700 font-semibold block mb-1">Job Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="name" placeholder="e.g. Headquarters Remodel, Tower Phase 2" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-xs text-gray-700 font-semibold block mb-1">Status <span class="text-red-500">*</span></label>
                                <select wire:model="status" class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in progress">In Progress</option>
                                    <option value="active">Active</option>
                                    <option value="on hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Location Preset Dropdown -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs text-gray-700 font-semibold flex items-center gap-1.5">
                                    <span>🏢 Location Preset (from Locations)</span>
                                </label>
                                <a href="{{ route('map') }}" target="_blank" class="text-[11px] text-cs-blue hover:underline">
                                    Manage Locations &rarr;
                                </a>
                            </div>
                            <select wire:model.live="locationId" class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                <option value="">-- No Location / Remote Job --</option>
                                @foreach($locationPresets as $preset)
                                    <option value="{{ $preset->id }}">{{ $preset->display_name }}</option>
                                @endforeach
                            </select>
                            @error('locationId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            <p class="text-[11px] text-gray-500 mt-1">
                                Selecting a location will automatically navigate the map to its coordinates and configure default tracking.
                            </p>
                        </div>

                        <!-- GPS Policy Selector -->
                        <div>
                            <label class="text-xs text-gray-700 font-semibold block mb-1">GPS Location Tracking Policy</label>
                            <select wire:model.live="gpsPolicy" class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                <option value="none">Off (No GPS Tracking required - Default)</option>
                                <option value="flag">Flag Discrepancy (Flag if employee clocks in outside radius)</option>
                                <option value="mandatory">Mandatory GPS (Require valid GPS coordinates within geofence)</option>
                            </select>
                            @error('gpsPolicy') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Override Warning Banner -->
                        @if($gpsPolicyWarning)
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5 shadow-xs">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600 flex-shrink-0 text-sm"></i>
                                <div>
                                    <div class="font-bold text-amber-900">GPS Policy Override Warning</div>
                                    <div class="text-[11px] mt-0.5">{{ $gpsPolicyWarning }}</div>
                                </div>
                            </div>
                        @endif

                        <!-- Hidden Form Inputs for Coords & Radius -->
                        <input type="hidden" wire:model="latitude">
                        <input type="hidden" wire:model="longitude">
                        <input type="hidden" wire:model="geofenceRadius">

                        <!-- Interactive Leaflet Map Preview Container -->
                        <div x-data="jobMapViewer()" x-init="init()" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                                    <span>📍 Map & Geofence Preview</span>
                                </label>
                                @if($latitude && $longitude)
                                    <span class="text-[11px] font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                        {{ number_format((float)$latitude, 5) }}, {{ number_format((float)$longitude, 5) }} &bull; Radius: {{ $geofenceRadius ?: 200 }}m
                                    </span>
                                @endif
                            </div>

                            <div wire:ignore class="relative w-full rounded-lg overflow-hidden border border-gray-300 shadow-inner bg-slate-100" style="height: 220px;">
                                <div x-ref="jobMapContainer" class="w-full h-full z-0"></div>
                                <div x-show="!hasCoordinates" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-gray-50/95 text-gray-500 p-4 text-center">
                                    <span class="text-2xl mb-1">📍</span>
                                    <span class="text-xs font-semibold text-gray-700">No Location Preset Assigned</span>
                                    <span class="text-[11px] text-gray-400">Select a location preset above to preview coordinates and geofence.</span>
                                </div>
                                <div x-show="hasCoordinates" class="absolute bottom-1 left-2 z-10 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-[10px] text-gray-600 font-medium pointer-events-none shadow-xs">
                                    Preview of attached coordinates & geofence (Use +/- or scroll to zoom)
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <button type="button" @click="navigateToCoordinates()" class="flex-1 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-[#005a9c] border border-blue-200 rounded text-xs font-semibold flex items-center justify-center gap-1.5 transition shadow-2xs">
                                    <span>🎯</span> Navigate to Coordinates & View Radius
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2.5 flex-shrink-0">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 text-sm text-gray-700 font-medium rounded hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit" form="job-form" class="px-5 py-2 text-sm bg-cs-blue text-white font-semibold rounded hover:bg-blue-800 shadow-sm transition">
                        Save Job
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function jobMapViewer() {
    return {
        map: null,
        marker: null,
        circle: null,
        hasCoordinates: false,
        resizeObserver: null,

        init() {
            this.$nextTick(() => {
                setTimeout(() => { this.updateMap(); }, 150);
                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 350);
            });

            this.$wire.$watch('latitude', () => { this.updateMap(); });
            this.$wire.$watch('longitude', () => { this.updateMap(); });
            this.$wire.$watch('geofenceRadius', () => { this.updateMap(); });

            window.addEventListener('job-location-preset-selected', (e) => {
                const detail = e.detail && e.detail[0] ? e.detail[0] : (e.detail || {});
                if (detail.lat && detail.lng) {
                    this.updateMapWithCoords(parseFloat(detail.lat), parseFloat(detail.lng), parseInt(detail.radius) || 200);
                }
            });

            window.addEventListener('job-location-preset-cleared', () => {
                this.hasCoordinates = false;
                if (this.marker) { this.marker.remove(); this.marker = null; }
                if (this.circle) { this.circle.remove(); this.circle = null; }
            });
        },

        navigateToCoordinates() {
            let lat = parseFloat(this.$wire.latitude);
            let lng = parseFloat(this.$wire.longitude);
            let radius = parseInt(this.$wire.geofenceRadius) || 200;
            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;
            this.updateMapWithCoords(lat, lng, radius);
            if (this.map) {
                if (this.circle) {
                    this.map.fitBounds(this.circle.getBounds(), { padding: [30, 30], maxZoom: 16 });
                } else {
                    this.map.setView([lat, lng], 15);
                }
                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
            }
        },

        updateMap() {
            let lat = parseFloat(this.$wire.latitude);
            let lng = parseFloat(this.$wire.longitude);
            let radius = parseInt(this.$wire.geofenceRadius) || 200;
            this.updateMapWithCoords(lat, lng, radius);
        },

        updateMapWithCoords(lat, lng, radius) {
            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                this.hasCoordinates = false;
                return;
            }
            this.hasCoordinates = true;

            const container = this.$refs.jobMapContainer;
            if (!container || typeof L === 'undefined') return;

            if (!this.map) {
                if (container._leaflet_id) {
                    delete container._leaflet_id;
                }
                container.innerHTML = '';
                this.map = L.map(container, {
                    attributionControl: false,
                    zoomControl: true,
                    dragging: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    boxZoom: true,
                    keyboard: true,
                    touchZoom: true
                }).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(this.map);

                if (window.ResizeObserver && !this.resizeObserver) {
                    this.resizeObserver = new ResizeObserver(() => {
                        if (this.map) this.map.invalidateSize();
                    });
                    this.resizeObserver.observe(container);
                }
            } else {
                this.map.setView([lat, lng], 15);
            }

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng]).addTo(this.map);
            }

            if (this.circle) {
                this.circle.setLatLng([lat, lng]);
                this.circle.setRadius(radius);
            } else {
                this.circle = L.circle([lat, lng], {
                    radius: radius,
                    color: '#005a9c',
                    weight: 2,
                    fillColor: '#005a9c',
                    fillOpacity: 0.16
                }).addTo(this.map);
            }

            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 350);
        }
    };
}
</script>
