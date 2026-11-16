# Panduan Agent & Pengembang (AGENTS.md)
## Sistem Matching Bukti Dukung ke Lembar Kerja (LK)

Dokumen ini merupakan panduan utama bagi AI Coding Agent dan pengembang yang bekerja pada repositori **Websinkronisasi (ELKA)**. Semua instruksi, batasan arsitektur, dan standar kode di bawah ini **wajib dipatuhi** demi mencegah kesalahan fatal (*zero fatal mistake*) dalam eksekusi proyek.

---

## 1. Konteks Proyek & Dokumen Acuan

Websinkronisasi (ELKA) adalah aplikasi web internal kantor untuk mengumpulkan, memverifikasi, mencari, dan memetakan dokumen Bukti Dukung (SK, SOP, laporan, data) ke berbagai Lembar Kerja (LK) penilaian secara terverifikasi.

Sebelum menulis atau memodifikasi kode, agen **wajib membaca dan merujuk** berkas-berkas spesifikasi berikut yang berada di direktori [`Md/`](file:///home/hashirama/websinkronisasi/Md):

| Dokumen | Deskripsi & Cakupan |
|---|---|
| [**`Md/PRD.md`**](file:///home/hashirama/websinkronisasi/Md/PRD.md) | Kebutuhan produk lengkap, alur pengajuan-verifikasi-persetujuan, pencarian dokumen, matriks monitor Dokumen x LK, sinkronisasi Google Drive, dan 27 Kriteria Penerimaan (AC-01 s.d. AC-27). |
| [**`Md/DATABASE.md`**](file:///home/hashirama/websinkronisasi/Md/DATABASE.md) | Skema DDL PostgreSQL lengkap, kolom persetujuan & Drive pada `evidence_documents`, tabel `document_lk_links`, `drive_sync_folders`, tabel saran `document_matches`, dan trigger imutabilitas audit. |
| [**`Md/RBAC.md`**](file:///home/hashirama/websinkronisasi/Md/RBAC.md) | Matriks hak akses 3 role (`super_admin`, `admin`, `pegawai`) sesuai `User::hasPermission`, kebijakan akses dokumen (`EvidenceDocumentPolicy`), SSO abstraction interface, dan pipeline middleware server-side. |
| [**`Md/MATCHING_ALGORITHM.md`**](file:///home/hashirama/websinkronisasi/Md/MATCHING_ALGORITHM.md) | Formula pencocokan saran indikator (`exact_code`, `exact_number`, Trigram + Keyword), ambang skor berbasis `config('evidence.*')`, dan perlindungan kekekalan penolakan (`status = 'rejected'`). |
| [**`Md/ARCHITECTURE.md`**](file:///home/hashirama/websinkronisasi/Md/ARCHITECTURE.md) | Arsitektur sistem berlapis (Layered Clean Architecture), alur unggah-verifikasi, sinkronisasi Drive & OCR selektif, unduhan aman berawalan kode LK resmi, dan struktur folder. |
| [**`Md/TASKS.md`**](file:///home/hashirama/websinkronisasi/Md/TASKS.md) | Rincian tahapan implementasi, fitur aktif vs fitur yang telah dipangkas, dan gerbang verifikasi (*verification gates*). |

---

## 2. Standar Teknologi & Konvensi Kode

### 2.1 Stack Resmi Proyek
* **Backend:** PHP 8.3+ / Laravel 11
* **Panel Admin & UI:** Filament v3 (Livewire 3 + Alpine.js + Tailwind CSS)
* **Basis Data:** PostgreSQL 15+ (Ekstensi: `pgcrypto`, `pg_trgm`, `unaccent`)
* **Queue / Asinkron:** Redis 7+
* **Dokumen Parser:** `smalot/pdfparser`, `phpoffice/phpspreadsheet`
* **OCR:** Tesseract OCR v5+ dengan bahasa Indonesia (`ind`)

### 2.2 Konvensi Penulisan Kode PHP & Laravel
* Wajib menggunakan deklarasi tipe ketat di setiap file PHP:
  ```php
  declare(strict_types=1);
  ```
* Seluruh method, parameter, dan return type **wajib memiliki type-hinting eksplisit**.
* Jangan menulis query SQL mentah yang tidak terparameter. Gunakan Eloquent ORM atau Query Builder berparameter untuk mencegah SQL Injection (SEARCH-05).
* Jangan menyimpan rahasia/kredensial di kode sumber. Selalu gunakan `config()` yang membaca dari environment variables (`.env`), khususnya `config('evidence.*')` dan `config('services.*')`.
* Validasi file fisik: Jangan pernah percaya ekstensi file semata. Selalu lakukan MIME-type sniffing berbasis *magic bytes* (BD-03). Batas ukuran unggah bukti dukung adalah **50 MB** (`config('evidence.max_file_size_mb')`); berkas lebih besar diajukan melalui mode tautan (`source_type = 'link'`).
* Nama file penyimpanan fisik wajib menggunakan acak UUID di folder privat `storage/app/private/evidence/` (NFR-04, BD-10). Saat diunduh, nama berkas dibentuk otomatis dengan awalan kode LK resmi (maksimal 4 kode urut abjad, atau `DRAFT` bila belum memiliki link resmi) dan panjang total maksimal 150 karakter dengan ekstensi utuh.

---

## 3. Delapan Aturan Invarian Bisnis yang Tidak Boleh Dilanggar

1. **Penegakan Hak Akses di Sisi Server (NFR-01):**
   Jangan pernah hanya mengandalkan penyembunyian tombol di antarmuka Blade/Filament. Setiap request HTTP, controller, halaman Livewire, dan aksi Filament **wajib** divalidasi oleh middleware/policy otorisasi server-side.
2. **Kekekalan Penolakan Admin (MATCH-06, AC-11):**
   Jika relasi di `document_matches` berstatus `rejected`, matching worker di masa mendatang **dilarang keras** menimpa atau membuat ulang relasi tersebut. Demikian pula pada sinkronisasi ulang Google Drive, berkas yang sudah ada tidak boleh menghidupkan kembali tautan `document_lk_links` yang telah dihapus admin.
3. **Aturan Tanda Centang Matriks Monitor (MON-02):**
   Tanda centang (**✔**) di Matriks Monitor **hanya** tampil untuk dokumen berstatus `approval_status = 'disetujui'` yang memiliki tautan resmi di `document_lk_links` (`link_source` IN `'admin'`, `'drive_sync'`, `'backfill'`). Tautan usulan (`link_source = 'proposed'`) **bukan** centang. *Pengecualian:* dokumen baru hasil sinkronisasi Google Drive pada folder yang terhubung ke LK (`drive_sync_folders.lk_id IS NOT NULL`) otomatis berstatus `'disetujui'` dengan `link_source = 'drive_sync'`. Sel tanpa kecocokan resmi **wajib dikosongkan murni**.
4. **Proteksi Super Admin Aktif Terakhir (AUTH-06, AC-04):**
   Sistem dilarang mengizinkan penonaktifan, penghapusan, atau penurunan role dari satu-satunya Super Admin aktif yang tersisa.
5. **Keutuhan Jejak Audit (AUDIT-02):**
   Tabel `audit_logs` bersifat *append-only* dan dilindungi trigger PostgreSQL `prevent_audit_logs_mutation`. Dilarang menyediakan fungsi/endpoint untuk mengedit atau menghapus rekaman audit.
6. **Keamanan Tautan Bukti (LK-09, AC-25, MON-10):**
   Tautan bukti pada indikator maupun dokumen hanya menerima skema `http://` dan `https://`. Skema berbahaya (`javascript:`, `file:`, `data:`, dll.) wajib ditolak saat impor, unggah tautan, maupun redirect unduhan. Tautan keluar wajib memiliki `rel="noopener noreferrer"`.
7. **Sinkronisasi Google Drive Baca-Saja (*Read-Only Drive Sync*):**
   Integrasi Google Drive API v3 bersifat murni baca-saja (*read-only*). Sistem **tidak pernah menulis, mengubah, atau menghapus** berkas/folder di Google Drive. Kunci API wajib dibaca hanya dari `config('evidence.drive_api_key')`.
8. **Kewajiban Ganti Password Pertama Super Admin Seeder (AUTH-03, AC-03):**
   Akun Super Admin awal yang dibuat melalui `SuperAdminSeeder` wajib memiliki `must_change_password = true` dan dikarantina oleh middleware `EnsurePasswordChanged` hingga kata sandi baru disimpan sebelum dapat mengakses fitur aplikasi lainnya.

---

## 4. Penggunaan Skill Agen dalam Proyek

Repositori ini telah dilengkapi dengan skill profesional di [`.agents/skills/`](file:///home/hashirama/websinkronisasi/.agents/skills). Agen yang bekerja pada proyek ini wajib memanfaatkan skill yang relevan sesuai jenis tugas:

| Kebutuhan Tugas | Skill yang Wajib Digunakan | Peran & Alasan |
|---|---|---|
| **Pengerjaan Fitur / Perbaikan Bug** | `/tdd` | Terapkan siklus *Red-Green-Refactor*. Tulis feature/unit test terlebih dahulu mengacu pada skenario AC-01 s.d. AC-27 sebelum menulis kode implementasi. |
| **Menulis Kode Minimal & Efisien** | `/ponytail` | Mencegah *over-engineering* atau penambahan dependensi yang tidak perlu. Tulis perubahan kode terkecil yang sepenuhnya menyelesaikan masalah dengan aman. |
| **Review & Verifikasi Kode** | `/code-review` / `/ponytail-review` | Sebelum melakukan commit, jalankan review 2 sumbu: kepatuhan standar (*standards*) dan kesesuaian spesifikasi PRD (*spec*). |
| **Pembangunan UI & Tampilan** | `/antislop-ui` & `/antislop-human` | Mencegah tampilan generik buatan AI. Pastikan kontras warna terbaca, tata letak responsif, dan interaksi kontrol ramah pengguna nyata. |
| **Diagnosis Bug / Kegagalan Queue** | `/diagnosing-bugs` | Gunakan siklus terstruktur: feedback loop yang gagal ➔ minimalkan kasus ➔ hipotesis ➔ instrumentasi ➔ perbaikan ➔ regression test. |
| **Komunikasi Ringkas & Hemat Token** | `/caveman` | Gunakan mode ini bila diminta respons padat, langsung ke jawaban inti tanpa basa-basi seremonial. |

---

## 5. Alur Kerja Eksekusi Bertahap

Dalam mengimplementasikan dan memelihara proyek ini, ikuti urutan tahapan yang telah ditetapkan di [**`Md/TASKS.md`**](file:///home/hashirama/websinkronisasi/Md/TASKS.md):

1. **Fase 1:** Fondasi Proyek, Skema Pivot Persetujuan & Tautan LK (`document_lk_links`, `drive_sync_folders`), & Backfill.
2. **Fase 2:** Autentikasi SSO BPS, Akun Seeder Super Admin, & Penyelarasan RBAC (`EvidenceDocumentPolicy`).
3. **Fase 3:** Pencarian Dokumen Disetujui, Saran Pencocokan (`MatchingEngineService`), & Kemiripan Dokumen (`DocumentSimilarityService`).
4. **Fase 4:** Unggah Bukti Dukung (Maks 50 MB), Mode Tautan Eksternal, Usulan LK (`proposed`), & Alur Revisi Aman.
5. **Fase 5:** Antarmuka Sederhana (Pengajuan Saya, Antrean Verifikasi, Matriks Monitor Dokumen x LK + CSV, Cari Dokumen).
6. **Fase 6:** Sinkronisasi Google Drive Baca-Saja & OCR Selektif (`ProcessDriveFileOcrJob`).
7. **Fase 7:** Unduhan Aman Berawalan Kode LK Resmi (`EvidenceController::download`) & Pembersihan Kode Usang.
8. **Fase 8:** Sinkronisasi Dokumentasi Teknis & Panduan Pengguna.

> **Catatan Penting:** Jangan melompat ke fase berikutnya sebelum gerbang verifikasi (*verification gates*) pada fase sebelumnya lolos dengan sempurna dan telah dikonfirmasi oleh pengguna.
