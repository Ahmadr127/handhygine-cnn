@extends('layouts.app')

@section('title', 'Log Monitoring')
@section('page-title', 'Log Monitoring Kepatuhan')

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

    .filter-bar .form-group { margin-bottom: 0; min-width: 160px; flex: 1; }
    .filter-bar .form-label { font-size: 11px; }

    .summary-chips {
        display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;
    }

    .chip {
        padding: 8px 16px;
        border-radius: var(--radius);
        font-size: 13px;
        font-weight: 600;
        border: 1px solid var(--border);
        background: var(--bg-card);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .chip.green { border-color: rgba(22,163,74,0.3); color: var(--green); background: var(--green-dim); }
    .chip.red   { border-color: rgba(220,38,38,0.3); color: var(--red);   background: var(--red-dim);   }
    .chip.blue  { border-color: rgba(37,99,235,0.3); color: var(--accent); background: var(--accent-dim); }
    .chip.orange{ border-color: rgba(217,119,6,0.3); color: var(--orange); background: var(--orange-dim); }

    .snapshot-thumb {
        width: 48px; height: 36px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.2s;
        border: 1px solid var(--border);
    }
    .snapshot-thumb:hover { transform: scale(1.5); }

    .no-snapshot {
        width: 48px; height: 36px;
        background: var(--bg-primary);
        border-radius: 4px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; color: var(--text-muted);
        border: 1px solid var(--border);
    }

    /* Pagination */
    .pagination-wrap { padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }
    .pagination-wrap nav { display: flex; gap: 6px; }
    .page-link {
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        background: var(--bg-card);
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    .page-link:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .page-link.active { background: var(--accent-dim); color: var(--accent); border-color: rgba(37,99,235,0.3); }

    /* Lightbox */
    .lightbox {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.85); z-index: 999;
        align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 85vw; max-height: 85vh; border-radius: var(--radius); box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
    .lightbox-close {
        position: absolute; top: 16px; right: 24px;
        font-size: 24px; color: #fff; cursor: pointer;
        background: none; border: none; line-height: 1;
        display: flex; align-items: center; justify-content: center;
    }
</style>
@endpush

@section('content')

<!-- ── Filter Bar ──────────────────────────────────────────────────── -->
<form method="GET" action="{{ route('monitoring.index') }}" class="filter-bar">
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="patuh"      {{ request('status') === 'patuh' ? 'selected' : '' }}>Patuh</option>
            <option value="tidak_patuh" {{ request('status') === 'tidak_patuh' ? 'selected' : '' }}>Tidak Patuh</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Kamera</label>
        <select name="camera_id" class="form-control">
            <option value="">Semua Kamera</option>
            @foreach($cameras as $cam)
            <option value="{{ $cam->id }}" {{ request('camera_id') == $cam->id ? 'selected' : '' }}>
                {{ $cam->nama_kamera }}
            </option>
            @endforeach
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
            <i data-lucide="search" style="width: 14px; height: 14px;"></i> Filter
        </button>
        <a href="{{ route('monitoring.index') }}" class="btn btn-ghost" style="display:inline-flex;align-items:center;gap:4px;">
            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Reset
        </a>
    </div>
</form>

<!-- ── Summary Chips ───────────────────────────────────────────────── -->
<div class="summary-chips">
    <div class="chip blue">
        <i data-lucide="bar-chart-2" style="width: 15px; height: 15px;"></i>
        Total: {{ $stats['total'] }}
    </div>
    <div class="chip green">
        <i data-lucide="check-circle" style="width: 15px; height: 15px;"></i>
        Patuh: {{ $stats['patuh'] }}
    </div>
    <div class="chip red">
        <i data-lucide="x-circle" style="width: 15px; height: 15px;"></i>
        Tidak Patuh: {{ $stats['tidak_patuh'] }}
    </div>
    <div class="chip orange">
        <i data-lucide="trending-up" style="width: 15px; height: 15px;"></i>
        Kepatuhan: {{ $stats['persen'] }}%
    </div>
    <div class="chip red">
        <i data-lucide="trending-down" style="width: 15px; height: 15px;"></i>
        Ketidakpatuhan: {{ $stats['persen_tidak_patuh'] }}%
    </div>
</div>

<!-- ── Tabel Log ───────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <span class="card-title" style="display:flex;align-items:center;gap:6px;">
            <i data-lucide="clipboard-list" style="width: 16px; height: 16px; color: var(--text-muted);"></i> Riwayat Monitoring
        </span>
        <span class="text-muted text-sm">{{ $logs->total() }} total record</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Snapshot</th>
                    <th>Person ID</th>
                    <th>Kamera</th>
                    <th>Status</th>
                    <th>Instrumen</th>
                    <th>Cuci Tangan</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>
                        @if($log->snapshot_path)
                        <img src="{{ asset('storage/' . $log->snapshot_path) }}"
                             class="snapshot-thumb"
                             onclick="openLightbox('{{ asset('storage/' . $log->snapshot_path) }}')"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                             alt="Snapshot">
                        <div class="no-snapshot" style="display:none">
                            <i data-lucide="image" style="width:16px;height:16px;"></i>
                        </div>
                        @else
                        <div class="no-snapshot">
                            <i data-lucide="image" style="width:16px;height:16px;"></i>
                        </div>
                        @endif
                    </td>
                    <td>
                        <span class="font-mono" style="color:var(--text-primary);font-weight:600">
                            #{{ $log->person_id }}
                        </span>
                    </td>
                    <td>{{ $log->camera?->nama_kamera ?? '—' }}</td>
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
                        @if($log->membawa_instrumen)
                            <span style="color: var(--orange); display:inline-flex; align-items:center; gap:4px; font-weight: 500;">
                                <i data-lucide="package" style="width: 14px; height: 14px;"></i> Ya
                            </span>
                        @else
                            <span style="color: var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->aktivitas_cuci_tangan)
                            <span style="color: var(--green); display:inline-flex; align-items:center; gap:4px; font-weight: 500;">
                                <i data-lucide="droplet" style="width: 14px; height: 14px;"></i> Ya
                            </span>
                        @else
                            <span style="color: var(--red); display:inline-flex; align-items:center; gap:4px; font-weight: 500;">
                                <i data-lucide="x" style="width: 14px; height: 14px;"></i> Tidak
                            </span>
                        @endif
                    </td>
                    <td class="font-mono" style="font-size:12px;">
                        {{ $log->waktu->format('d/m/Y H:i:s') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">
                        Tidak ada data untuk filter yang dipilih
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($logs->hasPages())
    <div class="pagination-wrap">
        {{ $logs->links('pagination::simple-bootstrap-4') }}
    </div>
    @endif
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i data-lucide="x" style="width:28px;height:28px;"></i>
    </button>
    <img id="lightboxImg" src="" alt="Snapshot">
</div>
@endsection

@push('scripts')
<script>
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
</script>
@endpush
