<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Services\GeofenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateGeofenceAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $vehicleId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeofenceService $geofenceService): void
    {
        $query = Vehicle::query();
        if ($this->vehicleId) {
            $query->where('id', $this->vehicleId);
        }

        $vehicles = $query->get();
        $totalAlertsTriggered = 0;

        foreach ($vehicles as $vehicle) {
            $alerts = $geofenceService->evaluateVehicleGeofenceAlerts($vehicle);
            $totalAlertsTriggered += count($alerts);
        }

        Log::info("EvaluateGeofenceAlertsJob completed. Vehicles evaluated: {$vehicles->count()}, Alerts triggered: {$totalAlertsTriggered}");
    }
}
