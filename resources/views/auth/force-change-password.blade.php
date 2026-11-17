<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pembaruan kata sandi wajib awal akun ELKA BPS Kabupaten Tegal.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Wajib Ganti Kata Sandi: ELKA - BPS Kabupaten Tegal</title>

    <style>
        /* Hallmark · shape: centered form screen · genre: editorial · theme: Studio (by request) */
        /* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: focus ring thickness, 2px single layer */
        :root {
            color-scheme: light;
            --color-paper: oklch(97.2% 0.006 215);
            --color-paper-2: oklch(94% 0.009 215);
            --color-paper-3: oklch(90.5% 0.011 215);
            --color-paper-hi: oklch(99% 0.004 215);
            --color-ink: oklch(16% 0.032 250);
            --color-ink-2: oklch(34% 0.022 245);
            --color-muted: oklch(49% 0.018 240);
            --color-rule: oklch(84% 0.010 225);
            --color-accent: oklch(68% 0.172 50);
            --color-accent-hover: oklch(63% 0.172 50);
            --color-accent-ink: oklch(16% 0.032 250);
            --color-focus: oklch(52% 0.15 48);
            --font-display: "Hedvig Letters Serif", ui-serif, Georgia, serif;
            --font-body: "Geist", ui-sans-serif, system-ui, sans-serif;
            --radius-card: 3px;
            --rule-hair: 1px;
            --shadow-sheet: 1px 2px 0 color-mix(in oklab, var(--color-ink) 7%, transparent), 10px 18px 36px -14px color-mix(in oklab, var(--color-ink) 20%, transparent);
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --color-paper: oklch(15% 0.010 215);
                --color-paper-2: oklch(18.5% 0.012 215);
                --color-paper-3: oklch(22% 0.014 215);
                --color-paper-hi: oklch(19% 0.012 215);
                --color-ink: oklch(94% 0.010 250);
                --color-ink-2: oklch(82% 0.014 245);
                --color-muted: oklch(70% 0.014 240);
                --color-rule: oklch(30% 0.012 225);
                --color-accent: oklch(70% 0.135 50);
                --color-accent-hover: oklch(75% 0.135 50);
                --color-accent-ink: oklch(16% 0.032 250);
                --color-focus: oklch(74% 0.14 48);
                --shadow-sheet: inset 0 0 0 1px var(--color-rule);
            }
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.55;
            background-color: var(--color-paper);
            color: var(--color-ink);
            overflow-x: clip;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        :focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        .form-input:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: -2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
            }
        }

        .quarantine-panel {
            width: 100%;
            max-width: 460px;
            background-color: var(--color-paper-hi);
            border: var(--rule-hair) solid var(--color-rule);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-sheet);
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .quarantine-brand {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--color-ink-2);
            padding-bottom: 10px;
            border-bottom: var(--rule-hair) solid var(--color-ink);
        }

        .quarantine-title {
            font-family: var(--font-display);
            font-size: 1.625rem;
            font-weight: 400;
            letter-spacing: -0.022em;
            color: var(--color-ink);
            margin: 0 0 6px 0;
            line-height: 1.15;
        }

        .quarantine-desc {
            font-size: 0.9375rem;
            color: var(--color-ink-2);
            margin: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }

        .form-label {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-ink);
        }

        .form-input {
            width: 100%;
            min-height: 56px;
            padding: 12px 14px;
            background-color: var(--color-paper-hi);
            border: var(--rule-hair) solid var(--color-muted);
            border-radius: var(--radius-card);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            transition: opacity 180ms var(--ease-out);
        }

        .btn-primary {
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            background-color: var(--color-accent);
            color: var(--color-accent-ink);
            border: var(--rule-hair) solid var(--color-ink);
            border-radius: var(--radius-card);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-primary:hover {
            background-color: var(--color-accent-hover);
            opacity: 0.96;
        }

        .btn-primary:active {
            transform: scale(0.97);
        }

        .btn-secondary {
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            background-color: var(--color-paper-2);
            color: var(--color-ink);
            border: var(--rule-hair) solid var(--color-ink);
            border-radius: var(--radius-card);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-secondary:hover {
            background-color: var(--color-paper-3);
            opacity: 0.96;
        }

        .btn-secondary:active {
            transform: scale(0.97);
        }

        .alert-error {
            background-color: #FEE2E2;
            border: var(--rule-hair) solid #FCA5A5;
            color: #991B1B;
            padding: 12px 14px;
            border-radius: var(--radius-card);
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
