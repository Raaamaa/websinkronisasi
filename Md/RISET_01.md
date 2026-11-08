# LAPORAN RISET AUDIT SISTEM WEBSINKRONISASI (RISET_01)

Dokumen ini memuat hasil verifikasi faktual terhadap temuan awal (T1–T12), temuan masalah tambahan (T13–T24), hasil pengujian otomatis, simulasi instalasi clone baru, serta uji kewajaran matching data bahasa Indonesia.

---

## Bagian 1: Verifikasi Temuan Awal (T1–T12)

| ID | Status | Bukti (File:Baris / Output) | Severity |
|---|---|---|---|
| **T1** | TERBUKTI | `docker/app/Dockerfile:36-50` tidak ada `composer install`; `.gitignore:8` abaikan `/vendor`; `.env.example:14` `APP_KEY=base64:PLACEHOLDER_JALANKAN_PHP_ARTISAN_KEY_GENERATE` (31 bytes, bukan 32 bytes AES-256); `README.md:32-58` tidak memuat instruksi composer/key generate. Simulasi clone gagal di Langkah 3 (`Fatal error: Failed opening required '/var/www/html/vendor/autoload.php'`) dan Langkah 5 (`RuntimeException: Unsupported cipher or incorrect key length`). | Fatal |
| **T2** | TERBUKTI | `docker-compose.yml:1-76` hanya ada 3 service (`app`, `postgres`, `redis`), tidak ada service queue worker; `.env.example:43` `QUEUE_CONNECTION=redis`; `app/Services/Evidence/EvidenceUploadService.php:157` `ProcessEvidenceDocumentJob::dispatch()`. Job masuk ke Redis dan tidak pernah diproses karena tidak ada worker berjalan, berkas tertahan status `memproses`. | Fatal |
| **T3** | TERBUKTI | `config/services.php:39` `'driver' => env('SSO_DRIVER', 'mock')`; `app/Services/Auth/Providers/MockSSOProvider.php:28` membaca query `$request->input('mock_user')`; `app/Http/Controllers/Auth/SSOController.php:72,120,150` memproses callback tanpa validasi kredensial/password. Pengguna bisa login langsung via URL `/auth/sso/callback?mock_user=...`. | Tinggi |
| **T4** | TERBUKTI | `database/migrations/2026_11_04_000001_update_lk_period_to_interval_strings.php:22` `UPDATE lembar_kerja SET period = '12 Bulan' WHERE period NOT IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan');`. Berbenturan dengan `uq_lk_code_period` (`database/migrations/2026_10_29_000001_create_lk_and_profiles_tables.php:60`) jika kode LK sama memiliki lebih dari 1 tahun. Baris 24-25 mengizinkan format 4 digit tahun (`OR period ~ '^[0-9]{4}$'`), bertolak belakang dengan update paksa di baris 22. | Fatal |
| **T5** | TERBUKTI | `app/Services/Matching/Rules/DeterministicMatchingRule.php:53` pattern `/(?:^|[\s_\[\(])'.$escapedCode.'(?:[\s_\]\)\.\-]|\b)/i` mencocokkan kode "1" atau "A" ke nama berkas "SK 1 Tahun 2025" atau "Peraturan No 1" (Status confirmed 100%, memotong aturan heuristik). Uji coba runtime regex membuktikan "SK 1 Tahun 2025" dan "Peraturan No 1" langsung MATCH ke kode "1". | Fatal |
| **T6** | TERBUKTI | `app/Services/Matching/Rules/HeuristicMatchingRule.php:48` `is_numeric($lkPeriod) && (int) $lkPeriod > 1900`. Pada periode baru ('12 Bulan', '6 Bulan', '3 Bulan', '1 Bulan'), `is_numeric('12 Bulan')` menghasilkan `false`, sehingga blok targetYear tidak aktif dan penalti tahun (-20.0) tidak pernah berlaku. | Sedang |
| **T7** | TERBUKTI | `app/Jobs/ProcessEvidenceDocumentJob.php:110-115` blok `try { $matchingEngine->matchDocument($document); } catch (Throwable) { }` menelan seluruh exception tanpa log (`Log::error`), tanpa pesan error, dan status dokumen telah diubah menjadi `selesai` di baris 70. | Tinggi |
| **T8** | TERBUKTI | `app/Services/Evidence/EvidenceUploadService.php:91` `EvidenceDocument::where('file_hash', $fileHash)->first()` mengecualikan soft-delete (`whereNull('deleted_at')`), sementara tabel memiliki constraint unik mutlak `uq_documents_file_hash` (`database/migrations/2026_10_29_000002_create_evidence_documents_table.php:49`). File fisik ditulis di baris 123 sebelum DB insert di baris 126, memicu QueryException 500 dan meninggalkan file yatim di disk privat. | Tinggi |
| **T9** | TERBUKTI | `database/seeders/SuperAdminSeeder.php:19` memiliki fallback hardcoded `SuperAdmin2026!`, namun `.env.example:54-55` mendefinisikan `SEEDER_SUPERADMIN_EMAIL=superadmin@kantor.local` dan `SEEDER_SUPERADMIN_PASSWORD=PLACEHOLDER_PASSWORD_AWAL_SUPERADMIN_123!`. Kredensial di `README.md:69-70` (`admin@kantor.go.id` / `SuperAdmin2026!`) tidak cocok pasca copy `.env.example`. Tombol mock SSO Admin mengarahkan ke `admin.sample@bps.go.id`, tetapi `BpsDemoUserSeeder.php` tidak dipanggil di `DatabaseSeeder.php:16-20` sehingga akun dibuat baru dengan role `pegawai` (`SSOController.php:126`). | Fatal |
| **T10** | TERBUKTI | `docker-compose.yml:37-38, 55-56` membuka port host `0.0.0.0:5432` (Postgres) dan `0.0.0.0:6379` (Redis) ke seluruh interface publik. Baris 36 & 54 memakai fallback default `YOUR_STRONG_POSTGRES_PASSWORD_PLACEHOLDER` dan `YOUR_STRONG_REDIS_PASSWORD_PLACEHOLDER`. | Tinggi |
| **T11** | TERBUKTI | `.env.example:13,15` menyetel `APP_ENV=local` dan `APP_DEBUG=true`; `routes/web.php:21` menerima GET pada `/logout` (rentan CSRF logout); `routes/web.php:40-41` mendaftarkan rute pengujian debug `/test-protected-route` dan `/test-admin-only-action`. | Sedang |
| **T12** | TERBUKTI | `app/Services/Evidence/EvidenceUploadService.php:66, 100, 118` mengambil ekstensi langsung dari input klien: `$extension = strtolower($file->getClientOriginalExtension());` dan menyimpan berkas sebagai `{$uuid}.{$inspection['extension']}`, bukan memetakan dari hasil sniffing MIME type `finfo`. | Sedang |

---

## Bagian 2: Temuan Masalah Tambahan (T13–T24)

| ID | Status | Bukti (File:Baris / Output) | Severity | Dampak |
|---|---|---|---|---|
| **T13** | TERBUKTI | `app/Services/Matching/Rules/DeterministicMatchingRule.php:95` `str_contains($cleanTitle, $cleanDocNum)`. Dokumen dengan nomor "1" (misal Peraturan No 1) langsung cocok 100% confirmed ke semua indikator dengan angka 1 pada judul (misal "Indikator P01"). | Fatal | False positive masif: dokumen naskah dinas salah kamar langsung centang hijau di monitor LK tanpa kurasi. |
| **T14** | TERBUKTI | `database/seeders/DatabaseSeeder.php:16-20`, `app/Services/Monitor/MonitorMatrixService.php:29-35`. `InitialLkSeeder` membuat LK periode interval ('12 Bulan') tanpa indikator, sementara seeder demo tidak mengisi data jika LK sudah ada. `getMatrixStats('12 Bulan')` menghasilkan `total_indicators: 0`. | Tinggi | Matriks monitor tampil kosong melompong (0 indikator) saat pertama kali sistem dijalankan sesuai panduan README. |
| **T15** | TERBUKTI | `app/Filament/Resources/LkImportProfileResource/Pages/ListLkImportProfiles.php:279-401`. Method `executeImport()` mengabaikan berkas unggahan dan hanya menyuntikkan 3 data contoh hardcoded (`getPreviewSamples()`), lalu mencatat fake summary (109 indikator, 142 items). Service parser asli (`LkImportService`) tidak pernah dipanggil. | Fatal | Form wizard impor Excel pada antarmuka admin bersifat semu/mock dan tidak memproses berkas Excel pengguna sebenarnya. |
| **T16** | TERBUKTI | `app/Services/Matching/VerificationService.php:16-73`. Method `confirmMatch()` dan `rejectMatch()` berjalan tanpa transaksi database (`DB::transaction`) dan tanpa row locking (`lockForUpdate`). | Sedang | Rentan race condition jika dua admin melakukan verifikasi bersamaan, serta risiko perubahan status tersimpan tanpa audit log jika audit gagal. |
| **T17** | TERBUKTI | `app/Jobs/RematchActiveDocumentsJob.php:42-48`, `app/Services/Matching/MatchingEngineService.php:97-106`. Query `EvidenceDocument::whereNull('deleted_at')->get()` memuat seluruh record beserta kolom `extracted_text` sekaligus ke memori tanpa pagination/chunking, dan tetap mengambil dokumen berstatus `memproses` atau `gagal`. | Sedang | Potensi kehabisan memori PHP (OOM crash) dan worker queue timeout ketika volume dokumen mencapai ribuan berkas. |
| **T18** | TERBUKTI | `app/Services/Monitor/MonitorMatrixService.php:174-176, 213`. Query matches hanya memuat eager load `indicator.lembarKerja`, namun baris 213 mengakses `$firstMatch->matchedItem?->resolved_name`. | Sedang | N+1 query problem pada pemanggilan item bukti dukung di setiap baris matriks monitor saat data dokumen besar. |
| **T19** | TERBUKTI | `app/Filament/Pages/VerificationQueuePage.php:469-490`, `resources/views/filament/pages/verification-queue.blade.php:730`. `highlightKeywords()` membungkus teks dengan `<mark class="...">`. Jika kata kunci berikutnya mengandung substring atribut ("class", "mark", "amber"), regex merusak tag HTML (`<mark <mark class="...">class</mark>...`) yang dicetak unescaped `{!! !!}`. | Sedang | Tampilan visual cuplikan teks di laci inspeksi verifikasi rusak dan berantakan. |
| **T20** | TERBUKTI | `app/Http/Controllers/Evidence/EvidenceController.php:50-55`. Saat terjadi duplikasi hash berkas, controller mengembalikan objek `$e->existingDocument` secara utuh ke respons JSON HTTP 409. | Sedang | Kebocoran informasi internal: path penyimpanan lokal di server (`file_path`) dan UUID pengunggah terekspos ke pengguna lain. |
| **T21** | TERBUKTI | `app/Services/LkImport/ExcelParserService.php:30-54`. Validasi berkas hanya memeriksa ekstensi `.xlsm` dan satu MIME type. Tidak membatasi ukuran maksimal berkas dan tidak memeriksa ekstensi makro alternatif (`.xltm`, `.xlam`). | Sedang | Risiko DoS konsumsi CPU dan memori berlebih saat PhpSpreadsheet mem-parsing berkas spreadsheet berukuran raksasa. |
| **T22** | TERBUKTI | `deploy/nginx/websinkronisasi.conf:63` mengarahkan `fastcgi_pass app:9000;`, sedangkan `docker/app/Dockerfile:1,50` menggunakan `php:8.3-cli-alpine` yang menjalankan server internal `php artisan serve` pada port 8000 (tidak ada PHP-FPM). | Sedang | Konfigurasi deploy Nginx bawaan tidak dapat berjalan dengan image Docker app bawaan (menghasilkan 502 Bad Gateway). |
| **T23** | TERBUKTI | Direktori `app/Policies` tidak ada di repositori; `app/Filament/Resources/UserResource.php` tidak mendefinisikan method `canEdit()` dan `canDelete()`. | Rendah | Tidak ada lapisan proteksi Laravel Policy terpusat untuk operasi otorisasi model Filament. |
| **T24** | TERBUKTI | `tests/TestCase.php:1-10` tidak menyertakan trait `RefreshDatabase` atau `DatabaseTransactions`. Pengujian feature melakukan mutasi langsung pada database PostgreSQL aktif (`User::withTrashed()->firstOrCreate(...)`). | Sedang | Eksekusi test mencemari database pengembangan/lokal dengan record dummy dan data residual. |

---

## Bagian 3: Hasil Eksekusi Test Otomatis & Simulasi Clone Baru

### 3.1 Hasil Uji Otomatis (`php artisan test`)
* **Total Pengujian:** 159 tests
* **Total Asersi:** 605 assertions
* **Status:** 159 lulus (PASS), 0 gagal (FAIL)
* **Durasi:** 17.45 detik
* **Catatan:** Pengujian otomatis lulus 100% karena `phpunit.xml` menyetel `QUEUE_CONNECTION=sync` dan `CACHE_STORE=array`, sehingga kecacatan arsitektur seperti ketiadaan worker antrean Redis (T2) terisolasi dan tidak terdeteksi oleh suite test bawaan.

### 3.2 Hasil Simulasi Clone Bersih (Tanpa `vendor/` & Tanpa `.env`)
Pengujian dilakukan dengan menyalin repositori ke lingkungan terisolasi tanpa direktori `vendor/` dan tanpa `.env`, lalu mengikuti 5 langkah pada `README.md`:
1. **Langkah 1 & 2 (`cp .env.example .env`):** Berhasil disalin. Namun `.env.example` membawa nilai placeholder tidak valid pada `APP_KEY` dan kredensial default yang tidak sinkron.
2. **Langkah 3 (`docker compose up -d`):** **GAGAL**. Container `websinkronisasi_app` langsung crash berulang kali (*restarting loop*) dengan pesan:
   ```text
   Fatal error: Uncaught Error: Failed opening required '/var/www/html/vendor/autoload.php' in /var/www/html/artisan:9
   ```
   Penyebab: `Dockerfile` tidak mengeksekusi `composer install`, `.gitignore` mengecualikan `vendor/`, dan `README.md` tidak menginstruksikan instalasi composer.
3. **Langkah 4 (`php artisan migrate:fresh --seed`):** Jika `vendor/` dipasang manual namun `php artisan key:generate` tidak dipanggil, proses HTTP/Web pada Langkah 5 **GAGAL FATAL** saat mengakses halaman `/admin` dengan error:
   ```text
   RuntimeException: Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.
   at vendor/laravel/framework/src/Illuminate/Encryption/Encrypter.php:62
   ```

---

## Bagian 4: Uji Kewajaran Matching Data Bahasa Indonesia Realistis

Uji coba dilakukan menggunakan 10 contoh dokumen kedinasan riil berbahasa Indonesia terhadap indikator dan butir bukti standar:

| No | Nama Dokumen Pengujian | Nomor Dokumen | Target Harapan | Hasil Mesin Matching | Skor & Status | Kategori Hasil |
|---|---|---|---|---|---|---|
| 1 | SK Kepala BPS tentang Pembentukan Tim Kerja Pembangunan ZI Tahun 2026 | B-105/3328/KP.010/2026 | ZI-1.1 | ZI-1.1 | Skor 78.1 (auto) | **True Positive** |
| 2 | SOP Pelayanan Statistik Terpadu BPS Tahun 2026 | SOP-01/2026 | PEKPPP-01 | Tidak ada kecocokan | Skor 73.85 (< 75.0) | **False Negative** (gagal tembus threshold 75%) |
| 3 | Laporan Kinerja Instansi Pemerintah LKjIP BPS Tahun 2026 | LKJIP-2026 | SAKIP-01 | SAKIP-01 | Skor 93.1 (auto) | **True Positive** |
| 4 | SK Penetapan Pengelola Informasi dan Dokumentasi PPID Tahun 2026 | SK-PPID-01 | KIP-01 | Tidak ada kecocokan | Skor 69.7 (< 75.0) | **False Negative** (gagal tembus threshold 75%) |
| 5 | Notulen Rapat Pembahasan Rencana Aksi Zona Integritas 2026 | NOT-05/2026 | ZI-1.2 | ZI-1.2 | Skor 79.5 (auto) | **True Positive** |
| 6 | Laporan Kegiatan Pembinaan Statistik Sektoral Triwulan II 2026 | LAP-SEC-02 | (Tidak ada) | Tidak ada kecocokan | Skor < 50.0 | **True Negative** |
| 7 | Peraturan Kepala BPS No 1 Tahun 2024 tentang Tata Naskah Dinas | 1 | (Tidak ada) | Indikator "1" (LKE) / Indikator P01 | Skor 100.0 (confirmed) | **False Positive Ekstrem** (nomor "1" memicu exact match ke indikator "1") |
| 8 | Notula Rapat Evaluasi Anggaran Triwulan I Tahun 2025 | NOT-01/2025 | (Tidak ada) | Tidak ada kecocokan | Skor < 50.0 | **True Negative** |
| 9 | Daftar Informasi Publik BPS Tahun 2026 | DIP-2026 | KIP-01 | Tidak ada kecocokan | Skor 64.37 (< 75.0) | **False Negative** (kemiripan frase tidak capai 75%) |
| 10 | Surat Keputusan Tim ZI-1.1 WBK Tahun 2026 | SK-ZI-01 | ZI-1.1 | ZI-1.1 | Skor 100.0 (confirmed) | **True Positive** (exact code match) |

---

## Bagian 5: Daftar Area yang Tidak Sempat Diperiksa Mendalam

1. **Integrasi Nyata SSO Keycloak BPS:** Pengujian koneksi langsung ke endpoint IdP Keycloak eksternal (`https://sso.bps.go.id`) dan mekanisme token exchange melalui FortiClient VPN live (hanya diuji via mock provider dan unit test mocking).
2. **Kinerja Ekstraksi Tesseract OCR pada File Pindaian Fisik Gambar Besar:** Evaluasi performa CPU dan konsumsi memori saat Tesseract v5 memproses berkas PDF scan bitmap murni resolusi tinggi (> 300 DPI multi-halaman).
3. **Kompatibilitas Format Spreadsheet XLS Legacy (Excel 97–2003):** Pengujian perilaku PhpSpreadsheet saat membaca berkas biner `.xls` lawas dengan pemetaan karakter non-UTF8.
4. **Stress Test Beban Paralel 50+ Pengguna Bersamaan:** Pengujian konkurensi skala tinggi pada antrean Redis worker dan matriks monitor di bawah beban multi-user instansi secara simultan.

