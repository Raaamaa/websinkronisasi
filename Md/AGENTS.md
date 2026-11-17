# Panduan Agent & Pengembang (AGENTS.md)
## Perpustakaan Bukti Dukung BPS Kabupaten Tegal (ELKA)

Dokumen ini merupakan panduan utama bagi AI Coding Agent dan pengembang yang bekerja pada repositori **Websinkronisasi (ELKA)**. Semua instruksi, batasan arsitektur, dan standar kode di bawah ini **wajib dipatuhi** demi mencegah kesalahan fatal (*zero fatal mistake*) dalam pemeliharaan proyek.

---

## 1. Konteks Proyek & Dokumen Acuan

Websinkronisasi (ELKA) adalah aplikasi web internal **BPS Kabupaten Tegal** berbentuk **Perpustakaan Bukti Dukung** (6 Lembar Kerja sebagai rak penyimpanan, dan dokumen bukti dukung sebagai buku) untuk mengumpulkan, memeriksa keabsahan, mencari, dan memetakan dokumen kantor (SK, SOP, laporan, data) ke 6 Lembar Kerja (LK): `KIP`, `PEKPPP`, `ZI`, `SAKIP`, `DATA_SEKTORAL`, dan `DATA_SEKUNDER`.

Sebelum menulis atau memodifikasi kode, agen **wajib membaca dan merujuk** berkas-berkas spesifikasi berikut yang berada di direktori [`Md/`](file:///home/hashirama/websinkronisasi/Md):

| Dokumen | Deskripsi & Cakupan |
|---|---|
| [**`Md/PRD.md`**](file:///home/hashirama/websinkronisasi/Md/PRD.md) | Kebutuhan produk final: Perpustakaan Bukti Dukung 6 LK, alur unggah-verifikasi-persetujuan, pencarian & matriks gabungan (`CariDokumenPage`), cek berkas sebelum unggah, dan kriteria penerimaan. |
| [**`Md/DATABASE.md`**](file:///home/hashirama/websinkronisasi/Md/DATABASE.md) | Skema DDL PostgreSQL, kolom persetujuan pada `evidence_documents`, tabel `document_lk_links`, tabel historis pasif, constraint database, dan trigger imutabilitas audit. |
| [**`Md/RBAC.md`**](file:///home/hashirama/websinkronisasi/Md/RBAC.md) | Matriks hak akses 3 role (`super_admin`, `admin`, `pegawai`) sesuai `User::hasPermission`, `EvidenceDocumentPolicy`, redirect peran `User::homeUrl()`, dan SSO abstraction interface. |
| [**`Md/MATCHING_ALGORITHM.md`**](file:///home/hashirama/websinkronisasi/Md/MATCHING_ALGORITHM.md) | Spesifikasi pendeteksi duplikasi SHA-256 dan kemiripan dokumen (`DocumentSimilarityService`: hash identik, nama mirip, isi mirip maks 5 kandidat + skor non-blocking; keputusan di tangan manusia). |
| [**`Md/ARCHITECTURE.md`**](file:///home/hashirama/websinkronisasi/Md/ARCHITECTURE.md) | Arsitektur sistem berlapis (Layered Clean Architecture), alur unggah-verifikasi, cek berkas sementara tanpa simpan, ekstraksi teks latar belakang, unduhan aman berawalan kode LK resmi, dan struktur folder aktual. |
| [**`Md/TASKS.md`**](file:///home/hashirama/websinkronisasi/Md/TASKS.md) | Ringkasan tahapan implementasi (Fase 1 s.d. Fase 15) dan gerbang verifikasi (*verification gates*). |

---

## 2. Standar Teknologi & Konvensi Kode

### 2.1 Stack Resmi Proyek
* **Backend:** PHP 8.3+ / Laravel 11
* **Panel Admin & UI:** Filament v3 (Livewire 3 + Alpine.js + Tailwind CSS statis & `public/css/filament-custom.css` tanpa build CSS di dev)
* **Basis Data:** PostgreSQL 15+ (Ekstensi: `pgcrypto`, `pg_trgm`, `unaccent`)
* **Queue / Asinkron:** Redis 7+ (`queue-worker` untuk ekstraksi teks `ProcessEvidenceDocumentJob`)
* **Dokumen Parser:** `smalot/pdfparser`, `phpoffice/phpspreadsheet`
* **OCR:** Tesseract OCR v5+ dengan bahasa Indonesia (`ind`)

### 2.2 Konvensi Penulisan Kode PHP & Laravel
* Wajib menggunakan deklarasi tipe ketat di setiap file PHP:
  ```php
  declare(strict_types=1);
  ```
* Seluruh method, parameter, dan return type **wajib memiliki type-hinting eksplisit**.
* Jangan menulis query SQL mentah yang tidak terparameter. Gunakan Eloquent ORM atau Query Builder berparameter untuk mencegah SQL Injection (`SEARCH-05`).
* Jangan menyimpan rahasia/kredensial di kode sumber. Selalu gunakan `config()` yang membaca dari environment variables (`.env`), khususnya `config('evidence.*')` dan `config('services.*')`.
* Validasi file fisik: Jangan pernah percaya ekstensi file semata. Selalu lakukan MIME-type sniffing berbasis *magic bytes* (`BD-03`). Batas ukuran unggah bukti dukung di aplikasi adalah **20 MB** (`config('evidence.max_file_size_mb', 20)`); berkas lebih besar diunggah ke Google Drive pengguna lalu diajukan melalui mode tautan (`source_type = 'link'`).
* Nama file penyimpanan fisik wajib menggunakan acak UUID di folder privat `storage/app/private/evidence/` (`NFR-04`, `BD-10`). Saat diunduh, nama berkas dibentuk otomatis dengan awalan kode LK resmi (maksimal 4 kode urut abjad, atau `DRAFT` bila belum memiliki link resmi) dan panjang total maksimal 150 karakter dengan ekstensi utuh.

---

## 3. Delapan Aturan Invarian Bisnis yang Tidak Boleh Dilanggar

1. **Penegakan Hak Akses di Sisi Server (`NFR-01`):**
   Jangan pernah hanya mengandalkan penyembunyian tombol di antarmuka Blade/Filament. Setiap request HTTP, controller, halaman Livewire, dan aksi Filament **wajib** divalidasi oleh middleware/policy otorisasi server-side.
2. **Keabsahan Tautan Resmi & Integritas Penolakan Verifikator:**
   Pemetaan resmi dokumen ke LK (`document_lk_links`) hanya boleh ditetapkan atau diubah oleh verifikator berwenang (`Admin`/`Super Admin` di `VerificationQueuePage` atau `Admin` via `CariDokumenPage::reviseMarkup`), bukan oleh proses otomatis latar belakang. Dokumen berstatus `'ditolak'` tidak pernah tampil sebagai centang resmi di matriks.
3. **Aturan Tanda Centang Matriks (`MON-02`):**
   Tanda centang (**✔**) di matriks **hanya** tampil untuk dokumen berstatus `approval_status = 'disetujui'` yang memiliki tautan resmi di `document_lk_links` (`DocumentLkLink::OFFICIAL_SOURCES`: `'admin'`, `'drive_sync'`, `'backfill'`). Tautan usulan (`link_source = 'proposed'`) **bukan** centang. Sel tanpa kecocokan resmi **wajib dikosongkan murni**.
4. **Proteksi Super Admin Aktif Terakhir (`AUTH-06`, `AC-04`):**
   Sistem dilarang mengizinkan penonaktifan, penghapusan, atau penurunan role dari satu-satunya Super Admin aktif yang tersisa.
5. **Keutuhan Jejak Audit (`AUDIT-02`):**
   Tabel `audit_logs` bersifat *append-only* dan dilindungi trigger PostgreSQL `prevent_audit_logs_mutation`. Dilarang menyediakan fungsi/endpoint untuk mengedit atau menghapus rekaman audit.
6. **Keamanan Tautan Bukti (`LK-09`, `AC-25`, `MON-10`):**
   Tautan bukti pada dokumen hanya menerima skema `http://` dan `https://`. Skema berbahaya (`javascript:`, `file:`, `data:`, dll.) wajib ditolak saat pengajuan tautan maupun redirect unduhan. Tautan keluar wajib memiliki `rel="noopener noreferrer"`.
7. **Berkas > 20 MB via Tautan Eksternal (Tanpa Integrasi API Google Drive):**
   Server ELKA **tidak** terhubung ke API Google Drive. Berkas yang berukuran melebihi **20 MB** (misalnya video) diunggah mandiri oleh pengguna ke Google Drive, lalu dikirim ke ELKA melalui mode tautan eksternal (`source_type = 'link'`).
8. **Kewajiban Ganti Password Pertama Super Admin Seeder (`AUTH-03`, `AC-03`):**
   Akun Super Admin awal yang dibuat melalui `SuperAdminSeeder` wajib memiliki `must_change_password = true` dan dikarantina oleh middleware `EnsurePasswordChanged` hingga kata sandi baru disimpan sebelum dapat mengakses fitur aplikasi lainnya.

---

## 4. Penggunaan Skill Agen dalam Proyek

Repositori ini dilengkapi skill di [`.agents/skills/`](file:///home/hashirama/websinkronisasi/.agents/skills). Agen memanfaatkan skill relevan sesuai jenis tugas:

| Kebutuhan Tugas | Skill yang Digunakan | Peran & Alasan |
|---|---|---|
| **Pengerjaan Fitur / Perbaikan Bug** | `/tdd` | Terapkan siklus *Red-Green-Refactor*. Tulis feature/unit test terlebih dahulu sebelum implementasi. |
| **Menulis Kode Minimal & Efisien** | `/ponytail` | Mencegah *over-engineering* atau penambahan dependensi yang tidak perlu. |
| **Review & Verifikasi Kode** | `/code-review` / `/ponytail-review` | Review kepatuhan standar dan kesesuaian spesifikasi PRD. |
| **Pembangunan UI & Tampilan** | `/hallmark` / `/antislop-ui` / `/antislop-human` | Kontras warna teruji ($\ge 4.5:1$), tombol `min-height: 56px`, ramah pengguna lanjut usia. |
| **Diagnosis Bug / Kegagalan Queue** | `/diagnosing-bugs` | Siklus terstruktur: reproduksi ➔ hipotesis ➔ perbaikan ➔ regression test. |
| **Komunikasi Ringkas & Hemat Token** | `/caveman` | Respons padat langsung ke inti teknis. |

---

## 5. Alur Kerja & Ringkasan Fase Proyek

Seluruh tahapan pembangunan dan penyederhanaan mengikuti [`Md/TASKS.md`](file:///home/hashirama/websinkronisasi/Md/TASKS.md):
* **Fase 1–8:** Fondasi database & backfill, RBAC & SSO BPS, pencarian & kemiripan, unggah aman & revisi, antarmuka antrean/matriks/cari, unduhan aman kode LK resmi, dan baseline awal.
* **Fase 9–14:** Penyeragaman batas unggah **20 MB** & penghapusan integrasi API Google Drive, penghapusan mesin pencocokan dokumen-ke-indikator, penghapusan UI Master LK/Impor Excel/Indikator (6 LK via `InitialLkSeeder`), penggabungan halaman **Cari Dokumen** (`CariDokumenPage` + matriks 6 LK + cek berkas sementara + ubah LK Admin + banner storage Super Admin di `VerificationQueuePage`), penegakan RBAC halaman **Unggah Bukti** & redirect `User::homeUrl()`, serta tema antarmuka **Vintage Hangat** BPS Kabupaten Tegal.
* **Fase 15:** Sinkronisasi seluruh 11 berkas dokumentasi aktif agar 100% selaras dengan kondisi kode produksi.
