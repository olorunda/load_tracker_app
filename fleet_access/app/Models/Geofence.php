<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geofence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'latitude',
        'longitude',
        'radius_meters',
        'max_speed_kmh',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'max_speed_kmh' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to triggered alerts.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
