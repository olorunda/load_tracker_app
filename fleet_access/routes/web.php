<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/live-tracking', function () {
        return view('live-tracking');
    })->name('live-tracking');

    Route::get('/precision-system', function () {
        return view('precision-system');
    })->name('precision-system');

    Route::get('/assets/add', function () {
        return view('asset-gps-mapping');
    })->name('assets.add');

    Route::get('/geofences', function () {
        return view('geofences');
    })->name('geofences');

    Route::get('/alerts', function () {
        return view('alerts');
    })->name('alerts');

    // Telemetry & Geofence API routes
    Route::get('/api/fleet/assets', [\App\Http\Controllers\FleetTelemetryController::class, 'assets'])->name('api.fleet.assets');
    Route::post('/api/fleet/assets/register', [\App\Http\Controllers\FleetTelemetryController::class, 'registerAsset'])->name('api.fleet.register');
    Route::get('/api/fleet/assets/{id}/trail', [\App\Http\Controllers\FleetTelemetryController::class, 'trail'])->name('api.fleet.trail');
    Route::get('/api/fleet/diagnostics', [\App\Http\Controllers\FleetTelemetryController::class, 'diagnostics'])->name('api.fleet.diagnostics');
    Route::post('/api/fleet/diagnostics/run', [\App\Http\Controllers\FleetTelemetryController::class, 'runDiagnostics'])->name('api.fleet.diagnostics.run');
    Route::get('/api/fleet/dtc-faults', [\App\Http\Controllers\FleetTelemetryController::class, 'dtcFaults'])->name('api.fleet.dtc.index');
    Route::post('/api/fleet/dtc-faults/{id}/resolve', [\App\Http\Controllers\FleetTelemetryController::class, 'resolveFault'])->name('api.fleet.dtc.resolve');
    Route::get('/api/fleet/dashboard-metrics', [\App\Http\Controllers\FleetTelemetryController::class, 'dashboardMetrics'])->name('api.fleet.dashboard');

    Route::get('/api/geofences', [\App\Http\Controllers\GeofenceController::class, 'index'])->name('api.geofences.index');
    Route::post('/api/geofences', [\App\Http\Controllers\GeofenceController::class, 'store'])->name('api.geofences.store');
    Route::post('/api/geofences/{id}/toggle', [\App\Http\Controllers\GeofenceController::class, 'toggle'])->name('api.geofences.toggle');
    Route::post('/api/geofences/evaluate', [\App\Http\Controllers\GeofenceController::class, 'evaluate'])->name('api.geofences.evaluate');
    Route::get('/api/alerts', [\App\Http\Controllers\GeofenceController::class, 'alerts'])->name('api.alerts.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

