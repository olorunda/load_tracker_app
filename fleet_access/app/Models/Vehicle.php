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
        'base_voltage',
        'current_voltage',
        'driver_name',
        'imei',
        'last_ping_at',
    ];

    protected $casts = [
        'last_ping_at' => 'datetime',
        'base_voltage' => 'integer',
        'current_voltage' => 'integer',
    ];

    /**
     * Determine if vehicle is loaded based on IO 66 (External Voltage) vs Base Voltage.
     * Any increase from the base voltage means the truck is loaded.
     */
    public function isLoaded(?int $voltage = null): bool
    {
        $v = $voltage ?? $this->current_voltage;
        if ($this->base_voltage === null || $this->base_voltage <= 0 || $v === null || $v <= 0) {
            return $this->status === 'Loaded';
        }

        return $v > $this->base_voltage;
    }

    /**
     * Compute and update load status based on current voltage reading.
     */
    public function updateLoadStatus(int $voltage): string
    {
        if ($this->base_voltage === null || $this->base_voltage <= 0) {
            $this->base_voltage = $voltage;
        }

        $this->current_voltage = $voltage;
        $this->status = ($voltage > $this->base_voltage) ? 'Loaded' : 'Empty';
        $this->save();

        return $this->status;
    }

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
