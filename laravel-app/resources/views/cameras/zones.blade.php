@extends('layouts.app')

@section('title', 'Konfigurasi Zona — ' . $camera->nama_kamera)
@section('page-title', 'Konfigurasi Zona: ' . $camera->nama_kamera)

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
    <a href="{{ route('cameras.index') }}" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Kamera
    </a>
</div>

@if(!$camera->group_id)
<div class="alert alert-danger" style="display:flex;align-items:center;gap:8px;">
    <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i>
    Kamera ini belum dimasukkan ke Grup Monitoring. Anda harus memasukkannya ke grup (melalui menu Grup Monitoring) sebelum dapat mengatur zona.
</div>
@endif

<div class="zone-layout" style="{{ !$camera->group_id ? 'opacity:0.5; pointer-events:none;' : '' }}">
    <!-- Canvas Area -->
    <div class="card" style="overflow:hidden; padding:0;">
        <div class="canvas-toolbar">
            <span style="font-size:12px;color:var(--text-muted);margin-right:4px;">Tipe Zona:</span>
            <button class="tipe-btn sanitizer active" id="btn-sanitizer"
                    onclick="setTipe('sanitizer')">Sanitizer</button>
            <button class="tipe-btn wastafel" id="btn-wastafel"
                    onclick="setTipe('wastafel')">Wastafel</button>
            <div style="flex:1"></div>
            <button class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:4px;" onclick="undoPoint()">
                <i data-lucide="undo-2" style="width:12px;height:12px;"></i> Undo
            </button>
            <button class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:4px;" onclick="clearCanvas()">
                <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Clear
            </button>
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
            <button class="btn btn-primary w-full" style="display:inline-flex;align-items:center;justify-content:center;gap:4px;" onclick="saveZone()">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Simpan Zona
            </button>
        </div>
    </div>

    <!-- Zone List Panel -->
    <div>
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <span class="card-title" style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="map-pin" style="width: 16px; height: 16px; color: var(--text-muted);"></i> Zona Tersimpan
                </span>
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
                        <button class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;justify-content:center;padding:5px;"
                                onclick="deleteZone({{ $zone->id }})">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>
                    @endforeach
                </div>

                @if($zones->isNotEmpty())
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                    <div class="zone-legend" style="display:flex;gap:12px;flex-wrap:wrap;font-size:12px;align-items:center;">
                        <span style="display:flex;align-items:center;gap:4px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#00e676;"></span> Sanitizer</span>
                        <span style="display:flex;align-items:center;gap:4px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ffd32a;"></span> Wastafel</span>
                    </div>
                    <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">
                        Minimal 1 zona cuci tangan (sanitizer/wastafel) diperlukan agar sistem bekerja.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>


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
    const existingZones = {!! json_encode($zones->map(fn($z) => [
        'nama'   => $z->nama_zona,
        'tipe'   => $z->tipe_zona,
        'points' => $z->polygon_points,
    ])) !!};

    function setTipe(tipe) {
        currentTipe = tipe;
        document.getElementById('btn-sanitizer').className = 'tipe-btn sanitizer' + (tipe === 'sanitizer' ? ' active' : '');
        document.getElementById('btn-wastafel').className = 'tipe-btn wastafel' + (tipe === 'wastafel' ? ' active' : '');
    }

    // Stream rendering via WebSocket preview
    const img = document.getElementById('videoStream');
    const loading = document.getElementById('loadingStream');
    const wsUrl = "{{ config('services.handhygiene-cnn.url', 'http://localhost:8001') }}".replace('http://', 'ws://').replace('https://', 'wss://') + `/ws/preview/${CAMERA_ID}`;

    const ws = new WebSocket(wsUrl);

    ws.onopen = () => {
        loading.style.display = 'none';
        img.style.display = 'block';
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.frame) {
            img.src = 'data:image/jpeg;base64,' + data.frame;
        } else if (data.error) {
            loading.textContent = data.error;
            loading.style.display = 'block';
            img.style.display = 'none';
        }
    };

    ws.onerror = () => {
        loading.textContent = 'Failed to connect to AI Service stream';
        loading.style.display = 'block';
        img.style.display = 'none';
    };

    window.addEventListener('beforeunload', () => {
        ws.close();
    });

    // Draw handler
    function draw() {
        ctx.clearRect(0,0, canvas.width, canvas.height);

        // Draw existing zones
        existingZones.forEach(z => {
            if (!z.points || !z.points.length) return;
            ctx.beginPath();
            ctx.moveTo(z.points[0][0], z.points[0][1]);
            for(let i=1; i<z.points.length; i++) {
                ctx.lineTo(z.points[i][0], z.points[i][1]);
            }
            ctx.closePath();
            ctx.fillStyle = z.tipe === 'sanitizer' ? 'rgba(0,230,118,0.2)' : 'rgba(255,211,42,0.2)';
            ctx.fill();
            ctx.strokeStyle = z.tipe === 'sanitizer' ? '#00e676' : '#ffd32a';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Label
            ctx.fillStyle = '#fff';
            ctx.font = '11px Inter, sans-serif';
            ctx.fillText(z.nama, z.points[0][0], z.points[0][1] - 5);
        });

        // Draw current polygon
        if (points.length) {
            ctx.beginPath();
            ctx.moveTo(points[0][0], points[0][1]);
            for(let i=1; i<points.length; i++) {
                ctx.lineTo(points[i][0], points[i][1]);
            }
            ctx.strokeStyle = currentTipe === 'sanitizer' ? '#00e676' : '#ffd32a';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Draw points
            points.forEach(p => {
                ctx.beginPath();
                ctx.arc(p[0], p[1], 4, 0, 2*Math.PI);
                ctx.fillStyle = '#fff';
                ctx.fill();
                ctx.strokeStyle = currentTipe === 'sanitizer' ? '#00e676' : '#ffd32a';
                ctx.stroke();
            });
        }
    }

    canvas.addEventListener('click', e => {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = Math.round((e.clientX - rect.left) * scaleX);
        const y = Math.round((e.clientY - rect.top) * scaleY);

        points.push([x, y]);
        document.getElementById('pointCount').textContent = `${points.length} titik`;
        draw();
    });

    canvas.addEventListener('dblclick', () => {
        if (points.length < 3) return;
        draw();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            if (points.length < 3) return;
            draw();
        }
    });

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
        const name = document.getElementById('zonaName').value.trim();
        if (!name) {
            alert('Nama zona harus diisi!');
            return;
        }
        if (points.length < 3) {
            alert('Gambarkan minimal 3 titik polygon di atas video!');
            return;
        }

        try {
            const res = await fetch(`/cameras/${CAMERA_ID}/zones`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({
                    nama_zona: name,
                    tipe_zona: currentTipe,
                    polygon_points: points
                })
            });

            if (res.ok) {
                location.reload();
            } else {
                alert('Gagal menyimpan zona');
            }
        } catch {
            alert('Terjadi kesalahan jaringan');
        }
    }

    async function deleteZone(id) {
        if (!confirm('Hapus zona ini?')) return;
        try {
            const res = await fetch(`/cameras/zones/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF }
            });
            if (res.ok) {
                location.reload();
            } else {
                alert('Gagal menghapus zona');
            }
        } catch {
            alert('Terjadi kesalahan jaringan');
        }
    }

    // Initial render
    setTimeout(draw, 500);
</script>
@endpush
