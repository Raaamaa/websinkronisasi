<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pembaruan kata sandi wajib awal akun ELKA BPS Kabupaten Tegal.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Wajib Ganti Kata Sandi: ELKA - BPS Kabupaten Tegal</title>

    <style>
        /* Hallmark · shape: centered form screen · genre: editorial-warm · theme: Vintage Hangat (custom) */
        /* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: form spacing, resolved via warm card */
        :root {
            --bg-page: #FAF9F5;
            --bg-secondary: #F0EEE6;
            --bg-surface: #FFFFFF;
            --border-warm: #E8E6DC;
            --border-warm-strong: #D6D3C6;
            --text-main: #141413;
            --text-secondary: #5E5D59;
            --accent-terracotta: #B5532F;
            --accent-terracotta-hover: #9C4426;
            --bps-blue: #0B5FA5;
            --font-serif: Georgia, 'Times New Roman', serif;
            --font-sans: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --shadow-soft: 0 2px 8px rgba(20, 20, 19, 0.06);
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: var(--font-sans);
            font-size: 17px;
            line-height: 1.6;
            background-color: var(--bg-page);
            color: var(--text-main);
            overflow-x: clip;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        :focus-visible {
            outline: 3px solid var(--bps-blue);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        .quarantine-panel {
            width: 100%;
            max-width: 480px;
            background-color: var(--bg-surface);
            border: 1px solid var(--border-warm);
            border-radius: 16px;
            box-shadow: var(--shadow-soft);
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .quarantine-brand {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-secondary);
        }

        .quarantine-title {
            font-family: var(--font-serif);
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 6px 0;
            line-height: 1.25;
        }

        .quarantine-desc {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .form-label {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .form-input {
            width: 100%;
            min-height: 56px;
            padding: 12px 16px;
            background-color: var(--bg-surface);
            border: 1px solid var(--border-warm-strong);
            border-radius: 12px;
            color: var(--text-main);
            font-family: var(--font-sans);
            font-size: 1rem;
        }

        .btn-primary {
            width: 100%;
            min-height: 56px;
            padding: 14px 20px;
            background-color: var(--accent-terracotta);
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            font-family: var(--font-sans);
            font-size: 1.0625rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-primary:hover {
            background-color: var(--accent-terracotta-hover);
        }

        .btn-secondary {
            width: 100%;
            min-height: 56px;
            padding: 14px 20px;
            background-color: var(--bg-secondary);
            color: var(--text-main);
            border: 1px solid var(--border-warm-strong);
            border-radius: 12px;
            font-family: var(--font-sans);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-secondary:hover {
            background-color: var(--border-warm);
        }

        .alert-error {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="quarantine-panel">
        <div class="quarantine-brand">ELKA &bull; BPS Kabupaten Tegal</div>

        <header>
            <h1 class="quarantine-title">Ganti Kata Sandi Akun</h1>
            <p class="quarantine-desc">
                Demi keamanan, buat kata sandi baru sebelum memakai sistem ELKA BPS Kabupaten Tegal.
            </p>
        </header>

        @if ($errors->any())
            <div class="alert-error" role="alert">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.password.change.update') }}">
            @csrf

            <div class="form-group">
                <label for="new_password" class="form-label">Kata Sandi Baru (minimal 8 huruf/angka)</label>
                <input type="password" id="new_password" name="new_password" required autofocus class="form-input">
            </div>

            <div class="form-group">
                <label for="new_password_confirmation" class="form-label">Ulangi Kata Sandi Baru</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" required class="form-input">
            </div>

            <button type="submit" class="btn-primary">
                Simpan Kata Sandi dan Masuk
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">
                Batal dan Keluar
            </button>
        </form>
    </main>
</body>
</html>
