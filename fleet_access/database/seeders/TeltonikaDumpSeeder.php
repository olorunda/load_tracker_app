<?php

namespace Database\Seeders;

use App\Jobs\EvaluateGeofenceAlertsJob;
use App\Models\DtcFault;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\FleetTelemetryService;
use App\Services\TeltonikaDumpImporterService;
use Illuminate\Database\Seeder;

class TeltonikaDumpSeeder extends Seeder
{
    public function __construct(
        private TeltonikaDumpImporterService $importerService,
        private FleetTelemetryService $telemetryService
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $findDump = function(string $filename): ?string {
            $paths = [
                base_path('../teltonika_tcp/' . $filename),
                base_path('teltonika_tcp/' . $filename),
                '/var/www/html/teltonika_tcp/' . $filename,
                '/var/www/html/fleet_tracker/teltonika_tcp/' . $filename,
            ];
            foreach ($paths as $p) {
                if (file_exists($p)) return $p;
            }
            return null;
        };

        $primaryDump = $findDump('860848081275126.jsonl');
        $allRecordsDump = $findDump('all_records.jsonl');

        if ($primaryDump) {
            $this->importerService->fromFile($primaryDump)->import();
        }

        if ($allRecordsDump) {
            $this->importerService->fromFile($allRecordsDump)->import();
        }

        // Seed Geofences
        $geofences = [
            [
                'name' => 'Lagos Container Port Terminal',
                'type' => 'Port Terminal',
                'latitude' => 6.5802366,
                'longitude' => 3.2932433,
                'radius_meters' => 2500,
                'max_speed_kmh' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Ikeja Central Logistics Hub',
                'type' => 'Depot Yard',
                'latitude' => 6.5950000,
                'longitude' => 3.3400000,
                'radius_meters' => 3800,
                'max_speed_kmh' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Victoria Island Freight Corridor',
                'type' => 'Highway Corridor',
                'latitude' => 6.4300000,
                'longitude' => 3.4200000,
                'radius_meters' => 5000,
                'max_speed_kmh' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Chicago Central Hub',
                'type' => 'Distribution Center',
                'latitude' => 41.8781000,
                'longitude' => -87.6298000,
                'radius_meters' => 3000,
                'max_speed_kmh' => 30,
                'is_active' => true,
            ]
        ];

        foreach ($geofences as $g) {
            Geofence::firstOrCreate(['name' => $g['name']], $g);
        }

        // Seed Initial DTC Fault Codes
        $vehicle = Vehicle::first();
        if ($vehicle) {
            DtcFault::firstOrCreate([
                'vehicle_id' => $vehicle->id,
                'code' => 'P0299',
            ], [
                'description' => 'Turbocharger / Supercharger Underboost Condition',
                'severity' => 'critical',
                'recommended_action' => 'Schedule Turbo Service Inspection',
                'status' => 'active',
            ]);

            DtcFault::firstOrCreate([
                'vehicle_id' => $vehicle->id,
                'code' => 'P0301',
            ], [
                'description' => 'Cylinder 1 Misfire Detected',
                'severity' => 'warning',
                'recommended_action' => 'Inspect Spark Plug / Injector',
                'status' => 'active',
            ]);
        }

        // Run diagnostics engine & alert evaluation
        $this->telemetryService->runDiagnosticsEngine();
        EvaluateGeofenceAlertsJob::dispatchSync();
    }
}
