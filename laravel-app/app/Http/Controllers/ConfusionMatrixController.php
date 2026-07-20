<?php

namespace App\Http\Controllers;

use App\Models\MonitoringLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfusionMatrixController extends Controller
{
    public function index(Request $request): View
    {
        $query = MonitoringLog::with('camera')->orderBy('waktu', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('ground_truth')) {
            if ($request->ground_truth === 'sudah') {
                $query->whereNotNull('ground_truth');
            } elseif ($request->ground_truth === 'belum') {
                $query->whereNull('ground_truth');
            } elseif (in_array($request->ground_truth, ['patuh', 'tidak_patuh'], true)) {
                $query->where('ground_truth', $request->ground_truth);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('waktu', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('waktu', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();
        $metrics = $this->computeMetrics($this->metricsBaseQuery($request));

        return view('confusion-matrix.index', compact('logs', 'metrics'));
    }

    public function updateGroundTruth(Request $request, MonitoringLog $monitoringLog): JsonResponse
    {
        $validated = $request->validate([
            'ground_truth' => 'nullable|in:patuh,tidak_patuh',
        ]);

        $value = $validated['ground_truth'] ?? null;
        if ($value === '' || $value === null) {
            $monitoringLog->ground_truth = null;
        } else {
            $monitoringLog->ground_truth = $value;
        }
        $monitoringLog->save();

        // Metrik mengikuti filter yang dikirim client (sama seperti index)
        $metrics = $this->computeMetrics($this->metricsBaseQuery($request));

        return response()->json([
            'ok'      => true,
            'id'      => $monitoringLog->id,
            'ground_truth' => $monitoringLog->ground_truth,
            'metrics' => $metrics,
        ]);
    }

    private function metricsBaseQuery(Request $request)
    {
        // Agregat confusion matrix: semua baris yang sudah punya GT.
        // Filter tanggal/status prediksi tetap diterapkan agar evaluasi bisa
        // dibatasi ke periode yang sedang diteliti.
        $q = MonitoringLog::query()->whereNotNull('ground_truth');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $q->whereDate('waktu', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('waktu', '<=', $request->date_to);
        }

        return $q;
    }

    private function computeMetrics($query): array
    {
        $rows = (clone $query)->get(['status', 'ground_truth']);

        $tp = 0;
        $fp = 0;
        $tn = 0;
        $fn = 0;
        $gtPatuh = 0;
        $gtTidak = 0;

        foreach ($rows as $row) {
            $pred = $row->status;
            $gt   = $row->ground_truth;

            if ($gt === 'patuh') {
                $gtPatuh++;
            } else {
                $gtTidak++;
            }

            if ($gt === 'patuh' && $pred === 'patuh') {
                $tp++;
            } elseif ($gt === 'tidak_patuh' && $pred === 'patuh') {
                $fp++;
            } elseif ($gt === 'patuh' && $pred === 'tidak_patuh') {
                $fn++;
            } elseif ($gt === 'tidak_patuh' && $pred === 'tidak_patuh') {
                $tn++;
            }
        }

        $total = $tp + $fp + $tn + $fn;

        $accuracy  = $total > 0 ? ($tp + $tn) / $total * 100 : 0.0;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) * 100 : 0.0;
        $recall    = ($tp + $fn) > 0 ? $tp / ($tp + $fn) * 100 : 0.0;
        $f1        = ($precision + $recall) > 0
            ? 2 * $precision * $recall / ($precision + $recall)
            : 0.0;

        return [
            'total'          => $total,
            'gt_patuh'       => $gtPatuh,
            'gt_tidak_patuh' => $gtTidak,
            'tp' => $tp,
            'fp' => $fp,
            'tn' => $tn,
            'fn' => $fn,
            'accuracy'  => round($accuracy, 2),
            'precision' => round($precision, 2),
            'recall'    => round($recall, 2),
            'f1'        => round($f1, 2),
        ];
    }
}
