# Rencana Implementasi Teknis — Fase 3
## Master Lembar Kerja (LK), Profil Impor, & Parser Excel Idempoten

| Parameter | Spesifikasi |
|---|---|
| **Fase Proyek** | Fase 3 dari 8 (Sesuai `Md/TASKS.md`, `Md/PRD.md`, `Md/DATABASE.md`) |
| **Kriteria Penerimaan Terkait** | **AC-14** (Soft delete LK), **AC-22** (Parsing profil ZI, PEKPPP, KIP), **AC-23** (Idempotensi impor), **AC-24** (Penolakan file `.xlsm`/palsu), **AC-25** (Sanitasi URL tautan `http/https`), **AC-26** (Resolusi `[t-1]`) |
| **Pustaka Utama** | `phpoffice/phpspreadsheet` v2.4+ (Mode Read-Only & Hyperlink extraction) |
| **Metodologi Pengujian** | Test-Driven Development (TDD) via PHPUnit 11 & Fixture Excel |

---

## 1. Arsitektur & Desain Modul Parser Excel

```mermaid
graph TD
    subgraph Klien / UI Filament
        UP[Unggah Berkas .xlsx] --> PREVIEW_UI[Layar Pratinjau Impor<br/>Preview Gate TSK-305]
        PREVIEW_UI -->|Konfirmasi Super Admin| COMMIT[Simpan ke Basis Data<br/>TSK-306]
    end

    subgraph Service & Sanitasi Keamanan (TSK-303)
        UP --> VALIDATE[Validasi Tipe & Ekstensi<br/>Tolak .xlsm & MIME palsu AC-24]
        VALIDATE --> READER[PhpSpreadsheet Reader<br/>Read Data & Hyperlink]
        READER --> STRATEGY_RESOLVER[Strategy Factory / Resolver]
    end

    subgraph Strategi Parsing Excel (TSK-302)
        STRATEGY_RESOLVER --> S1[NumberedListSingleCellStrategy<br/>Contoh: LKE ZI]
        STRATEGY_RESOLVER --> S2[ChecklistSheetStrategy<br/>Contoh: PEKPPP]
        STRATEGY_RESOLVER --> S3[SingleItemPerRowStrategy<br/>Contoh: KIP & Generik]
    end

    subgraph Normalisasi & Idempotensi
        S1 & S2 & S3 --> NORM[Filter Baris Non-Indikator<br/>Judul, Narasi, Rekap, Rumus]
        NORM --> URL_CHECK[Sanitasi Skema Tautan<br/>Hanya http/https, tolak javascript AC-25]
        URL_CHECK --> YEAR_RESOLVE[YearPlaceholderResolver<br/>Ubah t-1 jadi tahun konkret AC-26]
        YEAR_RESOLVE --> DTO[ParsedImportResult DTO]
        DTO --> PREVIEW_UI
        COMMIT --> DB_UPSERT[Idempotent Upsert Transaction<br/>lk_indicators & lk_evidence_items AC-23]
        DB_UPSERT --> AUDIT[Catat lk_import_logs & audit_logs]
    end
```

---

## 2. Rincian Tugas Kerja (Work Breakdown Structure)

### TSK-301: Manajemen Master LK & Proteksi Relasi (LK-02, LK-03, LK-06, AC-14)
* **File Target:**
  - `app/Filament/Resources/LembarKerjaResource.php`
  - `app/Filament/Resources/LembarKerjaResource/Pages/ListLembarKerjas.php`
* **Spesifikasi Logika:**
  - Menampilkan daftar master LK (kode, nama, kategori internal/eksternal, periode tahun, status aktif).
  - Validasi keunikan kombinasi `(code, period)` sesuai constraint `uq_lk_code_period`.
  - **Soft Delete Terproteksi (AC-14):** Aksi hapus LK menampilkan modal konfirmasi yang memuat jumlah indikator dan bukti dukung yang terdampak. Penghapusan menggunakan Soft Deletes (`deleted_at`) tanpa merusak relasi historis.

---

### TSK-302: Mesin Profil Impor Excel Dinamis (LKIMP-02, LKIMP-03)
* **File Target:**
  - `app/Filament/Resources/LkImportProfileResource.php`
  - `app/Services/LkImport/Contracts/ParsingStrategyInterface.php`
  - `app/Services/LkImport/DTOs/ParsedIndicatorData.php`
  - `app/Services/LkImport/DTOs/ParsedEvidenceItemData.php`
  - `app/Services/LkImport/DTOs/ParsedImportResult.php`
* **Spesifikasi Logika:**
  - Pengelolaan profil konfigurasi: `sheet_name`, `header_row`, `parsing_strategy`, `column_mapping` (JSON), dan `target_unit_column`.
  - Tiga strategi parsing terimplementasi:
    1. `NumberedListSingleCellStrategy`: Memecah butir bukti dukung bernomor dalam satu sel (format ZI, regex: `/^\s*(\d+)[\.\)]\s*(.+)$/m`).
    2. `ChecklistSheetStrategy`: Membaca butir indikator pada sheet utama dan butir bukti pada sheet checklist terpisah (format PEKPPP).
    3. `SingleItemPerRowStrategy`: Satu baris mewakili satu butir indikator/bukti (format KIP & profil generik).

---

### TSK-303: Parser Excel, Sanitasi URL & Penolakan File Makro (LKIMP-05, LKIMP-09, LKIMP-10, LK-09, AC-24, AC-25)
* **File Target:**
  - `app/Services/LkImport/ExcelParserService.php`
* **Spesifikasi Keamanan & Sanitasi:**
  1. **Penolakan Berkas Makro / Ilegal (AC-24):** Jika file berekstensi `.xlsm` atau memiliki MIME type `application/vnd.ms-excel.sheet.macroEnabled.12`, sistem langsung melempar `ValidationException` dengan pesan: *"File dengan format makro (.xlsm) ditolak demi keamanan server."*
  2. **Ekstraksi Hyperlink Ganda (LKIMP-09):** Tautan bukti dibaca dari nilai teks sel DAN objek hyperlink sel (`$cell->getHyperlink()->getUrl()`), memprioritaskan URL valid.
  3. **Sanitasi Skema Tautan (LK-09, AC-25):** Tautan diverifikasi menggunakan regex ketat:
     `^https?://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(/.*)?$`
     Jika berisi skema berbahaya (`javascript:`, `file:`, `data:`, dll.), nilai tautan dikosongkan (`null`) dan baris dicatat pada daftar peringatan (*warnings*).
  4. **Filter Baris Sampah (LKIMP-05):** Otomatis mendeteksi dan melewati baris kosong, baris penggabungan (*merge cells* untuk judul/header tabel), rekap, dan baris rumus agregasi (seperti `SUM`, `TOTAL`, `AVERAGE`).

---

### TSK-304: Resolusi Placeholder Tahun Dinamis (LK-05, AC-26)
* **File Target:**
  - `app/Services/LkImport/Resolvers/YearPlaceholderResolver.php`
* **Spesifikasi Logika:**
  - Menerima teks template bukti (contoh: `"Formulir Evaluasi SP tahun [t-1]"`) dan tahun periode LK (contoh: `2026`).
  - Menghitung formula offset tahun:
    * `[t]` ➔ `$period` (contoh: `2026`)
    * `[t-1]` ➔ `$period - 1` (contoh: `2025`)
    * `[t-2]` ➔ `$period - 2` (contoh: `2024`)
    * `[t+1]` ➔ `$period + 1` (contoh: `2027`)
  - Menyimpan teks asli ke `raw_template_name` dan hasil substitusi ke `resolved_name` pada tabel `lk_evidence_items` (AC-26).

---

### TSK-305: Layar Pratinjau Impor / Preview Gate (LKIMP-04)
* **File Target:**
  - `app/Services/LkImport/LkImportService.php`
  - `app/Filament/Resources/LembarKerjaResource/Pages/ImportLembarKerja.php`
  - `resources/views/filament/resources/lembar-kerja-resource/pages/import-preview.blade.php`
* **Spesifikasi Logika:**
  - Setelah file diunggah, parser mengeksekusi pratinjau tanpa menyimpan ke basis data utama.
  - Sesi dicatat di `lk_import_logs` dengan status `previewed`.
  - Menampilkan ringkasan:
    * Total baris dibaca.
    * Jumlah calon indikator terdeteksi.
    * Jumlah item bukti dukung yang berhasil dipecah.
    * Tabel baris yang dilewati (*skipped rows*) beserta alasan (misal: "Baris judul/rekap", "Kode indikator kosong", "Tautan bukan HTTP/HTTPS").
  - Tombol **"Konfirmasi & Simpan"** hanya aktif jika minimal terdapat 1 indikator valid.

---

### TSK-306: Eksekusi Impor Idempoten & Transaksional (LKIMP-07, AC-23)
* **File Target:**
  - `app/Services/LkImport/LkImportService.php`
* **Spesifikasi Logika:**
  - Seluruh penyimpanan dijalankan di dalam `DB::transaction`.
  - Menggunakan query `UPSERT` idempoten:
    * Indikator: `(lk_id, code)` unik ➔ jika sudah ada, perbarui `title`, `evidence_link`, `pic`, dan `metadata`.
    * Item Bukti: `(indicator_id, resolved_name)` unik ➔ jika sudah ada, lewati atau perbarui `order_index`.
  - Mengimpor file yang sama dua kali **tidak akan menggandakan baris** (`count` indikator dan item bukti identik sebelum dan sesudah impor ulang — AC-23).
  - Status pada `lk_import_logs` diperbarui menjadi `committed`.
  - Jejak audit dicatat pada `audit_logs` (`action = 'lk.imported'`).

---

## 3. Rencana Pengujian Otomatis (TDD Test Suite)

Pengujian akan disusun pada file `tests/Feature/Phase3LkImportTest.php` menggunakan berkas fixture Excel sintetis yang dibuat via PhpSpreadsheet:

1. `test_soft_delete_lk_preserves_historical_data_with_confirmation_guard()` (AC-14)
2. `test_import_zi_numbered_list_single_cell_strategy_extracts_all_items()` (AC-22)
3. `test_import_pekppp_checklist_sheet_strategy_extracts_items_from_separate_sheet()` (AC-22)
4. `test_import_kip_single_item_per_row_strategy_extracts_target_unit()` (AC-22)
5. `test_import_identical_file_twice_is_idempotent_and_does_not_duplicate_records()` (AC-23)
6. `test_uploading_macro_xlsm_file_is_strictly_rejected_with_clean_error()` (AC-24)
7. `test_malicious_javascript_or_file_urls_are_sanitized_and_nullified()` (AC-25)
8. `test_year_placeholders_like_t_minus_1_are_resolved_to_concrete_years()` (AC-26)

---

## 4. Gerbang Verifikasi (Verification Gate 3)

Fase 3 dinyatakan **Selesai & Lulus** jika:
* [ ] Seluruh pengujian di `tests/Feature/Phase3LkImportTest.php` berjalan **PASS 100%**.
* [ ] Mengunggah berkas makro `.xlsm` ditolak dengan pesan validasi yang ramah pengguna tanpa error 500.
* [ ] Tautan berbahaya `javascript:` terbukti dinetralisir dan tidak tersimpan.
* [ ] Impor ganda pada file yang sama menghasilkan total baris yang persis sama di database.
* [ ] Menu navigasi Lembar Kerja dan Profil Impor di Filament Admin dapat diakses dan digunakan dengan lancar oleh Super Admin.

