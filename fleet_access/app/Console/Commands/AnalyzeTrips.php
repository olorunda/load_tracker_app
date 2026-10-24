<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\TripAnalyticsService;
use Illuminate\Console\Command;

class AnalyzeTrips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trips:analyze
                            {vehicle? : Vehicle ID, code or IMEI (defaults to all vehicles)}
                            {--rebuild : Discard derived stops, trips, load events and alerts, then replay full history}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detects trips, loading/unloading and unauthorized loading from Teltonika IO 66 telemetry.';

    /**
     * Execute the console command.
     */
    public function handle(TripAnalyticsService $analyticsService): int
    {
        $identifier = $this->argument('vehicle');
        $vehicles = $identifier
            ? Vehicle::where('id', $identifier)->orWhere('code', $identifier)->orWhere('imei', $identifier)->get()
            : Vehicle::all();

        if ($vehicles->isEmpty()) {
            $this->error('No matching vehicles found.');

            return Command::FAILURE;
        }

        $rows = $vehicles->map(function (Vehicle $vehicle) use ($analyticsService) {
            $stats = $this->option('rebuild')
                ? $analyticsService->rebuildVehicle($vehicle)
                : $analyticsService->processVehicle($vehicle);

            return [$stats['vehicle'], $stats['processed'], $stats['events'], $stats['skipped'] ?? '-'];
        });

        $this->table(['Vehicle', 'Positions Processed', 'Events', 'Skipped'], $rows);

        return Command::SUCCESS;
    }
}
