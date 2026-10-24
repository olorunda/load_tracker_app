<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\GpsPosition;
use App\Models\LoadEvent;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleStop;
use App\Services\TripAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    private Carbon $clock;

    private int $odometer = 1_000_000;

    private float $latitude = 6.5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehicle = Vehicle::create([
            'name' => 'Volvo FH16',
            'code' => 'TRK-9021',
            'category' => 'Heavy Duty Truck',
            'status' => 'Empty',
            'imei' => '860848081275126',
            'base_voltage' => 12000,
            'load_increase_percentage' => 5,
        ]);
        $this->clock = now()->subDays(2);
    }

    public function test_trip_with_unauthorized_loading_is_persisted_with_alerts(): void
    {
        $this->recordTripWithUnauthorizedLoading();

        $stats = app(TripAnalyticsService::class)->processVehicle($this->vehicle);

        $this->assertSame(GpsPosition::count(), $stats['processed']);

        $trip = Trip::sole();
        $this->assertSame('completed', $trip->status);
        $this->assertTrue($trip->is_billable);
        $this->assertSame(14000, $trip->baseline_load);
        $this->assertNotNull($trip->end_stop_id);
        $this->assertGreaterThan(20000, $trip->distance_m);

        $this->assertSame(
            ['loading', 'suspected_unauthorized_loading', 'confirmed_unauthorized_loading', 'unauthorized_offload', 'unloading'],
            $trip->loadEvents()->pluck('type')->all()
        );
        $this->assertSame(
            ['warning', 'critical', 'critical'],
            Alert::orderBy('id')->pluck('severity')->all()
        );
        $this->assertNotNull(LoadEvent::where('type', 'unauthorized_offload')->value('stop_id'));
    }

    public function test_incremental_processing_matches_a_full_rebuild(): void
    {
        $this->recordTripWithUnauthorizedLoading();
        $service = app(TripAnalyticsService::class);

        $service->processVehicle($this->vehicle);
        $this->assertSame(0, $service->processVehicle($this->vehicle)['processed']);
        $first = $this->snapshot();

        $service->rebuildVehicle($this->vehicle);

        $this->assertEquals($first, $this->snapshot());
        $this->assertSame(3, Alert::count());
    }

    public function test_open_trip_distance_is_kept_current(): void
    {
        $this->drive(2, 12000);
        $this->park(30, 12000);
        $this->drive(8, 14000);

        app(TripAnalyticsService::class)->processVehicle($this->vehicle);

        $trip = Trip::sole();
        $this->assertSame('active', $trip->status);
        $this->assertGreaterThan(7000, $trip->distance_m);
    }

    public function test_uncalibrated_vehicle_is_skipped(): void
    {
        $this->vehicle->update(['base_voltage' => null]);
        $this->drive(5, 12000);

        $stats = app(TripAnalyticsService::class)->processVehicle($this->vehicle->fresh());

        $this->assertSame(0, $stats['processed']);
        $this->assertNotNull($stats['skipped']);
    }

    public function test_recent_positions_wait_for_the_processing_lag(): void
    {
        $this->clock = now()->subMinute();
        $this->drive(5, 12000);

        $this->assertSame(0, app(TripAnalyticsService::class)->processVehicle($this->vehicle)['processed']);
    }

    public function test_analyze_command_reports_vehicle_stats(): void
    {
        $this->recordTripWithUnauthorizedLoading();

        $this->artisan('trips:analyze', ['vehicle' => 'TRK-9021'])
            ->expectsOutputToContain('TRK-9021')
            ->assertSuccessful();

        $this->assertSame(1, Trip::count());
        $this->assertSame(4, VehicleStop::count());
    }

    private function recordTripWithUnauthorizedLoading(): void
    {
        $this->drive(2, 12000);
        $this->park(30, 12000);
        $this->drive(6, 14000);
        $this->park(5, 14000, ignition: true);
        $this->drive(10, 17000);
        $this->park(5, 17000, ignition: true);
        $this->drive(5, 14000);
        $this->park(60, 14000);
        $this->drive(10, 12000);
    }

    private function snapshot(): array
    {
        return [
            Trip::get(['status', 'baseline_load', 'distance_m', 'loaded_at', 'completed_at'])->toArray(),
            LoadEvent::orderBy('occurred_at')->orderBy('id')->get(['type', 'occurred_at', 'load_value'])->toArray(),
            VehicleStop::orderBy('started_at')->get(['started_at', 'duration_seconds', 'load_before', 'load_after'])->toArray(),
        ];
    }

    private function drive(float $km, int $load): void
    {
        $stepM = 50 / 3.6 * 30;
        for ($i = 0; $i < ceil($km * 1000 / $stepM); $i++) {
            $this->clock = $this->clock->copy()->addSeconds(30);
            $this->odometer += (int) round($stepM);
            $this->latitude += $stepM / 111000;
            $this->record(50, true, $load);
        }
    }

    private function park(int $minutes, int $load, bool $ignition = false): void
    {
        for ($i = 0; $i < $minutes; $i++) {
            $this->clock = $this->clock->copy()->addMinute();
            $this->record(0, $ignition, $load);
        }
    }

    private function record(int $speed, bool $ignition, int $load): void
    {
        GpsPosition::create([
            'vehicle_id' => $this->vehicle->id,
            'imei' => $this->vehicle->imei,
            'recorded_at' => $this->clock,
            'latitude' => $this->latitude,
            'longitude' => 3.3,
            'speed_kmh' => $speed,
            'ignition_state' => $ignition,
            'movement_state' => $speed > 0,
            'external_voltage' => $load,
            'total_odometer' => $this->odometer,
        ]);
    }
}
