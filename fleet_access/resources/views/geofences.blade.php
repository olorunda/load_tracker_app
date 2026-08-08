<x-layout title="Geofence Management - FleetManager">
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-64px)] relative overflow-hidden" x-data="geofenceApp()">
        <!-- Geofences Sidebar Panel -->
        <aside class="w-full lg:w-[400px] bg-surface-container-lowest border-r border-outline-variant flex flex-col shadow-xs shrink-0 h-1/2 lg:h-full z-10">
            <div class="p-md border-b border-outline-variant bg-surface-container-lowest sticky top-0 z-20">
                <div class="flex justify-between items-center mb-xs">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Active Geofences</h2>
                    <div class="flex gap-2">
                        <button @click="triggerEvaluation()" class="px-2.5 py-1.5 bg-surface-container-high text-on-surface border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">sensors</span> Evaluate
                        </button>
                        <button @click="showCreateModal = true" class="px-3 py-1.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">add</span> New Zone
                        </button>
                    </div>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-md">Manage perimeter boundaries, dwell rules, and speed caps.</p>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchGeofences()" placeholder="Search geofences..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow"/>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-outline-variant/40 bg-surface-bright">
                <template x-for="g in geofences" :key="g.id">
                    <div @click="selectGeofence(g)" 
                         :class="selectedGeofence && selectedGeofence.id === g.id ? 'bg-surface-container-low border-l-4 border-l-primary' : 'bg-surface-container-lowest hover:bg-surface-container-low'"
                         class="p-md cursor-pointer transition-colors">
                        <div class="flex justify-between items-start mb-xs">
                            <div>
                                <span class="font-headline-sm text-headline-sm text-on-surface font-semibold" x-text="g.name"></span>
                                <span class="font-body-sm text-body-sm text-on-surface-variant block" x-text="g.type"></span>
                            </div>
                            <span :class="g.active ? 'bg-[#e6f4ea] text-[#137333]' : 'bg-surface-variant text-on-surface-variant'" 
                                  class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase tracking-wider font-bold"
                                  x-text="g.active ? 'Active' : 'Disabled'"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-sm text-body-sm text-on-surface-variant mt-sm">
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">directions_bus</span>
                                <span><strong class="text-on-surface" x-text="g.vehicles"></strong> Assets inside</span>
                            </div>
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-[#b45309]">speed</span>
                                <span>Speed cap: <strong class="text-on-surface" x-text="g.maxSpeed"></strong></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </aside>

        <!-- Map View for Geofences -->
        <div class="flex-1 relative h-1/2 lg:h-full bg-surface-container-low overflow-hidden flex flex-col">
            <div id="geofence-map" class="w-full h-full"></div>

            <!-- Floating Overlay Card for Selected Geofence -->
            <div x-show="selectedGeofence" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute bottom-4 left-4 right-4 lg:left-auto lg:right-4 lg:w-96 bg-surface-container-lowest/95 backdrop-blur-md p-md rounded-xl border border-outline-variant shadow-lg z-[1000]"
                 style="display: none;">
                <div class="flex justify-between items-start border-b border-outline-variant pb-xs mb-sm">
                    <div>
                        <span class="font-label-md text-label-md text-primary font-bold uppercase tracking-wider" x-text="selectedGeofence?.type"></span>
                        <h3 class="font-headline-md text-headline-md text-on-surface" x-text="selectedGeofence?.name"></h3>
                    </div>
                    <button @click="selectedGeofence = null" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="space-y-xs text-body-sm text-on-surface-variant mb-md">
                    <p>Perimeter Radius: <strong class="text-on-surface" x-text="selectedGeofence?.radius"></strong></p>
                    <p>Allowed Speed Cap: <strong class="text-on-surface" x-text="selectedGeofence?.maxSpeed"></strong></p>
                    <p>Current Occupancy: <strong class="text-on-surface" x-text="selectedGeofence?.vehicles + ' Vehicles'"></strong></p>
                </div>

                <div class="flex gap-sm">
                    <button @click="toggleGeofence(selectedGeofence)" 
                            :class="selectedGeofence?.active ? 'bg-surface-container-high text-on-surface border border-outline-variant' : 'bg-primary text-on-primary'"
                            class="flex-1 py-2 rounded-lg font-label-md text-label-md transition-colors">
                        <span x-text="selectedGeofence?.active ? 'Disable Geofence' : 'Enable Geofence'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Geofence Modal -->
        <div x-show="showCreateModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[2000] bg-on-surface/40 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none; background-color: rgba(17, 28, 45, 0.5); backdrop-filter: blur(4px);">
            <div class="bg-surface-container-lowest w-full max-w-lg rounded-xl border border-outline-variant shadow-2xl p-6 space-y-5"
                 style="width: 100%; max-width: 540px; min-width: 300px; box-sizing: border-box; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 16px; padding: 24px;">
                <div class="flex justify-between items-center border-b border-outline-variant pb-3">
                    <h3 class="font-headline-md text-headline-md text-on-surface font-bold text-lg">Draw New Geofence Perimeter</h3>
                    <button @click="showCreateModal = false" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Geofence Name</label>
                        <input type="text" x-model="newForm.name" placeholder="e.g. Lagos Lekki Toll Logistics Depot" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Latitude</label>
                            <input type="number" step="0.0000001" x-model="newForm.latitude" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Longitude</label>
                            <input type="number" step="0.0000001" x-model="newForm.longitude" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Boundary Type</label>
                        <select x-model="newForm.type" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                            <option value="Depot Yard">Depot Yard</option>
                            <option value="Port Terminal">Port Terminal</option>
                            <option value="Distribution Center">Distribution Center</option>
                            <option value="Highway Corridor">Highway Corridor</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Radius (Meters)</label>
                            <input type="number" x-model="newForm.radius_meters" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Speed Limit (km/h)</label>
                            <input type="number" x-model="newForm.max_speed_kmh" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none"/>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-outline-variant pt-4">
                    <button @click="showCreateModal = false" class="px-4 py-2 border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-low font-semibold">Cancel</button>
                    <button @click="createGeofence()" class="px-5 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 font-bold">Save Perimeter</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function geofenceApp() {
            return {
                searchQuery: '',
                selectedGeofence: null,
                showCreateModal: false,
                map: null,
                geofenceOverlays: [],
                geofences: [],
                newForm: {
                    name: 'Lagos Lekki Toll Logistics Hub',
                    type: 'Depot Yard',
                    latitude: 6.4300,
                    longitude: 3.4200,
                    radius_meters: 2500,
                    max_speed_kmh: 30
                },

                async fetchGeofences() {
                    try {
                        const url = new URL('/api/geofences', window.location.origin);
                        if (this.searchQuery) url.searchParams.append('q', this.searchQuery);

                        const res = await fetch(url);
                        const json = await res.json();
                        if (json.success) {
                            this.geofences = json.geofences;
                            this.renderGeofenceMapOverlays();
                            if (!this.selectedGeofence && this.geofences.length > 0) {
                                this.selectGeofence(this.geofences[0]);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch geofences', e);
                    }
                },

                async createGeofence() {
                    try {
                        const res = await fetch('/api/geofences', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showCreateModal = false;
                            this.fetchGeofences();
                        }
                    } catch (e) {
                        console.error('Failed to create geofence', e);
                    }
                },

                async toggleGeofence(g) {
                    if (!g) return;
                    try {
                        const res = await fetch(`/api/geofences/${g.id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            g.active = json.geofence.is_active;
                            this.renderGeofenceMapOverlays();
                        }
                    } catch (e) {
                        console.error('Failed to toggle geofence', e);
                    }
                },

                async triggerEvaluation() {
                    try {
                        const res = await fetch('/api/geofences/evaluate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            alert(`Geofence Alert Evaluation Complete! Active Alerts: ${json.active_alerts_count}`);
                            this.fetchGeofences();
                        }
                    } catch (e) {
                        console.error('Failed to evaluate alerts', e);
                    }
                },

                selectGeofence(g) {
                    this.selectedGeofence = g;
                    if (this.map && g.lat && g.lng) {
                        this.map.flyTo([g.lat, g.lng], 13, { duration: 1.2 });
                    }
                },

                renderGeofenceMapOverlays() {
                    if (!this.map || typeof L === 'undefined') return;

                    // Clear previous overlays
                    this.geofenceOverlays.forEach(o => this.map.removeLayer(o));
                    this.geofenceOverlays = [];

                    this.geofences.forEach(g => {
                        if (!g.lat || !g.lng) return;

                        const color = g.active ? '#004ac6' : '#737686';

                        // Draw Circle Perimeter
                        const circle = L.circle([g.lat, g.lng], {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.15,
                            radius: g.radius_meters || 2500,
                            weight: 2
                        }).addTo(this.map);

                        circle.bindPopup(`<strong>${g.name}</strong><br>Type: ${g.type}<br>Radius: ${g.radius}<br>Speed Cap: ${g.maxSpeed}<br>Vehicles Inside: ${g.vehicles}`);
                        this.geofenceOverlays.push(circle);
                    });
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof L === 'undefined') return;

                        // Center on Lagos Teltonika GPS hub
                        this.map = L.map('geofence-map', { zoomControl: false }).setView([6.5802, 3.2932], 12);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap &copy; CARTO'
                        }).addTo(this.map);

                        L.control.zoom({ position: 'topright' }).addTo(this.map);

                        this.fetchGeofences();
                    });
                }
            }
        }
    </script>
</x-layout>
