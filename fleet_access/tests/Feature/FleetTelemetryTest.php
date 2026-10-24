<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\GpsPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_assets_api_returns_json_payload(): void
    {
        $user = User::factory()->create();

        $vehicle = Vehicle::create([
            'name' => 'Volvo FH16',
            'code' => 'TRK-9021',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'driver_name' => 'Marcus Vance',
            'imei' => '860848081275126',
        ]);

        GpsPosition::create([
            'vehicle_id' => $vehicle->id,
            'imei' => '860848081275126',
            'recorded_at' => now(),
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'altitude_m' => 54,
            'angle_deg' => 77,
            'satellites' => 12,
            'speed_kmh' => 45,
            'is_valid' => true,
            'ignition_state' => true,
            'movement_state' => true,
            'internal_battery_voltage' => 3991,
            'external_voltage' => 12000,
            'total_odometer' => 1500,
            'hdop' => 8,
            'codec' => 'Codec 8 Extended',
        ]);

        $response = $this->actingAs($user)->getJson('/api/fleet/assets');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'count' => 1,
                 ])
                 ->assertJsonPath('assets.0.imei', '860848081275126')
                 ->assertJsonPath('assets.0.battery_voltage', '3991 mV');
    }

    public function test_telemetry_trail_api_returns_historical_path(): void
    {
        $user = User::factory()->create();

        $vehicle = Vehicle::create([
            'name' => 'Volvo FH16',
            'code' => 'TRK-9021',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'driver_name' => 'Marcus Vance',
            'imei' => '860848081275126',
        ]);

        GpsPosition::create([
            'vehicle_id' => $vehicle->id,
            'imei' => '860848081275126',
            'recorded_at' => now()->subMinutes(5),
            'latitude' => 6.5802366,
            'longitude' => 3.2932433,
            'speed_kmh' => 10,
        ]);

        GpsPosition::create([
            'vehicle_id' => $vehicle->id,
            'imei' => '860848081275126',
            'recorded_at' => now(),
            'latitude' => 6.5801366,
            'longitude' => 3.2931366,
            'speed_kmh' => 15,
        ]);

        $response = $this->actingAs($user)->getJson("/api/fleet/assets/{$vehicle->id}/trail");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'trail' => [
                         'total_points' => 2,
                     ],
                 ]);
    }

    public function test_asset_registration_saves_vehicle_to_database(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Scania R500 V8',
            'code' => 'TRK-7788',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'driver_name' => 'Sarah Jenkins',
            'imei' => '860848089999999',
        ];

        $response = $this->actingAs($user)->postJson('/api/fleet/assets/register', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'vehicle' => [
                         'code' => 'TRK-7788',
                         'imei' => '860848089999999',
                     ],
                 ]);

        $this->assertDatabaseHas('vehicles', [
            'code' => 'TRK-7788',
            'imei' => '860848089999999',
            'name' => 'Scania R500 V8',
        ]);
    }

    public function test_can_fetch_and_resolve_dtc_faults(): void
    {
        $user = User::factory()->create();

        $vehicle = Vehicle::create([
            'name' => 'DAF XF 105',
            'code' => 'TRK-1050',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'imei' => '860848081231231',
        ]);

        $fault = \App\Models\DtcFault::create([
            'vehicle_id' => $vehicle->id,
            'code' => 'P0299',
            'description' => 'Turbo Underboost',
            'severity' => 'critical',
            'recommended_action' => 'Schedule Inspection',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson('/api/fleet/dtc-faults');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'count' => 1,
                 ])
                 ->assertJsonPath('faults.0.code', 'P0299');

        $resolveResponse = $this->actingAs($user)->postJson("/api/fleet/dtc-faults/{$fault->id}/resolve");

        $resolveResponse->assertStatus(200)
                        ->assertJson(['success' => true]);

        $this->assertDatabaseHas('dtc_faults', [
            'id' => $fault->id,
            'status' => 'resolved',
        ]);
    }

    public function test_can_fetch_executive_dashboard_metrics(): void
    {
        $user = User::factory()->create();

        Vehicle::create([
            'name' => 'Volvo FH16',
            'code' => 'TRK-9001',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'imei' => '860848081111111',
        ]);

        $response = $this->actingAs($user)->getJson('/api/fleet/dashboard-metrics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'kpis' => [
                         'total_fleet' => '1',
                         'active_loads' => '1',
                     ],
                 ]);
    }

    public function test_asset_registration_with_base_voltage_and_percentage_threshold(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'MAN TGX Heavy Hauler',
            'code' => 'TRK-5544',
            'category' => 'Heavy Duty Truck',
            'status' => 'Empty',
            'base_voltage' => 11800,
            'load_increase_percentage' => 4.5,
            'driver_name' => 'John Doe',
            'imei' => '860848085555555',
        ];

        $response = $this->actingAs($user)->postJson('/api/fleet/assets/register', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'vehicle' => [
                         'code' => 'TRK-5544',
                         'imei' => '860848085555555',
                         'base_voltage' => 11800,
                         'load_increase_percentage' => 4.5,
                     ],
                 ]);

        $this->assertDatabaseHas('vehicles', [
            'code' => 'TRK-5544',
            'imei' => '860848085555555',
            'base_voltage' => 11800,
            'load_increase_percentage' => 4.50,
        ]);
    }

    public function test_vehicle_load_determination_with_percentage_threshold(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Mercedes Actros',
            'code' => 'TRK-8800',
            'category' => 'Heavy Duty Truck',
            'status' => 'Empty',
            'imei' => '860848088888888',
            'base_voltage' => 10000,
            'load_increase_percentage' => 5.0, // 5% threshold => requires > 10500 mV
        ]);

        $this->assertEquals(10500, $vehicle->getLoadThresholdVoltage());

        // Below threshold (+2% / 10200 mV) => Empty
        $this->assertFalse($vehicle->isLoaded(10200));
        $vehicle->updateLoadStatus(10200);
        $this->assertEquals('Empty', $vehicle->status);

        // Above threshold (+6% / 10600 mV) => Loaded
        $this->assertTrue($vehicle->isLoaded(10600));
        $vehicle->updateLoadStatus(10600);
        $this->assertEquals('Loaded', $vehicle->status);
    }

    public function test_can_calibrate_base_voltage_and_percentage_via_api(): void
    {
        $user = User::factory()->create();

        $vehicle = Vehicle::create([
            'name' => 'Kenworth T680',
            'code' => 'TRK-3322',
            'category' => 'Heavy Duty Truck',
            'status' => 'Empty',
            'imei' => '860848083333333',
            'base_voltage' => 10000,
            'current_voltage' => 10400,
            'load_increase_percentage' => 5.0, // 10000 * 1.05 = 10500, currently 10400 => Empty
        ]);

        $response = $this->actingAs($user)->postJson("/api/fleet/assets/{$vehicle->id}/calibrate-base-voltage", [
            'base_voltage' => 10000,
            'load_increase_percentage' => 3.0, // 10000 * 1.03 = 10300, 10400 > 10300 => becomes Loaded!
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'vehicle' => [
                         'base_voltage' => 10000,
                         'load_increase_percentage' => 3.0,
                         'status' => 'Loaded',
                     ],
                 ]);
    }
}
