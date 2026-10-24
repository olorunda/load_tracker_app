<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\GpsPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_alert_rules(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/alert-rules');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);
        
        $this->assertGreaterThan(0, AlertRule::count());
    }

    public function test_can_save_new_alert_rule(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Speed Cap Warning 80kmh',
            'type' => 'Speeding Threshold',
            'target_group' => 'All Heavy Duty Trucks',
            'severity' => 'critical',
            'threshold_value' => 80.0,
            'description' => 'Notify when heavy transport exceeds 80 km/h.',
        ];

        $response = $this->actingAs($user)->postJson('/api/alert-rules', $payload);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'rule' => [
                         'name' => 'Speed Cap Warning 80kmh',
                         'type' => 'Speeding Threshold',
                         'severity' => 'critical',
                         'threshold_value' => 80.0,
                         'icon' => 'speed',
                     ],
                 ]);

        $this->assertDatabaseHas('alert_rules', [
            'name' => 'Speed Cap Warning 80kmh',
            'severity' => 'critical',
            'threshold_value' => 80.0,
        ]);
    }

    public function test_can_toggle_alert_rule_status(): void
    {
        $user = User::factory()->create();

        $rule = AlertRule::create([
            'name' => 'Test Rule',
            'type' => 'Load Status Change',
            'target_group' => 'All Fleet Assets',
            'severity' => 'warning',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson("/api/alert-rules/{$rule->id}/toggle");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'rule' => [
                         'is_active' => false,
                     ],
                 ]);

        $this->assertDatabaseHas('alert_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_alert_rule(): void
    {
        $user = User::factory()->create();

        $rule = AlertRule::create([
            'name' => 'Temporary Rule',
            'type' => 'Diagnostic Fault',
            'target_group' => 'All Fleet Assets',
            'severity' => 'info',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/alert-rules/{$rule->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('alert_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_can_resolve_and_resolve_all_alerts(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Freight Liner',
            'code' => 'TRK-22',
            'category' => 'Heavy Duty Truck',
            'status' => 'Loaded',
            'imei' => '860848080000022',
        ]);

        $alert1 = Alert::create([
            'vehicle_id' => $vehicle->id,
            'type' => 'overspeed',
            'severity' => 'critical',
            'title' => 'Speed limit reached',
            'description' => 'Vehicle exceeded speed limit',
            'triggered_at' => now(),
            'is_resolved' => false,
        ]);

        $alert2 = Alert::create([
            'vehicle_id' => $vehicle->id,
            'type' => 'power_disconnect',
            'severity' => 'warning',
            'title' => 'Power drop',
            'description' => 'External voltage low',
            'triggered_at' => now(),
            'is_resolved' => false,
        ]);

        // Resolve single alert
        $response1 = $this->actingAs($user)->postJson("/api/alerts/{$alert1->id}/resolve");
        $response1->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue($alert1->fresh()->is_resolved);

        // Resolve all alerts
        $response2 = $this->actingAs($user)->postJson('/api/alerts/resolve-all');
        $response2->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue($alert2->fresh()->is_resolved);
    }
}
