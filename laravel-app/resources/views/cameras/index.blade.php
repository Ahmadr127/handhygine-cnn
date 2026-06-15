@extends('layouts.app')

@section('title', 'Manajemen Kamera')
@section('page-title', '📷 Manajemen Kamera')

@push('styles')
<style>
    .page-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; }

    .camera-table-card .card-body { padding: 0; }

    .cam-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .cam-row:last-child { border-bottom: none; }
    .cam-row:hover { background: var(--bg-card-hover); }

    .cam-icon { font-size: 22px; width: 36px; text-align: center; }

    .cam-info { flex: 1; }
    .cam-name { font-weight: 600; font-size: 14px; color: var(--text-primary); }
    .cam-source { font-size: 12px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace; margin-top: 2px; }

    .cam-badge {
        font-size: 11px; font-weight: 600; padding: 3px 8px;
        border-radius: 10px;
    }
    .cam-badge.aktif {
        background: var(--green-dim); color: var(--green);
        border: 1px solid rgba(0,230,118,0.3);
    }
    .cam-badge.nonaktif {
        background: var(--bg-primary); color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .cam-actions { display: flex; gap: 6px; }

    /* Zone modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 200;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }

    .modal-box {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        width: 700px; max-height: 90vh;
        display: flex; flex-direction: column;
        overflow: hidden;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }

    .modal-body { padding: 20px; overflow-y: auto; }
    .modal-close { background: none; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; }
    .modal-close:hover { color: var(--text-primary); }

    /* Zone canvas */
    #zoneCanvas {
        width: 100%; height: 300px;
        background: #000;
        border-radius: var(--radius-sm);
        cursor: crosshair;
        border: 1px solid var(--border);
        display: block;
    }

    .zone-legend { display: flex; gap: 16px; margin-top: 8px; flex-wrap: wrap; }
    .zone-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; }
    .zone-dot { width: 10px; height: 10px; border-radius: 50%; }

    .zone-list { margin-top: 12px; }
    .zone-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-primary); border-radius: var(--radius-sm); margin-bottom: 6px; }
    .zone-item-info { font-size: 13px; }
    .zone-item-name { font-weight: 500; }
    .zone-item-type { font-size: 11px; color: var(--text-muted); }

    .tipe-colors { display: flex; gap: 8px; margin-bottom: 12px; }
    .tipe-btn { flex: 1; padding: 8px; border-radius: var(--radius-sm); border: 2px solid transparent; cursor: pointer; font-size: 12px; font-weight: 600; font-family: 'Inter', sans-serif; transition: all 0.2s; }
    .tipe-btn.selected { transform: scale(1.02); }
    .tipe-btn.sanitizer { background: rgba(0,230,118,0.15); color: #00e676; border-color: #00e676; }
    .tipe-btn.wastafel  { background: rgba(255,235,59,0.15); color: #ffd32a; border-color: #ffd32a; }
    .tipe-btn.pintu     { background: rgba(255,71,87,0.15); color: #ff4757; border-color: #ff4757; }

    .usb-scan-result { margin-top: 8px; }
    .usb-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-primary); border-radius: var(--radius-sm); margin-bottom: 4px; font-size: 13px; }
</style>
@endpush

@section('content')
<div class="page-grid">
    <!-- ── Daftar Kamera ───────────────────────────────────────── -->
    <div class="card camera-table-card">
        <div class="card-header">
            <span class="card-title">📷 Daftar Kamera</span>
            <span class="text-muted text-sm">{{ $cameras->count() }} kamera terdaftar</span>
        </div>

        @if($cameras->isEmpty())
        <div style="padding:40px; text-align:center; color:var(--text-muted);">
            <div style="font-size:48px;margin-bottom:12px;">📷</div>
            <div>Belum ada kamera. Tambahkan kamera di sebelah kanan.</div>
        </div>
        @endif

        @foreach($cameras as $cam)
        <div class="cam-row" id="camrow-{{ $cam->id }}">
            <div class="cam-icon">
                @if($cam->tipe === 'usb') 🎥
                @elseif($cam->tipe === 'rtsp') 📡
                @else 📁 @endif
            </div>
            <div class="cam-info">
                <div class="cam-name">{{ $cam->nama_kamera }}</div>
                <div class="cam-source">{{ $cam->source }}</div>
            </div>
            <span class="cam-badge {{ $cam->aktif ? 'aktif' : 'nonaktif' }}" id="badge-{{ $cam->id }}">
                {{ $cam->aktif ? '● AKTIF' : '○ OFF' }}
            </span>
            <div class="cam-actions">
                <!-- Hapus -->
                <button class="btn btn-danger btn-sm"
                        onclick="deleteCamera({{ $cam->id }}, '{{ $cam->nama_kamera }}')">
                    🗑
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- ── Form Tambah Kamera ──────────────────────────────────── -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <span class="card-title">➕ Tambah Kamera</span>
            </div>
            <div class="card-body">
                <form action="{{ route('cameras.store') }}" method="POST" id="addCameraForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nama Kamera</label>
                        <input type="text" name="nama_kamera" class="form-control"
                               placeholder="cth: Kamera Depan IGD" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Kamera</label>
                        <select name="tipe" class="form-control" id="tipeSelect"
                                onchange="onTipeChange()">
                            <option value="usb">🎥 USB / Webcam</option>
                            <option value="rtsp">📡 RTSP / IP Camera</option>
                            <option value="file">📁 File Video</option>
                        </select>
                    </div>

                    <!-- Source input (dinamis) -->
                    <div class="form-group" id="sourceGroup">
                        <label class="form-label">Source</label>
                        <div style="display:flex;gap:8px;">
                            <input type="text" name="source" class="form-control" id="sourceInput"
                                   placeholder="0 (index USB)" required>
                            <button type="button" class="btn btn-ghost btn-sm" id="scanBtn"
                                    onclick="scanUsb()" title="Scan USB">🔍</button>
                        </div>
                        <div id="usbScanResult" class="usb-scan-result" style="display:none;"></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;" id="sourceHint">
                            Masukkan index kamera (0, 1, 2...)
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">➕ Tambah Kamera</button>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="card">
            <div class="card-header"><span class="card-title">ℹ️ Panduan Source</span></div>
            <div class="card-body" style="font-size:13px;line-height:1.8;color:var(--text-secondary);">
                <strong style="color:var(--accent)">USB / Webcam:</strong><br>
                <code style="background:var(--bg-primary);padding:2px 6px;border-radius:4px;font-size:12px;">0</code> — Webcam bawaan<br>
                <code style="background:var(--bg-primary);padding:2px 6px;border-radius:4px;font-size:12px;">1</code> — USB Camera kedua<br><br>
                <strong style="color:var(--accent)">RTSP:</strong><br>
                <code style="background:var(--bg-primary);padding:2px 6px;border-radius:4px;font-size:12px;word-break:break-all;">rtsp://user:pass@ip:554/stream</code><br><br>
                <strong style="color:var(--accent)">File Video:</strong><br>
                <code style="background:var(--bg-primary);padding:2px 6px;border-radius:4px;font-size:12px;">C:/rekaman/video.mp4</code>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirm form (hidden) -->
<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function onTipeChange() {
        const tipe = document.getElementById('tipeSelect').value;
        const input = document.getElementById('sourceInput');
        const hint  = document.getElementById('sourceHint');
        const scan  = document.getElementById('scanBtn');
        const usbRes = document.getElementById('usbScanResult');

        usbRes.style.display = 'none';

        if (tipe === 'usb') {
            input.placeholder = '0 (index kamera)';
            hint.textContent  = 'Masukkan index kamera (0, 1, 2...)';
            scan.style.display = 'inline-flex';
        } else if (tipe === 'rtsp') {
            input.placeholder = 'rtsp://user:pass@192.168.1.100:554/stream';
            hint.textContent  = 'URL RTSP lengkap termasuk username & password';
            scan.style.display = 'none';
        } else {
            input.placeholder = 'C:/path/to/video.mp4';
            hint.textContent  = 'Path absolut file video';
            scan.style.display = 'none';
        }
    }

    async function scanUsb() {
        const btn = document.getElementById('scanBtn');
        const res = document.getElementById('usbScanResult');
        btn.textContent = '⏳';
        btn.disabled = true;

        try {
            const data = await fetch('/cameras/scan/usb').then(r => r.json());
            res.style.display = 'block';
            if (data.error) {
                res.innerHTML = `<div style="color:var(--red);font-size:12px;">❌ ${data.error}</div>`;
            } else if (!data.length) {
                res.innerHTML = `<div style="color:var(--text-muted);font-size:12px;">Tidak ada USB camera terdeteksi</div>`;
            } else {
                res.innerHTML = data.map(c => `
                    <div class="usb-item">
                        <span>🎥 ${c.label}</span>
                        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sourceInput').value='${c.source}'">
                            Pilih
                        </button>
                    </div>
                `).join('');
            }
        } catch {
            res.innerHTML = `<div style="color:var(--red);font-size:12px;">❌ AI Service tidak tersedia</div>`;
            res.style.display = 'block';
        }

        btn.textContent = '🔍';
        btn.disabled = false;
    }

    function deleteCamera(id, name) {
        if (!confirm(`Hapus kamera "${name}"?`)) return;
        const form = document.getElementById('deleteForm');
        form.action = `/cameras/${id}`;
        form.submit();
    }
</script>
@endpush
