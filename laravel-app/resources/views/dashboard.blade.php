@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('page-title', '📊 Dashboard Monitoring')

@push('styles')
<style>
    /* ─── Stats Row ─────────────────────────────────────────────── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    /* ─── Camera Grid (2x2) ─────────────────────────────────────── */
    .camera-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 12px;
        height: calc(100vh - var(--topbar-h) - 130px);
        min-height: 420px;
        margin-bottom: 20px;
    }
    
    /* Fullscreen Support */
    .camera-grid:fullscreen {
        height: 100vh !important;
        margin: 0 !important;
        gap: 4px;
        background: #111;
        padding: 4px;
    }
    .camera-frame:fullscreen {
        border: none !important;
        border-radius: 0 !important;
        background: #000;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
    }
    .btn-icon {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        border-radius: 4px;
        padding: 2px 6px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s;
    }
    .btn-icon:hover { background: rgba(255,255,255,0.4); }

    .camera-frame {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: border-color 0.3s;
    }

    .camera-frame.status-patuh     { border-color: var(--green); }
    .camera-frame.status-tidak     { border-color: var(--red); }
    .camera-frame.status-aktif     { border-color: var(--accent); }

    .camera-frame-header {
        padding: 8px 12px;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: absolute;
        top: 0; left: 0; right: 0;
        z-index: 10;
    }

    .camera-frame-name {
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        text-shadow: 0 1px 4px rgba(0,0,0,0.8);
    }

    .camera-slot-status {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 10px;
    }

    .camera-slot-status.patuh    { background: var(--green-dim); color: var(--green); }
    .camera-slot-status.tidak    { background: var(--red-dim); color: var(--red); }
    .camera-slot-status.aktif    { background: var(--accent-dim); color: var(--accent); }
    .camera-slot-status.idle     { background: rgba(0,0,0,0.5); color: var(--text-muted); }

    .camera-canvas-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #000;
        position: relative;
    }

    .camera-canvas {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .camera-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: var(--text-muted);
        font-size: 13px;
        height: 100%;
    }

    .camera-placeholder-icon { font-size: 40px; opacity: 0.4; }

    .camera-selector {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 8px 10px;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(4px);
    }

    .camera-selector select {
        flex: 1;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 5px 8px;
        color: var(--text-primary);
        font-size: 12px;
        font-family: 'Inter', sans-serif;
        outline: none;
    }

    .btn-connect {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-connect.connect {
        background: var(--green);
        color: #000;
    }

    .btn-connect.disconnect {
        background: var(--red-dim);
        color: var(--red);
        border: 1px solid rgba(255,71,87,0.3);
    }

    /* FPS counter overlay */
    .fps-counter {
        position: absolute;
        top: 36px; right: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        color: rgba(255,255,255,0.5);
        background: rgba(0,0,0,0.5);
        padding: 2px 5px;
        border-radius: 4px;
        z-index: 10;
    }

    /* ─── Recent Logs ───────────────────────────────────────────── */
    .recent-logs-card {
        max-height: 320px;
        overflow-y: auto;
    }

    .log-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
        font-size: 13px;
    }

    .log-item:hover { background: var(--bg-card-hover); }
    .log-item:last-child { border-bottom: none; }

    .log-status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .log-status-dot.patuh     { background: var(--green); box-shadow: 0 0 6px var(--green); }
    .log-status-dot.tidak     { background: var(--red); box-shadow: 0 0 6px var(--red); }

    .log-person { font-weight: 600; color: var(--text-primary); min-width: 70px; }
    .log-camera { color: var(--text-muted); flex: 1; }
    .log-time   { color: var(--text-muted); font-size: 11px; font-family: 'JetBrains Mono', monospace; }
</style>
@endpush

@section('content')

<!-- ── Group Selector ────────────────────────────────────────────────── -->
<div class="card" style="padding:8px 16px; display:flex; align-items:center; gap:12px; margin-bottom:12px;">
    <span style="font-weight:600;font-size:12px;">Grup Monitoring:</span>
    <form method="GET" action="{{ route('dashboard') }}" style="display:flex;gap:8px;flex:1;">
        <select name="group_id" class="form-control" style="width:auto;min-width:180px;font-size:12px;padding:4px 8px;" onchange="this.form.submit()">
            @if($groups->isEmpty())
                <option value="">-- Belum ada grup --</option>
            @endif
            @foreach($groups as $g)
                <option value="{{ $g->id }}" {{ ($selected_group && $selected_group->id == $g->id) ? 'selected' : '' }}>
                    {{ $g->nama_kamera ?? $g->nama_grup }} ({{ $g->cameras->count() }} kamera)
                </option>
            @endforeach
        </select>
        @if($selected_group)
        <span class="badge {{ $selected_group->aktif ? 'badge-patuh' : 'badge-tidak-patuh' }}" style="margin-left:auto;font-size:11px;padding:4px 8px;">
            {{ $selected_group->aktif ? '● GRUP AKTIF' : '○ GRUP OFF' }}
        </span>
        @endif
    </form>
    <button id="btn-multi-connect" class="btn btn-success" style="padding:4px 10px;font-size:12px;background:var(--green-dim);color:var(--green);border:1px solid var(--green);" onclick="toggleAllCameras()">▶ Connect Semua</button>
    <button class="btn btn-primary" style="padding:4px 10px;font-size:12px;" onclick="toggleFullscreen('cameraGrid')" title="Fullscreen Grid">⛶ Fullscreen</button>
</div>

<!-- ── Grid Kamera dalam Grup ────────────────────────────────────────── -->
<div class="camera-grid" id="cameraGrid">
    @if($selected_group && $selected_group->cameras->isNotEmpty())
        @foreach($selected_group->cameras as $index => $cam)
            @php $slot = $index + 1; @endphp
            <div class="camera-frame {{ $cam->aktif ? 'status-aktif' : '' }}" id="frame-{{ $slot }}">

                <!-- Header overlay -->
                <div class="camera-frame-header">
                    <span class="camera-frame-name" id="fname-{{ $slot }}">
                        {{ $cam->tipe_icon }} {{ $cam->nama_kamera }}
                    </span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <button class="btn-icon" onclick="toggleFullscreen('frame-{{ $slot }}')" title="Fullscreen">⛶</button>
                        <span class="camera-slot-status {{ $cam->aktif ? 'aktif' : 'idle' }}" id="fstatus-{{ $slot }}">
                            {{ $cam->aktif ? 'LIVE' : 'IDLE' }}
                        </span>
                    </div>
                </div>

                <!-- FPS counter -->
                <div class="fps-counter" id="fps-{{ $slot }}">0 fps</div>

                <!-- Video canvas -->
                <div class="camera-canvas-wrap" id="canvasWrap-{{ $slot }}">
                    <div class="camera-placeholder" id="placeholder-{{ $slot }}" style="display: {{ $cam->aktif ? 'none' : 'flex' }}">
                        <div class="camera-placeholder-icon">📷</div>
                        <div>{{ $cam->aktif ? 'Connecting...' : 'Kamera OFF' }}</div>
                    </div>
                    <img id="canvas-{{ $slot }}" class="camera-canvas" style="display: {{ $cam->aktif ? 'block' : 'none' }}" alt="Camera {{ $slot }}">
                </div>

                <!-- Bottom selector -->
                <div class="camera-selector">
                    <input type="hidden" id="select-{{ $slot }}" value="{{ $cam->id }}">
                    <button class="btn-connect {{ $cam->aktif ? 'disconnect' : 'connect' }}" id="btn-{{ $slot }}"
                            onclick="toggleCamera({{ $slot }})">
                        {{ $cam->aktif ? '⏹ Stop' : '▶ Connect' }}
                    </button>
                    <span style="font-size:11px;color:var(--text-muted);margin-left:auto;">ID: {{ $cam->id }}</span>
                </div>
            </div>
        @endforeach
        
        <!-- Fill empty slots up to 4 if less than 4 cameras -->
        @for($i = $selected_group->cameras->count() + 1; $i <= 4; $i++)
            <div class="camera-frame" style="border: 1px dashed var(--border); background: var(--bg-primary); display:flex; align-items:center; justify-content:center; flex-direction:column; color:var(--text-muted); font-size:13px; gap:8px;">
                <div style="font-size:32px;opacity:0.3">➕</div>
                <div>Slot Kosong</div>
            </div>
        @endfor
    @else
        <div style="grid-column: 1 / -1; display:flex; flex-direction:column; align-items:center; justify-content:center; background:var(--bg-card); border:1px dashed var(--border); border-radius:var(--radius); min-height:300px; color:var(--text-muted);">
            <div style="font-size:48px; margin-bottom:16px;">📹</div>
            <div style="font-size:16px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Belum Ada Kamera di Grup Ini</div>
            <div style="font-size:13px; margin-bottom:16px;">Tambahkan kamera ke grup melalui menu Manajemen Grup.</div>
            <a href="{{ route('groups.index') }}" class="btn btn-primary">Kelola Grup</a>
        </div>
    @endif
</div>

<!-- ── Log Terbaru ─────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="card-title">🕐 Log Terbaru</span>
        <a href="{{ route('monitoring.index') }}" class="btn btn-ghost btn-sm">Lihat Semua →</a>
    </div>
    <div class="recent-logs-card" id="recentLogsContainer">
        @forelse($recent_logs as $log)
        <div class="log-item">
            <div class="log-status-dot {{ $log->status === 'patuh' ? 'patuh' : 'tidak' }}"></div>
            <span class="log-person">Person #{{ $log->person_id }}</span>
            <span class="log-camera">{{ $log->camera?->nama_kamera ?? 'Unknown' }}</span>
            <span class="badge {{ $log->status === 'patuh' ? 'badge-patuh' : 'badge-tidak-patuh' }}">
                {{ $log->status === 'patuh' ? '✅ Patuh' : '❌ Tidak Patuh' }}
            </span>
            <span class="log-time">{{ $log->waktu->format('H:i:s') }}</span>
        </div>
        @empty
        <div class="log-item" style="justify-content:center;color:var(--text-muted);">
            Belum ada data monitoring hari ini
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    const AI_WS = '{{ $ai_service_url }}';

    // State per slot: { ws, cameraId, frameCount, lastFpsTime, fps }
    const slotState = { 1: null, 2: null, 3: null, 4: null };
    let allConnected = false;

    function toggleAllCameras() {
        const btn = document.getElementById('btn-multi-connect');
        if (allConnected) {
            for (let slot = 1; slot <= 4; slot++) {
                if (slotState[slot]) disconnect(slot);
            }
            btn.innerHTML = '▶ Connect Semua';
            btn.style.background = 'var(--green-dim)';
            btn.style.color = 'var(--green)';
            btn.style.borderColor = 'var(--green)';
            allConnected = false;
        } else {
            for (let slot = 1; slot <= 4; slot++) {
                const sel = document.getElementById(`select-${slot}`);
                if (sel && sel.value && !slotState[slot]) connect(slot);
            }
            btn.innerHTML = '⏹ Stop Semua';
            btn.style.background = 'var(--red-dim)';
            btn.style.color = 'var(--red)';
            btn.style.borderColor = 'var(--red)';
            allConnected = true;
        }
    }

    function toggleFullscreen(elemId) {
        const elem = document.getElementById(elemId);
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                alert(`Gagal membuka mode fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    function onCameraSelect(slot) {
        // Obsolete function since select is removed, but kept for safe error handling just in case.
    }

    // Auto-connect if camera is already active
    document.addEventListener('DOMContentLoaded', () => {
        @if($selected_group && $selected_group->cameras->isNotEmpty())
            @foreach($selected_group->cameras as $index => $cam)
                @if($cam->aktif)
                    connect({{ $index + 1 }}, true);
                @endif
            @endforeach
        @endif
    });

    function toggleCamera(slot) {
        if (slotState[slot]) {
            disconnect(slot);
        } else {
            connect(slot);
        }
    }

    async function connect(slot, isAutoStart = false) {
        const sel = document.getElementById(`select-${slot}`);
        if (!sel) return;
        const cameraId = sel.value;
        if (!cameraId) return;

        if (!isAutoStart) {
            // Start monitoring on AI service manually
            try {
                const res = await fetch(`${AI_WS.replace('ws://', 'http://')}/api/cameras/${cameraId}/start`, {method: 'POST'});
                if (!res.ok) console.error(`[Cam ${slot}] Failed to start on AI service`);
            } catch(e) {}
        }

        const ws = new WebSocket(`${AI_WS}/ws/stream/${cameraId}`);
        const img = document.getElementById(`canvas-${slot}`);
        const placeholder = document.getElementById(`placeholder-${slot}`);
        const fpsEl = document.getElementById(`fps-${slot}`);
        const statusEl = document.getElementById(`fstatus-${slot}`);
        const frameEl = document.getElementById(`frame-${slot}`);
        const btn = document.getElementById(`btn-${slot}`);

        let frameCount = 0;
        let lastTime = Date.now();

        ws.onopen = () => {
            placeholder.style.display = 'none';
            img.style.display = 'block';
            btn.textContent = '⏹ Stop';
            btn.className = 'btn-connect disconnect';
            statusEl.textContent = 'LIVE';
            statusEl.className = 'camera-slot-status aktif';
            frameEl.className = 'camera-frame status-aktif';
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.error) {
                console.error(`[Cam ${slot}] ${data.error}`);
                return;
            }

            // Update frame
            img.src = 'data:image/jpeg;base64,' + data.frame;

            // FPS counter
            frameCount++;
            const now = Date.now();
            if (now - lastTime >= 1000) {
                const fps = Math.round(frameCount * 1000 / (now - lastTime));
                fpsEl.textContent = `${fps} fps`;
                frameCount = 0;
                lastTime = now;
            }
        };

        ws.onclose = () => {
            resetSlot(slot);
        };

        ws.onerror = () => {
            resetSlot(slot);
            statusEl.textContent = 'ERROR';
            statusEl.className = 'camera-slot-status idle';
        };

        slotState[slot] = { ws, cameraId };
    }

    function disconnect(slot) {
        if (slotState[slot]) {
            const { ws, cameraId } = slotState[slot];
            ws.close();
            fetch(`${AI_WS.replace('ws://', 'http://')}/api/cameras/${cameraId}/stop`, {method: 'POST'})
                .catch(() => {});
        }
        resetSlot(slot);
    }

    function resetSlot(slot) {
        slotState[slot] = null;
        const img = document.getElementById(`canvas-${slot}`);
        const placeholder = document.getElementById(`placeholder-${slot}`);
        const btn = document.getElementById(`btn-${slot}`);
        const statusEl = document.getElementById(`fstatus-${slot}`);
        const frameEl = document.getElementById(`frame-${slot}`);
        const fpsEl = document.getElementById(`fps-${slot}`);

        img.style.display = 'none';
        img.src = '';
        placeholder.style.display = 'flex';
        btn.textContent = '▶ Connect';
        btn.className = 'btn-connect connect';
        statusEl.textContent = 'IDLE';
        statusEl.className = 'camera-slot-status idle';
        frameEl.className = 'camera-frame';
        fpsEl.textContent = '0 fps';
    }

    // Refresh statistik & log setiap 10 detik
    async function refreshStats() {
        try {
            const res = await fetch('/api/stats/today');
            if (!res.ok) return;
            const d = await res.json();
            document.getElementById('statPatuh').textContent  = d.total_patuh  || 0;
            document.getElementById('statTidak').textContent  = d.total_tidak_patuh || 0;
            document.getElementById('statTotal').textContent  = d.total || 0;
            const pct = d.total > 0 ? Math.round(d.total_patuh / d.total * 100) : 0;
            document.getElementById('statPersen').textContent = pct + '%';
        } catch {}
    }

    async function refreshLogs() {
        try {
            const res = await fetch('/api/logs/recent?limit=10');
            if (!res.ok) return;
            const logs = await res.json();
            const container = document.getElementById('recentLogsContainer');
            if (!logs.length) return;

            container.innerHTML = logs.map(log => `
                <div class="log-item">
                    <div class="log-status-dot ${log.status === 'patuh' ? 'patuh' : 'tidak'}"></div>
                    <span class="log-person">Person #${log.person_id}</span>
                    <span class="log-camera">${log.nama_kamera || 'Unknown'}</span>
                    <span class="badge ${log.status === 'patuh' ? 'badge-patuh' : 'badge-tidak-patuh'}">
                        ${log.status === 'patuh' ? '✅ Patuh' : '❌ Tidak Patuh'}
                    </span>
                    <span class="log-time">${new Date(log.waktu).toLocaleTimeString('id-ID')}</span>
                </div>
            `).join('');
        } catch {}
    }

    setInterval(refreshStats, 10000);
    setInterval(refreshLogs, 8000);
</script>
@endpush
