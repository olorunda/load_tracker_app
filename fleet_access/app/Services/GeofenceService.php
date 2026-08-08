<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Geofence;
use App\Models\GpsPosition;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class GeofenceService
{
    private ?string $searchQuery = null;

    /**
     * Filter geofences by search query via method chaining.
     */
    public function withSearch(?string $query): self
    {
        $this->searchQuery = $query;
        return $this;
    }

    /**
     * Fetch all geofences with active occupancy count calculation.
     */
    public function getGeofencesWithOccupancy(): Collection
    {
        $query = Geofence::query();

        if ($this->searchQuery) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(type) LIKE ?', [$search]);
        }

        $geofences = $query->orderBy('name', 'asc')->get();
        $vehicles = Vehicle::with('latestPosition')->get();

        return $geofences->map(function (Geofence $geofence) use ($vehicles) {
            $occupiedCount = 0;

            $vehicles->each(function (Vehicle $vehicle) use ($geofence, &$occupiedCount) {
                $pos = $vehicle->latestPosition;
                if ($pos) {
                    $dist = $this->calculateDistanceMeters(
                        $geofence->latitude,
                        $geofence->longitude,
                        $pos->latitude,
                        $pos->longitude
                    );

                    if ($dist <= $geofence->radius_meters) {
                        $occupiedCount++;
                    }
                }
            });

            return [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'type' => $geofence->type,
                'radius' => ($geofence->radius_meters / 1000) . ' km',
                'radius_meters' => $geofence->radius_meters,
                'maxSpeed' => $geofence->max_speed_kmh . ' km/h',
                'max_speed_kmh' => $geofence->max_speed_kmh,
                'vehicles' => $occupiedCount,
                'active' => $geofence->is_active,
                'lat' => $geofence->latitude,
                'lng' => $geofence->longitude,
            ];
        });
    }

    /**
     * Create a new Geofence perimeter.
     */
    public function createGeofence(array $payload): Geofence
    {
        return Geofence::create([
            'name' => trim($payload['name']),
            'type' => $payload['type'] ?? 'Depot Yard',
            'latitude' => (float)$payload['latitude'],
            'longitude' => (float)$payload['longitude'],
            'radius_meters' => (int)($payload['radius_meters'] ?? 2500),
            'max_speed_kmh' => (int)($payload['max_speed_kmh'] ?? 40),
            'is_active' => true,
        ]);
    }

    /**
     * Toggle Geofence active status.
     */
    public function toggleGeofence(int $id): ?Geofence
    {
        $geofence = Geofence::find($id);
        if ($geofence) {
            $geofence->update(['is_active' => !$geofence->is_active]);
        }
        return $geofence;
    }

    /**
     * Evaluate vehicle positions against active geofences and trigger alerts.
     */
    public function evaluateVehicleGeofenceAlerts(Vehicle $vehicle): array
    {
        $activeGeofences = Geofence::where('is_active', true)->get();
        $positions = GpsPosition::where('vehicle_id', $vehicle->id)
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($positions->isEmpty() || $activeGeofences->isEmpty()) {
            return [];
        }

        $alertsCreated = [];
        $latestPos = $positions->last();

        foreach ($activeGeofences as $geofence) {
            $dist = $this->calculateDistanceMeters(
                $geofence->latitude,
                $geofence->longitude,
                $latestPos->latitude,
                $latestPos->longitude
            );

            $isInside = $dist <= $geofence->radius_meters;

            // 1. Geofence Speed Violation Alert
            if ($isInside && $latestPos->speed_kmh > $geofence->max_speed_kmh) {
                $alertsCreated[] = Alert::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'geofence_id' => $geofence->id,
                        'type' => 'overspeed',
                        'triggered_at' => $latestPos->recorded_at,
                    ],
                    [
                        'severity' => 'critical',
                        'title' => "Geofence Speed Limit Violation ({$vehicle->code})",
                        'description' => "{$vehicle->name} exceeded speed limit in {$geofence->name}: {$latestPos->speed_kmh} km/h (Cap: {$geofence->max_speed_kmh} km/h).",
                    ]
                );
            }

            // 2. Geofence Boundary Entry / Exit Alert
            if ($isInside) {
                $alertsCreated[] = Alert::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'geofence_id' => $geofence->id,
                        'type' => 'entry',
                        'triggered_at' => $latestPos->recorded_at,
                    ],
                    [
                        'severity' => 'info',
                        'title' => "Geofence Entry ({$vehicle->code})",
                        'description' => "{$vehicle->name} entered perimeter: {$geofence->name}.",
                    ]
                );
            }
        }

        return $alertsCreated;
    }

    /**
     * Calculate distance between two lat/lng coordinates in meters via Haversine formula.
     */
    public function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
