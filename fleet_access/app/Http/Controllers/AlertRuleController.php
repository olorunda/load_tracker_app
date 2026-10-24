<?php

namespace App\Http\Controllers;

use App\Jobs\EvaluateGeofenceAlertsJob;
use App\Models\Alert;
use App\Models\AlertRule;
use Database\Seeders\AlertRuleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    /**
     * List all configured notification triggers and alert rules.
     */
    public function index(): JsonResponse
    {
        if (AlertRule::count() === 0) {
            app(AlertRuleSeeder::class)->run();
        }

        $rules = AlertRule::orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'count' => $rules->count(),
            'rules' => $rules,
        ]);
    }

    /**
     * Store and activate a newly configured alert rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'target_group' => 'required|string|max:100',
            'severity' => 'required|string|in:critical,warning,info',
            'threshold_value' => 'nullable|numeric',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $icon = AlertRule::resolveIcon($validated['type']);

        $rule = AlertRule::create([
            'name' => trim($validated['name']),
            'type' => trim($validated['type']),
            'target_group' => trim($validated['target_group']),
            'severity' => $validated['severity'],
            'threshold_value' => isset($validated['threshold_value']) && $validated['threshold_value'] !== '' ? (float)$validated['threshold_value'] : null,
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $validated['is_active'] ?? true,
            'icon' => $icon,
        ]);

        // Trigger immediate evaluation against fleet
        try {
            EvaluateGeofenceAlertsJob::dispatchSync();
        } catch (\Throwable $e) {
            // Ignore evaluation failure during creation
        }

        return response()->json([
            'success' => true,
            'message' => "Alert rule '{$rule->name}' successfully configured and saved.",
            'rule' => $rule,
        ], 201);
    }

    /**
     * Toggle alert rule enabled/disabled status.
     */
    public function toggle(int $id): JsonResponse
    {
        $rule = AlertRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();

        return response()->json([
            'success' => true,
            'message' => "Alert rule '{$rule->name}' is now " . ($rule->is_active ? 'enabled' : 'disabled') . ".",
            'rule' => $rule,
        ]);
    }

    /**
     * Delete an alert rule.
     */
    public function destroy(int $id): JsonResponse
    {
        $rule = AlertRule::findOrFail($id);
        $name = $rule->name;
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => "Alert rule '{$name}' has been deleted.",
        ]);
    }

    /**
     * Get active/recent alerts list.
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
                    'is_resolved' => (bool)$alert->is_resolved,
                    'time_ago' => $alert->triggered_at ? $alert->triggered_at->diffForHumans() : 'Recently',
                    'triggered_at' => $alert->triggered_at ? $alert->triggered_at->format('M d, Y H:i:s T') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $alerts->count(),
            'alerts' => $alerts,
        ]);
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->update(['is_resolved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Alert has been marked as resolved.',
            'alert_id' => $alert->id,
        ]);
    }

    /**
     * Mark all active alerts as resolved.
     */
    public function resolveAll(): JsonResponse
    {
        Alert::where('is_resolved', false)->update(['is_resolved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All active alerts marked as resolved.',
        ]);
    }

    /**
     * Evaluate rules across all fleet vehicles.
     */
    public function evaluate(): JsonResponse
    {
        EvaluateGeofenceAlertsJob::dispatchSync();

        $activeAlertsCount = Alert::where('is_resolved', false)->count();

        return response()->json([
            'success' => true,
            'message' => 'Alert evaluation executed across fleet.',
            'active_alerts_count' => $activeAlertsCount,
        ]);
    }
}
