<?php

namespace App\Console\Commands;

use App\Models\GpsPosition;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class CalibrateLoadVoltage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fleet:calibrate-load-voltage 
                            {--imei= : Specific vehicle IMEI to calibrate} 
                            {--base-voltage= : Force specific base voltage in mV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calibrates vehicle base voltage (IO 66) and updates loaded/empty status based on voltage increase.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Evaluating Fleet Load Status from Teltonika IO 66 (External Voltage)...");

        $query = Vehicle::query();
        if ($imei = $this->option('imei')) {
            $query->where('imei', $imei);
        }

        $vehicles = $query->get();
        if ($vehicles->isEmpty()) {
            $this->warn("No vehicles found to calibrate.");
            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($vehicles as $vehicle) {
            $forcedBase = $this->option('base-voltage');

            // Find earliest non-zero external voltage (initial resting voltage)
            $firstPos = GpsPosition::where('vehicle_id', $vehicle->id)
                ->where('external_voltage', '>', 0)
                ->orderBy('recorded_at', 'asc')
                ->first();

            // Find lowest observed external voltage as alternative baseline
            $minVolt = GpsPosition::where('vehicle_id', $vehicle->id)
                ->where('external_voltage', '>', 0)
                ->min('external_voltage');

            $latestPos = GpsPosition::where('vehicle_id', $vehicle->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            // Determine base voltage
            $baseVolt = $forcedBase ? (int)$forcedBase : ($vehicle->base_voltage ?: ($minVolt ?: ($firstPos ? $firstPos->external_voltage : null)));
            $currentVolt = $latestPos ? $latestPos->external_voltage : ($vehicle->current_voltage ?: null);

            $status = $vehicle->status;
            $delta = 0;
            if ($baseVolt !== null && $currentVolt !== null && $baseVolt > 0) {
                $delta = $currentVolt - $baseVolt;
                // Any increase from the base means the truck is loaded
                $status = ($currentVolt > $baseVolt) ? 'Loaded' : 'Empty';
            }

            $vehicle->update([
                'base_voltage' => $baseVolt,
                'current_voltage' => $currentVolt,
                'status' => $status,
                'last_ping_at' => $latestPos ? $latestPos->recorded_at : $vehicle->last_ping_at,
            ]);

            $rows[] = [
                'ID' => $vehicle->code,
                'Vehicle Name' => $vehicle->name,
                'IMEI' => $vehicle->imei,
                'Base Volt (Empty)' => $baseVolt ? "{$baseVolt} mV" : 'N/A',
                'Current Volt (IO 66)' => $currentVolt ? "{$currentVolt} mV" : 'N/A',
                'Delta' => ($delta > 0 ? "+{$delta}" : "{$delta}") . ' mV',
                'Calculated Status' => $status,
            ];
        }

        $this->table(
            ['ID', 'Vehicle Name', 'IMEI', 'Base Volt (Empty)', 'Current Volt (IO 66)', 'Delta', 'Calculated Status'],
            $rows
        );

        $this->info("Load voltage calibration and status update completed.");

        return Command::SUCCESS;
    }
}
