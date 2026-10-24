<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'start_position_id',
        'end_position_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'latitude',
        'longitude',
        'ignition_off',
        'load_before',
        'load_after',
        'load_delta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'ignition_off' => 'boolean',
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
