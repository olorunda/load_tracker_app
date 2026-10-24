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
                            {--base-voltage= : Force specific base voltage in mV}
                            {--percentage= : Force load increase threshold percentage (e.g. 5.0 for 5%)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calibrates vehicle base voltage (IO 66) and % increase threshold, updating loaded/empty status.';

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
            $forcedPct = $this->option('percentage');

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
            $loadPct = $forcedPct !== null ? (float)$forcedPct : (float)($vehicle->load_increase_percentage ?? 0.0);

            $status = $vehicle->status;
            $delta = 0;
            $increasePct = 0.0;
            if ($baseVolt !== null && $currentVolt !== null && $baseVolt > 0) {
                $delta = $currentVolt - $baseVolt;
                $increasePct = round((($currentVolt - $baseVolt) / $baseVolt) * 100, 2);
                $minLoadedVolt = (int)round($baseVolt * (1 + ($loadPct / 100.0)));
                $status = ($currentVolt > $minLoadedVolt) ? 'Loaded' : 'Empty';
            }

            $vehicle->update([
                'base_voltage' => $baseVolt,
                'load_increase_percentage' => $loadPct,
                'current_voltage' => $currentVolt,
                'status' => $status,
                'last_ping_at' => $latestPos ? $latestPos->recorded_at : $vehicle->last_ping_at,
            ]);

            $rows[] = [
                'ID' => $vehicle->code,
                'Vehicle Name' => $vehicle->name,
                'IMEI' => $vehicle->imei,
                'Base Volt' => $baseVolt ? "{$baseVolt} mV" : 'N/A',
                'Current Volt' => $currentVolt ? "{$currentVolt} mV" : 'N/A',
                'Delta' => ($delta > 0 ? "+{$delta}" : "{$delta}") . ' mV',
                'Change %' => ($increasePct > 0 ? "+{$increasePct}%" : "{$increasePct}%"),
                'Threshold %' => "+{$loadPct}%",
                'Calculated Status' => $status,
            ];
        }

        $this->table(
            ['ID', 'Vehicle Name', 'IMEI', 'Base Volt', 'Current Volt', 'Delta', 'Change %', 'Threshold %', 'Calculated Status'],
            $rows
        );

        $this->info("Load voltage calibration and status update completed.");

        return Command::SUCCESS;
    }
}
