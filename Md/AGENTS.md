# Panduan Agent & Pengembang (AGENTS.md)
## Sistem Matching Bukti Dukung ke Lembar Kerja (LK)

Dokumen ini merupakan panduan utama bagi AI Coding Agent dan pengembang yang bekerja pada repositori **Websinkronisasi**. Semua instruksi, batasan arsitektur, dan standar kode di bawah ini **wajib dipatuhi** demi mencegah kesalahan fatal (*zero fatal mistake*) dalam eksekusi proyek.

---

## 1. Konteks Proyek & Dokumen Acuan

Websinkronisasi adalah aplikasi web internal kantor untuk memetakan dokumen Bukti Dukung (SK, laporan, data) ke indikator penilaian pada berbagai Lembar Kerja (LK) secara otomatis dan terverifikasi.

Sebelum menulis atau memodifikasi kode, agen **wajib membaca dan merujuk** berkas-berkas spesifikasi berikut yang berada di direktori [`Md/`](file:///home/hashirama/websinkronisasi/Md):

| Dokumen | Deskripsi & Cakupan |
|---|---|
| [**`Md/PRD.md`**](file:///home/hashirama/websinkronisasi/Md/PRD.md) | Kebutuhan produk lengkap, latar belakang, tujuan (G1–G6), kebutuhan fungsional (AUTH, LK, LKIMP, BD, MATCH, MON, SEARCH, AUDIT), dan 27 Kriteria Penerimaan (AC-01 s.d. AC-27). |
| [**`Md/DATABASE.md`**](file:///home/hashirama/websinkronisasi/Md/DATABASE.md) | Skema DDL PostgreSQL lengkap, tipe ENUM, tabel, constraints, relasi FK, trigger audit immutability, dan indeks komposit query matriks monitor. |
| [**`Md/RBAC.md`**](file:///home/hashirama/websinkronisasi/Md/RBAC.md) | Matriks hak akses 3 role (Super Admin, Admin, Pegawai), status nonaktif, SSO abstraction interface, alur auto-provisioning, dan pipeline middleware server-side. |
| [**`Md/MATCHING_ALGORITHM.md`**](file:///home/hashirama/websinkronisasi/Md/MATCHING_ALGORITHM.md) | Formula pencocokan bukti pasti (`exact_code`, `exact_number`), kemiripan teks heuristik (Trigram + Keyword), dan mekanisme perlindungan kekekalan penolakan admin (`status = 'rejected'`). |
| [**`Md/ARCHITECTURE.md`**](file:///home/hashirama/websinkronisasi/Md/ARCHITECTURE.md) | Arsitektur sistem berlapis (Layered Clean Architecture), C4 container diagram, sequence diagram, struktur folder, dan spesifikasi stack teknologi. |
| [**`Md/TASKS.md`**](file:///home/hashirama/websinkronisasi/Md/TASKS.md) | Rincian 8 fase kerja implementasi, daftar tugas terukur, dan gerbang verifikasi (*verification gates*). |

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
* Jangan menyimpan rahasia/kredensial di kode sumber. Selalu gunakan `config()` yang membaca dari environment variables (`.env`).
* Validasi file fisik: Jangan pernah percaya ekstensi file semata. Selalu lakukan MIME-type sniffing berbasis *magic bytes* (BD-03).
* Nama file penyimpanan fisik wajib menggunakan acak UUID / Hash di folder privat (NFR-04, BD-10).

---

## 3. Aturan Invarian Bisnis yang Tidak Boleh Dilanggar

1. **Penegakan Hak Akses di Sisi Server (NFR-01):**
   Jangan pernah hanya mengandalkan penyembunyian tombol di antarmuka blade/Filament. Setiap request API/controller **wajib** divalidasi oleh middleware otorisasi server-side.
2. **Kekekalan Penolakan Admin (MATCH-06, AC-11):**
   Jika relasi berstatus `rejected`, matching worker di masa mendatang **dilarang keras** membuat ulang relasi tersebut. Kueri penyimpanan match wajib menghormati kondisi ini.
3. **Centang Langsung Tampil di Monitor (D2, MON-02):**
   Match berstatus `auto` (hasil algoritma >= 75%) dan `confirmed` (bukti pasti/manual) **langsung menampilkan tanda centang (✔)** di monitor tanpa menunggu persetujuan admin. Sel tanpa kecocokan **wajib dikosongkan murni**.
4. **Proteksi Super Admin Terakhir (AUTH-06, AC-04):**
   Sistem dilarang mengizinkan penonaktifan atau penurunan role dari satu-satunya Super Admin aktif yang tersisa.
5. **Keutuhan Jejak Audit (AUDIT-02):**
   Tabel `audit_logs` bersifat *append-only*. Dilarang menyediakan fungsi/endpoint untuk mengedit atau menghapus rekaman audit.
6. **Keamanan Tautan Bukti (LK-09, AC-25, MON-10):**
   Tautan bukti pada indikator hanya menerima skema `http://` dan `https://`. Skema berbahaya (`javascript:`, `file:`, dll.) wajib ditolak saat impor. Tautan keluar wajib memiliki `rel="noopener noreferrer"`.

---

## 4. Penggunaan Skill Agen dalam Proyek

Repositori ini telah dilengkapi dengan 72 skill profesional di [`.agents/skills/`](file:///home/hashirama/websinkronisasi/.agents/skills). Agen yang bekerja pada proyek ini wajib memanfaatkan skill yang relevan sesuai jenis tugas:

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

Dalam mengimplementasikan proyek ini, ikuti urutan fase yang telah ditetapkan di [**`Md/TASKS.md`**](file:///home/hashirama/websinkronisasi/Md/TASKS.md):

1. **Fase 1:** Fondasi Proyek, Database Migrations, & Seeders.
2. **Fase 2:** Autentikasi SSO, Akun Dummy Super Admin, & RBAC Middleware.
3. **Fase 3:** Master LK, Profil Impor, & Parser Excel Idempoten.
4. **Fase 4:** Bukti Dukung Upload, Deduplikasi Hash, & Ekstraksi Teks/OCR.
5. **Fase 5:** Mesin Algoritma Matching & Anti-Overwrite Rejection Logic.
6. **Fase 6:** Matriks Monitor, Antrean Verifikasi, & Koreksi Manual.
7. **Fase 7:** Full-Text Search PostgreSQL, Trigram Fuzzy, & Audit Log.
8. **Fase 8:** Pengujian E2E 27 Kriteria Penerimaan (AC) & Hardening Produksi.

> **Catatan Penting:** Jangan melompat ke fase berikutnya sebelum gerbang verifikasi (*verification gates*) pada fase sebelumnya lolos dengan sempurna dan telah dikonfirmasi oleh pengguna.
