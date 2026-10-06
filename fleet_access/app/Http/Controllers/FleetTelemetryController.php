<?php

namespace App\Http\Controllers;

use App\Services\AssetRegistrationService;
use App\Services\FleetTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetTelemetryController extends Controller
{
    public function __construct(
        private FleetTelemetryService $telemetryService
    ) {}

    /**
     * Get active fleet vehicle list with latest Teltonika GPS telemetry payload.
     */
    public function assets(Request $request): JsonResponse
    {
        $assets = $this->telemetryService
            ->withStatus($request->query('status'))
            ->search($request->query('q'))
            ->getActiveFleetPayload();

        return response()->json([
            'success' => true,
            'count' => $assets->count(),
            'assets' => $assets,
        ]);
    }

    /**
     * Get vehicle GPS trail trajectory for Leaflet path rendering.
     */
    public function trail(int $id): JsonResponse
    {
        $trail = $this->telemetryService
            ->forVehicle($id)
            ->getVehicleTrailPayload();

        return response()->json([
            'success' => true,
            'trail' => $trail,
        ]);
    }

    /**
     * Get precision health telematics metrics.
     */
    public function diagnostics(): JsonResponse
    {
        $metrics = $this->telemetryService->getSystemHealthMetrics();

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Execute live telemetry diagnostics engine.
     */
    public function runDiagnostics(): JsonResponse
    {
        $result = $this->telemetryService->runDiagnosticsEngine();

        return response()->json($result);
    }

    /**
     * Get active DTC diagnostic fault codes payload.
     */
    public function dtcFaults(): JsonResponse
    {
        $faults = $this->telemetryService->getDtcFaultsPayload();

        return response()->json([
            'success' => true,
            'count' => $faults->count(),
            'faults' => $faults,
        ]);
    }

    /**
     * Resolve DTC diagnostic fault code.
     */
    public function resolveFault(int $id): JsonResponse
    {
        $resolved = $this->telemetryService->resolveDtcFault($id);

        return response()->json([
            'success' => $resolved,
            'message' => $resolved ? 'DTC fault resolved successfully.' : 'DTC fault not found.',
        ]);
    }

    /**
     * Register a new asset with GPS device IMEI mapping.
     */
    public function registerAsset(Request $request, AssetRegistrationService $registrationService): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'status' => 'required|string|max:50',
            'base_voltage' => 'nullable|integer',
            'driver_name' => 'nullable|string|max:255',
            'imei' => 'required|string|max:50',
        ]);

        $vehicle = $registrationService
            ->withPayload($validated)
            ->registerAsset();

        return response()->json([
            'success' => true,
            'message' => 'Asset successfully registered and mapped to GPS stream.',
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Get executive dashboard overview KPIs, live alert feed, and performance charts.
     */
    public function dashboardMetrics(): JsonResponse
    {
        $payload = $this->telemetryService->getExecutiveDashboardMetrics();

        return response()->json(array_merge(['success' => true], $payload));
    }

    /**
     * Calibrate or set base voltage (IO 66) for a vehicle.
     */
    public function calibrateBaseVoltage(int $id, Request $request): JsonResponse
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        $baseVoltage = $request->input('base_voltage');

        if ($baseVoltage === null || $baseVoltage <= 0) {
            $firstPos = \App\Models\GpsPosition::where('vehicle_id', $vehicle->id)
                ->where('external_voltage', '>', 0)
                ->orderBy('recorded_at', 'asc')
                ->first();
            $baseVoltage = $firstPos ? $firstPos->external_voltage : ($vehicle->current_voltage ?: 0);
        }

        $vehicle->base_voltage = (int)$baseVoltage;
        if ($vehicle->current_voltage) {
            $vehicle->status = ($vehicle->current_voltage > $vehicle->base_voltage) ? 'Loaded' : 'Empty';
        }
        $vehicle->save();

        return response()->json([
            'success' => true,
            'message' => "Base voltage set to {$vehicle->base_voltage} mV. Status is {$vehicle->status}.",
            'vehicle' => $vehicle,
        ]);
    }
}
