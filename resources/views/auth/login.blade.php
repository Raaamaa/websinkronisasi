<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal autentikasi terpadu ELKA BPS Kabupaten Tegal untuk pemetaan dokumen bukti dukung ke lembar kerja internal dan eksternal.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ELKA: BPS Kabupaten Tegal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
/* Hallmark · shape: app screen · genre: modern-minimal · theme: Cobalt (by request)
 * nav: edge-aligned brand mast · signature: dual-lane keycap auth gateway · states: 8 control states
 * axes: light/grotesk-sans/cool · posture: restrained · contrast: pass · v2.0.0 */
/* Hallmark · pre-emit critique: P5 H5 E5 S5 R5 V5 · weakest: contrast on info panel, resolved via OKLCH tokens */
        html, body { overflow-x: clip; }
        :focus-visible { outline: 2px solid var(--color-focus); outline-offset: 2px; }
        :active { transform: translateY(1px); }
        :disabled { opacity: 0.5; cursor: not-allowed; }
    </style>

    <link rel="stylesheet" href="{{ asset('css/login-hallmark.css') }}">
</head>

<body>
    <main class="portal-root">
        <div class="portal-container">
            {{-- Brand Masthead --}}
            <header class="brand-mast">
                <div class="brand-identity">
                    <div class="brand-logo-slot">
                        <img src="{{ asset('images/logo_bps.png') }}"
                             alt="Logo Badan Pusat Statistik"
                             class="brand-logo-img"
                             onerror="this.style.display='none'">
                    </div>
                    <div class="brand-title-group">
                        <span class="brand-agency">BPS Kabupaten Tegal</span>
                        <h1 class="brand-system-name">ELKA</h1>
                    </div>
                </div>
                <div>
                    <span class="brand-tag">Portal Sinkronisasi</span>
                </div>
            </header>

            {{-- Dual-Lane Authentication Stage --}}
            <div class="portal-stage">
                {{-- Bilah Kiri: Opsi 1 - SSO Pegawai & Admin BPS --}}
                <section class="lane-sso" aria-labelledby="sso-heading">
                    <div class="lane-heading-group">
                        <h2 id="sso-heading" class="lane-title">Login SSO BPS</h2>
                        <p class="lane-description">
                            Akses terpusat untuk Pegawai dan Admin operasional BPS Kabupaten Tegal menggunakan akun Single Sign-On resmi.
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: var(--space-md);">
                        <a href="{{ route('auth.sso.login') }}" class="keycap keycap--sso" id="btn-login-sso">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Masuk dengan SSO BPS</span>
                        </a>

                        <div class="notice-vpn">
                            <div class="notice-vpn-head">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>VPN FortiClient Diperlukan</span>
                            </div>
                            <p class="notice-vpn-body">
                                Dari luar kantor BPS, pastikan telah terhubung ke VPN FortiClient BPS sebelum menekan tombol Login SSO.
                            </p>
                        </div>

                        <div class="mock-link-box">
                            <a href="{{ route('auth.sso.mock_login') }}" class="mock-link">
                                Mode Simulasi SSO (Uji Coba Mandiri)
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Bilah Kanan: Opsi 2 - Akun Pengelola Lokal --}}
                <section class="lane-local" aria-labelledby="local-heading">
                    <div class="lane-heading-group">
                        <h2 id="local-heading" class="lane-title">Masuk Akun Pengelola</h2>
                        <p class="lane-description">
                            Khusus akun Super Admin dan pemeliharaan teknis sistem Websinkronisasi.
                        </p>
                    </div>

                    <div>
                        {{-- Notifikasi Galat Sesi --}}
                        @if (session('error'))
                            <div class="auth-alert auth-alert--error" role="alert" style="margin-bottom: var(--space-md);">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="flex-shrink: 0; margin-top: 1px;">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="auth-alert auth-alert--success" role="alert" style="margin-bottom: var(--space-md);">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="flex-shrink: 0; margin-top: 1px;">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        {{-- Formulir Masuk Lokal --}}
                        <form method="POST" action="{{ route('login') }}" class="auth-form">
                            @csrf

                            <div class="form-group">
                                <label for="email" class="form-label">Alamat Email atau NIP</label>
                                <input
                                    id="email"
                                    type="text"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    class="form-input"
                                    placeholder="admin@kantor.go.id">
                                @error('email')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Kata Sandi</label>
                                <div class="input-wrapper">
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        class="form-input"
                                        placeholder="Masukkan kata sandi">

                                    <button
                                        type="button"
                                        id="togglePassword"
                                        aria-label="Tampilkan kata sandi"
                                        class="toggle-password-btn">
                                        <svg id="eyeShow" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.522 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg id="eyeHide" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587A2 2 0 0013.413 13.4M9.88 5.09A9.77 9.77 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.79 9.79 0 01-4.132 5.411M6.228 6.232A9.785 9.785 0 002.458 12c1.274 4.057 5.064 7 9.542 7a9.77 9.77 0 005.147-1.462" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                                <label for="remember" class="form-check-label">Ingat sesi saya di peramban ini</label>
                            </div>

                            <div style="padding-top: var(--space-xs);">
                                <button type="submit" class="keycap keycap--local" id="btn-login-local">
                                    <span>Masuk ke Aplikasi</span>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </form>

                        <div class="seeder-note" style="margin-top: var(--space-md);">
                            <span>Akun Bawaan: <code>admin@kantor.go.id</code> (Sandi: <code>123456789</code>)</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- Portal Footer --}}
        <footer class="portal-footer">
            <span>&copy; {{ date('Y') }} BPS Kabupaten Tegal</span>
            <span>ELKA: Sistem Websinkronisasi Bukti Dukung</span>
        </footer>
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
