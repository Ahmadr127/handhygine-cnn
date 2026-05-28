@extends('layouts.app')

@section('title', 'Detail Grup: ' . $group->nama_grup)
@section('page-title', '🏢 Detail Grup: ' . $group->nama_grup)

@section('content')
<div style="margin-bottom:16px;">
    <a href="{{ route('groups.index') }}" class="btn btn-ghost btn-sm">← Kembali ke Daftar Grup</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <h2 style="margin-bottom:8px;">{{ $group->nama_grup }}</h2>
        @if($group->lokasi) <p style="color:var(--text-secondary);font-size:14px;margin-bottom:4px;">📍 Lokasi: {{ $group->lokasi }}</p> @endif
        @if($group->deskripsi) <p style="color:var(--text-muted);font-size:14px;">{{ $group->deskripsi }}</p> @endif
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    
    <!-- ── Kamera Terhubung ──────────────────────────────────────── -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span class="card-title">📷 Kamera dalam Grup</span>
            <span class="badge badge-monitoring">{{ $group->cameras->count() }} / 4 Kamera</span>
        </div>
        <div class="card-body">
            @if($group->cameras->isEmpty())
                <p style="color:var(--text-muted); font-size:13px; text-align:center; padding:20px;">
                    Belum ada kamera di grup ini. Tambahkan di bawah.
                </p>
            @else
                @foreach($group->cameras as $cam)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px; background:var(--bg-primary); border:1px solid var(--border); border-radius:var(--radius-sm); margin-bottom:8px;">
                    <div>
                        <div style="font-weight:600; font-size:14px;">{{ $cam->tipe_icon }} {{ $cam->nama_kamera }}</div>
                        <div style="font-size:12px; color:var(--text-muted); font-family:monospace; margin-top:4px;">{{ $cam->source }}</div>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <a href="{{ route('cameras.zones', $cam) }}" class="btn btn-ghost btn-sm">🗺 Set Zona</a>
                        <form action="{{ route('groups.remove', [$group, $cam]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Keluarkan kamera ini dari grup?')">Keluarkan</button>
                        </form>
                    </div>
                </div>
                @endforeach
            @endif

            @if($group->cameras->count() < 4)
            <div style="margin-top:20px; padding-top:16px; border-top:1px dashed var(--border);">
                <form action="{{ route('groups.assign', $group) }}" method="POST" style="display:flex; gap:8px;">
                    @csrf
                    <select name="camera_id" class="form-control" required>
                        <option value="">-- Pilih Kamera Tersedia --</option>
                        @foreach($available_cameras as $avail)
                            <option value="{{ $avail->id }}">{{ $avail->nama_kamera }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" {{ $available_cameras->isEmpty() ? 'disabled' : '' }}>
                        ➕ Tambah
                    </button>
                </form>
                @if($available_cameras->isEmpty())
                <p style="font-size:11px; color:var(--text-muted); margin-top:8px;">*Tidak ada kamera yang belum masuk grup. Buat kamera baru di menu Kamera.</p>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- ── Konfigurasi Zona Keseluruhan ─────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">🗺 Status Zona Grup</span>
        </div>
        <div class="card-body">
            @php
                $hasSanitizer = false;
                $hasPintu = false;
                $totalZones = 0;
                foreach($group->cameras as $c) {
                    foreach($c->zones as $z) {
                        $totalZones++;
                        if (in_array($z->tipe_zona, ['sanitizer', 'wastafel'])) $hasSanitizer = true;
                        if ($z->tipe_zona === 'pintu') $hasPintu = true;
                    }
                }
            @endphp

            <div style="margin-bottom:20px;">
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">Syarat monitoring: 1 Zona Cuci Tangan + 1 Zona Pintu di seluruh kamera dalam grup.</p>
                
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div style="padding:10px 14px; border-radius:var(--radius-sm); border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:{{ $hasSanitizer ? 'var(--green-dim)' : 'var(--red-dim)' }}">
                        <span style="font-size:13px; font-weight:600; color:{{ $hasSanitizer ? 'var(--green)' : 'var(--red)' }}">🟢 Zona Cuci Tangan / Sanitizer</span>
                        <span>{{ $hasSanitizer ? '✅ ADA' : '❌ BELUM ADA' }}</span>
                    </div>
                    <div style="padding:10px 14px; border-radius:var(--radius-sm); border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:{{ $hasPintu ? 'var(--green-dim)' : 'var(--red-dim)' }}">
                        <span style="font-size:13px; font-weight:600; color:{{ $hasPintu ? 'var(--green)' : 'var(--red)' }}">🔴 Zona Pintu Masuk</span>
                        <span>{{ $hasPintu ? '✅ ADA' : '❌ BELUM ADA' }}</span>
                    </div>
                </div>
            </div>

            @if($totalZones > 0)
                <h4 style="font-size:13px; margin-bottom:12px;">Daftar Zona:</h4>
                @foreach($group->cameras as $c)
                    @foreach($c->zones as $z)
                    <div style="display:flex; justify-content:space-between; font-size:12px; padding:8px 0; border-bottom:1px solid var(--border);">
                        <span>{{ $z->nama_zona }} <span style="opacity:0.5;">(di {{ $c->nama_kamera }})</span></span>
                        <span style="font-weight:600; color:{{ $z->tipe_zona === 'pintu' ? 'var(--red)' : 'var(--green)' }}">{{ strtoupper($z->tipe_zona) }}</span>
                    </div>
                    @endforeach
                @endforeach
            @else
                <p style="color:var(--text-muted); font-size:13px; text-align:center;">Belum ada zona yang diatur. Klik "Set Zona" pada salah satu kamera.</p>
            @endif
        </div>
    </div>

</div>
@endsection
