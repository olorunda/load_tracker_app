<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Str;

class AssetRegistrationService
{
    private array $payload = [];

    /**
     * Set payload data via method chaining.
     */
    public function withPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Register a new vehicle asset or update existing by IMEI.
     */
    public function registerAsset(): Vehicle
    {
        $code = strtoupper(trim($this->payload['code'] ?? 'TRK-' . rand(1000, 9999)));
        $imei = trim($this->payload['imei']);

        $baseVoltage = (isset($this->payload['base_voltage']) && $this->payload['base_voltage'] !== '' && $this->payload['base_voltage'] !== null) 
            ? (int)$this->payload['base_voltage'] 
            : null;

        $increasePct = (isset($this->payload['load_increase_percentage']) && $this->payload['load_increase_percentage'] !== '' && $this->payload['load_increase_percentage'] !== null) 
            ? (float)$this->payload['load_increase_percentage'] 
            : 0.00;

        $vehicle = Vehicle::firstOrNew(['imei' => $imei]);
        $vehicle->name = trim($this->payload['name']);
        $vehicle->code = $code;
        $vehicle->category = $this->payload['category'] ?? 'Heavy Duty Truck';
        $vehicle->driver_name = $this->payload['driver_name'] ?? 'Driver Assigned';
        $vehicle->base_voltage = $baseVoltage;
        $vehicle->load_increase_percentage = $increasePct;
        $vehicle->last_ping_at = $vehicle->last_ping_at ?: now();

        if ($vehicle->current_voltage && $vehicle->base_voltage) {
            $vehicle->status = $vehicle->isLoaded() ? 'Loaded' : 'Empty';
        } else {
            $vehicle->status = $this->payload['status'] ?? 'Loaded';
        }

        $vehicle->save();

        return $vehicle;
    }
}
