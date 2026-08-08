<?php

namespace Tests\Feature;

use App\Jobs\EvaluateGeofenceAlertsJob;
use App\Models\Alert;
use App\Models\Geofence;
use App\Models\GpsPosition;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_geofences_with_occupancy_count(): void
    {
        $user = User::factory()->create();

        $geofence = Geofence::create([
            'name' => 'Lagos Port Hub',
            'type' => 'Port Terminal',
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'radius_meters' => 2500,
            'max_speed_kmh' => 20,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'name' => 'Volvo FH16',
            'code' => 'TRK-9021',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'imei' => '860848081275126',
        ]);

        GpsPosition::create([
            'vehicle_id' => $vehicle->id,
            'imei' => '860848081275126',
            'recorded_at' => now(),
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'speed_kmh' => 10,
        ]);

        $response = $this->actingAs($user)->getJson('/api/geofences');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'count' => 1,
                 ])
                 ->assertJsonPath('geofences.0.vehicles', 1);
    }

    public function test_can_create_new_geofence_perimeter(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Abuja Logistics Depot',
            'type' => 'Depot Yard',
            'latitude' => 9.0765,
            'longitude' => 7.3986,
            'radius_meters' => 3000,
            'max_speed_kmh' => 30,
        ];

        $response = $this->actingAs($user)->postJson('/api/geofences', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'geofence' => [
                         'name' => 'Abuja Logistics Depot',
                     ],
                 ]);

        $this->assertDatabaseHas('geofences', [
            'name' => 'Abuja Logistics Depot',
        ]);
    }

    public function test_evaluate_job_triggers_speed_and_entry_alerts(): void
    {
        $geofence = Geofence::create([
            'name' => 'Speed Sensitive Yard',
            'type' => 'Depot Yard',
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'radius_meters' => 3000,
            'max_speed_kmh' => 10,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'name' => 'Fast Truck',
            'code' => 'TRK-99',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'imei' => '860848081275999',
        ]);

        GpsPosition::create([
            'vehicle_id' => $vehicle->id,
            'imei' => '860848081275999',
            'recorded_at' => now(),
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'speed_kmh' => 45, // Speed limit is 10!
        ]);

        EvaluateGeofenceAlertsJob::dispatchSync();

        $this->assertDatabaseHas('alerts', [
            'vehicle_id' => $vehicle->id,
            'geofence_id' => $geofence->id,
            'type' => 'overspeed',
        ]);
    }
}
