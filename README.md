# ELKA — Perpustakaan Bukti Dukung BPS Kabupaten Tegal

Selamat datang di **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*) **BPS Kabupaten Tegal**!

**ELKA** adalah aplikasi web internal kantor berbentuk **Perpustakaan Bukti Dukung**:
* **6 Rak Lembar Kerja (LK):** `KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, dan `DATA_SEKUNDER` (disiapkan melalui `InitialLkSeeder`).
* **Dokumen Bukti Dukung (Buku):** Surat Keputusan (SK), SOP, laporan kegiatan, surat edaran, dan data statistik kantor yang dikumpulkan, diperiksa Admin, lalu ditata ke satu atau beberapa rak LK secara resmi.

---

## 📋 Prasyarat Sebelum Memulai

1. **Docker Desktop / Docker Engine & Docker Compose** (pastikan berstatus *Running*).
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
> **Worker Antrean (`queue-worker`) Tetap Diperlukan:** Layanan `queue-worker` wajib aktif agar proses **ekstraksi teks PDF/DOCX dan OCR Tesseract** (`ProcessEvidenceDocumentJob`) berjalan di latar belakang sehingga isi dokumen unggahan dapat ditemukan melalui pencarian teks lengkap dan deteksi kemiripan dokumen. Periksa dengan `docker compose ps` untuk memastikan kontainer `queue-worker` berstatus *Up/Running*.

### 3. Siapkan Basis Data & Data Awal (Khusus Instalasi Baru)
> [!CAUTION]
> **Wajib Backup `pg_dump` Sebelum Migrasi:** Pada server yang sudah memiliki data, setiap eksekusi `php artisan migrate` **wajib didahului** pencadangan database menggunakan `pg_dump`. Jangan pernah menjalankan `migrate:fresh` pada database produksi/dev yang berisi data kerja.

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
| `EVIDENCE_MAX_FILE_SIZE_MB` | `20` | Batas maksimal ukuran unggah berkas fisik bukti dukung (**20 MB**). Untuk berkas berukuran $> 20\text{ MB}$ (misalnya video), unggah lebih dulu ke Google Drive pribadi/kantor lalu kirim tautannya via mode **Tempel Tautan** (`source_type = 'link'`). Server ELKA tidak terhubung ke API Google Drive. |
| `STORAGE_WARNING_THRESHOLD_PERCENT` | `80` | Ambang persentase pemakaian disk server (**80%**) untuk menampilkan **Banner Peringatan Kapasitas Penyimpanan** khusus kepada **Super Admin** di halaman **Antrean Verifikasi** (`/admin/verification-queue`). Admin dan Pegawai tidak melihat banner ini. |

---

## 💾 Prosedur Backup Terjadwal (`pg_dump` & Folder `storage`)

Untuk mencegah kehilangan data, jadwalkan pencadangan rutin ke disk terpisah atau server cadangan lain (misalnya via `cron` sistem operasi host):

1. **Backup Database PostgreSQL Terjadwal (`pg_dump`):**
   ```bash
   docker compose exec -T postgres pg_dump -U postgres -d websinkronisasi > /lokasi/backup/elka_db_$(date +%F_%H%M).sql
   ```
   *(Selalu jalankan `pg_dump` sebelum menjalankan migrasi atau pemeliharaan database).*
2. **Salinan Terjadwal Folder `storage`:**
   Salin seluruh isi folder `storage/app/private/` (tempat berkas fisik bukti dukung disimpan dengan nama acak UUID di `storage/app/private/evidence/`) ke media penyimpanan eksternal atau server cadangan terpisah:
   ```bash
   rsync -avz storage/app/private/ /lokasi/backup/storage_private/
   ```
3. **Pemantauan Kapasitas Disk:**
   Jika pemakaian disk penyimpanan mendekati atau melewati `STORAGE_WARNING_THRESHOLD_PERCENT` (`80%`), Super Admin akan melihat banner peringatan berwarna merah di bagian atas halaman **Antrean Verifikasi**.

---

## 🛠️ Perintah Operasional & Pengujian

| Kebutuhan | Perintah |
|---|---|
| **Menyalakan Semua Kontainer** | `docker compose up -d` |
| **Memeriksa Status Kontainer & Worker** | `docker compose ps` |
| **Menjalankan Suite Pengujian Otomatis** | `timeout -k 10s 300s docker compose exec -T app php artisan test` |

Panduan pemakaian sehari-hari untuk Pegawai, Admin, dan Super Admin tersedia di [**`carapenggunaan.md`**](carapenggunaan.md), serta panduan uji manual tersedia di [**`skenario_testing.md`**](skenario_testing.md).
