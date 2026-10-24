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
        'load_increase_percentage',
        'current_voltage',
        'driver_name',
        'imei',
        'last_ping_at',
    ];

    protected $casts = [
        'last_ping_at' => 'datetime',
        'base_voltage' => 'integer',
        'load_increase_percentage' => 'float',
        'current_voltage' => 'integer',
    ];

    /**
     * Determine if vehicle is loaded based on IO 66 (External Voltage) vs Base Voltage and % increase threshold.
     * If % increase is 0 or unset, any increase from base voltage triggers Loaded status.
     * If % increase > 0, voltage must exceed base_voltage * (1 + (percentage / 100)).
     */
    public function isLoaded(?int $voltage = null): bool
    {
        $v = $voltage ?? $this->current_voltage;
        if ($this->base_voltage === null || $this->base_voltage <= 0 || $v === null || $v <= 0) {
            return $this->status === 'Loaded';
        }

        $thresholdVoltage = $this->getLoadThresholdVoltage();

        return $v > $thresholdVoltage;
    }

    /**
     * Get the minimum voltage required to trigger Loaded status.
     */
    public function getLoadThresholdVoltage(): ?int
    {
        if ($this->base_voltage === null || $this->base_voltage <= 0) {
            return null;
        }

        $pct = (float)($this->load_increase_percentage ?? 0.0);
        if ($pct <= 0) {
            return $this->base_voltage;
        }

        return (int)round($this->base_voltage * (1 + ($pct / 100.0)));
    }

    /**
     * Get the percentage difference between current voltage and base voltage.
     */
    public function getVoltageIncreasePercentage(?int $voltage = null): float
    {
        $v = $voltage ?? $this->current_voltage;
        if ($this->base_voltage === null || $this->base_voltage <= 0 || $v === null || $v <= 0) {
            return 0.0;
        }

        return round((($v - $this->base_voltage) / $this->base_voltage) * 100, 2);
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
        $this->status = $this->isLoaded($voltage) ? 'Loaded' : 'Empty';
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
        return $this->hasOne(GpsPosition::class)->latestOfMany('id');
    }
}
