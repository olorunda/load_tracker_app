<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAnalyticsState extends Model
{
    protected $fillable = [
        'vehicle_id',
        'last_position_id',
        'last_recorded_at',
        'state',
    ];

    protected $casts = [
        'last_recorded_at' => 'datetime',
        'state' => 'array',
    ];

    /**
     * Relationship to owner Vehicle.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
