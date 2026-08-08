<x-layout title="Fleet Precision System - FleetManager">
    <div class="p-md md:p-margin flex-1 bg-background space-y-lg" x-data="{
        metrics: {
            precision_index: '99.84%',
            active_vehicles: 2,
            total_gps_records: 367,
            active_faults: 2,
            avg_satellites: 14.2,
            avg_hdop: 0.7,
            last_stream_ping: 'Just now',
            stream_protocol: 'Teltonika TCP Codec 8 / 8 Ext'
        },
        dtcFaults: [],
        loadingDiagnostics: false,
        chart: null,

        initChart() {
            const ctx = document.getElementById('precisionChart');
            if (!ctx) return;
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Telemetry Packets Received',
                            data: [0, 0, 0, 0, 0, 0, 0],
                            backgroundColor: '#004ac6',
                            borderRadius: 6
                        },
                        {
                            label: 'Diagnostic Ping Success Rate (%)',
                            data: [100, 100, 100, 100, 100, 100, 100],
                            type: 'line',
                            borderColor: '#059669',
                            borderWidth: 3,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        async initDiagnostics() {
            this.initChart();
            try {
                const res = await fetch('/api/fleet/diagnostics');
                const json = await res.json();
                if (json.success && json.metrics) {
                    this.metrics = json.metrics;
                    if (json.metrics.chart && this.chart) {
                        this.chart.data.labels = json.metrics.chart.labels;
                        this.chart.data.datasets[0].data = json.metrics.chart.packets_received;
                        this.chart.data.datasets[1].data = json.metrics.chart.ping_success_rate;
                        this.chart.update();
                    }
                }
                this.fetchDtcFaults();
            } catch (e) {
                console.error('Failed to load diagnostics', e);
            }
        },

        async runDiagnosticsEngine() {
            this.loadingDiagnostics = true;
            try {
                const res = await fetch('/api/fleet/diagnostics/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const json = await res.json();
                if (json.success) {
                    this.metrics = json.metrics;
                    if (json.metrics.chart && this.chart) {
                        this.chart.data.labels = json.metrics.chart.labels;
                        this.chart.data.datasets[0].data = json.metrics.chart.packets_received;
                        this.chart.data.datasets[1].data = json.metrics.chart.ping_success_rate;
                        this.chart.update();
                    }
                    this.fetchDtcFaults();
                }
            } catch (e) {
                console.error('Failed to run diagnostics', e);
            } finally {
                this.loadingDiagnostics = false;
            }
        },

        async fetchDtcFaults() {
            try {
                const res = await fetch('/api/fleet/dtc-faults');
                const json = await res.json();
                if (json.success) {
                    this.dtcFaults = json.faults;
                }
            } catch (e) {
                console.error('Failed to fetch DTC faults', e);
            }
        },

        async resolveFault(faultId) {
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
                    this.initDiagnostics();
                }
            } catch (e) {
                console.error('Failed to resolve fault', e);
            }
        }
    }" x-init="initDiagnostics()">
        <!-- Header -->
        <div class="flex justify-between items-end pb-sm border-b border-outline-variant">
            <div>
                <h2 class="text-display font-display text-on-surface">Fleet Precision & Health System</h2>
                <p class="text-body-lg font-body-lg text-on-surface-variant mt-1">Real-time telematics precision index, Teltonika AVL packet diagnostics, and sensor calibration.</p>
            </div>
            <div class="flex gap-sm">
                <button @click="runDiagnosticsEngine()" :disabled="loadingDiagnostics" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors flex items-center gap-2 shadow-xs disabled:opacity-50">
                    <span x-show="!loadingDiagnostics" class="material-symbols-outlined text-[18px]">build</span>
                    <span x-show="loadingDiagnostics" class="animate-spin material-symbols-outlined text-[18px]">progress_activity</span>
                    <span x-text="loadingDiagnostics ? 'Evaluating...' : 'Run System Diagnostics'"></span>
                </button>
            </div>
        </div>

        <!-- System Health KPI Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
            <!-- Telematics Accuracy -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Precision Index</span>
                    <span class="material-symbols-outlined text-primary">precision_manufacturing</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="metrics.precision_index">99.84%</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-[#059669] flex items-center"><span class="material-symbols-outlined text-[14px]">check_circle</span> Optimal</span>
                    <span class="text-on-surface-variant">Avg HDOP: <strong class="text-on-surface" x-text="metrics.avg_hdop"></strong></span>
                </div>
            </div>

            <!-- Active Records -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">GPS Telemetry Records</span>
                    <span class="material-symbols-outlined text-primary">database</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="metrics.total_gps_records">367</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-primary flex items-center"><span class="material-symbols-outlined text-[14px]">sensors</span> Teltonika Codec 8</span>
                    <span class="text-on-surface-variant">Stored AVL</span>
                </div>
            </div>

            <!-- Sensor Calibration & Satellites -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">GNSS Satellite Coverage</span>
                    <span class="material-symbols-outlined text-[#059669]">satellite_alt</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="metrics.avg_satellites + ' Sats'">14.2 Sats</div>
                <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-1 overflow-hidden">
                    <div class="bg-[#059669] h-1.5 rounded-full" style="width: 94%"></div>
                </div>
                <div class="font-body-sm text-body-sm text-on-surface-variant">High Precision Fix</div>
            </div>

            <!-- Protocol Stream Rate -->
            <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex flex-col gap-sm">
                <div class="flex justify-between items-center text-on-surface-variant">
                    <span class="font-label-md text-label-md">Active Faults</span>
                    <span class="material-symbols-outlined text-error">warning</span>
                </div>
                <div class="font-headline-lg text-headline-lg text-on-surface" x-text="dtcFaults.length + ' Faults'">0 Faults</div>
                <div class="flex items-center gap-xs font-body-sm text-body-sm">
                    <span class="text-error flex items-center"><span class="material-symbols-outlined text-[14px]">report</span> Diagnostic DTC</span>
                    <span class="text-on-surface-variant" x-text="metrics.last_stream_ping"></span>
                </div>
            </div>
        </div>

        <!-- Interactive Precision Chart Integration with wire:ignore wrapper rule -->
        <div class="bg-surface-container-lowest p-md border border-outline-variant rounded-xl shadow-xs space-y-md">
            <div class="flex justify-between items-center border-b border-outline-variant pb-xs">
                <div>
                    <h3 class="font-headline-md text-headline-md text-on-surface">Telematics Stream Precision Rating</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Daily packet receipt volume vs diagnostic ping integrity</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container text-label-md font-label-md">Live Analytics</span>
            </div>
            <!-- STRICT CONSTRAINT: Must have wire:ignore wrapper -->
            <div wire:ignore class="h-64 w-full">
                <canvas id="precisionChart"></canvas>
            </div>
        </div>

        <!-- Diagnostic Trouble Codes (DTC) Table Card -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xs overflow-hidden">
            <div class="p-md bg-surface-container-lowest border-b border-outline-variant flex justify-between items-center">
                <div>
                    <h3 class="text-headline-md font-headline-md text-on-surface">Active Diagnostic Trouble Codes (DTC)</h3>
                    <p class="text-body-sm text-on-surface-variant">Automated Teltonika battery & OBD-II sensor diagnostics</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-error-container text-on-error-container font-label-md text-label-md font-bold" x-text="dtcFaults.length + ' Active Faults'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">DTC Code</th>
                            <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Fault Description</th>
                            <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Vehicle</th>
                            <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Severity</th>
                            <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Recommended Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-body-md font-body-md text-on-surface divide-y divide-outline-variant/40">
                        <template x-for="fault in dtcFaults" :key="fault.id">
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="py-4 px-md font-data-mono font-bold" :class="fault.severity === 'critical' ? 'text-error' : 'text-[#b45309]'" x-text="fault.code"></td>
                                <td class="py-4 px-md" x-text="fault.description"></td>
                                <td class="py-4 px-md">
                                    <div class="font-data-mono font-bold text-on-surface" x-text="fault.vehicle_code"></div>
                                    <div class="text-body-sm text-on-surface-variant" x-text="fault.vehicle_name"></div>
                                </td>
                                <td class="py-4 px-md">
                                    <span :class="fault.severity === 'critical' ? 'bg-error-container text-on-error-container' : 'bg-[#b45309]/10 text-[#b45309]'" class="px-2.5 py-1 rounded-full font-label-md text-[10px] uppercase tracking-wider font-bold" x-text="fault.severity"></span>
                                </td>
                                <td class="py-4 px-md text-right flex items-center justify-end gap-2">
                                    <span class="text-body-sm text-on-surface-variant mr-2" x-text="fault.recommended_action"></span>
                                    <button @click="resolveFault(fault.id)" class="px-3 py-1 bg-surface border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-high transition-colors">Resolve</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="dtcFaults.length === 0">
                            <td colspan="5" class="py-8 text-center text-on-surface-variant font-body-md">
                                <span class="material-symbols-outlined text-[36px] text-[#059669] block mb-1">check_circle</span>
                                No active diagnostic trouble codes detected. All sensors optimal.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
