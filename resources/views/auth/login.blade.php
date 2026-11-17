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
        /* Hallmark · shape: split auth screen · genre: editorial-warm · theme: Vintage Hangat (custom) */
        /* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: terracotta contrast, verified 4.96:1 */
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
            --bps-blue-hover: #084B85;
            --font-serif: Georgia, 'Times New Roman', serif;
            --font-sans: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --shadow-soft: 0 2px 8px rgba(20, 20, 19, 0.06);
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: var(--font-sans);
            font-size: 17px;
            line-height: 1.6;
            background-color: var(--bg-page);
            color: var(--text-main);
            overflow-x: clip;
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

        .login-split-root {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .panel-brand {
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-warm);
            padding: clamp(32px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 32px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-img {
            width: 48px;
            height: 48px;
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
            color: var(--text-main);
            line-height: 1.3;
        }

        .brand-agency-sub {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            line-height: 1.3;
        }

        .brand-system-name {
            font-family: var(--font-serif);
            font-size: clamp(2rem, 3.2vw, 2.75rem);
            font-weight: 700;
            margin: 0 0 12px 0;
            color: var(--text-main);
            line-height: 1.15;
        }

        .brand-description {
            font-size: 1.0625rem;
            color: var(--text-secondary);
            margin: 0 0 28px 0;
            max-width: 46ch;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            background-color: var(--bg-surface);
            border: 1px solid var(--border-warm);
            border-radius: 14px;
            box-shadow: var(--shadow-soft);
        }

        .feature-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: #ECFDF5;
            border: 1px solid #86EFAC;
            color: #166534;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .feature-title {
            font-family: var(--font-serif);
            font-size: 1.0625rem;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: var(--text-main);
        }

        .feature-desc {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .brand-footer {
            font-size: 0.9375rem;
            color: var(--text-secondary);
        }

        .panel-auth {
            background-color: var(--bg-page);
            padding: clamp(32px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .auth-stage-inner {
            width: 100%;
            max-width: 460px;
            margin: auto 0;
        }

        .auth-title {
            font-family: var(--font-serif);
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: var(--text-main);
        }

        .auth-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0 0 24px 0;
        }

        .auth-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-warm);
            border-radius: 16px;
            padding: 28px;
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .auth-alert-box {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .auth-alert-error {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }

        .auth-alert-success {
            background-color: #ECFDF5;
            border: 1px solid #86EFAC;
            color: #166534;
        }

        .btn-sso {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 56px;
            padding: 14px 20px;
            border-radius: 12px;
            background-color: var(--accent-terracotta);
            color: #FFFFFF;
            font-size: 1.0625rem;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }

        .btn-sso:hover {
            background-color: var(--accent-terracotta-hover);
        }

        .notice-vpn-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-warm);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9375rem;
            color: var(--text-main);
        }

        .notice-vpn-text {
            margin: 0;
            line-height: 1.5;
        }

        .mock-sso-link {
            text-align: center;
            font-size: 0.9375rem;
            color: var(--bps-blue);
            font-weight: 600;
            text-decoration: underline;
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border-warm);
        }

        .auth-form-local {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input-modern {
            width: 100%;
            min-height: 56px;
            padding: 12px 48px 12px 16px;
            border: 1px solid var(--border-warm-strong);
            border-radius: 12px;
            background-color: var(--bg-surface);
            color: var(--text-main);
            font-family: var(--font-sans);
            font-size: 1rem;
        }

        .input-trailing-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            min-width: 44px;
            min-height: 44px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .form-check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9375rem;
            color: var(--text-main);
        }

        .form-checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--accent-terracotta);
        }

        .btn-submit-local {
            width: 100%;
            min-height: 56px;
            padding: 14px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-warm-strong);
            background-color: var(--bg-secondary);
            color: var(--text-main);
            font-family: var(--font-sans);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-submit-local:hover {
            background-color: var(--border-warm);
        }

        .field-error {
            display: block;
            margin-top: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #991B1B;
        }

        .auth-helpdesk {
            margin-top: 18px;
            text-align: center;
            font-size: 0.9375rem;
            color: var(--text-secondary);
        }

        .auth-helpdesk a {
            color: var(--bps-blue);
            font-weight: 600;
        }

        .auth-footer-clean {
            width: 100%;
            max-width: 460px;
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--text-secondary);
            padding-top: 12px;
            border-top: 1px solid var(--border-warm);
        }

        @media (max-width: 960px) {
            .login-split-root {
                grid-template-columns: 1fr;
            }

            .panel-brand {
                border-right: none;
                border-bottom: 1px solid var(--border-warm);
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
                    <svg class="brand-logo-img" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect width="48" height="48" rx="12" fill="#B5532F"/>
                        <path d="M13 15h22M13 24h16M13 33h22" stroke="#FAF9F5" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                @endif
                <div class="brand-meta">
                    <span class="brand-agency-top">BPS Kabupaten Tegal</span>
                    <span class="brand-agency-sub">Perpustakaan Bukti Dukung Kantor</span>
                </div>
            </header>

            <div>
                <h1 class="brand-system-name">ELKA</h1>
                <p class="brand-description">
                    Tempat menyimpan, mencari, dan memeriksa dokumen bukti dukung untuk enam Lembar Kerja BPS Kabupaten Tegal.
                </p>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">✓</div>
                        <div>
                            <h2 class="feature-title">Cari Dokumen Cepat</h2>
                            <p class="feature-desc">Temukan surat keputusan, SOP, dan laporan yang sudah disetujui dalam satu tabel.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">✓</div>
                        <div>
                            <h2 class="feature-title">Cegah Berkas Ganda</h2>
                            <p class="feature-desc">Periksa berkas lebih dulu sebelum diunggah agar dokumen yang sama tidak masuk dua kali.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">✓</div>
                        <div>
                            <h2 class="feature-title">Terhubung ke Enam LK</h2>
                            <p class="feature-desc">KIP, PEKPPP, ZI, SAKIP, Data Sektoral, dan Data Sekunder tersusun rapi.</p>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="brand-footer">
                Jaringan Internal BPS Kabupaten Tegal
            </footer>
        </section>

        <section class="panel-auth" aria-label="Autentikasi Pengguna">
            <div class="auth-stage-inner">
                <header>
                    <h2 class="auth-title">Masuk ke ELKA</h2>
                    <p class="auth-subtitle">Silakan masuk memakai akun pegawai BPS atau akun pengelola.</p>
                </header>

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
