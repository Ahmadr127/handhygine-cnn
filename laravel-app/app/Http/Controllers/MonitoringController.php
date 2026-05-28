<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $query = MonitoringLog::with('camera')->orderBy('waktu', 'desc');

        // Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('camera_id')) {
            $query->where('camera_id', $request->camera_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('waktu', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('waktu', '<=', $request->date_to);
        }

        $logs    = $query->paginate(20)->withQueryString();
        $cameras = Camera::orderBy('nama_kamera')->get();

        // Statistik untuk periode filter
        $statsQuery = MonitoringLog::query();
        if ($request->filled('camera_id')) $statsQuery->where('camera_id', $request->camera_id);
        if ($request->filled('date_from'))  $statsQuery->whereDate('waktu', '>=', $request->date_from);
        if ($request->filled('date_to'))    $statsQuery->whereDate('waktu', '<=', $request->date_to);

        $stats = [
            'total'           => $statsQuery->count(),
            'patuh'           => (clone $statsQuery)->where('status', 'patuh')->count(),
            'tidak_patuh'     => (clone $statsQuery)->where('status', 'tidak_patuh')->count(),
        ];
        $stats['persen'] = $stats['total'] > 0
            ? round(($stats['patuh'] / $stats['total']) * 100, 1) : 0;

        return view('monitoring.index', compact('logs', 'cameras', 'stats'));
    }

    public function show(MonitoringLog $monitoringLog)
    {
        $monitoringLog->load('camera');
        return view('monitoring.show', compact('monitoringLog'));
    }
}
