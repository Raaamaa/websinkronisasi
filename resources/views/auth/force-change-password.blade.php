<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pembaruan kata sandi wajib awal akun ELKA BPS Kabupaten Tegal.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Wajib Ganti Kata Sandi: ELKA - BPS Kabupaten Tegal</title>

    <style>
        /* Hallmark · shape: centered form screen · genre: editorial · theme: Field Office base + BPS logo palette */
        :root {
            color-scheme: light;
            --color-paper: #F6F8FA;
            --color-paper-2: #EEF2F6;
            --color-paper-3: #E4EAF1;
            --color-paper-hi: #FFFFFF;
            --color-ink: #0F1B2D;
            --color-ink-2: #334155;
            --color-muted: #526071;
            --color-focus: #0077B6;
            --font-display: ui-serif, Georgia, Cambria, "Times New Roman", serif;
            --font-body: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --radius-card: 20px;
            --radius-control: 14px;
            --radius-pill: 999px;
            --rule-hair: 1px;
            --border-soft: rgba(15, 27, 45, 0.10);
            --border-input: rgba(15, 27, 45, 0.20);
            --btn-primary-from: #0077B6;
            --btn-primary-to: #00629A;
            --btn-primary-hover-from: #00629A;
            --btn-primary-hover-to: #005180;
            --btn-primary-ink: #FFFFFF;
            --shadow-sheet: 0 2px 6px rgba(15, 27, 45, 0.04), 0 12px 28px -12px rgba(15, 27, 45, 0.10);
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        html.dark {
            color-scheme: dark;
            --color-paper: #0E1621;
            --color-paper-2: #152231;
            --color-paper-3: #1C2D40;
            --color-paper-hi: #152231;
            --color-ink: #EAF1F8;
            --color-ink-2: #C2D0DF;
            --color-muted: #9DB0C4;
            --color-focus: #4DB8EE;
            --border-soft: rgba(234, 241, 248, 0.12);
            --border-input: rgba(234, 241, 248, 0.22);
            --btn-primary-hover-from: #0086CE;
            --btn-primary-hover-to: #006BA6;
            --shadow-sheet: 0 2px 8px rgba(0, 0, 0, 0.28), 0 14px 32px -14px rgba(0, 0, 0, 0.50), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        html {
            background-color: var(--color-paper);
            overflow-x: clip;
        }

        body {
            margin: 0;
            padding: 24px 16px;
            min-height: 100vh;
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.55;
            background-color: transparent;
            color: var(--color-ink);
            overflow-x: clip;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            isolation: isolate;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
            background-image:
                radial-gradient(46rem 34rem at 12% 10%, rgba(0, 147, 221, 0.14), transparent 68%),
                radial-gradient(42rem 32rem at 88% 18%, rgba(235, 137, 27, 0.12), transparent 68%),
                radial-gradient(46rem 34rem at 76% 88%, rgba(104, 185, 46, 0.12), transparent 68%);
        }

        html.dark body::before {
            background-image:
                radial-gradient(46rem 34rem at 12% 10%, rgba(0, 147, 221, 0.13), transparent 72%),
                radial-gradient(42rem 32rem at 88% 18%, rgba(235, 137, 27, 0.09), transparent 72%),
                radial-gradient(46rem 34rem at 76% 88%, rgba(104, 185, 46, 0.10), transparent 72%);
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
            border: var(--rule-hair) solid var(--border-soft);
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
            border-bottom: var(--rule-hair) solid var(--border-soft);
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
            border: var(--rule-hair) solid var(--border-input);
            border-radius: var(--radius-control);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            transition: opacity 180ms var(--ease-out);
        }

        .btn-primary {
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            background: linear-gradient(180deg, var(--btn-primary-from), var(--btn-primary-to));
            color: var(--btn-primary-ink);
            border: var(--rule-hair) solid rgba(0, 98, 154, 0.30);
            border-radius: var(--radius-pill);
            box-shadow: 0 2px 6px rgba(0, 119, 182, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.22);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-primary:hover {
            background: linear-gradient(180deg, var(--btn-primary-hover-from), var(--btn-primary-hover-to));
            transform: translateY(-1px);
            opacity: 0.97;
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
            border: var(--rule-hair) solid var(--border-soft);
            border-radius: var(--radius-pill);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-secondary:hover {
            background-color: var(--color-paper-3);
            transform: translateY(-1px);
            opacity: 0.97;
        }

        .btn-secondary:active {
            transform: scale(0.97);
        }

        .alert-error {
            background-color: #FEE2E2;
            border: var(--rule-hair) solid #FCA5A5;
            color: #991B1B;
            padding: 12px 14px;
            border-radius: var(--radius-control);
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
