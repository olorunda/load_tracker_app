<x-layout title="Alert Configuration & Trigger Log - FleetManager">
    <div class="p-md md:p-margin flex-1 bg-background" x-data="alertConfigApp()">
        <div class="max-w-6xl mx-auto space-y-lg">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-sm pb-sm border-b border-outline-variant">
                <div>
                    <h2 class="text-display font-display text-on-surface">Alert Configuration & Telemetry Logs</h2>
                    <p class="text-body-lg font-body-lg text-on-surface-variant mt-1">Real-time geofence alerts, load status violations, and automated notification triggers.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="evaluateFleet()" :disabled="evaluating" class="bg-surface-container-high border border-outline-variant text-on-surface px-3 py-2 rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1.5 disabled:opacity-50">
                        <span class="material-symbols-outlined text-[18px]" :class="evaluating ? 'animate-spin' : ''">rule</span>
                        <span x-text="evaluating ? 'Evaluating...' : 'Run Fleet Evaluation'"></span>
                    </button>
                    <button @click="fetchLiveAlerts()" class="bg-surface-container-high border border-outline-variant text-on-surface px-3 py-2 rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">refresh</span> Refresh
                    </button>
                    <button @click="openCreateModal()" class="bg-primary text-on-primary px-4 py-2 rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors flex items-center gap-2 shadow-xs">
                        <span class="material-symbols-outlined text-[18px]">add</span> New Alert Rule
                    </button>
                </div>
            </div>

            <!-- Toast / Feedback Banner -->
            <div x-show="toastMessage" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="p-sm px-md rounded-lg bg-primary/10 border border-primary/20 text-primary font-medium text-body-sm flex justify-between items-center"
                 style="display: none;">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span x-text="toastMessage"></span>
                </div>
                <button @click="toastMessage = ''" class="text-primary hover:opacity-80">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>

            <!-- Live Triggered Alerts Section -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xs overflow-hidden">
                <div class="p-md bg-surface-container-lowest border-b border-outline-variant flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                    <div>
                        <h3 class="text-headline-md font-headline-md text-on-surface">Live Triggered Geofence & Load Alerts</h3>
                        <p class="text-body-sm text-on-surface-variant">Automated evaluations against active vehicle telemetry and rules</p>
                    </div>
                    <div class="flex items-center gap-sm">
                        <!-- Filter Tabs -->
                        <div class="flex rounded-lg bg-surface-container-low p-1 border border-outline-variant/60 text-[11px] font-medium">
                            <button @click="alertFilter = 'all'" :class="alertFilter === 'all' ? 'bg-surface text-primary shadow-xs font-bold' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded transition-colors">All (<span x-text="liveAlerts.length"></span>)</button>
                            <button @click="alertFilter = 'active'" :class="alertFilter === 'active' ? 'bg-surface text-primary shadow-xs font-bold' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded transition-colors">Active (<span x-text="activeAlertsCount"></span>)</button>
                            <button @click="alertFilter = 'resolved'" :class="alertFilter === 'resolved' ? 'bg-surface text-primary shadow-xs font-bold' : 'text-on-surface-variant'" class="px-2.5 py-1 rounded transition-colors">Resolved (<span x-text="resolvedAlertsCount"></span>)</button>
                        </div>

                        <button x-show="activeAlertsCount > 0" @click="resolveAllAlerts()" class="text-[11px] px-2.5 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface font-medium flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">done_all</span> Resolve All
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Alert Details</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Vehicle</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Zone / Context</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Severity</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Triggered At</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-md font-body-md text-on-surface divide-y divide-outline-variant/40">
                            <template x-for="alert in filteredAlerts" :key="alert.id">
                                <tr class="hover:bg-surface-container-low transition-colors" :class="alert.is_resolved ? 'opacity-60 bg-surface-container-lowest' : ''">
                                    <td class="py-4 px-md">
                                        <div class="font-semibold text-on-surface flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]" 
                                                  :class="alert.severity === 'critical' ? 'text-error' : (alert.severity === 'info' ? 'text-primary' : 'text-[#d97706]')" 
                                                  x-text="getAlertIcon(alert.type)"></span>
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
                                        <span :class="alert.severity === 'critical' ? 'bg-error-container text-on-error-container' : (alert.severity === 'info' ? 'bg-primary/10 text-primary' : 'bg-[#fef3c7] text-[#92400e]')" 
                                              class="px-2.5 py-1 rounded-full font-label-md text-[11px] uppercase tracking-wider font-bold" 
                                              x-text="alert.severity"></span>
                                    </td>
                                    <td class="py-4 px-md text-right text-body-sm text-on-surface-variant">
                                        <div class="font-bold text-on-surface" x-text="alert.time_ago"></div>
                                        <div class="text-[11px] font-data-mono" x-text="alert.triggered_at"></div>
                                    </td>
                                    <td class="py-4 px-md text-right">
                                        <template x-if="!alert.is_resolved">
                                            <button type="button" @click="resolveAlert(alert.id)" class="px-2.5 py-1 text-[11px] rounded bg-surface border border-outline-variant hover:bg-surface-container-high text-primary font-semibold transition-colors">
                                                Resolve
                                            </button>
                                        </template>
                                        <template x-if="alert.is_resolved">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-on-surface-variant">
                                                <span class="material-symbols-outlined text-[14px] text-[#059669]">check</span> Resolved
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredAlerts.length === 0">
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                        <div class="flex flex-col items-center justify-center space-y-1">
                                            <span class="material-symbols-outlined text-[32px] text-outline">verified</span>
                                            <span class="font-semibold text-body-md text-on-surface">No alerts in this view</span>
                                            <span class="text-[12px]">All active vehicle sensors and telematics operating normally.</span>
                                        </div>
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
                    <div>
                        <h3 class="text-headline-md font-headline-md text-on-surface">Active Notification Triggers</h3>
                        <p class="text-body-sm text-on-surface-variant">Automated monitoring policies configured for fleet assets</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-primary/10 text-primary font-label-md text-label-md font-bold" x-text="rules.length + ' Configured Rules'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Alert Name</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Trigger Type</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Target Fleet</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase">Severity / Threshold</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Status</th>
                                <th class="py-3 px-md text-label-md font-label-md text-on-surface-variant uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-md font-body-md text-on-surface divide-y divide-outline-variant/40">
                            <template x-for="rule in rules" :key="rule.id">
                                <tr class="hover:bg-surface-container-low transition-colors" :class="!rule.is_active ? 'opacity-50' : ''">
                                    <td class="py-4 px-md">
                                        <div class="font-semibold text-on-surface" x-text="rule.name"></div>
                                        <div class="text-body-sm font-body-sm text-on-surface-variant" x-text="rule.description || 'Automated policy rule'"></div>
                                    </td>
                                    <td class="py-4 px-md">
                                        <span class="inline-flex items-center gap-1.5 bg-surface-variant text-on-surface px-2.5 py-1 rounded-md text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px] text-primary" x-text="rule.icon || 'notifications'"></span>
                                            <span x-text="rule.type"></span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-md font-medium" x-text="rule.target_group"></td>
                                    <td class="py-4 px-md">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="rule.severity === 'critical' ? 'bg-error-container text-on-error-container' : (rule.severity === 'info' ? 'bg-primary/10 text-primary' : 'bg-[#fef3c7] text-[#92400e]')" class="px-2 py-0.5 rounded text-[10px] uppercase font-bold" x-text="rule.severity"></span>
                                            <template x-if="rule.threshold_value !== null && rule.threshold_value !== undefined">
                                                <span class="text-[11px] font-data-mono text-on-surface-variant" x-text="'Cap: ' + rule.threshold_value"></span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="py-4 px-md text-right">
                                        <button type="button" @click="toggleRule(rule)" 
                                                :class="rule.is_active ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant border border-outline-variant'"
                                                class="px-3 py-1 rounded-full font-label-md text-label-md transition-colors cursor-pointer">
                                            <span x-text="rule.is_active ? 'Enabled' : 'Disabled'"></span>
                                        </button>
                                    </td>
                                    <td class="py-4 px-md text-right">
                                        <button type="button" @click="deleteRule(rule.id)" title="Delete Rule" class="p-1 rounded text-on-surface-variant hover:text-error hover:bg-error-container/20 transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="rules.length === 0">
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                        <div class="flex flex-col items-center justify-center space-y-1">
                                            <span class="material-symbols-outlined text-[32px] text-outline">tune</span>
                                            <span class="font-semibold text-body-md text-on-surface">No alert rules configured</span>
                                            <span class="text-[12px]">Click "New Alert Rule" above to create automated notification policies.</span>
                                        </div>
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
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[2000] flex items-center justify-center p-4"
             style="display: none; background-color: rgba(17, 28, 45, 0.55); backdrop-filter: blur(4px);">
            <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl border border-outline-variant shadow-2xl p-6 space-y-5"
                 @click.outside="showNewRuleModal = false"
                 style="width: 100%; max-width: 540px; box-sizing: border-box; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 16px; padding: 24px;">
                
                <div class="flex justify-between items-center border-b border-outline-variant pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">notification_add</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface font-bold text-lg">Create Alert Rule</h3>
                    </div>
                    <button type="button" @click="showNewRuleModal = false" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Error Notice -->
                <div x-show="formError" class="p-3 rounded-lg bg-error-container text-on-error-container text-body-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span x-text="formError"></span>
                </div>

                <form @submit.prevent="saveRule()" class="space-y-4">
                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Rule Name *</label>
                        <input type="text" x-model="ruleForm.name" required placeholder="e.g. Unscheduled Unload Warning" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;"/>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Trigger Type *</label>
                            <select x-model="ruleForm.type" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                                <option value="Load Status Change">Load Status Change</option>
                                <option value="Speeding Threshold">Speeding Threshold</option>
                                <option value="Geofence Exit / Entry">Geofence Exit / Entry</option>
                                <option value="Voltage Anomaly">IO 66 Voltage Anomaly</option>
                                <option value="Cold Chain Temp">Cold Chain Temp</option>
                                <option value="Diagnostic Fault">Diagnostic Fault</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Severity Level *</label>
                            <select x-model="ruleForm.severity" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                                <option value="critical">Critical (Red Alert)</option>
                                <option value="warning">Warning (Amber)</option>
                                <option value="info">Informational (Blue)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Target Fleet Group *</label>
                            <select x-model="ruleForm.target_group" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;">
                                <option value="All Fleet Assets">All Fleet Assets</option>
                                <option value="All Heavy Duty Trucks">All Heavy Duty Trucks</option>
                                <option value="Reefer Cold Chain Fleet">Reefer Cold Chain Fleet</option>
                                <option value="Regional Delivery Vans">Regional Delivery Vans</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Trigger Threshold (Optional)</label>
                            <input type="number" step="any" x-model="ruleForm.threshold_value" :placeholder="getThresholdPlaceholder()" class="w-full px-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md font-data-mono focus:border-primary outline-none" style="width: 100%;"/>
                        </div>
                    </div>

                    <div>
                        <label class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase font-semibold text-xs">Rule Description / Instructions</label>
                        <textarea x-model="ruleForm.description" rows="2" placeholder="Describe the trigger condition or recommended dispatcher response..." class="w-full px-4 py-2 rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary outline-none" style="width: 100%;"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-outline-variant pt-4">
                        <button type="button" @click="showNewRuleModal = false" class="px-4 py-2 border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-low font-semibold">Cancel</button>
                        <button type="submit" :disabled="savingRule" class="px-5 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 font-bold flex items-center gap-1.5 disabled:opacity-50">
                            <span x-show="savingRule" class="animate-spin material-symbols-outlined text-[16px]">progress_activity</span>
                            <span x-text="savingRule ? 'Saving...' : 'Save Alert Rule'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function alertConfigApp() {
            return {
                showNewRuleModal: false,
                savingRule: false,
                evaluating: false,
                formError: '',
                toastMessage: '',
                alertFilter: 'all', // 'all', 'active', 'resolved'
                liveAlerts: [],
                rules: [],
                ruleForm: {
                    name: '',
                    type: 'Load Status Change',
                    target_group: 'All Heavy Duty Trucks',
                    severity: 'warning',
                    threshold_value: '',
                    description: '',
                },

                get activeAlertsCount() {
                    return this.liveAlerts.filter(a => !a.is_resolved).length;
                },

                get resolvedAlertsCount() {
                    return this.liveAlerts.filter(a => a.is_resolved).length;
                },

                get filteredAlerts() {
                    if (this.alertFilter === 'active') {
                        return this.liveAlerts.filter(a => !a.is_resolved);
                    }
                    if (this.alertFilter === 'resolved') {
                        return this.liveAlerts.filter(a => a.is_resolved);
                    }
                    return this.liveAlerts;
                },

                getAlertIcon(type) {
                    const t = (type || '').toLowerCase();
                    if (t.includes('speed') || t === 'overspeed') return 'speed';
                    if (t.includes('load') || t.includes('cargo')) return 'inventory_2';
                    if (t.includes('volt') || t.includes('power')) return 'bolt';
                    if (t.includes('entry') || t.includes('exit') || t.includes('geofence')) return 'share_location';
                    return 'warning';
                },

                getThresholdPlaceholder() {
                    const t = (this.ruleForm.type || '').toLowerCase();
                    if (t.includes('speed')) return 'e.g. 75 (km/h)';
                    if (t.includes('volt')) return 'e.g. 11500 (mV)';
                    if (t.includes('temp')) return 'e.g. -18 (°C)';
                    return 'e.g. 0 (Optional numeric cap)';
                },

                openCreateModal() {
                    this.formError = '';
                    this.ruleForm = {
                        name: '',
                        type: 'Load Status Change',
                        target_group: 'All Heavy Duty Trucks',
                        severity: 'warning',
                        threshold_value: '',
                        description: '',
                    };
                    this.showNewRuleModal = true;
                },

                async fetchLiveAlerts() {
                    try {
                        const res = await fetch('/api/alerts', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (json.success && json.alerts) {
                            this.liveAlerts = json.alerts;
                        }
                    } catch (e) {
                        console.error('Failed to fetch alerts', e);
                    }
                },

                async fetchRules() {
                    try {
                        const res = await fetch('/api/alert-rules', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (json.success && json.rules) {
                            this.rules = json.rules;
                        }
                    } catch (e) {
                        console.error('Failed to fetch alert rules', e);
                    }
                },

                async saveRule() {
                    this.formError = '';
                    if (!this.ruleForm.name.trim()) {
                        this.formError = 'Please specify a rule name.';
                        return;
                    }

                    this.savingRule = true;
                    try {
                        const res = await fetch('/api/alert-rules', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                name: this.ruleForm.name,
                                type: this.ruleForm.type,
                                target_group: this.ruleForm.target_group,
                                severity: this.ruleForm.severity,
                                threshold_value: this.ruleForm.threshold_value !== '' ? this.ruleForm.threshold_value : null,
                                description: this.ruleForm.description,
                                is_active: true
                            })
                        });

                        const json = await res.json();
                        if (res.ok && json.success) {
                            this.showNewRuleModal = false;
                            this.toastMessage = json.message || 'Alert rule saved successfully!';
                            await this.fetchRules();
                            await this.fetchLiveAlerts();
                            setTimeout(() => { this.toastMessage = ''; }, 4000);
                        } else {
                            this.formError = json.message || 'Failed to save alert rule. Please check fields.';
                        }
                    } catch (e) {
                        console.error('Save alert rule error', e);
                        this.formError = 'A network error occurred while saving alert rule.';
                    } finally {
                        this.savingRule = false;
                    }
                },

                async toggleRule(rule) {
                    const prevStatus = rule.is_active;
                    rule.is_active = !rule.is_active; // Optimistic update

                    try {
                        const res = await fetch(`/api/alert-rules/${rule.id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (!res.ok || !json.success) {
                            rule.is_active = prevStatus; // Rollback
                            alert(json.message || 'Failed to update rule status.');
                        } else {
                            this.toastMessage = json.message;
                            setTimeout(() => { this.toastMessage = ''; }, 3000);
                        }
                    } catch (e) {
                        rule.is_active = prevStatus;
                        console.error('Toggle rule error', e);
                    }
                },

                async deleteRule(ruleId) {
                    if (!confirm('Are you sure you want to delete this alert rule?')) return;

                    try {
                        const res = await fetch(`/api/alert-rules/${ruleId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.success) {
                            this.rules = this.rules.filter(r => r.id !== ruleId);
                            this.toastMessage = 'Alert rule removed.';
                            setTimeout(() => { this.toastMessage = ''; }, 3000);
                        } else {
                            alert(json.message || 'Failed to delete alert rule.');
                        }
                    } catch (e) {
                        console.error('Delete rule error', e);
                    }
                },

                async resolveAlert(alertId) {
                    try {
                        const res = await fetch(`/api/alerts/${alertId}/resolve`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.success) {
                            const alertItem = this.liveAlerts.find(a => a.id === alertId);
                            if (alertItem) alertItem.is_resolved = true;
                            this.toastMessage = 'Alert marked as resolved.';
                            setTimeout(() => { this.toastMessage = ''; }, 3000);
                        }
                    } catch (e) {
                        console.error('Resolve alert error', e);
                    }
                },

                async resolveAllAlerts() {
                    if (!confirm('Mark all active alerts as resolved?')) return;

                    try {
                        const res = await fetch('/api/alerts/resolve-all', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.success) {
                            this.liveAlerts.forEach(a => a.is_resolved = true);
                            this.toastMessage = 'All alerts marked as resolved.';
                            setTimeout(() => { this.toastMessage = ''; }, 3000);
                        }
                    } catch (e) {
                        console.error('Resolve all error', e);
                    }
                },

                async evaluateFleet() {
                    this.evaluating = true;
                    try {
                        const res = await fetch('/api/geofences/evaluate', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const json = await res.json();
                        await this.fetchLiveAlerts();
                        this.toastMessage = `Fleet evaluation complete. ${json.active_alerts_count || 0} active alert(s).`;
                        setTimeout(() => { this.toastMessage = ''; }, 4000);
                    } catch (e) {
                        console.error('Evaluate fleet error', e);
                    } finally {
                        this.evaluating = false;
                    }
                },

                init() {
                    this.fetchLiveAlerts();
                    this.fetchRules();
                }
            }
        }
    </script>
</x-layout>
