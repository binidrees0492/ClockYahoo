<div class="container mx-auto px-4 py-6" style="max-width: 1300px;">

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-200 flex items-center justify-between">
            <span class="font-medium text-sm">{{ session('message') }}</span>
            <button type="button" @click="show = false" class="text-green-700 hover:text-green-900 font-bold ml-3 leading-none text-base">&times;</button>
        </div>
    @endif

    @if(!$showForm)
        {{-- LIST VIEW --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <span>🏢</span> Locations &amp; Site Presets
                </h1>
                <p class="text-xs text-gray-500 mt-1">
                    Manage company job locations, geofences, and GPS tracking coordinates. Location presets are mandatory when assigning locations to jobs and shifts.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('map') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-2 rounded text-xs font-semibold transition border border-gray-300 flex items-center gap-1.5">
                    <span>🗺️</span> Full Geofence Map
                </a>
                <button wire:click="create" class="bg-[#005088] hover:bg-[#003e6b] text-white px-4 py-2 rounded text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                    <span>+</span> Add Location Preset
                </button>
            </div>
        </div>

        {{-- Filters & Search Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 bg-white p-4 rounded-lg shadow-xs border border-gray-200">
            <div class="flex items-center space-x-2 w-full max-w-md">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 text-xs">🔍</span>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search locations by name, alias or address..." class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-[#005088] focus:border-[#005088]" />
                </div>
                <button wire:click="clearFilters" class="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded text-xs hover:bg-gray-50">Clear</button>
            </div>
            @if(auth()->user()?->isSuperAdmin())
                <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer select-none bg-gray-50 border border-gray-300 px-3 py-1.5 rounded">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>🗑️ Include Deleted Records</span>
                </label>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white shadow-xs rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold text-gray-600 uppercase tracking-wider">Location / Preset</th>
                            <th class="px-5 py-3 text-left font-bold text-gray-600 uppercase tracking-wider">Coordinates & Geofence</th>
                            <th class="px-5 py-3 text-left font-bold text-gray-600 uppercase tracking-wider">Tracking Policy</th>
                            <th class="px-5 py-3 text-left font-bold text-gray-600 uppercase tracking-wider">Address / Notes</th>
                            <th class="px-5 py-3 text-left font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($locations as $location)
                            <tr class="hover:bg-blue-50/20 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-gray-900 text-sm flex items-center gap-1.5">
                                        <span>🏢</span> {{ $location->name }}
                                    </div>
                                    @if($location->site_alias)
                                        <div class="mt-0.5">
                                            <span class="inline-block px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded font-mono font-bold text-[10px]">
                                                Alias: {{ $location->site_alias }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($location->latitude && $location->longitude)
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-gray-800 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded text-[11px]">
                                                📍 {{ number_format($location->latitude, 5) }}, {{ number_format($location->longitude, 5) }}
                                            </span>
                                            <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}" target="_blank" title="Open in Google Maps" class="text-blue-600 hover:text-blue-800 text-xs">
                                                ↗
                                            </a>
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-0.5 font-medium">
                                            Radius: <strong class="text-gray-700">{{ $location->geofence_radius ?: 200 }} meters</strong>
                                        </div>
                                    @else
                                        <span class="text-red-500 font-semibold italic text-[11px]">⚠️ Missing coordinates (Edit to pin)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $policy = strtolower($location->tracking_policy ?? 'flag');
                                    @endphp
                                    @if($policy === 'required' || $policy === 'mandatory')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            🛡️ Mandatory GPS
                                        </span>
                                    @elseif($policy === 'disabled')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                            Untracked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            ⚠️ Flag Outside
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate" title="{{ $location->address }}">
                                    {{ $location->address ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ strtolower($location->status) === 'active' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ ucfirst($location->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if(strtolower($location->status) === 'active')
                                        <button wire:click="edit({{ $location->id }})" class="text-[#005088] hover:text-[#003e6b] font-bold text-xs bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded transition mr-1.5">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $location->id }})" wire:confirm="Are you sure you want to delete this location preset?" class="text-red-600 hover:text-red-900 font-bold text-xs bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded transition">
                                            Delete
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400 mr-2">Inactive</span>
                                        @if(auth()->user()?->isSuperAdmin())
                                            <button wire:click="restoreLocation({{ $location->id }})" class="text-emerald-700 hover:text-emerald-900 font-bold text-xs bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded transition mr-1.5">
                                                Restore
                                            </button>
                                        @endif
                                        @if(auth()->user()?->canHardDelete())
                                            <button wire:click="permanentlyDeleteLocation({{ $location->id }})" wire:confirm="Are you sure you want to permanently delete this location and unlink its child records?" class="text-red-700 hover:text-red-900 font-bold text-xs bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded transition">
                                                Delete Permanently
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="text-3xl mb-2">🏢</div>
                                    <div class="font-bold text-gray-700">No locations found.</div>
                                    <div class="text-xs text-gray-400 mt-1">Click "+ Add Location Preset" to add your first site with GPS coordinates.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $locations->links() }}
            </div>
        </div>
    @else
        {{-- CREATE / EDIT VIEW WITH INTERACTIVE MAP PICKER --}}
        <div class="mb-5 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <span>{{ $locationId ? '✏️ Edit Location Preset' : '📍 Add New Location Preset' }}</span>
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Location presets require mandatory GPS coordinates and a geofence radius. Click anywhere on the map or drag the pin to set the boundary.
                </p>
            </div>
            <button wire:click="cancel" class="text-[#005088] hover:underline text-xs font-bold flex items-center gap-1">
                &larr; Back to Locations List
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6"
             x-data="locationPresetMapPicker()"
             x-init="init()">

            {{-- LEFT COLUMN: FORM DETAILS (5 COLS) --}}
            <div class="lg:col-span-5 bg-white shadow-xs rounded-xl border border-gray-200 p-5 space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-800">Site Configuration</h2>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    {{-- Site Name --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Location / Site Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" placeholder="e.g. Headquarters, Downtown Branch, Job Site A"
                               class="w-full rounded border-gray-300 text-xs px-3 py-2 focus:border-[#005088] focus:ring-[#005088]">
                        @error('name') <span class="text-red-500 text-[11px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Site Alias --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Site Alias / Code (e.g. HQ, DT-01, WFH)
                        </label>
                        <input type="text" wire:model="site_alias" placeholder="Short code shown in dropdowns"
                               class="w-full rounded border-gray-300 text-xs px-3 py-2 uppercase font-mono focus:border-[#005088] focus:ring-[#005088]">
                        <span class="text-[10px] text-gray-400 mt-0.5 block">Used to quickly identify site on schedule board & time clock.</span>
                        @error('site_alias') <span class="text-red-500 text-[11px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Tracking Policy --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            GPS Tracking Policy <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <label class="relative flex flex-col p-2 border rounded-lg cursor-pointer transition text-center {{ in_array($tracking_policy, ['required', 'mandatory']) ? 'border-[#005088] bg-blue-50/70 ring-1 ring-[#005088]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="mandatory" class="sr-only">
                                <span class="font-bold text-gray-900 text-xs">Mandatory</span>
                                <span class="text-[10px] text-gray-500">Require GPS</span>
                            </label>

                            <label class="relative flex flex-col p-2 border rounded-lg cursor-pointer transition text-center {{ in_array($tracking_policy, ['optional', 'flag']) ? 'border-[#005088] bg-blue-50/70 ring-1 ring-[#005088]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="flag" class="sr-only">
                                <span class="font-bold text-gray-900 text-xs">Flag</span>
                                <span class="text-[10px] text-gray-500">Warn outside</span>
                            </label>

                            <label class="relative flex flex-col p-2 border rounded-lg cursor-pointer transition text-center {{ $tracking_policy === 'disabled' ? 'border-[#005088] bg-blue-50/70 ring-1 ring-[#005088]' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="tracking_policy" value="disabled" class="sr-only">
                                <span class="font-bold text-gray-900 text-xs">Untracked</span>
                                <span class="text-[10px] text-gray-500">Disabled</span>
                            </label>
                        </div>
                        @error('tracking_policy') <span class="text-red-500 text-[11px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Geofence Radius Slider & Number --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Geofence Radius: <span class="text-[#005088] font-mono font-bold" x-text="radius + 'm'"></span>
                            </label>
                            <span class="text-[10px] text-gray-400">Default: 200m</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="range" min="20" max="2000" step="10" x-model.number="radius" @input="updateRadiusCircle()"
                                   class="w-full accent-[#005088] cursor-pointer">
                            <input type="number" wire:model="geofence_radius" x-model.number="radius" @input="updateRadiusCircle()"
                                   min="10" max="10000" class="w-20 rounded border-gray-300 text-xs font-mono text-center py-1">
                        </div>
                        @error('geofence_radius') <span class="text-red-500 text-[11px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Mandatory Coordinates Inputs --}}
                    <div class="bg-blue-50/50 p-3 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-800 flex items-center gap-1">
                                <span>📍</span> GPS Coordinates <span class="text-red-500">*</span>
                            </span>
                            <span class="text-[10px] text-blue-700 font-bold bg-blue-100 px-1.5 py-0.2 rounded">Mandatory</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600">Latitude</label>
                                <input type="text" wire:model="latitude" x-model="lat" @input="onManualCoordChange()"
                                       placeholder="Latitude" class="w-full rounded border-gray-300 text-xs font-mono bg-white py-1.5">
                                @error('latitude') <span class="text-red-500 text-[10px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600">Longitude</label>
                                <input type="text" wire:model="longitude" x-model="lng" @input="onManualCoordChange()"
                                       placeholder="Longitude" class="w-full rounded border-gray-300 text-xs font-mono bg-white py-1.5">
                                @error('longitude') <span class="text-red-500 text-[10px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Address / Description --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Address or Location Notes
                        </label>
                        <textarea wire:model="address" rows="2" placeholder="Street address, building number, suite, gate codes..."
                                  class="w-full rounded border-gray-300 text-xs p-2 focus:border-[#005088] focus:ring-[#005088]"></textarea>
                        @error('address') <span class="text-red-500 text-[11px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Status</label>
                        <select wire:model="status" class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:border-[#005088] focus:ring-[#005088]">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex justify-end items-center gap-2 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="cancel" class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-xs font-medium hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#005088] hover:bg-[#003e6b] text-white px-5 py-2 rounded text-xs font-bold shadow-xs transition">
                            {{ $locationId ? 'Update Location Preset' : 'Save Location Preset' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- RIGHT COLUMN: INTERACTIVE LEAFLET MAP PICKER (7 COLS) --}}
            <div class="lg:col-span-7 bg-white shadow-xs rounded-xl border border-gray-200 p-5 flex flex-col space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-800 flex items-center gap-1.5">
                            <span>🗺️</span> Interactive Geofence Map Picker
                        </h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Click anywhere to move pin &bull; Drag pin to adjust location &bull; Circle represents geofence</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="pinCurrentGPS()" :disabled="locating"
                                class="px-2.5 py-1 bg-blue-50 border border-blue-200 text-[#005088] hover:bg-blue-100 rounded text-xs font-semibold shadow-2xs transition flex items-center gap-1">
                            <span>🎯</span>
                            <span x-text="locating ? 'Locating...' : 'Pin My Device GPS'"></span>
                        </button>
                        <button type="button" @click="resetToDefaultPin()" class="px-2 py-1 text-xs text-gray-500 hover:text-gray-800 font-medium">
                            Reset Pin
                        </button>
                    </div>
                </div>

                {{-- Search Address Bar --}}
                <div class="flex gap-2">
                    <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchAddress()"
                           placeholder="Search address, landmark, or city name..."
                           class="flex-1 rounded border-gray-300 text-xs px-3 py-1.5 focus:border-[#005088] focus:ring-[#005088]">
                    <button type="button" @click="searchAddress()" :disabled="searching"
                            class="px-4 py-1.5 bg-[#005088] hover:bg-[#003e6b] text-white rounded text-xs font-bold shadow-2xs transition">
                        <span x-show="!searching">🔍 Search</span>
                        <span x-show="searching">Searching...</span>
                    </button>
                </div>

                <div x-show="statusMsg" x-text="statusMsg" class="text-[11px] text-blue-700 font-medium"></div>

                {{-- Leaflet Map Container --}}
                <div wire:ignore class="rounded-xl overflow-hidden border border-gray-300 relative shadow-inner flex-1 min-h-[420px] bg-slate-100">
                    <div x-ref="locationMapContainer" style="height: 100%; min-height: 420px; width: 100%;" class="z-0"></div>
                    <div class="absolute bottom-2 left-3 z-10 bg-white/95 backdrop-blur px-2.5 py-1 rounded text-[11px] text-gray-700 font-medium shadow-xs border border-gray-200 pointer-events-none">
                        💡 Click anywhere to drop pin &bull; Drag pin to refine location
                    </div>
                </div>
            </div>
        </div>

        {{-- Alpine.js Map Manager --}}
        <script>
        function locationPresetMapPicker() {
            return {
                map: null,
                marker: null,
                circle: null,
                lat: @js((float)($latitude ?? 37.7749)),
                lng: @js((float)($longitude ?? -122.4194)),
                radius: @js((int)($geofence_radius ?? 200)),
                searchQuery: '',
                searching: false,
                locating: false,
                statusMsg: '',
                resizeObserver: null,

                init() {
                    this.$nextTick(() => {
                        setTimeout(() => { this.initMap(); }, 150);
                        setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 400);
                    });

                    this.$wire.$watch('latitude', (val) => {
                        let parsed = parseFloat(val);
                        if (!isNaN(parsed) && parsed !== parseFloat(this.lat)) {
                            this.lat = parsed;
                            this.syncMapVisuals();
                        }
                    });

                    this.$wire.$watch('longitude', (val) => {
                        let parsed = parseFloat(val);
                        if (!isNaN(parsed) && parsed !== parseFloat(this.lng)) {
                            this.lng = parsed;
                            this.syncMapVisuals();
                        }
                    });

                    this.$wire.$watch('geofence_radius', (val) => {
                        let parsed = parseInt(val);
                        if (!isNaN(parsed) && parsed !== parseInt(this.radius)) {
                            this.radius = parsed;
                            this.updateRadiusCircle();
                        }
                    });
                },

                getMarkerIcon() {
                    if (typeof L === 'undefined') return null;
                    return L.icon({
                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });
                },

                initMap() {
                    const container = this.$refs.locationMapContainer;
                    if (!container || typeof L === 'undefined') return;

                    if (this.map) {
                        try { this.map.remove(); } catch (e) {}
                        this.map = null;
                    }
                    if (container._leaflet_id) {
                        delete container._leaflet_id;
                    }
                    container.innerHTML = '';

                    const initLat = parseFloat(this.lat) || 37.7749;
                    const initLng = parseFloat(this.lng) || -122.4194;

                    this.map = L.map(container, {
                        attributionControl: false,
                        zoomControl: true,
                        dragging: true,
                        scrollWheelZoom: true
                    }).setView([initLat, initLng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.map);

                    this.placeMarkerAndCircle(initLat, initLng);

                    this.map.on('click', (e) => {
                        const clickedLat = parseFloat(e.latlng.lat.toFixed(7));
                        const clickedLng = parseFloat(e.latlng.lng.toFixed(7));
                        this.setCoordinates(clickedLat, clickedLng);
                        this.statusMsg = `Pin placed at ${clickedLat}, ${clickedLng}`;
                    });

                    if (window.ResizeObserver && !this.resizeObserver) {
                        this.resizeObserver = new ResizeObserver(() => {
                            if (this.map) this.map.invalidateSize();
                        });
                        this.resizeObserver.observe(container);
                    }

                    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 200);
                    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                },

                placeMarkerAndCircle(lat, lng) {
                    if (!this.map || typeof L === 'undefined') return;

                    const icon = this.getMarkerIcon();
                    const currentRadius = parseInt(this.radius) || 200;

                    if (this.marker) {
                        this.marker.setLatLng([lat, lng]);
                    } else {
                        this.marker = L.marker([lat, lng], { draggable: true, icon: icon }).addTo(this.map);
                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            const draggedLat = parseFloat(pos.lat.toFixed(7));
                            const draggedLng = parseFloat(pos.lng.toFixed(7));
                            this.setCoordinates(draggedLat, draggedLng);
                            this.statusMsg = `Pin moved to ${draggedLat}, ${draggedLng}`;
                        });
                    }

                    if (this.circle) {
                        this.circle.setLatLng([lat, lng]);
                        this.circle.setRadius(currentRadius);
                    } else {
                        this.circle = L.circle([lat, lng], {
                            radius: currentRadius,
                            color: '#005088',
                            fillColor: '#0284c7',
                            fillOpacity: 0.18,
                            weight: 2
                        }).addTo(this.map);
                    }
                },

                setCoordinates(lat, lng) {
                    this.lat = lat;
                    this.lng = lng;
                    this.$wire.set('latitude', lat);
                    this.$wire.set('longitude', lng);
                    this.placeMarkerAndCircle(lat, lng);
                },

                syncMapVisuals() {
                    const lat = parseFloat(this.lat);
                    const lng = parseFloat(this.lng);
                    if (!isNaN(lat) && !isNaN(lng) && this.map) {
                        this.placeMarkerAndCircle(lat, lng);
                        this.map.setView([lat, lng], 15);
                    }
                },

                updateRadiusCircle() {
                    const r = parseInt(this.radius) || 200;
                    this.$wire.set('geofence_radius', r);
                    if (this.circle) {
                        this.circle.setRadius(r);
                    }
                },

                onManualCoordChange() {
                    const parsedLat = parseFloat(this.lat);
                    const parsedLng = parseFloat(this.lng);
                    if (!isNaN(parsedLat) && !isNaN(parsedLng) && (parsedLat >= -90 && parsedLat <= 90) && (parsedLng >= -180 && parsedLng <= 180)) {
                        this.$wire.set('latitude', parsedLat);
                        this.$wire.set('longitude', parsedLng);
                        if (this.map) {
                            this.placeMarkerAndCircle(parsedLat, parsedLng);
                            this.map.setView([parsedLat, parsedLng], 15);
                        }
                    }
                },

                resetToDefaultPin() {
                    const defLat = 37.7749;
                    const defLng = -122.4194;
                    this.setCoordinates(defLat, defLng);
                    if (this.map) {
                        this.map.setView([defLat, defLng], 14);
                    }
                    this.statusMsg = 'Reset to default location.';
                },

                pinCurrentGPS() {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by your browser.');
                        return;
                    }
                    this.locating = true;
                    this.statusMsg = 'Detecting device GPS position...';

                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.locating = false;
                            const curLat = parseFloat(pos.coords.latitude.toFixed(7));
                            const curLng = parseFloat(pos.coords.longitude.toFixed(7));
                            this.setCoordinates(curLat, curLng);
                            if (this.map) {
                                this.map.setView([curLat, curLng], 16);
                                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
                            }
                            this.statusMsg = `Device location pinned: ${curLat}, ${curLng}`;
                        },
                        (err) => {
                            this.locating = false;
                            this.statusMsg = 'Unable to get device location: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                searchAddress() {
                    if (!this.searchQuery || this.searchQuery.trim().length === 0) return;
                    this.searching = true;
                    this.statusMsg = `Searching for "${this.searchQuery}"...`;

                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}`)
                        .then(r => r.json())
                        .then(data => {
                            this.searching = false;
                            if (data && data.length > 0) {
                                const first = data[0];
                                const foundLat = parseFloat(parseFloat(first.lat).toFixed(7));
                                const foundLng = parseFloat(parseFloat(first.lon).toFixed(7));
                                this.setCoordinates(foundLat, foundLng);
                                if (!this.$wire.address) {
                                    this.$wire.set('address', first.display_name);
                                }
                                if (this.map) {
                                    this.map.setView([foundLat, foundLng], 15);
                                    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
                                }
                                this.statusMsg = `Found: ${first.display_name}`;
                            } else {
                                this.statusMsg = 'No locations found for your search query.';
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
    @endif
</div>
