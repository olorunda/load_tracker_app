<x-layout title="Asset & GPS Mapping - FleetManager">
    <div class="p-md md:p-margin flex-1 bg-background" x-data="{ 
        currentStep: 1, 
        submitted: false,
        loading: false,
        errorMessage: '',
        registeredVehicle: null,
        form: {
            code: '',
            name: '',
            category: '',
            status: 'Loaded',
            base_voltage: '',
            driver_name: '',
            imei: '',
            hardware: 'Teltonika FMC130 (Codec 8 Extended)',
            protocol: 'Teltonika TCP Data Parser (Codec 8 / Extended)',
            ping_interval: 15,
            geofence: '',
            speed_threshold: 70
        },
        async submitAssetForm() {
            this.loading = true;
            this.errorMessage = '';
            try {
                const res = await fetch('/api/fleet/assets/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    this.registeredVehicle = json.vehicle;
                    this.submitted = true;
                } else {
                    this.errorMessage = json.message || 'Failed to register asset. Please check fields.';
                }
            } catch (e) {
                console.error('Asset mapping error', e);
                this.errorMessage = 'Network error occurred while mapping asset.';
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.submitted = false;
            this.currentStep = 1;
            this.errorMessage = '';
            this.form.code = '';
            this.form.name = '';
            this.form.category = '';
            this.form.driver_name = '';
            this.form.imei = '';
        }
    }">
        <div class="max-w-4xl mx-auto space-y-lg">
            <!-- Header -->
            <div class="flex justify-between items-end pb-sm border-b border-outline-variant">
                <div>
                    <h2 class="text-display font-display text-on-surface">Asset GPS Device Mapping</h2>
                    <p class="text-body-lg font-body-lg text-on-surface-variant mt-1">Register new fleet vehicles, assign Teltonika / OBD-II GPS hardware, and set stream parameters.</p>
                </div>
                <a href="{{ route('live-tracking') }}" class="px-4 py-2 bg-surface-container-high border border-outline-variant rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container-highest transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Live Assets
                </a>
            </div>

            <!-- Progress Step Indicator -->
            <div class="grid grid-cols-3 gap-md" x-show="!submitted">
                <div @click="currentStep = 1" class="cursor-pointer p-md rounded-xl border transition-all flex items-center gap-md"
                     :class="currentStep === 1 ? 'bg-primary-container/10 border-primary text-primary' : 'bg-surface-container-lowest border-outline-variant text-on-surface-variant'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-label-md shrink-0"
                         :class="currentStep === 1 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">1</div>
                    <div>
                        <span class="font-label-md text-label-md uppercase tracking-wider block">Step 1</span>
                        <span class="font-headline-sm text-headline-sm font-semibold text-on-surface">Asset Metadata</span>
                    </div>
                </div>

                <div @click="currentStep = 2" class="cursor-pointer p-md rounded-xl border transition-all flex items-center gap-md"
                     :class="currentStep === 2 ? 'bg-primary-container/10 border-primary text-primary' : 'bg-surface-container-lowest border-outline-variant text-on-surface-variant'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-label-md shrink-0"
                         :class="currentStep === 2 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">2</div>
                    <div>
                        <span class="font-label-md text-label-md uppercase tracking-wider block">Step 2</span>
                        <span class="font-headline-sm text-headline-sm font-semibold text-on-surface">GPS & IMEI Mapping</span>
                    </div>
                </div>

                <div @click="currentStep = 3" class="cursor-pointer p-md rounded-xl border transition-all flex items-center gap-md"
                     :class="currentStep === 3 ? 'bg-primary-container/10 border-primary text-primary' : 'bg-surface-container-lowest border-outline-variant text-on-surface-variant'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-label-md shrink-0"
                         :class="currentStep === 3 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">3</div>
                    <div>
                        <span class="font-label-md text-label-md uppercase tracking-wider block">Step 3</span>
                        <span class="font-headline-sm text-headline-sm font-semibold text-on-surface">Geofence Rules</span>
                    </div>
                </div>
            </div>

            <!-- Error Banner -->
            <div x-show="errorMessage" class="p-md rounded-xl bg-error-container text-on-error-container border border-error flex items-center gap-sm">
                <span class="material-symbols-outlined text-error">error</span>
                <span x-text="errorMessage" class="font-body-md text-body-md"></span>
            </div>

            <!-- Form Card Container -->
            <div x-show="!submitted" class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xs p-lg space-y-lg">
                <!-- Step 1: Asset Details -->
                <div x-show="currentStep === 1" class="space-y-md">
                    <h3 class="font-headline-md text-headline-md text-on-surface border-b border-outline-variant pb-xs">Vehicle Identification</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Vehicle Identifier Code (VIN / ID)</label>
                            <input type="text" x-model="form.code" placeholder="TRK-1534" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Asset Title / Name</label>
                            <input type="text" x-model="form.name" placeholder="Volvo FH16 Globetrotter" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Vehicle Category</label>
                            <select x-model="form.category" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                <option value="" disabled selected>Select category...</option>
                                <option value="Heavy Duty Truck">Heavy Duty Truck</option>
                                <option value="Reefer Cold Chain Trailer">Reefer Cold Chain Trailer</option>
                                <option value="Light Delivery Van">Light Delivery Van</option>
                                <option value="Flatbed Transport">Flatbed Transport</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Driver Name</label>
                            <input type="text" x-model="form.driver_name" placeholder="Marcus Vance" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Initial Cargo Status</label>
                            <select x-model="form.status" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                <option value="Loaded">Loaded (Active Manifest)</option>
                                <option value="Empty">Empty (Unassigned)</option>
                                <option value="Maintenance">Maintenance / Service</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Telematics Device -->
                <div x-show="currentStep === 2" class="space-y-md" style="display: none;">
                    <h3 class="font-headline-md text-headline-md text-on-surface border-b border-outline-variant pb-xs">Telematics Hardware & Protocol</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Hardware Manufacturer / Model</label>
                            <select x-model="form.hardware" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                <option>Teltonika FMC130 (Codec 8 Extended)</option>
                                <option>Teltonika FMB920 (Codec 8)</option>
                                <option>Ruptela Trace5</option>
                                <option>CalAmp LMU-3030 OBD-II</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Device IMEI / Serial Identifier</label>
                            <input type="text" x-model="form.imei" placeholder="e.g. 860848081275126" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md font-data-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Stream Protocol Format</label>
                            <select x-model="form.protocol" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                <option>Teltonika TCP Data Parser (Codec 8 / Extended)</option>
                                <option>MQTT JSON Telemetry Gateway</option>
                                <option>HTTP REST Webhook Ingestion</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Ping Interval (Seconds)</label>
                            <input type="number" x-model="form.ping_interval" placeholder="15" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">IO 66 Baseline External Voltage (mV)</label>
                            <input type="number" x-model="form.base_voltage" placeholder="e.g. 11520 (Initial resting voltage when empty - auto-calibrates from first ping if blank)" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md font-data-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                            <span class="text-[11px] text-on-surface-variant block mt-1">Teltonika IO 66 (External Voltage): Any increase from this base determines the truck is Loaded.</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Geofence & Notification Rules -->
                <div x-show="currentStep === 3" class="space-y-md" style="display: none;">
                    <h3 class="font-headline-md text-headline-md text-on-surface border-b border-outline-variant pb-xs">Geofence & Alert Grouping</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Primary Hub / Depot Geofence</label>
                            <select x-model="form.geofence" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                <option value="" disabled selected>Select primary depot...</option>
                                <option value="Lagos Container Port Terminal">Lagos Container Port Terminal</option>
                                <option value="Ikeja Industrial Zone Geofence">Ikeja Industrial Zone Geofence</option>
                                <option value="Chicago Central Logistics Yard">Chicago Central Logistics Yard</option>
                                <option value="Port of Long Beach Terminal">Port of Long Beach Terminal</option>
                                <option value="Dallas Fort Worth Distribution">Dallas Fort Worth Distribution</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Speed Alarm Threshold (km/h)</label>
                            <input type="number" x-model="form.speed_threshold" placeholder="70" class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface text-body-md focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"/>
                        </div>
                    </div>
                </div>

                <!-- Step Actions Buttons -->
                <div class="flex justify-between items-center pt-md border-t border-outline-variant">
                    <button type="button" @click="if(currentStep > 1) currentStep--" :disabled="currentStep === 1 || loading" 
                            class="px-md py-sm rounded-lg border border-outline-variant text-on-surface hover:bg-surface-container-low transition-colors font-label-md text-label-md disabled:opacity-50">
                        Previous Step
                    </button>
                    <div class="flex gap-sm">
                        <button type="button" x-show="currentStep < 3" @click="currentStep++" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs">
                            Continue
                        </button>
                        <button type="button" x-show="currentStep === 3" @click="submitAssetForm()" :disabled="loading" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center gap-2 disabled:opacity-50">
                            <span x-show="!loading" class="material-symbols-outlined text-[18px]">check_circle</span>
                            <span x-show="loading" class="animate-spin material-symbols-outlined text-[18px]">progress_activity</span>
                            <span x-text="loading ? 'Registering...' : 'Register Asset Stream'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submission Confirmation Card -->
            <div x-show="submitted" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="p-xl bg-surface-container-lowest rounded-xl border border-primary text-center space-y-md shadow-md max-w-[640px] mx-auto"
                 style="display: none;">
                <div class="w-14 h-14 rounded-full bg-[#059669]/10 text-[#059669] mx-auto flex items-center justify-center">
                    <span class="material-symbols-outlined text-[36px]">task_alt</span>
                </div>
                <h3 class="font-headline-lg text-headline-lg text-on-surface">Asset Successfully Mapped</h3>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-[500px] mx-auto leading-relaxed">
                    <strong class="text-on-surface" x-text="registeredVehicle?.code"></strong> (<span x-text="registeredVehicle?.name"></span>) with IMEI <span class="font-data-mono font-bold text-primary" x-text="registeredVehicle?.imei"></span> is now registered and receiving telematics streams.
                </p>
                <div class="flex justify-center gap-sm pt-sm">
                    <a href="{{ route('live-tracking') }}" class="px-5 py-2.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors">View on Live Asset Map</a>
                    <button @click="resetForm()" class="px-5 py-2.5 bg-surface-container-high border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors">Add Another Asset</button>
                </div>
            </div>
        </div>
    </div>
</x-layout>
