# PRD — Sistem Matching Bukti Dukung ke Lembar Kerja (LK)

| Atribut | Nilai |
|---|---|
| Versi Dokumen | 2.0 |
| Tanggal | 16 November 2026 |
| Penulis | Akhmad Rizki Ramadhani |
| Status | FINAL — Selaras dengan Implementasi Produksi (Fase 1 s.d. Fase 8) |
| Dokumen Turunan | `DATABASE.md`, `RBAC.md`, `MATCHING_ALGORITHM.md`, `ARCHITECTURE.md`, `TASKS.md`, `AGENTS.md` |

---

## 1. Latar Belakang & Tujuan Produk

Pegawai kantor memerlukan dokumen Bukti Dukung (SK, SOP, laporan kinerja, data, dan sejenisnya) untuk memenuhi berbagai Lembar Kerja (LK) penilaian internal dan eksternal (ZI, PEKPPP, KIP, SAKIP, Data Sekunder, Data Sektoral). Satu dokumen bukti dukung sering dipakai pada beberapa Lembar Kerja sekaligus.

### Tujuan Utama (G1–G6)

| ID | Tujuan | Ukuran Keberhasilan |
|---|---|---|
| **G1** | Pegawai dan Admin dapat mencari dokumen yang telah disetujui dan langsung melihat LK resmi terkait | Halaman **Cari Dokumen** (`CariDokumenPage`) dan **Matriks Monitor** (`MonitorMatrixPage`) menampilkan badge/centang LK resmi dalam satu layar |
| **G2** | Alur pengajuan bukti dukung terstruktur: unggah/tautan ➔ menunggu ➔ verifikasi admin ➔ disetujui | Pengajuan baru berstatus `menunggu` dengan usulan LK (`proposed`), disertai saran indikator otomatis dari mesin pencocokan |
| **G3** | Verifikasi terpusat & integrasi Google Drive baca-saja | Admin memverifikasi pengajuan di Antrean Verifikasi (setujui, minta revisi, atau tolak) serta dapat menyinkronkan folder Google Drive secara baca-saja dengan OCR selektif |
| **G4** | Akses dibatasi ketat di sisi server sesuai 3 peran (`super_admin`, `admin`, `pegawai`) | Middleware dan Policy menolak setiap akses lintas wewenang dengan HTTP 403/404 |
| **G5** | Unduhan berkas terstandar & aman | Berkas unggahan yang diunduh otomatis diberi awalan kode LK resmi (maks 4 kode urut abjad, atau `DRAFT` bila belum resmi, panjang $\le 150$ karakter dengan ekstensi utuh) |
| **G6** | Master LK dan indikator dapat diimpor secara idempoten dari file Excel | Super Admin dapat mengimpor file `.xlsx` dengan pratinjau wajib tanpa menggandakan data |

---

## 2. Pengguna dan Peran (3 Role)

Rincian izin granular terdapat pada [`Md/RBAC.md`](file:///home/hashirama/websinkronisasi/Md/RBAC.md).

1. **Pegawai (`pegawai`):**
   * Masuk melalui SSO BPS (`BpsKeycloakOidcProvider`). Saat login pertama kali, akun dibuat otomatis (*auto-provisioning*).
   * Mencari dokumen resmi yang telah berstatus `disetujui` pada menu **Cari Dokumen**.
   * Mengajukan bukti dukung baru melalui menu **Unggah & Pengajuan Saya**:
     * Unggah berkas fisik (PDF, DOCX, XLSX, JPG, PNG) hingga batas **50 MB**.
     * Tempel tautan eksternal (`http://` atau `https://`) untuk berkas berukuran $> 50\text{ MB}$ atau video/tautan Drive.
   * Melihat daftar pengajuan miliknya sendiri dan melakukan revisi apabila status pengajuan dikembalikan menjadi `revisi` oleh Admin.
   * Melihat **Matriks Monitor** (Dokumen x LK), mengunduh CSV matriks, serta mengunduh berkas yang telah `disetujui` (atau pengajuan miliknya sendiri).
2. **Admin (`admin`):**
   * Memiliki kewenangan operasional Pegawai (cari dokumen, unggah/ajukan bukti dukung, lihat matriks monitor).
   * Memeriksa dan memutus pengajuan pada **Antrean Verifikasi** (`VerificationQueuePage`): menyetujui dokumen ke satu atau lebih LK resmi (`link_source = 'admin'`), meminta revisi beserta catatan, atau menolak pengajuan.
   * Mengelola daftar folder Google Drive dan memicu **Sinkronisasi Google Drive** (`drive.sync`), serta mengelola tautan Google Drive (`evidence_link`) pada indikator LK.
   * Mengaktifkan atau menonaktifkan akun ber-role `pegawai`.
3. **Super Admin (`super_admin`):**
   * Akun awal dibuat lewat `SuperAdminSeeder` (login lokal) dan wajib mengganti password saat login pertama (`must_change_password = true`).
   * Mengelola master Lembar Kerja, profil impor & wizard impor Excel, master indikator LK, manajemen pengguna & perubahan role (`Pegawai ↔ Admin`), serta melihat/mengekspor **Jejak Audit** (`AuditLogResource`).
   * Ikut memverifikasi pengajuan di **Antrean Verifikasi** (`evidence.verify`) dan satu-satunya peran yang berwenang melakukan *soft-delete* dokumen (`evidence.delete`).
   * **Tidak** mengunggah bukti dukung, **tidak** membuka halaman Cari Dokumen, dan **tidak** menjalankan sinkronisasi Google Drive.

---

## 3. Kebutuhan Fungsional Utama

### 3.1 Autentikasi & Sesi (AUTH)
* **AUTH-01 & AUTH-02:** Portal login terpadu di `/login` mendukung SSO Keycloak BPS dan login lokal pengelola.
* **AUTH-03 & AUTH-04:** Akun Super Admin seeder membaca kredensial dari `.env` dan dikarantina di `/auth/password/change` sampai kata sandi awal diganti.
* **AUTH-05 & AUTH-06:** Hanya Super Admin yang dapat mengubah role pengguna. Sistem melindungi satu-satunya Super Admin aktif terakhir dari penonaktifan, penghapusan, atau penurunan role.
* **AUTH-07 & AUTH-08:** Penonaktifan akun (`is_active = false`) memutus sesi aktif seketika di sisi server pada setiap request.

### 3.2 Master Lembar Kerja & Impor Excel (LK & LKIMP)
* **LK-01 s.d. LK-06:** 6 LK awal disediakan via `InitialLkSeeder` (`ZI`, `PEKPPP`, `KIP`, `SAKIP`, `DATA_SEKUNDER`, `DATA_SEKTORAL`) dengan kategori `internal`/`eksternal`, tahun `period`, dan `evaluation_interval` (`1 Bulan`, `3 Bulan`, `6 Bulan`, `12 Bulan`).
* **LKIMP-01 s.d. LKIMP-11:** Mendukung 3 strategi parsing Excel (`numbered_list_single_cell`, `checklist_sheet`, `single_item_per_row`), resolusi placeholder tahun (`[t]`, `[t-1]`, `[t-2]`), sanitasi tautan `http`/`https`, penolakan makro `.xlsm`, pratinjau wajib sebelum simpan, serta upsert idempoten.

### 3.3 Pengajuan Bukti Dukung, Revisi, & Unduhan Aman (BD)
* **BD-01 & BD-02 (Batas 50 MB & Mode Tautan):** Batas ukuran unggah berkas bukti dukung adalah **50 MB** (`config('evidence.max_file_size_mb')`). Untuk berkas berukuran lebih dari 50 MB, pengguna cukup mengajukan tautan eksternal (`source_type = 'link'`).
* **BD-03 & BD-04 (Validasi & Hash):** Validasi tipe berkas menggunakan MIME *magic bytes* sniffing (`finfo` + inspeksi struktur ZIP untuk DOCX/XLSX) dan kalkulasi hash SHA-256.
* **BD-05 (Deduplikasi & Peringatan Kemiripan):** Hash SHA-256 yang identik dengan dokumen aktif ditolak (`DuplicateDocumentException`). Saat mengisi form, `DocumentSimilarityService` menampilkan maksimal 5 kandidat dokumen yang mirip.
* **BD-06 (Alur Persetujuan & Revisi):** Pengajuan baru disimpan dalam satu transaksi DB (`EvidenceDocument` berstatus `menunggu` + `document_lk_links` berstatus `proposed`). Jika diminta revisi (`approval_status = 'revisi'`), pemilik dokumen dapat memperbarui metadata/berkas melalui `reviseSubmission()` yang menghapus berkas lama hanya setelah transaksi DB berhasil *commit*.
* **BD-10 (Unduhan Aman Berawalan Kode LK Resmi):**
  * Endpoint `GET /evidence/download/{id}` memvalidasi izin `evidence.view_link` dan `EvidenceDocumentPolicy::view` (dokumen `disetujui`, pemilik dokumen sendiri, atau Admin/Super Admin; dokumen *soft-deleted* = 404).
  * Dokumen `source_type` `'drive'` atau `'link'` dialihkan (`redirect`) secara aman ke `external_link` hanya bila skema `http`/`https` valid (selain itu 422).
  * Dokumen `source_type` `'upload'` di-stream dari disk `private` dengan nama berawalan kode LK resmi (`link_source` IN `admin`/`drive_sync`/`backfill`, `DISTINCT`, urut abjad, maks 4 kode dipisah `_`, atau `DRAFT` bila belum ada link resmi), disanitasi dari karakter ilegal/kontrol, dengan panjang total $\le 150$ karakter dan ekstensi utuh.

### 3.4 Mesin Pencocokan Saran & Sinkronisasi Google Drive (MATCH & DRIVE)
* **MATCH-01 s.d. MATCH-10 (Saran Indikator):** `MatchingEngineService` mengevaluasi dokumen terhadap indikator/item bukti LK (`exact_code`, `exact_number`, dan kemiripan heuristik Trigram + Keyword) dengan ambang dari `config('evidence.matching_suggest_threshold', 75.0)` (`auto`) dan `config('evidence.matching_review_threshold', 60.0)` (`perlu_verifikasi`). Hasil disimpan di `document_matches` sebagai **saran verifikasi** (bukan penentu centang matriks). Baris `rejected` tidak pernah ditimpa ulang.
* **DRIVE-01 s.d. DRIVE-04 (Sinkronisasi Drive Baca-Saja & OCR Selektif):** `GoogleDriveSyncService` membaca metadata berkas dari folder Drive aktif menggunakan `config('evidence.drive_api_key')`. Dokumen baru dari folder yang terhubung ke LK (`drive_sync_folders.lk_id`) otomatis berstatus `disetujui` dengan `link_source = 'drive_sync'`. Berkas PDF/gambar $\le 50\text{ MB}$ dengan nama generik diproses melalui `ProcessDriveFileOcrJob`.

### 3.5 Matriks Monitor, Cari Dokumen, & Audit Log (MON, SEARCH, AUDIT)
* **MON-01 s.d. MON-10 (Matriks Dokumen x LK + Unduh CSV):** Menampilkan dokumen berstatus `disetujui` pada baris dan LK pada kolom. Tanda centang (**✔**) tampil hanya jika ada tautan resmi di `document_lk_links` (`admin`, `drive_sync`, `backfill`); sel lainnya kosong murni. Dilengkapi fitur Unduh CSV dan peringatan kapasitas penyimpanan bagi Super Admin.
* **SEARCH-01 s.d. SEARCH-06 (Cari Dokumen):** Halaman khusus Pegawai dan Admin untuk mencari dokumen `disetujui` menggunakan FTS bahasa Indonesia, substring, dan trigram fuzzy beserta filter LK dan tahun.
* **AUDIT-01 s.d. AUDIT-03 (Jejak Audit Kekal):** Seluruh mutasi data tercatat di `audit_logs` yang bersifat *append-only* dan dapat diekspor ke CSV oleh Super Admin.

---

## 4. Fitur Warisan yang Telah Dipangkas

Seiring penyederhanaan alur kerja menjadi berbasis persetujuan dokumen-ke-LK, fitur-fitur warisan berikut telah dihapus dari sistem:
* Antrean verifikasi match per indikator lama & koreksi manual per indikator (`ManualMatchService`).
* Matriks monitor berbasis baris indikator & modal drilldown indikator.
* Panel uji simulasi kecocokan (`DryRunMatchingService`) dan `QuickWorkflowWidget`.
* Antarmuka pengaturan sistem di database (`SystemSettingResource`, `SystemSettingObserver`, `SystemSettingPolicy`); seluruh konfigurasi kini berbasis `config/evidence.php` dan `.env`.

---

## 5. Kriteria Penerimaan (AC-01 s.d. AC-27)

Seluruh kriteria penerimaan diverifikasi secara otomatis melalui suite pengujian PHPUnit (`305 passed`, `0 failed`):
* **AC-01 s.d. AC-04, AC-15, AC-18 s.d. AC-21:** Penegakan RBAC server-side, karantina password pertama Super Admin seeder, proteksi Super Admin aktif terakhir, pencabutan sesi akun nonaktif seketika, dan *auto-provisioning* SSO Pegawai.
* **AC-05 s.d. AC-07:** Deduplikasi hash SHA-256 aktif, penolakan manipulasi ekstensi lewat MIME *magic bytes* sniffing, penanganan PDF scan kosong (`perlu_review_manual`), batas unggah 50 MB + mode tautan, serta unduhan aman berawalan kode LK resmi.
* **AC-08 s.d. AC-12:** Matriks Monitor merender tanda ✔ hanya untuk dokumen `disetujui` dengan tautan LK resmi (`admin`/`drive_sync`/`backfill`), sel tanpa kecocokan kosong murni, dan kekekalan status `rejected` saat *re-matching*.
* **AC-13, AC-14, AC-17:** Keamanan pencarian FTS/trigram dari injeksi SQL dan input panjang, *soft-delete* LK, serta pencatatan audit log *append-only*.
* **AC-22 s.d. AC-27:** Akurasi dan idempotensi impor sampel Excel LK, penolakan makro `.xlsm` dan skema tautan berbahaya (`javascript:`, `file:`), resolusi placeholder `[t-1]`, serta pelestarian status match saat impor ulang.
