@extends('layouts.app')

@section('title', 'Konfigurasi Zona — ' . $camera->nama_kamera)
@section('page-title', '🗺 Konfigurasi Zona: ' . $camera->nama_kamera)

@push('styles')
<style>
    .zone-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }

    .canvas-container {
        background: #000;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        position: relative;
    }

    #zoneCanvas { display: block; width: 100%; cursor: crosshair; }

    .canvas-toolbar {
        padding: 10px 16px;
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
        display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
    }

    .tipe-btn {
        padding: 6px 14px;
        border-radius: 20px;
        border: 2px solid transparent;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
        background: var(--bg-primary);
        color: var(--text-secondary);
    }
    .tipe-btn.sanitizer { color: #00e676; border-color: #00e676; }
    .tipe-btn.wastafel  { color: #ffd32a; border-color: #ffd32a; }
    .tipe-btn.pintu     { color: #ff4757; border-color: #ff4757; }
    .tipe-btn.active { opacity: 1; }
    .tipe-btn:not(.active) { opacity: 0.5; }

    .canvas-instructions {
        padding: 8px 16px;
        font-size: 11px;
        color: var(--text-muted);
        background: var(--bg-card);
        border-top: 1px solid var(--border);
        display: flex; justify-content: space-between;
    }

    /* Zone list panel */
    .zone-panel-list { display: flex; flex-direction: column; gap: 8px; }

    .zone-panel-item {
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        display: flex; align-items: center; justify-content: space-between;
        transition: background 0.15s;
    }
    .zone-panel-item:hover { background: var(--bg-card-hover); }

    .zone-panel-info .zone-name { font-size: 13px; font-weight: 600; }
    .zone-panel-info .zone-type {
        font-size: 11px;
        margin-top: 2px;
        padding: 2px 6px;
        border-radius: 10px;
        display: inline-block;
    }
    .zone-panel-info .zone-type.sanitizer { background: rgba(0,230,118,0.15); color: #00e676; }
    .zone-panel-info .zone-type.wastafel  { background: rgba(255,211,42,0.15); color: #ffd32a; }
    .zone-panel-info .zone-type.pintu     { background: rgba(255,71,87,0.15); color: #ff4757; }

    .zone-panel-info .zone-points { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .nama-input {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 8px 12px;
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        outline: none;
        margin-bottom: 8px;
    }
    .nama-input:focus { border-color: var(--accent); }
</style>
@endpush

@section('content')

<div style="margin-bottom:12px;">
    <a href="{{ route('cameras.index') }}" class="btn btn-ghost btn-sm">← Kembali ke Kamera</a>
</div>

@if(!$camera->group_id)
<div class="alert alert-danger">
    ⚠️ Kamera ini belum dimasukkan ke Grup Monitoring. Anda harus memasukkannya ke grup (melalui menu Grup Monitoring) sebelum dapat mengatur zona.
</div>
@endif

<div class="zone-layout" style="{{ !$camera->group_id ? 'opacity:0.5; pointer-events:none;' : '' }}">
    <!-- Canvas Area -->
    <div class="card" style="overflow:hidden; padding:0;">
        <div class="canvas-toolbar">
            <span style="font-size:12px;color:var(--text-muted);margin-right:4px;">Tipe Zona:</span>
            <button class="tipe-btn sanitizer active" id="btn-sanitizer"
                    onclick="setTipe('sanitizer')">🟢 Sanitizer</button>
            <button class="tipe-btn wastafel" id="btn-wastafel"
                    onclick="setTipe('wastafel')">🟡 Wastafel</button>
            <button class="tipe-btn pintu" id="btn-pintu"
                    onclick="setTipe('pintu')">🔴 Pintu</button>
            <div style="flex:1"></div>
            <button class="btn btn-ghost btn-sm" onclick="undoPoint()">↩ Undo</button>
            <button class="btn btn-ghost btn-sm" onclick="clearCanvas()">🗑 Clear</button>
        </div>

        <div class="canvas-container">
            <div id="loadingStream" style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); color:var(--text-muted); z-index: 1;">Connecting to video stream...</div>
            <img id="videoStream" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit: fill; z-index: 2; display: none;" />
            <canvas id="zoneCanvas" width="800" height="450" style="position: relative; z-index: 3; background: transparent;"></canvas>
        </div>

        <div class="canvas-instructions">
            <span>Klik untuk tambah titik polygon • Double-klik / Enter untuk selesai</span>
            <span id="pointCount">0 titik</span>
        </div>

        <!-- Save zone form -->
        <div style="padding:12px 16px;background:var(--bg-secondary);border-top:1px solid var(--border);">
            <input type="text" id="zonaName" class="nama-input"
                   placeholder="Nama zona (cth: Sanitizer Depan)">
            <button class="btn btn-primary w-full" onclick="saveZone()">💾 Simpan Zona</button>
        </div>
    </div>

    <!-- Zone List Panel -->
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">📌 Zona Tersimpan</span>
                <span class="badge badge-monitoring">{{ $zones->count() }} zona</span>
            </div>
            <div class="card-body">
                @if($zones->isEmpty())
                <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0;">
                    Belum ada zona.<br>Gambar polygon di canvas.
                </p>
                @endif

                <div class="zone-panel-list">
                    @foreach($zones as $zone)
                    <div class="zone-panel-item">
                        <div class="zone-panel-info">
                            <div class="zone-name">{{ $zone->nama_zona }}</div>
                            <div class="zone-type {{ $zone->tipe_zona }}">{{ $zone->tipe_zona }}</div>
                            <div class="zone-points">{{ count($zone->polygon_points) }} titik</div>
                        </div>
                        <button class="btn btn-danger btn-sm"
                                onclick="deleteZone({{ $zone->id }})">🗑</button>
                    </div>
                    @endforeach
                </div>

                @if($zones->isNotEmpty())
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                    <div class="zone-legend" style="display:flex;gap:12px;flex-wrap:wrap;font-size:12px;">
                        <span>🟢 Sanitizer</span>
                        <span>🟡 Wastafel</span>
                        <span>🔴 Pintu</span>
                    </div>
                    <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">
                        Minimal 1 zona pintu + 1 zona cuci tangan diperlukan agar sistem bekerja.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete zone form -->
<form id="deleteZoneForm" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
    const canvas = document.getElementById('zoneCanvas');
    const ctx    = canvas.getContext('2d');
    const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
    const CAMERA_ID = {{ $camera->id }};

    let currentTipe = 'sanitizer';
    let points = [];

    // Existing zones for display
    const existingZones = @json($zones->map(fn($z) => [
        'nama'   => $z->nama_zona,
        'tipe'   => $z->tipe_zona,
        'points' => $z->polygon_points
    ]));

    const COLORS = {
        sanitizer: '#00e676',
        wastafel:  '#ffd32a',
        pintu:     '#ff4757',
    };

    function setTipe(tipe) {
        currentTipe = tipe;
        ['sanitizer','wastafel','pintu'].forEach(t => {
            document.getElementById(`btn-${t}`).classList.toggle('active', t === tipe);
        });
    }

    function getCanvasPoint(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width  / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: Math.round((e.clientX - rect.left) * scaleX),
            y: Math.round((e.clientY - rect.top)  * scaleY),
        };
    }

    canvas.addEventListener('click', (e) => {
        if (e.detail === 2) return; // ignore dblclick
        const pt = getCanvasPoint(e);
        points.push(pt);
        document.getElementById('pointCount').textContent = `${points.length} titik`;
        draw();
    });

    canvas.addEventListener('dblclick', () => finishPolygon());
    document.addEventListener('keydown', e => { if (e.key === 'Enter') finishPolygon(); });

    function finishPolygon() {
        if (points.length >= 3) {
            draw(true); // close polygon
        }
    }

    function draw(closed = false) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw existing zones
        existingZones.forEach(zone => {
            drawZone(zone.points, zone.tipe, zone.nama, true);
        });

        // Draw current polygon
        if (points.length === 0) return;
        const color = COLORS[currentTipe];
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        if (closed) ctx.closePath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.stroke();

        if (closed) {
            ctx.fillStyle = color + '33';
            ctx.fill();
        }

        // Draw points
        points.forEach((p, i) => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        });
    }

    function drawZone(pts, tipe, nama, filled) {
        if (!pts || pts.length < 2) return;
        const color = COLORS[tipe] || '#888';
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        pts.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.closePath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.stroke();
        if (filled) {
            ctx.fillStyle = color + '33';
            ctx.fill();
        }
        // Label
        const cx = pts.reduce((s,p) => s + p.x, 0) / pts.length;
        const cy = pts.reduce((s,p) => s + p.y, 0) / pts.length;
        ctx.fillStyle = color;
        ctx.font = 'bold 12px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(nama, cx, cy);
    }

    function undoPoint() {
        points.pop();
        document.getElementById('pointCount').textContent = `${points.length} titik`;
        draw();
    }

    function clearCanvas() {
        points = [];
        document.getElementById('pointCount').textContent = '0 titik';
        draw();
    }

    async function saveZone() {
        if (points.length < 3) {
            alert('Minimal 3 titik untuk membuat zona polygon!');
            return;
        }
        const namaInput = document.getElementById('zonaName');
        const nama = namaInput.value.trim() || (currentTipe + '_zone');

        const res = await fetch(`/cameras/${CAMERA_ID}/zones`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify({
                nama_zona: nama,
                tipe_zona: currentTipe,
                polygon_points: points,
            }),
        });

        if (res.ok) {
            alert(`✅ Zona "${nama}" berhasil disimpan!`);
            location.reload();
        } else {
            alert('❌ Gagal menyimpan zona');
        }
    }

    async function deleteZone(id) {
        if (!confirm('Hapus zona ini?')) return;
        const form = document.getElementById('deleteZoneForm');
        form.action = `/cameras/zones/${id}`;
        form.submit();
    }

    // Initial draw existing zones
    draw();

    // Video Stream WebSocket
    const AI_WS = '{{ config('services.handhygiene-cnn.ws_url', 'ws://localhost:8001') }}';
    const ws = new WebSocket(`${AI_WS}/ws/stream/${CAMERA_ID}`);
    const videoStream = document.getElementById('videoStream');
    const loadingStream = document.getElementById('loadingStream');

    ws.onopen = () => {
        console.log('WebSocket connected for stream');
        // Ensure camera is started in AI service
        fetch(`${AI_WS.replace('ws://', 'http://')}/api/cameras/${CAMERA_ID}/start`, {method: 'POST'}).catch(() => {});
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.error) return;
        videoStream.src = 'data:image/jpeg;base64,' + data.frame;
        videoStream.style.display = 'block';
        loadingStream.style.display = 'none';
    };

    ws.onerror = () => {
        loadingStream.textContent = 'Gagal terhubung ke AI Service.';
        loadingStream.style.display = 'block';
        videoStream.style.display = 'none';
    };

    ws.onclose = () => {
        loadingStream.textContent = 'Stream terputus. Muat ulang halaman.';
        loadingStream.style.display = 'block';
        videoStream.style.display = 'none';
    };
</script>
@endpush
