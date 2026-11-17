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
        /* Hallmark · shape: two-column auth + depth stack · genre: editorial · theme: Studio (by request) · soft glass + depth */
        /* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: muted terracotta on glass, verified 5.85:1+ */
        :root {
            color-scheme: light;
            --color-paper: oklch(97.2% 0.006 215);
            --color-paper-2: oklch(94% 0.009 215);
            --color-paper-3: oklch(90.5% 0.011 215);
            --color-paper-hi: oklch(99% 0.004 215);
            --color-ink: oklch(16% 0.032 250);
            --color-ink-2: oklch(34% 0.022 245);
            --color-muted: oklch(44% 0.018 240);
            --color-focus: oklch(52% 0.15 48);
            --color-sage: #2E6F65;
            --color-sage-soft: rgba(46, 111, 101, 0.14);
            --color-cobalt: #1D5A96;
            --color-cobalt-soft: rgba(29, 90, 150, 0.14);
            --font-display: "Hedvig Letters Serif", ui-serif, Georgia, serif;
            --font-body: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --radius-card: 20px;
            --radius-control: 14px;
            --radius-pill: 999px;
            --rule-hair: 1px;
            --border-soft: rgba(20, 28, 44, 0.11);
            --border-input: rgba(20, 28, 44, 0.22);
            --glass-bg: rgba(255, 255, 255, 0.72);
            --glass-border: rgba(255, 255, 255, 0.72);
            --glass-subtle-bg: rgba(244, 246, 248, 0.68);
            --btn-primary-from: #A44A28;
            --btn-primary-to: #8F3D1F;
            --btn-primary-hover-from: #984323;
            --btn-primary-hover-to: #7E3419;
            --btn-primary-ink: #FFFFFF;
            --shadow-soft: 0 2px 6px rgba(18, 24, 38, 0.04), 0 12px 28px -12px rgba(18, 24, 38, 0.10);
            --shadow-card-hover: 0 4px 10px rgba(18, 24, 38, 0.06), 0 18px 36px -14px rgba(18, 24, 38, 0.15);
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
            --bps-blue: var(--color-cobalt);
        }

        html.dark {
            color-scheme: dark;
            --color-paper: oklch(15% 0.010 215);
            --color-paper-2: oklch(18.5% 0.012 215);
            --color-paper-3: oklch(22% 0.014 215);
            --color-paper-hi: oklch(19% 0.012 215);
            --color-ink: oklch(94% 0.010 250);
            --color-ink-2: oklch(82% 0.014 245);
            --color-muted: oklch(72% 0.014 240);
            --color-focus: oklch(74% 0.14 48);
            --color-sage: #6EC4B4;
            --color-sage-soft: rgba(110, 196, 180, 0.16);
            --color-cobalt: #7CB4EB;
            --color-cobalt-soft: rgba(124, 180, 235, 0.16);
            --border-soft: rgba(255, 255, 255, 0.12);
            --border-input: rgba(255, 255, 255, 0.22);
            --glass-bg: rgba(24, 29, 40, 0.76);
            --glass-border: rgba(255, 255, 255, 0.13);
            --glass-subtle-bg: rgba(34, 40, 52, 0.66);
            --btn-primary-hover-from: #B25734;
            --btn-primary-hover-to: #984323;
            --shadow-soft: 0 2px 8px rgba(0, 0, 0, 0.25), 0 14px 32px -14px rgba(0, 0, 0, 0.48), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            --shadow-card-hover: 0 4px 12px rgba(0, 0, 0, 0.34), 0 20px 40px -14px rgba(0, 0, 0, 0.62), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            --bps-blue: var(--color-cobalt);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            background-color: var(--color-paper);
            overflow-x: clip;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100dvh;
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.5;
            background-color: transparent;
            color: var(--color-ink);
            overflow-x: clip;
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
                radial-gradient(48rem 36rem at 10% 8%, rgba(214, 134, 96, 0.38), transparent 68%),
                radial-gradient(44rem 34rem at 88% 16%, rgba(235, 212, 174, 0.48), transparent 68%),
                radial-gradient(48rem 36rem at 74% 88%, rgba(146, 190, 224, 0.38), transparent 68%);
        }

        html.dark body::before {
            background-image:
                radial-gradient(46rem 34rem at 10% 8%, rgba(164, 74, 40, 0.14), transparent 72%),
                radial-gradient(40rem 32rem at 88% 16%, rgba(170, 148, 114, 0.10), transparent 72%),
                radial-gradient(46rem 34rem at 74% 88%, rgba(82, 132, 178, 0.14), transparent 72%);
        }

        :focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        .form-input-modern:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: -2px;
        }

        .login-split-root {
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr;
        }

        .panel-brand {
            padding: 16px 20px;
            border-bottom: var(--rule-hair) solid var(--border-soft);
            background: linear-gradient(155deg, rgba(244, 232, 216, 0.72), rgba(232, 240, 246, 0.65));
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
        }

        html.dark .panel-brand {
            background: linear-gradient(155deg, rgba(32, 28, 30, 0.75), rgba(22, 28, 38, 0.75));
        }

        .brand-main-block,
        .brand-footer,
        .login-doc-visual {
            display: none;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-weight: 700;
            font-size: 1rem;
            color: var(--color-ink);
            line-height: 1.2;
        }

        .brand-agency-sub {
            font-size: 0.9375rem;
            color: var(--color-ink-2);
            line-height: 1.3;
        }

        .panel-auth {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 16px;
        }

        .auth-stage-inner {
            width: 100%;
            max-width: 430px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        @media (min-width: 1024px) {
            .login-split-root {
                grid-template-columns: 1.05fr 0.95fr;
            }

            .panel-brand {
                padding: 28px 44px;
                border-bottom: none;
                border-right: var(--rule-hair) solid var(--border-soft);
            }

            .brand-main-block {
                display: flex;
                flex-direction: column;
                gap: 14px;
                max-width: 48ch;
            }

            .brand-footer {
                display: block;
                font-size: 0.9375rem;
                color: var(--color-muted);
            }

            .login-doc-visual {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                padding: 4px 0;
            }

            .panel-auth {
                padding: 20px 36px;
            }
        }

        .brand-system-name {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 4vw + 1rem, 4rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.05;
            margin: 0;
            color: var(--color-ink);
        }

        .brand-description {
            font-size: 1.0625rem;
            color: var(--color-ink-2);
            margin: 0;
            line-height: 1.5;
        }

        .brand-points {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .brand-point {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-ink);
        }

        .brand-point-tile {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .brand-point-tile-warm {
            background-color: rgba(164, 74, 40, 0.14);
            color: var(--btn-primary-from);
        }

        .brand-point-tile-sage {
            background-color: var(--color-sage-soft);
            color: var(--color-sage);
        }

        .brand-point-tile-blue {
            background-color: var(--color-cobalt-soft);
            color: var(--color-cobalt);
        }

        .login-doc-stack {
            position: relative;
            width: 240px;
            height: 158px;
            perspective: 720px;
        }

        .login-doc-sheet {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 122px;
            height: 138px;
            border-radius: 16px;
            background-color: var(--color-paper-hi);
            border: var(--rule-hair) solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            will-change: transform;
        }

        .login-doc-sheet::before {
            content: "";
            position: absolute;
            left: 16px;
            right: 16px;
            top: 22px;
            height: 8px;
            border-radius: 999px;
            background-color: var(--border-input);
        }

        .login-doc-sheet::after {
            content: "";
            position: absolute;
            left: 16px;
            right: 34px;
            top: 40px;
            height: 6px;
            border-radius: 999px;
            background-color: var(--border-soft);
        }

        .login-doc-sheet-1 {
            transform: translate(-84%, -48%) rotateX(14deg) rotateY(-14deg) rotateZ(-9deg);
            opacity: 0.78;
            animation: loginFloat1 7s ease-in-out infinite;
        }

        .login-doc-sheet-2 {
            transform: translate(-18%, -46%) rotateX(12deg) rotateY(12deg) rotateZ(8deg);
            opacity: 0.88;
            animation: loginFloat2 7.5s ease-in-out infinite;
        }

        .login-doc-sheet-3 {
            transform: translate(-50%, -52%) rotateX(10deg) rotateY(-4deg);
            animation: loginFloat3 6.5s ease-in-out infinite;
        }

        .login-doc-sheet-3::before {
            background: linear-gradient(90deg, var(--btn-primary-from), var(--btn-primary-to));
        }

        @keyframes loginFloat1 {
            0%, 100% {
                transform: translate(-84%, -48%) rotateX(14deg) rotateY(-14deg) rotateZ(-9deg);
            }
            50% {
                transform: translate(-84%, -54%) rotateX(16deg) rotateY(-12deg) rotateZ(-7deg);
            }
        }

        @keyframes loginFloat2 {
            0%, 100% {
                transform: translate(-18%, -46%) rotateX(12deg) rotateY(12deg) rotateZ(8deg);
            }
            50% {
                transform: translate(-18%, -52%) rotateX(14deg) rotateY(10deg) rotateZ(6deg);
            }
        }

        @keyframes loginFloat3 {
            0%, 100% {
                transform: translate(-50%, -52%) rotateX(10deg) rotateY(-4deg);
            }
            50% {
                transform: translate(-50%, -59%) rotateX(8deg) rotateY(-2deg);
            }
        }

        .auth-title {
            font-family: var(--font-body);
            font-size: clamp(1.5rem, 1.5vw + 1rem, 1.875rem);
            font-weight: 700;
            letter-spacing: -0.025em;
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
            background-color: var(--glass-bg);
            backdrop-filter: blur(10px) saturate(140%);
            border: var(--rule-hair) solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 18px 20px;
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 200ms var(--ease-out), box-shadow 200ms var(--ease-out);
        }

        .auth-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card-hover);
        }

        @supports not (backdrop-filter: blur(1px)) {
            .auth-card {
                background-color: var(--color-paper-hi);
            }
        }

        .auth-alert-box {
            padding: 9px 12px;
            border-radius: var(--radius-control);
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
            border-radius: var(--radius-pill);
            border: var(--rule-hair) solid rgba(120, 48, 22, 0.28);
            background: linear-gradient(180deg, var(--btn-primary-from), var(--btn-primary-to));
            color: var(--btn-primary-ink);
            box-shadow: 0 2px 6px rgba(143, 61, 31, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.18);
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out);
        }

        .btn-sso:hover {
            background: linear-gradient(180deg, var(--btn-primary-hover-from), var(--btn-primary-hover-to));
            transform: translateY(-1px);
            opacity: 0.97;
        }

        .btn-sso:active {
            transform: scale(0.97);
        }

        .notice-vpn-card {
            background-color: var(--glass-subtle-bg);
            border: var(--rule-hair) solid var(--border-soft);
            border-radius: var(--radius-control);
            padding: 8px 12px;
            font-size: 0.9375rem;
            color: var(--color-ink-2);
        }

        .notice-vpn-text {
            margin: 0;
            line-height: 1.4;
        }

        .mock-sso-link {
            text-align: center;
            font-size: 0.8125rem;
            color: var(--color-muted);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 2px;
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
            background-color: var(--border-soft);
        }

        .auth-form-local {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .form-label {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-ink);
            margin-bottom: 3px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input-modern {
            width: 100%;
            min-height: 56px;
            padding: 10px 48px 10px 14px;
            border: var(--rule-hair) solid var(--border-input);
            border-radius: var(--radius-control);
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
            border-radius: var(--radius-pill);
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
            accent-color: var(--btn-primary-from);
        }

        .btn-submit-local {
            width: 100%;
            min-height: 56px;
            padding: 12px 18px;
            border-radius: var(--radius-pill);
            border: var(--rule-hair) solid var(--border-soft);
            background-color: var(--glass-subtle-bg);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(18, 24, 38, 0.05);
            transition: transform 180ms var(--ease-out), opacity 180ms var(--ease-out), background-color 180ms var(--ease-out);
        }

        .btn-submit-local:hover {
            background-color: var(--color-paper-2);
            transform: translateY(-1px);
            opacity: 0.97;
        }

        .btn-submit-local:active {
            transform: scale(0.97);
        }

        .field-error {
            display: block;
            margin-top: 3px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #991B1B;
        }

        .auth-helpdesk {
            text-align: center;
            font-size: 0.9375rem;
            color: var(--color-ink-2);
        }

        .auth-helpdesk a {
            color: var(--bps-blue);
            font-weight: 600;
        }

        .auth-footer-clean {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--color-muted);
            padding-top: 6px;
            border-top: var(--rule-hair) solid var(--border-soft);
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <main class="login-split-root">
        <section class="panel-brand" aria-label="Informasi Sistem ELKA">
            <header class="brand-header">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" alt="Logo BPS Kabupaten Tegal" class="brand-logo-img">
                @else
                    <svg class="brand-logo-img" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                        <rect x="1.5" y="1.5" width="33" height="33" rx="8" fill="var(--color-paper-hi)" stroke="var(--color-ink)" stroke-width="1.5"/>
                        <path d="M11 8.5h9.5L25 13v14.5H11V8.5z" stroke="var(--color-ink)" stroke-width="1.75" stroke-linejoin="round"/>
                        <path d="M20.5 8.5V13H25" stroke="var(--color-ink)" stroke-width="1.75" stroke-linejoin="round"/>
                        <rect x="14" y="17.5" width="8" height="3" rx="1.5" fill="var(--btn-primary-from)"/>
                    </svg>
                @endif
                <div class="brand-meta">
                    <span class="brand-agency-top">BPS Kabupaten Tegal</span>
                    <span class="brand-agency-sub">Perpustakaan Bukti Dukung Kantor</span>
                </div>
            </header>

            <div class="brand-main-block">
                <h1 class="brand-system-name">ELKA</h1>
                <p class="brand-description">
                    Tempat menyimpan, mencari, dan memeriksa dokumen bukti dukung untuk enam Lembar Kerja BPS Kabupaten Tegal.
                </p>

                <ul class="brand-points">
                    <li class="brand-point">
                        <span class="brand-point-tile brand-point-tile-warm" aria-hidden="true">1</span>
                        <span>Cari Cepat</span>
                    </li>
                    <li class="brand-point">
                        <span class="brand-point-tile brand-point-tile-sage" aria-hidden="true">2</span>
                        <span>Cegah Berkas Ganda</span>
                    </li>
                    <li class="brand-point">
                        <span class="brand-point-tile brand-point-tile-blue" aria-hidden="true">3</span>
                        <span>Terhubung ke Enam LK</span>
                    </li>
                </ul>

                <div class="login-doc-visual" aria-hidden="true">
                    <div class="login-doc-stack">
                        <span class="login-doc-sheet login-doc-sheet-1"></span>
                        <span class="login-doc-sheet login-doc-sheet-2"></span>
                        <span class="login-doc-sheet login-doc-sheet-3"></span>
                    </div>
                </div>
            </div>

            <footer class="brand-footer">
                Jaringan Internal BPS Kabupaten Tegal
            </footer>
        </section>

        <section class="panel-auth" aria-label="Autentikasi Pengguna ELKA">
            <div class="auth-stage-inner">
                <div>
                    <h2 class="auth-title">Masuk ke ELKA</h2>
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
            </div>
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
