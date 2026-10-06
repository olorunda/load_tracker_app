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

        return Vehicle::updateOrCreate(
            ['imei' => $imei],
            [
                'name' => trim($this->payload['name']),
                'code' => $code,
                'category' => $this->payload['category'] ?? 'Heavy Duty Truck',
                'status' => $this->payload['status'] ?? 'Loaded',
                'base_voltage' => isset($this->payload['base_voltage']) ? (int)$this->payload['base_voltage'] : null,
                'driver_name' => $this->payload['driver_name'] ?? 'Driver Assigned',
                'last_ping_at' => now(),
            ]
        );
    }
}
