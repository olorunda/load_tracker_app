<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRules = [
            [
                'name' => 'Unscheduled Unload',
                'type' => 'Load Status Change',
                'description' => 'Alert when cargo status changes to Empty outside registered depot.',
                'target_group' => 'All Heavy Duty Trucks',
                'severity' => 'warning',
                'threshold_value' => null,
                'is_active' => true,
                'icon' => 'inventory_2',
            ],
            [
                'name' => 'Excessive Speed Limit',
                'type' => 'Speeding Threshold',
                'description' => 'Trigger when vehicle speed exceeds 75 km/h for active units.',
                'target_group' => 'All Fleet Assets',
                'severity' => 'critical',
                'threshold_value' => 75.00,
                'is_active' => true,
                'icon' => 'speed',
            ],
            [
                'name' => 'Geofence Perimeter Exit',
                'type' => 'Geofence Exit / Entry',
                'description' => 'Broadcast alert whenever an asset departs designated hub boundary.',
                'target_group' => 'Regional Delivery Vans',
                'severity' => 'info',
                'threshold_value' => null,
                'is_active' => true,
                'icon' => 'map',
            ],
            [
                'name' => 'IO 66 Main Power Disconnect',
                'type' => 'Voltage Anomaly',
                'description' => 'Trigger when IO 66 external voltage drops to 0 mV or below resting threshold.',
                'target_group' => 'All Fleet Assets',
                'severity' => 'critical',
                'threshold_value' => 0.00,
                'is_active' => true,
                'icon' => 'bolt',
            ],
        ];

        foreach ($defaultRules as $rule) {
            AlertRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
