<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'geofence_id',
        'type',
        'severity',
        'title',
        'description',
        'is_resolved',
        'triggered_at',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    /**
     * Vehicle associated with the alert.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Geofence associated with the alert.
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }
}
