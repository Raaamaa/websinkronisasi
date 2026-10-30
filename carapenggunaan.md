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
6. [Mengunggah Berkas Bukti Dukung Baru (Untuk Admin)](#6-mengunggah-berkas-bukti-dukung-baru-untuk-admin)
7. [Memeriksa & Menyetujui Kecocokan di Antrean Verifikasi (Untuk Admin)](#7-memeriksa--menyetujui-kecocokan-di-antrean-verifikasi-untuk-admin)
8. [Menambah atau Mencabut Hubungan Dokumen Manual (Untuk Admin)](#8-menambah-atau-mencabut-hubungan-dokumen-manual-untuk-admin)
9. [Mengimpor Lembar Kerja dari File Excel (Untuk Super Admin)](#9-mengimpor-lembar-kerja-dari-file-excel-untuk-super-admin)
10. [Melihat Jejak Audit & Pengaturan Sistem (Untuk Super Admin)](#10-melihat-jejak-audit--pengaturan-sistem-untuk-super-admin)
11. [Tips Penamaan File agar Otomatis Cocok 100%](#11-tips-penamaan-file-agar-otomatis-cocok-100)

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
| **Pegawai** | Seluruh staf & pegawai kantor | • Melihat tabel Matriks Monitor (tanda centang dokumen).<br>• Mencari berkas dokumen berdasarkan nama, nomor surat, atau isinya.<br>• Mengklik tanda centang untuk melihat detail indikator dan membuka tautan bukti.<br>• *Sifat akses: Hanya melihat (Read-Only) sehingga aman dari ketidaksengajaan.* |
| **Admin** | Tim penilai, operator LK, atau PIC dokumen | • Memiliki semua hak akses Pegawai.<br>• Mengunggah berkas bukti dukung baru (PDF, Word, Gambar).<br>• Membuka **Antrean Verifikasi** untuk menyetujui atau menolak saran kecocokan dari sistem.<br>• Menambah atau menghapus centang bukti secara manual.<br>• Mengaktifkan atau menonaktifkan akun Pegawai. |
| **Super Admin** | Pengelola sistem TI utama kantor | • Memiliki semua hak akses Admin dan Pegawai.<br>• Menambah, mengubah, dan menghapus master Lembar Kerja.<br>• Mengimpor ribuan butir indikator langsung dari file Excel LKE.<br>• Mengubah peran pengguna (misal: mengangkat Pegawai menjadi Admin).<br>• Mengubah pengaturan sensitivitas pencocokan sistem.<br>• Melihat **Jejak Audit** (rekaman siapa yang mengubah data apa). |

---

## 3. Cara Masuk (Login) ke Aplikasi

### Cara 1: Masuk Melalui SSO Kantor (Untuk Pegawai & Admin)
1. Buka browser dan kunjungi alamat: `http://localhost:8000/auth/sso/login` (atau pilih menu **Masuk via SSO**).
2. Anda akan diarahkan ke halaman login tunggal kantor.
3. Masukkan identitas Anda (Username/NIP dan Kata Sandi).
4. Setelah berhasil, Anda akan otomatis masuk ke halaman utama ELKA. Jika ini pertama kali Anda login, sistem akan otomatis mendaftarkan Anda sebagai **Pegawai**.

### Cara 2: Masuk Menggunakan Akun Khusus (Untuk Super Administrator)
1. Buka browser dan kunjungi alamat: `http://localhost:8000/admin/login`.
2. Masukkan email: `admin@kantor.go.id` dan kata sandi Anda.
3. Klik tombol **Sign in**.

> [!TIP]
> **Khusus Login Pertama Kali Super Admin:** Jika Anda baru pertama kali login menggunakan akun bawaan, sistem akan menampilkan layar **Karantina Ganti Kata Sandi**. Anda wajib memasukkan kata sandi baru yang kuat (minimal 8 karakter) sebelum dapat mengakses menu lainnya.

---

## 4. Menggunakan Matriks Monitor (Layar Utama Centang)

Menu **Matriks Monitor** adalah menu favorit yang paling sering digunakan oleh seluruh pegawai kantor.

### Cara Membuka:
Pada bilah menu sebelah kiri, klik menu **Operasional** ➔ pilih **Matriks Monitor**.

```text
========================================================================================
                               MATRIKS SINKRONISASI BUKTI DUKUNG
========================================================================================
[Filter Tahun: 2026 ▼]   [Jenis Dokumen: Semua ▼]   [Cari Dokumen: _________________ 🔍]
----------------------------------------------------------------------------------------
                                          │     INTERNAL BPS      │    EKSTERNAL
NAMA DOKUMEN BUKTI DUKUNG                 │  ZI  │ PEKPPP │  KIP  │ SEKUNDER │ SEKTORAL
------------------------------------------┼──────┼────────┼───────┼──────────┼──────────
1. SK Tim Reformasi Birokrasi 2026        │  ✔   │   ✔    │       │          │
2. SOP Pelayanan Statistik Terpadu        │  ✔   │   ✔    │   ✔   │          │
3. Buku Pedoman Tata Kelola Data          │      │        │       │    ✔     │    ✔
========================================================================================
```

### Memahami Tanda pada Tabel:
* **Tanda Centang Hijau (✔):** Menandakan bahwa dokumen bukti dukung pada baris tersebut **sudah cocok dan diakui** untuk Lembar Kerja pada kolom tersebut.
* **Kotak Kosong Bersih:** Menandakan dokumen tersebut belum atau tidak berhubungan dengan Lembar Kerja terkait. Di ELKA, kotak yang tidak cocok sengaja dikosongkan murni tanpa simbol membingungkan agar mata pembaca nyaman dan tidak lelah.

### Melihat Detail dan Tautan Bukti (Fitur Drill-Down):
1. Arahkan mouse Anda ke salah satu tanda **✔** pada tabel.
2. **Klik tanda ✔ tersebut.**
3. Sebuah jendela sembulan (*modal*) akan muncul menampilkan:
   * Nama dan nomor lengkap dokumen.
   * Daftar indikator Lembar Kerja yang cocok.
   * Tingkat persentase kecocokan (misalnya: *100% Cocok*).
   * Status verifikasi (*Otomatis* atau *Terverifikasi oleh Admin*).
   * **Tombol Buka Tautan Bukti:** Jika indikator memiliki tautan (misal Google Drive kantor atau web resmi), Anda dapat langsung mengkliknya. Tautan ini dijamin aman dan akan terbuka di tab baru.

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

## 6. Mengunggah Berkas Bukti Dukung Baru (Untuk Admin)

Sebagai Admin, Anda dapat menambahkan berkas dokumen baru ke dalam sistem:

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

## 8. Menambah atau Mencabut Hubungan Dokumen Manual (Untuk Admin)

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

## 9. Mengimpor Lembar Kerja dari File Excel (Untuk Super Admin)

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

## 10. Melihat Jejak Audit & Pengaturan Sistem (Untuk Super Admin)

### 1. Memeriksa Jejak Audit (Audit Log)
Untuk menjamin keterbukaan dan mencegah penyalahgunaan, semua tindakan penting di ELKA terekam secara otomatis:
* Buka menu **Manajemen Sistem** ➔ pilih **Jejak Audit**.
* Anda dapat melihat siapa yang masuk/keluar aplikasi, siapa yang mengunggah dokumen, siapa yang menyetujui centang, dan kapan aksi tersebut dilakukan.
* Catatan audit ini bersifat **kekal** (*tidak dapat diedit maupun dihapus oleh siapa pun*).

### 2. Mengatur Parameter Sistem
* Buka menu **Manajemen Sistem** ➔ pilih **Pengaturan Sistem**.
* Anda dapat mengatur sensitivitas ambang batas pencocokan (nilai standar: `75.0%`) dan batas ukuran berkas upload.

---

## 11. Tips Penamaan File agar Otomatis Cocok 100%

Agar mesin cerdas ELKA dapat langsung mencocokkan dokumen Anda dengan status **100% Cocok Pasti** tanpa perlu verifikasi rumit, gunakan salah satu dari dua tips praktis berikut saat menyimpan nama berkas PDF:

1. **Cantumkan Kode Indikator pada Nama Berkas:**
   * Contoh baik: `1.2.a SOP Layanan Statistik Terpadu.pdf`
   * Mesin akan membaca kode `1.2.a` dan langsung memasangkannya ke indikator 1.2.a dengan skor 100%.
2. **Cantumkan Nomor SK Resmi:**
   * Contoh baik: Dokumen nomor `SK-123-2026`.
   * Jika pada indikator lembar kerja tertulis nomor SK yang sama, mesin akan langsung mengenalinya secara akurat.

---

*Selamat bekerja dan menyinkronkan bukti dukung kantor dengan lebih mudah, cepat, dan rapi bersama **ELKA**!*
