@extends('layouts.app')

@section('title', 'Confusion Matrix')
@section('page-title', 'Evaluasi Confusion Matrix')

@push('styles')
<style>
    .filter-bar {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filter-bar .form-group { margin-bottom: 0; min-width: 150px; flex: 1; }
    .filter-bar .form-label { font-size: 11px; }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    .metric-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 20px;
    }
    .metric-card .metric-label {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 6px;
    }
    .metric-card .metric-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--text-primary);
        font-variant-numeric: tabular-nums;
    }
    .metric-card .metric-unit {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted);
        margin-left: 2px;
    }

    .cm-hint {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 16px;
        padding: 10px 14px;
        background: var(--bg-card);
        border: 1px dashed var(--border);
        border-radius: var(--radius);
    }

    .cm-table-wrap { overflow-x: auto; }
    .cm-matrix {
        width: 100%;
        max-width: 560px;
        border-collapse: collapse;
    }
    .cm-matrix th, .cm-matrix td {
        border: 1px solid var(--border);
        padding: 14px 16px;
        text-align: center;
        font-size: 14px;
    }
    .cm-matrix thead th {
        background: var(--bg-primary);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .cm-matrix tbody th {
        background: var(--bg-primary);
        color: var(--text-secondary);
        font-weight: 600;
        text-align: left;
        font-size: 13px;
    }
    .cm-matrix .cell-tp { background: rgba(22,163,74,0.12); color: var(--green); font-weight: 700; font-size: 20px; }
    .cm-matrix .cell-tn { background: rgba(37,99,235,0.10); color: var(--accent); font-weight: 700; font-size: 20px; }
    .cm-matrix .cell-fp { background: rgba(217,119,6,0.12); color: var(--orange); font-weight: 700; font-size: 20px; }
    .cm-matrix .cell-fn { background: rgba(220,38,38,0.10); color: var(--red); font-weight: 700; font-size: 20px; }
    .cm-matrix .cell-sub {
        display: block;
        font-size: 11px;
        font-weight: 500;
        opacity: 0.8;
        margin-top: 2px;
    }

    .gt-select {
        min-width: 140px;
        font-size: 13px;
        padding: 6px 10px;
    }
    .save-status {
        font-size: 11px;
        color: var(--text-muted);
        margin-left: 6px;
        white-space: nowrap;
    }
    .save-status.ok { color: var(--green); }
    .save-status.err { color: var(--red); }
    .save-status.pending { color: var(--orange); }

    .pagination-wrap {
        padding: 16px 20px;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
    }

    .lightbox {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.85); z-index: 999;
        align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
    }
    .lightbox.open { display: flex; }
    .lightbox img {
        max-width: 85vw; max-height: 85vh;
        border-radius: var(--radius);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    .lightbox-close {
        position: absolute; top: 16px; right: 24px;
        font-size: 24px; color: #fff; cursor: pointer;
        background: none; border: none; line-height: 1;
        display: flex; align-items: center; justify-content: center;
    }
</style>
@endpush

@section('content')

<!-- Filter -->
<form method="GET" action="{{ route('confusion-matrix.index') }}" class="filter-bar">
    <div class="form-group">
        <label class="form-label">Prediksi Sistem</label>
        <select name="status" class="form-control">
            <option value="">Semua</option>
            <option value="patuh" {{ request('status') === 'patuh' ? 'selected' : '' }}>Patuh</option>
            <option value="tidak_patuh" {{ request('status') === 'tidak_patuh' ? 'selected' : '' }}>Tidak Patuh</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Ground Truth</label>
        <select name="ground_truth" class="form-control">
            <option value="">Semua</option>
            <option value="sudah" {{ request('ground_truth') === 'sudah' ? 'selected' : '' }}>Sudah diisi</option>
            <option value="belum" {{ request('ground_truth') === 'belum' ? 'selected' : '' }}>Belum diisi</option>
            <option value="patuh" {{ request('ground_truth') === 'patuh' ? 'selected' : '' }}>GT Patuh</option>
            <option value="tidak_patuh" {{ request('ground_truth') === 'tidak_patuh' ? 'selected' : '' }}>GT Tidak Patuh</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Tanggal Akhir</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:4px;">
            <i data-lucide="search" style="width:14px;height:14px;"></i> Filter
        </button>
        <a href="{{ route('confusion-matrix.index') }}" class="btn btn-ghost" style="display:inline-flex;align-items:center;gap:4px;">
            <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Reset
        </a>
    </div>
</form>

@if($metrics['total'] === 0)
<div class="cm-hint" id="cmHint">
    Isi Ground Truth pada tabel di bawah untuk menghitung metrik evaluasi.
</div>
@else
<div class="cm-hint" id="cmHint" style="display:none;"></div>
@endif

<!-- Stat cards -->
<div class="stats-grid" id="statsGrid">
    <div class="stat-card total">
        <div class="stat-value" data-m="total">{{ $metrics['total'] }}</div>
        <div class="stat-label">Total Data (ber-GT)</div>
    </div>
    <div class="stat-card patuh">
        <div class="stat-value" data-m="gt_patuh">{{ $metrics['gt_patuh'] }}</div>
        <div class="stat-label">Jumlah Patuh (GT)</div>
    </div>
    <div class="stat-card tidak-patuh">
        <div class="stat-value" data-m="gt_tidak_patuh">{{ $metrics['gt_tidak_patuh'] }}</div>
        <div class="stat-label">Jumlah Tidak Patuh (GT)</div>
    </div>
    <div class="stat-card patuh">
        <div class="stat-value" data-m="tp">{{ $metrics['tp'] }}</div>
        <div class="stat-label">TP (True Positive)</div>
    </div>
    <div class="stat-card persen">
        <div class="stat-value" data-m="fp">{{ $metrics['fp'] }}</div>
        <div class="stat-label">FP (False Positive)</div>
    </div>
    <div class="stat-card total">
        <div class="stat-value" data-m="tn">{{ $metrics['tn'] }}</div>
        <div class="stat-label">TN (True Negative)</div>
    </div>
    <div class="stat-card tidak-patuh">
        <div class="stat-value" data-m="fn">{{ $metrics['fn'] }}</div>
        <div class="stat-label">FN (False Negative)</div>
    </div>
</div>

<!-- Metrics % -->
<div class="metrics-grid" id="metricsGrid">
    <div class="metric-card">
        <div class="metric-label">Accuracy</div>
        <div class="metric-value"><span data-m="accuracy">{{ number_format($metrics['accuracy'], 2) }}</span><span class="metric-unit">%</span></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Precision</div>
        <div class="metric-value"><span data-m="precision">{{ number_format($metrics['precision'], 2) }}</span><span class="metric-unit">%</span></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Recall</div>
        <div class="metric-value"><span data-m="recall">{{ number_format($metrics['recall'], 2) }}</span><span class="metric-unit">%</span></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">F1 Score</div>
        <div class="metric-value"><span data-m="f1">{{ number_format($metrics['f1'], 2) }}</span><span class="metric-unit">%</span></div>
    </div>
</div>

<!-- Confusion Matrix 2x2 -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <span class="card-title" style="display:flex;align-items:center;gap:6px;">
            <i data-lucide="grid-3x3" style="width:16px;height:16px;color:var(--text-muted);"></i>
            Confusion Matrix
        </span>
    </div>
    <div class="card-body cm-table-wrap">
        <table class="cm-matrix">
            <thead>
                <tr>
                    <th>Ground Truth \ Prediksi</th>
                    <th>Prediksi Patuh</th>
                    <th>Prediksi Tidak Patuh</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Patuh</th>
                    <td class="cell-tp">
                        <span data-m="tp">{{ $metrics['tp'] }}</span>
                        <span class="cell-sub">TP</span>
                    </td>
                    <td class="cell-fn">
                        <span data-m="fn">{{ $metrics['fn'] }}</span>
                        <span class="cell-sub">FN</span>
                    </td>
                </tr>
                <tr>
                    <th>Tidak Patuh</th>
                    <td class="cell-fp">
                        <span data-m="fp">{{ $metrics['fp'] }}</span>
                        <span class="cell-sub">FP</span>
                    </td>
                    <td class="cell-tn">
                        <span data-m="tn">{{ $metrics['tn'] }}</span>
                        <span class="cell-sub">TN</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Evaluation table -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <span class="card-title" style="display:flex;align-items:center;gap:6px;">
            <i data-lucide="clipboard-check" style="width:16px;height:16px;color:var(--text-muted);"></i>
            Tabel Data Evaluasi
        </span>
        <span class="text-muted text-sm">{{ $logs->total() }} record</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Video</th>
                    <th>Person ID</th>
                    <th>Prediksi Sistem</th>
                    <th>Ground Truth</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $videoName = $log->camera?->nama_kamera
                        ?: ($log->camera?->source ?? '—');
                @endphp
                <tr data-log-id="{{ $log->id }}">
                    <td>{{ $logs->firstItem() + $loop->index }}</td>
                    <td>{{ $videoName }}</td>
                    <td>
                        <span class="font-mono" style="color:var(--text-primary);font-weight:600;">
                            #{{ $log->person_id }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $log->status === 'patuh' ? 'badge-patuh' : 'badge-tidak-patuh' }}" style="display:inline-flex;align-items:center;gap:4px;">
                            @if($log->status === 'patuh')
                                <i data-lucide="check-circle" style="width:12px;height:12px;"></i> Patuh
                            @else
                                <i data-lucide="x-circle" style="width:12px;height:12px;"></i> Tidak Patuh
                            @endif
                        </span>
                    </td>
                    <td>
                        <select
                            class="form-control gt-select"
                            data-id="{{ $log->id }}"
                            aria-label="Ground Truth #{{ $log->id }}"
                        >
                            <option value="" {{ $log->ground_truth === null ? 'selected' : '' }}>—</option>
                            <option value="patuh" {{ $log->ground_truth === 'patuh' ? 'selected' : '' }}>Patuh</option>
                            <option value="tidak_patuh" {{ $log->ground_truth === 'tidak_patuh' ? 'selected' : '' }}>Tidak Patuh</option>
                        </select>
                        <span class="save-status" data-status-for="{{ $log->id }}"></span>
                    </td>
                    <td>
                        @if($log->snapshot_path)
                        <button type="button" class="btn btn-ghost btn-sm"
                                style="display:inline-flex;align-items:center;gap:4px;"
                                onclick="openLightbox('{{ asset('storage/' . $log->snapshot_path) }}')">
                            <i data-lucide="image" style="width:14px;height:14px;"></i> Snapshot
                        </button>
                        @else
                        <span style="color:var(--text-muted);font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">
                        Tidak ada data log monitoring untuk dievaluasi
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="pagination-wrap">
        {{ $logs->links('pagination::simple-bootstrap-4') }}
    </div>
    @endif
</div>

<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i data-lucide="x" style="width:28px;height:28px;"></i>
    </button>
    <img id="lightboxImg" src="" alt="Snapshot">
</div>
@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
        || '{{ csrf_token() }}';

    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightbox').classList.add('open');
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('open');
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeLightbox();
    });

    function fmt2(n) {
        return Number(n).toFixed(2);
    }

    function applyMetrics(m) {
        document.querySelectorAll('[data-m]').forEach(el => {
            const key = el.getAttribute('data-m');
            if (!(key in m)) return;
            if (['accuracy', 'precision', 'recall', 'f1'].includes(key)) {
                el.textContent = fmt2(m[key]);
            } else {
                el.textContent = m[key];
            }
        });

        const hint = document.getElementById('cmHint');
        if (hint) {
            if (m.total === 0) {
                hint.style.display = '';
                hint.textContent = 'Isi Ground Truth pada tabel di bawah untuk menghitung metrik evaluasi.';
            } else {
                hint.style.display = 'none';
            }
        }
    }

    document.querySelectorAll('.gt-select').forEach(sel => {
        sel.addEventListener('change', async () => {
            const id = sel.dataset.id;
            const statusEl = document.querySelector(`[data-status-for="${id}"]`);
            const value = sel.value === '' ? null : sel.value;

            if (statusEl) {
                statusEl.className = 'save-status pending';
                statusEl.textContent = 'Menyimpan…';
            }

            try {
                const params = new URLSearchParams(window.location.search);
                const payload = {
                    ground_truth: value,
                    status: params.get('status') || '',
                    date_from: params.get('date_from') || '',
                    date_to: params.get('date_to') || '',
                };
                const res = await fetch(`/confusion-matrix/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || `HTTP ${res.status}`);
                }

                const data = await res.json();
                if (data.metrics) applyMetrics(data.metrics);

                if (statusEl) {
                    statusEl.className = 'save-status ok';
                    statusEl.textContent = 'Tersimpan';
                    setTimeout(() => { statusEl.textContent = ''; }, 2000);
                }
            } catch (e) {
                if (statusEl) {
                    statusEl.className = 'save-status err';
                    statusEl.textContent = 'Gagal';
                }
                console.error(e);
            }
        });
    });
</script>
@endpush
