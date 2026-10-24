<?php

namespace App\Http\Controllers;

use App\Services\TripReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        private TripReportService $tripReportService
    ) {}

    /**
     * List detected trips with billing & unauthorized loading KPIs.
     */
    public function index(Request $request): JsonResponse
    {
        $payload = $this->tripReportService
            ->withStatus($request->query('status'))
            ->search($request->query('q'))
            ->getTripsPayload();

        return response()->json([
            'success' => true,
            'count' => $payload['trips']->count(),
            ...$payload,
        ]);
    }

    /**
     * Get a single trip with its load event timeline and GPS path.
     */
    public function show(int $id): JsonResponse
    {
        $trip = $this->tripReportService->getTripDetailPayload($id);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'trip' => $trip,
        ]);
    }

    /**
     * Get recent load events and the open trip for a vehicle.
     */
    public function vehicleLoadEvents(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->tripReportService->getVehicleLoadEventsPayload($id),
        ]);
    }
}
