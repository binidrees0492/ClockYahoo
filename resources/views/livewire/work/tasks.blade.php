<div>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-normal text-gray-800">Tasks</h1>
            @if(!auth()->user()->canEdit('tasks'))
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

            @if(auth()->user()->canEdit('tasks'))
                <button wire:click="openModal" class="bg-cs-blue hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-semibold inline-flex items-center gap-1.5 shadow-2xs">
                    <span>+</span> Add Task
                </button>
            @endif
        </div>
    </div>

    <div class="border border-gray-200 rounded bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                <th class="p-3 text-gray-600 font-semibold">Task Name</th>
                <th class="p-3 text-gray-600 font-semibold w-28">Rate / hr</th>
                <th class="p-3 text-gray-600 font-semibold">Location & Geofence</th>
                <th class="p-3 text-gray-600 font-semibold w-44">GPS Policy</th>
                <th class="p-3 text-gray-600 font-semibold w-28">Status</th>
                @if(auth()->user()->canEdit('tasks'))
                    <th class="p-3 text-gray-600 font-semibold text-right w-36">Actions</th>
                @endif
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($tasks as $task)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-3">
                        <div class="text-gray-900 font-medium whitespace-normal break-words max-w-xs">{{ $task->name }}</div>
                        <div class="text-[11px] text-gray-400">
                            @if($task->assignment)
                                <span class="text-blue-600 font-semibold">Job: {{ $task->assignment->name }}</span> &middot;
                            @endif
                            ID: #{{ $task->id }}
                        </div>
                    </td>
                    <td class="p-3 text-xs font-semibold text-gray-800">
                        @if($task->hourly_rate)
                            ${{ number_format($task->hourly_rate, 2) }}/hr
                        @else
                            <span class="text-gray-400 font-normal italic">Emp. Rate</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($task->locationPreset)
                            <div class="font-medium text-gray-800 text-xs flex items-center gap-1">
                                <span>🏢</span> {{ $task->locationPreset->display_name }}
                            </div>
                        @endif
                        @if($task->latitude && $task->longitude)
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700 font-mono">
                                    📍 {{ number_format($task->latitude, 5) }}, {{ number_format($task->longitude, 5) }}
                                </span>
                                <a href="https://www.google.com/maps?q={{ $task->latitude }},{{ $task->longitude }}" target="_blank" title="Open in Google Maps" class="text-blue-600 hover:text-blue-800 text-xs inline-flex items-center">
                                    ↗
                                </a>
                            </div>
                            <div class="text-[11px] text-gray-500 mt-0.5">
                                Geofence Radius: <span class="font-semibold text-gray-700">{{ $task->geofence_radius ?? 200 }} meters</span>
                            </div>
                        @elseif(!$task->locationPreset)
                            <span class="text-xs text-gray-400 italic">No GPS coordinates set</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if(($task->gps_policy ?? 'none') === 'none')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 font-medium">
                                Off (Default)
                            </span>
                        @elseif($task->gps_policy === 'flag')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800 font-medium">
                                ⚠️ Flag Outside Radius
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 font-medium">
                                🛡️ Mandatory GPS
                            </span>
                        @endif
                    </td>
                    <td class="p-3">
                        @php
                            $badgeClass = match(strtolower($task->status)) {
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
                            {{ ucfirst($task->status) }}
                        </span>
                    </td>
                    @if(auth()->user()->canEdit('tasks'))
                        <td class="p-3 text-right whitespace-nowrap">
                            @if(strtolower($task->status) === 'inactive' && auth()->user()?->isSuperAdmin())
                                <button wire:click="restoreTask({{ $task->id }})" class="text-emerald-600 hover:text-emerald-800 hover:underline text-xs font-bold mr-3 inline-flex items-center gap-1">
                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                </button>
                                @if(auth()->user()?->canHardDelete())
                                    <button wire:click="permanentlyDeleteTask({{ $task->id }})" wire:confirm="PERMANENT DELETE: Are you sure you want to permanently delete task '{{ $task->name }}' and ALL linked child records (timesheets, schedules)? This action cannot be undone." class="text-rose-600 hover:text-rose-800 hover:underline text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-trash-can"></i> Delete Permanently
                                    </button>
                                @endif
                            @else
                                <button wire:click="edit({{ $task->id }})" class="text-cs-blue text-xs font-semibold mr-3 hover:underline">Edit</button>
                                <button wire:click="delete({{ $task->id }})" wire:confirm="Are you sure you want to delete this task?" class="text-red-500 text-xs hover:underline">Delete</button>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->canEdit('tasks') ? 6 : 5 }}" class="p-8 text-center text-gray-400">No tasks found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create/Edit Modal with Unified Details, Job Location Inheritance & Leaflet Map -->
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl my-6 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-gray-800">{{ $taskId ? 'Edit Task' : 'Add Task' }}</h3>
                        <p class="text-xs text-gray-500">Configure task details, parent job association, location presets, and GPS tracking.</p>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <form id="task-form" wire:submit.prevent="save" class="space-y-4">
                        <!-- Parent Job (Mandatory) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs text-gray-700 font-semibold flex items-center gap-1.5">
                                    <span>Parent Job (Belongs to Job) <span class="text-red-500">*</span></span>
                                </label>
                                <span class="text-[11px] text-gray-500 italic">Tasks must belong to a parent job</span>
                            </div>
                            <select wire:model.live="assignmentId" class="w-full border border-gray-300 rounded p-2 text-sm bg-white focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                <option value="">-- Select Parent Job (Required) * --</option>
                                @foreach($availableJobs as $job)
                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                @endforeach
                            </select>
                            @error('assignmentId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Task Name, Rate, Status -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-700 font-semibold block mb-1">Task Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="name" placeholder="e.g. Electrical Wiring, Plumbing, Drywall" class="w-full border border-gray-300 rounded p-2 text-sm focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-xs text-gray-700 font-semibold block mb-1">Rate ($/hr)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-gray-400">$</span>
                                    <input type="number" step="0.01" wire:model="hourlyRate" placeholder="{{ auth()->user()->base_pay ?: '0.00' }}" class="w-full pl-6 border border-gray-300 rounded p-2 text-sm focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                </div>
                                @error('hourlyRate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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

                        <!-- Location & GPS Tracking Section -->
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                    <span>🏢 Location Preset (Inherited from Job or Custom Override)</span>
                                </label>
                                @php
                                    $parentJob = $assignmentId ? $availableJobs->firstWhere('id', $assignmentId) : null;
                                    $isInherited = $parentJob && $parentJob->location_id && ($parentJob->location_id == $locationPresetId);
                                @endphp
                                @if($isInherited)
                                    <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">
                                        ⚡ Inherited from {{ $parentJob->name }} &bull; Preset Locked
                                    </span>
                                @elseif($locationPresetId)
                                    <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">
                                        🔒 Preset Locked
                                    </span>
                                @endif
                            </div>

                            <select wire:model.live="locationPresetId" class="w-full border border-gray-300 rounded p-2 text-sm bg-white cursor-pointer focus:border-cs-blue focus:ring-1 focus:ring-cs-blue">
                                <option value="">-- No Location / Remote Task --</option>
                                @foreach($locationPresets as $preset)
                                    <option value="{{ $preset->id }}">{{ $preset->display_name }}</option>
                                @endforeach
                            </select>
                            @error('locationPresetId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @if($locationPresetId)
                                <p class="text-[10px] text-blue-700 font-medium mt-1">
                                    🔒 Coordinates & geofence radius are locked to preset. Modify or configure presets from <a href="{{ route('map') }}" target="_blank" class="underline font-bold">Company Locations</a>.
                                </p>
                            @else
                                <p class="text-[11px] text-gray-500">
                                    If a location is set on the parent job, it will automatically populate here. You can change this to any other location preset or clear it.
                                </p>
                            @endif

                            <!-- GPS Policy Selector -->
                            <div class="pt-1">
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

                            {{-- Hidden fields to preserve Livewire binding --}}
                            <input type="hidden" wire:model="latitude">
                            <input type="hidden" wire:model="longitude">
                            <input type="hidden" wire:model="geofenceRadius">

                            <!-- Interactive Leaflet Map Preview Container -->
                            <div x-data="taskMapViewer()" x-init="init()" class="space-y-2 pt-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                                        <span>📍 Map & Geofence Preview</span>
                                    </label>
                                    @if($latitude && $longitude)
                                        <span class="text-[11px] font-mono text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200">
                                            {{ number_format((float)$latitude, 5) }}, {{ number_format((float)$longitude, 5) }} &bull; Radius: {{ $geofenceRadius ?: 200 }}m
                                        </span>
                                    @endif
                                </div>

                                <div wire:ignore class="relative w-full rounded-lg overflow-hidden border border-gray-300 shadow-inner bg-slate-100" style="height: 220px;">
                                    <div x-ref="taskMapContainer" class="w-full h-full z-0"></div>
                                    <div x-show="!hasCoordinates" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-gray-50/95 text-gray-500 p-4 text-center">
                                        <span class="text-2xl mb-1">📍</span>
                                        <span class="text-xs font-semibold text-gray-700">No Location Assigned</span>
                                        <span class="text-[11px] text-gray-400">Select a parent job with a location or choose a location preset above to preview coordinates and geofence.</span>
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
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2.5 flex-shrink-0">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 text-sm text-gray-700 font-medium rounded hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit" form="task-form" class="px-5 py-2 text-sm bg-cs-blue text-white font-semibold rounded hover:bg-blue-800 shadow-sm transition">
                        Save Task
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function taskMapViewer() {
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

            window.addEventListener('task-location-preset-selected', (e) => {
                const detail = e.detail && e.detail[0] ? e.detail[0] : (e.detail || {});
                if (detail.lat && detail.lng) {
                    this.updateMapWithCoords(parseFloat(detail.lat), parseFloat(detail.lng), parseInt(detail.radius) || 200);
                }
            });

            window.addEventListener('task-location-preset-cleared', () => {
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

            const container = this.$refs.taskMapContainer;
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
