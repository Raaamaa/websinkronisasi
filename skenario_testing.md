# Panduan Skenario Pengujian Manual (Manual Testing Guide)
## Perpustakaan Bukti Dukung BPS Kabupaten Tegal (ELKA)

Panduan ini membantu penguji mencoba seluruh alur kerja utama **ELKA** sesuai kondisi akhir aplikasi pada 3 peran pengguna (**Pegawai**, **Admin**, dan **Super Admin**).

---

## 1. Akun & Alamat Uji Coba

* **URL Login Utama:** `http://localhost:8000/login`
* **URL Simulasi SSO (khusus `local`/`testing`):** `http://localhost:8000/auth/sso/mock-login`

| Peran | Cara Masuk Saat Uji Coba | Redirect Utama | Menu yang Tersedia |
|---|---|---|---|
| **Pegawai** | `/auth/sso/mock-login` ➔ **Masuk sebagai Pegawai** | `/admin/cari-dokumen` | **Cari Dokumen**, **Unggah Bukti** |
| **Admin** | `/auth/sso/mock-login` ➔ **Masuk sebagai Admin** | `/admin/cari-dokumen` | **Cari Dokumen**, **Unggah Bukti**, **Antrean Verifikasi** |
| **Super Admin** | Form lokal di `/login` (akun seeder / lokal) | `/admin/verification-queue` | **Antrean Verifikasi**, **Manajemen Pengguna**, **Jejak Audit** |

---

## 2. Skenario 1: Login, Redirect Peran, & Pembatasan Akses (RBAC)

1. **Ganti Password Pertama Super Admin:**
   * Masuk di `/login` memakai akun awal Super Admin (`must_change_password = true`).
   * **Hasil:** Diarahkan ke `/auth/password/change` sampai kata sandi baru disimpan, lalu masuk ke `/admin/verification-queue`.
2. **Blokir Login Lokal Pegawai & Akun Nonaktif:**
   * Masukkan email Pegawai di form password lokal `/login` ➔ **Ditolak** (wajib SSO).
   * Buka `/auth/sso/mock-login` ➔ klik **Masuk sebagai Akun Nonaktif** ➔ **Ditolak (HTTP 403)**.
3. **Pembatasan URL & Menu per Peran:**
   * **Pegawai:** Mengakses `/admin/cari-dokumen` dan `/admin/evidence-documents` = **200 OK**. Mengakses `/admin/verification-queue`, `/admin/users`, atau `/admin/audit-logs` = **403 Forbidden**.
   * **Admin:** Mengakses `/admin/cari-dokumen`, `/admin/evidence-documents`, dan `/admin/verification-queue` = **200 OK**. Mengakses `/admin/users` atau `/admin/audit-logs` = **403 Forbidden**.
   * **Super Admin:** Mengakses `/admin/verification-queue`, `/admin/users`, dan `/admin/audit-logs` = **200 OK**. Mengakses `/admin/cari-dokumen` atau `/admin/evidence-documents` = **403 Forbidden** (menu Unggah Bukti dan Cari Dokumen tidak tampil di sidebar).
4. **Redirect `/admin`:** Mengakses `GET /admin` oleh Pegawai/Admin mengarah ke `/admin/cari-dokumen`; oleh Super Admin mengarah ke `/admin/verification-queue`.

---

## 3. Skenario 2: Cari Dokumen, Matriks 6 LK, & Cek Berkas Sebelum Unggah

1. **Matriks 6 LK & Klik Centang Unduhan (`/admin/cari-dokumen`):**
   * Masuk sebagai **Pegawai** atau **Admin**.
   * Pastikan tampil tepat **6 kolom LK** (`KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, `DATA_SEKUNDER`).
   * Hanya dokumen berstatus `'disetujui'` dengan tautan resmi (`admin`/`drive_sync`/`backfill`) yang mendapat centang (**✔**); tautan `'proposed'` tidak memunculkan centang; sel tanpa kecocokan kosong bersih.
   * Klik simbol **✔** pada salah satu baris ➔ bagian unduhan terbuka menampilkan **Unduh Dokumen** (`/evidence/download/{id}`) dan/atau **Buka Tautan** (`rel="noopener noreferrer"`).
2. **Cek Berkas Sebelum Unggah (`checkUploadedFile`):**
   * **Berkas Identik:** Pilih berkas PDF yang isinya persis sama (SHA-256 identik) dengan dokumen terdaftar ➔ klik **Periksa Berkas** ➔ muncul kotak merah **Ditolak — Dokumen Sudah Ada** beserta **Judul**, **Pengunggah**, **Status**, dan **Lembar Kerja**; tidak ada baris baru di `evidence_documents`.
   * **Berkas Mirip:** Pilih berkas baru yang judul atau isinya mirip dengan dokumen di perpustakaan ➔ muncul peringatan kuning non-blocking (maksimal 5 kandidat + skor `%`) beserta tombol **Tetap Lanjutkan ke Halaman Unggah**.
   * **Berkas Baru:** Pilih berkas unik yang belum ada ➔ muncul kotak hijau *"Dokumen belum ada di perpustakaan. Silakan unggah."* beserta tombol ke `/admin/evidence-documents/create`. Berkas sementara langsung dihapus dari disk.
3. **Ubah LK dari Hasil Cari (Khusus Admin):**
   * Sebagai **Admin**, klik **Ubah LK** pada satu baris dokumen disetujui. Coba simpan tanpa memilih LK ➔ **Gagal** (wajib $\ge 1$ LK). Pilih LK baru lalu simpan ➔ centang matriks berpindah seketika dan tercatat di `audit_logs` (`bd.markup_revised`).

---

## 4. Skenario 3: Unggah Bukti (Maks 20 MB), Tautan Google Drive, & Revisi

1. **Panduan > 20 MB & Wajib $\ge 1$ Usul LK:**
   * Buka `/admin/evidence-documents/create` ➔ pastikan tampak panduan *"Berkas lebih dari 20 MB (mis. video): unggah dulu ke Google Drive, lalu kirim tautannya."*
   * Coba kirim tanpa memilih LK ➔ **Ditolak validasi** (wajib $\ge 1$ LK).
2. **Unggah Berkas Fisik ($\le 20\text{ MB}$):**
   * Unggah PDF/DOCX/XLSX/JPG/PNG $\le 20\text{ MB}$ + pilih minimal 1 LK ➔ status awal **Menunggu Verifikasi** (`proposed`).
   * Berkas $> 20\text{ MB}$ atau file `.exe` yang diganti nama menjadi `.pdf` ➔ **Ditolak**.
3. **Mode Tempel Tautan (Untuk Berkas $> 20\text{ MB}$):**
   * Pilih **Tempel Tautan**, masukkan URL Google Drive `https://...`. Tautan berbahaya seperti `javascript:alert(1)` ➔ **Ditolak**.
4. **Revisi Khusus Pengunggah Asli:**
   * Di tabel **Unggah Bukti**, tombol **Revisi** hanya tampil pada baris milik pengunggah asli yang berstatus **Perlu Revisi**. Setelah diperbarui dan disimpan, status kembali menjadi **Menunggu Verifikasi**.

---

## 5. Skenario 4: Antrean Verifikasi & Banner Storage Super Admin

1. **Banner Storage $\ge 80\%$:**
   * Saat pemakaian disk $\ge$ `STORAGE_WARNING_THRESHOLD_PERCENT`, buka `/admin/verification-queue`:
     * **Super Admin:** melihat banner merah **Peringatan Kapasitas Penyimpanan Disk Server**.
     * **Admin:** tidak melihat banner tersebut.
2. **Keputusan Verifikator:**
   * **Setujui Dokumen** tanpa mencentang LK ➔ **Ditolak** (wajib $\ge 1$ LK). Centang minimal 1 LK lalu **Setujui Dokumen** ➔ status menjadi **Disetujui** dan centang muncul di matriks **Cari Dokumen**.
   * **Minta Revisi** atau **Tolak Pengajuan** tanpa catatan ➔ **Ditolak** (catatan wajib diisi).

---

## 6. Skenario 5: Unduhan Aman, Manajemen Pengguna, & Jejak Audit

1. **Format Nama Unduhan (`/evidence/download/{id}`):**
   * Dokumen belum disetujui (diunduh pemiliknya) ➔ awalan **`DRAFT_`**.
   * Dokumen disetujui dengan LK `ZI` dan `KIP` ➔ awalan **`KIP_ZI_`** (maksimal 4 kode urut abjad, panjang total $\le 150$ karakter, ekstensi utuh).
   * Pegawai lain mengunduh dokumen milik Pegawai A yang masih `menunggu` ➔ **403 Forbidden**.
2. **Super Admin — Manajemen Pengguna & Jejak Audit:**
   * Di `/admin/users`, menonaktifkan atau menurunkan role satu-satunya Super Admin aktif terakhir ➔ **Ditolak**.
   * Di `/admin/audit-logs`, seluruh aktivitas terekam kronologis tanpa tombol ubah/hapus dan dapat diekspor ke CSV.
