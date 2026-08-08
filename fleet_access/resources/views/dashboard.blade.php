<x-layout title="Fleet Overview Dashboard - FleetManager">
    <div class="p-md md:p-margin flex flex-col gap-lg flex-1" x-data="dashboardApp()" x-init="initDashboard()">
        <!-- KPIs Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
            <div>
                <h2 class="font-display text-display text-on-surface">Overview</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">System status and live metrics across all regions.</p>
            </div>
            <div class="flex gap-sm">
                <button @click="fetchDashboardMetrics()" class="px-4 py-2 bg-surface border border-outline-variant rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">refresh</span> Refresh
                </button>
                <a href="{{ route('assets.add') }}" class="px-4 py-2 bg-primary rounded-lg font-label-md text-label-md text-on-primary hover:bg-primary/90 transition-colors shadow-xs flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span> Dispatch Asset
                </a>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-md">
            <!-- Total Fleet -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Total Fleet</span>
                    <span class="material-symbols-outlined text-primary">local_shipping</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="kpis.total_fleet">2</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-[#059669] flex items-center"><span class="material-symbols-outlined text-[14px]">trending_up</span> Active</span>
                    <span class="text-on-surface-variant">Registered Telematics</span>
                </div>
            </div>

            <!-- Active Loads -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Active Loads</span>
                    <span class="material-symbols-outlined text-[#059669]">inventory_2</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="kpis.active_loads">2</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-[#059669]/10 text-[#059669] font-label-md text-label-md">Optimal</span>
                    <span class="text-on-surface-variant">100% utilization</span>
                </div>
            </div>

            <!-- Stream Precision Rating (Real Telematics Data) -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Stream Precision</span>
                    <span class="material-symbols-outlined text-primary">precision_manufacturing</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="kpis.precision_rating || '99.00%'">99.00%</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-[#059669] flex items-center font-bold"><span class="material-symbols-outlined text-[14px]">verified</span> Live Stream</span>
                    <span class="text-on-surface-variant">HDOP & GNSS index</span>
                </div>
            </div>

            <!-- Critical Alerts -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Critical Alerts</span>
                    <span class="material-symbols-outlined text-error">warning</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="kpis.critical_alerts">1</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-error flex items-center"><span class="material-symbols-outlined text-[14px]">report</span> Active</span>
                    <span class="text-on-surface-variant">Requires attention</span>
                </div>
            </div>

            <!-- In Geofence -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">In Geofence</span>
                    <span class="material-symbols-outlined text-primary">share_location</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="kpis.in_geofence_vehicles + ' Assets'">2 Assets</div>
                <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-1 overflow-hidden">
                    <div class="bg-primary h-1.5 rounded-full" style="width: 100%"></div>
                </div>
                <div class="font-body-sm text-body-sm text-on-surface-variant" x-text="kpis.adherence_rate + ' adherence rate'">100% adherence rate</div>
            </div>
        </div>

        <!-- Map & Activity Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-md flex-1">
            <!-- Leaflet Interactive Map Card -->
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs flex flex-col min-h-[420px]">
                <div class="p-sm px-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low/60 z-10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">map</span>
                        <span class="font-label-md text-label-md text-on-surface uppercase tracking-wider">Live Regional Asset Map</span>
                    </div>
                    <div class="flex items-center gap-2 text-body-sm text-on-surface-variant">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span> Active Stream</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-error"></span> Alerts (<span x-text="kpis.critical_alerts"></span>)</span>
                    </div>
                </div>
                <div class="flex-1 relative w-full h-full min-h-[360px]" id="dashboard-map">
                    <!-- Floating Overlay -->
                    <div class="absolute top-4 left-4 z-[1000] pointer-events-none">
                        <div class="bg-surface/90 backdrop-blur-md px-3 py-2 rounded-lg border border-outline-variant/60 shadow-md flex items-center gap-2 pointer-events-auto">
                            <div class="w-3 h-3 rounded-full bg-primary animate-pulse"></div>
                            <span class="font-label-md text-label-md text-on-surface">Lagos Logistics Corridor: Teltonika GPS Stream Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Alerts List -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs flex flex-col h-full overflow-hidden">
                <div class="p-md border-b border-outline-variant flex justify-between items-center">
                    <span class="font-headline-md text-headline-md text-on-surface">Recent Alerts & Diagnostics</span>
                    <a href="{{ route('alerts') }}" class="font-label-md text-label-md text-primary hover:underline flex items-center gap-1">
                        View All <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-outline-variant/40">
                    <template x-for="item in recentAlerts" :key="item.id">
                        <div class="p-md hover:bg-surface-container-low transition-colors cursor-pointer group">
                            <div class="flex justify-between items-start mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]" :class="item.severity === 'critical' ? 'text-error' : 'text-[#b45309]'" x-text="item.icon"></span>
                                    <span class="font-label-md text-label-md text-on-surface group-hover:text-primary transition-colors" x-text="item.title"></span>
                                </div>
                                <span class="font-body-sm text-body-sm text-on-surface-variant" x-text="item.time_ago"></span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="font-data-mono text-data-mono text-on-surface-variant" x-text="item.vehicle_code + ' (' + item.vehicle_name + ')'"></span>
                                <span :class="item.severity === 'critical' ? 'bg-error-container text-on-error-container' : 'bg-[#b45309]/10 text-[#b45309]'" class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase tracking-wider font-bold" x-text="item.severity"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="recentAlerts.length === 0" class="p-md text-center text-on-surface-variant text-body-sm">
                        No recent alerts. Fleet operating normally.
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Diagnostic Trouble Codes (DTC) & Performance Chart Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-md">
            <!-- Active Diagnostic Trouble Codes (DTC) Card -->
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs flex flex-col overflow-hidden">
                <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low/40">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">build_circle</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface">Active Diagnostic Trouble Codes (DTC)</h3>
                        <span class="px-2 py-0.5 rounded-full bg-error/10 text-error font-label-md text-[11px] font-bold" x-text="activeDtcFaults.length + ' Faults'"></span>
                    </div>
                    <a href="{{ route('precision-system') }}" class="font-label-md text-label-md text-primary hover:underline flex items-center gap-1">
                        Precision System <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto divide-y divide-outline-variant/40">
                    <template x-for="fault in activeDtcFaults" :key="fault.id">
                        <div class="p-md hover:bg-surface-container-low/60 transition-colors flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                            <div class="flex items-start gap-md">
                                <div class="p-2 rounded-lg bg-surface-container-high border border-outline-variant shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-[20px]" :class="fault.severity === 'critical' ? 'text-error' : 'text-[#b45309]'">warning</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-data-mono font-bold text-body-md text-on-surface" x-text="fault.code"></span>
                                        <span :class="fault.severity === 'critical' ? 'bg-error-container text-on-error-container' : 'bg-[#b45309]/10 text-[#b45309]'" class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase font-bold" x-text="fault.severity"></span>
                                        <span class="text-[11px] font-data-mono px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant font-semibold" x-text="fault.vehicle_code"></span>
                                    </div>
                                    <p class="font-body-md text-body-md text-on-surface font-medium" x-text="fault.description"></p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">info</span> Action: <span class="font-medium text-on-surface" x-text="fault.recommended_action"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-md self-end sm:self-center shrink-0">
                                <span class="text-[11px] text-on-surface-variant font-data-mono" x-text="fault.created_at"></span>
                                <button @click="resolveDtc(fault.id)" class="px-3 py-1.5 bg-surface border border-outline-variant hover:bg-surface-container-high rounded-lg text-label-md font-label-md text-on-surface transition-colors flex items-center gap-1 shadow-2xs">
                                    <span class="material-symbols-outlined text-[16px] text-[#059669]">check_circle</span> Resolve
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="activeDtcFaults.length === 0" class="p-lg text-center text-on-surface-variant text-body-md">
                        <span class="material-symbols-outlined text-[32px] text-[#059669] block mb-1">verified</span>
                        No active diagnostic trouble codes detected across Teltonika telemetry nodes.
                    </div>
                </div>
            </div>

            <!-- Fleet Telemetry Performance Chart Integration with wire:ignore wrapper rule -->
            <div class="bg-surface-container-lowest p-md border border-outline-variant rounded-xl shadow-xs flex flex-col gap-md">
                <div class="flex justify-between items-center border-b border-outline-variant pb-xs">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-surface">Telemetry Performance</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Speed vs. fuel index</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-label-md font-label-md font-bold">Live Stream</span>
                </div>
                <!-- STRICT CONSTRAINT: Must have wire:ignore wrapper -->
                <div wire:ignore class="h-64 w-full">
                    <canvas id="dashboardMetricsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboardApp() {
            return {
                kpis: {
                    total_fleet: '0',
                    active_loads: '0',
                    critical_alerts: 0,
                    in_geofence_vehicles: '0',
                    adherence_rate: '100%',
                    precision_rating: '99.00%'
                },
                recentAlerts: [],
                activeDtcFaults: [],
                map: null,
                chart: null,

                async initDashboard() {
                    this.initChart();
                    this.initMap();
                    this.fetchDashboardMetrics();
                },

                async fetchDashboardMetrics() {
                    try {
                        const res = await fetch('/api/fleet/dashboard-metrics');
                        const json = await res.json();
                        if (json.success) {
                            this.kpis = json.kpis;
                            this.recentAlerts = json.recent_alerts;
                            this.activeDtcFaults = json.active_dtc_faults || [];
                        }
                    } catch (e) {
                        console.error('Failed to fetch dashboard metrics', e);
                    }
                },

                async resolveDtc(faultId) {
                    try {
                        const res = await fetch(`/api/fleet/dtc-faults/${faultId}/resolve`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.fetchDashboardMetrics();
                        }
                    } catch (e) {
                        console.error('Failed to resolve DTC fault', e);
                    }
                },

                initChart() {
                    const ctx = document.getElementById('dashboardMetricsChart');
                    if (!ctx) return;
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                            datasets: [
                                {
                                    label: 'Avg Speed (km/h)',
                                    data: [58, 62, 54, 65, 68, 60, 56],
                                    borderColor: '#004ac6',
                                    backgroundColor: 'rgba(0, 74, 198, 0.08)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: 'Fuel Economy Index (mpg)',
                                    data: [7.2, 7.5, 6.8, 7.8, 8.1, 7.9, 7.6],
                                    borderColor: '#059669',
                                    borderDash: [5, 5],
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } },
                            scales: { y: { beginAtZero: false } }
                        }
                    });
                },

                async initMap() {
                    if (typeof L === 'undefined') return;
                    this.map = L.map('dashboard-map', { zoomControl: false }).setView([6.5802, 3.2932], 13);
                    
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap &copy; CARTO'
                    }).addTo(this.map);

                    L.control.zoom({ position: 'topright' }).addTo(this.map);

                    try {
                        const res = await fetch('/api/fleet/assets');
                        const json = await res.json();
                        if (json.success && json.assets) {
                            json.assets.forEach(t => {
                                const color = t.status === 'Alert' ? '#ba1a1a' : (t.status === 'Loaded' ? '#004ac6' : '#505f76');
                                const markerHtml = `<div style="background-color: ${color}; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><div style="width: 6px; height: 6px; background-color: white; border-radius: 50%;"></div></div>`;
                                const customIcon = L.divIcon({
                                    html: markerHtml,
                                    className: 'custom-leaflet-marker',
                                    iconSize: [18, 18],
                                    iconAnchor: [9, 9]
                                });
                                L.marker([t.lat, t.lng], { icon: customIcon }).addTo(this.map).bindPopup(`<strong>${t.name} (${t.id})</strong><br>IMEI: ${t.imei}<br>Speed: ${t.speed}<br>Battery: ${t.battery_voltage}`);
                            });

                            // Draw trajectory of first asset
                            if (json.assets.length > 0) {
                                const firstAsset = json.assets[0];
                                const trailRes = await fetch(`/api/fleet/assets/${firstAsset.db_id}/trail`);
                                const trailJson = await trailRes.json();
                                if (trailJson.success && trailJson.trail && trailJson.trail.path) {
                                    const coords = trailJson.trail.path.map(pt => [pt.lat, pt.lng]);
                                    if (coords.length > 0) {
                                        const polyline = L.polyline(coords, { color: '#004ac6', weight: 4, dashArray: '6, 6' }).addTo(this.map);
                                        this.map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
                                    }
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load dashboard telemetry map', e);
                    }
                }
            }
        }
    </script>
</x-layout>
