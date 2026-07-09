<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Monitoring Kepatuhan Cuci Tangan</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --accent: #2563eb;
            --accent-dim: rgba(37, 99, 235, 0.08);
            --accent-glow: 0 4px 14px rgba(37, 99, 235, 0.25);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --red: #dc2626;
            --red-dim: rgba(220, 38, 38, 0.08);
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 40px 32px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .brand-sub {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 11px 14px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            user-select: none;
        }

        .remember-me input {
            cursor: pointer;
            accent-color: var(--accent);
        }

        .btn-login {
            width: 100%;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--accent-glow);
        }

        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            background: var(--red-dim);
            color: var(--red);
            border: 1px solid rgba(220, 38, 38, 0.2);
            padding: 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="brand-header">
            <span class="brand-icon">🏥</span>
            <h1 class="brand-title">Hand Hygiene Monitor</h1>
            <p class="brand-sub">Silakan masuk untuk mengakses sistem</p>
        </div>

        @if ($errors->any())
            <div class="alert">
                <span>⚠️</span>
                <span>Username atau password salah.</span>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn-login">Masuk ke Dashboard</button>
        </form>
    </div>
</div>

</body>
</html>
