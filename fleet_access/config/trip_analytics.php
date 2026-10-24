<?php

/*
|--------------------------------------------------------------------------
| Trip & Load Analytics Thresholds
|--------------------------------------------------------------------------
|
| A trip is anchored on the load cycle: it starts at a stop where the IO 66
| load signal rises above the vehicle's loaded threshold and ends at a stop
| where it falls back. Parking without a load change never creates a trip.
|
*/

return [
    // Speed (km/h) below which the vehicle is considered stopped.
    'stop_speed_kmh' => env('TRIP_STOP_SPEED_KMH', 3),

    // Speed (km/h) above which the vehicle is considered moving; distance only accrues above this.
    'move_speed_kmh' => env('TRIP_MOVE_SPEED_KMH', 5),

    // Minimum stop duration (seconds) before a stop is evaluated for a load change.
    'min_stop_seconds' => env('TRIP_MIN_STOP_SECONDS', 60),

    // Continuous distance (metres) the vehicle must travel loaded to confirm a trip start / end.
    'trip_confirm_distance_m' => env('TRIP_CONFIRM_DISTANCE_M', 5000),

    // Continuous distance (metres) to confirm an unauthorized loading or its offload.
    'unauthorized_confirm_distance_m' => env('TRIP_UNAUTHORIZED_CONFIRM_DISTANCE_M', 3000),

    // Load must exceed the trip baseline by this percentage to be suspected as unauthorized loading.
    'unauthorized_increase_pct' => env('TRIP_UNAUTHORIZED_INCREASE_PCT', 15),

    // Number of moving IO 66 samples used for the rolling median load reading.
    'load_window_samples' => env('TRIP_LOAD_WINDOW_SAMPLES', 5),

    // Odometer jumps larger than this (metres) between two samples are ignored in favour of GPS distance.
    'max_odometer_jump_m' => env('TRIP_MAX_ODOMETER_JUMP_M', 20000),

    // Only positions older than this (seconds) are processed, so late buffered records can arrive in order.
    'processing_lag_seconds' => env('TRIP_PROCESSING_LAG_SECONDS', 300),
];
