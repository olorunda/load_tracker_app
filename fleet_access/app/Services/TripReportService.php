<?php

namespace App\Services;

use App\Models\GpsPosition;
use App\Models\LoadEvent;
use App\Models\Trip;
use Illuminate\Support\Collection;

class TripReportService
{
    private const OPEN_STATUSES = ['pending', 'active', 'closing'];

    /**
     * Display metadata per load event type: [label, severity, map colour, icon].
     */
    private const EVENT_META = [
        'loading' => ['Trip Cargo Loaded', 'info', '#059669', 'upload'],
        'unloading' => ['Trip Cargo Unloaded', 'info', '#004ac6', 'download'],
        'suspected_unauthorized_loading' => ['Suspected Unauthorized Loading', 'warning', '#b45309', 'help'],
        'confirmed_unauthorized_loading' => ['Unauthorized Loading Confirmed', 'critical', '#ba1a1a', 'report'],
        'unauthorized_loading_dismissed' => ['Suspicion Dismissed', 'info', '#737686', 'check_circle'],
        'unauthorized_offload' => ['Unauthorized Cargo Offloaded', 'critical', '#7c3aed', 'move_down'],
    ];

    private ?string $statusFilter = null;

    private ?string $searchQuery = null;

    /**
     * Filter by trip status via method chaining ("open" matches pending, active and closing).
     */
    public function withStatus(?string $status): self
    {
        // Always assign: the controller (and this service) can be reused across requests
        $this->statusFilter = ($status && strtolower($status) !== 'all') ? strtolower($status) : null;

        return $this;
    }

    /**
     * Apply vehicle search query via method chaining.
     */
    public function search(?string $query): self
    {
        $this->searchQuery = $query;

        return $this;
    }

    /**
     * Trip list with summary KPIs for the trips page.
     */
    public function getTripsPayload(): array
    {
        $query = Trip::with(['vehicle', 'startStop', 'endStop', 'loadEvents'])->orderBy('loaded_at', 'desc');

        if ($this->statusFilter === 'open') {
            $query->whereIn('status', self::OPEN_STATUSES);
        } elseif ($this->statusFilter === 'flagged') {
            $query->whereHas('loadEvents', fn ($q) => $q->where('type', 'confirmed_unauthorized_loading'));
        } elseif ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->searchQuery) {
            $search = '%'.strtolower($this->searchQuery).'%';
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(imei) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(driver_name) LIKE ?', [$search]);
            });
        }

        $trips = $query->limit(200)->get()->map(fn (Trip $trip) => $this->formatTrip($trip));

        $billable = Trip::where('is_billable', true);

        return [
            'trips' => $trips,
            'summary' => [
                'billable_trips' => (clone $billable)->count(),
                'billable_distance_km' => round((clone $billable)->sum('distance_m') / 1000, 1),
                'open_trips' => Trip::whereIn('status', self::OPEN_STATUSES)->count(),
                'cancelled_trips' => Trip::where('status', 'cancelled')->count(),
                'suspected_unauthorized' => LoadEvent::where('type', 'suspected_unauthorized_loading')->count(),
                'confirmed_unauthorized' => LoadEvent::where('type', 'confirmed_unauthorized_loading')->count(),
            ],
        ];
    }

    /**
     * Single trip with its load event timeline and the GPS path driven while it was open.
     */
    public function getTripDetailPayload(int $tripId): ?array
    {
        $trip = Trip::with(['vehicle', 'startStop', 'endStop', 'loadEvents'])->find($tripId);
        if (! $trip) {
            return null;
        }

        $from = $trip->startStop?->started_at ?? $trip->loaded_at;
        $until = $trip->completed_at ?? $trip->cancelled_at ?? now();

        $path = GpsPosition::where('vehicle_id', $trip->vehicle_id)
            ->where('is_valid', true)
            ->whereBetween('recorded_at', [$from, $until])
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude', 'speed_kmh', 'external_voltage', 'recorded_at'])
            ->map(fn (GpsPosition $pos) => [
                'lat' => $pos->latitude,
                'lng' => $pos->longitude,
                'speed' => $pos->speed_kmh,
                'load_mv' => $pos->external_voltage,
                'recorded_at' => $pos->recorded_at->format('M d, H:i:s'),
            ])
            ->values();

        return [
            ...$this->formatTrip($trip),
            'events' => $this->formatEvents($trip->loadEvents),
            'path' => $path,
        ];
    }

    /**
     * Recent load events and the open trip for one vehicle (live tracking overlay).
     */
    public function getVehicleLoadEventsPayload(int $vehicleId): array
    {
        $events = LoadEvent::where('vehicle_id', $vehicleId)
            ->orderBy('occurred_at', 'desc')
            ->limit(50)
            ->get();

        $openTrip = Trip::with(['vehicle', 'startStop', 'endStop', 'loadEvents'])
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->latest('loaded_at')
            ->first();

        return [
            'events' => $this->formatEvents($events),
            'open_trip' => $openTrip ? $this->formatTrip($openTrip) : null,
        ];
    }

    private function formatTrip(Trip $trip): array
    {
        $types = $trip->loadEvents->pluck('type');
        $endedAt = $trip->unloaded_at ?? $trip->cancelled_at;
        $durationMinutes = (int) $trip->loaded_at->diffInMinutes($endedAt ?? now());

        return [
            'id' => $trip->id,
            'reference' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT),
            'vehicle_id' => $trip->vehicle_id,
            'vehicle_code' => $trip->vehicle?->code ?? 'TRK-SYS',
            'vehicle_name' => $trip->vehicle?->name ?? 'Fleet Vehicle',
            'driver' => $trip->vehicle?->driver_name ?? 'Unassigned',
            'status' => $trip->status,
            'is_open' => in_array($trip->status, self::OPEN_STATUSES, true),
            'is_billable' => $trip->is_billable,
            'baseline_load' => "{$trip->baseline_load} mV",
            'distance_km' => round($trip->distance_m / 1000, 1),
            'duration' => sprintf('%dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60),
            'loaded_at' => $trip->loaded_at->format('M d, Y H:i'),
            'unloaded_at' => $trip->unloaded_at?->format('M d, Y H:i'),
            'cancel_reason' => $trip->cancel_reason,
            'start' => $trip->startStop ? ['lat' => $trip->startStop->latitude, 'lng' => $trip->startStop->longitude] : null,
            'end' => $trip->endStop ? ['lat' => $trip->endStop->latitude, 'lng' => $trip->endStop->longitude] : null,
            'unauthorized' => match (true) {
                $types->contains('confirmed_unauthorized_loading') => 'confirmed',
                $types->contains('suspected_unauthorized_loading') && ! $types->contains('unauthorized_loading_dismissed') => 'suspected',
                default => null,
            },
            'offload_located' => $types->contains('unauthorized_offload'),
        ];
    }

    private function formatEvents(Collection $events): Collection
    {
        return $events->map(function (LoadEvent $event) {
            [$label, $severity, $color, $icon] = self::EVENT_META[$event->type] ?? [$event->type, 'info', '#505f76', 'inventory_2'];

            return [
                'id' => $event->id,
                'trip_id' => $event->trip_id,
                'type' => $event->type,
                'label' => $label,
                'severity' => $severity,
                'color' => $color,
                'icon' => $icon,
                'description' => $event->description,
                'lat' => $event->latitude,
                'lng' => $event->longitude,
                'load_value' => $event->load_value !== null ? "{$event->load_value} mV" : 'N/A',
                'load_delta' => $event->load_delta !== null ? sprintf('%+d mV', $event->load_delta) : null,
                'occurred_at' => $event->occurred_at->format('M d, Y H:i'),
                'time_ago' => $event->occurred_at->diffForHumans(),
            ];
        })->values();
    }
}
