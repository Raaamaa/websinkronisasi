# ELKA — Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja

Selamat datang di **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*)! 

**ELKA** adalah aplikasi web resmi internal kantor yang dirancang untuk memetakan dan menyinkronkan berkas **Bukti Dukung** (seperti Surat Keputusan/SK, Standar Operasional Prosedur/SOP, laporan kegiatan, dan dokumen dinas lainnya) ke indikator penilaian pada berbagai **Lembar Kerja (LK)** kantor (seperti Zona Integritas/ZI, PEKPPP, KIP, SAKIP, dan lainnya) secara otomatis, akurat, dan transparan.

Panduan ini disusun dengan bahasa yang mudah dipahami bagi siapa saja, termasuk bagi Anda yang bukan seorang *programmer*.

---

## 📋 Prasyarat Komputer Sebelum Memulai

Sebelum menjalankan ELKA, pastikan komputer/laptop Anda telah terpasang:
1. **Docker Desktop** (Pastikan aplikasi Docker Desktop sudah dalam status *Running* / menyala).
2. **Git** (Opsional, untuk mengambil pembaruan kode).
3. **Web Browser** modern (seperti Google Chrome, Microsoft Edge, Mozilla Firefox, atau Safari).

> [!NOTE]
> Anda **tidak perlu** menginstal PHP, PostgreSQL, atau Redis secara manual di komputer Anda, karena semua komponen tersebut sudah dibungkus rapi di dalam teknologi Docker.

---

## 🚀 Panduan Cepat Menjalankan ELKA (5 Langkah Mudah)

Ikuti 5 langkah sederhana berikut untuk menyalakan ELKA di komputer Anda:

### Langkah 1: Buka Terminal / Command Prompt
Buka terminal di folder proyek ini (`websinkronisasi`):
* Pada **Windows**: Buka folder ini di File Explorer, klik kanan di area kosong, lalu pilih **Open in Terminal** (atau gunakan PowerShell / WSL Ubuntu).
* Pada **Linux / macOS**: Buka Terminal dan arahkan ke folder proyek ini.

### Langkah 2: Siapkan Berkas Konfigurasi (`.env`)
Salin berkas contoh pengaturan menjadi berkas aktif:
```bash
cp .env.example .env
```
*(Bagi pengguna Windows Command Prompt biasa, gunakan perintah: `copy .env.example .env`)*

### Langkah 3: Nyalakan Layanan Server (Docker)
Jalankan perintah berikut untuk menyalakan wadah server aplikasi, database PostgreSQL, dan Redis:
```bash
docker compose up -d
```
Tunggu beberapa saat hingga muncul tanda centang hijau (`✔ Started`) pada ketiga layanan (`websinkronisasi_app`, `websinkronisasi_postgres`, dan `websinkronisasi_redis`).

### Langkah 4: Siapkan Basis Data & Data Awal
Setelah server menyala, siapkan tabel-tabel dan data master awal (seperti 6 Lembar Kerja standar dan akun admin awal) dengan menjalankan satu perintah berikut:
```bash
docker compose exec app php artisan migrate:fresh --seed
```
*Proses ini hanya memakan waktu beberapa detik dan menyiapkan seluruh master lembar kerja dan akun secara otomatis.*

### Langkah 5: Buka Aplikasi di Browser
Buka browser favorit Anda, lalu ketik alamat berikut di bilah alamat (*address bar*):
```text
http://localhost:8000/admin
```
🎉 **Selamat! Halaman login aplikasi ELKA kini sudah tampil dan siap digunakan.**

---

## 🔑 Akun Login Bawaan untuk Uji Coba

Sistem telah menyediakan akun siap pakai untuk mencoba seluruh fitur aplikasi:

### 1. Akun Super Administrator (Login Lokal)
Gunakan akun ini untuk mengelola lembar kerja, impor Excel, pengaturan sistem, dan jejak audit:
* **Halaman Login:** `http://localhost:8000/admin/login`
* **Alamat Email:** `admin@kantor.go.id`
* **Kata Sandi (Password):** `SuperAdmin2026!`

> [!IMPORTANT]
> **Ganti Kata Sandi Pertama Kali:** Demi keamanan, saat pertama kali Anda masuk menggunakan akun ini, sistem akan otomatis meminta Anda membuat kata sandi baru. Setelah kata sandi baru disimpan, Anda dapat langsung menggunakan seluruh menu.

---

### 2. Akun Pegawai & Admin Operasional (Simulasi SSO BPS)
Untuk mencoba alur login Pegawai atau Admin kantor seperti di lingkungan intranet nyata, buka halaman simulasi berikut:
* **Alamat Simulasi:** `http://localhost:8000/auth/sso/mock-login`
* Anda dapat memilih salah satu dari 3 tombol:
  1. **Masuk sebagai Pegawai (Read-Only):** Untuk melihat matriks centang monitor dan mencari dokumen.
  2. **Masuk sebagai Admin (Operasional):** Untuk mencoba mengunggah berkas bukti dukung dan memverifikasi kecocokan.
  3. **Masuk sebagai Akun Nonaktif:** Untuk melihat bagaimana sistem melindungi kantor dengan menolak akun yang dinonaktifkan.

---

## 🛠️ Perintah Berguna Sehari-hari

Berikut daftar perintah singkat yang dapat Anda jalankan di terminal sesuai kebutuhan:

| Kebutuhan Anda | Perintah yang Dijalankan | Penjelasan Singkat |
|---|---|---|
| **Menyalakan ELKA** | `docker compose up -d` | Menghidupkan server aplikasi di latar belakang. |
| **Mematikan ELKA** | `docker compose down` | Menghentikan server aplikasi dengan aman saat komputer akan dimatikan. |
| **Melihat Status Server** | `docker compose ps` | Memeriksa apakah semua layanan server berjalan normal. |
| **Menjalankan Pengujian Sistem** | `docker compose exec app php artisan test` | Memeriksa kesehatan seluruh modul sistem secara otomatis (106 pengujian). |
| **Memperbaiki Format Kode** | `docker compose exec app ./vendor/bin/pint` | Merapikan susunan kode PHP agar selalu sesuai standar baku. |

---

## ❓ Pertanyaan yang Sering Diajukan (FAQ)

#### Q1: Kenapa saat membuka `http://localhost:8000` halaman tidak mau terbuka (*This site can’t be reached*)?
* **Solusi:** Pastikan aplikasi Docker Desktop di komputer Anda sudah menyala (*running*), lalu jalankan perintah `docker compose up -d` di terminal. Pastikan tidak ada aplikasi lain yang memakai port 8000 di komputer Anda.

#### Q2: Di mana letak berkas bukti dukung fisik yang diunggah?
* **Penjelasan:** Semua berkas dokumen bukti dukung disimpan secara aman dan terisolasi di direktori privat server (`storage/app/private/evidence/`) dengan nama acak yang diacak sistem. Berkas ini tidak dapat diakses sembarangan oleh pihak luar tanpa login dan izin otorisasi.

#### Q3: Bagaimana cara membaca panduan pemakaian aplikasi untuk pengguna kantor?
* **Panduan Lengkap Penggunaan:** Silakan buka berkas [**`carapenggunaan.md`**](file:///home/hashirama/websinkronisasi/carapenggunaan.md) untuk melihat panduan langkah demi langkah cara menggunakan fitur-fitur ELKA.
