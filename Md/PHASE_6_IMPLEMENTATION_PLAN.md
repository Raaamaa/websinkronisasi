# Rencana Implementasi Teknis — Fase 6
## Matriks Monitor, Antrean Verifikasi & Koreksi Manual

| Parameter | Spesifikasi |
|---|---|
| **Fase Proyek** | Fase 6 dari 8 (Sesuai `Md/TASKS.md`, `Md/PRD.md`, `Md/DATABASE.md`, `Md/RBAC.md`) |
| **Kriteria Penerimaan Terkait** | **AC-08** (Centang pada kolom LK yang cocok), **AC-09** (Sel tanpa kecocokan kosong murni), **AC-10** (Status auto tampil di monitor & antrean verifikasi), **AC-11** (Penolakan match menghilangkan ✔ dari monitor), **AC-12** (Detail drill-down centang & link aman), **AC-16** (Pencegahan race-condition penambahan match konkuren), **AC-17** (Audit log mutasi verifikasi/koreksi) |
| **Kebutuhan Fungsional** | **MON-01** s.d. **MON-10**, **MATCH-04**, **MATCH-05**, **NFR-05** (Waktu respons monitor <= 2 detik untuk 10.000 dokumen) |
| **Teknologi & Komponen** | Filament Page / Livewire 3, PostgreSQL JSON Aggregation (`jsonb_object_agg`), Alpine.js modal dialog |
| **Metodologi Pengujian** | Test-Driven Development (TDD) via PHPUnit 11 |

---

## 1. Arsitektur Komponen Monitor & Verifikasi

```mermaid
graph TD
    subgraph Pengguna & Akses (RBAC)
        P[Pegawai SSO] -->|Hanya Lihat| MON_PAGE[Halaman Matriks Monitor<br/>MON-01, MON-08]
        A[Admin / Super Admin] -->|Lihat & Kelola| MON_PAGE
        A -->|Verifikasi Match Auto| QUEUE_PAGE[Antrean Verifikasi Admin<br/>MATCH-04, MATCH-10]
    end

    subgraph Service Layer
        MON_PAGE --> MON_SVC[MonitorMatrixService<br/>Kueri Paged Pivot Server-Side TSK-602]
        MON_PAGE --> DRILL_MODAL[Modal Drill-Down Detail Centang<br/>TSK-603, MON-04, AC-12]
        QUEUE_PAGE --> VERIF_SVC[VerificationQueueService<br/>TSK-604, TSK-605]
        DRILL_MODAL --> VERIF_SVC
    end

    subgraph Basis Data (PostgreSQL)
        MON_SVC -->|Agregasi JSON Cepat| DB_MATCH[document_matches<br/>status IN 'auto', 'confirmed']
        MON_SVC --> DB_DOCS[evidence_documents]
        MON_SVC --> DB_LK[lembar_kerja & lk_indicators]
        
        VERIF_SVC -->|Confirm: status='confirmed'| DB_MATCH
        VERIF_SVC -->|Reject: status='rejected' AC-11| DB_MATCH
        VERIF_SVC -->|Manual Add: ON CONFLICT AC-16| DB_MATCH
        VERIF_SVC -->|Perekaman Jejak Mutasi| AUDIT[audit_logs: match.verified / rejected / manual_created]
    end
```

---

## 2. Rincian Tugas Kerja (Work Breakdown Structure)

### TSK-601: Layar Matriks Monitor (MON) & Kelompok Kolom LK (MON-01, MON-02, MON-03, MON-07, MON-08, AC-08, AC-09)
* **File Target:**
  - `app/Filament/Pages/MonitorMatrixPage.php`
  - `resources/views/filament/pages/monitor-matrix.blade.php`
* **Spesifikasi Tampilan & Logika:**
  - **Struktur Tabel:**
    - Baris: Dokumen Bukti Dukung (Nama Dokumen, Nomor Dokumen, Tahun, Jenis Dokumen).
    - Kolom dikelompokkan dengan dua super-header:
      1. **Internal (BPS):** KIP, SAKIP, ZI, PEKPPP
      2. **Eksternal:** Data Sekunder, Data Sektoral
  - **Penyajian Tanda Centang (MON-02, MON-03, AC-08, AC-09):**
    - Render simbol centang **✔** jika dokumen memiliki kecocokan berstatus `auto` atau `confirmed` pada LK terkait.
    - **Kosong Murni (AC-09):** Jika dokumen tidak cocok dengan suatu LK, sel **wajib dikosongkan secara murni** (tanpa teks, tanpa simbol `-`, tanpa latar warna).
    - Match yang berstatus `rejected` **dilarang pernah tampil** di monitor (MON-03).
  - **Penyaringan (MON-07):**
    - Filter periode tahun LK (default: tahun berjalan / 2026).
    - Filter kategori LK (Semua, Internal, Eksternal).
    - Filter jenis dokumen (SK, SOP, Laporan, dll.).
    - Filter tahun dokumen bukti dukung.

---

### TSK-602: Kueri Pivot Server-Side & Paginasi Cepat (MON-08, NFR-05)
* **File Target:**
  - `app/Services/Monitor/MonitorMatrixService.php`
* **Spesifikasi Performa & Kueri:**
  - Menerapkan kueri agregasi JSON PostgreSQL di sisi server sesuai `DATABASE.md` Bab 5.1:
    * Mengambil dokumen per halaman (default: 25 dokumen per halaman via `LIMIT` dan `OFFSET`).
    * Melakukan `LEFT JOIN` ke `document_matches` dengan filter `status IN ('auto', 'confirmed')`.
    * Menggunakan fungsi PostgreSQL `jsonb_object_agg(lk.code, ...)` untuk mengagregasi relasi tiap LK dalam bentuk JSON key-value.
  - Memanfaatkan indeks komposit parsial:
    `CREATE INDEX idx_matches_monitor_lookup ON document_matches(document_id, indicator_id) WHERE status IN ('auto', 'confirmed');`
  - Menjamin eksekusi kueri selesai dalam $\le 2$ detik untuk skala 10.000 dokumen.

---

### TSK-603: Modal Detail Centang (Drill-Down) & Tautan Aman (MON-04, MON-05, MON-06, MON-10, AC-12)
* **File Target:**
  - `app/Filament/Pages/MonitorMatrixPage.php` (Livewire modal event)
  - `resources/views/filament/pages/partials/monitor-drilldown-modal.blade.php`
* **Spesifikasi Interaksi:**
  - Saat tanda centang **✔** pada suatu sel diklik oleh pengguna (baik Pegawai, Admin, maupun Super Admin):
    * Membuka modal popup detail tanpa me-reload halaman.
    * Menampilkan identitas Bukti Dukung (nama, nomor dokumen, tahun).
    * Menampilkan daftar seluruh indikator LK yang cocok pada sel tersebut:
      - Kode Indikator (contoh: `1.2.a`).
      - Judul / Uraian Indikator (contoh: `Standar Pelayanan Publik`).
      - Butir Bukti yang cocok (contoh: `SOP Layanan tahun 2026`).
      - Status Kecocokan: Badge label `Otomatis` (untuk `auto`) atau `Terverifikasi` (untuk `confirmed`).
      - Skor Kecocokan (contoh: `100.0%` atau `85.5%`).
      - Tautan Bukti (MON-05, MON-06, MON-10, AC-12):
        * Jika indikator memiliki `evidence_link` valid atau dokumen memiliki `external_link`, render tombol tautan dengan atribut `target="_blank" rel="noopener noreferrer"` (skema aman `http/https` saja).
        * Jika tautan kosong/null, tampilkan teks informatif: *"tautan belum tersedia"*.
    * Untuk role Admin & Super Admin: disediakan tombol cepat untuk mencabut match (*Hapus Relasi* ➔ menjadi `rejected`).

---

### TSK-604: Antrean Verifikasi Admin (MATCH-04, MATCH-10, AC-10, AC-11)
* **File Target:**
  - `app/Filament/Pages/VerificationQueuePage.php`
  - `app/Services/Matching/VerificationService.php`
  - `resources/views/filament/pages/verification-queue.blade.php`
* **Spesifikasi Fitur:**
  - **Otorisasi Server-Side:** Hanya dapat diakses oleh `super_admin` dan `admin` (`permission: match.verify`). Pengguna `pegawai` ditolak dengan `403 Forbidden`.
  - **Daftar Antrean:** Menampilkan relasi `document_matches` yang masih berstatus `auto`.
  - **Informasi Kolom:**
    * Dokumen Bukti (nama & nomor SK).
    * Target LK & Kode/Judul Indikator.
    * Metode Pencocokan (`title_similarity`, `content_similarity`).
    * Skor Persentase & Rincian Alasan (`match_reasons`: kata kunci cocok, komponen skor judul & isi).
    * Tanggal Pencocokan.
  - **Aksi Satu Klik (Single-Click Action):**
    1. **Konfirmasi (Verify):** Mengubah status menjadi `confirmed`, mencatat `verified_by = user.id`, `verified_at = now()`. Tanda centang pada monitor tetap aktif (AC-10).
    2. **Tolak (Reject):** Mengubah status menjadi `rejected`, mencatat `verified_by = user.id`, `verified_at = now()`. Tanda centang pada monitor seketika **hilang** dan tidak akan dibuat ulang saat re-match (AC-11).
  - Perekaman jejak audit otomatis pada tabel `audit_logs` (`match.verified` / `match.rejected`).

---

### TSK-605: Koreksi Manual Relasi & Proteksi Balapan Konkurensi (MATCH-05, AC-16)
* **File Target:**
  - `app/Services/Matching/ManualMatchService.php`
* **Spesifikasi Logika:**
  - **Tambah Match Manual:** Admin memilih dokumen dan indikator LK secara manual.
    * Sistem menyimpan relasi dengan status `confirmed`, `match_method = 'manual'`, `confidence_score = 100.0`.
    * **Pencegahan Race Condition (AC-16):** Jika dua admin menambah match yang sama secara bersamaan, query menggunakan `ON CONFLICT (document_id, indicator_id) DO UPDATE ...` sehingga hanya satu baris yang tersimpan tanpa memicu *duplicate key error 500*.
  - **Hapus Match Manual:** Menghapus relasi dari tampilan monitor dengan memperbarui status menjadi `rejected` (menghormati invarian MATCH-06 agar tidak dibuat ulang oleh worker otomatis).
  - Perekaman audit log: `match.manual_created` dan `match.manual_deleted`.

---

## 3. Rencana Pengujian Otomatis (TDD Test Suite)

Skenario uji akan diimplementasikan pada file `tests/Feature/Phase6MonitorAndVerificationTest.php`:

1. `test_monitor_matrix_renders_checkmark_for_auto_and_confirmed_matches_only()` (MON-02, AC-08)
2. `test_monitor_matrix_leaves_cells_completely_blank_when_no_match()` (MON-02, MON-03, AC-09)
3. `test_monitor_matrix_groups_lks_by_internal_and_external_categories()` (MON-01)
4. `test_clicking_checkmark_returns_drilldown_details_with_safe_links_or_placeholder()` (MON-04, MON-05, MON-06, MON-10, AC-12)
5. `test_pegawai_cannot_access_verification_queue_returns_403()` (RBAC.md, AC-01)
6. `test_admin_and_super_admin_can_access_verification_queue()` (MATCH-04)
7. `test_admin_can_confirm_auto_match_updating_status_to_confirmed_and_logging_audit()` (MATCH-04, AC-10, AC-17)
8. `test_admin_can_reject_auto_match_removing_it_from_monitor_and_setting_rejected()` (MATCH-04, AC-11, AC-17)
9. `test_admin_can_manually_add_match_as_confirmed()` (MATCH-05)
10. `test_concurrent_manual_match_creation_does_not_duplicate_or_throw_500()` (AC-16)
11. `test_admin_can_manually_delete_match_setting_status_to_rejected()` (MATCH-05, MATCH-06)
12. `test_all_roles_can_access_monitor_page()` (MON-01, RBAC.md)

---

## 4. Gerbang Verifikasi (Verification Gate 6)

Fase 6 dinyatakan **Selesai & Lulus** jika:
* [ ] Seluruh pengujian di `tests/Feature/Phase6MonitorAndVerificationTest.php` berjalan **PASS 100%**.
* [ ] Matriks monitor merender tanda centang (✔) pada LK yang cocok dan sel tanpa kecocokan **kosong murni** tanpa karakter apa pun (AC-08, AC-09).
* [ ] Klik tanda centang menampilkan modal detail indikator lengkap dengan status Otomatis/Terverifikasi dan tautan bukti aman `rel="noopener noreferrer"` (AC-12).
* [ ] Penolakan match `auto` di antrean verifikasi mengubah status menjadi `rejected` dan menghilangkan tanda ✔ dari monitor (AC-11).
* [ ] Dua admin menambahkan match manual yang sama tidak menghasilkan data ganda atau error 500 (AC-16).
* [ ] Seluruh pengujian sistem (Fase 1 s.d. Fase 6, total 65+ tests) berjalan hijau (*all green*).

