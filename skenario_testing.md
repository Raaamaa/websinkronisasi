# Panduan Skenario Pengujian Manual (Manual Testing Guide)
## Sistem ELKA (Sinkronisasi Bukti Dukung ke Lembar Kerja)

Panduan ini ditulis dengan bahasa sehari-hari untuk membantu penguji mencoba seluruh alur kerja utama **ELKA** sesuai 3 peran pengguna (**Pegawai**, **Admin**, dan **Super Admin**).

---

## 1. Akun Uji Coba yang Tersedia

* **URL Login Utama:** `http://localhost:8000/login`
* **URL Simulasi SSO (Lokal):** `http://localhost:8000/auth/sso/mock-login`

| Peran | Cara Masuk Saat Uji Coba | Tugas yang Diuji |
|---|---|---|
| **Pegawai** | Buka `/auth/sso/mock-login` ➔ Klik **Masuk sebagai Pegawai** | Cari dokumen disetujui, unggah berkas ($\le 50\text{ MB}$) / tempel tautan, revisi pengajuan sendiri, lihat Matriks Monitor & unduh berkas. |
| **Admin** | Buka `/auth/sso/mock-login` ➔ Klik **Masuk sebagai Admin** (atau form lokal `dimas.pratama@bps.go.id` / `Password123!`) | Verifikasi pengajuan di Antrean Verifikasi (setujui, minta revisi, tolak), kelola & sinkronkan folder Google Drive, kelola tautan Drive indikator. |
| **Super Admin** | Form lokal di `/login` dengan akun seeder (`admin@kantor.go.id`) | Ganti password pertama kali, kelola Master LK & Impor Excel, kelola pengguna & role, lihat/ekspor Jejak Audit, ikut verifikasi antrean, hapus dokumen. |

---

## 2. Skenario 1: Alur Masuk (Login) & Pembatasan Hak Akses 3 Peran

1. **Ganti Password Pertama Super Admin:**
   * Masuk di `/login` memakai akun awal Super Admin (`must_change_password = true`).
   * **Hasil:** Sistem langsung mengarahkan ke halaman ganti kata sandi dan menahan akses menu lain sampai kata sandi baru disimpan.
2. **Blokir Login Lokal untuk Pegawai & Blokir Akun Nonaktif:**
   * Coba masukkan email Pegawai pada form password lokal di `/login` ➔ **Ditolak** (Pegawai wajib melalui SSO).
   * Buka `/auth/sso/mock-login` lalu klik **Masuk sebagai Akun Nonaktif** ➔ **Ditolak (HTTP 403)** dan sesi tidak dibuat.
3. **Pembatasan Menu per Peran:**
   * **Pegawai:** Bisa membuka *Matriks Monitor*, *Cari Dokumen*, dan *Unggah & Pengajuan Saya*. Jika mencoba membuka `/admin/verification-queue`, `/admin/lembar-kerjas`, `/admin/users`, atau `/admin/audit-logs`, ditolak dengan **403**.
   * **Admin:** Bisa membuka *Matriks Monitor*, *Cari Dokumen*, *Unggah & Pengajuan Saya*, *Antrean Verifikasi*, dan *Indikator LK*. Jika mencoba membuka `/admin/lembar-kerjas`, `/admin/users`, atau `/admin/audit-logs`, ditolak dengan **403**.
   * **Super Admin:** Bisa membuka *Matriks Monitor*, *Antrean Verifikasi*, *Master LK*, *Profil Impor*, *Indikator LK*, *Manajemen Pengguna*, dan *Jejak Audit*. Jika mencoba membuka `/admin/cari-dokumen` atau `/admin/evidence-documents/create` (mengunggah bukti), ditolak dengan **403**.

---

## 3. Skenario 2: Alur Pegawai — Unggah Bukti Dukung, Mode Tautan, & Revisi

1. **Unggah Berkas Fisik (Maksimal 50 MB) dengan Usulan LK:**
   * Masuk sebagai **Pegawai**, buka **Unggah & Pengajuan Saya** ➔ **Unggah Bukti Dukung**.
   * Pilih **Unggah Berkas (maks 50 MB)**, unggah berkas PDF/DOCX/XLSX/JPG/PNG yang sah, isi Nama Dokumen (misal: `SK Program Kunjungan`), Tahun `2026`, dan pilih usulan LK (misal: `ZI`).
   * **Hasil:** Pengajuan tersimpan dengan status **Menunggu Verifikasi**. Di Matriks Monitor maupun Cari Dokumen, dokumen ini **belum** muncul sebagai centang resmi karena masih menunggu verifikasi Admin.
2. **Pengajuan Berkas Besar ($> 50\text{ MB}$) via Mode Tempel Tautan:**
   * Pilih **Tempel Tautan**, masukkan URL `https://drive.google.com/file/d/contoh123/view`, isi nama dokumen dan pilih LK usulan.
   * Coba masukkan tautan tidak aman seperti `javascript:alert(1)` ➔ **Ditolak oleh validasi**.
3. **Tolak File Palsu & File Duplikat:**
   * Coba unggah file program/script yang diganti namanya menjadi `.pdf` ➔ **Ditolak oleh pemeriksa isi berkas (MIME sniffing)**.
   * Coba unggah kembali berkas PDF yang persis sama dua kali ➔ **Ditolak sebagai duplikat** dan menampilkan informasi dokumen yang sudah ada.
4. **Isolasi Daftar Pengajuan & Alur Revisi:**
   * Pegawai A hanya dapat melihat daftar pengajuan miliknya sendiri di menu **Unggah & Pengajuan Saya** (tidak bisa melihat atau membuka pengajuan Pegawai B yang masih berstatus `menunggu`).
   * Saat Admin meminta revisi pada dokumen Pegawai A, status berubah menjadi **Perlu Revisi** beserta catatan Admin. Pegawai A mengklik **Revisi**, mengganti berkas/judul, lalu menyimpan ➔ Status kembali menjadi **Menunggu Verifikasi**.

---

## 4. Skenario 3: Alur Admin — Antrean Verifikasi & Sinkronisasi Google Drive

1. **Verifikasi Pengajuan (Setujui, Minta Revisi, Tolak):**
   * Masuk sebagai **Admin**, buka **Antrean Verifikasi** (`/admin/verification-queue`).
   * Pada kartu pengajuan Pegawai (yang mengusulkan LK X), Admin memilih LK Y lalu menekan **Setujui**.
   * **Hasil:** Status dokumen berubah menjadi **Disetujui**, dan hanya LK Y yang tercatat sebagai tautan LK resmi (`link_source = 'admin'`).
   * Coba klik **Minta Revisi** atau **Tolak** tanpa mengisi catatan ➔ **Ditolak** (catatan wajib diisi).
2. **Sinkronisasi Google Drive Baca-Saja & OCR Selektif:**
   * Pada **Antrean Verifikasi**, klik **Kelola Folder Drive**, tambahkan ID/tautan folder Google Drive dan hubungkan ke LK `ZI`.
   * Klik **Sinkronkan Google Drive** (pastikan `queue-worker` berjalan).
   * **Hasil:** Berkas baru dari folder tersebut terdaftar otomatis dengan `source_type = 'drive'`, berstatus **Disetujui**, dan langsung terhubung resmi ke LK `ZI` (`link_source = 'drive_sync'`).

---

## 5. Skenario 4: Cari Dokumen, Matriks Monitor, & Unduhan Aman

1. **Cari Dokumen Resmi:**
   * Masuk sebagai **Pegawai** atau **Admin**, buka **Cari Dokumen** (`/admin/cari-dokumen`).
   * Ketik kata kunci nama, nomor, atau isi dokumen (termasuk uji salah ketik ringan/typo).
   * **Hasil:** Hanya dokumen berstatus **Disetujui** yang tampil beserta badge LK resminya.
2. **Matriks Monitor Dokumen x LK & Unduh CSV:**
   * Buka **Matriks Monitor** (`/admin/monitor-matrix`).
   * **Hasil:** Baris menampilkan dokumen yang telah **Disetujui**, kolom menampilkan LK (Internal & Eksternal). Kolom LK resmi menampilkan tanda centang (**✔**), sedangkan kolom tanpa kecocokan resmi kosong bersih.
   * Klik **Unduh CSV** ➔ Berkas CSV terunduh rapi dan aman dibuka di Excel.
3. **Aturan Otorisasi & Format Nama Berkas Unduhan (`/evidence/download/{id}`):**
   * Unduh berkas unggahan (`source_type = 'upload'`) yang belum memiliki LK resmi (oleh pemiliknya saat status masih `menunggu`) ➔ Berkas terunduh dengan awalan **`DRAFT_`** (contoh: `DRAFT_SK_Program_Kunjungan.pdf`).
   * Pegawai lain mencoba mengunduh dokumen yang belum disetujui tersebut ➔ **Ditolak (HTTP 403)**.
   * Setelah dokumen disetujui untuk LK `ZI` dan `KIP`, semua pegawai aktif dapat mengunduhnya ➔ Nama berkas otomatis diawali kode LK resmi urut abjad (maksimal 4 kode, panjang total $\le 150$ karakter dengan ekstensi utuh), contoh: **`KIP_ZI_SK_Program_Kunjungan.pdf`**.
   * Untuk dokumen bertipe `drive` atau `link`, membuka tautan unduhan akan mengalihkan (*redirect*) secara aman ke `external_link` (`http`/`https`).

---

## 6. Skenario 5: Alur Super Admin — Master LK, Pengguna, & Jejak Audit

1. **Master LK & Impor Excel Idempoten:**
   * Masuk sebagai **Super Admin**, buka **Lembar Kerja** (`/admin/lembar-kerjas`) dan **Profil Impor LK** (`/admin/lk-import-profiles`).
   * Jalankan pratinjau dan simpan impor file `.xlsx` LKE dua kali berturut-turut ➔ Data indikator dan item bukti tidak berlipat ganda (idempoten). File `.xlsm` (bermakro) ditolak.
2. **Manajemen Pengguna & Proteksi Super Admin Terakhir:**
   * Buka **Manajemen Pengguna** (`/admin/users`), ubah role Pegawai menjadi Admin atau nonaktifkan akun Pegawai ➔ Berhasil dan sesi Pegawai yang dinonaktifkan langsung terputus.
   * Coba nonaktifkan atau turunkan role satu-satunya Super Admin aktif yang tersisa ➔ **Ditolak** oleh sistem.
3. **Jejak Audit Kekal (*Append-Only*):**
   * Buka **Jejak Audit** (`/admin/audit-logs`), periksa rekaman aktivitas (`bd.uploaded`, `bd.approved`, `bd.revised`, `user.role_changed`, dll.) dan klik **Ekspor CSV**.
   * Tidak ada tombol ubah atau hapus pada Jejak Audit, dan database menolak perintah `UPDATE`/`DELETE` pada tabel `audit_logs`.
