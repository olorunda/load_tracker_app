<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'trip_id',
        'stop_id',
        'alert_id',
        'type',
        'occurred_at',
        'latitude',
        'longitude',
        'load_value',
        'load_delta',
        'description',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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

    /**
     * Relationship to the trip the event occurred on.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Relationship to the stop where the load change happened.
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(VehicleStop::class, 'stop_id');
    }
}
