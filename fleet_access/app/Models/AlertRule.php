<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'target_group',
        'severity',
        'threshold_value',
        'is_active',
        'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'threshold_value' => 'float',
    ];

    /**
     * Map trigger types to standard icon names.
     */
    public static function resolveIcon(string $type): string
    {
        return match (strtolower(str_replace([' ', '_', '-'], '', $type))) {
            'loadstatus', 'loadstatuschange', 'load' => 'inventory_2',
            'speed', 'speeding', 'speedingthreshold', 'overspeed' => 'speed',
            'geofence', 'geofenceexitentry', 'geofenceentry', 'geofenceexit' => 'map',
            'voltage', 'battery', 'voltageanomaly', 'power' => 'bolt',
            'coldchaintemp', 'temp', 'temperature' => 'thermostat',
            'diagnostic', 'dtc', 'fault' => 'warning',
            default => 'notifications',
        };
    }
}
