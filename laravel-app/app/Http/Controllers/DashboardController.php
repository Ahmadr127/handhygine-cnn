<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\MonitoringGroup;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $groups = MonitoringGroup::with('cameras')->orderBy('id')->get();
        $selected_group = null;
        if ($request->has('group_id')) {
            $selected_group = MonitoringGroup::with('cameras')->find($request->group_id);
        } elseif ($groups->isNotEmpty()) {
            $selected_group = $groups->first();
        }

        // Statistik hari ini
        $today = today();
        $stats = [
            'total_patuh'        => MonitoringLog::whereDate('waktu', $today)->where('status', 'patuh')->count(),
            'total_tidak_patuh'  => MonitoringLog::whereDate('waktu', $today)->where('status', 'tidak_patuh')->count(),
            'total'              => MonitoringLog::whereDate('waktu', $today)->count(),
        ];
        $stats['persen_patuh'] = $stats['total'] > 0
            ? round(($stats['total_patuh'] / $stats['total']) * 100, 1)
            : 0;

        // Log terbaru (10 terakhir)
        $recent_logs = MonitoringLog::with(['camera', 'group'])
            ->orderBy('waktu', 'desc')
            ->limit(10)
            ->get();

        $ai_service_url = config('services.handhygiene-cnn.ws_url', 'ws://localhost:8001');

        return view('dashboard', compact(
            'groups', 'selected_group', 'stats', 'recent_logs', 'ai_service_url'
        ));
    }
}
