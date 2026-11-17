@php
    $logoPath = null;
    if (file_exists(public_path('images/logo-bps-tegal.png'))) {
        $logoPath = asset('images/logo-bps-tegal.png');
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ELKA BPS Kabupaten Tegal - Perpustakaan Bukti Dukung Kantor.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ELKA - BPS Kabupaten Tegal</title>

    <style>
        /* Hallmark · shape: single column auth · genre: editorial · theme: Studio (by request) */
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
            --bps-blue: oklch(42% 0.14 248);
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
                --bps-blue: oklch(74% 0.12 248);
            }
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.5;
            background-color: var(--color-paper);
            color: var(--color-ink);
            overflow-x: clip;
        }

        :focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        .form-input-modern:focus-visible {
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

        .login-split-root {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
        }

        .auth-stage-inner {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: var(--rule-hair) solid var(--color-ink);
        }

        .brand-logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .brand-meta {
            display: flex;
            flex-direction: column;
        }

        .brand-agency-top {
            font-family: var(--font-display);
            font-size: 1.375rem;
            font-weight: 400;
            letter-spacing: -0.022em;
            color: var(--color-ink);
            line-height: 1.1;
        }

        .brand-agency-sub {
            font-size: 0.875rem;
            color: var(--color-ink-2);
            line-height: 1.3;
        }

        .auth-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 400;
            letter-spacing: -0.022em;
            margin: 0 0 2px 0;
            color: var(--color-ink);
            line-height: 1.15;
        }

        .auth-subtitle {
            font-size: 0.9375rem;
            color: var(--color-ink-2);
            margin: 0;
        }

        .auth-card {
            background-color: var(--color-paper-hi);
            border: var(--rule-hair) solid var(--color-rule);
            border-radius: var(--radius-card);
            padding: 20px;
            box-shadow: var(--shadow-sheet);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .auth-alert-box {
            padding: 10px 12px;
            border-radius: var(--radius-card);
            font-size: 0.9375rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .auth-alert-error {
            background-color: #FEE2E2;
            border: var(--rule-hair) solid #FCA5A5;
            color: #991B1B;
        }

        .auth-alert-success {
            background-color: #ECFDF5;
            border: var(--rule-hair) solid #86EFAC;
            color: #166534;
        }

        .btn-sso {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            border-radius: var(--radius-card);
            border: var(--rule-hair) solid var(--color-ink);
            background-color: var(--color-accent);
            color: var(--color-accent-ink);
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-sso:hover {
            background-color: var(--color-accent-hover);
            opacity: 0.96;
        }

        .btn-sso:active {
            transform: scale(0.97);
        }

        .notice-vpn-card {
            background-color: var(--color-paper-2);
            border: var(--rule-hair) solid var(--color-rule);
            border-radius: var(--radius-card);
            padding: 10px 12px;
            font-size: 0.875rem;
            color: var(--color-ink-2);
        }

        .notice-vpn-text {
            margin: 0;
            line-height: 1.45;
        }

        .mock-sso-link {
            text-align: center;
            font-size: 0.875rem;
            color: var(--bps-blue);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8125rem;
            color: var(--color-muted);
            text-align: center;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--color-rule);
        }

        .auth-form-local {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .form-label {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-ink);
            margin-bottom: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input-modern {
            width: 100%;
            min-height: 56px;
            padding: 12px 48px 12px 14px;
            border: var(--rule-hair) solid var(--color-muted);
            border-radius: var(--radius-card);
            background-color: var(--color-paper-hi);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            transition: opacity 180ms var(--ease-out);
        }

        .form-input-modern::placeholder {
            color: var(--color-muted);
        }

        .input-trailing-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            min-width: 44px;
            min-height: 44px;
            border: none;
            background: transparent;
            color: var(--color-ink-2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-card);
        }

        .form-check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9375rem;
            color: var(--color-ink);
        }

        .form-checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--color-accent);
        }

        .btn-submit-local {
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            border-radius: var(--radius-card);
            border: var(--rule-hair) solid var(--color-ink);
            background-color: var(--color-paper-2);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-submit-local:hover {
            background-color: var(--color-paper-3);
            opacity: 0.96;
        }

        .btn-submit-local:active {
            transform: scale(0.97);
        }

        .field-error {
            display: block;
            margin-top: 4px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #991B1B;
        }

        .auth-helpdesk {
            text-align: center;
            font-size: 0.875rem;
            color: var(--color-ink-2);
        }

        .auth-helpdesk a {
            color: var(--bps-blue);
            font-weight: 600;
        }

        .auth-footer-clean {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            color: var(--color-muted);
            padding-top: 10px;
            border-top: var(--rule-hair) solid var(--color-rule);
        }
    </style>
</head>

<body>
    <main class="login-split-root">
        <section class="auth-stage-inner" aria-label="Autentikasi Pengguna ELKA">
            <header class="brand-header">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" alt="Logo BPS Kabupaten Tegal" class="brand-logo-img">
                @else
                    <svg class="brand-logo-img" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                        <rect x="1.5" y="1.5" width="33" height="33" rx="3" fill="var(--color-paper-hi)" stroke="var(--color-ink)" stroke-width="1.5"/>
                        <path d="M11 8.5h9.5L25 13v14.5H11V8.5z" stroke="var(--color-ink)" stroke-width="1.75" stroke-linejoin="round"/>
                        <path d="M20.5 8.5V13H25" stroke="var(--color-ink)" stroke-width="1.75" stroke-linejoin="round"/>
                        <rect x="14" y="17.5" width="8" height="3" rx="1" fill="var(--color-accent)"/>
                    </svg>
                @endif
                <div class="brand-meta">
                    <span class="brand-agency-top">ELKA</span>
                    <span class="brand-agency-sub">BPS Kabupaten Tegal &bull; Perpustakaan Bukti Dukung</span>
                </div>
            </header>

            <div>
                <h1 class="auth-title">Masuk ke ELKA</h1>
                <p class="auth-subtitle">Silakan masuk memakai akun pegawai BPS atau akun pengelola.</p>
            </div>

            <div class="auth-card">
                @if (session('error'))
                    <div class="auth-alert-box auth-alert-error" role="alert">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('success'))
                    <div class="auth-alert-box auth-alert-success" role="alert">
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <a href="{{ route('auth.sso.login') }}" class="btn-sso" id="btn-login-sso">
                    <span>Login SSO BPS &rarr;</span>
                </a>

                <div class="notice-vpn-card">
                    <p class="notice-vpn-text">
                        Bila membuka dari luar kantor, nyalakan <strong>VPN FortiClient</strong> terlebih dahulu sebelum klik Login SSO BPS.
                    </p>
                </div>

                <a href="{{ route('auth.sso.mock_login') }}" class="mock-sso-link">
                    Mode Simulasi SSO (Uji Coba Mandiri)
                </a>

                <div class="auth-divider">
                    <span>atau masuk dengan akun pengelola</span>
                </div>

                <form method="POST" action="{{ route('login', [], false) }}" class="auth-form-local">
                    @csrf

                    <div>
                        <label for="email" class="form-label">Email atau NIP Pengelola</label>
                        <input
                            id="email"
                            type="text"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="form-input-modern"
                            placeholder="nama@bps.go.id atau 19850101...">
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Kata Sandi</label>
                        <div class="input-wrapper">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="form-input-modern"
                                placeholder="Masukkan kata sandi">
                            <button
                                type="button"
                                id="togglePassword"
                                aria-label="Tampilkan kata sandi"
                                class="input-trailing-btn">
                                <svg id="eyeShow" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.522 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eyeHide" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587A2 2 0 0013.413 13.4M9.88 5.09A9.77 9.77 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.79 9.79 0 01-4.132 5.411M6.228 6.232A9.785 9.785 0 002.458 12c1.274 4.057 5.064 7 9.542 7a9.77 9.77 0 005.147-1.462" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-check-row">
                        <input type="checkbox" name="remember" id="remember" class="form-checkbox">
                        <label for="remember">Ingat saya di peramban ini</label>
                    </div>

                    <button type="submit" class="btn-submit-local" id="btn-login-local">
                        Masuk sebagai Pengelola
                    </button>
                </form>
            </div>

            <div class="auth-helpdesk">
                Butuh bantuan akun? <a href="mailto:bps3328@bps.go.id">Hubungi Pengelola BPS Kabupaten Tegal</a>
            </div>

            <footer class="auth-footer-clean">
                <span>&copy; {{ date('Y') }} BPS Kabupaten Tegal</span>
                <span>ELKA</span>
            </footer>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeShow = document.getElementById('eyeShow');
            const eyeHide = document.getElementById('eyeHide');

            if (toggle && passwordInput && eyeShow && eyeHide) {
                toggle.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    eyeShow.style.display = isPassword ? 'none' : 'block';
                    eyeHide.style.display = isPassword ? 'block' : 'none';
                    toggle.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            }
        });
    </script>
</body>
</html>
