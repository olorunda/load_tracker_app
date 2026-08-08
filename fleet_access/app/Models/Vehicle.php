<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'status',
        'driver_name',
        'imei',
        'last_ping_at',
    ];

    protected $casts = [
        'last_ping_at' => 'datetime',
    ];

    /**
     * Relationship to all recorded GPS positions.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(GpsPosition::class)->orderBy('recorded_at', 'asc');
    }

    /**
     * Relationship to the single latest GPS position.
     */
    public function latestPosition(): HasOne
    {
        return $this->hasOne(GpsPosition::class)->latestOfMany('recorded_at');
    }
}
