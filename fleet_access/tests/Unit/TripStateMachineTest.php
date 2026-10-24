<?php

namespace Tests\Unit;

use App\Analytics\TelemetryPoint;
use App\Analytics\TripStateMachine;
use PHPUnit\Framework\TestCase;

class TripStateMachineTest extends TestCase
{
    private const EMPTY = 12000;

    private const THRESHOLD = 13000;

    private const LOADED = 14000;

    private const OVERLOADED = 17000;

    private const CONFIG = [
        'stop_speed_kmh' => 3,
        'move_speed_kmh' => 5,
        'min_stop_seconds' => 60,
        'trip_confirm_distance_m' => 5000,
        'unauthorized_confirm_distance_m' => 3000,
        'unauthorized_increase_pct' => 15,
        'load_window_samples' => 5,
        'max_odometer_jump_m' => 20000,
    ];

    private array $points = [];

    private int $id = 0;

    private int $time = 1_760_000_000;

    private int $odometer = 1_000_000;

    private float $latitude = 6.5;

    public function test_load_cycle_creates_one_billable_trip(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(10, self::LOADED);
        $this->park(60, self::LOADED);
        $this->drive(10, self::EMPTY);

        $events = $this->replay();

        $this->assertSame(
            ['trip_pending', 'loading', 'trip_started', 'unloading', 'trip_closing', 'trip_completed'],
            $this->sequence($events)
        );
        $completed = $this->first($events, 'trip_completed');
        $this->assertGreaterThan(10000, $completed['distance_m']);
    }

    public function test_overnight_park_mid_trip_does_not_create_a_new_trip(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(10, self::LOADED);
        $this->park(10 * 60, self::LOADED);
        $this->drive(10, self::LOADED);
        $this->park(60, self::LOADED);
        $this->drive(10, self::EMPTY);

        $events = $this->replay();

        $this->assertCount(1, $this->ofType($events, 'trip_pending'));
        $this->assertCount(1, $this->ofType($events, 'trip_completed'));
        $this->assertCount(3, $this->ofType($events, 'stop'));
    }

    public function test_parking_without_a_load_change_never_starts_a_trip(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(12 * 60, self::EMPTY);
        $this->drive(10, self::EMPTY);

        $events = $this->replay();

        $this->assertSame(['stop'], array_column($events, 'type'));
    }

    public function test_load_removed_before_confirmation_distance_cancels_trip(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(2, self::LOADED);
        $this->park(20, self::LOADED);
        $this->drive(10, self::EMPTY);

        $events = $this->replay();

        $this->assertContains('trip_cancelled', $this->sequence($events));
        $this->assertNotContains('trip_started', $this->sequence($events));
    }

    public function test_stop_during_trip_confirmation_resets_the_continuous_distance(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(4, self::LOADED);
        $this->park(5, self::LOADED, ignition: true);
        $this->drive(4, self::LOADED);

        $this->assertNotContains('trip_started', $this->sequence($this->replay()));

        $this->drive(2, self::LOADED);

        $this->assertContains('trip_started', $this->sequence($this->replay()));
    }

    public function test_unauthorized_loading_is_suspected_confirmed_and_offload_located(): void
    {
        $this->startActiveTrip();
        $this->park(5, self::LOADED, ignition: true);
        $this->drive(5, self::OVERLOADED);
        $this->drive(5, self::OVERLOADED);
        $offloadLatitude = $this->latitude;
        $this->park(5, self::OVERLOADED, ignition: true);
        $this->drive(5, self::LOADED);
        $this->park(60, self::LOADED);
        $this->drive(10, self::EMPTY);

        $events = $this->replay();

        $this->assertSame(
            ['suspected_unauthorized_loading', 'confirmed_unauthorized_loading', 'unauthorized_offload', 'unloading'],
            array_slice(array_column($this->ofType($events, 'load_event'), 'kind'), 1)
        );
        $offload = $this->firstLoadEvent($events, 'unauthorized_offload');
        $this->assertEqualsWithDelta($offloadLatitude, $offload['latitude'], 0.0001);
        $this->assertSame(1, count($this->ofType($events, 'trip_completed')));
    }

    public function test_load_back_to_baseline_within_confirmation_distance_dismisses_suspicion(): void
    {
        $this->startActiveTrip();
        $this->park(5, self::LOADED, ignition: true);
        $this->drive(1, self::OVERLOADED);
        $this->park(3, self::OVERLOADED, ignition: true);
        $this->drive(5, self::LOADED);

        $kinds = array_column($this->ofType($this->replay(), 'load_event'), 'kind');

        $this->assertContains('unauthorized_loading_dismissed', $kinds);
        $this->assertNotContains('confirmed_unauthorized_loading', $kinds);
    }

    public function test_voltage_spikes_and_traffic_stops_are_ignored(): void
    {
        $this->startActiveTrip();
        $this->drive(1, self::LOADED);
        $this->point(50, self::OVERLOADED);
        $this->drive(1, self::LOADED);
        $this->park(1, self::LOADED, ignition: true, interval: 20, samples: 2);
        $this->drive(5, self::LOADED);

        $events = $this->replay();

        $this->assertCount(1, $this->ofType($events, 'stop'));
        $this->assertCount(1, $this->ofType($events, 'load_event'));
    }

    public function test_state_can_be_resumed_between_batches(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(10, self::LOADED);
        $this->park(60, self::LOADED);
        $this->drive(10, self::EMPTY);

        $single = $this->replay();

        $split = [];
        $state = null;
        foreach (array_chunk($this->points, 7) as $batch) {
            $machine = new TripStateMachine(self::CONFIG, self::THRESHOLD, $state);
            foreach ($batch as $point) {
                array_push($split, ...$machine->feed($point));
            }
            $state = json_decode(json_encode($machine->state()), true);
        }

        $this->assertEquals($single, $split);
    }

    private function startActiveTrip(): void
    {
        $this->drive(2, self::EMPTY);
        $this->park(30, self::EMPTY);
        $this->drive(6, self::LOADED);
    }

    private function drive(float $km, int $load, int $speed = 50, int $interval = 30): void
    {
        $stepM = $speed / 3.6 * $interval;
        $steps = (int) ceil($km * 1000 / $stepM);

        for ($i = 0; $i < $steps; $i++) {
            $this->time += $interval;
            $this->odometer += (int) round($stepM);
            $this->latitude += $stepM / 111000;
            $this->points[] = $this->makePoint($speed, true, $load);
        }
    }

    private function park(int $minutes, int $load, bool $ignition = false, int $interval = 60, ?int $samples = null): void
    {
        $samples ??= max(1, intdiv($minutes * 60, $interval));

        for ($i = 0; $i < $samples; $i++) {
            $this->time += $interval;
            $this->points[] = $this->makePoint(0, $ignition, $load);
        }
    }

    private function point(int $speed, int $load): void
    {
        $this->time += 30;
        $this->odometer += 400;
        $this->latitude += 400 / 111000;
        $this->points[] = $this->makePoint($speed, true, $load);
    }

    private function makePoint(int $speed, bool $ignition, int $load): TelemetryPoint
    {
        return new TelemetryPoint(++$this->id, $this->time, $this->latitude, 3.3, $speed, $ignition, $load, $this->odometer);
    }

    private function replay(): array
    {
        $machine = new TripStateMachine(self::CONFIG, self::THRESHOLD);
        $events = [];
        foreach ($this->points as $point) {
            array_push($events, ...$machine->feed($point));
        }

        return $events;
    }

    /**
     * Trip lifecycle and load event kinds in order, without stop records.
     */
    private function sequence(array $events): array
    {
        $sequence = [];
        foreach ($events as $event) {
            if ($event['type'] === 'load_event') {
                $sequence[] = $event['kind'];
            } elseif ($event['type'] !== 'stop') {
                $sequence[] = $event['type'];
            }
        }

        return $sequence;
    }

    private function ofType(array $events, string $type): array
    {
        return array_values(array_filter($events, fn ($e) => $e['type'] === $type));
    }

    private function first(array $events, string $type): array
    {
        return $this->ofType($events, $type)[0];
    }

    private function firstLoadEvent(array $events, string $kind): array
    {
        return array_values(array_filter($this->ofType($events, 'load_event'), fn ($e) => $e['kind'] === $kind))[0];
    }
}
