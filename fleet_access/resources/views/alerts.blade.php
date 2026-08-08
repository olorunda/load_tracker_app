<x-layout title="Alert Configuration & Trigger Log - FleetManager">
    <div class="p-md md:p-margin flex-1 bg-background" x-data="alertConfigApp()">
        <div class="max-w-6xl mx-auto space-y-lg">
            <!-- Header -->
            <div class="flex justify-between items-end pb-sm border-b border-outline-variant">
                <div>
                    <h2 class="text-display font-display text-on-surface">Alert Configuration & Telemetry Logs</h2>
                    <p class="text-body-lg font-body-lg text-on-surface-variant mt-1">Real-time geofence alerts, speed violations, and notification triggers.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="fetchLiveAlerts()" class="bg-surface-container-high border border-outline-variant text-on-surface px-3 py-2 rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">refresh</span> Refresh Logs
                    </button>
                    <button @click="showNewRuleModal = true" class="bg-primary text-on-primary px-4 py-2 rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors flex items-center gap-2 shadow-xs">
                        <span class="material-symbols-outlined text-[18px]">add</span> New Alert Rule
                    </button>
                </div>
            </div>

            <!-- Live Triggered Alerts Section -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xs overflow-hidden">
                <div class="p-md bg-surface-container-lowest border-b border-outline-variant flex justify-between items-center">
                    <div>
                        <h3 class="text-headline-md font-headline-md text-on-surface">Live Triggered Geofence Alerts</h3>
                        <p class="text-body-sm text-on-surface-variant">Automated background job evaluations against active vehicle positions</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-error-container text-on-error-container font-label-md text-label-md font-bold" x-text="liveAlerts.length + ' Triggered Alerts'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Alert Details</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Vehicle</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Geofence Zone</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Severity</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Triggered At</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-md font-body-md text-on-surface divide-y divide-outline-variant/40">
                            <template x-for="alert in liveAlerts" :key="alert.id">
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    <td class="py-4 px-md">
                                        <div class="font-semibold text-on-surface flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]" :class="alert.severity === 'critical' ? 'text-error' : 'text-primary'" x-text="alert.type === 'overspeed' ? 'speed' : 'share_location'"></span>
                                            <span x-text="alert.title"></span>
                                        </div>
                                        <div class="text-body-sm font-body-sm text-on-surface-variant mt-0.5" x-text="alert.description"></div>
                                    </td>
                                    <td class="py-4 px-md">
                                        <div class="font-data-mono font-bold text-on-surface" x-text="alert.vehicle_code"></div>
                                        <div class="text-body-sm text-on-surface-variant" x-text="alert.vehicle_name"></div>
                                    </td>
                                    <td class="py-4 px-md font-medium" x-text="alert.geofence_name"></td>
                                    <td class="py-4 px-md">
                                        <span :class="alert.severity === 'critical' ? 'bg-error-container text-on-error-container' : 'bg-primary/10 text-primary'" class="px-2.5 py-1 rounded-full font-label-md text-[11px] uppercase tracking-wider font-bold" x-text="alert.severity"></span>
                                    </td>
                                    <td class="py-4 px-md text-right text-body-sm text-on-surface-variant">
                                        <div class="font-bold text-on-surface" x-text="alert.time_ago"></div>
                                        <div class="text-[11px] font-data-mono" x-text="alert.triggered_at"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rules Config Card -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xs overflow-hidden">
                <div class="p-md bg-surface-container-lowest border-b border-outline-variant flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                    <h3 class="text-headline-md font-headline-md text-on-surface">Active Notification Triggers</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Alert Name</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Trigger Type</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Target Fleet</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-md font-body-md text-on-surface divide-y divide-outline-variant/40">
                            <template x-for="rule in rules" :key="rule.id">
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    <td class="py-4 px-md">
                                        <div class="font-semibold text-on-surface" x-text="rule.name"></div>
                                        <div class="text-body-sm font-body-sm text-on-surface-variant" x-text="rule.desc"></div>
                                    </td>
                                    <td class="py-4 px-md">
                                        <span class="inline-flex items-center gap-1.5 bg-surface-variant text-on-surface px-2.5 py-1 rounded-md text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]" x-text="rule.icon"></span>
                                            <span x-text="rule.type"></span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-md" x-text="rule.target"></td>
                                    <td class="py-4 px-md text-right">
                                        <button @click="rule.active = !rule.active" 
                                                :class="rule.active ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant border border-outline-variant'"
                                                class="px-3 py-1 rounded-full font-label-md text-label-md transition-colors">
                                            <span x-text="rule.active ? 'Enabled' : 'Disabled'"></span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- New Rule Modal -->
        <div x-show="showNewRuleModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[2000] bg-on-surface/40 backdrop-blur-xs flex items-center justify-center p-4"
             style="display: none; background-color: rgba(17, 28, 45, 0.5); backdrop-filter: blur(4px);">
            <div class="bg-surface-container-lowest w-full max-w-lg rounded-xl border border-outline-variant shadow-2xl p-6 space-y-5"
                 style="width: 100%; max-width: 540px; min-width: 300px; box-sizing: border-box; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 16px; padding: 24px;">
                <div class="flex justify-between items-center border-b border-outline-variant pb-3">
                    <h3 class="font-headline-md text-headline-md text-on-surface font-bold text-lg">Create Alert Rule</h3>
                    <button @click="showNewRuleModal = false" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Rule Name</label>
                        <input type="text" placeholder="e.g. Engine Overheat Warning" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;"/>
                    </div>
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Trigger Type</label>
                        <select class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                            <option>Load Status Change</option>
                            <option>Speeding Threshold</option>
                            <option>Geofence Exit / Entry</option>
                            <option>Cold Chain Temp</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Target Fleet Group</label>
                        <select class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                            <option>All Heavy Duty Trucks</option>
                            <option>Reefer Cold Chain Fleet</option>
                            <option>Regional Delivery Vans</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-outline-variant pt-4">
                    <button @click="showNewRuleModal = false" class="px-4 py-2 border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-low font-semibold">Cancel</button>
                    <button @click="showNewRuleModal = false" class="px-5 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 font-bold">Save Rule</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function alertConfigApp() {
            return {
                showNewRuleModal: false,
                liveAlerts: [],
                rules: [
                    { id: 1, name: 'Unscheduled Unload', desc: 'Alert when cargo status changes to Empty outside registered depot.', type: 'Load Status', icon: 'inventory_2', target: 'All Heavy Duty', active: true },
                    { id: 2, name: 'Excessive Speed Limit', desc: 'Trigger when vehicle speed exceeds 75 mph for more than 2 minutes.', type: 'Speed', icon: 'speed', target: 'All Fleet Assets', active: true },
                    { id: 3, name: 'Geofence Perimeter Exit', desc: 'Broadcast alert whenever an asset departs designated hub boundary.', type: 'Geofence', icon: 'map', target: 'Regional Freight', active: true }
                ],
                async fetchLiveAlerts() {
                    try {
                        const res = await fetch('/api/alerts');
                        const json = await res.json();
                        if (json.success) {
                            this.liveAlerts = json.alerts;
                        }
                    } catch (e) {
                        console.error('Failed to fetch alerts', e);
                    }
                },
                init() {
                    this.fetchLiveAlerts();
                }
            }
        }
    </script>
</x-layout>
