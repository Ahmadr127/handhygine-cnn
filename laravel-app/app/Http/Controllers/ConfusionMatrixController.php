<?php

namespace App\Http\Controllers;

use App\Models\MonitoringLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfusionMatrixController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredLogsQuery($request)->with(['camera.zones']);

        $logs = $query->paginate(20)->withQueryString();
        $metrics = $this->computeMetrics($this->metricsBaseQuery($request));

        return view('confusion-matrix.index', compact('logs', 'metrics'));
    }

    public function export(Request $request): StreamedResponse
    {
        $logs = $this->filteredLogsQuery($request)
            ->with(['camera.zones'])
            ->get();

        $metrics = $this->computeMetrics($this->metricsBaseQuery($request));
        $filename = 'confusion_matrix_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs, $metrics) {
            $out = fopen('php://output', 'w');

            // BOM agar Excel Windows membaca UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");

            // sep=, memberitahu Excel (terutama locale ID) agar pakai koma sebagai delimiter
            fwrite($out, "sep=,\r\n");

            fputcsv($out, [
                'No',
                'Person ID',
                'Video / Kamera',
                'Zona',
                'Dwell Time > 2 Detik',
                'Status Hand Washed',
                'Membawa Instrumen',
                'Prediksi Sistem',
                'Ground Truth',
                'Kelas Confusion Matrix',
                'Waktu',
                'Confidence',
            ]);

            $no = 1;
            foreach ($logs as $log) {
                $row = $this->buildEvaluationRow($log, $no);
                fputcsv($out, [
                    $row['no'],
                    $row['person_id'],
                    $row['video'],
                    $row['zona'],
                    $row['dwell_gt_2s'],
                    $row['hand_washed'],
                    $row['membawa_instrumen'],
                    $row['prediksi'],
                    $row['ground_truth'],
                    $row['cm_class'],
                    $row['waktu'],
                    $row['confidence'],
                ]);
                $no++;
            }

            // Ringkasan metrik
            fputcsv($out, []);
            fputcsv($out, ['RINGKASAN CONFUSION MATRIX']);
            fputcsv($out, ['Total Data (ber-GT)', $metrics['total']]);
            fputcsv($out, ['Jumlah Patuh (GT)', $metrics['gt_patuh']]);
            fputcsv($out, ['Jumlah Tidak Patuh (GT)', $metrics['gt_tidak_patuh']]);
            fputcsv($out, ['TP (True Positive)', $metrics['tp']]);
            fputcsv($out, ['FP (False Positive)', $metrics['fp']]);
            fputcsv($out, ['TN (True Negative)', $metrics['tn']]);
            fputcsv($out, ['FN (False Negative)', $metrics['fn']]);
            fputcsv($out, ['Accuracy (%)', $metrics['accuracy']]);
            fputcsv($out, ['Precision (%)', $metrics['precision']]);
            fputcsv($out, ['Recall (%)', $metrics['recall']]);
            fputcsv($out, ['F1 Score (%)', $metrics['f1']]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

        $metrics = $this->computeMetrics($this->metricsBaseQuery($request));

        return response()->json([
            'ok'           => true,
            'id'           => $monitoringLog->id,
            'ground_truth' => $monitoringLog->ground_truth,
            'metrics'      => $metrics,
        ]);
    }

    private function filteredLogsQuery(Request $request)
    {
        $query = MonitoringLog::query()->orderBy('waktu', 'desc');

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

        return $query;
    }

    private function metricsBaseQuery(Request $request)
    {
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

    /**
     * Susun baris evaluasi untuk tabel / CSV.
     * Dwell ≥ 2 detik & hand washed diturunkan dari aktivitas_cuci_tangan
     * (sistem hanya mencatat wash setelah dwell timer ≥ 2 detik).
     */
    public static function buildEvaluationRow(MonitoringLog $log, int $no): array
    {
        $washed = (bool) $log->aktivitas_cuci_tangan;
        $zones = $log->camera?->zones
            ? $log->camera->zones
                ->filter(fn ($z) => in_array($z->tipe_zona, ['sanitizer', 'wastafel'], true))
                ->map(fn ($z) => $z->nama_zona . ' (' . $z->tipe_zona . ')')
                ->values()
                ->all()
            : [];

        // Pakai "-" ASCII (bukan "—" Unicode) agar Excel Windows tidak rusak encoding
        $empty = '-';

        $zona = count($zones) ? implode(', ', $zones) : $empty;

        $predLabel = $log->status === 'patuh' ? 'Patuh' : 'Tidak Patuh';
        $gtLabel = match ($log->ground_truth) {
            'patuh'       => 'Patuh',
            'tidak_patuh' => 'Tidak Patuh',
            default       => $empty,
        };

        return [
            'no'                 => $no,
            'person_id'          => '#' . $log->person_id,
            'video'              => $log->camera?->nama_kamera
                ?: ($log->camera?->source ?? $empty),
            'zona'               => $zona,
            'dwell_gt_2s'        => $washed ? 'Ya' : 'Tidak',
            'hand_washed'        => $washed ? 'Hand Washed' : 'Tidak',
            'membawa_instrumen'  => $log->membawa_instrumen ? 'Ya' : 'Tidak',
            'prediksi'           => $predLabel,
            'ground_truth'       => $gtLabel,
            'cm_class'           => self::classifyConfusion($log->status, $log->ground_truth),
            'waktu'              => optional($log->waktu)->format('Y-m-d H:i:s') ?? $empty,
            'confidence'         => $log->confidence !== null
                ? number_format((float) $log->confidence, 2)
                : $empty,
        ];
    }

    public static function classifyConfusion(?string $prediksi, ?string $groundTruth): string
    {
        if ($groundTruth === null || $groundTruth === '') {
            return '-';
        }

        if ($groundTruth === 'patuh' && $prediksi === 'patuh') {
            return 'TP';
        }
        if ($groundTruth === 'tidak_patuh' && $prediksi === 'patuh') {
            return 'FP';
        }
        if ($groundTruth === 'patuh' && $prediksi === 'tidak_patuh') {
            return 'FN';
        }
        if ($groundTruth === 'tidak_patuh' && $prediksi === 'tidak_patuh') {
            return 'TN';
        }

        return '-';
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
