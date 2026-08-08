<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'imei',
        'recorded_at',
        'latitude',
        'longitude',
        'altitude_m',
        'angle_deg',
        'satellites',
        'speed_kmh',
        'is_valid',
        'ignition_state',
        'movement_state',
        'internal_battery_voltage',
        'external_voltage',
        'total_odometer',
        'hdop',
        'codec',
        'raw_io_json',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_valid' => 'boolean',
        'ignition_state' => 'boolean',
        'movement_state' => 'boolean',
        'raw_io_json' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Relationship to owner Vehicle.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
