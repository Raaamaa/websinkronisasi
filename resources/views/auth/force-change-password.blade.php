<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pembaruan kata sandi wajib awal akun ELKA BPS Kabupaten Tegal.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Wajib Ganti Kata Sandi: ELKA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">

    <style>
/* Hallmark · shape: app screen · genre: modern-minimal · theme: Cobalt (by request)
 * nav: edge-aligned brand mast · signature: quarantine security keycap · states: 8 control states
 * axes: light/grotesk-sans/cool · posture: restrained · contrast: pass · v2.0.0 */
/* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: form containment, resolved via paper surface */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
            background-color: var(--color-paper-2);
        }

        .quarantine-panel {
            width: 100%;
            max-width: 440px;
            background-color: var(--color-paper);
            border: var(--rule-hair) solid var(--color-rule-2);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            padding: var(--space-xl);
            display: flex;
            flex-direction: column;
            gap: var(--space-lg);
        }

        .quarantine-header {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
        }

        .quarantine-title {
            font-family: var(--font-display);
            font-size: var(--text-display-s);
            font-weight: var(--display-weight);
            color: var(--color-ink);
            line-height: 1.2;
            letter-spacing: var(--tracking-display);
        }

        .quarantine-desc {
            font-family: var(--font-body);
            font-size: var(--text-sm);
            color: var(--color-muted);
            line-height: 1.5;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-2xs);
            margin-bottom: var(--space-md);
        }

        .form-label {
            font-family: var(--font-body);
            font-size: var(--text-xs);
            font-weight: 600;
            color: var(--color-ink-2);
        }

        .form-input {
            width: 100%;
            min-height: 44px;
            padding: var(--space-xs) var(--space-sm);
            background-color: var(--color-paper-2);
            border: var(--rule-hair) solid var(--color-rule-2);
            border-radius: var(--radius-input);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: var(--text-sm);
            box-sizing: border-box;
        }

        .form-input:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 1px;
            background-color: var(--color-paper);
        }

        .keycap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            width: 100%;
            min-height: 48px;
            padding: var(--space-sm) var(--space-lg);
            font-family: var(--font-display);
            font-size: var(--text-sm);
            font-weight: 600;
            letter-spacing: var(--tracking-display);
            text-decoration: none;
            cursor: pointer;
            border-radius: var(--radius-input);
            box-sizing: border-box;
            transition: transform var(--dur-micro) var(--ease-out), box-shadow var(--dur-micro) var(--ease-out);
        }

        .keycap:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        .keycap--primary {
            background-color: var(--color-accent);
            color: var(--color-accent-ink);
            border: var(--rule-hair) solid var(--color-accent-edge);
            box-shadow: var(--shadow-key-accent);
        }

        .keycap--primary:hover {
            transform: translateY(-1px);
        }

        .keycap--primary:active {
            transform: translateY(3px);
            box-shadow: var(--shadow-key-press);
        }

        .keycap--secondary {
            background-color: var(--color-paper-2);
            color: var(--color-muted);
            border: var(--rule-hair) solid var(--color-rule-2);
            box-shadow: var(--shadow-key);
        }

        .keycap--secondary:hover {
            color: var(--color-ink);
            transform: translateY(-1px);
        }

        .keycap--secondary:active {
            transform: translateY(3px);
            box-shadow: var(--shadow-key-press);
        }

        .alert-error {
            background-color: color-mix(in oklab, var(--color-error) 12%, var(--color-paper));
            border: var(--rule-hair) solid var(--color-error);
            color: var(--color-error);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-input);
            font-size: var(--text-xs);
            margin-bottom: var(--space-md);
        }
    </style>
</head>
<body>
    <main class="quarantine-panel">
        <header class="quarantine-header">
            <h1 class="quarantine-title">Perbarui Kata Sandi Akun</h1>
            <p class="quarantine-desc">
                Demi keamanan sistem, Anda diwajibkan mengganti kata sandi bawaan sebelum dapat mengakses dasbor ELKA.
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
                <label for="new_password" class="form-label">Kata Sandi Baru (Min. 8 karakter)</label>
                <input type="password" id="new_password" name="new_password" required autofocus class="form-input">
            </div>

            <div class="form-group">
                <label for="new_password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" required class="form-input">
            </div>

            <div style="margin-top: var(--space-md); display: flex; flex-direction: column; gap: var(--space-xs);">
                <button type="submit" class="keycap keycap--primary">
                    Simpan dan Lanjutkan
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="keycap keycap--secondary">
                Batal dan Keluar
            </button>
        </form>
    </main>
</body>
</html>
