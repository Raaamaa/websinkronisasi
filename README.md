# ELKA — Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja

Selamat datang di **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*)!

**ELKA** adalah aplikasi web internal kantor untuk mengumpulkan, memverifikasi, mencari, dan memetakan dokumen **Bukti Dukung** (SK, SOP, laporan kegiatan, dan dokumen dinas lainnya) ke berbagai **Lembar Kerja (LK)** penilaian (ZI, PEKPPP, KIP, SAKIP, Data Sekunder, Data Sektoral) secara terverifikasi dan aman.

---

## 📋 Prasyarat Sebelum Memulai

1. **Docker Desktop / Docker Engine & Docker Compose** (pastikan sudah berstatus *Running*).
2. **Git** (opsional, untuk mengambil pembaruan kode).
3. **Web Browser** modern (Chrome, Edge, Firefox, atau Safari).

---

## 🚀 Panduan Cepat Menjalankan ELKA

### 1. Siapkan Berkas Konfigurasi (`.env`)
```bash
cp .env.example .env
```

### 2. Nyalakan Seluruh Layanan Kontainer (Termasuk `queue-worker`)
```bash
docker compose up -d
```
> [!IMPORTANT]
> **Worker Antrean (`queue-worker`) WAJIB Berjalan:** Layanan `queue-worker` wajib aktif agar **Sinkronisasi Google Drive**, **ekstraksi teks PDF/DOCX**, **OCR Tesseract**, dan **mesin saran pencocokan** dapat diproses di latar belakang. Periksa dengan `docker compose ps` untuk memastikan kontainer `queue-worker` berstatus *Up/Running*.

### 3. Siapkan Basis Data & Data Awal (Khusus Instalasi Baru)
> [!CAUTION]
> **Wajib Backup `pg_dump` Sebelum Migrasi:** Pada server yang sudah memiliki data, setiap eksekusi `php artisan migrate` **wajib didahului** oleh pencadangan database menggunakan `pg_dump`. Jangan pernah menjalankan `migrate:fresh` pada database produksi/dev yang berisi data kerja.

Untuk instalasi lokal baru yang masih kosong:
```bash
docker compose exec app php artisan migrate --seed
```

### 4. Buka Aplikasi di Browser
```text
http://localhost:8000/login
```

---

## ⚙️ Variabel Konfigurasi `.env` Penting

 Seluruh parameter operasional aplikasi dikonfigurasi melalui berkas `.env` (dibaca oleh `config/evidence.php`):

| Variabel `.env` | Nilai Default | Fungsi & Penjelasan |
|---|---|---|
| `EVIDENCE_MAX_FILE_SIZE_MB` | `50` | Batas maksimal ukuran unggah berkas bukti dukung (50 MB). Untuk berkas $> 50\text{ MB}$, gunakan mode tempel tautan (`source_type = 'link'`). |
| `STORAGE_WARNING_THRESHOLD_PERCENT` | `80` | Ambang persentase pemakaian disk server (80%) untuk memunculkan banner peringatan kapasitas penyimpanan bagi Super Admin di Matriks Monitor. |
| `MATCHING_SIMILARITY_THRESHOLD` | `75.0` | Ambang skor minimal (0–100) agar mesin pencocokan memberi saran berstatus `auto`. |
| `MATCHING_REVIEW_THRESHOLD` | `60.0` | Ambang skor tinjauan (60.0–74.99) agar mesin pencocokan memberi saran berstatus `perlu_verifikasi`. |
| `DRIVE_API_KEY` | *(kosong)* | Kunci API Google Drive v3 (hanya baca / *read-only*) untuk fitur Sinkronisasi Folder Google Drive. |
| `DRIVE_SYNC_SCHEDULE_ENABLED` | `false` | Set `true` jika ingin mengaktifkan sinkronisasi Google Drive terjadwal harian via Laravel Scheduler (`evidence:sync-drive`). |

### Cara Mengganti Kunci API Google Drive Pribadi ke Kunci Resmi BPS
Saat beralih dari kunci uji coba pribadi ke kunci API Google Cloud resmi milik BPS:
1. Buka berkas `.env` di server, lalu ganti nilai `DRIVE_API_KEY=` dengan kunci API resmi BPS yang baru.
2. Segarkan konfigurasi dan muat ulang kontainer aplikasi serta worker antrean:
   ```bash
   docker compose exec -T app php artisan config:clear
   docker compose restart app queue-worker
   ```

---

## ⏰ Konfigurasi Cron Host untuk Sinkronisasi Terjadwal

Tanpa perlu mengubah `docker-compose.yml`, Anda dapat menjalankan penjadwal Laravel (*scheduler*) dari `crontab` sistem operasi host (ketika `DRIVE_SYNC_SCHEDULE_ENABLED=true`):

```cron
* * * * * cd /path/ke/websinkronisasi && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

---

## 💾 Prosedur Backup Terjadwal (`pg_dump` & Folder `storage`)

Untuk mencegah kehilangan data, jadwalkan backup rutin ke disk terpisah atau server cadangan lain:

1. **Backup Database PostgreSQL (`pg_dump`):**
   ```bash
   docker compose exec -T postgres pg_dump -U postgres -d websinkronisasi > /lokasi/backup/elka_db_$(date +%F_%H%M).sql
   ```
   *(Selalu jalankan perintah `pg_dump` ini sebelum menjalankan migrasi database apa pun).*
2. **Salin Folder `storage` ke Disk/Server Lain:**
   Salin seluruh isi folder `storage/app/private/evidence/` (tempat berkas fisik bukti dukung disimpan dengan nama UUID) ke media penyimpanan eksternal atau server backup terpisah menggunakan `rsync` / salinan terjadwal:
   ```bash
   rsync -avz storage/app/private/ /lokasi/backup/storage_private/
   ```

---

## 🛠️ Perintah Operasional & Pengujian

| Kebutuhan | Perintah |
|---|---|
| **Menyalakan Semua Kontainer** | `docker compose up -d` |
| **Memeriksa Status Kontainer & Worker** | `docker compose ps` |
| **Menjalankan Sinkronisasi Drive Manual** | `docker compose exec -T app php artisan evidence:sync-drive` |
| **Menjalankan Suite Pengujian Otomatis** | `timeout -k 10s 300s docker compose exec -T app php artisan test` |

Panduan pemakaian sehari-hari untuk Pegawai, Admin, dan Super Admin tersedia di [**`carapenggunaan.md`**](file:///home/hashirama/websinkronisasi/carapenggunaan.md), serta panduan uji manual tersedia di [**`skenario_testing.md`**](file:///home/hashirama/websinkronisasi/skenario_testing.md).
