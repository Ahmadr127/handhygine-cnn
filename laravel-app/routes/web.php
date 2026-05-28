<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard (halaman utama)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Manajemen Grup
Route::prefix('groups')->name('groups.')->group(function () {
    Route::get('/',                    [GroupController::class, 'index'])->name('index');
    Route::post('/',                   [GroupController::class, 'store'])->name('store');
    Route::get('/{group}',             [GroupController::class, 'show'])->name('show');
    Route::delete('/{group}',          [GroupController::class, 'destroy'])->name('destroy');
    Route::post('/{group}/assign',     [GroupController::class, 'assignCamera'])->name('assign');
    Route::delete('/{group}/remove/{camera}', [GroupController::class, 'removeCamera'])->name('remove');
    Route::post('/{group}/start',      [GroupController::class, 'start'])->name('start');
    Route::post('/{group}/stop',       [GroupController::class, 'stop'])->name('stop');
});

// Manajemen Kamera
Route::prefix('cameras')->name('cameras.')->group(function () {
    Route::get('/',                   [CameraController::class, 'index'])->name('index');
    Route::post('/',                  [CameraController::class, 'store'])->name('store');
    Route::delete('/{camera}',        [CameraController::class, 'destroy'])->name('destroy');
    Route::post('/{camera}/start',    [CameraController::class, 'start'])->name('start');
    Route::post('/{camera}/stop',     [CameraController::class, 'stop'])->name('stop');
    Route::get('/scan/usb',           [CameraController::class, 'scanUsb'])->name('scan-usb');

    // Manajemen Zona
    Route::get('/{camera}/zones',     [CameraController::class, 'zones'])->name('zones');
    Route::post('/{camera}/zones',    [CameraController::class, 'storeZone'])->name('zones.store');
    Route::delete('/zones/{zone}',    [CameraController::class, 'destroyZone'])->name('zones.destroy');
});

// Log Monitoring
Route::prefix('monitoring')->name('monitoring.')->group(function () {
    Route::get('/',           [MonitoringController::class, 'index'])->name('index');
    Route::get('/{monitoringLog}', [MonitoringController::class, 'show'])->name('show');
});

// API Proxy ke AI Service (untuk refresh stats di dashboard)
Route::prefix('api')->group(function () {
    Route::get('/stats/today', function () {
        $today = today();
        return response()->json([
            'total_patuh'       => \App\Models\MonitoringLog::whereDate('waktu', $today)->where('status', 'patuh')->count(),
            'total_tidak_patuh' => \App\Models\MonitoringLog::whereDate('waktu', $today)->where('status', 'tidak_patuh')->count(),
            'total'             => \App\Models\MonitoringLog::whereDate('waktu', $today)->count(),
        ]);
    });

    Route::get('/logs/recent', function (Illuminate\Http\Request $request) {
        $limit = min((int) $request->get('limit', 10), 50);
        $logs  = \App\Models\MonitoringLog::with('camera')
            ->orderBy('waktu', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($l) => [
                'id'          => $l->id,
                'person_id'   => $l->person_id,
                'status'      => $l->status,
                'nama_kamera' => $l->camera?->nama_kamera,
                'waktu'       => $l->waktu,
                'membawa_instrumen'     => $l->membawa_instrumen,
                'aktivitas_cuci_tangan' => $l->aktivitas_cuci_tangan,
                'snapshot_path'         => $l->snapshot_path,
            ]);
        return response()->json($logs);
    });
});
