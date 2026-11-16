# Buku Panduan Penggunaan Aplikasi ELKA
### Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja (LK)

Selamat datang di Buku Panduan Penggunaan **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*). Panduan ini ditulis dengan bahasa sehari-hari agar seluruh pegawai kantor dapat langsung menggunakan ELKA sesuai perannya.

---

## 1. Mengenal Tiga Peran Pengguna di ELKA

ELKA membagi pengguna ke dalam 3 peran dengan tugas yang jelas:

| Peran | Siapa Penggunanya? | Tugas Utama di Aplikasi |
|---|---|---|
| **Pegawai** | Seluruh staf & pegawai kantor | • **Cari Dokumen:** Mencari dan mengunduh dokumen bukti dukung yang sudah disetujui.<br>• **Unggah & Pengajuan Saya:** Mengunggah berkas bukti dukung baru (maksimal **50 MB**) atau menempel tautan luar (untuk berkas $> 50\text{ MB}$ / video) beserta usulan LK.<br>• **Revisi Pengajuan:** Memperbaiki pengajuan milik sendiri apabila diminta revisi oleh Admin.<br>• **Matriks Monitor:** Melihat tabel centang pemenuhan bukti dukung Dokumen x LK dan mengunduh CSV. |
| **Admin** | Tim verifikator, operator LK, atau PIC dokumen | • **Antrean Verifikasi:** Memeriksa dokumen yang diajukan pegawai, melihat saran indikator dari sistem, lalu memilih **Setujui** (beserta LK resminya), **Minta Revisi**, atau **Tolak**.<br>• **Sinkronisasi Google Drive:** Mengatur daftar folder Google Drive dan menjalankan sinkronisasi otomatis ke sistem.<br>• **Tautan Drive Indikator:** Mengatur tautan Google Drive pada indikator LK.<br>• **Cari & Unggah:** Juga dapat mencari dokumen dan mengajukan bukti dukung seperti Pegawai. |
| **Super Admin** | Pengelola utama sistem & master data | • **Master Data LK & Impor Excel:** Mengelola daftar Lembar Kerja, profil impor, indikator LK, serta mengimpor file Excel LKE.<br>• **Manajemen Pengguna:** Menambah pengguna, mengubah peran (Pegawai ↔ Admin), serta mengaktifkan/menonaktifkan akun.<br>• **Jejak Audit:** Memantau dan mengekspor catatan riwayat aktivitas sistem ke CSV.<br>• **Verifikasi & Hapus Dokumen:** Ikut membantu di Antrean Verifikasi dan satu-satunya peran yang dapat menghapus dokumen bukti dukung.<br>• *Catatan: Super Admin tidak mengunggah dokumen, tidak memakai menu Cari Dokumen, dan tidak menjalankan sinkronisasi Drive.* |

---

## 2. Cara Masuk (Login) ke Aplikasi

Buka alamat **`http://localhost:8000/login`** di browser Anda:
1. **Pegawai & Admin (SSO BPS):** Klik tombol **Masuk dengan SSO BPS** di sebelah kiri (pastikan FortiClient VPN sudah tersambung bila sedang di luar kantor).
2. **Super Admin (Pengelola Lokal):** Masuk melalui formulir di sebelah kanan menggunakan email/NIP dan kata sandi. Saat pertama kali login menggunakan akun awal, sistem akan meminta Anda mengganti kata sandi terlebih dahulu.

---

## 3. Panduan untuk Pegawai: Cari Dokumen & Unggah Pengajuan

### A. Mencari dan Mengunduh Dokumen Resmi (Menu: **Cari Dokumen**)
1. Klik menu **Cari Dokumen** di sebelah kiri.
2. Ketik kata kunci (bisa nama dokumen, nomor SK, atau isi teks di dalam dokumen) serta pilih filter **Lembar Kerja** atau **Tahun** bila perlu.
3. Hanya dokumen yang sudah **Disetujui** yang tampil di sini, lengkap dengan label Lembar Kerja resminya (misal: `ZI`, `KIP`).
4. Klik tombol **Unduh** untuk mengunduh berkas, atau **Buka Tautan** untuk membuka link Google Drive/eksternal.
   * Saat diunduh, nama berkas otomatis rapi diawali kode LK resminya, contoh: `KIP_ZI_SK_Program_Kunjungan.pdf`.

### B. Mengajukan Bukti Dukung Baru (Menu: **Unggah & Pengajuan Saya**)
1. Klik menu **Unggah & Pengajuan Saya**, lalu klik tombol **Unggah Bukti Dukung**.
2. Pilih **Jenis Pengajuan**:
   * **Unggah Berkas (maks 50 MB):** Untuk berkas PDF, Word (`.docx`), Excel (`.xlsx`), JPG, atau PNG hingga ukuran **50 MB**.
   * **Tempel Tautan (untuk berkas $> 50\text{ MB}$ / video):** Cukup tempel alamat link `https://...` (misalnya link Google Drive) tanpa perlu mengunggah file fisik.
3. Isi **Nama Dokumen**, **Nomor Dokumen/SK** (opsional), **Tahun**, **Jenis Dokumen**, lalu pilih satu atau lebih **Lembar Kerja (LK)** yang diusulkan.
   * Jika sistem mendeteksi ada dokumen mirip yang sudah pernah diunggah, akan muncul kotak peringatan (maksimal 5 dokumen mirip) agar Anda tidak mengunggah dokumen ganda.
4. Klik **Simpan**. Pengajuan Anda akan berstatus **Menunggu Verifikasi**.

### C. Memperbaiki Pengajuan yang Diminta Revisi
1. Buka menu **Unggah & Pengajuan Saya**. Di tabel ini Anda hanya melihat pengajuan milik Anda sendiri.
2. Jika ada pengajuan berstatus **Perlu Revisi**, baca pesan perbaikan pada kolom **Catatan Admin**.
3. Klik tombol **Revisi**, perbaiki data atau ganti berkasnya, lalu simpan kembali. Statusnya akan kembali menjadi **Menunggu Verifikasi**.

---

## 4. Panduan untuk Admin: Verifikasi Antrean & Sinkronisasi Google Drive

### A. Memeriksa Pengajuan di **Antrean Verifikasi**
1. Klik menu **Antrean Verifikasi**.
2. Pada tab **Menunggu**, Anda akan melihat kartu setiap dokumen yang diajukan beserta:
   * Usulan LK dari pengunggah,
   * Saran kecocokan indikator dari mesin pintar ELKA, dan
   * Tombol **Unduh Berkas** / **Buka Tautan** untuk memeriksa isinya.
3. Ambil salah satu keputusan:
   * **Setujui:** Centang minimal satu **Lembar Kerja (LK)** resmi yang sesuai, lalu klik **Setujui**. Dokumen langsung berstatus **Disetujui**, muncul di **Cari Dokumen**, dan menampilkan tanda centang (**✔**) pada **Matriks Monitor**.
   * **Minta Revisi:** Tulis alasan/kekurangan pada kotak catatan, lalu klik **Minta Revisi** agar pengunggah dapat memperbaikinya.
   * **Tolak:** Tulis alasan penolakan pada kotak catatan, lalu klik **Tolak**.

### B. Menyinkronkan Folder Google Drive ke ELKA
1. Pada halaman **Antrean Verifikasi**, klik tombol **Kelola Folder Drive**.
2. Tempel tautan atau ID folder Google Drive kantor, beri label nama folder, dan pilih **Lembar Kerja (LK)** tujuan bila folder tersebut khusus untuk satu LK tertentu, lalu klik **Tambah Folder**.
3. Klik tombol **Sinkronkan Google Drive**.
4. Sistem secara aman (hanya baca, tanpa pernah mengubah atau menghapus isi Google Drive Anda) akan mendaftarkan berkas dari Drive ke ELKA:
   * Jika folder sudah dihubungkan ke LK tertentu, dokumen baru dari folder tersebut otomatis berstatus **Disetujui** dan langsung mendapat centang (**✔**) pada LK tersebut.
   * Jika nama berkas di Drive terlalu pendek/umum, sistem otomatis menjalankan pembacaan teks/OCR di latar belakang.

---

## 5. Panduan Menggunakan Matriks Monitor (Untuk Semua Peran)

1. Klik menu **Matriks Monitor**.
2. Tabel menampilkan daftar **Dokumen Bukti Dukung (yang sudah Disetujui)** pada baris, dan kolom **Lembar Kerja (Internal & Eksternal)** di sebelah kanan.
3. **Tanda Centang (✔):** Menandakan dokumen tersebut resmi terhubung ke Lembar Kerja pada kolom terkait. Sel yang tidak terhubung dibiarkan kosong bersih.
4. Klik tombol **Unduh CSV** di bagian atas jika Anda ingin mengunduh rekapitulasi tabel matriks ke komputer untuk dibuka di Microsoft Excel.

---

## 6. Panduan untuk Super Admin: Master Data, Pengguna, & Jejak Audit

1. **Master Lembar Kerja & Impor Excel:** Buka menu **Lembar Kerja** atau **Profil Impor LK** untuk menambah LK baru, mengatur periode evaluasi (`1 Bulan`, `3 Bulan`, `6 Bulan`, `12 Bulan`), dan mengimpor daftar indikator dari file Excel (`.xlsx`) dengan layar pratinjau aman.
2. **Manajemen Pengguna:** Buka menu **Manajemen Pengguna** untuk menambah akun baru, mengangkat Pegawai menjadi Admin, atau menonaktifkan akun pegawai yang sudah pindah/pensiun.
3. **Jejak Audit:** Buka menu **Jejak Audit** untuk memeriksa rekaman seluruh aktivitas penting di sistem dan mengekspornya ke berkas CSV.
