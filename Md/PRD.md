# PRD — Perpustakaan Bukti Dukung BPS Kabupaten Tegal (ELKA)

| Atribut | Nilai |
|---|---|
| Versi Dokumen | 3.0 |
| Tanggal | 17 November 2026 |
| Instansi | BPS Kabupaten Tegal |
| Status | FINAL — Selaras dengan Implementasi Produksi (Fase 1 s.d. Fase 14) |
| Dokumen Turunan | `DATABASE.md`, `RBAC.md`, `MATCHING_ALGORITHM.md`, `ARCHITECTURE.md`, `TASKS.md`, `AGENTS.md` |

---

## 1. Latar Belakang & Konsep Produk ("Perpustakaan Bukti Dukung")

**ELKA** adalah aplikasi web internal **BPS Kabupaten Tegal** yang dirancang sebagai **Perpustakaan Bukti Dukung**:
* **6 Lembar Kerja (LK) = Rak Penyimpanan:** `KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, dan `DATA_SEKUNDER` (disiapkan satu arah via `InitialLkSeeder`).
* **Dokumen Bukti Dukung = Buku:** Surat Keputusan (SK), SOP, laporan kegiatan, surat edaran, dan data/statistik kantor yang dapat masuk ke satu atau beberapa rak LK sekaligus.
* **Sederhana & Ramah Pengguna Lansia:** Tingkat indikator dihilangkan dari alur kerja aplikasi agar pencarian, pengecekan duplikat, unggah, dan verifikasi fokus pada **Dokumen $\leftrightarrow$ 6 Rak LK** dengan antarmuka bertema hangat, huruf $\ge 17\text{ px}$, dan tombol `min-height: 56px`.

### Tujuan Utama (G1–G5)

| ID | Tujuan | Ukuran Keberhasilan |
|---|---|---|
| **G1** | Pegawai dan Admin dapat mencari dokumen disetujui sekaligus melihat matriks 6 LK dalam satu halaman | Halaman **Cari Dokumen** (`CariDokumenPage`) menampilkan kotak cari, filter, matriks Dokumen x 6 LK (`DISTINCT lk_id`), dan klik centang (✔) membuka bagian unduh |
| **G2** | Mencegah berkas kembar sebelum maupun saat mengunggah | Fitur **Cek Berkas Sebelum Unggah** (`checkUploadedFile`) memeriksa hash SHA-256 (tolak identik + detail) dan kemiripan (maks 5 + skor non-blocking) tanpa menyimpan berkas ke server |
| **G3** | Alur unggah dan verifikasi terstruktur: unggah/tautan ➔ menunggu ➔ verifikasi ➔ disetujui | Unggah berkas maks **20 MB** atau tautan Google Drive untuk $> 20\text{ MB}$ dengan **wajib $\ge 1$ usul LK**; verifikator memutus Setujui ($\ge 1$ LK), Minta Revisi (wajib catatan), atau Tolak (wajib catatan) |
| **G4** | Akses dibatasi ketat di sisi server sesuai 3 peran (`pegawai`, `admin`, `super_admin`) | Navigasi, policy, middleware, dan redirect pasca-login (`User::homeUrl()`) menegakkan batas tiap peran (HTTP 403 untuk akses lintas wewenang) |
| **G5** | Unduhan berkas terstandar & aman | Berkas unggahan yang diunduh otomatis diberi awalan kode LK resmi (maks 4 kode urut abjad, atau `DRAFT` bila belum resmi, panjang $\le 150$ karakter dengan ekstensi utuh) |

---

## 2. Pengguna dan Peran (3 Role)

Rincian izin granular terdapat pada [`Md/RBAC.md`](RBAC.md).

1. **Pegawai (`pegawai`) — Menu: *Cari Dokumen* & *Unggah Bukti*:**
   * Masuk melalui SSO BPS (`BpsKeycloakOidcProvider`). Saat login pertama kali, akun dibuat otomatis (*auto-provisioning*).
   * Pasca-login diarahkan ke `/admin/cari-dokumen`.
   * Di **Cari Dokumen**: mencari dokumen `disetujui`, melihat matriks 6 LK, mengklik centang (✔) untuk mengunduh/membuka tautan, mengecek berkas sebelum unggah, dan melihat ringkasan status pengajuan miliknya tanpa centang di matriks resmi.
   * Di **Unggah Bukti**: mengirim berkas fisik ($\le 20\text{ MB}$) atau tautan eksternal (untuk berkas $> 20\text{ MB}$ / video), wajib memilih $\ge 1$ LK usulan, serta merevisi pengajuan miliknya saat status `'revisi'`.
2. **Admin (`admin`) — Menu: *Cari Dokumen*, *Unggah Bukti*, & *Antrean Verifikasi*:**
   * Memiliki seluruh fungsi Pegawai dan pasca-login diarahkan ke `/admin/cari-dokumen`.
   * Di **Cari Dokumen**: dapat mengubah pilihan LK resmi dokumen yang sudah `'disetujui'` langsung melalui tombol **Ubah LK** (`reviseMarkup`, wajib $\ge 1$ LK).
   * Di **Antrean Verifikasi** (`/admin/verification-queue`): memeriksa pengajuan baru dan memutus **Setujui Dokumen** (wajib $\ge 1$ LK), **Minta Revisi** (wajib catatan), atau **Tolak Pengajuan** (wajib catatan).
   * Berwenang mengaktifkan/menonaktifkan akun ber-role `pegawai`.
3. **Super Admin (`super_admin`) — Menu: *Antrean Verifikasi*, *Manajemen Pengguna*, & *Jejak Audit*:**
   * Akun awal dibuat lewat `SuperAdminSeeder` (login lokal) dan wajib mengganti password saat login pertama (`must_change_password = true`).
   * Pasca-login diarahkan ke `/admin/verification-queue`, lengkap dengan **Banner Peringatan Kapasitas Penyimpanan Disk Server** (muncul khusus Super Admin saat pemakaian disk $\ge 80\%$; Admin tidak melihat banner ini).
   * Mengelola akun pengguna dan role (`Pegawai ↔ Admin`) di **Manajemen Pengguna** (`/admin/users`), memantau/mengekspor **Jejak Audit** (`/admin/audit-logs`), serta satu-satunya peran yang berhak melakukan *soft-delete* dokumen (`evidence.delete`).
   * **Tidak** mengakses menu maupun halaman **Cari Dokumen** (`/admin/cari-dokumen` = 403) dan **Unggah Bukti** (`/admin/evidence-documents` = 403).

---

## 3. Kebutuhan Fungsional Utama

### 3.1 Autentikasi, Sesi, & Redirect Peran (AUTH)
* **AUTH-01 & AUTH-02:** Portal login terpadu di `/login` mendukung SSO Keycloak BPS dan login lokal pengelola.
* **AUTH-03 & AUTH-04:** Akun Super Admin seeder dikarantina di `/auth/password/change` sampai kata sandi awal diganti.
* **AUTH-05 & AUTH-06:** Hanya Super Admin yang dapat mengubah role pengguna; satu-satunya Super Admin aktif terakhir dilindungi dari penonaktifan, penghapusan, dan penurunan role.
* **AUTH-07 & AUTH-08:** Penonaktifan akun (`is_active = false`) memutus sesi aktif seketika di sisi server pada setiap request.
* **Redirect `User::homeUrl()`:** Pegawai dan Admin diarahkan ke `/admin/cari-dokumen`; Super Admin diarahkan ke `/admin/verification-queue` (termasuk saat mengakses `/`, `/admin`, atau pasca-login/SSO/ganti password).

### 3.2 Lembar Kerja (6 Rak via Seeder)
* **LK-01 s.d. LK-06:** Tepat 6 LK aktif dibuat oleh `InitialLkSeeder` (`period = 2026`, `default_profile_id = null`):
  * **Internal (4):** `KIP` (`3 Bulan`), `PEKPPP` (`6 Bulan`), `ZI` (`12 Bulan`), `SAKIP` (`12 Bulan`).
  * **Eksternal (2):** `DATA_SEKTORAL` (`1 Bulan`), `DATA_SEKUNDER` (`1 Bulan`).

### 3.3 Unggah Bukti, Cek Berkas, Revisi, & Unduhan Aman (BD)
* **BD-01 & BD-02 (Batas 20 MB & Panduan Google Drive):** Batas ukuran unggah berkas fisik adalah **20 MB** (`config('evidence.max_file_size_mb', 20)`). Form menampilkan panduan jelas: *"Berkas lebih dari 20 MB (mis. video): unggah dulu ke Google Drive, lalu kirim tautannya."*
* **BD-03 & BD-04 (Validasi & Hash):** MIME *magic bytes* sniffing (`finfo` + inspeksi ZIP untuk DOCX/XLSX) dan kalkulasi hash SHA-256.
* **BD-05 (Deduplikasi & Cek Berkas Sebelum Unggah):**
  * Hash SHA-256 yang identik dengan dokumen aktif ditolak (`DuplicateDocumentException`).
  * Di halaman **Cari Dokumen**, `checkUploadedFile()` memeriksa berkas sementara tanpa membuat baris baru di `evidence_documents` dan selalu menghapus berkas sementara di blok `finally`:
    * **Hash Identik:** Kotak merah penolakan disertai judul, pengunggah, status, dan daftar LK resmi.
    * **Kandidat Mirip:** Peringatan kuning non-blocking maksimal 5 kandidat + skor kemiripan (`%`) beserta tautan ke halaman Unggah.
    * **Belum Ada:** Kotak hijau menyarankan unggah dengan tautan ke `/admin/evidence-documents/create`.
* **BD-06 (Wajib Usul LK & Alur Revisi):** Pengajuan baru wajib memilih minimal 1 LK (`lk_ids` disimpan sebagai `proposed`). Tombol **Revisi** di tabel **Unggah Bukti** hanya tampil bagi pengunggah asli saat status `'revisi'`.
* **BD-10 (Unduhan Aman Berawalan Kode LK Resmi):** Endpoint `GET /evidence/download/{id}` men-stream berkas dari disk `private` dengan awalan maks 4 kode LK resmi (`admin`, `drive_sync`, `backfill`; atau `DRAFT`), panjang $\le 150$ karakter, atau me-redirect tautan eksternal `http`/`https` yang valid.

### 3.4 Pencarian & Matriks 6 LK Gabungan, Verifikasi, & Audit (SEARCH, VERIFY, AUDIT)
* **SEARCH & MATRIKS (`CariDokumenPage`):** Menggabungkan pencarian FTS bahasa Indonesia + `ILIKE` + trigram dengan tabel matriks 6 LK (`DISTINCT lk_id`). Tanda centang (**✔**) hanya tampil untuk dokumen `'disetujui'` dengan link resmi (`DocumentLkLink::OFFICIAL_SOURCES`). Mengklik centang memanggil `toggleDownloadSection` untuk membuka tombol **Unduh Dokumen** dan/atau **Buka Tautan** (`rel="noopener noreferrer"`).
* **AUDIT (`AuditLogResource`):** Seluruh mutasi data tercatat di `audit_logs` (*append-only*, dilindungi trigger PostgreSQL) dan dapat diekspor ke CSV oleh Super Admin.

---

## 4. Fitur yang Telah Disederhanakan / Dihapus

Agar sesuai kebutuhan operasional BPS Kabupaten Tegal yang sederhana:
* Sinkronisasi API Google Drive otomatis dan OCR Drive di server telah dihapus; berkas $> 20\text{ MB}$ dikirim sebagai tautan eksternal biasa.
* Mesin pencocokan dokumen ke indikator dan layar CRUD Master LK / Indikator / Impor Excel telah dihapus; sistem kini berfokus pada 6 LK rak perpustakaan.
* Halaman `MonitorMatrixPage` terpisah dan ekspor CSV matriks telah digabung langsung ke **Cari Dokumen** (`CariDokumenPage`), sedangkan peringatan disk $\ge 80\%$ dipindahkan ke **Antrean Verifikasi** khusus Super Admin.

---

## 5. Kriteria Penerimaan & Verifikasi Otomatis

Seluruh kriteria penerimaan diverifikasi otomatis melalui suite PHPUnit (**211 tes lulus, 0 gagal, 0 dilewati**):
* Penegakan RBAC server-side 3 peran, redirect `/admin` dan pasca-login per peran, karantina Super Admin seeder, proteksi Super Admin terakhir, serta pemutusan sesi akun nonaktif seketika.
* Deduplikasi hash SHA-256, batas 20 MB (`20.971.520` byte), MIME sniffing, ekstraksi teks PDF/DOCX/OCR di `queue-worker`, dan unduhan aman berkode LK resmi.
* Matriks 6 LK (`DISTINCT lk_id`), klik centang membuka unduhan, cek berkas sementara (`finally` cleanup tanpa baris DB), ubah LK Admin di Cari Dokumen, serta aksesibilitas tombol $\ge 56\text{ px}$ dan kontras WCAG AA ($\ge 4.5:1$).
