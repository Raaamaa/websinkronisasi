# Rencana Implementasi Teknis — Fase 8
## Validasi Menyeluruh Kriteria Penerimaan (AC-01 s.d. AC-27) & Kesiapan Produksi

| Parameter | Spesifikasi |
|---|---|
| **Fase Proyek** | Fase 8 dari 8 (Sesuai `Md/TASKS.md`, `Md/PRD.md`, `Md/DATABASE.md`, `Md/ARCHITECTURE.md`) |
| **Kriteria Penerimaan Terkait** | **AC-01 s.d. AC-27** (Validasi menyeluruh 100% 27 Kriteria Penerimaan) |
| **Kebutuhan Non-Fungsional** | **NFR-03** (SSL/TLS intranet), **NFR-05** (Waktu respons monitor <= 2 detik untuk 10.000 dokumen), **NFR-06 & NFR-07** (Supervisor/Systemd queue worker dengan auto-restart), **NFR-08** (Backup harian database & file fisik) |
| **Metodologi Pengujian** | Test-Driven Development (TDD) via PHPUnit 11 & Benchmark Script |
| **Skill Terkait** | `/tdd`, `/ponytail`, `/code-review`, `/antislop-ui` |

---

## 1. Arsitektur Verifikasi & Kesiapan Rilis

```mermaid
graph TD
    subgraph Pengujian Akseptansi Menyeluruh (TSK-801)
        E2E[Phase8AcceptanceCriteriaValidationTest<br/>AC-01 s.d. AC-27 Explicit Suite]
        PERF[Benchmark Matriks 10.000 Dokumen<br/>Target: Waktu respons <= 2.0s NFR-05]
    end

    subgraph Hardening Server & Lingkungan Produksi (TSK-802, TSK-803, TSK-804)
        NGINX[Konfigurasi Nginx Reverse Proxy<br/>HTTP/2, SSL Intranet, Security Headers TSK-802]
        SYSTEMD[Konfigurasi Systemd / Supervisor<br/>Worker Redis Queue Auto-Restart TSK-803]
        BACKUP[Skrip Otomatisasi Backup<br/>pg_dump PostgreSQL & Sync Berkas Bukti TSK-804]
    end

    E2E -->|27/27 PASSED| GATE[Gerbang Verifikasi Fase 8: READY FOR PRODUCTION]
    PERF -->|Latency <= 2.0s| GATE
    NGINX --> GATE
    SYSTEMD --> GATE
    BACKUP --> GATE
```

---

## 2. Kesepakatan Titik Uji (*Seams Agreement*) — Metodologi `/tdd`

Sesuai aturan skill **`/tdd`**, pengujian hanya dilakukan pada batas publik (*seams*) yang telah disepakati:

| No | Batas Publik (*Seam*) | Apa yang Ditangkap (*Catches*) | Apa yang Dilewati (*Misses*) |
|---|---|---|---|
| **Seam 1** | **HTTP Endpoints & Filament Policy Gates (AC-01 s.d. AC-04, AC-18, AC-19)** | Memastikan otorisasi sisi server mutlak: penolakan Pegawai (403), isolasi akun nonaktif, proteksi paksa ganti sandi Super Admin seeder, dan perlindungan Super Admin terakhir dari mutasi. | Tidak memeriksa tampilan visual CSS/DOM browser secara mendalam. |
| **Seam 2** | **Evidence Upload & Ingestion Engine (AC-05 s.d. AC-07)** | Memastikan validasi MIME magic bytes (.exe diganti .pdf ditolak), deduplikasi hash SHA-256 (file kembar ditolak), dan fallback OCR ke status `perlu_review_manual` saat teks nihil. | Tidak memeriksa antarmuka interaktif drag-and-drop di browser fisik. |
| **Seam 3** | **Monitor Matrix Rendering & Verification (AC-08 s.d. AC-12, AC-16)** | Memastikan centang (✔) hanya tampil pada LK yang cocok, sel kosong murni (tanpa karakter) pada LK tanpa match, modal detail aman (link http/https only), penolakan admin kekal (AC-11), dan pencegahan race condition penambahan match konkuren. | Tidak menguji kecepatan render grafis browser klien di sisi hardware. |
| **Seam 4** | **Excel Import & Placeholder Resolver (AC-22 s.d. AC-27)** | Memastikan ekstraksi riil format ZI (139 butir), PEKPPP (31 indikator), KIP (59 butir), idempoten tanpa duplikasi saat diimpor ulang, sanitasi skema `javascript:`, dan resolusi dinamis `[t-1]`. | Tidak memeriksa visual styling cell formatting Excel (warna cell, font family). |
| **Seam 5** | **Audit Trail Immutability & Audit Coverage (AC-17)** | Memastikan setiap aksi AC-05 s.d. AC-16 terekam otomatis di `audit_logs` dan trigger anti-tampering PostgreSQL memblokir UPDATE/DELETE. | Tidak menguji audit log di luar mutasi bisnis yang ditentukan. |
| **Seam 6** | **Monitor Query Benchmark (NFR-05 Gate)** | Menguji latensi query matriks monitor pada skala 10.000 data dokumen bukti dukung sintesis untuk membuktikan waktu respons server <= 2.0 detik. | Tidak mensimulasikan latensi koneksi internet publik ISP pengguna. |

---

## 3. Rincian Tugas Kerja (Work Breakdown Structure)

### TSK-801: Suite Pengujian Otomatis AC-01 s.d. AC-27 & Performance Benchmark
* **Target File:**
  - `tests/Feature/Phase8AcceptanceCriteriaValidationTest.php`
* **Cakupan Tes:**
  - Membangun suite komprehensif yang menguji seluruh butir **AC-01 sampai AC-27** secara end-to-end dengan penamaan method yang eksplisit dan jelas (`test_ac_01_...` hingga `test_ac_27_...`).
  - Menjalankan uji benchmark query komposit matriks monitor terhadap **10.000 dokumen** untuk membuktikan kepatuhan terhadap ambang batas <= 2 detik (**NFR-05**).

---

### TSK-802: Konfigurasi Web Server Nginx & HTTPS Intranet (NFR-03, NFR-15, D8)
* **Target File:**
  - `deploy/nginx/websinkronisasi.conf`
* **Spesifikasi Konfigurasi:**
  - Protokol modern: HTTP/2 dengan SSL/TLS termination.
  - Header Keamanan Ketat:
    - `X-Frame-Options: SAMEORIGIN`
    - `X-Content-Type-Options: nosniff`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
    - `Content-Security-Policy` kompatibel dengan Livewire 3 dan Filament v3.
  - Batas Ukuran Unggah: `client_max_body_size 25M` (mendukung batas BD-02 20MB).
  - Isolasi Berkas Privat: Blokir akses langsung via web ke direktori penyimpanan fisik privat server.

---

### TSK-803: Konfigurasi Systemd Supervisor Daemon (NFR-06, NFR-07)
* **Target File:**
  - `deploy/systemd/websinkronisasi-worker.service`
  - `deploy/supervisor/websinkronisasi-worker.conf`
* **Spesifikasi Konfigurasi:**
  - Menjalankan perintah Laravel queue worker:
    `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90 --max-jobs=1000 --max-time=3600`
  - Auto-restart jika worker mengalami crash (`Restart=always`, `RestartSec=5s`).
  - Konfigurasi graceful stop saat shutdown sistem (`KillSignal=SIGTERM`, `TimeoutStopSec=30`).

---

### TSK-804: Prosedur Backup Terjadwal (NFR-08)
* **Target File:**
  - `deploy/backup/backup.sh`
  - `deploy/backup/crontab.example`
* **Spesifikasi Prosedur:**
  - Dump database PostgreSQL: `pg_dump` dengan kompresi gzip.
  - Sinkronisasi direktori berkas bukti dukung privat (`storage/app/private`).
  - Pembersihan otomatis arsip cadangan yang lebih tua dari 30 hari (*30-day retention policy*).
  - Logging status keberhasilan atau kegagalan cadangan.

---

## 4. Kriteria Gerbang Verifikasi (*Definition of Done*)
1. Seluruh 27 Kriteria Penerimaan (**AC-01 s.d. AC-27**) lolos pengujian otomatis dengan status **PASSED**.
2. Benchmark kueri matriks monitor membuktikan latensi respons server **<= 2.0 detik** pada beban 10.000 dokumen.
3. Berkas konfigurasi deployment produksi (`deploy/nginx/`, `deploy/systemd/`, `deploy/backup/`) siap pakai dan terdokumentasi lengkap.
4. Laporan akhir kesiapan rilis disajikan secara jelas kepada pengguna.

