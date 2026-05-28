@extends('layouts.app')

@section('title', 'Grup Monitoring')
@section('page-title', '🏢 Manajemen Grup Monitoring')

@push('styles')
<style>
    .page-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; }
    .group-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .group-row:last-child { border-bottom: none; }
    .group-row:hover { background: var(--bg-card-hover); }

    .group-icon { font-size: 28px; width: 40px; text-align: center; opacity: 0.8; }
    .group-info { flex: 1; }
    .group-name { font-weight: 600; font-size: 15px; color: var(--text-primary); }
    .group-desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .group-loc { font-size: 11px; color: var(--accent); margin-top: 4px; display:inline-block; background: var(--accent-dim); padding: 2px 6px; border-radius: 4px;}

    .group-badge {
        font-size: 11px; font-weight: 600; padding: 3px 8px;
        border-radius: 10px;
    }
    .group-badge.aktif {
        background: var(--green-dim); color: var(--green);
        border: 1px solid rgba(0,230,118,0.3);
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
        <div class="card-header">
            <span class="card-title">🏢 Daftar Grup Monitoring</span>
            <span class="text-muted text-sm">{{ $groups->count() }} grup terdaftar</span>
        </div>

        @if($groups->isEmpty())
        <div style="padding:40px; text-align:center; color:var(--text-muted);">
            <div style="font-size:48px;margin-bottom:12px;">🏢</div>
            <div>Belum ada grup monitoring. Tambahkan grup di sebelah kanan.</div>
        </div>
        @endif

        @foreach($groups as $group)
        <div class="group-row">
            <div class="group-icon">🏢</div>
            <div class="group-info">
                <div class="group-name">{{ $group->nama_grup }}</div>
                @if($group->lokasi) <div class="group-loc">📍 {{ $group->lokasi }}</div> @endif
                @if($group->deskripsi) <div class="group-desc">{{ $group->deskripsi }}</div> @endif
                <div class="text-sm mt-2" style="margin-top:8px;">
                    <span style="color:var(--text-secondary)">{{ $group->cameras_count }} Kamera terhubung</span>
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                <span class="group-badge {{ $group->aktif ? 'aktif' : 'nonaktif' }}" id="badge-{{ $group->id }}">
                    {{ $group->aktif ? '● GRUP AKTIF' : '○ GRUP OFF' }}
                </span>
                
                <div style="display:flex; gap:6px;">
                    <button class="btn btn-sm {{ $group->aktif ? 'btn-danger' : 'btn-success' }}"
                            id="toggleBtn-{{ $group->id }}"
                            onclick="toggleGroup({{ $group->id }}, {{ $group->aktif ? 'false' : 'true' }})">
                        {{ $group->aktif ? '⏹ Stop' : '▶ Start' }}
                    </button>
                    <a href="{{ route('groups.show', $group) }}" class="btn btn-primary btn-sm">Kelola</a>
                    <button class="btn btn-danger btn-sm" onclick="deleteGroup({{ $group->id }}, '{{ $group->nama_grup }}')">🗑</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- ── Form Tambah Grup ──────────────────────────────────── -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <span class="card-title">➕ Tambah Grup Baru</span>
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
                    <button type="submit" class="btn btn-primary w-full">➕ Tambah Grup</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">ℹ️ Tentang Grup</span></div>
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
    async function toggleGroup(id, start) {
        const btn = document.getElementById(`toggleBtn-${id}`);
        const badge = document.getElementById(`badge-${id}`);
        btn.disabled = true;
        btn.textContent = '⏳';

        try {
            const endpoint = start ? 'start' : 'stop';
            const res = await fetch(`/groups/${id}/${endpoint}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();

            if (data.success) {
                if (start) {
                    btn.textContent = '⏹ Stop';
                    btn.className = 'btn btn-sm btn-danger';
                    btn.onclick = () => toggleGroup(id, false);
                    badge.textContent = '● GRUP AKTIF';
                    badge.className = 'group-badge aktif';
                } else {
                    btn.textContent = '▶ Start';
                    btn.className = 'btn btn-sm btn-success';
                    btn.onclick = () => toggleGroup(id, true);
                    badge.textContent = '○ GRUP OFF';
                    badge.className = 'group-badge nonaktif';
                }
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch {
            alert('Gagal: AI Service tidak tersedia');
        }

        btn.disabled = false;
    }

    function deleteGroup(id, name) {
        if (!confirm(`Hapus grup "${name}" dan semua zonanya? (Kamera tidak akan terhapus, hanya keluar dari grup)`)) return;
        const form = document.getElementById('deleteForm');
        form.action = `/groups/${id}`;
        form.submit();
    }
</script>
@endpush
