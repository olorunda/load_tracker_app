<?php

namespace App\Http\Controllers;

use App\Jobs\EvaluateGeofenceAlertsJob;
use App\Models\Alert;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    public function __construct(
        private GeofenceService $geofenceService
    ) {}

    /**
     * Get list of geofences with calculated vehicle occupancy.
     */
    public function index(Request $request): JsonResponse
    {
        $geofences = $this->geofenceService
            ->withSearch($request->query('q'))
            ->getGeofencesWithOccupancy();

        return response()->json([
            'success' => true,
            'count' => $geofences->count(),
            'geofences' => $geofences,
        ]);
    }

    /**
     * Create a new geofence perimeter.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meters' => 'nullable|integer|min:50|max:100000',
            'max_speed_kmh' => 'nullable|integer|min:5|max:200',
        ]);

        $geofence = $this->geofenceService->createGeofence($validated);

        // Immediately evaluate geofence alerts for newly added perimeter
        EvaluateGeofenceAlertsJob::dispatchSync();

        return response()->json([
            'success' => true,
            'message' => 'Geofence perimeter created successfully.',
            'geofence' => $geofence,
        ]);
    }

    /**
     * Toggle Geofence active status.
     */
    public function toggle(int $id): JsonResponse
    {
        $geofence = $this->geofenceService->toggleGeofence($id);

        if (!$geofence) {
            return response()->json(['success' => false, 'message' => 'Geofence not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Geofence '{$geofence->name}' is now " . ($geofence->is_active ? 'Active' : 'Disabled'),
            'geofence' => $geofence,
        ]);
    }

    /**
     * Trigger background job to evaluate alerts across active vehicles.
     */
    public function evaluate(): JsonResponse
    {
        EvaluateGeofenceAlertsJob::dispatchSync();

        $activeAlertsCount = Alert::where('is_resolved', false)->count();

        return response()->json([
            'success' => true,
            'message' => 'Geofence alert evaluation job executed.',
            'active_alerts_count' => $activeAlertsCount,
        ]);
    }

    /**
     * Get active alert list for Alerts dashboard page.
     */
    public function alerts(): JsonResponse
    {
        $alerts = Alert::with(['vehicle', 'geofence'])
            ->orderBy('triggered_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (Alert $alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'vehicle_code' => $alert->vehicle ? $alert->vehicle->code : 'TRK-SYS',
                    'vehicle_name' => $alert->vehicle ? $alert->vehicle->name : 'Fleet Vehicle',
                    'geofence_name' => $alert->geofence ? $alert->geofence->name : 'System Boundary',
                    'is_resolved' => $alert->is_resolved,
                    'time_ago' => $alert->triggered_at ? $alert->triggered_at->diffForHumans() : 'Recently',
                    'triggered_at' => $alert->triggered_at ? $alert->triggered_at->format('M d, Y H:i:s UTC') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $alerts->count(),
            'alerts' => $alerts,
        ]);
    }
}
