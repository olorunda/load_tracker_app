<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'start_stop_id',
        'end_stop_id',
        'status',
        'baseline_load',
        'loaded_at',
        'confirmed_at',
        'unloaded_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'distance_m',
        'is_billable',
    ];

    protected $casts = [
        'loaded_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'unloaded_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_billable' => 'boolean',
        'baseline_load' => 'integer',
        'distance_m' => 'integer',
    ];

    /**
     * Relationship to owner Vehicle.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Stop where the trip cargo was loaded.
     */
    public function startStop(): BelongsTo
    {
        return $this->belongsTo(VehicleStop::class, 'start_stop_id');
    }

    /**
     * Stop where the trip cargo was unloaded.
     */
    public function endStop(): BelongsTo
    {
        return $this->belongsTo(VehicleStop::class, 'end_stop_id');
    }

    /**
     * Load events (including unauthorized loading) recorded during the trip.
     */
    public function loadEvents(): HasMany
    {
        return $this->hasMany(LoadEvent::class)->orderBy('occurred_at', 'asc');
    }
}
