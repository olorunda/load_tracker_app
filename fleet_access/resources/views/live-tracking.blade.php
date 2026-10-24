<x-layout title="Live Asset Tracking - FleetManager">
    <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-64px)] max-h-[calc(100vh-64px)] min-h-0 relative overflow-hidden bg-surface-container-low"
         x-data="liveTrackingApp()"
         @keydown.window="handleKeydown($event)">

        <!-- Fleet List Panel -->
        <aside class="order-3 lg:order-none w-full lg:w-[300px] 2xl:w-[340px] flex-1 lg:flex-none min-h-0 bg-surface-container-lowest border-r border-outline-variant flex-col shadow-xs shrink-0 z-10"
               :class="listPaneClass">
            <div class="p-md pb-sm border-b border-outline-variant space-y-sm">
                <div class="flex justify-between items-center">
                    <h2 class="font-headline-md text-headline-md text-on-surface">Live Fleet</h2>
                    <button @click="toggleAutoRefresh()"
                            :class="autoRefreshEnabled ? 'bg-emerald-500/10 text-emerald-700 border-emerald-500/30' : 'bg-surface-variant text-on-surface-variant border-outline-variant'"
                            class="px-2.5 py-1 rounded-full border text-[12px] font-semibold flex items-center gap-1.5 transition-colors"
                            :title="autoRefreshEnabled ? 'Live updates every 5s. Click to pause.' : 'Updates paused. Click to resume.'">
                        <span class="w-2 h-2 rounded-full" :class="fetchError ? 'bg-error' : (autoRefreshEnabled ? 'bg-emerald-500 animate-pulse' : 'bg-on-surface-variant/40')"></span>
                        <span x-text="fetchError ? 'Reconnecting…' : (autoRefreshEnabled ? 'Live · ' + updatedAgo : 'Paused')"></span>
                    </button>
                </div>

                <div class="relative">
                    <span class="material-symbols-outlined text-[20px] absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" x-ref="search" x-model="searchQuery" placeholder="Search vehicle or driver" title="Search by code, name, driver or IMEI"
                           class="w-full pl-10 pr-9 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none"/>
                    <button x-show="searchQuery" @click="searchQuery = ''; $refs.search.focus()" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-full text-on-surface-variant hover:bg-surface-container-high" title="Clear search">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                    <kbd x-show="!searchQuery" class="hidden lg:block absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-on-surface-variant border border-outline-variant rounded px-1.5">/</kbd>
                </div>

                <div class="flex flex-wrap gap-xs">
                    <template x-for="f in filters" :key="f.value">
                        <button @click="activeFilter = f.value"
                                :class="activeFilter === f.value ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:bg-surface-container-low'"
                                class="px-3 py-1 rounded-full border text-[12px] font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <span x-text="f.label"></span>
                            <span class="font-data-mono text-[11px] opacity-80" x-text="countFor(f.value)"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto" x-ref="list">
                <!-- Initial loading skeleton -->
                <template x-if="!hasLoaded">
                    <div class="divide-y divide-outline-variant/40">
                        <template x-for="i in 4" :key="i">
                            <div class="p-md animate-pulse space-y-2">
                                <div class="h-4 w-1/3 bg-surface-container-high rounded"></div>
                                <div class="h-3 w-2/3 bg-surface-container rounded"></div>
                            </div>
                        </template>
                    </div>
                </template>

                <ul class="divide-y divide-outline-variant/40">
                    <template x-for="asset in filteredAssets" :key="asset.db_id">
                        <li>
                            <button type="button" @click="selectAsset(asset.db_id)" :id="'asset-row-' + asset.db_id"
                                    :class="selectedId === asset.db_id ? 'bg-secondary-container/50 border-l-primary' : 'border-l-transparent hover:bg-surface-container-low'"
                                    class="w-full text-left px-md py-sm border-l-4 transition-colors flex items-center gap-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                <span class="relative shrink-0 w-3 h-3">
                                    <span class="absolute inset-0 rounded-full" :style="'background-color:' + statusColor(asset)"></span>
                                    <span x-show="asset.speed_raw > 0" class="absolute inset-0 rounded-full animate-ping opacity-60" :style="'background-color:' + statusColor(asset)"></span>
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-center gap-xs">
                                        <span class="font-semibold text-on-surface text-[15px]" x-text="asset.id"></span>
                                        <span :class="statusBadge(asset)" class="px-1.5 py-px rounded text-[10px] uppercase tracking-wider font-bold" x-text="asset.status"></span>
                                    </span>
                                    <span class="block text-body-sm text-on-surface-variant truncate" x-text="asset.name + ' · ' + asset.driver"></span>
                                </span>
                                <span class="text-right shrink-0">
                                    <span class="block font-data-mono font-bold text-on-surface" x-text="asset.speed_raw"></span>
                                    <span class="block text-[11px] text-on-surface-variant" x-text="asset.speed_raw > 0 ? 'km/h' : ago(asset.last_ping)"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>

                <div x-show="hasLoaded && filteredAssets.length === 0" class="p-lg text-center text-body-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-[32px] block mb-xs text-outline">search_off</span>
                    <p>No vehicles match.</p>
                    <button @click="searchQuery = ''; activeFilter = 'all'" class="mt-sm text-primary font-semibold hover:underline">Clear filters</button>
                </div>
            </div>
        </aside>

        <!-- Map -->
        <div class="order-1 lg:order-none relative h-[45vh] lg:h-full lg:flex-1 min-w-0 shrink-0 overflow-hidden">
            <div id="live-tracking-map" class="w-full h-full"></div>

            <!-- Map Toolbar -->
            <div class="absolute top-3 right-3 z-[1000] flex flex-col gap-1 bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant rounded-xl shadow-md p-1">
                <button @click="fitFleet()" title="Show whole fleet" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high">
                    <span class="material-symbols-outlined text-[20px]">zoom_out_map</span>
                </button>
                <button @click="toggleFollow()" :disabled="!selectedAsset"
                        :class="followSelected && selectedAsset ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high'"
                        :title="followSelected ? 'Stop following vehicle' : 'Follow selected vehicle'"
                        class="p-2 rounded-lg disabled:opacity-40 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">my_location</span>
                </button>
                <button @click="toggleTrail()" :disabled="!selectedAsset"
                        :class="showTrail && selectedAsset ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:bg-surface-container-high'"
                        :title="showTrail ? 'Hide route history' : 'Show route history'"
                        class="p-2 rounded-lg disabled:opacity-40 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">timeline</span>
                </button>
            </div>

            <!-- Connection Banner -->
            <div x-show="fetchError" x-transition style="display: none;"
                 class="absolute top-3 left-1/2 -translate-x-1/2 z-[1000] px-3 py-1.5 rounded-full bg-error text-on-error text-[12px] font-semibold shadow-md flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">cloud_off</span> Connection lost. Retrying…
            </div>

            <!-- Legend -->
            <div class="absolute bottom-3 left-3 z-[1000] bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant rounded-lg shadow-xs px-2.5 py-1.5 text-[11px] text-on-surface-variant flex flex-wrap gap-x-3 gap-y-1 max-w-[calc(100%-1.5rem)]"
                 x-show="!playback.active">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full" style="background:#004ac6"></span>Loaded</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full" style="background:#505f76"></span>Empty</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full" style="background:#ba1a1a"></span>Alert</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rotate-45" style="background:#7c3aed"></span>Unauthorized load event</span>
            </div>

            <!-- Trajectory Playback Bar -->
            <div x-show="playback.active" x-transition style="display: none;"
                 class="absolute bottom-3 left-3 right-3 lg:left-1/2 lg:right-auto lg:-translate-x-1/2 lg:w-[min(560px,calc(100%-1.5rem))] z-[1000] bg-surface-container-lowest/95 backdrop-blur-md border border-primary/40 rounded-xl shadow-xl p-sm space-y-xs">
                <div class="flex items-center gap-sm">
                    <button @click="stepPlayback(-1)" class="p-1.5 rounded-lg hover:bg-surface-container-high text-on-surface" title="Previous point (←)">
                        <span class="material-symbols-outlined text-[20px]">skip_previous</span>
                    </button>
                    <button @click="togglePlay()" class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-xs hover:bg-primary/90" :title="playback.playing ? 'Pause (space)' : 'Play (space)'">
                        <span class="material-symbols-outlined text-[24px]" x-text="playback.playing ? 'pause' : 'play_arrow'"></span>
                    </button>
                    <button @click="stepPlayback(1)" class="p-1.5 rounded-lg hover:bg-surface-container-high text-on-surface" title="Next point (→)">
                        <span class="material-symbols-outlined text-[20px]">skip_next</span>
                    </button>

                    <div class="flex-1 min-w-0 px-xs">
                        <div class="flex justify-between text-[12px]">
                            <span class="font-semibold text-on-surface truncate" x-text="playbackPoint?.recorded_at"></span>
                            <span class="font-data-mono text-[12px] text-on-surface-variant" x-text="(playback.index + 1) + ' / ' + trailPoints.length"></span>
                        </div>
                        <div class="flex gap-sm text-[12px] text-on-surface-variant">
                            <span><strong class="text-on-surface" x-text="(playbackPoint?.speed ?? 0) + ' km/h'"></strong></span>
                            <span x-text="'Ignition ' + (playbackPoint?.ignition ? 'on' : 'off')"></span>
                        </div>
                    </div>

                    <select x-model.number="playback.speed" @change="restartPlayTimer()" class="text-[12px] font-semibold border border-outline-variant rounded-lg py-1 pl-2 pr-6 bg-surface" title="Playback speed">
                        <option value="1">1×</option>
                        <option value="2">2×</option>
                        <option value="4">4×</option>
                        <option value="8">8×</option>
                    </select>
                    <button @click="fitTrail()" class="p-1.5 rounded-lg hover:bg-surface-container-high text-on-surface-variant" title="Fit route">
                        <span class="material-symbols-outlined text-[20px]">fit_screen</span>
                    </button>
                    <button @click="exitPlayback()" class="p-1.5 rounded-lg hover:bg-surface-container-high text-on-surface-variant" title="Exit playback (Esc)">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <input type="range" min="0" :max="Math.max(trailPoints.length - 1, 0)" x-model.number="playback.index" @input="pausePlay(); renderPlaybackFrame()"
                       class="w-full accent-primary cursor-pointer"/>
            </div>
        </div>

        <!-- Mobile Pane Switcher -->
        <div class="order-2 lg:hidden flex border-b border-outline-variant bg-surface-container-lowest">
            <button @click="mobilePane = 'list'" :class="mobilePane === 'list' ? 'text-primary border-primary' : 'text-on-surface-variant border-transparent'"
                    class="flex-1 py-2 text-body-sm font-semibold border-b-2" x-text="'Fleet (' + assets.length + ')'"></button>
            <button @click="mobilePane = 'detail'" :disabled="!selectedAsset" :class="mobilePane === 'detail' ? 'text-primary border-primary' : 'text-on-surface-variant border-transparent'"
                    class="flex-1 py-2 text-body-sm font-semibold border-b-2 disabled:opacity-40" x-text="selectedAsset ? selectedAsset.id : 'Details'"></button>
        </div>

        <!-- Vehicle Detail Panel -->
        <aside class="order-3 lg:order-none w-full lg:w-[360px] 2xl:w-[380px] flex-1 lg:flex-none min-h-0 bg-surface-container-lowest border-l border-outline-variant flex-col shrink-0 z-10"
               :class="selectedAsset ? (mobilePane === 'detail' ? 'flex' : 'hidden lg:flex') : 'hidden'">
            <template x-if="selectedAsset">
                <div class="flex flex-col h-full min-h-0">
                    <!-- Header -->
                    <div class="p-md border-b border-outline-variant">
                        <div class="flex justify-between items-start gap-sm">
                            <div class="min-w-0">
                                <div class="flex items-center gap-xs">
                                    <h3 class="font-headline-md text-headline-md text-on-surface" x-text="selectedAsset.id"></h3>
                                    <span :class="statusBadge(selectedAsset)" class="px-2 py-0.5 rounded-full text-[11px] uppercase tracking-wider font-bold" x-text="selectedAsset.status"></span>
                                </div>
                                <p class="text-body-sm text-on-surface-variant truncate" x-text="selectedAsset.name"></p>
                                <p class="text-body-sm text-on-surface-variant">Driver: <strong class="text-on-surface" x-text="selectedAsset.driver"></strong></p>
                            </div>
                            <button @click="clearSelection()" class="p-1.5 rounded-full text-on-surface-variant hover:bg-surface-container-high" title="Close (Esc)">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <!-- Primary Stats -->
                        <div class="grid grid-cols-3 gap-sm mt-md">
                            <div>
                                <div class="text-[11px] uppercase tracking-wider text-on-surface-variant">Speed</div>
                                <div class="text-on-surface"><span class="text-[22px] leading-7 font-bold tabular-nums" x-text="selectedAsset.speed_raw"></span> <span class="text-body-sm">km/h</span></div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wider text-on-surface-variant">Ignition</div>
                                <div class="text-[16px] font-bold mt-1" :class="selectedAsset.ignition ? 'text-[#059669]' : 'text-on-surface-variant'" x-text="selectedAsset.ignition ? 'On' : 'Off'"></div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wider text-on-surface-variant">Last seen</div>
                                <div class="text-[16px] font-bold mt-1 text-on-surface" x-text="ago(selectedAsset.last_ping)" :title="selectedAsset.formatted_time"></div>
                            </div>
                        </div>

                        <div class="flex gap-sm mt-md">
                            <button @click="startPlayback()" :disabled="trailLoading || trailPoints.length === 0"
                                    class="flex-1 py-2 bg-primary text-on-primary rounded-lg text-label-md font-label-md hover:bg-primary/90 shadow-xs flex items-center justify-center gap-1 disabled:opacity-50">
                                <span class="material-symbols-outlined text-[18px]" x-text="trailLoading ? 'hourglass_top' : 'play_circle'"></span>
                                <span x-text="trailLoading ? 'Loading route…' : 'Replay route'"></span>
                            </button>
                            <button @click="centerOnSelected()" class="px-3 py-2 border border-outline-variant rounded-lg text-on-surface hover:bg-surface-container-low" title="Center on map">
                                <span class="material-symbols-outlined text-[18px]">center_focus_strong</span>
                            </button>
                            <a href="{{ route('precision-system') }}" class="px-3 py-2 border border-outline-variant rounded-lg text-on-surface hover:bg-surface-container-low" title="Diagnostics">
                                <span class="material-symbols-outlined text-[18px]">query_stats</span>
                            </a>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-md space-y-md">
                        <!-- Load (IO 66) -->
                        <section class="space-y-sm">
                            <div class="flex items-center justify-between">
                                <h4 class="flex items-center gap-1.5 text-label-md font-label-md uppercase text-on-surface">
                                    <span class="material-symbols-outlined text-[18px] text-primary">scale</span> Load
                                </h4>
                                <button @click="openCalibration()" class="text-[12px] font-semibold text-primary hover:underline flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[16px]">tune</span> Calibrate
                                </button>
                            </div>

                            <template x-if="loadGauge">
                                <div class="space-y-xs">
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-[18px] font-bold" :class="selectedAsset.is_loaded ? 'text-primary' : 'text-on-surface-variant'" x-text="selectedAsset.is_loaded ? 'Loaded' : 'Empty'"></span>
                                        <span class="font-data-mono text-body-sm text-on-surface" x-text="selectedAsset.external_voltage + ' (' + selectedAsset.voltage_increase_pct_formatted + ')'"></span>
                                    </div>
                                    <div class="relative h-2.5 rounded-full bg-surface-container-high overflow-visible">
                                        <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-700" :class="selectedAsset.is_loaded ? 'bg-primary' : 'bg-secondary'" :style="'width:' + loadGauge.current + '%'"></div>
                                        <div class="absolute -top-1 -bottom-1 w-0.5 bg-on-surface" :style="'left:' + loadGauge.threshold + '%'" title="Loaded threshold"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-on-surface-variant">
                                        <span x-text="'Base ' + selectedAsset.base_voltage"></span>
                                        <span x-text="'Loaded above ' + selectedAsset.load_threshold_voltage"></span>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!loadGauge" class="text-body-sm text-on-surface-variant">
                                Not calibrated yet. Set the empty base voltage to start detecting load.
                            </p>

                            <!-- Calibration Form -->
                            <div x-show="calibration.open" x-transition class="p-sm rounded-lg bg-surface-container-low border border-outline-variant/60 space-y-sm">
                                <div class="grid grid-cols-2 gap-sm">
                                    <label class="block">
                                        <span class="text-[11px] uppercase text-on-surface-variant">Empty base (mV)</span>
                                        <input type="number" x-model="calibration.base" placeholder="e.g. 11520" class="mt-0.5 w-full px-2 py-1.5 font-data-mono rounded-lg border border-outline-variant bg-surface outline-none focus:border-primary"/>
                                    </label>
                                    <label class="block">
                                        <span class="text-[11px] uppercase text-on-surface-variant">Loaded above (+%)</span>
                                        <input type="number" step="0.1" min="0" x-model="calibration.pct" placeholder="0" class="mt-0.5 w-full px-2 py-1.5 font-data-mono rounded-lg border border-outline-variant bg-surface outline-none focus:border-primary"/>
                                    </label>
                                </div>
                                <button type="button" @click="calibration.base = selectedAsset.external_voltage_raw || calibration.base" class="text-[12px] text-primary font-semibold hover:underline">
                                    Use current reading as empty base
                                </button>
                                <div class="flex items-center justify-between">
                                    <span class="text-[12px]" :class="calibration.error ? 'text-error' : 'text-[#059669]'" x-text="calibration.message"></span>
                                    <div class="flex gap-xs">
                                        <button type="button" @click="calibration.open = false" class="px-3 py-1.5 rounded-lg border border-outline-variant text-[12px] font-semibold">Cancel</button>
                                        <button type="button" @click="saveCalibration()" :disabled="calibration.saving" class="px-3 py-1.5 rounded-lg bg-primary text-on-primary text-[12px] font-semibold disabled:opacity-60" x-text="calibration.saving ? 'Saving…' : 'Save'"></button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Current Trip -->
                        <section class="space-y-sm pt-md border-t border-outline-variant/60">
                            <div class="flex items-center justify-between">
                                <h4 class="flex items-center gap-1.5 text-label-md font-label-md uppercase text-on-surface">
                                    <span class="material-symbols-outlined text-[18px] text-primary">route</span> Current Trip
                                </h4>
                                <a href="{{ route('trips') }}" class="text-[12px] font-semibold text-primary hover:underline">All trips</a>
                            </div>
                            <template x-if="openTrip">
                                <div class="p-sm rounded-lg border space-y-1"
                                     :class="openTrip.unauthorized === 'confirmed' ? 'bg-error/5 border-error/40' : 'bg-surface-container-low border-outline-variant/60'">
                                    <div class="flex justify-between items-center">
                                        <span class="font-data-mono font-bold text-on-surface" x-text="openTrip.reference"></span>
                                        <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[11px] font-bold" x-text="tripStatusLabel(openTrip.status)"></span>
                                    </div>
                                    <div class="text-body-sm text-on-surface-variant" x-text="'Loaded ' + openTrip.loaded_at"></div>
                                    <div class="text-body-sm text-on-surface-variant" x-text="openTrip.distance_km + ' km · ' + openTrip.duration + ' · baseline ' + openTrip.baseline_load"></div>
                                    <div x-show="openTrip.unauthorized" class="text-body-sm font-bold flex items-center gap-1 pt-1"
                                         :class="openTrip.unauthorized === 'confirmed' ? 'text-error' : 'text-[#b45309]'">
                                        <span class="material-symbols-outlined text-[18px]">report</span>
                                        <span x-text="openTrip.unauthorized === 'confirmed' ? 'Unauthorized loading confirmed' : 'Suspected unauthorized loading'"></span>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!openTrip" class="text-body-sm text-on-surface-variant"
                               x-text="loadEvents.length > 0 ? ('No trip in progress. Last event: ' + loadEvents[0].label + ', ' + loadEvents[0].time_ago + '.') : 'No trip in progress.'"></p>

                            <ul x-show="theftEvents.length > 0" class="space-y-1">
                                <template x-for="e in theftEvents.slice(0, 5)" :key="e.id">
                                    <li>
                                        <button @click="focusPoint(e.lat, e.lng)" class="w-full text-left flex gap-sm p-xs rounded-lg hover:bg-surface-container-low">
                                            <span class="material-symbols-outlined text-[18px]" :style="'color:' + e.color" x-text="e.icon"></span>
                                            <span class="min-w-0">
                                                <span class="block text-body-sm font-semibold text-on-surface" x-text="e.label"></span>
                                                <span class="block text-[12px] text-on-surface-variant" x-text="e.occurred_at + ' · ' + e.load_value"></span>
                                            </span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </section>

                        <!-- Device -->
                        <section class="space-y-sm pt-md border-t border-outline-variant/60">
                            <h4 class="flex items-center gap-1.5 text-label-md font-label-md uppercase text-on-surface">
                                <span class="material-symbols-outlined text-[18px] text-primary">developer_board</span> Device
                            </h4>
                            <dl class="grid grid-cols-2 gap-x-md gap-y-sm text-body-sm">
                                <div><dt class="text-on-surface-variant">Backup battery</dt><dd class="font-data-mono text-on-surface" x-text="selectedAsset.battery_voltage + ' (' + selectedAsset.battery_pct + ')'"></dd></div>
                                <div><dt class="text-on-surface-variant">GNSS</dt><dd class="font-data-mono text-on-surface" x-text="selectedAsset.satellites + ' sats · HDOP ' + selectedAsset.hdop"></dd></div>
                                <div><dt class="text-on-surface-variant">Odometer</dt><dd class="font-data-mono text-on-surface" x-text="selectedAsset.odometer"></dd></div>
                                <div><dt class="text-on-surface-variant">Motion</dt><dd class="text-on-surface" x-text="selectedAsset.movement ? 'Moving' : 'Stationary'"></dd></div>
                                <div class="col-span-2"><dt class="text-on-surface-variant">IMEI</dt><dd class="font-data-mono text-on-surface" x-text="selectedAsset.imei"></dd></div>
                                <div class="col-span-2"><dt class="text-on-surface-variant">Last fix</dt><dd class="text-on-surface" x-text="selectedAsset.formatted_time"></dd></div>
                            </dl>
                        </section>
                    </div>
                </div>
            </template>
        </aside>
    </div>

    <script>
        function liveTrackingApp() {
            // Leaflet objects stay outside Alpine's reactive proxy to keep map updates cheap
            const layers = { markers: {}, markerKeys: {}, trail: null, passed: null, playbackMarker: null, loadEvents: [] };

            return {
                searchQuery: '',
                activeFilter: 'all',
                filters: [
                    { value: 'all', label: 'All' },
                    { value: 'moving', label: 'Moving' },
                    { value: 'loaded', label: 'Loaded' },
                    { value: 'empty', label: 'Empty' },
                    { value: 'alert', label: 'Alerts' },
                ],
                assets: [],
                selectedId: null,
                hasLoaded: false,
                isFetching: false,
                fetchError: false,
                lastRefreshedAt: null,
                now: Date.now(),
                autoRefreshEnabled: true,
                autoRefreshInterval: 5000,
                autoRefreshTimer: null,
                followSelected: false,
                showTrail: true,
                mobilePane: 'list',
                map: null,
                trailPoints: [],
                trailLoading: false,
                playback: { active: false, playing: false, index: 0, speed: 1, timer: null },
                openTrip: null,
                loadEvents: [],
                calibration: { open: false, base: '', pct: 0, saving: false, message: '', error: false },

                get selectedAsset() {
                    return this.assets.find(a => a.db_id === this.selectedId) || null;
                },

                get filteredAssets() {
                    const q = this.searchQuery.trim().toLowerCase();
                    return this.assets.filter(a => this.matchesFilter(a, this.activeFilter) && (!q ||
                        [a.id, a.name, a.driver, a.imei].some(v => (v || '').toLowerCase().includes(q))));
                },

                // Mobile shows one pane at a time; between lg and xl the list yields to the detail panel
                get listPaneClass() {
                    if (!this.selectedAsset) return 'flex';
                    return this.mobilePane === 'list' ? 'flex lg:hidden xl:flex' : 'hidden xl:flex';
                },

                get playbackPoint() {
                    return this.trailPoints[this.playback.index] || null;
                },

                get theftEvents() {
                    return this.loadEvents.filter(e => e.type.includes('unauthorized') && e.type !== 'unauthorized_loading_dismissed');
                },

                get loadGauge() {
                    const a = this.selectedAsset;
                    if (!a || !a.base_voltage_raw || !a.load_threshold_voltage_raw) return null;

                    const min = a.base_voltage_raw * 0.97;
                    const max = Math.max(a.load_threshold_voltage_raw, a.external_voltage_raw || 0) * 1.03;
                    const pct = v => Math.min(100, Math.max(0, ((v - min) / (max - min)) * 100));

                    return { current: pct(a.external_voltage_raw || min), threshold: pct(a.load_threshold_voltage_raw) };
                },

                get updatedAgo() {
                    if (!this.lastRefreshedAt) return 'connecting';
                    const s = Math.max(0, Math.round((this.now - this.lastRefreshedAt) / 1000));
                    return s < 2 ? 'just now' : s + 's ago';
                },

                matchesFilter(a, filter) {
                    const status = (a.status || '').toLowerCase();
                    return filter === 'all'
                        || (filter === 'moving' && a.speed_raw > 0)
                        || filter === status;
                },

                countFor(filter) {
                    return this.assets.filter(a => this.matchesFilter(a, filter)).length;
                },

                statusColor(a) {
                    return a.status === 'Alert' ? '#ba1a1a' : (a.status === 'Loaded' ? '#004ac6' : '#505f76');
                },

                statusBadge(a) {
                    return a.status === 'Alert' ? 'bg-error-container text-on-error-container'
                        : (a.status === 'Loaded' ? 'bg-primary/10 text-primary' : 'bg-surface-variant text-on-surface-variant');
                },

                tripStatusLabel(status) {
                    return { pending: 'Confirming', active: 'In transit', closing: 'Unloading' }[status] || status;
                },

                ago(iso) {
                    if (!iso) return 'never';
                    const s = Math.max(0, Math.round((this.now - new Date(iso).getTime()) / 1000));
                    if (s < 60) return s + 's ago';
                    if (s < 3600) return Math.round(s / 60) + 'm ago';
                    if (s < 86400) return Math.round(s / 3600) + 'h ago';
                    return Math.round(s / 86400) + 'd ago';
                },

                // ---- Data -------------------------------------------------------------

                async fetchAssets() {
                    if (this.isFetching) return;
                    this.isFetching = true;

                    try {
                        const res = await fetch('/api/fleet/assets', { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const json = await res.json();
                        if (!json.success || !Array.isArray(json.assets)) throw new Error('Malformed payload');

                        const isFirstLoad = !this.hasLoaded;
                        this.mergeAssets(json.assets);
                        this.fetchError = false;
                        this.lastRefreshedAt = Date.now();
                        this.hasLoaded = true;
                        this.renderMarkers();
                        this.extendLiveTrail();

                        if (isFirstLoad) this.fitFleet(false);
                        if (this.followSelected && this.selectedAsset && !this.playback.active) {
                            this.map?.panTo([this.selectedAsset.lat, this.selectedAsset.lng], { animate: true, duration: 1 });
                        }
                    } catch (e) {
                        console.error('Failed to fetch fleet assets', e);
                        this.fetchError = true;
                    } finally {
                        this.isFetching = false;
                    }
                },

                // Update existing objects in place so the list and detail panel never re-render from scratch
                mergeAssets(incoming) {
                    const existing = new Map(this.assets.map(a => [a.db_id, a]));
                    this.assets = incoming.map(next => {
                        const current = existing.get(next.db_id);
                        return current ? Object.assign(current, next) : next;
                    });
                },

                startAutoRefresh() {
                    this.stopAutoRefresh();
                    if (this.autoRefreshEnabled) {
                        this.autoRefreshTimer = setInterval(() => this.fetchAssets(), this.autoRefreshInterval);
                    }
                },

                stopAutoRefresh() {
                    clearInterval(this.autoRefreshTimer);
                    this.autoRefreshTimer = null;
                },

                toggleAutoRefresh() {
                    this.autoRefreshEnabled = !this.autoRefreshEnabled;
                    if (this.autoRefreshEnabled) this.fetchAssets();
                    this.startAutoRefresh();
                },

                // ---- Selection --------------------------------------------------------

                selectAsset(id) {
                    if (this.selectedId === id) {
                        this.centerOnSelected();
                        return;
                    }

                    this.exitPlayback();
                    this.selectedId = id;
                    this.mobilePane = 'detail';
                    this.calibration.open = false;
                    this.openTrip = null;
                    this.loadEvents = [];
                    this.renderMarkers();
                    this.centerOnSelected();
                    this.loadTrail(id);
                    this.loadVehicleLoadEvents(id);
                    this.$nextTick(() => {
                        document.getElementById('asset-row-' + id)?.scrollIntoView({ block: 'nearest' });
                        this.resizeMap();
                    });
                },

                clearSelection() {
                    this.exitPlayback();
                    this.selectedId = null;
                    this.followSelected = false;
                    this.mobilePane = 'list';
                    this.trailPoints = [];
                    this.clearTrailLayers();
                    this.clearLoadEventLayers();
                    this.renderMarkers();
                    this.$nextTick(() => this.resizeMap());
                },

                moveSelection(step) {
                    const list = this.filteredAssets;
                    if (list.length === 0) return;
                    const index = list.findIndex(a => a.db_id === this.selectedId);
                    const next = list[Math.min(list.length - 1, Math.max(0, index === -1 ? 0 : index + step))];
                    this.selectAsset(next.db_id);
                },

                // ---- Map controls -----------------------------------------------------

                centerOnSelected() {
                    const a = this.selectedAsset;
                    if (this.map && a?.lat && a?.lng) {
                        this.map.flyTo([a.lat, a.lng], Math.max(this.map.getZoom(), 14), { duration: 0.8 });
                    }
                },

                focusPoint(lat, lng) {
                    if (this.map && lat && lng) {
                        this.followSelected = false;
                        this.map.flyTo([lat, lng], 16, { duration: 0.8 });
                    }
                },

                fitFleet(animate = true) {
                    const points = this.assets.filter(a => a.lat && a.lng).map(a => [a.lat, a.lng]);
                    if (!this.map || points.length === 0) return;
                    this.followSelected = false;
                    this.map.fitBounds(L.latLngBounds(points), { padding: [60, 60], maxZoom: 15, animate });
                },

                toggleFollow() {
                    this.followSelected = !this.followSelected;
                    if (this.followSelected) this.centerOnSelected();
                },

                toggleTrail() {
                    this.showTrail = !this.showTrail;
                    if (!layers.trail) return;
                    this.showTrail ? layers.trail.addTo(this.map) : layers.trail.remove();
                },

                resizeMap() {
                    if (this.map) setTimeout(() => this.map.invalidateSize({ pan: false }), 50);
                },

                handleKeydown(e) {
                    const typing = ['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName);

                    if (e.key === '/' && !typing) {
                        e.preventDefault();
                        this.mobilePane = 'list';
                        this.$refs.search.focus();
                    } else if (e.key === 'Escape') {
                        if (typing) {
                            e.target.blur();
                        } else if (this.playback.active) {
                            this.exitPlayback();
                        } else if (this.selectedId) {
                            this.clearSelection();
                        }
                    } else if (typing || (e.key === ' ' && e.target.tagName === 'BUTTON')) {
                        return;
                    } else if (this.playback.active && (e.key === ' ' || e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
                        e.preventDefault();
                        e.key === ' ' ? this.togglePlay() : this.stepPlayback(e.key === 'ArrowLeft' ? -1 : 1);
                    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.moveSelection(e.key === 'ArrowDown' ? 1 : -1);
                    }
                },

                // ---- Markers ----------------------------------------------------------

                markerHtml(a, selected) {
                    const color = this.statusColor(a);
                    const size = selected ? 34 : 26;
                    const moving = a.speed_raw > 0;
                    const ring = selected ? `box-shadow: 0 0 0 6px ${color}33, 0 3px 10px rgba(0,0,0,0.35);` : 'box-shadow: 0 2px 8px rgba(0,0,0,0.3);';
                    // North-pointing arrow rotated to the GPS heading (degrees clockwise from north)
                    const arrow = selected ? 16 : 12;
                    const glyph = moving
                        ? `<svg width="${arrow}" height="${arrow}" viewBox="0 0 24 24" style="transform:rotate(${a.heading || 0}deg)"><path d="M12 2 L20 21 L12 16.5 L4 21 Z" fill="white"/></svg>`
                        : `<span style="width:8px;height:8px;background:white;border-radius:50%"></span>`;

                    return {
                        size,
                        html: `<div style="background:${color};width:${size}px;height:${size}px;border-radius:50%;border:3px solid white;${ring}display:flex;align-items:center;justify-content:center;">${glyph}</div>`,
                    };
                },

                renderMarkers() {
                    if (!this.map) return;

                    const ids = new Set(this.assets.map(a => a.db_id));
                    Object.keys(layers.markers).forEach(id => {
                        if (!ids.has(Number(id))) {
                            layers.markers[id].remove();
                            delete layers.markers[id];
                            delete layers.markerKeys[id];
                        }
                    });

                    this.assets.forEach(a => {
                        if (!a.lat || !a.lng) return;

                        const selected = a.db_id === this.selectedId;
                        const key = [a.status, selected, a.speed_raw > 0, a.heading].join('|');
                        let marker = layers.markers[a.db_id];

                        if (!marker) {
                            const { size, html } = this.markerHtml(a, selected);
                            const icon = L.divIcon({ html, className: 'live-asset-marker', iconSize: [size, size], iconAnchor: [size / 2, size / 2] });
                            marker = L.marker([a.lat, a.lng], { icon, keyboard: false })
                                .addTo(this.map)
                                .on('click', () => this.selectAsset(a.db_id));
                            marker.bindTooltip(a.id, { direction: 'top', offset: [0, -16] });
                            layers.markers[a.db_id] = marker;
                        } else {
                            const pos = marker.getLatLng();
                            if (pos.lat !== a.lat || pos.lng !== a.lng) marker.setLatLng([a.lat, a.lng]);
                        }

                        // Only swap the icon when its appearance changes, so position updates can glide
                        if (layers.markerKeys[a.db_id] !== key) {
                            const { size, html } = this.markerHtml(a, selected);
                            marker.setIcon(L.divIcon({ html, className: 'live-asset-marker', iconSize: [size, size], iconAnchor: [size / 2, size / 2] }));
                            marker.setZIndexOffset(selected ? 1000 : 0);
                            layers.markerKeys[a.db_id] = key;
                        }
                    });
                },

                // ---- Route history & playback -----------------------------------------

                async loadTrail(id) {
                    this.trailLoading = true;
                    this.clearTrailLayers();
                    this.trailPoints = [];

                    try {
                        const res = await fetch(`/api/fleet/assets/${id}/trail`);
                        const json = await res.json();
                        if (this.selectedId !== id || !json.success) return;

                        this.trailPoints = json.trail?.path || [];
                        if (this.trailPoints.length > 1) {
                            layers.trail = L.polyline(this.trailPoints.map(p => [p.lat, p.lng]), {
                                color: '#004ac6', weight: 4, opacity: 0.45, dashArray: '6, 6', lineJoin: 'round'
                            });
                            if (this.showTrail) layers.trail.addTo(this.map);
                        }
                    } catch (e) {
                        console.error('Failed to load trail', e);
                    } finally {
                        if (this.selectedId === id) this.trailLoading = false;
                    }
                },

                extendLiveTrail() {
                    const a = this.selectedAsset;
                    if (!a || !layers.trail || this.playback.active || this.trailPoints.length === 0) return;

                    const last = this.trailPoints[this.trailPoints.length - 1];
                    if (Math.abs(last.lat - a.lat) > 0.00001 || Math.abs(last.lng - a.lng) > 0.00001) {
                        this.trailPoints.push({ lat: a.lat, lng: a.lng, speed: a.speed_raw, ignition: a.ignition, recorded_at: a.formatted_time });
                        layers.trail.addLatLng([a.lat, a.lng]);
                    }
                },

                clearTrailLayers() {
                    ['trail', 'passed', 'playbackMarker'].forEach(k => {
                        layers[k]?.remove();
                        layers[k] = null;
                    });
                },

                startPlayback() {
                    if (this.trailPoints.length === 0) return;
                    this.followSelected = false;
                    this.playback.active = true;
                    this.playback.index = 0;
                    if (layers.trail && !this.showTrail) layers.trail.addTo(this.map);
                    this.fitTrail();
                    this.renderPlaybackFrame();
                    this.play();
                },

                exitPlayback() {
                    this.pausePlay();
                    this.playback.active = false;
                    layers.passed?.remove();
                    layers.playbackMarker?.remove();
                    layers.passed = null;
                    layers.playbackMarker = null;
                    if (layers.trail && !this.showTrail) layers.trail.remove();
                },

                play() {
                    if (this.playback.index >= this.trailPoints.length - 1) this.playback.index = 0;
                    this.playback.playing = true;
                    this.restartPlayTimer();
                },

                pausePlay() {
                    this.playback.playing = false;
                    clearInterval(this.playback.timer);
                    this.playback.timer = null;
                },

                togglePlay() {
                    this.playback.playing ? this.pausePlay() : this.play();
                },

                restartPlayTimer() {
                    clearInterval(this.playback.timer);
                    if (!this.playback.playing) return;

                    this.playback.timer = setInterval(() => {
                        if (this.playback.index < this.trailPoints.length - 1) {
                            this.playback.index++;
                            this.renderPlaybackFrame();
                        } else {
                            this.pausePlay();
                        }
                    }, 400 / this.playback.speed);
                },

                stepPlayback(step) {
                    this.pausePlay();
                    this.playback.index = Math.min(this.trailPoints.length - 1, Math.max(0, this.playback.index + step));
                    this.renderPlaybackFrame();
                },

                fitTrail() {
                    if (this.map && layers.trail) this.map.fitBounds(layers.trail.getBounds(), { padding: [60, 60], maxZoom: 16 });
                },

                renderPlaybackFrame() {
                    const point = this.playbackPoint;
                    if (!this.map || !point) return;

                    const icon = L.divIcon({
                        html: `<div style="background:#059669;width:30px;height:30px;border-radius:50%;border:3px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="color:white;font-size:16px" data-weight="fill">local_shipping</span></div>`,
                        className: 'live-playback-marker', iconSize: [30, 30], iconAnchor: [15, 15]
                    });

                    if (!layers.playbackMarker) {
                        layers.playbackMarker = L.marker([point.lat, point.lng], { icon, zIndexOffset: 2000 }).addTo(this.map);
                    } else {
                        layers.playbackMarker.setLatLng([point.lat, point.lng]);
                    }

                    const passed = this.trailPoints.slice(0, this.playback.index + 1).map(p => [p.lat, p.lng]);
                    if (!layers.passed) {
                        layers.passed = L.polyline(passed, { color: '#059669', weight: 5, opacity: 0.9, lineJoin: 'round' }).addTo(this.map);
                    } else {
                        layers.passed.setLatLngs(passed);
                    }

                    if (!this.map.getBounds().pad(-0.15).contains([point.lat, point.lng])) {
                        this.map.panTo([point.lat, point.lng], { animate: true, duration: 0.3 });
                    }
                },

                // ---- Trip & load events -----------------------------------------------

                async loadVehicleLoadEvents(id) {
                    try {
                        const res = await fetch(`/api/fleet/assets/${id}/load-events`);
                        const json = await res.json();
                        if (!json.success || this.selectedId !== id) return;

                        this.openTrip = json.open_trip;
                        this.loadEvents = json.events;
                        this.renderLoadEventLayers();
                    } catch (e) {
                        console.error('Failed to load load events', e);
                    }
                },

                clearLoadEventLayers() {
                    layers.loadEvents.forEach(m => m.remove());
                    layers.loadEvents = [];
                },

                renderLoadEventLayers() {
                    this.clearLoadEventLayers();
                    if (!this.map) return;

                    this.theftEvents.filter(e => e.lat && e.lng).forEach(e => {
                        const label = document.createElement('div');
                        label.textContent = `${e.label}: ${e.occurred_at} (${e.load_value})`;

                        const icon = L.divIcon({
                            html: `<div style="background:${e.color};width:18px;height:18px;transform:rotate(45deg);border:3px solid white;box-shadow:0 3px 10px rgba(0,0,0,0.35);"></div>`,
                            className: 'live-load-event-marker', iconSize: [18, 18], iconAnchor: [9, 9]
                        });

                        layers.loadEvents.push(L.marker([e.lat, e.lng], { icon }).addTo(this.map).bindPopup(label.innerHTML));
                    });
                },

                // ---- Calibration ------------------------------------------------------

                openCalibration() {
                    const a = this.selectedAsset;
                    Object.assign(this.calibration, {
                        open: !this.calibration.open,
                        base: a?.base_voltage_raw || '',
                        pct: a?.load_increase_percentage || 0,
                        message: '',
                        error: false,
                    });
                },

                async saveCalibration() {
                    const id = this.selectedId;
                    this.calibration.saving = true;
                    this.calibration.message = '';

                    try {
                        const res = await fetch(`/api/fleet/assets/${id}/calibrate-base-voltage`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ base_voltage: this.calibration.base, load_increase_percentage: this.calibration.pct })
                        });
                        const json = await res.json();

                        this.calibration.error = !json.success;
                        this.calibration.message = json.success ? 'Saved' : (json.message || 'Could not save');
                        if (json.success) {
                            await this.fetchAssets();
                            setTimeout(() => { this.calibration.open = false; this.calibration.message = ''; }, 1000);
                        }
                    } catch (e) {
                        this.calibration.error = true;
                        this.calibration.message = 'Network error';
                    } finally {
                        this.calibration.saving = false;
                    }
                },

                // ---- Lifecycle --------------------------------------------------------

                init() {
                    this.$nextTick(() => {
                        if (typeof L === 'undefined') return;

                        this.map = L.map('live-tracking-map', { zoomControl: false }).setView([6.5802, 3.2932], 12);

                        window.addBaseLayers(this.map, { position: 'topleft' });

                        L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                        // Dragging the map means the user wants to look elsewhere
                        this.map.on('dragstart', () => { this.followSelected = false; });

                        this.fetchAssets();
                        this.startAutoRefresh();
                        setInterval(() => { this.now = Date.now(); }, 1000);

                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState === 'hidden') {
                                this.stopAutoRefresh();
                            } else if (this.autoRefreshEnabled) {
                                this.fetchAssets();
                                this.startAutoRefresh();
                            }
                        });

                        window.addEventListener('resize', () => this.resizeMap());
                    });
                }
            }
        }
    </script>
</x-layout>
