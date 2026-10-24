<?php

namespace App\Services;

use App\Analytics\TelemetryPoint;
use App\Analytics\TripStateMachine;
use App\Models\Alert;
use App\Models\GpsPosition;
use App\Models\LoadEvent;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleAnalyticsState;
use App\Models\VehicleStop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TripAnalyticsService
{
    private const CHUNK_SIZE = 1000;

    /**
     * Load events that raise an operator alert: [severity, title].
     */
    private const ALERTING_EVENTS = [
        'suspected_unauthorized_loading' => ['warning', 'Suspected Unauthorized Loading'],
        'confirmed_unauthorized_loading' => ['critical', 'Unauthorized Loading Confirmed'],
        'unauthorized_offload' => ['critical', 'Unauthorized Cargo Offloaded'],
    ];

    /**
     * Incrementally run the trip state machine over positions not yet processed for the vehicle.
     */
    public function processVehicle(Vehicle $vehicle): array
    {
        $threshold = $vehicle->getLoadThresholdVoltage();
        if (! $threshold) {
            return ['vehicle' => $vehicle->code, 'processed' => 0, 'events' => 0, 'skipped' => 'Base voltage not calibrated'];
        }

        $record = VehicleAnalyticsState::firstOrNew(['vehicle_id' => $vehicle->id]);
        $machine = new TripStateMachine(config('trip_analytics'), $threshold, $record->state);
        $cutoff = now()->subSeconds((int) config('trip_analytics.processing_lag_seconds'))->format('Y-m-d H:i:s');
        $processed = 0;
        $eventCount = 0;

        do {
            // Records without a GNSS fix carry the device default time (2004-01-01) and 0,0 coordinates
            $query = GpsPosition::where('vehicle_id', $vehicle->id)
                ->where('is_valid', true)
                ->where('recorded_at', '<=', $cutoff);

            if ($record->last_recorded_at) {
                $lastAt = $record->last_recorded_at->format('Y-m-d H:i:s');
                $lastId = $record->last_position_id;
                $query->where(function ($q) use ($lastAt, $lastId) {
                    $q->where('recorded_at', '>', $lastAt)
                        ->orWhere(fn ($q) => $q->where('recorded_at', $lastAt)->where('id', '>', $lastId));
                });
            }

            $positions = $query->orderBy('recorded_at')->orderBy('id')->limit(self::CHUNK_SIZE)->get();
            if ($positions->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($positions, $machine, $vehicle, $record, &$eventCount) {
                foreach ($positions as $position) {
                    $events = $machine->feed(TelemetryPoint::fromPosition($position));
                    $this->persistEvents($vehicle, $events);
                    $eventCount += count($events);
                }

                // Keep the open trip's running distance current, not just on completion
                $state = $machine->state();
                if ($state['trip_key'] !== null) {
                    $this->trip($vehicle, $state['trip_key'])?->update(['distance_m' => (int) round($state['trip_distance_m'])]);
                }

                $last = $positions->last();
                $record->fill([
                    'last_position_id' => $last->id,
                    'last_recorded_at' => $last->recorded_at,
                    'state' => $machine->state(),
                ])->save();
            });

            $processed += $positions->count();
        } while ($positions->count() === self::CHUNK_SIZE);

        return ['vehicle' => $vehicle->code, 'processed' => $processed, 'events' => $eventCount, 'skipped' => null];
    }

    /**
     * Discard all derived analytics for the vehicle and replay its full position history.
     * Use after thresholds or base voltage calibration change.
     */
    public function rebuildVehicle(Vehicle $vehicle): array
    {
        DB::transaction(function () use ($vehicle) {
            $alertIds = LoadEvent::where('vehicle_id', $vehicle->id)->whereNotNull('alert_id')->pluck('alert_id');

            LoadEvent::where('vehicle_id', $vehicle->id)->delete();
            Trip::where('vehicle_id', $vehicle->id)->delete();
            VehicleStop::where('vehicle_id', $vehicle->id)->delete();
            Alert::whereIn('id', $alertIds)->delete();
            VehicleAnalyticsState::where('vehicle_id', $vehicle->id)->delete();
        });

        return $this->processVehicle($vehicle);
    }

    private function persistEvents(Vehicle $vehicle, array $events): void
    {
        foreach ($events as $event) {
            match ($event['type']) {
                'stop' => $this->persistStop($vehicle, $event['stop']),
                'trip_pending' => Trip::create([
                    'vehicle_id' => $vehicle->id,
                    'start_stop_id' => $this->stopId($vehicle, $event['trip_key']),
                    'status' => 'pending',
                    'baseline_load' => $event['baseline_load'],
                    'loaded_at' => Carbon::createFromTimestamp($event['at']),
                ]),
                'trip_started' => $this->trip($vehicle, $event['trip_key'])?->update([
                    'status' => 'active',
                    'baseline_load' => $event['baseline_load'],
                    'confirmed_at' => Carbon::createFromTimestamp($event['at']),
                ]),
                'trip_closing' => $this->trip($vehicle, $event['trip_key'])?->update([
                    'status' => 'closing',
                    'end_stop_id' => $this->stopId($vehicle, $event['stop_key']),
                    'unloaded_at' => Carbon::createFromTimestamp($event['at']),
                ]),
                'trip_reopened' => $this->trip($vehicle, $event['trip_key'])?->update([
                    'status' => 'active',
                    'end_stop_id' => null,
                    'unloaded_at' => null,
                ]),
                'trip_completed' => $this->trip($vehicle, $event['trip_key'])?->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::createFromTimestamp($event['at']),
                    'distance_m' => $event['distance_m'],
                    'is_billable' => true,
                ]),
                'trip_cancelled' => $this->trip($vehicle, $event['trip_key'])?->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::createFromTimestamp($event['at']),
                    'cancel_reason' => $event['reason'],
                ]),
                'load_event' => $this->persistLoadEvent($vehicle, $event),
            };
        }
    }

    private function persistStop(Vehicle $vehicle, array $stop): void
    {
        VehicleStop::updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'start_position_id' => $stop['start_position_id']],
            [
                'end_position_id' => $stop['end_position_id'],
                'started_at' => Carbon::createFromTimestamp($stop['started_at']),
                'ended_at' => Carbon::createFromTimestamp($stop['ended_at']),
                'duration_seconds' => $stop['duration_seconds'],
                'latitude' => $stop['latitude'],
                'longitude' => $stop['longitude'],
                'ignition_off' => $stop['ignition_off'],
                'load_before' => $stop['load_before'],
                'load_after' => $stop['load_after'],
                'load_delta' => $stop['load_delta'],
            ]
        );
    }

    private function persistLoadEvent(Vehicle $vehicle, array $event): void
    {
        $occurredAt = Carbon::createFromTimestamp($event['at']);
        $alertId = null;

        if (isset(self::ALERTING_EVENTS[$event['kind']])) {
            [$severity, $title] = self::ALERTING_EVENTS[$event['kind']];
            $alertId = Alert::create([
                'vehicle_id' => $vehicle->id,
                'type' => $event['kind'],
                'severity' => $severity,
                'title' => $title,
                'description' => sprintf(
                    '%s at %.5f, %.5f (load %d mV, %+d mV vs trip baseline). %s.',
                    $vehicle->code,
                    $event['latitude'],
                    $event['longitude'],
                    $event['load_value'],
                    $event['load_delta'] ?? 0,
                    $event['description']
                ),
                'is_resolved' => false,
                'triggered_at' => now(),
            ])->id;
        }

        LoadEvent::create([
            'vehicle_id' => $vehicle->id,
            'trip_id' => $event['trip_key'] ? $this->trip($vehicle, $event['trip_key'])?->id : null,
            'stop_id' => $this->stopId($vehicle, $event['stop_key']),
            'alert_id' => $alertId,
            'type' => $event['kind'],
            'occurred_at' => $occurredAt,
            'latitude' => $event['latitude'],
            'longitude' => $event['longitude'],
            'load_value' => $event['load_value'],
            'load_delta' => $event['load_delta'],
            'description' => $event['description'],
        ]);
    }

    private function stopId(Vehicle $vehicle, ?int $startPositionId): ?int
    {
        if ($startPositionId === null) {
            return null;
        }

        return VehicleStop::where('vehicle_id', $vehicle->id)
            ->where('start_position_id', $startPositionId)
            ->value('id');
    }

    private function trip(Vehicle $vehicle, int $tripKey): ?Trip
    {
        return Trip::where('vehicle_id', $vehicle->id)
            ->where('start_stop_id', $this->stopId($vehicle, $tripKey))
            ->first();
    }
}
