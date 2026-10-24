<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Services\TripAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVehicleAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $vehicleId,
        public bool $rebuild = false
    ) {}

    /**
     * Run one vehicle at a time so its state machine cursor is never advanced concurrently.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("trip-analytics-{$this->vehicleId}"))->releaseAfter(30)];
    }

    /**
     * Execute the job.
     */
    public function handle(TripAnalyticsService $analyticsService): void
    {
        $vehicle = Vehicle::find($this->vehicleId);
        if (! $vehicle) {
            return;
        }

        $stats = $this->rebuild
            ? $analyticsService->rebuildVehicle($vehicle)
            : $analyticsService->processVehicle($vehicle);

        Log::info("ProcessVehicleAnalyticsJob completed for {$stats['vehicle']}. Positions: {$stats['processed']}, Events: {$stats['events']}");
    }
}
