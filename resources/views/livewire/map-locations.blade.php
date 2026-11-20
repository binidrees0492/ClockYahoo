<div class="space-y-6">
    @if(!Auth::user()->canViewMap())
        <div class="bg-white rounded-xl shadow p-8 text-center text-red-600 border border-red-200">
            <h2 class="text-xl font-semibold mb-2">Access Restricted</h2>
            <p class="text-sm text-gray-500">You do not have permission to view or manage GPS map tracking presets.</p>
        </div>
    @else
        {{-- Toast Alert --}}
        @if($toastMessage)
            <div class="flex items-center justify-between p-3 rounded-lg text-xs font-semibold {{ $toastType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' }}">
                <span>{{ $toastMessage }}</span>
                <button wire:click="dismissToast" class="text-gray-400 hover:text-gray-600 font-bold ml-2">&times;</button>
            </div>
        @endif

        {{-- Top Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div>
                <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span>🗺️</span> Map & Location Presets
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Create and manage site geofence presets. Presets can be selected by Site Alias on shifts and tasks, locking coordinates and radius.
                </p>
            </div>
            <div>
                <button type="button" wire:click="resetForm" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <span>+</span> New Site Preset
                </button>
            </div>
        </div>

        {{-- Main Grid: Form + Interactive Map --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6"
             x-data="mapLocationsManager()"
             @preset-selected.window="loadPresetOnMap($event.detail)"
             @map-cleared.window="clearMap()">

            {{-- LEFT: PRESET CONFIGURATION FORM (5 cols) --}}
            <div class="lg:col-span-5 bg-white rounded-xl border border-gray-200 shadow-xs p-5 space-y-4">
                <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">
                        {{ $selectedPresetId ? 'Edit Location Preset' : 'Create Location Preset' }}
                    </h2>
                    @if($selectedPresetId)
                        <span class="text-[10px] bg-blue-100 text-cs-blue px-2 py-0.5 rounded font-bold">
                            Editing #{{ $selectedPresetId }}
                        </span>
                    @endif
                </div>

                <form wire:submit.prevent="savePreset" class="space-y-4">
                    {{-- Site Name --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Site Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" placeholder="e.g. Headquarters / Downtown Office"
                               class="w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs">
                        @error('name') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Site Alias (Short Code) --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Site Alias / Code (Selected on Shifts/Tasks)
                        </label>
                        <input type="text" wire:model="site_alias" placeholder="e.g. HQ, DT-01, WFH"
                               class="w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs uppercase font-mono">
                        <span class="text-[10px] text-gray-400 mt-0.5 block">Short alias used in dropdowns (e.g., HQ - Headquarters).</span>
                        @error('site_alias') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Tracking Policy Options --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Tracking Policy <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="relative flex flex-col p-2.5 border rounded-lg cursor-pointer transition text-center {{ $tracking_policy === 'required' ? 'border-[#005a9c] bg-blue-50/70 ring-2 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="required" class="sr-only">
                                <span class="text-xs font-bold text-gray-900">Tracked</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Required</span>
                            </label>

                            <label class="relative flex flex-col p-2.5 border rounded-lg cursor-pointer transition text-center {{ $tracking_policy === 'optional' ? 'border-[#005a9c] bg-blue-50/70 ring-2 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="optional" class="sr-only">
                                <span class="text-xs font-bold text-gray-900">Tracked</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Optional</span>
                            </label>

                            <label class="relative flex flex-col p-2.5 border rounded-lg cursor-pointer transition text-center {{ $tracking_policy === 'disabled' ? 'border-[#005a9c] bg-blue-50/70 ring-2 ring-[#005a9c]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="disabled" class="sr-only">
                                <span class="text-xs font-bold text-gray-900">Untracked</span>
                                <span class="text-[10px] text-gray-500 mt-0.5">Disabled</span>
                            </label>
                        </div>
                        <div class="mt-1 text-[10px] text-gray-500">
                            Site display name will append:
                            <strong class="text-cs-blue">{{ $tracking_policy === 'disabled' ? '(Untracked)' : '(Tracked)' }}</strong>
                        </div>
                    </div>

                    {{-- Geofence Radius Slider & Input --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Geofence Radius: <span class="text-cs-blue font-mono" x-text="radius + 'm'"></span>
                            </label>
                            <span class="text-[10px] text-gray-400">Default: 200m</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="range" min="20" max="2000" step="10" x-model.number="radius" @input="updateRadiusFromInput()"
                                   class="w-full accent-[#005a9c] cursor-pointer">
                            <input type="number" wire:model="geofence_radius" x-model.number="radius" @input="updateRadiusFromInput()"
                                   min="10" max="10000" class="w-20 rounded-md border-gray-300 text-xs font-mono text-center">
                        </div>
                        @error('geofence_radius') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Coordinates (Lat/Lng) --}}
                    <div class="grid grid-cols-2 gap-3 bg-gray-50 p-2.5 rounded-lg border border-gray-200">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600">Latitude</label>
                            <input type="text" wire:model="latitude" x-model="lat" @input="onManualCoordChange()"
                                   placeholder="Click map or pin GPS" class="w-full rounded border-gray-300 text-xs font-mono bg-white">
                            @error('latitude') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600">Longitude</label>
                            <input type="text" wire:model="longitude" x-model="lng" @input="onManualCoordChange()"
                                   placeholder="Click map or pin GPS" class="w-full rounded border-gray-300 text-xs font-mono bg-white">
                            @error('longitude') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Address / Description --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Address or Physical Notes
                        </label>
                        <textarea wire:model="address" rows="2" placeholder="Street address, building number, entrance details..."
                                  class="w-full rounded-md border-gray-300 shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c] text-xs"></textarea>
                        @error('address') <span class="text-red-500 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between pt-2">
                        @if($selectedPresetId)
                            <button type="button" wire:click="resetForm" class="text-xs text-gray-500 hover:text-gray-800 font-semibold underline">
                                Cancel Edit
                            </button>
                        @else
                            <button type="button" @click="clearMap()" class="text-xs text-gray-500 hover:text-red-600 font-semibold">
                                Clear Map
                            </button>
                        @endif

                        <button type="submit" class="px-5 py-2 bg-cs-blue hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-xs transition">
                            {{ $selectedPresetId ? 'Update Preset' : 'Save Location Preset' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- RIGHT: FULL INTERACTIVE LEAFLET MAP (7 cols) --}}
            <div class="lg:col-span-7 bg-white rounded-xl border border-gray-200 shadow-xs p-5 flex flex-col space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                        <span>📍</span> Interactive Geofence Map
                    </span>

                    {{-- Action buttons --}}
                    <div class="flex items-center gap-2">
                        <button type="button" @click="pinCurrentGPS()" :disabled="locating"
                                class="px-2.5 py-1 bg-blue-50 border border-blue-200 text-cs-blue hover:bg-blue-100 rounded text-xs font-semibold shadow-2xs transition flex items-center gap-1">
                            <span>🎯</span>
                            <span x-text="locating ? 'Locating...' : 'Pin My Current GPS'"></span>
                        </button>

                        <button type="button" @click="clearMap()"
                                class="px-2 py-1 text-xs text-gray-500 hover:text-red-600 font-semibold">
                            Reset Pin
                        </button>
                    </div>
                </div>

                {{-- Search Address Bar --}}
                <div class="flex gap-2">
                    <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchLocation()"
                           placeholder="Search address, landmark, or city (press Enter)..."
                           class="flex-1 rounded-md border-gray-300 text-xs shadow-xs focus:border-[#005a9c] focus:ring-[#005a9c]">
                    <button type="button" @click="searchLocation()" :disabled="searching"
                            class="px-4 py-1.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-md text-xs font-bold shadow-2xs transition">
                        <span x-show="!searching">🔍 Search</span>
                        <span x-show="searching">Searching...</span>
                    </button>
                </div>

                <div x-show="statusMsg" x-text="statusMsg" class="text-[11px] text-blue-700 font-medium"></div>

                {{-- Map Container --}}
                <div wire:ignore class="rounded-xl overflow-hidden border border-gray-300 relative shadow-inner flex-1 min-h-[380px]">
                    <div x-ref="leafletMapContainer" style="height: 100%; min-height: 380px; width: 100%;" class="z-0"></div>
                    <div class="absolute bottom-2 left-3 z-10 bg-white/95 backdrop-blur px-2.5 py-1 rounded-md text-[11px] text-gray-700 font-medium shadow-xs border border-gray-200 pointer-events-none">
                        💡 Click anywhere to drop pin &bull; Drag pin to adjust &bull; Circle shows geofence radius
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTTOM: LOCATION PRESETS LIST --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-4 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-800">
                        Saved Location Presets ({{ $presets->count() }})
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Sites listed below will be available in dropdowns across shift and task creation.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-600 uppercase tracking-wide text-[10px]">
                            <th class="p-3">Site Alias</th>
                            <th class="p-3">Display Name (With Policy)</th>
                            <th class="p-3 text-center">Tracking Policy</th>
                            <th class="p-3">Coordinates (Lat, Lng)</th>
                            <th class="p-3 text-center">Radius</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($presets as $preset)
                            <tr class="hover:bg-blue-50/30 transition {{ $selectedPresetId === $preset->id ? 'bg-blue-50/60 font-semibold' : '' }}">
                                <td class="p-3 font-mono font-bold text-gray-900">
                                    {{ $preset->site_alias ?: '—' }}
                                </td>
                                <td class="p-3 font-medium text-gray-900">
                                    <div class="font-bold text-xs">{{ $preset->display_name }}</div>
                                    @if($preset->address)
                                        <div class="text-[10px] text-gray-400 truncate max-w-xs">{{ $preset->address }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($preset->tracking_policy === 'disabled')
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                            Untracked
                                        </span>
                                    @elseif($preset->tracking_policy === 'optional')
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Tracked (Optional)
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Tracked (Required)
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-[11px] text-gray-600">
                                    @if($preset->latitude && $preset->longitude)
                                        {{ number_format($preset->latitude, 5) }}, {{ number_format($preset->longitude, 5) }}
                                    @else
                                        <span class="text-gray-400 italic">No GPS set</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-mono text-[11px] text-gray-700">
                                    {{ $preset->geofence_radius ?? 200 }}m
                                </td>
                                <td class="p-3 text-right space-x-2">
                                    <button type="button" wire:click="selectForPreview({{ $preset->id }})"
                                            class="px-2.5 py-1 bg-white hover:bg-blue-50 border border-blue-200 text-cs-blue rounded text-[11px] font-bold transition">
                                        Locate
                                    </button>
                                    <button type="button" wire:click="editPreset({{ $preset->id }})"
                                            class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-[11px] font-bold transition">
                                        Edit
                                    </button>
                                    <button type="button"
                                            wire:click="deletePreset({{ $preset->id }})"
                                            wire:confirm="Are you sure you want to delete preset '{{ $preset->name }}'?"
                                            class="px-2 py-1 text-red-600 hover:bg-red-50 rounded text-[11px] font-bold transition">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-xs text-gray-400 italic">
                                    No location presets found. Create your first preset above by choosing on the map.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
function mapLocationsManager() {
    return {
        map: null,
        marker: null,
        circle: null,
        lat: @entangle('latitude'),
        lng: @entangle('longitude'),
        radius: @entangle('geofence_radius'),
        searchQuery: '',
        searching: false,
        locating: false,
        statusMsg: '',

        init() {
            this.$nextTick(() => {
                this.initMap();
            });
        },

        initMap() {
            const container = this.$refs.leafletMapContainer;
            if (!container || typeof L === 'undefined') return;

            if (this.map) {
                try { this.map.remove(); } catch (e) {}
                this.map = null;
            }
            if (container._leaflet_id) {
                delete container._leaflet_id;
            }
            container.innerHTML = '';

            // Default center: existing coordinates or Karachi default
            const initialLat = this.lat ? parseFloat(this.lat) : 24.8607;
            const initialLng = this.lng ? parseFloat(this.lng) : 67.0011;
            const initialZoom = this.lat ? 15 : 12;

            this.map = L.map(container, {
                attributionControl: false,
                zoomControl: true,
                dragging: true,
                scrollWheelZoom: true
            }).setView([initialLat, initialLng], initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);

            // Fix container sizing
            setTimeout(() => {
                if (this.map) this.map.invalidateSize();
            }, 200);
            setTimeout(() => {
                if (this.map) this.map.invalidateSize();
            }, 500);

            // Render existing marker if coordinates exist
            if (this.lat && this.lng) {
                this.placeMarkerAndCircle(initialLat, initialLng);
            }

            // Click listener
            this.map.on('click', (e) => {
                const clickedLat = e.latlng.lat;
                const clickedLng = e.latlng.lng;
                this.lat = clickedLat.toFixed(7);
                this.lng = clickedLng.toFixed(7);
                this.placeMarkerAndCircle(clickedLat, clickedLng);
                this.statusMsg = `Marker placed at ${this.lat}, ${this.lng}`;
            });
        },

        placeMarkerAndCircle(lat, lng) {
            if (!this.map) return;

            const icon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true, icon: icon }).addTo(this.map);
                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.lat = pos.lat.toFixed(7);
                    this.lng = pos.lng.toFixed(7);
                    if (this.circle) this.circle.setLatLng(pos);
                    this.statusMsg = `Moved to ${this.lat}, ${this.lng}`;
                });
            }

            const currentRadius = this.radius ? parseInt(this.radius) : 200;

            if (this.circle) {
                this.circle.setLatLng([lat, lng]);
                this.circle.setRadius(currentRadius);
            } else {
                this.circle = L.circle([lat, lng], {
                    radius: currentRadius,
                    color: '#005a9c',
                    fillColor: '#0284c7',
                    fillOpacity: 0.18,
                    weight: 2
                }).addTo(this.map);
            }
        },

        updateRadiusFromInput() {
            const r = parseInt(this.radius) || 200;
            if (this.circle) {
                this.circle.setRadius(r);
            }
        },

        onManualCoordChange() {
            const parsedLat = parseFloat(this.lat);
            const parsedLng = parseFloat(this.lng);
            if (!isNaN(parsedLat) && !isNaN(parsedLng)) {
                this.placeMarkerAndCircle(parsedLat, parsedLng);
                if (this.map) {
                    this.map.setView([parsedLat, parsedLng], 15);
                }
            }
        },

        loadPresetOnMap(detail) {
            if (!detail || !detail.lat || !detail.lng) return;
            const targetLat = parseFloat(detail.lat);
            const targetLng = parseFloat(detail.lng);
            const targetRadius = detail.radius ? parseInt(detail.radius) : 200;

            this.lat = targetLat.toFixed(7);
            this.lng = targetLng.toFixed(7);
            this.radius = targetRadius;

            this.$nextTick(() => {
                if (this.map) {
                    this.map.invalidateSize();
                    this.map.setView([targetLat, targetLng], 16);
                    this.placeMarkerAndCircle(targetLat, targetLng);
                    this.statusMsg = `Viewing preset: ${detail.name || ''}`;
                }
            });
        },

        clearMap() {
            if (this.marker && this.map) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
            if (this.circle && this.map) {
                this.map.removeLayer(this.circle);
                this.circle = null;
            }
            this.lat = null;
            this.lng = null;
            this.statusMsg = 'Map pin cleared.';
        },

        pinCurrentGPS() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            this.locating = true;
            this.statusMsg = 'Fetching GPS coordinates from device...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.locating = false;
                    const curLat = pos.coords.latitude;
                    const curLng = pos.coords.longitude;
                    this.lat = curLat.toFixed(7);
                    this.lng = curLng.toFixed(7);

                    if (!this.map) {
                        this.initMap();
                    }

                    if (this.map) {
                        this.placeMarkerAndCircle(curLat, curLng);
                        this.map.setView([curLat, curLng], 16);
                        setTimeout(() => {
                            if (this.map) this.map.invalidateSize();
                        }, 100);
                        setTimeout(() => {
                            if (this.map) this.map.invalidateSize();
                        }, 300);
                    }
                    this.statusMsg = `Pinned device GPS: ${this.lat}, ${this.lng}`;
                },
                (err) => {
                    this.locating = false;
                    this.statusMsg = 'Unable to get location: ' + err.message;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        searchLocation() {
            if (!this.searchQuery || this.searchQuery.trim().length === 0) return;
            this.searching = true;
            this.statusMsg = `Searching for "${this.searchQuery}"...`;

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}`)
                .then(r => r.json())
                .then(data => {
                    this.searching = false;
                    if (data && data.length > 0) {
                        const first = data[0];
                        const foundLat = parseFloat(first.lat);
                        const foundLng = parseFloat(first.lon);
                        this.lat = foundLat.toFixed(7);
                        this.lng = foundLng.toFixed(7);
                        if (this.map) {
                            this.map.setView([foundLat, foundLng], 15);
                            this.placeMarkerAndCircle(foundLat, foundLng);
                        }
                        this.statusMsg = `Found: ${first.display_name}`;
                    } else {
                        this.statusMsg = 'No locations found for your search.';
                    }
                })
                .catch(e => {
                    this.searching = false;
                    this.statusMsg = 'Search error: ' + e.message;
                });
        }
    };
}
</script>
