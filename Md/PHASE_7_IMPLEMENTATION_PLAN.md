# Rencana Implementasi Teknis — Fase 7
## Full-Text Search, Jejak Audit & Pengaturan Sistem Dinamis

| Parameter | Spesifikasi |
|---|---|
| **Fase Proyek** | Fase 7 dari 8 (Sesuai `Md/TASKS.md`, `Md/PRD.md`, `Md/DATABASE.md`, `Md/RBAC.md`) |
| **Kriteria Penerimaan Terkait** | **AC-13** (Pencarian aman dari SQL injection, karakter khusus, dan input 10.000 karakter), **AC-17** (Setiap aksi mutasi data pada AC-05 s.d. AC-16 tercatat di audit log) |
| **Kebutuhan Fungsional** | **SEARCH-01** s.d. **SEARCH-06**, **AUDIT-01** s.d. **AUDIT-03**, **MATCH-11**, **BD-02** |
| **Teknologi & Komponen** | PostgreSQL FTS (`to_tsvector`, `plainto_tsquery`), Trigram (`pg_trgm` `similarity`), Laravel Model Observers, Event Listeners, Filament v3 Resources & Pages |
| **Metodologi Pengujian** | Test-Driven Development (TDD) via PHPUnit 11 |

---

## 1. Arsitektur Komponen Fase 7

```mermaid
graph TD
    subgraph Antarmuka Pengguna & Admin (Filament v3)
        MON[Matriks Monitor / Pencarian<br/>SEARCH-01..06, AC-13]
        AUDIT_UI[AuditLogResource<br/>AUDIT-03]
        SETTING_UI[SystemSettingResource<br/>MATCH-11, BD-02]
    end

    subgraph Service Layer & Search Engine
        MON --> SEARCH_SVC[DocumentSearchService<br/>FTS + Trigram Fuzzy Sanitized]
        SEARCH_SVC --> MON_SVC[MonitorMatrixService<br/>Row Matrix Presentation]
        SETTING_SVC[SystemSetting Model / Cache]
    end

    subgraph Audit Observer & Event Subsystem
        OBS_LK[LembarKerjaObserver<br/>lk.created, updated, deleted]
        OBS_PROF[LkImportProfileObserver<br/>profile.created, updated, deleted]
        OBS_SET[SystemSettingObserver<br/>setting.updated]
        AUTH_EVT[AuditAuthEventsListener<br/>auth.login, auth.logout]
        
        OBS_LK --> AUDIT_TABLE[(audit_logs Table<br/>Append-Only, Trigger Protected)]
        OBS_PROF --> AUDIT_TABLE
        OBS_SET --> AUDIT_TABLE
        AUTH_EVT --> AUDIT_TABLE
    end

    subgraph PostgreSQL Database
        SEARCH_SVC --> DB_DOCS[(evidence_documents<br/>GIN FTS & Trigram Ops)]
        AUDIT_UI --> AUDIT_TABLE
        SETTING_UI --> DB_SET[(system_settings Table)]
    end
```

---

## 2. Rincian Tugas Kerja (Work Breakdown Structure)

### TSK-701: Pencarian Multi-Kolom FTS & Fuzzy Trigram (SEARCH-01, SEARCH-02, SEARCH-04, SEARCH-05, SEARCH-06, AC-13)
* **File Target:**
  - `app/Services/Search/DocumentSearchService.php`
  - `app/Models/EvidenceDocument.php` (Scope `scopeSearchFullTextAndFuzzy`)
* **Spesifikasi Teknis:**
  - **Sanitasi Input (SEARCH-05, SEARCH-06, AC-13):**
    - Potong input maksimal 500 karakter untuk mencegah overhead memori/CPU.
    - Bersihkan karakter kontrol dan null-bytes.
    - Semua query menggunakan parameterized binding `?`.
    - Input berbahaya seperti `'; DROP TABLE--` atau 10.000 karakter tidak menghasilkan exception/SQL injection.
  - **Kombinasi Algoritma Pencarian:**
    - FTS PostgreSQL: `to_tsvector('indonesian', coalesce(document_name, '') || ' ' || coalesce(document_number, '') || ' ' || coalesce(extracted_text, '')) @@ plainto_tsquery('indonesian', ?)`
    - Substring: `document_name ILIKE ? OR document_number ILIKE ?`
    - Trigram Fuzzy (`pg_trgm`): `similarity(document_name, ?) > 0.25 OR similarity(coalesce(document_number, ''), ?) > 0.25`
  - **Hanya Bukti Dukung Aktif (SEARCH-04):**
    - Selalu terapkan `whereNull('deleted_at')`.

---

### TSK-702: Integrasi Pencarian pada Matriks Monitor (SEARCH-03, SEARCH-04)
* **File Target:**
  - `app/Services/Monitor/MonitorMatrixService.php`
  - `app/Filament/Pages/MonitorMatrixPage.php`
* **Spesifikasi Teknis:**
  - Mengganti kueri `ilike` sederhana pada `MonitorMatrixService::getMatrixData` dengan pemanggilan `DocumentSearchService`.
  - Dokumen yang cocok dengan pencarian FTS/Fuzzy langsung dirender dalam bentuk baris matriks monitor lengkap dengan centang (✔) pada kolom LK yang berstatus `auto` atau `confirmed`.

---

### TSK-703: Perekaman Jejak Audit Otomatis (AUDIT-01, AUDIT-02, AC-17)
* **File Target:**
  - `app/Services/Audit/AuditLogService.php`
  - `app/Observers/LembarKerjaObserver.php`
  - `app/Observers/LkImportProfileObserver.php`
  - `app/Observers/SystemSettingObserver.php`
  - `app/Listeners/AuditAuthEventsListener.php`
  - `app/Providers/AppServiceProvider.php`
* **Spesifikasi Teknis:**
  - Menghubungkan Observer dan Event Listener agar seluruh mutasi tercatat secara otomatis:
    - Master LK: `lk.created`, `lk.updated`, `lk.deleted`, `lk.restored`
    - Profil Impor: `lk_profile.created`, `lk_profile.updated`, `lk_profile.deleted`
    - Autentikasi: `auth.login`, `auth.logout`
    - Konfigurasi Sistem: `setting.updated`
  - Memastikan pencatatan aman dan tidak memicu trigger anti-tampering (hanya operasi `INSERT`).

---

### TSK-704: Layar Penampil Audit Log Super Admin (AUDIT-03)
* **File Target:**
  - `app/Filament/Resources/AuditLogResource.php`
  - `app/Filament/Resources/AuditLogResource/Pages/ListAuditLogs.php`
* **Spesifikasi Tampilan & Akses:**
  - **Hak Akses:** Hanya dapat diakses oleh **Super Admin** (`canViewAny()`). Admin dan Pegawai ditolak dengan HTTP 403.
  - **Sifat Read-Only:** Tidak ada tombol Buat, Edit, atau Hapus (`canCreate(): false`, `canEdit(): false`, `canDelete(): false`) sesuai prinsip immutability (AUDIT-02).
  - **Filter Tabel:**
    - Filter Pengguna (Select User).
    - Filter Jenis Aksi (Select Action).
    - Filter Rentang Tanggal (Date Picker Filter).
  - **Modal Detail:** Menampilkan JSON `old_values` dan `new_values` secara terformat dan rapi.

---

### TSK-705: Pengaturan Sistem Dinamis (MATCH-11, BD-02)
* **File Target:**
  - `app/Filament/Resources/SystemSettingResource.php`
  - `app/Filament/Resources/SystemSettingResource/Pages/ListSystemSettings.php`
  - `app/Filament/Resources/SystemSettingResource/Pages/EditSystemSetting.php`
* **Spesifikasi Tampilan & Logika:**
  - **Hak Akses:** Hanya dapat diakses dan diubah oleh **Super Admin** (`super_admin`).
  - **Pengaturan yang Didukung:**
    - `matching.similarity_threshold`: Ambang batas kecocokan (50.0 - 100.0).
    - `upload.max_file_size_mb`: Batas ukuran upload berkas (1 - 50 MB).
    - `session.idle_timeout_minutes`: Timeout sesi tidak aktif.
  - Perubahan nilai otomatis tercatat di tabel `audit_logs` dan langsung berefek pada layanan terkait tanpa memerlukan restart server.

---

## 3. Rencana Pengujian TDD (Slice & Seams)

1. **Slice 1 (TSK-701 & TSK-702):**
   - Test FTS dan Fuzzy search: mencari berdasarkan nama, nomor, dan isi teks ekstrak.
   - Test toleransi salah ketik (fuzzy).
   - Test keamanan AC-13: input `'; DROP TABLE--` dan string 10.000 karakter tidak menghasilkan error 500 dan lolos sanitasi.
   - Test integrasi matriks monitor (SEARCH-03): baris dokumen hasil pencarian menampilkan centang LK yang cocok.
2. **Slice 2 (TSK-703):**
   - Test audit log mutasi Master LK (`lk.created`, `lk.updated`, `lk.deleted`).
   - Test audit log mutasi Profil Impor LK (`lk_profile.created`, `lk_profile.updated`, `lk_profile.deleted`).
   - Test audit log event login & logout (`auth.login`, `auth.logout`).
   - Test audit log mutasi pengaturan sistem (`setting.updated`).
   - Test proteksi immutability jejak audit (trigger PostgreSQL menolak UPDATE & DELETE).
3. **Slice 3 (TSK-704):**
   - Test aksesibilitas `AuditLogResource`: Super Admin diizinkan, Admin dan Pegawai ditolak dengan HTTP 403.
4. **Slice 4 (TSK-705):**
   - Test aksesibilitas dan pembaruan `SystemSettingResource`: Super Admin berhasil mengubah nilai, Admin ditolak dengan HTTP 403.
   - Test efek dinamis: perubahan ambang batas matching langsung memengaruhi penentuan status `auto` pada matching engine.
