# Buku Panduan Penggunaan Aplikasi ELKA
### Perpustakaan Bukti Dukung BPS Kabupaten Tegal

Selamat datang di **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*) **BPS Kabupaten Tegal**. Aplikasi ini dirancang seperti **perpustakaan kantor**:
* **6 Lembar Kerja (LK)** berperan sebagai **rak buku** (`KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, dan `DATA_SEKUNDER`).
* **Dokumen Bukti Dukung** (SK, SOP, laporan, surat edaran, data) berperan sebagai **buku** yang dapat ditempatkan pada satu atau beberapa rak LK sekaligus.

---

## 1. Mengenal Tiga Peran Pengguna & Menunya

| Peran | Halaman Utama Setelah Masuk | Daftar Menu yang Tersedia |
|---|---|---|
| **Pegawai** | `/admin/cari-dokumen` | 1. **Cari Dokumen** (cari dokumen resmi, lihat matriks 6 LK, unduh, & cek berkas sebelum unggah)<br>2. **Unggah Bukti** (kirim berkas maks **20 MB** atau tempel tautan Google Drive untuk berkas $> 20\text{ MB}$, pantau status, & perbaiki revisi milik sendiri) |
| **Admin** | `/admin/cari-dokumen` | 1. **Cari Dokumen** (semua fitur Pegawai + tombol **Ubah LK** pada dokumen yang sudah disetujui)<br>2. **Unggah Bukti**<br>3. **Antrean Verifikasi** (periksa kiriman baru: Setujui, Minta Revisi, atau Tolak) |
| **Super Admin** | `/admin/verification-queue` | 1. **Antrean Verifikasi** (dilengkapi banner peringatan kapasitas penyimpanan bila disk server $\ge 80\%$)<br>2. **Manajemen Pengguna** (kelola akun & peran Pegawai/Admin)<br>3. **Jejak Audit** (pantau & ekspor riwayat aktivitas)<br>*Catatan: Super Admin tidak memakai menu Cari Dokumen maupun Unggah Bukti.* |

---

## 2. Cara Masuk (Login) ke ELKA

Buka alamat **`http://localhost:8000/login`** di browser Anda:
1. **Pegawai & Admin (SSO BPS):** Klik tombol **Login SSO BPS**. Jika membuka dari luar kantor BPS Kabupaten Tegal, nyalakan **VPN FortiClient** terlebih dahulu. Setelah masuk, Anda langsung diarahkan ke halaman **Cari Dokumen**.
2. **Super Admin:** Masuk lewat formulir di bagian kanan menggunakan email/NIP dan kata sandi. Saat pertama kali masuk memakai akun awal, sistem mewajibkan Anda mengganti kata sandi terlebih dahulu sebelum membuka **Antrean Verifikasi**.

---

## 3. Panduan Pegawai & Admin: Cari Dokumen & Cek Berkas

Buka menu **Cari Dokumen** (`/admin/cari-dokumen`):

### A. Mencari dan Mengunduh dari Matriks 6 LK
1. Ketik nama dokumen, nomor surat/SK, atau kata kunci isi dokumen di kotak pencarian, lalu klik **Cari Dokumen** (atau pilih filter kategori **Semua LK / Internal / Eksternal**, **Jenis**, dan **Tahun**).
2. Tabel di bawah menampilkan **dokumen yang sudah Disetujui** pada baris, dan **6 kolom Lembar Kerja** (`KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, `DATA_SEKUNDER`) di sebelah kanan.
3. **Tanda Centang (✔):** Menandakan dokumen tersebut resmi berada di rak LK kolom itu.
4. **Klik Tanda Centang (✔):** Bagian unduhan akan langsung terbuka di bawah baris dokumen:
   * Klik **Unduh Dokumen** untuk mengunduh berkas ke komputer (nama berkas otomatis diawali kode LK resmi, misalnya `KIP_ZI_SK_Tim.pdf`).
   * Klik **Buka Tautan** untuk membuka link Google Drive atau tautan luar.
5. Jika Anda memiliki pengajuan yang belum disetujui (masih *Menunggu*, *Perlu Revisi*, atau *Ditolak*), ringkasan jumlahnya tampil di kotak informasi atas tanpa tanda centang di matriks resmi.

### B. Cek Berkas Sebelum Unggah (Agar Tidak Mengunggah Berkas Kembar)
1. Pada kotak **Cek Berkas Sebelum Unggah**, pilih berkas dari komputer Anda lalu klik **Periksa Berkas**. *(Berkas hanya diperiksa sementara dan tidak disimpan ke server).*
2. Salah satu dari 3 hasil berikut akan muncul:
   * **Kotak Merah (Ditolak — Dokumen Sudah Ada):** Berkas yang persis sama sudah ada di perpustakaan, lengkap dengan info **Judul**, **Pengunggah**, **Status**, dan **Lembar Kerja**. Anda tidak perlu mengunggahnya lagi.
   * **Kotak Kuning (Peringatan Dokumen Mirip):** Ada hingga 5 dokumen dengan judul/isi mirip beserta persentase kemiripannya. Jika dokumen Anda memang berbeda, Anda tetap bisa klik **Tetap Lanjutkan ke Halaman Unggah**.
   * **Kotak Hijau (Belum Ada):** Muncul pesan *"Dokumen belum ada di perpustakaan. Silakan unggah."* beserta tombol langsung menuju halaman **Unggah Bukti**.

### C. Mengubah LK Dokumen Disetujui (Khusus Admin)
1. Pada halaman **Cari Dokumen**, Admin melihat tombol **Ubah LK** di kolom paling kanan setiap baris dokumen resmi.
2. Klik **Ubah LK**, centang ulang satu atau lebih Lembar Kerja resmi yang sesuai (minimal 1 LK), lalu klik **Simpan Perubahan**.

---

## 4. Panduan Pegawai & Admin: Mengirim & Memperbaiki Bukti

Buka menu **Unggah Bukti** (`/admin/evidence-documents`), lalu klik **Unggah Bukti**:

1. **Pilih Cara Pengajuan:**
   * **Unggah Berkas (maks 20 MB):** Pilih berkas PDF, Word (`.docx`), Excel (`.xlsx`), JPG, atau PNG hingga **20 MB**.
   * **Tempel Tautan:** *Berkas lebih dari 20 MB (mis. video): unggah dulu ke Google Drive, lalu kirim tautannya* (`https://...`).
2. Lengkapi **Nama Dokumen**, **Nomor Dokumen** (opsional), **Tahun**, **Jenis Dokumen**, dan **Pilih Lembar Kerja (LK)** (**wajib pilih minimal 1 LK**).
3. Klik **Kirim Pengajuan Bukti**. Dokumen akan berstatus **Menunggu Verifikasi**.
4. **Jika Diminta Revisi:** Buka menu **Unggah Bukti**, lihat pesan pada kolom **Catatan Admin**, lalu klik tombol **Revisi** (tombol ini muncul khusus untuk pengunggah asli saat status **Perlu Revisi**). Perbaiki data atau ganti berkas, lalu simpan kembali.

---

## 5. Panduan Admin & Super Admin: Antrean Verifikasi

Buka menu **Antrean Verifikasi** (`/admin/verification-queue`):

1. Pilih kartu pengajuan di tab **Menunggu**, periksa berkas/tautan dan usulan LK dari pengirim.
2. Pilih salah satu dari 3 tindakan:
   * **Setujui Dokumen:** Centang minimal 1 **Lembar Kerja Resmi**, lalu klik **Setujui Dokumen**. Dokumen langsung muncul di matriks **Cari Dokumen**.
   * **Minta Revisi:** Isi **Catatan atau Alasan** (wajib), lalu klik **Minta Revisi**.
   * **Tolak Pengajuan:** Isi **Catatan atau Alasan** (wajib), lalu klik **Tolak Pengajuan**.
3. **Banner Kapasitas Penyimpanan (Khusus Super Admin):** Bila kapasitas disk server mencapai $\ge 80\%$, Super Admin akan melihat kotak peringatan merah di bagian paling atas halaman Antrean Verifikasi.

---

## 6. Panduan Super Admin: Manajemen Pengguna & Jejak Audit

1. **Manajemen Pengguna (`/admin/users`):** Tambah akun pengelola, ubah peran (`Pegawai ↔ Admin`), atau nonaktifkan akun pegawai yang pindah/pensiun. Satu-satunya Super Admin aktif terakhir dilindungi agar tidak terkunci.
2. **Jejak Audit (`/admin/audit-logs`):** Pantau seluruh catatan aktivitas sistem yang tersimpan permanen (tidak dapat diubah atau dihapus) dan ekspor ke CSV bila diperlukan.
