@extends('layouts.app')

@section('title', 'Log Monitoring')
@section('page-title', '📋 Log Monitoring Kepatuhan')

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
    }
    .chip.green { border-color: rgba(0,230,118,0.3); color: var(--green); background: var(--green-dim); }
    .chip.red   { border-color: rgba(255,71,87,0.3); color: var(--red);   background: var(--red-dim);   }
    .chip.blue  { border-color: rgba(0,212,255,0.3); color: var(--accent); background: var(--accent-dim); }
    .chip.orange{ border-color: rgba(255,165,2,0.3); color: var(--orange); background: var(--orange-dim); }

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
    .page-link.active { background: var(--accent-dim); color: var(--accent); border-color: rgba(0,212,255,0.3); }

    /* Lightbox */
    .lightbox {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.9); z-index: 999;
        align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 90vw; max-height: 90vh; border-radius: var(--radius); }
    .lightbox-close {
        position: absolute; top: 16px; right: 24px;
        font-size: 32px; color: #fff; cursor: pointer;
        background: none; border: none; line-height: 1;
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
            <option value="patuh"      {{ request('status') === 'patuh' ? 'selected' : '' }}>✅ Patuh</option>
            <option value="tidak_patuh" {{ request('status') === 'tidak_patuh' ? 'selected' : '' }}>❌ Tidak Patuh</option>
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
        <button type="submit" class="btn btn-primary">🔍 Filter</button>
        <a href="{{ route('monitoring.index') }}" class="btn btn-ghost">↺ Reset</a>
    </div>
</form>

<!-- ── Summary Chips ───────────────────────────────────────────────── -->
<div class="summary-chips">
    <div class="chip blue">📊 Total: {{ $stats['total'] }}</div>
    <div class="chip green">✅ Patuh: {{ $stats['patuh'] }}</div>
    <div class="chip red">❌ Tidak Patuh: {{ $stats['tidak_patuh'] }}</div>
    <div class="chip orange">📈 Kepatuhan: {{ $stats['persen'] }}%</div>
</div>

<!-- ── Tabel Log ───────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Riwayat Monitoring</span>
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
                    <th>Conf.</th>
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
                        <div class="no-snapshot" style="display:none">📷</div>
                        @else
                        <div class="no-snapshot">📷</div>
                        @endif
                    </td>
                    <td>
                        <span class="font-mono" style="color:var(--text-primary);font-weight:600">
                            #{{ $log->person_id }}
                        </span>
                    </td>
                    <td>{{ $log->camera?->nama_kamera ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $log->status === 'patuh' ? 'badge-patuh' : 'badge-tidak-patuh' }}">
                            {{ $log->status === 'patuh' ? '✅ Patuh' : '❌ Tidak Patuh' }}
                        </span>
                    </td>
                    <td>
                        <span style="color: {{ $log->membawa_instrumen ? 'var(--orange)' : 'var(--text-muted)' }}">
                            {{ $log->membawa_instrumen ? '🧰 Ya' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="color: {{ $log->aktivitas_cuci_tangan ? 'var(--green)' : 'var(--red)' }}">
                            {{ $log->aktivitas_cuci_tangan ? '🚿 Ya' : '✗ Tidak' }}
                        </span>
                    </td>
                    <td class="font-mono" style="font-size:12px;">
                        {{ $log->waktu->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="font-mono" style="font-size:12px;color:var(--text-muted)">
                        {{ $log->confidence ?? '—' }}%
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">
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
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
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
