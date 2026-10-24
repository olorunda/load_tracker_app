<?php

namespace App\Analytics;

use App\Models\GpsPosition;

/**
 * Immutable snapshot of a single Teltonika AVL record used by the trip analytics engine.
 */
final class TelemetryPoint
{
    public function __construct(
        public readonly int $id,
        public readonly int $timestamp,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $speedKmh,
        public readonly bool $ignition,
        public readonly int $load,
        public readonly int $odometerM,
    ) {}

    /**
     * Build a point from a stored GPS position (IO 66 external voltage is the load signal, IO 16 the odometer).
     */
    public static function fromPosition(GpsPosition $position): self
    {
        return new self(
            id: $position->id,
            timestamp: $position->recorded_at->getTimestamp(),
            latitude: $position->latitude,
            longitude: $position->longitude,
            speedKmh: (int) $position->speed_kmh,
            ignition: (bool) $position->ignition_state,
            load: (int) $position->external_voltage,
            odometerM: (int) $position->total_odometer,
        );
    }
}
