# Buku Panduan Penggunaan Aplikasi ELKA
### Sistem Sinkronisasi Bukti Dukung ke Lembar Kerja (LK)

Selamat datang di Buku Panduan Penggunaan **ELKA** (*Elektronik Lembar Kerja & Bukti Dukung*). 

Buku panduan ini disusun khusus menggunakan bahasa yang sederhana dan ramah bagi orang awam agar seluruh pegawai kantor dan tim kerja dapat menggunakan aplikasi ini dengan mudah dan nyaman tanpa kebingungan.

---

## 📖 Daftar Isi

1. [Mengenal ELKA & Cara Kerjanya](#1-mengenal-elka--cara-kerjanya)
2. [Tiga Peran Pengguna (Siapa Bisa Melakukan Apa?)](#2-tiga-peran-pengguna-siapa-bisa-melakukan-apa)
3. [Cara Masuk (Login) ke Aplikasi](#3-cara-masuk-login-ke-aplikasi)
4. [Menggunakan Matriks Monitor (Layar Utama Centang)](#4-menggunakan-matriks-monitor-layar-utama-centang)
5. [Mencari Dokumen dengan Cepat & Pintar](#5-mencari-dokumen-dengan-cepat--pintar)
6. [Mengunggah Berkas Bukti Dukung Baru (Untuk Pegawai & Admin)](#6-mengunggah-berkas-bukti-dukung-baru-untuk-pegawai--admin)
7. [Memeriksa & Menyetujui Kecocokan di Antrean Verifikasi (Untuk Admin)](#7-memeriksa--menyetujui-kecocokan-di-antrean-verifikasi-untuk-admin)
8. [Mengelola Tautan Google Drive LK di Database (Untuk Super Admin & Admin)](#8-mengelola-tautan-google-drive-lk-di-database-untuk-super-admin--admin)
9. [Menambah atau Mencabut Hubungan Dokumen Manual (Untuk Admin)](#9-menambah-atau-mencabut-hubungan-dokumen-manual-untuk-admin)
10. [Mengimpor Lembar Kerja dari File Excel (Untuk Super Admin)](#10-mengimpor-lembar-kerja-dari-file-excel-untuk-super-admin)
11. [Melihat Jejak Audit & Pengaturan Sistem (Untuk Super Admin)](#11-melihat-jejak-audit--pengaturan-sistem-untuk-super-admin)
12. [Tips Penamaan File agar Otomatis Cocok 100%](#12-tips-penamaan-file-agar-otomatis-cocok-100)

---

## 1. Mengenal ELKA & Cara Kerjanya

Di kantor kita, setiap tahun terdapat berbagai penilaian kinerja dan reformasi birokrasi yang menggunakan **Lembar Kerja (LK)** berbeda-beda, contohnya:
* **ZI (Zona Integritas)** menuju WBK/WBBM
* **PEKPPP** (Pelayanan Publik)
* **KIP** (Keterbukaan Informasi Publik)
* **SAKIP** (Akuntabilitas Kinerja)
* Dan lembar kerja pendataan lainnya.

Sering kali, **satu dokumen kantor** (misalnya *SK Pembentukan Tim Kerja* atau *SOP Pelayanan*) dibutuhkan dan dipakai di **beberapa Lembar Kerja sekaligus**. 

Dahulu, pegawai harus mencari berkas satu per satu dan menyalin tautan secara berulang ke banyak berkas Excel. Dengan **ELKA**, Anda cukup mengunggah satu berkas bukti dukung, lalu sistem pintar ELKA akan membaca isi dokumen tersebut dan secara otomatis memetakannya ke lembar kerja yang cocok dengan tanda centang (**✔**).

```text
[ Berkas SK / Laporan Diunggah ]
               │
               ▼
   [ Mesin Cerdas ELKA Membaca Teks ]
               │
       ┌───────┼───────┐
       ▼       ▼       ▼
    [ ZI ] [ PEKPPP ] [ KIP ]
      ✔        ✔        -
```

---

## 2. Tiga Peran Pengguna (Siapa Bisa Melakukan Apa?)

Aplikasi ELKA membagi pengguna menjadi 3 tingkat peran demi menjaga keamanan data kantor:

| Peran | Siapa Penggunanya? | Apa Saja yang Boleh Dilakukan? |
|---|---|---|
| **Pegawai** | Seluruh staf & pegawai kantor | • Melihat tabel Matriks Monitor (tanda centang indikator kesamaan).<br>• Mengunggah berkas dokumen (SK, SOP, dll.) untuk mencocokkan kesamaan secara otomatis.<br>• Mengklik tanda centang (✔) untuk **langsung membuka Google Drive** bukti dukung.<br>• Mencari berkas dokumen berdasarkan nama, nomor surat, atau isinya. |
| **Admin** | Tim penilai, operator LK, atau PIC dokumen | • Memiliki semua hak akses Pegawai (termasuk mengunggah berkas bukti dukung untuk mencocokkan).<br>• Menambah, mengubah, dan menghapus tautan Google Drive LK di database.<br>• Membuka **Antrean Verifikasi** untuk menyetujui atau menolak saran kecocokan dari sistem.<br>• Menambah atau menghapus centang bukti secara manual.<br>• Mengaktifkan atau menonaktifkan akun Pegawai biasa. |
| **Super Admin** | Pengelola sistem TI utama kantor | • Mengelola akun Admin (menambah admin, menonaktifkan, atau mengubah peran).<br>• Menambah, mengubah, dan menghapus tautan Google Drive LK di database.<br>• Menambah, mengubah, dan menghapus master Lembar Kerja & impor Excel LKE.<br>• Melihat **Jejak Audit** (rekaman siapa yang mengubah data apa) dan Pengaturan Sistem.<br>• *Catatan: Super Admin tidak mengunggah dokumen SK pencocokan.* |

---

## 3. Cara Masuk (Login) ke Aplikasi

Aplikasi ELKA kini menggunakan **1 Portal Tunggal Terpadu (*Unified Single Portal*)**. Seluruh pengguna kantor—baik Pegawai, Admin operasional, maupun Super Administrator—cukup mengakses satu tautan utama yang sama di peramban:

👉 **`http://localhost:8000/login`** (atau cukup buka alamat utama `http://localhost:8000/`)

Halaman portal akan menampilkan antarmuka modern dua bilah yang sangat jelas dan mudah digunakan:

```text
+---------------------------------------------------------------------------------------------------+
|  [LOGO BPS]  BPS KABUPATEN TEGAL — SISTEM ELKA                                                   |
+----------------------------------------------------+----------------------------------------------+
| BILAH KIRI: OPSI 1 — SSO PEGAWAI & ADMIN BPS       | BILAH KANAN: OPSI 2 — AKUN PENGELOLA LOKAL   |
+----------------------------------------------------+----------------------------------------------+
|                                                    |                                              |
|  [Tag] SSO Terintegrasi                            |  [Tag] Pengelola Sistem                      |
|  Login SSO BPS                                     |  Masuk Akun Pengelola                        |
|                                                    |                                              |
|  Otentikasi terpusat untuk Pegawai dan Admin       |  Khusus Super Admin dan pemeliharaan teknis  |
|  operasional satuan kerja BPS Kabupaten Tegal.     |  sistem Websinkronisasi kantor.              |
|                                                    |                                              |
|  [ TOMBOL BESAR: MASUK DENGAN SSO BPS ]            |  [ Formulir Masuk ]                          |
|                                                    |  * Alamat Email atau NIP                     |
|  [ Peringatan Penting VPN FortiClient ]            |  * Kata Sandi (Dilengkapi Tombol Intip)      |
|  Jika Anda berada di luar jaringan kantor BPS,     |  [x] Ingat sesi saya di peramban ini         |
|  pastikan aplikasi FortiClient VPN BPS telah       |                                              |
|  terhubung (Connected) sebelum menekan tombol.     |  [ TOMBOL HITAM: MASUK KE APLIKASI ]         |
|                                                    |                                              |
|  [ Tautan Simulasi Mode Mandiri / Mock ]           |  [ Catatan Akun Bawaan Seeder Awal ]         |
|  -> Mode Simulasi SSO (Uji Coba Mandiri)           |  Super Admin: admin@kantor.go.id             |
+----------------------------------------------------+----------------------------------------------+
|  © 2026 BPS Kabupaten Tegal • ELKA: Sistem Websinkronisasi Bukti Dukung                          |
+---------------------------------------------------------------------------------------------------+
```

### Opsi 1: Masuk via SSO BPS (Untuk Seluruh Pegawai & Admin Operasional)
1. Buka portal di `http://localhost:8000/login`.
2. Pada **Bilah Kiri (Opsi 1)**, klik tombol biru **Masuk dengan SSO BPS**.
3. Sistem akan mengarahkan Anda ke layar SSO resmi BPS (`https://sso.bps.go.id`).
4. Masukkan username SSO kantor dan kata sandi Anda.
5. Setelah berhasil, Anda akan otomatis masuk ke dalam sistem ELKA. Bila ini pertama kali login, akun Anda otomatis terdaftar sebagai **Pegawai**.
6. > [!IMPORTANT]
   > **Akses dari Luar Kantor BPS (Work from Home / Dinas Luar):** Pastikan aplikasi **FortiClient VPN BPS** pada komputer/laptop Anda sudah berstatus **Connected** sebelum menekan tombol SSO. Jika VPN belum terhubung, sistem akan menampilkan notifikasi ramah untuk menyalakan VPN terlebih dahulu.

### Opsi 2: Masuk Akun Pengelola Lokal (Khusus Super Administrator)
1. Buka portal di `http://localhost:8000/login`.
2. Pada **Bilah Kanan (Opsi 2)**, masukkan email atau NIP pengelola (contoh akun bawaan seeder: `admin@kantor.go.id`) dan kata sandi.
3. Anda dapat menekan ikon **mata** di samping kolom kata sandi untuk memeriksa ketepatan ketikan.
4. Klik tombol **Masuk ke Aplikasi**.
5. > [!TIP]
   > **Khusus Login Pertama Kali Super Admin:** Jika Anda baru pertama kali login menggunakan akun bawaan, sistem akan menampilkan layar **Karantina Ganti Kata Sandi**. Anda wajib memasukkan kata sandi baru (minimal 8 karakter) sebelum dapat mengakses menu lainnya. Jika ingin membatalkan, tersedia tombol **Batal dan Keluar** yang aman.

---

## 4. Menggunakan Matriks Monitor (Layar Utama Centang)

Menu **Matriks Monitor** adalah layar utama yang digunakan oleh seluruh pegawai dan pimpinan kantor untuk melihat pemenuhan bukti dukung di setiap Lembar Kerja.

### Cara Membuka:
Pada bilah menu sebelah kiri, klik menu **Operasional** ➔ pilih **Matriks Monitor**.

```text
========================================================================================
                              MATRIKS SINKRONISASI INDIKATOR LK
========================================================================================
[Filter Periode: 2026 ▼]    [Cari Indikator / Dokumen: _________________ 🔍]
----------------------------------------------------------------------------------------
                                          │     INTERNAL BPS      │    EKSTERNAL
INDIKATOR KESAMAAN / KEBUTUHAN BUKTI      │  KIP │   ZI   │ PEKPPP│ SEKUNDER │ SEKTORAL
------------------------------------------┼──────┼────────┼───────┼──────────┼──────────
1. SK Pengelola Informasi Publik (PPID)   │  ✔   │        │       │          │
2. SOP Pelayanan Informasi & Pengaduan    │  ✔   │   ✔    │   ✔   │          │
3. Laporan Evaluasi & Monev Triwulanan    │      │   ✔    │   ✔   │    ✔     │
========================================================================================
```



### Memahami Tanda pada Tabel:
* **Tanda Centang Hijau (✔):** Menandakan bahwa indikator tersebut **sudah terpenuhi kecocokan dokumennya** pada Lembar Kerja di kolom terkait.
* **Kotak Kosong Bersih:** Menandakan indikator tersebut belum memiliki bukti dukung pada Lembar Kerja terkait. Di ELKA, kotak yang belum terpenuhi dikosongkan murni tanpa simbol agar bersih dan nyaman dibaca.

### Membuka Berkas di Google Drive:
1. Arahkan mouse Anda ke salah satu tanda **✔** pada tabel.
2. **Klik tanda ✔ tersebut.**
3. Sistem akan **langsung membuka tab baru ke Google Drive** tempat berkas bukti dukung LK tersebut tersimpan. Cepat, praktis, dan tanpa jendela pop-up perantara!

---

## 5. Mencari Dokumen dengan Cepat & Pintar

ELKA dilengkapi dengan mesin pencari yang sangat cerdas:

### Apa Saja yang Bisa Dicari?
Cukup ketikkan kata kunci pada kolom pencarian di bagian atas tabel:
1. **Berdasarkan Nama Dokumen:** Contoh: `Standar Pelayanan`.
2. **Berdasarkan Nomor Surat / Dokumen:** Contoh: `SK/001/2026`.
3. **Berdasarkan Isi Dokumen:** Meskipun kata tersebut tidak tertulis di judul file, sistem ELKA dapat mencari hingga ke **isi teks di dalam dokumen PDF atau hasil pemindaian (OCR)**! Contoh: ketik `Reformasi Birokrasi`.

### Cerdas Terhadap Salah Ketik (Typo-Tolerant):
Tidak perlu khawatir jika Anda tidak sengaja salah mengetik, misalnya mengetik `Keputussan` (huruf 's' dobel). Sistem ELKA tetap dapat mengenali dan menampilkan berkas `Keputusan`.

---

## 6. Mengunggah Berkas Bukti Dukung Baru (Untuk Pegawai & Admin)

Sebagai Pegawai atau Admin, Anda dapat menambahkan berkas dokumen baru ke dalam sistem untuk mencari kesesuaian (matching) dengan Lembar Kerja:

1. Pada menu sebelah kiri, klik **Bukti Dukung** ➔ pilih **Daftar Bukti Dukung**.
2. Klik tombol kuning di kanan atas: **+ Bukti Dukung Baru**.
3. Isi formulir yang disediakan:
   * **Nama Dokumen:** Tuliskan judul resmi dokumen (contoh: *Surat Keputusan Tim Pelaksana Reformasi Birokrasi*).
   * **Nomor Dokumen:** Tuliskan nomor resmi surat/SK jika ada (contoh: *SK-BPS-01/2026*).
   * **Tahun Dokumen:** Masukkan tahun berlakunya dokumen (contoh: *2026*).
   * **Jenis Dokumen:** Pilih jenisnya (SK, SOP, Laporan Kinerja, Pedoman, dll.).
   * **Unggah Berkas:** Seret atau pilih berkas dari komputer Anda (format yang didukung: **PDF, Word DOCX, JPG, atau PNG** dengan ukuran maksimal **20 MB**).
4. Klik tombol **Simpan**.

> [!NOTE]
> **Pencegahan Berkas Ganda Otomatis:**
> Jika tanpa sengaja Anda mengunggah berkas yang persis sama dengan yang sudah pernah diunggah sebelumnya (meskipun nama filenya diganti), sistem ELKA akan menolaknya secara otomatis demi mencegah pemborosan ruang penyimpanan kantor.

---

## 7. Memeriksa & Menyetujui Kecocokan di Antrean Verifikasi (Untuk Admin)

Ketika dokumen selesai diunggah, mesin cerdas ELKA akan langsung menganalisis teksnya. Jika tingkat kemiripan dokumen mencapai ambang batas (75% ke atas), dokumen tersebut akan langsung diberi tanda centang (**✔**) di Matriks Monitor dan masuk ke menu verifikasi:

1. Klik menu **Operasional** ➔ pilih **Antrean Verifikasi**.
2. Anda akan melihat daftar dokumen yang dipasangkan oleh sistem secara otomatis:
   * **Nama Dokumen:** Dokumen bukti yang diunggah.
   * **Indikator LK Target:** Butir pertanyaan/evaluasi pada Lembar Kerja.
   * **Skor Keyakinan:** Persentase kemiripan (misal: *85.5%*).
   * **Alasan Pencocokan:** Kata-kata kunci yang ditemukan sama.
3. Anda memiliki dua pilihan tindakan:
   * **Tombol Hijau (Konfirmasi):** Klik tombol ini jika Anda setuju bahwa bukti ini memang tepat untuk indikator tersebut. Statusnya akan berubah menjadi *Terverifikasi*.
   * **Tombol Merah (Tolak):** Klik tombol ini jika menurut Anda dokumen ini tidak relevan untuk indikator tersebut.

> [!IMPORTANT]
> **Kekekalan Penolakan Admin:**
> Jika Anda sudah menekan tombol **Tolak**, tanda centang (✔) akan langsung terhapus dari monitor. Di masa depan, mesin otomatis ELKA **tidak akan pernah lagi** memaksakan kecocokan tersebut, menghormati keputusan Anda sebagai verifikator manusia.

---

## 8. Mengelola Tautan Google Drive LK di Database (Untuk Super Admin & Admin)

Agar pengguna yang mengklik tanda centang (✔) langsung terhubung ke folder atau berkas resmi bukti dukung di Google Drive, Super Admin dan Admin dapat mengatur tautan Drive indikator:

1. Pada menu sebelah kiri, buka **Master Data** ➔ pilih **Tautan Drive LK**.
2. Anda akan melihat daftar seluruh indikator Lembar Kerja berserta status link Drive-nya.
3. Anda dapat menyaring indikator yang **Belum Ada Link Drive** untuk melengkapinya.
4. Klik tombol **Atur Link Drive** pada baris indikator yang diinginkan.
5. Masukkan tautan lengkap Google Drive (wajib diawali `https://` atau `http://`, contoh: `https://drive.google.com/drive/folders/abcdef...`).
6. Klik **Simpan**. Tautan tersebut akan langsung aktif di database, dan setiap kali bukti dukung indikator ini cocok di Matriks Monitor, klik centang (✔) akan langsung membawa pengguna ke Google Drive tersebut.
7. Anda juga dapat menghapus tautan sewaktu-waktu dengan tombol **Hapus Link**.

---

## 9. Menambah atau Mencabut Hubungan Dokumen Manual (Untuk Admin)

Terkadang, ada dokumen yang secara tata bahasa berbeda namun sebenarnya merupakan bukti yang sah:

### Cara Menambah Kecocokan Manual:
1. Buka menu **Matriks Monitor**.
2. Klik tanda **+** atau buka detail dokumen yang ingin dihubungkan.
3. Pilih Lembar Kerja dan butir indikator yang dituju.
4. Klik **Hubungkan Bukti**. Hubungan ini akan langsung berstatus *Terverifikasi* (100%).

### Cara Mencabut Kecocokan:
1. Klik tanda **✔** pada sel yang bersangkutan di Matriks Monitor.
2. Pada jendela detail yang muncul, klik tombol merah bertuliskan **Cabut Relasi**.
3. Hubungan tersebut akan langsung terhapus dan dicatat di riwayat kantor.

---

## 10. Mengimpor Lembar Kerja dari File Excel (Untuk Super Admin)

Jika kantor memiliki Lembar Kerja evaluasi baru dari kementerian atau pusat (berupa file Excel dengan ratusan/ribuan butir):

1. Masuk sebagai **Super Admin**.
2. Klik menu **Master Data** ➔ pilih **Lembar Kerja**.
3. Cari Lembar Kerja yang ingin diisi (misal: *LKE Zona Integritas 2026*).
4. Klik tombol aksi **Impor Excel**.
5. Pilih profil impor yang sesuai (misal: *Profil LKE ZI* atau *Profil Standar PEKPPP*) dan pilih file Excel dari komputer Anda.
6. Klik **Mulai Impor**.
7. Sistem akan mengekstrak seluruh indikator dan butir bukti secara otomatis.

> [!TIP]
> **Aman dari Duplikasi:** Jika sewaktu-waktu file Excel yang sama diimpor ulang, data tidak akan berlipat ganda. Sistem ELKA menggunakan pola cerdas yang memperbarui data lama tanpa membuat data ganda.

---

## 11. Melihat Jejak Audit & Pengaturan Sistem (Untuk Super Admin)

### 1. Memeriksa Jejak Audit (Audit Log)
Untuk menjamin keterbukaan dan mencegah penyalahgunaan, semua tindakan penting di ELKA terekam secara otomatis:
* Buka menu **Manajemen Sistem** ➔ pilih **Jejak Audit**.
* Anda dapat melihat siapa yang masuk/keluar aplikasi, siapa yang mengunggah dokumen, siapa yang menyetujui centang, dan kapan aksi tersebut dilakukan.
* Catatan audit ini bersifat **kekal** (*tidak dapat diedit maupun dihapus oleh siapa pun*).

### 2. Mengatur Parameter Sistem
* Buka menu **Manajemen Sistem** ➔ pilih **Pengaturan Sistem**.
* Anda dapat mengatur sensitivitas ambang batas pencocokan (nilai standar: `75.0%`) dan batas ukuran berkas upload.

---

## 12. Tips Penamaan File agar Otomatis Cocok 100%

Agar mesin cerdas ELKA dapat langsung mencocokkan dokumen Anda dengan status **100% Cocok Pasti** tanpa perlu verifikasi rumit, gunakan salah satu dari dua tips praktis berikut saat menyimpan nama berkas PDF:

1. **Cantumkan Kode Indikator pada Nama Berkas:**
   * Contoh baik: `1.2.a SOP Layanan Statistik Terpadu.pdf`
   * Mesin akan membaca kode `1.2.a` dan langsung memasangkannya ke indikator 1.2.a dengan skor 100%.
2. **Cantumkan Nomor SK Resmi:**
   * Contoh baik: Dokumen nomor `SK-123-2026`.
   * Jika pada indikator lembar kerja tertulis nomor SK yang sama, mesin akan langsung mengenalinya secara akurat.

---

*Selamat bekerja dan menyinkronkan bukti dukung kantor dengan lebih mudah, cepat, dan rapi bersama **ELKA**!*

