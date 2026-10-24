<x-layout title="Trips & Load Events - FleetManager">
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-64px)] relative overflow-hidden" x-data="tripsApp()">
        <!-- Trips Sidebar Panel -->
        <aside class="w-full lg:w-[420px] bg-surface-container-lowest border-r border-outline-variant flex flex-col shadow-xs shrink-0 h-1/2 lg:h-full z-10">
            <div class="p-md border-b border-outline-variant bg-surface-container-lowest sticky top-0 z-20">
                <div class="flex justify-between items-center mb-xs">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Trips &amp; Load Events</h2>
                    <button @click="fetchTrips()" class="px-2.5 py-1.5 bg-surface-container-high text-on-surface border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]" :class="isFetching ? 'animate-spin' : ''">sync</span> Refresh
                    </button>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-md">Trips are detected from IO 66 load cycles: loaded at one stop, unloaded at another.</p>

                <!-- KPI Strip -->
                <div class="grid grid-cols-3 gap-sm mb-md">
                    <div class="p-sm rounded-lg bg-surface-container-low border border-outline-variant/60">
                        <div class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">Billable</div>
                        <div class="font-data-mono font-bold text-on-surface text-headline-sm" x-text="summary.billable_trips ?? 0"></div>
                        <div class="text-[10px] text-on-surface-variant" x-text="(summary.billable_distance_km ?? 0) + ' km'"></div>
                    </div>
                    <div class="p-sm rounded-lg bg-surface-container-low border border-outline-variant/60">
                        <div class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">In Progress</div>
                        <div class="font-data-mono font-bold text-primary text-headline-sm" x-text="summary.open_trips ?? 0"></div>
                        <div class="text-[10px] text-on-surface-variant" x-text="(summary.cancelled_trips ?? 0) + ' cancelled'"></div>
                    </div>
                    <div class="p-sm rounded-lg border" :class="summary.confirmed_unauthorized > 0 ? 'bg-error/5 border-error/40' : 'bg-surface-container-low border-outline-variant/60'">
                        <div class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">Unauthorized</div>
                        <div class="font-data-mono font-bold text-headline-sm" :class="summary.confirmed_unauthorized > 0 ? 'text-error' : 'text-on-surface'" x-text="summary.confirmed_unauthorized ?? 0"></div>
                        <div class="text-[10px] text-on-surface-variant" x-text="(summary.suspected_unauthorized ?? 0) + ' suspected'"></div>
                    </div>
                </div>

                <!-- Status Filter Chips -->
                <div class="flex flex-wrap gap-xs mb-sm">
                    <template x-for="f in filters" :key="f.value">
                        <button @click="setFilter(f.value)"
                                :class="activeFilter === f.value ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:bg-surface-container-low'"
                                class="px-2.5 py-1 rounded-full border font-label-md text-[11px] font-semibold transition-colors"
                                x-text="f.label"></button>
                    </template>
                </div>

                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchTrips()" placeholder="Search vehicle, IMEI, driver..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow"/>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-outline-variant/40 bg-surface-bright">
                <template x-for="t in trips" :key="t.id">
                    <div @click="selectTrip(t)"
                         :class="selectedTrip && selectedTrip.id === t.id ? 'bg-surface-container-low border-l-4 border-l-primary' : 'bg-surface-container-lowest hover:bg-surface-container-low'"
                         class="p-md cursor-pointer transition-colors">
                        <div class="flex justify-between items-start mb-xs">
                            <div>
                                <span class="font-headline-sm text-headline-sm text-on-surface font-semibold" x-text="t.vehicle_code + ' · ' + t.reference"></span>
                                <span class="font-body-sm text-body-sm text-on-surface-variant block" x-text="t.vehicle_name + ' — ' + t.driver"></span>
                            </div>
                            <span :class="statusBadge(t.status)"
                                  class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase tracking-wider font-bold shrink-0"
                                  x-text="statusLabel(t.status)"></span>
                        </div>

                        <div class="grid grid-cols-3 gap-sm text-body-sm text-on-surface-variant mt-sm">
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">straighten</span>
                                <strong class="text-on-surface" x-text="t.distance_km + ' km'"></strong>
                            </div>
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">schedule</span>
                                <strong class="text-on-surface" x-text="t.duration"></strong>
                            </div>
                            <div class="flex items-center gap-xs text-[11px]" x-text="t.loaded_at"></div>
                        </div>

                        <div x-show="t.unauthorized" class="mt-sm flex items-center gap-xs text-[11px] font-bold"
                             :class="t.unauthorized === 'confirmed' ? 'text-error' : 'text-[#b45309]'">
                            <span class="material-symbols-outlined text-[16px]">report</span>
                            <span x-text="t.unauthorized === 'confirmed' ? (t.offload_located ? 'Unauthorized loading confirmed · offload located' : 'Unauthorized loading confirmed') : 'Suspected unauthorized loading'"></span>
                        </div>
                    </div>
                </template>

                <div x-show="!isFetching && trips.length === 0" class="p-lg text-center text-body-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-[32px] block mb-xs text-outline">route</span>
                    No trips detected yet. Trips appear once a calibrated vehicle is loaded, drives 5 km, and is unloaded.
                </div>
            </div>
        </aside>

        <!-- Trip Map -->
        <div class="flex-1 relative h-1/2 lg:h-full bg-surface-container-low overflow-hidden flex flex-col">
            <div id="trips-map" class="w-full h-full"></div>

            <!-- Map Legend -->
            <div class="absolute top-4 left-4 bg-surface-container-lowest/95 backdrop-blur-md p-sm rounded-lg border border-outline-variant shadow-xs z-[1000] text-[11px] space-y-1">
                <template x-for="l in legend" :key="l.label">
                    <div class="flex items-center gap-xs">
                        <span class="w-3 h-3 rounded-full border-2 border-white shadow-xs" :style="'background-color:' + l.color"></span>
                        <span class="text-on-surface-variant" x-text="l.label"></span>
                    </div>
                </template>
            </div>

            <!-- Floating Overlay Card for Selected Trip -->
            <div x-show="selectedTrip"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute bottom-4 left-4 right-4 lg:left-auto lg:right-4 lg:w-[400px] max-h-[70%] overflow-y-auto bg-surface-container-lowest/95 backdrop-blur-md p-md rounded-xl border border-outline-variant shadow-lg z-[1000]"
                 style="display: none;">
                <div class="flex justify-between items-start border-b border-outline-variant pb-xs mb-sm">
                    <div>
                        <span class="font-label-md text-label-md text-primary font-bold uppercase tracking-wider" x-text="selectedTrip?.reference"></span>
                        <h3 class="font-headline-md text-headline-md text-on-surface" x-text="selectedTrip?.vehicle_name"></h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Driver: <strong class="text-on-surface" x-text="selectedTrip?.driver"></strong></p>
                    </div>
                    <button @click="clearSelection()" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-x-md gap-y-xs text-body-sm text-on-surface-variant mb-sm">
                    <p>Status: <strong class="text-on-surface" x-text="statusLabel(selectedTrip?.status)"></strong></p>
                    <p>Billable: <strong class="text-on-surface" x-text="selectedTrip?.is_billable ? 'Yes' : 'No'"></strong></p>
                    <p>Distance: <strong class="text-on-surface" x-text="selectedTrip?.distance_km + ' km'"></strong></p>
                    <p>Duration: <strong class="text-on-surface" x-text="selectedTrip?.duration"></strong></p>
                    <p>Loaded: <strong class="text-on-surface" x-text="selectedTrip?.loaded_at"></strong></p>
                    <p>Unloaded: <strong class="text-on-surface" x-text="selectedTrip?.unloaded_at || '—'"></strong></p>
                    <p class="col-span-2">Trip load baseline: <strong class="text-on-surface font-data-mono" x-text="selectedTrip?.baseline_load"></strong></p>
                    <p class="col-span-2 text-error" x-show="selectedTrip?.cancel_reason" x-text="'Cancelled: ' + selectedTrip?.cancel_reason"></p>
                </div>

                <div class="border-t border-outline-variant pt-sm">
                    <div class="flex items-center justify-between mb-xs">
                        <span class="font-label-md text-label-md text-on-surface font-bold uppercase text-[11px] tracking-wider">Load Event Timeline</span>
                        <span x-show="detailLoading" class="text-primary font-bold animate-pulse text-[10px]">Loading...</span>
                    </div>
                    <ol class="space-y-sm">
                        <template x-for="e in (selectedTrip?.events || [])" :key="e.id">
                            <li @click="focusEvent(e)" class="flex gap-sm cursor-pointer rounded-lg p-xs hover:bg-surface-container-low">
                                <span class="material-symbols-outlined text-[18px] shrink-0" :style="'color:' + e.color" x-text="e.icon"></span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-xs flex-wrap">
                                        <span class="font-bold text-on-surface text-body-sm" x-text="e.label"></span>
                                        <span class="font-data-mono text-[10px] px-1.5 rounded bg-surface-container-high text-on-surface-variant" x-show="e.load_delta" x-text="e.load_delta"></span>
                                    </div>
                                    <div class="text-[11px] text-on-surface-variant" x-text="e.occurred_at + ' · ' + e.load_value"></div>
                                    <div class="text-[11px] text-on-surface-variant italic" x-text="e.description"></div>
                                </div>
                            </li>
                        </template>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script>
        function tripsApp() {
            return {
                searchQuery: '',
                activeFilter: 'all',
                filters: [
                    { value: 'all', label: 'All' },
                    { value: 'open', label: 'In Progress' },
                    { value: 'completed', label: 'Completed' },
                    { value: 'flagged', label: 'Unauthorized' },
                    { value: 'cancelled', label: 'Cancelled' },
                ],
                legend: [
                    { label: 'Loading point', color: '#059669' },
                    { label: 'Unloading point', color: '#004ac6' },
                    { label: 'Suspected loading', color: '#b45309' },
                    { label: 'Confirmed unauthorized', color: '#ba1a1a' },
                    { label: 'Unauthorized offload', color: '#7c3aed' },
                ],
                trips: [],
                summary: {},
                selectedTrip: null,
                isFetching: false,
                detailLoading: false,
                map: null,
                overlays: [],

                async fetchTrips() {
                    this.isFetching = true;
                    try {
                        const url = new URL('/api/trips', window.location.origin);
                        if (this.searchQuery) url.searchParams.append('q', this.searchQuery);
                        if (this.activeFilter !== 'all') url.searchParams.append('status', this.activeFilter);

                        const res = await fetch(url);
                        const json = await res.json();
                        if (json.success) {
                            this.trips = json.trips;
                            this.summary = json.summary;
                            if (!this.selectedTrip && this.trips.length > 0) {
                                this.selectTrip(this.trips[0]);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch trips', e);
                    } finally {
                        this.isFetching = false;
                    }
                },

                setFilter(filter) {
                    this.activeFilter = filter;
                    this.fetchTrips();
                },

                async selectTrip(t) {
                    this.selectedTrip = { ...t, events: [] };
                    this.detailLoading = true;
                    try {
                        const res = await fetch(`/api/trips/${t.id}`);
                        const json = await res.json();
                        if (json.success && this.selectedTrip?.id === t.id) {
                            this.selectedTrip = json.trip;
                            this.renderTripOverlays();
                        }
                    } catch (e) {
                        console.error('Failed to load trip detail', e);
                    } finally {
                        this.detailLoading = false;
                    }
                },

                clearSelection() {
                    this.selectedTrip = null;
                    this.clearOverlays();
                },

                focusEvent(e) {
                    if (this.map && e.lat && e.lng) {
                        this.map.flyTo([e.lat, e.lng], 16, { duration: 1 });
                    }
                },

                statusLabel(status) {
                    return { pending: 'Confirming', active: 'In Transit', closing: 'Unloading', completed: 'Completed', cancelled: 'Cancelled' }[status] || status;
                },

                statusBadge(status) {
                    return {
                        pending: 'bg-[#fef3c7] text-[#b45309]',
                        active: 'bg-primary/10 text-primary',
                        closing: 'bg-primary/10 text-primary',
                        completed: 'bg-[#e6f4ea] text-[#137333]',
                        cancelled: 'bg-surface-variant text-on-surface-variant',
                    }[status] || 'bg-surface-variant text-on-surface-variant';
                },

                escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value ?? '';
                    return div.innerHTML;
                },

                clearOverlays() {
                    if (!this.map) return;
                    this.overlays.forEach(o => this.map.removeLayer(o));
                    this.overlays = [];
                },

                renderTripOverlays() {
                    if (!this.map || typeof L === 'undefined' || !this.selectedTrip) return;
                    this.clearOverlays();

                    const coords = (this.selectedTrip.path || []).map(p => [p.lat, p.lng]);
                    if (coords.length > 0) {
                        const route = L.polyline(coords, {
                            color: this.selectedTrip.unauthorized === 'confirmed' ? '#ba1a1a' : '#004ac6',
                            weight: 4,
                            opacity: 0.7,
                            lineJoin: 'round'
                        }).addTo(this.map);
                        this.overlays.push(route);
                    }

                    (this.selectedTrip.events || []).forEach(e => {
                        if (!e.lat || !e.lng) return;

                        const size = e.severity === 'critical' ? 24 : 20;
                        const icon = L.divIcon({
                            html: `<div style="background-color: ${e.color}; width: ${size}px; height: ${size}px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.35);"></div>`,
                            className: 'custom-load-event-marker',
                            iconSize: [size, size],
                            iconAnchor: [size / 2, size / 2]
                        });

                        const marker = L.marker([e.lat, e.lng], { icon })
                            .addTo(this.map)
                            .bindPopup(`<strong>${this.escapeHtml(e.label)}</strong><br>${this.escapeHtml(e.occurred_at)}<br>Load: ${this.escapeHtml(e.load_value)} ${e.load_delta ? '(' + this.escapeHtml(e.load_delta) + ')' : ''}<br><em>${this.escapeHtml(e.description)}</em>`);
                        this.overlays.push(marker);
                    });

                    const bounds = L.latLngBounds([
                        ...coords,
                        ...(this.selectedTrip.events || []).filter(e => e.lat && e.lng).map(e => [e.lat, e.lng]),
                    ]);
                    if (bounds.isValid()) {
                        this.map.fitBounds(bounds, { padding: [60, 60] });
                    }
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof L === 'undefined') return;

                        this.map = L.map('trips-map', { zoomControl: false }).setView([6.5802, 3.2932], 12);

                        window.addBaseLayers(this.map, { position: 'topright' });

                        L.control.zoom({ position: 'topright' }).addTo(this.map);

                        this.fetchTrips();
                    });
                }
            }
        }
    </script>
</x-layout>
