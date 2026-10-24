<?php

namespace Tests\Feature;

use App\Models\GpsPosition;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripReportTest extends TestCase
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
            'driver_name' => 'Marcus Vance',
            'imei' => '860848081275126',
            'base_voltage' => 12000,
            'load_increase_percentage' => 5,
        ]);
        $this->clock = now()->subDays(2);

        $this->drive(2, 12000);
        $this->park(30, 12000);
        $this->drive(6, 14000);
        $this->park(5, 14000, ignition: true);
        $this->drive(10, 17000);
        $this->park(5, 17000, ignition: true);
        $this->drive(5, 14000);
        $this->park(60, 14000);
        $this->drive(10, 12000);

        app(TripAnalyticsService::class)->processVehicle($this->vehicle);
    }

    public function test_trips_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Trips &amp; Load Events', false);
    }

    public function test_trips_api_returns_trips_and_summary(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/trips')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('trips.0.vehicle_code', 'TRK-9021')
            ->assertJsonPath('trips.0.status', 'completed')
            ->assertJsonPath('trips.0.unauthorized', 'confirmed')
            ->assertJsonPath('trips.0.offload_located', true)
            ->assertJsonPath('summary.billable_trips', 1)
            ->assertJsonPath('summary.confirmed_unauthorized', 1);
    }

    public function test_trips_api_filters_by_status_and_search(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/trips?status=flagged')->assertJsonPath('count', 1);
        $this->actingAs($user)->getJson('/api/trips?status=open')->assertJsonPath('count', 0);
        $this->actingAs($user)->getJson('/api/trips?q=marcus')->assertJsonPath('count', 1);
        $this->actingAs($user)->getJson('/api/trips?q=nobody')->assertJsonPath('count', 0);
    }

    public function test_trip_detail_returns_timeline_and_path(): void
    {
        $trip = Trip::sole();

        $response = $this->actingAs(User::factory()->create())
            ->getJson("/api/trips/{$trip->id}")
            ->assertOk()
            ->assertJsonPath('trip.events.0.type', 'loading')
            ->assertJsonPath('trip.events.3.label', 'Unauthorized Cargo Offloaded');

        $this->assertGreaterThan(50, count($response->json('trip.path')));
    }

    public function test_unknown_trip_returns_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/trips/999')
            ->assertNotFound();
    }

    public function test_vehicle_load_events_api(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson("/api/fleet/assets/{$this->vehicle->id}/load-events")
            ->assertOk()
            ->assertJsonPath('open_trip', null)
            ->assertJsonPath('events.0.type', 'unloading')
            ->assertJsonCount(5, 'events');
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
