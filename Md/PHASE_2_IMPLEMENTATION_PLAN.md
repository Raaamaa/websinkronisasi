# Rencana Implementasi Teknis — Fase 2
## Autentikasi SSO BPS, Mock Provider, Karantina Kata Sandi, & Pipeline Middleware RBAC

| Parameter | Spesifikasi |
|---|---|
| **Fase Proyek** | Fase 2 dari 8 (Sesuai `Md/TASKS.md` & `Md/RBAC.md`) |
| **Status Verifikasi Eksternal** | Terverifikasi Konkret: Identity Provider BPS berbasis **Red Hat Keycloak** |
| **Protokol Resmi** | OpenID Connect (OIDC) 1.0 & OAuth 2.0 |
| **Prinsip Utama** | *Server-Side Strict Enforcement* (NFR-01), *Least Privilege*, *Zero Fatal Mistake* |
| **Metodologi Pengujian** | Test-Driven Development (TDD) via PHPUnit 11 |

---

## 1. Bukti Konkret & Verifikasi Endpoint SSO BPS

Berdasarkan pengujian teknis langsung dan inspeksi endpoint OIDC terhadap host `https://sso.bps.go.id/`, berikut adalah fakta konkret yang terkonfirmasi:

1. **Platform Identity Provider:** Host `sso.bps.go.id` menggunakan **Red Hat Keycloak** (dilindungi WAF F5 BIG-IP dan Cloudflare).
2. **Realm Resmi Pegawai:** Realm yang aktif untuk pegawai BPS adalah `pegawai-bps`.
3. **Dokumen Konfigurasi Standar OIDC:** Dapat diakses secara publik pada URL:
   ```
   https://sso.bps.go.id/auth/realms/pegawai-bps/.well-known/openid-configuration
   ```
4. **Daftar Endpoint Riil BPS:**
   * **Issuer:** `https://sso.bps.go.id/auth/realms/pegawai-bps`
   * **Authorization Endpoint:** `https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/auth`
   * **Token Endpoint:** `https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/token`
   * **Userinfo Endpoint:** `https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/userinfo`
   * **End Session / Logout Endpoint:** `https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/logout`
   * **JWKS Certificates:** `https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/certs`
5. **Pemetaan Atribut (Claims) Pengguna BPS:**
   * `preferred_username` ➔ NIP Pegawai / Username BPS (18 digit NIP).
   * `name` ➔ Nama Lengkap Pegawai.
   * `email` ➔ Email Kedinasan BPS (`@bps.go.id`).
   * `sub` ➔ UUID unik Subject ID SSO (`auth_provider_id`).

---

## 2. Arsitektur Komponen Fase 2

```mermaid
graph TD
    subgraph Browser / Klien
        REQ[Permintaan Pengguna / Request]
    end

    subgraph Pipeline Middleware Keamanan Server-Side (NFR-01)
        M1[EnsureAccountIsActive<br/>Cek is_active & deleted_at di DB per request]
        M2[EnsurePasswordChanged<br/>Cek must_change_password untuk Super Admin dummy]
        M3[AuthorizePermission<br/>Cek matriks izin granular RBAC.md]
    end

    subgraph Autentikasi & Penyedia SSO
        SSO_CTRL[SSOController<br/>/auth/sso/login & callback]
        CONTRACT[SSOProviderInterface]
        MOCK[MockSSOProvider<br/>Lokal / Dev / Unit Test]
        KEYCLOAK[BpsKeycloakOidcProvider<br/>OIDC Terhubung sso.bps.go.id]
    end

    subgraph Karantina & Sesi
        FORCE_PWD[ForceChangePasswordController<br/>Form Ganti Sandi Pertama Kali]
        REVOKE[InstantSessionRevoker<br/>Putus sesi seketika saat nonaktif]
    end

    REQ --> M1
    M1 -->|Nonaktif / Dihapus| REJECT403[Tolak HTTP 403 & Hancurkan Sesi]
    M1 -->|Aktif| M2
    M2 -->|must_change_password=true| FORCE_PWD
    M2 -->|must_change_password=false| M3
    M3 -->|Tidak Berhak| FORBIDDEN[HTTP 403 Forbidden]
    M3 -->|Berhak| APP_CONTROLLER[Akses Menu / Filament Panel]

    SSO_CTRL --> CONTRACT
    CONTRACT -.-> MOCK
    CONTRACT -.-> KEYCLOAK
```

---

## 3. Rincian Tugas Teknis (Work Breakdown Structure)

### TSK-201: Abstraksi SSO Driver & DTO Pengguna (NFR-16, AUTH-01)
* **File Target:**
  - `app/Services/Auth/Contracts/SSOProviderInterface.php`
  - `app/Services/Auth/DTOs/SSOUserData.php`
  - `app/Services/Auth/Providers/MockSSOProvider.php`
  - `app/Services/Auth/Providers/BpsKeycloakOidcProvider.php`
* **Deskripsi:**
  - `SSOProviderInterface` memiliki method:
    - `getRedirectResponse(): Symfony\Component\HttpFoundation\Response`
    - `handleCallback(Illuminate\Http\Request $request): SSOUserData`
  - `SSOUserData` memegang nilai `id`, `email`, `name`, `nip`, `rawAttributes`.
  - `MockSSOProvider` menyediakan tombol/pilihan instan untuk login lokal sebagai "Pegawai Contoh" atau "Admin Contoh" tanpa perlu koneksi internet atau izin client ID Keycloak internal saat pengembangan.
  - `BpsKeycloakOidcProvider` mengimplementasikan pertukaran kode otorisasi (*Authorization Code Flow*) menggunakan Guzzle HTTP client ke endpoint `sso.bps.go.id` yang telah dibuktikan di atas.
  - Daftarkan binding di `app/Providers/AppServiceProvider.php` berdasarkan nilai `config('services.sso.driver')` (`mock` atau `oidc`).

---

### TSK-202: SSO Controller & Idempotent Auto-Provisioning (AUTH-02, AUTH-10, AC-18, AC-21)
* **File Target:**
  - `app/Http/Controllers/Auth/SSOController.php`
  - `routes/web.php`
* **Deskripsi Logika Bisnis:**
  1. Route `GET /auth/sso/login` memanggil `SSOProviderInterface::getRedirectResponse()`.
  2. Route `GET /auth/sso/callback` menerima redirect callback:
     - Ambil `SSOUserData` dari provider.
     - Cek apakah email atau NIP sudah ada di tabel `users`.
     - **Kasus Akun Dinonaktifkan (AUTH-07, AC-18):** Jika akun ditemukan tetapi `is_active === false`, **tolak pembentukan sesi**, catat event di `audit_logs` (`auth.sso_login_blocked_inactive`), dan return response HTTP 403 dengan pesan informatif: *"Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator"*.
     - **Kasus Akun Baru (AUTH-10, AC-21):** Jika belum ada, lakukan *auto-provisioning*:
       - `name`: `$ssoUser->name`
       - `email`: `$ssoUser->email`
       - `nip`: `$ssoUser->nip`
       - `role`: `'pegawai'` (peran default pembaca mutlak)
       - `is_active`: `true`
       - `auth_provider`: `'sso'`
       - `auth_provider_id`: `$ssoUser->id`
       - `password_hash`: `null` (tidak ada sandi lokal)
       - `must_change_password`: `false`
     - Catat login ke tabel `audit_logs` (`auth.sso_login_success`).
     - Jalankan `Auth::login($user)`.
     - Arahkan ke halaman utama/dasbor.

---

### TSK-203: Karantina & Alur Ganti Kata Sandi Dummy Super Admin (AUTH-03, AUTH-04, D6, AC-03)
* **File Target:**
  - `app/Http/Controllers/Auth/ForceChangePasswordController.php`
  - `resources/views/auth/force-change-password.blade.php`
  - `routes/web.php`
* **Deskripsi Logika Bisnis:**
  1. Jika akun Super Admin lokal memiliki `must_change_password === true`, pengguna dikarantina.
  2. Semua akses URL dialihkan ke rute `/auth/password/change` (kecuali proses submit ganti sandi dan logout).
  3. Form meminta kata sandi baru dan konfirmasi kata sandi (minimal 8 karakter, kombinasi huruf dan angka).
  4. Kata sandi lama tidak boleh sama dengan kata sandi baru.
  5. Setelah valid, simpan `password_hash = Hash::make($newPassword)`, set `must_change_password = false`.
  6. Catat audit log: `action = 'auth.password_changed'`.
  7. Redirect ke `/admin` dengan notifikasi sukses.

---

### TSK-204: Pipeline Middleware Keamanan Server-Side (NFR-01, RBAC.md Bab 6)
* **File Target:**
  - `app/Http/Middleware/EnsureAccountIsActive.php`
  - `app/Http/Middleware/EnsurePasswordChanged.php`
  - `app/Http/Middleware/AuthorizePermission.php`
  - `bootstrap/app.php` (Pendaftaran middleware global & route middleware di Laravel 11)
* **Deskripsi Logika Bisnis:**
  1. **`EnsureAccountIsActive` (AUTH-07, AC-15, AC-18):**
     - Dijalankan pada setiap request yang terautentikasi.
     - Cek apakah `$request->user()->is_active` bernilai `true` dan `deleted_at === null`.
     - Jika tidak aktif: jalankan `Auth::logout()`, `$request->session()->invalidate()`, regenerasi token CSRF, dan kirim respon `abort(403, 'Akun dinonaktifkan')`.
  2. **`EnsurePasswordChanged` (AUTH-04, AC-03):**
     - Jika `$request->user()->must_change_password === true` dan rute bukan rute ganti sandi/logout, lempar ke rute `/auth/password/change`.
  3. **`AuthorizePermission` (NFR-01, AC-01):**
     - Menerima parameter permission string (contoh: `user.change_role`, `evidence.upload`, dll.).
     - Memeriksa hak akses melalui `$request->user()->hasPermission($permission)`.
     - Jika gagal: `abort(403, 'Anda tidak memiliki wewenang untuk melakukan aksi ini.')`.

---

### TSK-205: Pengelolaan Akun & Guard Rails di Panel Admin (AUTH-05, AUTH-06, AUTH-08, D5, AC-04, AC-19, AC-20)
* **File Target:**
  - `app/Filament/Resources/UserResource.php`
  - `app/Filament/Resources/UserResource/Pages/ListUsers.php`
  - `app/Filament/Resources/UserResource/Pages/EditUser.php`
* **Deskripsi Aturan Bisnis Server-Side:**
  1. **Super Admin:**
     - Berwenang mengubah status `is_active` seluruh akun.
     - Berwenang mengubah role (`pegawai` ↔ `admin`).
     - **Proteksi Super Admin Terakhir (AC-04):** Ditolak di level validasi PHP dan trigger database jika mencoba menonaktifkan atau menurunkan role dari satu-satunya Super Admin aktif.
     - **Proteksi Kunci Diri:** Dilarang menonaktifkan akun miliknya sendiri yang sedang login.
  2. **Admin (AC-19, AC-20):**
     - HANYA diizinkan mengaktifkan atau menonaktifkan akun dengan peran `pegawai`.
     - Ditolak (HTTP 403) jika mencoba menonaktifkan akun sesama `admin` atau `super_admin`.
     - Ditolak jika mencoba mengubah role akun apapun.
  3. **Pegawai (AC-01):**
     - Menu manajemen pengguna disembunyikan dan endpoint rute dilindungi middleware (HTTP 403 Forbidden).

---

### TSK-206: Pemutusan Sesi Seketika / Instant Session Revocation (AUTH-07, AC-15)
* **File Target:**
  - `app/Services/Auth/SessionManagerService.php`
* **Deskripsi:**
  - Ketika Admin/Super Admin menonaktifkan pengguna (`is_active = false`), sistem secara otomatis menghapus record session ID pengguna dari penyimpanan sesi Redis/Database.
  - Ditambah dengan `EnsureAccountIsActive` pada layer middleware, pengguna yang sedang membuka aplikasi akan langsung ditolak pada klik tombol berikutnya tanpa menunggu session expired.

---

## 4. Rencana Pengujian Otomatis (TDD Test Suite)

Pengujian akan disusun pada file `tests/Feature/Phase2RbacAndAuthTest.php` yang mencakup 8 skenario pengujian:

1. `test_pegawai_cannot_access_restricted_admin_endpoints_returns_403()` (AC-01)
2. `test_dummy_super_admin_with_must_change_password_is_quarantined()` (AC-03)
3. `test_dummy_super_admin_can_successfully_change_password_and_gain_full_access()` (AC-03)
4. `test_system_strictly_prevents_deactivating_last_active_super_admin()` (AC-04)
5. `test_inactive_account_logging_in_via_sso_is_rejected_without_session()` (AC-18)
6. `test_active_user_deactivated_by_admin_loses_session_on_subsequent_request()` (AC-15)
7. `test_admin_can_only_toggle_pegawai_and_is_blocked_from_mutating_admin_or_super_admin()` (AC-19, AC-20)
8. `test_first_time_sso_login_auto_provisions_pegawai_account_and_logs_audit()` (AC-21)

---

## 5. Gerbang Verifikasi (Verification Gate 2)

Fase 2 dinyatakan **Selesai & Lulus** jika:
* [ ] Seluruh pengujian di `tests/Feature/Phase2RbacAndAuthTest.php` berjalan **PASS 100%**.
* [ ] Verifikasi browser: Super Admin login pertama kali diarahkan ke halaman ganti sandi dan berhasil mengganti sandi.
* [ ] Verifikasi browser: Login SSO Mock berhasil mendeteksi peran Pegawai dan Admin.
* [ ] Verifikasi database: Tabel `audit_logs` mencatat event login, perubahan sandi, dan perubahan status akun.

