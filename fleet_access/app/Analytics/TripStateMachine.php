<?php

namespace App\Analytics;

/**
 * Pure, persistence-free trip & load state machine.
 *
 * Positions are fed in chronological order. They are segmented into stops, and each stop is
 * classified by comparing the median moving load before it against the median moving load after it.
 * Comparing moving samples on both sides keeps the ignition state (alternator charge on IO 66) equal.
 *
 * Trip lifecycle:   idle -> pending -> active -> closing -> idle (completed)
 * Unauthorized:     none -> suspected -> confirmed -> offload_pending -> none   (only while active)
 *
 * The machine emits plain array events; TripAnalyticsService persists them.
 */
class TripStateMachine
{
    public const TRIP_IDLE = 'idle';

    public const TRIP_PENDING = 'pending';

    public const TRIP_ACTIVE = 'active';

    public const TRIP_CLOSING = 'closing';

    public const THEFT_NONE = 'none';

    public const THEFT_SUSPECTED = 'suspected';

    public const THEFT_CONFIRMED = 'confirmed';

    public const THEFT_OFFLOAD_PENDING = 'offload_pending';

    private array $state;

    private array $events = [];

    public function __construct(
        private readonly array $config,
        private readonly int $loadThreshold,
        ?array $state = null,
    ) {
        $this->state = $state ?? self::initialState();
    }

    public static function initialState(): array
    {
        return [
            'last_point' => null,
            'candidate' => null,       // stop in progress (speed below stop threshold)
            'open_stop' => null,       // finished stop waiting for post-stop load samples
            'window' => [],            // recent moving load samples (mV)
            'trip' => self::TRIP_IDLE,
            'trip_key' => null,        // start_position_id of the loading stop
            'baseline' => null,        // trip load after loading (mV)
            'trip_distance_m' => 0,
            'confirm_distance_m' => 0, // continuous moving distance since the last stop
            'theft' => self::THEFT_NONE,
            'theft_stop' => null,
        ];
    }

    public function state(): array
    {
        return $this->state;
    }

    /**
     * Feed the next chronological point and return the events it produced.
     */
    public function feed(TelemetryPoint $point): array
    {
        $this->events = [];
        $previous = $this->state['last_point'];

        if ($previous !== null && $point->timestamp < $previous['ts']) {
            return [];
        }

        $distance = $previous !== null ? $this->distanceBetween($previous, $point) : 0;
        $this->state['last_point'] = [
            'id' => $point->id,
            'ts' => $point->timestamp,
            'lat' => $point->latitude,
            'lng' => $point->longitude,
            'odometer' => $point->odometerM,
        ];

        if ($point->speedKmh < $this->config['stop_speed_kmh']) {
            if ($this->state['candidate'] === null) {
                $this->state['candidate'] = [
                    'start_position_id' => $point->id,
                    'started_at' => $point->timestamp,
                    'latitude' => $point->latitude,
                    'longitude' => $point->longitude,
                    'ignition_off' => ! $point->ignition,
                ];
            } elseif (! $point->ignition) {
                $this->state['candidate']['ignition_off'] = true;
            }

            return $this->events;
        }

        // Hysteresis band between stop and move speed: neither starts nor ends a stop
        if ($point->speedKmh <= $this->config['move_speed_kmh']) {
            if ($this->state['candidate'] !== null && ! $point->ignition) {
                $this->state['candidate']['ignition_off'] = true;
            }

            return $this->events;
        }

        if ($this->state['candidate'] !== null) {
            $candidate = $this->state['candidate'];
            $this->state['candidate'] = null;

            if ($point->timestamp - $candidate['started_at'] >= $this->config['min_stop_seconds']) {
                $this->finalizeStop($candidate, $point);
            }
        }

        $this->state['confirm_distance_m'] += $distance;
        if ($this->state['trip'] !== self::TRIP_IDLE) {
            $this->state['trip_distance_m'] += $distance;
        }

        if ($point->load > 0) {
            $this->state['window'][] = $point->load;
            $this->state['window'] = array_slice($this->state['window'], -$this->windowSize());
        }

        if ($this->state['open_stop'] !== null && count($this->state['window']) >= $this->windowSize()) {
            $this->evaluateOpenStop();
        }

        if ($this->state['open_stop'] === null) {
            $this->checkConfirmations($point);
        }

        return $this->events;
    }

    private function finalizeStop(array $candidate, TelemetryPoint $departure): void
    {
        if ($this->state['open_stop'] !== null) {
            $this->evaluateOpenStop();
        }

        $this->state['open_stop'] = [
            'start_position_id' => $candidate['start_position_id'],
            'end_position_id' => $departure->id,
            'started_at' => $candidate['started_at'],
            'ended_at' => $departure->timestamp,
            'duration_seconds' => $departure->timestamp - $candidate['started_at'],
            'latitude' => $candidate['latitude'],
            'longitude' => $candidate['longitude'],
            'ignition_off' => $candidate['ignition_off'],
            'load_before' => $this->median($this->state['window']),
        ];

        // A stop breaks distance continuity and starts a fresh post-stop load window
        $this->state['window'] = [];
        $this->state['confirm_distance_m'] = 0;
    }

    private function evaluateOpenStop(): void
    {
        $stop = $this->state['open_stop'];
        $this->state['open_stop'] = null;

        $after = $this->median($this->state['window']);
        $before = $stop['load_before'];
        $stop['load_after'] = $after;
        $stop['load_delta'] = ($before !== null && $after !== null) ? $after - $before : null;

        $this->emit(['type' => 'stop', 'stop' => $stop]);

        if ($after !== null) {
            $this->applyStop($stop, $before, $after);
        }
    }

    private function applyStop(array $stop, ?int $before, int $after): void
    {
        $loadedAfter = $after > $this->loadThreshold;

        switch ($this->state['trip']) {
            case self::TRIP_IDLE:
                if ($before !== null && $before <= $this->loadThreshold && $loadedAfter) {
                    $this->startPendingTrip($stop, $after);
                }
                break;

            case self::TRIP_PENDING:
                if (! $loadedAfter) {
                    $this->loadEvent('unloading', $stop, $after, 'Load removed before trip was confirmed');
                    $this->cancelTrip('Load dropped before the confirmation distance was reached', $stop['ended_at']);
                } else {
                    // Top-up loading before departure: the latest reading becomes the trip baseline
                    $this->state['baseline'] = $after;
                }
                break;

            case self::TRIP_ACTIVE:
                $this->applyStopDuringActiveTrip($stop, $after);
                break;

            case self::TRIP_CLOSING:
                if ($loadedAfter) {
                    // Unloaded then reloaded before driving away: close the old trip, begin a new one
                    $this->completeTrip($stop['started_at']);
                    $this->startPendingTrip($stop, $after);
                }
                break;
        }
    }

    private function applyStopDuringActiveTrip(array $stop, int $after): void
    {
        $theftThreshold = $this->theftThreshold();

        if ($after <= $this->loadThreshold) {
            if ($this->state['theft'] === self::THEFT_CONFIRMED) {
                $this->loadEvent('unauthorized_offload', $stop, $after, 'Unauthorized cargo offloaded together with trip cargo');
            } elseif ($this->state['theft'] === self::THEFT_OFFLOAD_PENDING) {
                $this->loadEvent('unauthorized_offload', $this->state['theft_stop'], $after, 'Unauthorized cargo offloaded');
            } elseif ($this->state['theft'] === self::THEFT_SUSPECTED) {
                $this->loadEvent('unauthorized_loading_dismissed', $this->state['theft_stop'], $after, 'Vehicle unloaded before suspected loading was confirmed');
            }
            $this->resetTheft();

            $this->loadEvent('unloading', $stop, $after, 'Trip cargo unloaded');
            $this->state['trip'] = self::TRIP_CLOSING;
            $this->emit([
                'type' => 'trip_closing',
                'trip_key' => $this->state['trip_key'],
                'stop_key' => $stop['start_position_id'],
                'at' => $stop['started_at'],
            ]);

            return;
        }

        $elevated = $after > $theftThreshold;

        switch ($this->state['theft']) {
            case self::THEFT_NONE:
                if ($elevated) {
                    $this->state['theft'] = self::THEFT_SUSPECTED;
                    $this->state['theft_stop'] = $stop;
                    $this->loadEvent('suspected_unauthorized_loading', $stop, $after, 'Load rose above trip baseline during a stop');
                }
                break;

            case self::THEFT_SUSPECTED:
                if (! $elevated) {
                    $this->loadEvent('unauthorized_loading_dismissed', $this->state['theft_stop'], $after, 'Load returned to trip baseline before confirmation');
                    $this->resetTheft();
                }
                break;

            case self::THEFT_CONFIRMED:
                if (! $elevated) {
                    $this->state['theft'] = self::THEFT_OFFLOAD_PENDING;
                    $this->state['theft_stop'] = $stop;
                }
                break;

            case self::THEFT_OFFLOAD_PENDING:
                if ($elevated) {
                    $this->state['theft'] = self::THEFT_CONFIRMED;
                    $this->state['theft_stop'] = null;
                }
                break;
        }
    }

    private function checkConfirmations(TelemetryPoint $point): void
    {
        $load = $this->median($this->state['window']);
        if ($load === null) {
            return;
        }

        $distance = $this->state['confirm_distance_m'];
        $tripDistance = $this->config['trip_confirm_distance_m'];
        $theftDistance = $this->config['unauthorized_confirm_distance_m'];

        if ($this->state['trip'] === self::TRIP_PENDING && $distance >= $tripDistance) {
            if ($load > $this->loadThreshold) {
                $this->state['trip'] = self::TRIP_ACTIVE;
                $this->emit([
                    'type' => 'trip_started',
                    'trip_key' => $this->state['trip_key'],
                    'baseline_load' => $this->state['baseline'],
                    'at' => $point->timestamp,
                ]);
            } else {
                $this->cancelTrip('Load not sustained over the confirmation distance', $point->timestamp);
            }

            return;
        }

        if ($this->state['trip'] === self::TRIP_CLOSING && $distance >= $tripDistance) {
            if ($load <= $this->loadThreshold) {
                $this->completeTrip($point->timestamp);
            } else {
                // The unloading reading was not sustained; the trip is still in progress
                $this->state['trip'] = self::TRIP_ACTIVE;
                $this->emit(['type' => 'trip_reopened', 'trip_key' => $this->state['trip_key'], 'at' => $point->timestamp]);
            }

            return;
        }

        if ($this->state['trip'] !== self::TRIP_ACTIVE || $distance < $theftDistance) {
            return;
        }

        $elevated = $load > $this->theftThreshold();

        if ($this->state['theft'] === self::THEFT_SUSPECTED) {
            if ($elevated) {
                $this->state['theft'] = self::THEFT_CONFIRMED;
                $this->loadEvent('confirmed_unauthorized_loading', $this->state['theft_stop'], $load, 'Elevated load sustained over the confirmation distance');
                $this->state['theft_stop'] = null;
            } else {
                $this->loadEvent('unauthorized_loading_dismissed', $this->state['theft_stop'], $load, 'Load returned to trip baseline over the confirmation distance');
                $this->resetTheft();
            }
        } elseif ($this->state['theft'] === self::THEFT_OFFLOAD_PENDING) {
            if (! $elevated) {
                $this->loadEvent('unauthorized_offload', $this->state['theft_stop'], $load, 'Unauthorized cargo offloaded');
                $this->resetTheft();
            } else {
                $this->state['theft'] = self::THEFT_CONFIRMED;
                $this->state['theft_stop'] = null;
            }
        }
    }

    private function startPendingTrip(array $stop, int $load): void
    {
        $this->state['trip'] = self::TRIP_PENDING;
        $this->state['trip_key'] = $stop['start_position_id'];
        $this->state['baseline'] = $load;
        $this->state['trip_distance_m'] = $this->state['confirm_distance_m'];
        $this->resetTheft();

        $this->emit([
            'type' => 'trip_pending',
            'trip_key' => $stop['start_position_id'],
            'baseline_load' => $load,
            'at' => $stop['started_at'],
        ]);
        $this->loadEvent('loading', $stop, $load, 'Trip cargo loaded');
    }

    private function completeTrip(int $at): void
    {
        $this->emit([
            'type' => 'trip_completed',
            'trip_key' => $this->state['trip_key'],
            'distance_m' => (int) round($this->state['trip_distance_m']),
            'at' => $at,
        ]);
        $this->resetTrip();
    }

    private function cancelTrip(string $reason, int $at): void
    {
        $this->emit([
            'type' => 'trip_cancelled',
            'trip_key' => $this->state['trip_key'],
            'reason' => $reason,
            'at' => $at,
        ]);
        $this->resetTrip();
    }

    private function resetTrip(): void
    {
        $this->state['trip'] = self::TRIP_IDLE;
        $this->state['trip_key'] = null;
        $this->state['baseline'] = null;
        $this->state['trip_distance_m'] = 0;
        $this->resetTheft();
    }

    private function resetTheft(): void
    {
        $this->state['theft'] = self::THEFT_NONE;
        $this->state['theft_stop'] = null;
    }

    private function loadEvent(string $kind, array $stop, int $load, string $description): void
    {
        $reference = $kind === 'loading' || $kind === 'unloading' ? $stop['load_before'] : $this->state['baseline'];

        $this->emit([
            'type' => 'load_event',
            'kind' => $kind,
            'trip_key' => $this->state['trip_key'],
            'stop_key' => $stop['start_position_id'],
            'at' => $stop['started_at'],
            'latitude' => $stop['latitude'],
            'longitude' => $stop['longitude'],
            'load_value' => $load,
            'load_delta' => $reference !== null ? $load - $reference : null,
            'description' => $description,
        ]);
    }

    private function emit(array $event): void
    {
        $this->events[] = $event;
    }

    private function theftThreshold(): int
    {
        return (int) round($this->state['baseline'] * (1 + $this->config['unauthorized_increase_pct'] / 100));
    }

    private function windowSize(): int
    {
        return max(1, (int) $this->config['load_window_samples']);
    }

    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2
            ? (int) $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }

    /**
     * Prefer the IO 16 odometer delta; fall back to haversine when it is missing or implausible.
     */
    private function distanceBetween(array $previous, TelemetryPoint $point): float
    {
        $delta = $point->odometerM - $previous['odometer'];
        if ($previous['odometer'] > 0 && $point->odometerM > 0 && $delta >= 0 && $delta <= $this->config['max_odometer_jump_m']) {
            return $delta;
        }

        $earthRadius = 6371000;
        $dLat = deg2rad($point->latitude - $previous['lat']);
        $dLng = deg2rad($point->longitude - $previous['lng']);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($previous['lat'])) * cos(deg2rad($point->latitude)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
