@extends('layouts.app')

@section('title', 'Grup Monitoring')
@section('page-title', 'Manajemen Grup Monitoring')

@push('styles')
<style>
    .page-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; }
    .group-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .group-row:last-child { border-bottom: none; }
    .group-row:hover { background: var(--bg-card-hover); }

    .group-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        background: var(--accent-dim);
        color: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .group-info { flex: 1; }
    .group-name { font-weight: 600; font-size: 15px; color: var(--text-primary); }
    .group-desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .group-loc {
        font-size: 11px;
        color: var(--accent);
        margin-top: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--accent-dim);
        padding: 2px 6px;
        border-radius: 4px;
    }

    .group-badge {
        font-size: 11px; font-weight: 600; padding: 3px 8px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .group-badge.aktif {
        background: var(--green-dim); color: var(--green);
        border: 1px solid rgba(22,163,74,0.3);
    }
    .group-badge.nonaktif {
        background: var(--bg-primary); color: var(--text-muted);
        border: 1px solid var(--border);
    }
</style>
@endpush

@section('content')
<div class="page-grid">
    <!-- ── Daftar Grup ───────────────────────────────────────── -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <span class="card-title" style="display:flex;align-items:center;gap:6px;">
                <i data-lucide="layers" style="width: 16px; height: 16px; color: var(--text-muted);"></i> Daftar Grup Monitoring
            </span>
            <span class="text-muted text-sm">{{ $groups->count() }} grup terdaftar</span>
        </div>

        @if($groups->isEmpty())
        <div style="padding:40px; text-align:center; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px;">
            <i data-lucide="layers" style="width: 48px; height: 48px; opacity: 0.3;"></i>
            <div>Belum ada grup monitoring. Tambahkan grup di sebelah kanan.</div>
        </div>
        @endif

        @foreach($groups as $group)
        <div class="group-row">
            <div class="group-icon">
                <i data-lucide="network" style="width: 20px; height: 20px;"></i>
            </div>
            <div class="group-info">
                <div class="group-name">{{ $group->nama_grup }}</div>
                @if($group->lokasi) 
                    <div class="group-loc">
                        <i data-lucide="map-pin" style="width: 11px; height: 11px;"></i> {{ $group->lokasi }}
                    </div> 
                @endif
                @if($group->deskripsi) <div class="group-desc">{{ $group->deskripsi }}</div> @endif
                <div class="text-sm" style="margin-top:8px;">
                    <span style="color:var(--text-secondary)">{{ $group->cameras_count }} Kamera terhubung</span>
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                <span class="group-badge {{ $group->aktif ? 'aktif' : 'nonaktif' }}" id="badge-{{ $group->id }}">
                    <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:currentColor;"></span>
                    {{ $group->aktif ? 'GRUP AKTIF' : 'GRUP OFF' }}
                </span>
                
                <div style="display:flex; gap:6px;">
                    <a href="{{ route('groups.show', $group) }}" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
                        <i data-lucide="settings" style="width: 12px; height: 12px;"></i> Kelola
                    </a>
                    <button class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;justify-content:center;padding:5px;" onclick="deleteGroup({{ $group->id }}, '{{ $group->nama_grup }}')">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- ── Form Tambah Grup ──────────────────────────────────── -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <span class="card-title" style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="plus" style="width: 16px; height: 16px; color: var(--text-muted);"></i> Tambah Grup Baru
                </span>
            </div>
            <div class="card-body">
                <form action="{{ route('groups.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nama Grup</label>
                        <input type="text" name="nama_grup" class="form-control"
                               placeholder="cth: Lorong IGD Lantai 1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control"
                               placeholder="cth: Gedung A">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Informasi tambahan mengenai grup ini..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-full" style="display:inline-flex;align-items:center;justify-content:center;gap:4px;">
                        <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Tambah Grup
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:6px;">
                <span class="card-title" style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="info" style="width: 16px; height: 16px; color: var(--text-muted);"></i> Tentang Grup
                </span>
            </div>
            <div class="card-body" style="font-size:13px;line-height:1.8;color:var(--text-secondary);">
                Setiap <strong>Grup Monitoring</strong> mewakili satu area (misal: satu lorong) yang mungkin dipantau oleh lebih dari satu kamera CCTV.<br><br>
                Sistem akan menggabungkan deteksi dari semua kamera di dalam grup yang sama untuk menyimpulkan kepatuhan.
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteGroup(id, name) {
        if (!confirm(`Hapus grup "${name}" dan semua zonanya? (Kamera tidak akan terhapus, hanya keluar dari grup)`)) return;
        const form = document.getElementById('deleteForm');
        form.action = `/groups/${id}`;
        form.submit();
    }
</script>
@endpush
