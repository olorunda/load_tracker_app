<x-layout title="Live Asset Tracking - FleetManager">
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-64px)] relative overflow-hidden" x-data="liveTrackingApp()">
        <!-- Asset Sidebar Panel -->
        <aside class="w-full lg:w-[380px] bg-surface-container-lowest border-r border-outline-variant flex flex-col shadow-xs shrink-0 h-1/2 lg:h-full z-10">
            <!-- Sidebar Header & Search -->
            <div class="p-md border-b border-outline-variant bg-surface-container-lowest sticky top-0 z-20">
                <div class="flex justify-between items-center mb-xs">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Fleet Telemetry</h2>
                    <span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary font-label-md text-label-md font-bold" x-text="assets.length + ' Live'"></span>
                </div>
                
                <div class="relative mb-sm">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchAssets()" placeholder="Search assets, VIN, drivers..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow"/>
                </div>

                <!-- Status Tabs -->
                <div class="flex border-b border-outline-variant/60 gap-md text-label-md font-label-md">
                    <button @click="setFilter('all')" :class="activeFilter === 'all' ? 'border-b-2 border-primary text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'" class="pb-xs transition-colors">All</button>
                    <button @click="setFilter('loaded')" :class="activeFilter === 'loaded' ? 'border-b-2 border-primary text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'" class="pb-xs transition-colors">Loaded</button>
                    <button @click="setFilter('empty')" :class="activeFilter === 'empty' ? 'border-b-2 border-primary text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'" class="pb-xs transition-colors">Empty</button>
                    <button @click="setFilter('alert')" :class="activeFilter === 'alert' ? 'border-b-2 border-primary text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'" class="pb-xs transition-colors">Alerts</button>
                </div>
            </div>

            <!-- Asset List Container -->
            <div class="flex-1 overflow-y-auto divide-y divide-outline-variant/40 bg-surface-bright">
                <template x-for="asset in assets" :key="asset.id">
                    <div @click="selectAsset(asset)" 
                         :class="selectedAsset && selectedAsset.id === asset.id ? 'bg-surface-container-low border-l-4 border-l-primary' : 'bg-surface-container-lowest hover:bg-surface-container-low'"
                         class="p-md cursor-pointer transition-colors">
                        <div class="flex justify-between items-start mb-xs">
                            <div>
                                <span class="font-headline-sm text-headline-sm text-on-surface font-semibold" x-text="asset.id"></span>
                                <span class="font-body-sm text-body-sm text-on-surface-variant block" x-text="asset.name"></span>
                            </div>
                            <span :class="asset.status === 'Alert' ? 'bg-error-container text-on-error-container' : (asset.status === 'Loaded' ? 'bg-primary/10 text-primary' : 'bg-surface-variant text-on-surface-variant')" 
                                  class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase tracking-wider font-bold"
                                  x-text="asset.status"></span>
                        </div>

                        <div class="grid grid-cols-3 gap-xs text-body-sm text-on-surface-variant mt-sm">
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">speed</span>
                                <span class="font-data-mono" x-text="asset.speed"></span>
                            </div>
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-[#059669]">battery_charging_full</span>
                                <span class="font-data-mono" x-text="asset.battery_voltage"></span>
                            </div>
                            <div class="flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">satellite_alt</span>
                                <span class="font-data-mono" x-text="asset.satellites + ' Sats'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </aside>

        <!-- Map & Asset Detail Area -->
        <div class="flex-1 relative h-1/2 lg:h-full bg-surface-container-low overflow-hidden flex flex-col">
            <div id="live-tracking-map" class="w-full h-full"></div>

            <!-- Trajectory Playback Control Bar -->
            <div x-show="trailPoints.length > 0" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-6"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute top-4 left-4 right-4 lg:left-12 lg:right-auto bg-surface-container-lowest/95 backdrop-blur-md p-md rounded-xl border border-primary shadow-xl z-[1000] flex flex-col gap-sm lg:w-[480px]">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px] animate-spin" x-show="isPlaying">sync</span>
                        <span class="material-symbols-outlined text-primary text-[20px]" x-show="!isPlaying">route</span>
                        <span class="font-headline-sm text-headline-sm text-on-surface font-bold">Trajectory Playback</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-data-mono text-body-sm font-bold text-primary" x-text="'Point ' + (playbackIndex + 1) + ' / ' + trailPoints.length"></span>
                        <button @click="stopTrajectoryPlayback()" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                </div>

                <!-- Playback Slider -->
                <div class="w-full flex items-center gap-2">
                    <input type="range" min="0" :max="trailPoints.length - 1" x-model.number="playbackIndex" @input="updatePlaybackPosition()" class="w-full accent-primary cursor-pointer h-2 bg-surface-container-high rounded-lg"/>
                </div>

                <!-- Control Buttons -->
                <div class="flex justify-between items-center pt-xs">
                    <div class="flex items-center gap-sm">
                        <button @click="stepBackward()" class="p-1.5 bg-surface-container-high text-on-surface rounded-lg hover:bg-surface-container-highest transition-colors">
                            <span class="material-symbols-outlined text-[18px]">skip_previous</span>
                        </button>
                        <button @click="togglePlayTrajectory()" class="px-4 py-1.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-[18px]" x-text="isPlaying ? 'pause' : 'play_arrow'"></span>
                            <span x-text="isPlaying ? 'Pause' : 'Play Trail'"></span>
                        </button>
                        <button @click="stepForward()" class="p-1.5 bg-surface-container-high text-on-surface rounded-lg hover:bg-surface-container-highest transition-colors">
                            <span class="material-symbols-outlined text-[18px]">skip_next</span>
                        </button>
                    </div>

                    <div class="text-body-sm font-data-mono text-on-surface-variant flex items-center gap-2">
                        <span class="font-bold text-on-surface" x-text="currentTrailPoint?.recorded_at"></span>
                        <span class="px-2 py-0.5 rounded bg-primary/10 text-primary font-bold" x-text="currentTrailPoint?.speed + ' km/h'"></span>
                    </div>
                </div>
            </div>

            <!-- Floating Asset Telemetry Overlay Panel -->
            <div x-show="selectedAsset" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute bottom-4 left-4 right-4 lg:left-auto lg:right-4 lg:w-[420px] bg-surface-container-lowest/95 backdrop-blur-md p-md rounded-xl border border-outline-variant shadow-lg z-[1000]"
                 style="display: none;">
                <div class="flex justify-between items-start border-b border-outline-variant pb-xs mb-sm">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-label-md text-label-md text-primary font-bold" x-text="selectedAsset?.id"></span>
                            <span class="text-[11px] font-data-mono px-2 py-0.5 rounded bg-primary/10 text-primary font-semibold" x-text="'IMEI: ' + selectedAsset?.imei"></span>
                        </div>
                        <h3 class="font-headline-md text-headline-md text-on-surface" x-text="selectedAsset?.name"></h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Driver: <strong class="text-on-surface" x-text="selectedAsset?.driver"></strong></p>
                    </div>
                    <button @click="selectedAsset = null" class="p-1 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Hardware IO Telemetry Grid -->
                <div class="grid grid-cols-4 gap-xs mb-sm text-center">
                    <div class="p-xs bg-surface rounded-lg border border-outline-variant/60">
                        <span class="text-[10px] uppercase text-on-surface-variant block">Speed</span>
                        <span class="font-data-mono font-bold text-on-surface text-body-sm" x-text="selectedAsset?.speed"></span>
                    </div>
                    <div class="p-xs bg-surface rounded-lg border border-outline-variant/60">
                        <span class="text-[10px] uppercase text-on-surface-variant block">Battery</span>
                        <span class="font-data-mono font-bold text-[#059669] text-body-sm" x-text="selectedAsset?.battery_voltage"></span>
                    </div>
                    <div class="p-xs bg-surface rounded-lg border border-outline-variant/60">
                        <span class="text-[10px] uppercase text-on-surface-variant block">Ignition</span>
                        <span class="font-data-mono font-bold text-body-sm" :class="selectedAsset?.ignition ? 'text-[#059669]' : 'text-error'" x-text="selectedAsset?.ignition ? 'ON' : 'OFF'"></span>
                    </div>
                    <div class="p-xs bg-surface rounded-lg border border-outline-variant/60">
                        <span class="text-[10px] uppercase text-on-surface-variant block">Motion</span>
                        <span class="font-data-mono font-bold text-primary text-body-sm" x-text="selectedAsset?.movement ? 'Moving' : 'Stopped'"></span>
                    </div>
                </div>

                <!-- Teltonika IO 66 Load Determination Telemetry -->
                <div class="mb-sm p-sm bg-surface-container-low rounded-lg border border-outline-variant/60">
                    <div class="flex items-center justify-between mb-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-primary">scale</span>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Teltonika Load Telemetry (IO 66)</span>
                        </div>
                        <span :class="selectedAsset?.is_loaded ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-surface-variant text-on-surface-variant'" class="px-2 py-0.5 rounded-full font-label-md text-[10px] uppercase font-bold" x-text="selectedAsset?.is_loaded ? 'LOADED' : 'EMPTY'"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-xs text-center">
                        <div class="p-xs bg-surface rounded border border-outline-variant/40">
                            <span class="text-[9px] uppercase text-on-surface-variant block">Base Volt</span>
                            <span class="font-data-mono font-bold text-on-surface text-[12px]" x-text="selectedAsset?.base_voltage || 'N/A'"></span>
                        </div>
                        <div class="p-xs bg-surface rounded border border-outline-variant/40">
                            <span class="text-[9px] uppercase text-on-surface-variant block">Current Volt</span>
                            <span class="font-data-mono font-bold text-primary text-[12px]" x-text="selectedAsset?.external_voltage || 'N/A'"></span>
                        </div>
                        <div class="p-xs bg-surface rounded border border-outline-variant/40">
                            <span class="text-[9px] uppercase text-on-surface-variant block">Delta</span>
                            <span class="font-data-mono font-bold text-[12px]" :class="selectedAsset?.voltage_delta > 0 ? 'text-primary' : 'text-on-surface-variant'" x-text="selectedAsset?.voltage_delta_formatted || '0 mV'"></span>
                        </div>
                    </div>
                    <div class="mt-xs text-[10px] text-on-surface-variant flex items-center justify-between">
                        <span x-text="selectedAsset?.load_summary"></span>
                        <span class="text-[9px] italic text-outline" x-text="selectedAsset?.is_loaded ? 'Δ > 0 mV (Loaded)' : 'Δ ≤ 0 mV (Empty)'"></span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-xs mb-md text-center text-body-sm">
                    <div class="p-xs bg-surface-container-low rounded border border-outline-variant/40">
                        <span class="text-[10px] text-on-surface-variant block">Satellites</span>
                        <span class="font-data-mono font-bold text-on-surface" x-text="selectedAsset?.satellites + ' Sats'"></span>
                    </div>
                    <div class="p-xs bg-surface-container-low rounded border border-outline-variant/40">
                        <span class="text-[10px] text-on-surface-variant block">HDOP</span>
                        <span class="font-data-mono font-bold text-on-surface" x-text="selectedAsset?.hdop"></span>
                    </div>
                    <div class="p-xs bg-surface-container-low rounded border border-outline-variant/40">
                        <span class="text-[10px] text-on-surface-variant block">Odometer</span>
                        <span class="font-data-mono font-bold text-on-surface" x-text="selectedAsset?.odometer"></span>
                    </div>
                </div>

                <div class="text-[11px] text-on-surface-variant mb-md flex justify-between items-center bg-surface-container-low p-2 rounded">
                    <span><strong class="text-on-surface">Last Ping:</strong> <span x-text="selectedAsset?.formatted_time"></span></span>
                    <span x-show="trailLoading" class="text-primary font-bold animate-pulse">Loading Path...</span>
                </div>

                <div class="flex gap-sm">
                    <button @click="loadAndStartTrajectoryPlayback(selectedAsset?.db_id)" class="flex-1 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">route</span> Play Trajectory Trail
                    </button>
                    <a href="{{ route('precision-system') }}" class="px-3 py-2 bg-surface-container-high text-on-surface border border-outline-variant rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">query_stats</span> Diagnostics
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function liveTrackingApp() {
            return {
                searchQuery: '',
                activeFilter: 'all',
                selectedAsset: null,
                assets: [],
                map: null,
                markers: {},
                trailPolyline: null,
                passedPolyline: null,
                playbackMarker: null,
                trailLoading: false,
                trailPoints: [],
                playbackIndex: 0,
                isPlaying: false,
                playbackTimer: null,

                get currentTrailPoint() {
                    return this.trailPoints[this.playbackIndex] || null;
                },

                async fetchAssets() {
                    try {
                        const url = new URL('/api/fleet/assets', window.location.origin);
                        if (this.activeFilter !== 'all') url.searchParams.append('status', this.activeFilter);
                        if (this.searchQuery) url.searchParams.append('q', this.searchQuery);

                        const res = await fetch(url);
                        const json = await res.json();
                        if (json.success) {
                            this.assets = json.assets;
                            this.renderMapMarkers();
                            if (!this.selectedAsset && this.assets.length > 0) {
                                this.selectAsset(this.assets[0]);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch fleet assets', e);
                    }
                },

                setFilter(filter) {
                    this.activeFilter = filter;
                    this.fetchAssets();
                },

                selectAsset(asset) {
                    this.stopTrajectoryPlayback();
                    this.selectedAsset = asset;
                    if (this.map && asset.lat && asset.lng) {
                        this.map.flyTo([asset.lat, asset.lng], 14, { duration: 1.2 });
                        this.loadVehicleTrail(asset.db_id);
                    }
                },

                async loadVehicleTrail(vehicleDbId) {
                    if (!vehicleDbId || !this.map) return;
                    this.trailLoading = true;

                    try {
                        const res = await fetch(`/api/fleet/assets/${vehicleDbId}/trail`);
                        const json = await res.json();

                        if (json.success && json.trail && json.trail.path) {
                            this.trailPoints = json.trail.path;
                            const coords = this.trailPoints.map(pt => [pt.lat, pt.lng]);
                            
                            if (this.trailPolyline) {
                                this.map.removeLayer(this.trailPolyline);
                            }

                            if (coords.length > 0) {
                                this.trailPolyline = L.polyline(coords, {
                                    color: '#004ac6',
                                    weight: 4,
                                    opacity: 0.6,
                                    dashArray: '6, 6',
                                    lineJoin: 'round'
                                }).addTo(this.map);

                                this.map.fitBounds(this.trailPolyline.getBounds(), { padding: [50, 50] });
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load trail', e);
                    } finally {
                        this.trailLoading = false;
                    }
                },

                async loadAndStartTrajectoryPlayback(vehicleDbId) {
                    await this.loadVehicleTrail(vehicleDbId);
                    if (this.trailPoints.length > 0) {
                        this.playbackIndex = 0;
                        this.startPlayback();
                    }
                },

                togglePlayTrajectory() {
                    if (this.isPlaying) {
                        this.pausePlayback();
                    } else {
                        if (this.playbackIndex >= this.trailPoints.length - 1) {
                            this.playbackIndex = 0;
                        }
                        this.startPlayback();
                    }
                },

                startPlayback() {
                    if (this.trailPoints.length === 0) return;
                    this.isPlaying = true;

                    if (this.playbackTimer) {
                        clearInterval(this.playbackTimer);
                    }

                    this.updatePlaybackPosition();

                    this.playbackTimer = setInterval(() => {
                        if (this.playbackIndex < this.trailPoints.length - 1) {
                            this.playbackIndex++;
                            this.updatePlaybackPosition();
                        } else {
                            this.pausePlayback();
                        }
                    }, 350);
                },

                pausePlayback() {
                    this.isPlaying = false;
                    if (this.playbackTimer) {
                        clearInterval(this.playbackTimer);
                        this.playbackTimer = null;
                    }
                },

                stopTrajectoryPlayback() {
                    this.pausePlayback();
                    this.trailPoints = [];
                    this.playbackIndex = 0;

                    if (this.playbackMarker) {
                        this.map.removeLayer(this.playbackMarker);
                        this.playbackMarker = null;
                    }

                    if (this.passedPolyline) {
                        this.map.removeLayer(this.passedPolyline);
                        this.passedPolyline = null;
                    }
                },

                stepForward() {
                    if (this.playbackIndex < this.trailPoints.length - 1) {
                        this.playbackIndex++;
                        this.updatePlaybackPosition();
                    }
                },

                stepBackward() {
                    if (this.playbackIndex > 0) {
                        this.playbackIndex--;
                        this.updatePlaybackPosition();
                    }
                },

                updatePlaybackPosition() {
                    if (!this.map || this.trailPoints.length === 0) return;

                    const point = this.trailPoints[this.playbackIndex];
                    if (!point) return;

                    // 1. Synchronize telemetry drawer overlay values with historical point
                    if (this.selectedAsset) {
                        this.selectedAsset.speed = point.speed + ' km/h';
                        this.selectedAsset.battery_voltage = point.battery_mv + ' mV';
                        this.selectedAsset.ignition = point.ignition;
                        this.selectedAsset.movement = point.movement;
                        this.selectedAsset.satellites = point.satellites;
                        this.selectedAsset.formatted_time = point.recorded_at;
                    }

                    // 2. Draw or update animated moving vehicle marker
                    const color = '#004ac6';
                    const markerHtml = `<div style="background-color: ${color}; width: 26px; height: 26px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;"><span class="material-symbols-outlined" style="color: white; font-size: 14px;">navigation</span></div>`;
                    
                    const customIcon = L.divIcon({
                        html: markerHtml,
                        className: 'custom-animated-playback-marker',
                        iconSize: [26, 26],
                        iconAnchor: [13, 13]
                    });

                    if (!this.playbackMarker) {
                        this.playbackMarker = L.marker([point.lat, point.lng], { icon: customIcon }).addTo(this.map);
                    } else {
                        this.playbackMarker.setLatLng([point.lat, point.lng]);
                        this.playbackMarker.setIcon(customIcon);
                    }

                    // 3. Draw passed polyline up to current playbackIndex
                    const passedCoords = this.trailPoints.slice(0, this.playbackIndex + 1).map(pt => [pt.lat, pt.lng]);
                    if (this.passedPolyline) {
                        this.map.removeLayer(this.passedPolyline);
                    }
                    if (passedCoords.length > 1) {
                        this.passedPolyline = L.polyline(passedCoords, {
                            color: '#059669',
                            weight: 5,
                            opacity: 0.9,
                            lineJoin: 'round'
                        }).addTo(this.map);
                    }

                    // 4. Smoothly pan map
                    this.map.panTo([point.lat, point.lng]);
                },

                renderMapMarkers() {
                    if (!this.map || typeof L === 'undefined') return;

                    // Clear previous markers
                    Object.values(this.markers).forEach(m => this.map.removeLayer(m));
                    this.markers = {};

                    this.assets.forEach(asset => {
                        if (!asset.lat || !asset.lng) return;

                        const color = asset.status === 'Alert' ? '#ba1a1a' : (asset.status === 'Loaded' ? '#004ac6' : '#505f76');
                        const markerHtml = `<div style="background-color: ${color}; width: 22px; height: 22px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center;"><div style="width: 8px; height: 8px; background-color: white; border-radius: 50%;"></div></div>`;
                        
                        const customIcon = L.divIcon({
                            html: markerHtml,
                            className: 'custom-live-marker',
                            iconSize: [22, 22],
                            iconAnchor: [11, 11]
                        });

                        const marker = L.marker([asset.lat, asset.lng], { icon: customIcon })
                            .addTo(this.map)
                            .on('click', () => {
                                this.selectAsset(asset);
                            });

                        this.markers[asset.id] = marker;
                    });
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof L === 'undefined') return;

                        // Center default on Nigeria Teltonika coordinates from dump (lat ~ 6.5802, lng ~ 3.2932)
                        this.map = L.map('live-tracking-map', { zoomControl: false }).setView([6.5802, 3.2932], 13);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png?key=cb1_4c3e_1_e8878c7b595a5fe459c526a1', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap &copy; CARTO'
                        }).addTo(this.map);

                        L.control.zoom({ position: 'topright' }).addTo(this.map);

                        this.fetchAssets();
                    });
                }
            }
        }
    </script>
</x-layout>
