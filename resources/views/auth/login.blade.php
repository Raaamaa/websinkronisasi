<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ELKA BPS Kabupaten Tegal - Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja Penilaian.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ELKA - BPS Kabupaten Tegal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/login-hallmark.css') }}">
</head>

<body>
    <main class="login-split-root">
        {{-- PANEL KIRI: HERO & BRANDING --}}
        <section class="panel-brand" aria-label="Informasi Sistem ELKA">
            {{-- Brand Header --}}
            <header class="brand-header">
                <img src="{{ asset('images/logo_bps.png') }}"
                     alt="Logo Badan Pusat Statistik"
                     class="brand-logo-img"
                     onerror="this.style.display='none'">
                <div class="brand-meta">
                    <span class="brand-agency-top">Badan Pusat Statistik</span>
                    <span class="brand-agency-sub">Kabupaten Tegal</span>
                </div>
            </header>

            {{-- Main Value Proposition & Features --}}
            <div class="brand-content">
                <div class="brand-title-wrap">
                    <h1 class="brand-system-name">ELKA</h1>
                    <span class="brand-pill-official">Portal Resmi</span>
                </div>

                <p class="brand-description">
                    Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja Penilaian Kantor BPS Seluruh Indonesia.
                </p>

                <div class="features-header">Fitur Sinkronisasi & Penilaian</div>

                <div class="features-list">
                    {{-- Fitur 1 --}}
                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="feature-text-group">
                            <h2 class="feature-title">Integrasi Otomatis Bukti Dukung</h2>
                            <p class="feature-desc">Pemetaan instan dokumen ke lembar kerja evaluasi ZI, PEKPPP, KIP, dan SAKIP.</p>
                        </div>
                    </div>

                    {{-- Fitur 2 --}}
                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="feature-text-group">
                            <h2 class="feature-title">Verifikasi Cerdas Berbasis Algoritma</h2>
                            <p class="feature-desc">Pencocokan heuristik metadata dokumen untuk meminimalkan redundansi data penilaian.</p>
                        </div>
                    </div>

                    {{-- Fitur 3 --}}
                    <div class="feature-item">
                        <div class="feature-icon-box" aria-hidden="true">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="feature-text-group">
                            <h2 class="feature-title">Monitoring Terpusat Tautan Dokumen</h2>
                            <p class="feature-desc">Audit trail validitas tautan cloud drive dan status kelengkapan berkas unit kerja.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Brand Footer: Security hint --}}
            <footer class="brand-footer">
                <div class="security-badge">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Koneksi Aman Terverifikasi &bull; Jaringan Internal BPS</span>
                </div>
            </footer>
        </section>

        {{-- PANEL KANAN: AUTHENTICATION STAGE --}}
        <section class="panel-auth" aria-label="Autentikasi Pengguna">
            <div class="auth-stage-inner">
                {{-- Header --}}
                <header class="auth-header">
                    <h2 class="auth-title">Masuk ke Sistem</h2>
                    <p class="auth-subtitle">Gunakan akun SSO BPS atau kredensial pengelola berwenang.</p>
                </header>

                {{-- Card Container --}}
                <div class="auth-card">
                    {{-- Notifikasi Error/Success --}}
                    @if (session('error'))
                        <div class="auth-alert-box auth-alert-error" role="alert">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="flex-shrink: 0; margin-top: 1px;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="auth-alert-box auth-alert-success" role="alert">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true" style="flex-shrink: 0; margin-top: 1px;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    {{-- Opsi 1: SSO BPS (Primary CTA) --}}
                    <a href="{{ route('auth.sso.login') }}" class="btn-sso" id="btn-login-sso">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Login SSO BPS &rarr;</span>
                    </a>

                    {{-- Notice VPN FortiClient --}}
                    <div class="notice-vpn-card">
                        <svg class="notice-vpn-icon" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="notice-vpn-text">
                            Akses dari luar kantor BPS wajib menyalakan koneksi <strong>VPN FortiClient</strong> BPS sebelum melakukan autentikasi SSO.
                        </p>
                    </div>

                    {{-- Link Simulasi SSO (Khusus Dev/Testing) --}}
                    <a href="{{ route('auth.sso.mock_login') }}" class="mock-sso-link">
                        Mode Simulasi SSO (Uji Coba Mandiri)
                    </a>

                    {{-- Divider --}}
                    <div class="auth-divider">
                        <span>atau masuk dengan Akun Pengelola (Admin / Super Admin)</span>
                    </div>

                    {{-- Formulir Masuk Lokal --}}
                    <form method="POST" action="{{ route('login', [], false) }}" class="auth-form-local">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <span>Email / NIP Pengelola</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-leading-icon" aria-hidden="true">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <input
                                    id="email"
                                    type="text"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    class="form-input-modern"
                                    placeholder="nama@bps.go.id atau 19850101...">
                            </div>
                            @error('email')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="form-label-row">
                                <label for="password" class="form-label">
                                    <span>Kata Sandi</span>
                                    <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="input-icon-wrapper">
                                <span class="input-leading-icon" aria-hidden="true">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
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

                        <div class="form-check-row">
                            <input type="checkbox" name="remember" id="remember" class="form-checkbox">
                            <label for="remember" class="form-check-label">Ingat sesi saya di peramban ini</label>
                        </div>

                        <button type="submit" class="btn-submit-local" id="btn-login-local">
                            <span>Masuk sebagai Pengelola</span>
                        </button>
                    </form>
                </div>

                {{-- Tautan Bantuan --}}
                <div class="auth-helpdesk">
                    Kendala akses akun atau sertifikat VPN?
                    <a href="mailto:bps3328@bps.go.id">Hubungi Helpdesk SISFO BPS</a>
                </div>
            </div>

            {{-- Footer --}}
            <footer class="auth-footer-clean">
                <span>&copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Tegal</span>
                <div class="auth-footer-links">
                    <span>ELKA v1.1</span>
                </div>
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
