<?php

namespace App\Services;

use App\Models\GpsPosition;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TeltonikaDumpImporterService
{
    private string $filePath;
    private int $importedCount = 0;
    private int $skippedCount = 0;

    /**
     * Set the target JSONL dump file path via method chaining.
     */
    public function fromFile(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * Execute the dump parsing & DB insertion pipeline.
     */
    public function import(): array
    {
        if (!File::exists($this->filePath)) {
            throw new \InvalidArgumentException("Dump file not found: {$this->filePath}");
        }

        $lines = File::lines($this->filePath);
        $recordsToInsert = collect();
        $vehiclesCache = collect();

        $lines->each(function (string $line) use (&$recordsToInsert, &$vehiclesCache) {
            $data = json_decode(trim($line), true);
            if (!$data || !isset($data['imei'])) {
                return;
            }

            $imei = (string)$data['imei'];
            $codec = $data['codec'] ?? 'Codec 8 Extended';

            // Ensure Vehicle exists
            if (!$vehiclesCache->has($imei)) {
                $vehicle = Vehicle::firstOrCreate(
                    ['imei' => $imei],
                    [
                        'name' => $imei === '860848081275126' ? 'Volvo FH16 - Heavy Transport' : "Teltonika Tracker ({$imei})",
                        'code' => 'TRK-' . substr($imei, -4),
                        'category' => 'Heavy Duty Truck',
                        'status' => 'Loaded',
                        'driver_name' => $imei === '860848081275126' ? 'Marcus Vance' : 'Driver Assigned',
                    ]
                );
                $vehiclesCache->put($imei, $vehicle);
            }

            $vehicle = $vehiclesCache->get($imei);

            // Process AVL Records if present
            if (isset($data['records']) && is_array($data['records'])) {
                foreach ($data['records'] as $record) {
                    $gps = $record['gps'] ?? [];
                    $ioElements = $record['io']['elements'] ?? [];
                    $timestampStr = $record['timestamp']['iso'] ?? null;

                    if (!$timestampStr || !isset($gps['latitude']) || !isset($gps['longitude'])) {
                        $this->skippedCount++;
                        continue;
                    }

                    $recordedAt = Carbon::parse($timestampStr)->format('Y-m-d H:i:s');
                    $now = now()->format('Y-m-d H:i:s');

                    $ignitionState = (bool)($ioElements['239']['value'] ?? $ioElements['1']['value'] ?? 0);
                    $movementState = (bool)($ioElements['240']['value'] ?? 0);
                    $internalBattery = (int)($ioElements['67']['value'] ?? 0);
                    $externalVoltage = (int)($ioElements['66']['value'] ?? 0);
                    $totalOdometer = (int)($ioElements['16']['value'] ?? $ioElements['199']['value'] ?? 0);
                    $hdop = (int)($ioElements['182']['value'] ?? 0);

                    $recordsToInsert->push([
                        'vehicle_id' => $vehicle->id,
                        'imei' => $imei,
                        'recorded_at' => $recordedAt,
                        'latitude' => (float)$gps['latitude'],
                        'longitude' => (float)$gps['longitude'],
                        'altitude_m' => (int)($gps['altitude_m'] ?? 0),
                        'angle_deg' => (int)($gps['angle_deg'] ?? 0),
                        'satellites' => (int)($gps['satellites'] ?? 0),
                        'speed_kmh' => (int)($gps['speed_kmh'] ?? 0),
                        'is_valid' => (bool)($gps['valid'] ?? true),
                        'ignition_state' => $ignitionState,
                        'movement_state' => $movementState,
                        'internal_battery_voltage' => $internalBattery,
                        'external_voltage' => $externalVoltage,
                        'total_odometer' => $totalOdometer,
                        'hdop' => $hdop,
                        'codec' => $codec,
                        'raw_io_json' => json_encode($ioElements),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        // Perform chunked upsert/insert using DB query builder for maximum efficiency
        if ($recordsToInsert->isNotEmpty()) {
            $recordsToInsert->chunk(100)->each(function ($chunk) {
                GpsPosition::insert($chunk->toArray());
            });

            // Update Vehicle last_ping_at timestamp
            $vehiclesCache->each(function (Vehicle $vehicle) {
                $latestAt = GpsPosition::where('vehicle_id', $vehicle->id)->max('recorded_at');
                if ($latestAt) {
                    $vehicle->update(['last_ping_at' => $latestAt]);
                }
            });
        }

        $this->importedCount = $recordsToInsert->count();

        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'vehicles' => $vehiclesCache->count(),
        ];
    }
}
