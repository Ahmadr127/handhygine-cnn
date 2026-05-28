<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hand Hygiene Monitor') — Sistem Monitoring Cuci Tangan</title>
    <meta name="description" content="Sistem monitoring kepatuhan cuci tangan tenaga kesehatan berbasis Computer Vision">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        /* ─── CSS Variables ─────────────────────────────────────────── */
        :root {
            --bg-primary:    #0a0e1a;
            --bg-secondary:  #111827;
            --bg-card:       #1a2236;
            --bg-card-hover: #1e2a40;
            --border:        #2d3748;
            --border-light:  #374151;

            --accent:        #00d4ff;
            --accent-dim:    rgba(0, 212, 255, 0.15);
            --accent-glow:   0 0 20px rgba(0, 212, 255, 0.3);

            --green:         #00e676;
            --green-dim:     rgba(0, 230, 118, 0.15);
            --red:           #ff4757;
            --red-dim:       rgba(255, 71, 87, 0.15);
            --orange:        #ffa502;
            --orange-dim:    rgba(255, 165, 2, 0.15);
            --yellow:        #ffd32a;

            --text-primary:   #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted:     #64748b;

            --sidebar-w:     240px;
            --topbar-h:      60px;
            --radius:        12px;
            --radius-sm:     8px;
            --shadow:        0 4px 24px rgba(0, 0, 0, 0.4);
        }

        /* ─── Reset & Base ──────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* ─── Sidebar ───────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
            line-height: 1.3;
        }

        .sidebar-logo-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 8px;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 8px 12px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--accent-dim);
            color: var(--accent);
            border: 1px solid rgba(0, 212, 255, 0.2);
        }

        .nav-item .nav-icon { font-size: 18px; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        .ai-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--bg-card);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .ai-status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--text-muted);
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .ai-status-dot.online  { background: var(--green); box-shadow: 0 0 8px var(--green); }
        .ai-status-dot.offline { background: var(--red); }

        .ai-status-text { font-size: 12px; color: var(--text-secondary); }

        /* ─── Main Content ──────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .topbar {
            height: var(--topbar-h);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
        }

        .topbar-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text-muted);
        }

        .page-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        /* ─── Cards ─────────────────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-body { padding: 20px; }

        /* ─── Stat Cards ─────────────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }

        .stat-card.patuh::before     { background: var(--green); }
        .stat-card.tidak-patuh::before { background: var(--red); }
        .stat-card.total::before     { background: var(--accent); }
        .stat-card.persen::before    { background: var(--orange); }

        .stat-icon { font-size: 28px; margin-bottom: 8px; }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-card.patuh .stat-value     { color: var(--green); }
        .stat-card.tidak-patuh .stat-value { color: var(--red); }
        .stat-card.total .stat-value     { color: var(--accent); }
        .stat-card.persen .stat-value    { color: var(--orange); }

        .stat-label { font-size: 12px; color: var(--text-muted); }

        /* ─── Badges ─────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-patuh {
            background: var(--green-dim);
            color: var(--green);
            border: 1px solid rgba(0, 230, 118, 0.3);
        }

        .badge-tidak-patuh {
            background: var(--red-dim);
            color: var(--red);
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .badge-monitoring {
            background: var(--accent-dim);
            color: var(--accent);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }

        /* ─── Buttons ────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: var(--accent);
            color: #0a0e1a;
        }

        .btn-primary:hover {
            background: #00b8d9;
            box-shadow: var(--accent-glow);
        }

        .btn-success {
            background: var(--green-dim);
            color: var(--green);
            border: 1px solid rgba(0, 230, 118, 0.3);
        }

        .btn-success:hover { background: rgba(0, 230, 118, 0.25); }

        .btn-danger {
            background: var(--red-dim);
            color: var(--red);
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .btn-danger:hover { background: rgba(255, 71, 87, 0.25); }

        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }

        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* ─── Forms ──────────────────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .form-control option { background: var(--bg-secondary); }

        /* ─── Tables ─────────────────────────────────────────────────── */
        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }

        tbody tr:hover td { background: var(--bg-card-hover); }
        tbody tr:last-child td { border-bottom: none; }

        /* ─── Alerts ─────────────────────────────────────────────────── */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: var(--green-dim);
            border: 1px solid rgba(0, 230, 118, 0.3);
            color: var(--green);
        }

        .alert-danger {
            background: var(--red-dim);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: var(--red);
        }

        /* ─── Scrollbar ──────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--border-light); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ─── Animations ─────────────────────────────────────────────── */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-in { animation: fadeIn 0.3s ease; }

        /* ─── Utilities ──────────────────────────────────────────────── */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .text-muted { color: var(--text-muted); }
        .text-sm { font-size: 13px; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .w-full { width: 100%; }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-title">🏥 Hand Hygiene</div>
            <div class="sidebar-logo-sub">Monitoring System v1.0</div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section-title">Utama</div>
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="{{ route('groups.index') }}"
               class="nav-item {{ request()->routeIs('groups.*') ? 'active' : '' }}">
                <span class="nav-icon">🏢</span> Grup Monitoring
            </a>
            <a href="{{ route('cameras.index') }}"
               class="nav-item {{ request()->routeIs('cameras.*') ? 'active' : '' }}">
                <span class="nav-icon">📷</span> Kamera
            </a>
            <a href="{{ route('monitoring.index') }}"
               class="nav-item {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                <span class="nav-icon">📋</span> Log Monitoring
            </a>

            <div class="nav-section-title" style="margin-top:8px">Info</div>
            <a href="http://localhost:8001/docs" target="_blank" class="nav-item">
                <span class="nav-icon">🔌</span> AI Service API
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="ai-status" id="aiStatusWidget">
                <div class="ai-status-dot" id="aiDot"></div>
                <div>
                    <div class="ai-status-text" id="aiStatusText">Mengecek AI...</div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-time" id="clockDisplay"></div>
        </div>

        <div class="page-content fade-in">
            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">❌ {{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

    <script>
        // Live clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clockDisplay').textContent =
                now.toLocaleDateString('id-ID', { weekday:'short', day:'2-digit', month:'short', year:'numeric' })
                + ' ' + now.toLocaleTimeString('id-ID');
        }
        updateClock();
        setInterval(updateClock, 1000);

        // AI Service health check
        async function checkAiService() {
            const dot  = document.getElementById('aiDot');
            const text = document.getElementById('aiStatusText');
            try {
                const res = await fetch('http://localhost:8001/api/status', { signal: AbortSignal.timeout(3000) });
                if (res.ok) {
                    const data = await res.json();
                    dot.className = 'ai-status-dot online';
                    const n = data.total_running || 0;
                    text.textContent = `AI Online · ${n} kamera`;
                } else {
                    throw new Error();
                }
            } catch {
                dot.className = 'ai-status-dot offline';
                text.textContent = 'AI Service Offline';
            }
        }
        checkAiService();
        setInterval(checkAiService, 10000);
    </script>

    @stack('scripts')
</body>
</html>
