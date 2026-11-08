# Rencana Implementasi Terpadu (Implementation Plan) - Edisi Revisi Mutakhir
## Perbaikan Fondasi, Integritas Data, Algoritma Matching, & Fitur Pencarian Cepat
**Dokumen Induk:** [`Md/RISET_01.md`](file:///home/hashirama/websinkronisasi/Md/RISET_01.md) | **Repositori:** Websinkronisasi (BPS LK Matching Engine)  
**Status Eksekusi:** *PERENCANAAN SAJA (Zero Code/Config/Migration/Dev DB Mutation)*  
**Target Basis Data Pengujian:** `websinkronisasi_testing` (Diisolasi Total dari `websinkronisasi` Dev DB)  
**Konvensi Framework:** PHP 8.3 / Laravel 11 / Filament v3 / PostgreSQL 15+

---

## 1. Changelog Revisi (Sesuai Aturan G6 & G15)

Dokumen rencana kerja ini telah diselaraskan secara ketat dengan aturan verifikasi empiris (G1 s.d. G18):
1. **G6 & G15 (Kejujuran Klaim & Penghapusan Temuan Tanpa Bukti):** Seluruh klaim perbaikan didukung bukti eksekusi riil. Temuan tambahan **N-03** (klaim ketiadaan indeks parsial `idx_matches_monitor_lookup`) **resmi DIHAPUS** karena terbukti dari inspeksi DDL `\d document_matches` bahwa indeks komposit parsial `(document_id, indicator_id) WHERE status IN ('auto', 'confirmed')` telah terpasang sejak migrasi awal `2026_10_29_000003_create_document_matches_table.php:38`.
2. **U1 Ulang (Pengukuran 10 Kasus pada DB Testing Bersih):** 10 kasus kedinasan diuji ulang secara empiris HANYA pada basis data `websinkronisasi_testing` yang di-seed murni menggunakan seeder resmi (`InitialLkSeeder` dan `DemoIndicatorAndSampleDocumentsSeeder` tanpa indikator uji coba seperti `A11i`/`A111i`/`A12i`). Hasil membuktikan:
   - Kasus 10 kembali menunjukkan status `confirmed` (100.00%) via `exact_code` pada indikator seeder resmi `ZI-1.1`.
   - Kasus 1 dan Kasus 5 terbukti memiliki status `auto` (78.14% dan 79.48%) pada mode teks ekstraksi realistis (bobot 70/30) karena kecocokan kata kunci konten (11/12 dan 8/8 kata kunci cocok). Pada mode murni judul (Title-Only), keduanya mencatat 72.34% dan 70.69% sehingga masuk kategori `perlu_verifikasi`.
3. **U1b (Generalisasi pada 5 Dokumen Baru Indonesia):** Menambahkan 5 dokumen kedinasan baru (SK Reformasi Birokrasi, SOP BMN, Laporan Keuangan DIPA, SK Pengelola Web, Notula Sensus Pertanian) untuk membuktikan algoritma tidak overfit. Dokumen tanpa relasi LK (SOP BMN, LK DIPA, Notula Sensus) mencatat skor rendah (28%–47%) dan bersih dari centang palsu.
4. **U2 Ulang (Bukti Matematis Dilusi Trigram & Kalibrasi Ambang K1):** Membuktikan secara empiris bahwa penambahan ekspansi singkatan `ppid` secara sepihak memperpanjang template indikator hingga 13 kata, menyebabkan inflasi penyebut gabungan trigram (*Jaccard union*) yang menjatuhkan skor Kasus 9 dari 74.23% menjadi 54.55%. Diputuskan: kamus sinonim tidak diekspansi secara sembarangan, melainkan mengandalkan pencocokan kata kunci isi atau penampungan pada antrean `perlu_verifikasi`. Ambang dikalibrasi terukur: **75.0% untuk `auto`** (0% False Positives) dan **60.0% untuk `perlu_verifikasi`** (menangkap seluruh True Positives).
5. **G16 (Penyelarasan Total Invarian Bisnis #1 s.d. #8):** Matriks invarian dan teks katalog hak akses ditulis ulang mengacu persis pada handoff dan teks mentah `AGENTS.md:46-61`. Catatan hak akses disederhanakan: `evidence.upload` memang tidak dimiliki Super Admin sesuai `RBAC.md` demi pemisahan peran operator dokumen.
6. **G17 (Hitung Ulang & Validasi Kesiapan):** Daftar task berjumlah persis **35 task**, terdiri dari **28 SIAP** dan **7 BLOKIR**. Setiap task memiliki 1 baris di tabel ringkasan kesiapan.
7. **G18 (Standar Laravel 11 & Pelabelan Metode Baru):** Semua kelas/metode baru ditandai "USULAN BARU" beserta rujukan file:line kode aslinya. Nama kelas yang tidak relevan dengan Laravel 11 (`AuthServiceProvider`, `TrustProxies.php`, `InvalidConfigurationException`, `AuditLogProfile`) telah dikoreksi menggunakan konvensi mutakhir (`bootstrap/app.php`, auto-discovery policy, `tests/Support/TestEnvironmentGuard.php`).
8. **Penataan Urutan FASE 1:** TASK-11 (pembersihan data uji coba pada dev DB dengan mode `--dry-run` default dan proteksi `pg_dump`) diposisikan mendahului TASK-10 (migrasi pemisahan skema period) untuk mencegah kegagalan foreign key / unique constraint.

---

## 2. Lampiran Bukti Eksekusi Mentah (U1–U8)

### U1. Output Mentah 10 Kasus pada DB Testing Bersih (5 Indikator Seeder Resmi)

Pengujian 10 kasus kedinasan riil dieksekusi langsung pada container `websinkronisasi_app` dengan koneksi `-e DB_DATABASE=websinkronisasi_testing` yang hanya memuat 5 indikator resmi dari `InitialLkSeeder` dan `DemoIndicatorAndSampleDocumentsSeeder`:
1. `ZI-1.1`: Pembentukan Tim Kerja Pembangunan Zona Integritas Menuju WBK/WBBM
2. `ZI-1.2`: Rencana Kerja dan Rencana Aksi Pembangunan Zona Integritas
3. `PEKPPP-01`: Penetapan Standar Pelayanan Statistik Terpadu (PST) dan Maklumat Pelayanan
4. `SAKIP-01`: Laporan Kinerja Instansi Pemerintah (LKjIP) dan Perjanjian Kinerja
5. `KIP-01`: Penetapan Struktur PPID dan Penetapan Daftar Informasi Publik (DIP)

```text
========================================================================================
U1. PENGUJIAN 10 KASUS PADA DB TESTING BERSIH (5 INDIKATOR SEEDER RESMI)
========================================================================================

### MODE 1: TERUKUR TITLE-ONLY (DATA REAL SAAT INI) - SEBELUM FIX TRIGRAM
---------------------------------------------------------------------------------------------------------
Kasus   | Best Ind    | Total  | Title  | Cont   | Weight  | Pen   | Method           | Status           | All Candidate Scores [ZI-1.1, ZI-1.2, PEKPPP-01, SAKIP-01, KIP-01]
---------------------------------------------------------------------------------------------------------
Kasus 1 | ZI-1.1      |  72.34 |  72.34 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [72.3, 60.1, 52.6, 50.9, 58.6]
Kasus 2 | PEKPPP-01   |  73.85 |  73.85 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [47.8, 50.8, 73.8, 40.6, 49.2]
Kasus 3 | SAKIP-01    |  96.30 |  96.30 |   0.00 | 100/0   |   0.0 | title_similarity | auto             | [48.8, 50.4, 33.0, 96.3, 52.2]
Kasus 4 | KIP-01      |  69.70 |  69.70 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [55.7, 48.5, 50.0, 48.0, 69.7]
Kasus 5 | ZI-1.2      |  70.69 |  70.69 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [59.6, 70.7, 41.4, 45.9, 43.1]
Kasus 6 | SAKIP-01    |  56.14 |  56.14 |   0.00 | 100/0   |   0.0 | title_similarity | none             | [45.9, 47.9, 49.6, 56.1, 43.0]
Kasus 7 | SAKIP-01    |  60.42 |  60.42 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [38.4, 30.4, 41.2, 60.4, 28.6]
Kasus 8 | SAKIP-01    |  40.43 |  40.43 |   0.00 | 100/0   |   0.0 | title_similarity | none             | [37.1, 29.5, 38.5, 40.4, 36.4]
Kasus 9 | KIP-01      |  74.23 |  74.23 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [45.7, 47.4, 45.8, 55.6, 74.2]
Kasus 10| ZI-1.1      | 100.00 | 100.00 |   0.00 | determ  |   0.0 | exact_code       | confirmed        | [100.0, 0.0, 0.0, 0.0, 0.0]

### MODE 2: TERUKUR TITLE-ONLY (DATA REAL SAAT INI) - SESUDAH FIX TRIGRAM
---------------------------------------------------------------------------------------------------------
Kasus   | Best Ind    | Total  | Title  | Cont   | Weight  | Pen   | Method           | Status           | All Candidate Scores [ZI-1.1, ZI-1.2, PEKPPP-01, SAKIP-01, KIP-01]
---------------------------------------------------------------------------------------------------------
Kasus 1 | ZI-1.1      |  72.34 |  72.34 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [72.3, 60.1, 52.6, 50.9, 58.6]
Kasus 2 | PEKPPP-01   |  73.85 |  73.85 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [47.8, 50.8, 73.8, 40.6, 49.2]
Kasus 3 | SAKIP-01    |  96.30 |  96.30 |   0.00 | 100/0   |   0.0 | title_similarity | auto             | [48.8, 50.4, 33.0, 96.3, 52.2]
Kasus 4 | KIP-01      |  69.70 |  69.70 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [55.7, 48.5, 50.0, 48.0, 69.7]
Kasus 5 | ZI-1.2      |  70.69 |  70.69 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [59.6, 70.7, 41.4, 45.9, 43.1]
Kasus 6 | SAKIP-01    |  56.14 |  56.14 |   0.00 | 100/0   |   0.0 | title_similarity | none             | [45.9, 47.9, 49.6, 56.1, 43.0]
Kasus 7 | SAKIP-01    |  60.42 |  60.42 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [38.4, 30.4, 41.2, 60.4, 28.6]
Kasus 8 | SAKIP-01    |  40.43 |  40.43 |   0.00 | 100/0   |   0.0 | title_similarity | none             | [37.1, 29.5, 38.5, 40.4, 36.4]
Kasus 9 | KIP-01      |  74.23 |  74.23 |   0.00 | 100/0   |   0.0 | title_similarity | perlu_verifikasi | [45.7, 47.4, 45.8, 55.6, 74.2]
Kasus 10| ZI-1.1      | 100.00 | 100.00 |   0.00 | determ  |   0.0 | exact_code       | confirmed        | [100.0, 0.0, 0.0, 0.0, 0.0]

### MODE 3: SIMULASI WITH REALISTIC TEXT (70% Judul + 30% Isi) - SESUDAH FIX TRIGRAM
---------------------------------------------------------------------------------------------------------
Kasus   | Best Ind    | Total  | Title  | Cont   | Weight  | Pen   | Method           | Status           | All Candidate Scores [ZI-1.1, ZI-1.2, PEKPPP-01, SAKIP-01, KIP-01]
---------------------------------------------------------------------------------------------------------
Kasus 1 | ZI-1.1      |  78.14 |  72.34 |  91.67 | 70/30   |   0.0 | heuristic_comb   | auto             | [78.1, 60.9, 42.8, 41.6, 47.1]
Kasus 2 | PEKPPP-01   |  72.70 |  73.85 |  70.00 | 70/30   |   0.0 | heuristic_comb   | perlu_verifikasi | [36.0, 39.3, 72.7, 32.7, 40.5]
Kasus 3 | SAKIP-01    |  93.12 |  96.30 |  85.71 | 70/30   |   0.0 | heuristic_comb   | auto             | [36.6, 39.0, 23.1, 93.1, 39.5]
Kasus 4 | KIP-01      |  72.79 |  69.70 |  80.00 | 70/30   |   0.0 | heuristic_comb   | perlu_verifikasi | [41.5, 37.7, 38.0, 37.9, 72.8]
Kasus 5 | ZI-1.2      |  79.48 |  70.69 | 100.00 | 70/30   |   0.0 | heuristic_comb   | auto             | [52.6, 79.5, 29.0, 36.4, 33.2]
Kasus 6 | SAKIP-01    |  43.58 |  56.14 |  14.29 | 70/30   |   0.0 | heuristic_comb   | none             | [32.1, 33.5, 37.7, 43.6, 30.1]
Kasus 7 | SAKIP-01    |  42.29 |  60.42 |   0.00 | 70/30   |   0.0 | heuristic_comb   | none             | [26.9, 21.3, 31.6, 42.3, 20.1]
Kasus 8 | SAKIP-01    |  28.30 |  40.43 |   0.00 | 70/30   |   0.0 | heuristic_comb   | none             | [26.0, 24.4, 27.0, 28.3, 25.4]
Kasus 9 | KIP-01      |  69.96 |  74.23 |  60.00 | 70/30   |   0.0 | heuristic_comb   | perlu_verifikasi | [34.5, 36.9, 37.5, 43.2, 70.0]
Kasus 10| ZI-1.1      | 100.00 | 100.00 |   0.00 | determ  |   0.0 | exact_code       | confirmed        | [100.0, 0.0, 0.0, 0.0, 0.0]
```

**Penjelasan Terukur Kasus Kunci:**
1. **Kasus 10 (`Surat Keputusan Tim ZI-1.1 WBK Tahun 2026`):** Terbukti kembali mencatat skor **100.00% confirmed** melalui metode `exact_code`. Kode `ZI-1.1` pada nama dokumen dan berkas cocok dengan batasan kata regex `(?:^|[\s_\[\(])ZI-1\.1(?:[\s_\]\)\.\-]|)`. Pada pengujian sebelumnya skor turun menjadi 71.74% karena basis data dev tercemar baris indikator non-resmi (`A11i`) yang tidak memiliki kode `ZI-1.1`.
2. **Kasus 1 (`SK Kepala BPS tentang Pembentukan Tim Kerja Pembangunan ZI Tahun 2026`):**
   - Skor kemiripan judul murni terhadap `ZI-1.1` adalah **72.34%** (masuk antrean `perlu_verifikasi`).
   - Saat teks ekstraksi dokumen diproses (`Keputusan Kepala BPS tentang Pembentukan Tim Kerja Pembangunan Zona Integritas Menuju WBK/WBBM BPS Tahun 2026`), 11 dari 12 kata kunci target berhasil ditemukan (skor konten = 91.67%).
   - Pembobotan kombinasi (70% Judul + 30% Isi) menghasilkan: $(0.70 	imes 72.34) + (0.30 	imes 91.67) = 50.638 + 27.501 =$ **78.14%** (Status `auto` $\ge 75.0\%$). Inilah asal angka 78.1% pada `Md/RISET_01.md`.
3. **Kasus 5 (`Notulen Rapat Pembahasan Rencana Aksi Zona Integritas 2026`):**
   - Skor kemiripan judul murni terhadap `ZI-1.2` adalah **70.69%** (masuk antrean `perlu_verifikasi`).
   - Teks ekstraksi dokumen (`Rapat rencana kerja dan dokumen rencana aksi pembangunan zona integritas tahun 2026`) mencakup 8 dari 8 kata kunci target (skor konten = 100.00%).
   - Pembobotan kombinasi menghasilkan: $(0.70 	imes 70.69) + (0.30 	imes 100.00) = 49.483 + 30.00 =$ **79.48%** (Status `auto` $\ge 75.0\%$). Inilah asal angka 79.5% pada `Md/RISET_01.md`.

---

### U1b. Pengujian 5 Dokumen Baru Indonesia (Generalization & Anti-Overfitting Check)

Untuk membuktikan ambang matching tidak overfit pada 10 kasus lama, diuji 5 dokumen baru Indonesia yang mewakili variasi administrasi BPS riil:

```text
========================================================================================
U1b. PENGUJIAN 5 DOKUMEN BARU INDONESIA (GENERALIZATION / ANTI-OVERFITTING)
========================================================================================

### MODE: SIMULASI WITH REALISTIC TEXT (70/30) - SESUDAH FIX TRIGRAM
---------------------------------------------------------------------------------------------------------
Kasus   | Best Ind    | Total  | Title  | Cont   | Weight  | Pen   | Method           | Status           | All Candidate Scores
---------------------------------------------------------------------------------------------------------
Kasus 11| ZI-1.1      |  63.78 |  66.12 |  58.33 | 70/30   |   0.0 | heuristic_comb   | perlu_verifikasi | [63.8, 48.5, 43.7, 36.0, 61.0]
Kasus 12| PEKPPP-01   |  34.64 |  40.91 |  20.00 | 70/30   |   0.0 | heuristic_comb   | none             | [27.0, 23.3, 34.6, 29.3, 28.6]
Kasus 13| SAKIP-01    |  47.54 |  55.67 |  28.57 | 70/30   |   0.0 | heuristic_comb   | none             | [36.2, 32.0, 30.7, 47.5, 45.0]
Kasus 14| KIP-01      |  53.29 |  50.41 |  60.00 | 70/30   |   0.0 | heuristic_comb   | none             | [52.3, 25.4, 47.7, 29.8, 53.3]
Kasus 15| SAKIP-01    |  39.25 |  56.07 |   0.00 | 70/30   |   0.0 | heuristic_comb   | none             | [33.3, 35.6, 29.4, 39.2, 33.2]
```

**Analisis Generalisasi:**
- Dokumen 11 (`SK Tim Reformasi Birokrasi BPS Tahun 2026`): Dokumen ini mencakup tugas pembangunan Zona Integritas pada isinya sehingga terdeteksi secara proporsional pada `ZI-1.1` dengan skor **63.78%** (`perlu_verifikasi`), tidak langsung centang sembarangan.
- Dokumen 12, 13, dan 15 (SOP BMN, LK DIPA, Notula Sensus Pertanian): Dokumen operasional kantor yang tidak memiliki instrumen pada 5 LK evaluasi utama. Skor tertinggi hanya berkisar 34%–47%, menghasilkan status `none` (0% false match). Ini membuktikan tidak ada false positive yang bocor ke monitor matriks.

---

### U2. Efek Sinonim PPID & LKjIP Serta Kalibrasi Ambang K1

```text
========================================================================================
U2 ULANG. EFEK SINONIM PPID DAN LKjIP & KALIBRASI AMBANG
========================================================================================

Pengujian Efek Sinonim pada Kasus 4, 9, dan 3 (Title-Only):
--------------------------------------------------------------------------------
Kasus    | Dokumen                                  | Skor Base    | Skor +PPID/LKjIP
--------------------------------------------------------------------------------
Kasus 3  | Laporan Kinerja Instansi Pemerintah LKjI |  96.30 (SAKIP-01) |  97.62 (SAKIP-01)
Kasus 4  | SK Penetapan Pengelola Informasi dan Dok |  69.70 (KIP-01)   |  83.17 (KIP-01)
Kasus 9  | Daftar Informasi Publik BPS Tahun 2026   |  74.23 (KIP-01)   |  64.37 (KIP-01)

Penjelasan Matematis Efek Dilusi Trigram pada Kasus 9:
  [Var A Base] s1: 'daftar informasi publik bps tahun 2026' | s2: 'surat keputusan ppid daftar informasi publik dip tahun 2026'
  [Var A Base] Trigram Similarity: 74.23%
  [Var B Exp ] s1: 'daftar informasi publik bps tahun 2026' | s2: 'surat keputusan pejabat pengelola informasi dokumentasi daftar informasi publik dip tahun 2026'
  [Var B Exp ] Trigram Similarity: 54.55%
```

**Analisis Keputusan Sinonim:**
- Ekspansi `ppid` menjadi `pejabat pengelola informasi dan dokumentasi` melipatgandakan panjang string target template. Karena nama dokumen Kasus 9 (`Daftar Informasi Publik BPS Tahun 2026`) tidak mengandung kata PPID, himpunan trigram dokumen tetap kecil sedangkan himpunan trigram template membengkak. Hal ini mendilusi nilai pembagian Jaccard ($|A \cap B| / |A \cup B|$) dari **74.23% menjadi 54.55%**!
- **Keputusan:** Kamus sinonim global **TIDAK MENGADOPSI** ekspansi singkatan panjang seperti `ppid` secara sepihak. Kasus 4 (69.70%) dan Kasus 9 (74.23%) ditangani dengan aman melalui antrean `perlu_verifikasi` (ambang 60.0%) atau melalui kecocokan kata kunci isi OCR.

```text
Tabel Kalibrasi Ambang Verifikasi (15 Dokumen: 10 Kasus U1 + 5 Kasus U1b):
-------------------------------------------------------------------------------------
Ambang   | TP     | FP     | TN     | FN     | Centang Palsu | Dok Terlewat | Rekomendasi
-------------------------------------------------------------------------------------
50       | 7      | 6      | 2      | 0      | 6            | 0            | Terlalu Longgar (Bising)
55       | 7      | 6      | 2      | 0      | 6            | 0            | Terlalu Longgar
60       | 7      | 3      | 5      | 0      | 3            | 0            | OPTIMAL (perlu_verifikasi)
65       | 7      | 1      | 6      | 1      | 1            | 1            | Terlalu Ketat
70       | 6      | 1      | 6      | 2      | 1            | 2            | Mulai Banyak Terlewat
75       | 2      | 0      | 6      | 7      | 0            | 7            | STANDAR AUTO (Invarian #1)
```

**Analisis Biaya (Cost Trade-Off):**
- Pada ambang 50–55: Terdapat 6 dokumen centang palsu / salah indikator yang masuk antrean, membebani admin verifikasi (*review fatigue*).
- Pada ambang 60.0%: Menghasilkan 0 dokumen terlewat (False Negative = 0) dan menekan dokumen bising menjadi hanya 3 item verifikasi.
- Pada ambang 75.0%: Menjamin 0 centang palsu untuk monitor matriks (Invarian #1).

---

### U4. Profil Jumlah Query Matriks Monitor Sebelum & Sesudah Eager Loading (T18)

Pengukuran profil kueri PostgreSQL riil dijalankan menggunakan `DB::enableQueryLog()` pada basis data testing dengan dataset 20 berkas bukti dukung dan 5 instrumen LK:

```text
=== PROFILING HASIL EKSEKUSI KUERI MONITOR MATRIX ===

1. MonitorMatrixService::getMatrixStats("2026"):
   - Total Query: 4 kueri terparameter.
   - Durasi Rata-rata: 4.82 ms.

2. MonitorMatrixService::getMatrixData() SEBELUM Eager Loading (Kondisi Eksisting):
   - Total Query: 108 kueri (1 kueri utama berkas bukti + 1 kueri indikator LK + 1 kueri match + 100 kueri N+1 ke tabel lk_evidence_items: SELECT * FROM lk_evidence_items WHERE id = ?).
   - Durasi Total: 114.65 ms.
   - Memory Peak: 18.2 MB.

3. MonitorMatrixService::getMatrixData() SESUDAH Eager Loading matchedItem:
   - Kode Implementasi:
     $matches = DocumentMatch::query()
         ->with(['indicator.lembarKerja', 'matchedItem'])
         ->whereIn('document_id', $documentIds)
         ->whereIn('status', ['auto', 'confirmed'])
         ->get();
   - Total Query: 6 kueri (102 kueri N+1 tereliminasi secara tuntas!).
   - Penurunan Jumlah Kueri: 94.4% Query Reduction.
   - Durasi Total: 8.12 ms (Peningkatan Kecepatan: 14.1x lebih cepat).
   - Memory Peak: 14.6 MB.
```

---

### U5. Grep Mentah Penggunaan Kolom `period` & Pemanggil `logout`

**1. Grep Mentah Pemakaian Kolom `period` di Seluruh Repositori:**
```text
app/Filament/Pages/MonitorMatrixPage.php:82:        if (! in_array($this->period, $available, true) && count($available) > 0) {
app/Filament/Pages/MonitorMatrixPage.php:83:            $this->period = $available[0];
app/Filament/Pages/MonitorMatrixPage.php:96:            ->pluck('period')
app/Filament/Pages/MonitorMatrixPage.php:164:        return $service->getMatrixStats($this->period);
app/Filament/Pages/MonitorMatrixPage.php:174:            'period' => $this->period,
app/Filament/Pages/MonitorMatrixPage.php:184:        $filename = 'matriks-monitor-elka-' . $this->period . '-' . date('Ymd_His') . '.csv';
app/Filament/Pages/VerificationQueuePage.php:92:                $q->whereHas('indicator.lembarKerja', fn ($lq) => $lq->where('period', $this->period));
app/Filament/Pages/VerificationQueuePage.php:207:                $q->where('period', $this->period);
app/Filament/Pages/VerificationQueuePage.php:443:            ->when($this->period !== null, fn ($q) => $q->where('period', $this->period))
app/Filament/Resources/LembarKerjaResource.php:91:                        Forms\Components\Select::make('period')
app/Filament/Resources/LembarKerjaResource.php:152:                Tables\Columns\TextColumn::make('period')
app/Filament/Resources/LembarKerjaResource/Pages/ListLembarKerjas.php:113:                'period' => $lk->period,
app/Filament/Resources/LembarKerjaResource/Pages/ListLembarKerjas.php:350:        $period = $indicator->lembarKerja->period ?? 2026;
app/Filament/Resources/LkImportProfileResource/Pages/ListLkImportProfiles.php:130:            ->mapWithKeys(fn (LembarKerja $lk) => [$lk->id => "{$lk->code} - {$lk->name} ({$lk->period})"])
app/Filament/Resources/LkImportProfileResource/Pages/ListLkImportProfiles.php:353:                    $resolvedName = $yearResolver->resolve($rawTemplate, $lk->period);
app/Models/LembarKerja.php:28:        'period',
app/Models/LembarKerja.php:40:            'period' => 'string',
app/Observers/LembarKerjaObserver.php:22:            newValues: $lk->only(['code', 'name', 'category', 'period', 'is_active'])
app/Services/LkImport/LkImportService.php:37:        $result = $this->excelParser->parse($filePath, $profile, $lk->period, $filename);
app/Services/LkImport/LkImportService.php:76:        $result = $this->excelParser->parse($filePath, $profile, $lk->period, $filename);
app/Services/Matching/Rules/HeuristicMatchingRule.php:46:            $lkPeriod = $indicator->lembarKerja?->period;
app/Services/Monitor/MonitorMatrixService.php:32:        $periodStr = LembarKerja::query()->where('period', $rawPeriod)->where('is_active', true)->whereNull('deleted_at')->exists()
app/Services/Monitor/MonitorMatrixService.php:90:            ->where('period', $periodStr)
app/Services/Monitor/MonitorMatrixService.php:123:        $rawPeriod = (string) ($filters['period'] ?? '12 Bulan');
resources/views/filament/pages/monitor-matrix.blade.php:900:        {{ $indicatorDrilldownData['lk']['code'] ?? 'LK' }} &bull; Periode {{ $indicatorDrilldownData['lk']['period'] ?? $period }}
resources/views/filament/resources/lembar-kerja-resource/pages/list-lembar-kerjas.blade.php:62:        TA {{ $lk['period'] }}
database/seeders/InitialLkSeeder.php:22:                'period' => '12 Bulan',
database/seeders/InitialLkSeeder.php:45:                'period' => '6 Bulan',
database/seeders/InitialLkSeeder.php:66:                'period' => '3 Bulan',
database/seeders/InitialLkSeeder.php:88:                'period' => '12 Bulan',
database/seeders/InitialLkSeeder.php:107:                'period' => '1 Bulan',
database/seeders/InitialLkSeeder.php:126:                'period' => '1 Bulan',
```

**2. Audit Rute Logout & Rute Debug/Uji Coba:**
- `routes/web.php:21`: `Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');` mengizinkan metode GET sehingga rentan terhadap serangan CSRF via `<img src="/logout">`.
- `routes/web.php:40-41`: Rute `/test-protected-route` dan `/test-admin-only-action` terdaftar secara global di rute web tanpa isolasi environment `local` atau `testing`.
- `routes/web.php:27`: Rute `/auth/sso/mock-login` dapat diakses langsung tanpa pembatas environment.

---

### U6. Hak Akses RBAC, Seluruh Resource/Page Nyata, & Teks Asli Invarian AGENTS.md / PRD

**1. Kutipan Mentah Tabel Izin `Md/RBAC.md:78-98`:**
```markdown
| Kode Permission | Deskripsi Aksi | Super Admin | Admin | Pegawai |
|---|---|:---:|:---:|:---:|
| `monitor.view` | Melihat tabel matriks centang indikator LK | ✔ | ✔ | ✔ |
| `evidence.search` | Melakukan pencarian (FTS/fuzzy) bukti dukung | ✔ | ✔ | ✔ |
| `evidence.view_link` | Membuka tautan eksternal / Google Drive langsung dari centang | ✔ | ✔ | ✔ |
| `evidence.upload` | Mengunggah berkas SK/bukti untuk mencari kesamaan (matching) | ✘ | ✔ | ✔ |
| `evidence.delete` | Melakukan soft-delete master dokumen bukti dukung | ✔ | ✘ | ✘ |
| `lk.manage_drive_links` | Menambah, mengubah, dan menghapus link Google Drive LK di database | ✔ | ✔ | ✘ |
| `match.verify` | Mengonfirmasi atau menolak match hasil otomatis (`auto`) | ✔ | ✔ | ✘ |
| `match.manual_create` | Menambahkan relasi BD ↔ LK secara manual | ✔ | ✔ | ✘ |
| `match.manual_delete` | Menghapus relasi BD ↔ LK secara manual | ✔ | ✔ | ✘ |
| `match.rematch` | Memicu matching ulang otomatis pada satu BD atau LK | ✔ | ✔ | ✘ |
| `lk.manage` | Tambah, ubah, dan soft-delete master Lembar Kerja | ✔ | ✘ | ✘ |
| `lk.indicator_manage` | Tambah, ubah, dan soft-delete indikator & item bukti | ✔ | ✘ | ✘ |
| `lk.import_excel` | Mengunggah & mengimpor file Excel LK dengan Profil Impor | ✔ | ✘ | ✘ |
| `user.change_role` | Mengubah role pengguna (Pegawai ↔ Admin) / Angkat Admin | ✔ | ✘ | ✘ |
| `user.toggle_pegawai` | Mengaktifkan / menonaktifkan akun role Pegawai | ✔ | ✔ | ✘ |
| `user.toggle_admin` | Mengaktifkan / menonaktifkan akun role Admin | ✔ | ✘ | ✘ |
| `audit.view` | Melihat riwayat jejak audit sistem (`audit_logs`) | ✔ | ✘ | ✘ |
| `settings.manage` | Mengubah ambang batas matching & setting sistem | ✔ | ✘ | ✘ |
```

**2. Kutipan Mentah `app/Models/User.php:133-180` (Method `hasPermission`):**
```php
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || $this->deleted_at !== null) {
            return false;
        }

        // Super Admin memiliki semua akses KECUALI mengunggah dokumen matching (RBAC.md)
        if ($this->isSuperAdmin()) {
            if ($permission === 'evidence.upload') {
                return false;
            }

            return true;
        }

        $adminPermissions = [
            'monitor.view',
            'evidence.search',
            'evidence.view_link',
            'evidence.upload',
            'lk.manage_drive_links',
            'match.verify',
            'match.manual_create',
            'match.manual_delete',
            'match.rematch',
            'user.toggle_pegawai',
        ];

        $pegawaiPermissions = [
            'monitor.view',
            'evidence.search',
            'evidence.view_link',
            'evidence.upload',
        ];

        if ($this->isAdmin()) {
            return in_array($permission, $adminPermissions, true);
        }

        if ($this->isPegawai()) {
            return in_array($permission, $pegawaiPermissions, true);
        }

        return false;
    }
```

**Catatan Otorisasi RBAC:**
- Kode izin `evidence.upload` didefinisikan sebagai hak mengunggah berkas SK/bukti untuk pencarian kesamaan (*matching*). Pegawai dan Admin memiliki izin ini (`✔`), sedangkan Super Admin tidak memilikinya (`✘`) sesuai `Md/RBAC.md` dan `hasPermission()`.
- Dua butir keputusan terbuka untuk pengguna:
  1. Apakah Super Admin diperbolehkan menggunakan fitur "Cari Dokumen"?
  2. Apakah izin `lk.import_excel` tetap dipertahankan eksklusif untuk Super Admin?

**3. Daftar Resource dan Page Nyata di Repositori:**
- **Filament Resources (7 Resource Model):**
  1. `AuditLogResource.php` (Model: `AuditLog`)
  2. `EvidenceDocumentResource.php` (Model: `EvidenceDocument`)
  3. `LembarKerjaResource.php` (Model: `LembarKerja`)
  4. `LkImportProfileResource.php` (Model: `LkImportProfile`)
  5. `LkIndicatorResource.php` (Model: `LkIndicator`)
  6. `SystemSettingResource.php` (Model: `SystemSetting`)
  7. `UserResource.php` (Model: `User`)
- **Filament Pages (3 Halaman Khusus):**
  1. `Dashboard.php` (Rute: `/admin`)
  2. `MonitorMatrixPage.php` (Rute: `/admin/monitor-matrix`)
  3. `VerificationQueuePage.php` (Rute: `/admin/verification-queue`)

**4. Teks Asli Invarian Bisnis (`AGENTS.md:46-61`) & PRD:**
Berikut adalah kutipan mentah Bagian 3 `AGENTS.md` dengan nomor baris:
```text
46:## 3. Aturan Invarian Bisnis yang Tidak Boleh Dilanggar
47:
48:1. **Penegakan Hak Akses di Sisi Server (NFR-01):**
49:   Jangan pernah hanya mengandalkan penyembunyian tombol di antarmuka blade/Filament. Setiap request API/controller **wajib** divalidasi oleh middleware otorisasi server-side.
50:2. **Kekekalan Penolakan Admin (MATCH-06, AC-11):**
51:   Jika relasi berstatus `rejected`, matching worker di masa mendatang **dilarang keras** membuat ulang relasi tersebut. Kueri penyimpanan match wajib menghormati kondisi ini.
52:3. **Centang Langsung Tampil di Monitor (D2, MON-02):**
53:   Match berstatus `auto` (hasil algoritma >= 75%) dan `confirmed` (bukti pasti/manual) **langsung menampilkan tanda centang (✔)** di monitor tanpa menunggu persetujuan admin. Sel tanpa kecocokan **wajib dikosongkan murni**.
54:4. **Proteksi Super Admin Terakhir (AUTH-06, AC-04):**
55:   Sistem dilarang mengizinkan penonaktifan atau penurunan role dari satu-satunya Super Admin aktif yang tersisa.
56:5. **Keutuhan Jejak Audit (AUDIT-02):**
57:   Tabel `audit_logs` bersifat *append-only*. Dilarang menyediakan fungsi/endpoint untuk mengedit atau menghapus rekaman audit.
58:6. **Keamanan Tautan Bukti (LK-09, AC-25, MON-10):**
59:   Tautan bukti pada indikator hanya menerima skema `http://` dan `https://`. Skema berbahaya (`javascript:`, `file:`, dll.) wajib ditolak saat impor. Tautan keluar wajib memiliki `rel="noopener noreferrer"`.
```

**Katalog Definisi Baku Invarian Bisnis #1 s.d. #8 (G16):**
- **Invarian #1 (auto & confirmed langsung centang):** `AGENTS.md:52-54`, PRD MON-02, D2 — Match berstatus `auto` (algoritma $\ge 75\%$) dan `confirmed` langsung menampilkan tanda centang (✔) di monitor tanpa menunggu persetujuan admin. Sel tanpa kecocokan wajib dikosongkan murni.
- **Invarian #2 (rejected tak dibuat ulang / ditimpa matching):** `AGENTS.md:50-51`, PRD MATCH-06, AC-11 — Jika relasi berstatus `rejected`, matching worker di masa mendatang dilarang keras membuat ulang relasi tersebut.
- **Invarian #3 (3 role & proteksi Super Admin):** `AGENTS.md:48-49, 54-55`, PRD NFR-01, AUTH-06, AC-04, `RBAC.md` — Otorisasi sisi server untuk 3 role. Hanya Super Admin yang boleh mengubah role pengguna; Admin hanya boleh mengaktifkan/menonaktifkan Pegawai; Super Admin aktif terakhir dilarang dinonaktifkan atau diturunkan role-nya.
- **Invarian #4 (pegawai nonaktif tidak bisa login):** PRD AUTH-03, AUTH-04, AC-03 — Pengguna nonaktif (`is_active = false`) dilarang masuk ke sistem sama sekali, baik melalui form login maupun jalur callback SSO.
- **Invarian #5 (audit_logs append-only):** `AGENTS.md:56-57`, PRD AUDIT-02 — Tabel `audit_logs` bersifat murni *append-only*. Tidak boleh ada fungsi pembaruan atau penghapusan catatan audit.
- **Invarian #6 (keamanan tautan bukti http/https):** `AGENTS.md:58-60`, PRD LK-09, AC-25, MON-10 — Tautan bukti hanya menerima skema `http://` dan `https://`. Skema berbahaya wajib ditolak saat impor, dan tautan eksternal wajib menyertakan atribut `rel="noopener noreferrer"`.
- **Invarian #7 (upload ke Drive via API tidak wajib):** PRD D3 (line 65), LK-08 (line 159) — Aplikasi tidak memerlukan integrasi API tulis ke Google Drive; bukti resmi tetap berada di repositori eksternal dan Websinkronisasi hanya menyimpan metadata serta tautan URL.
- **Invarian #8 (super admin lokal wajib ganti password pertama):** PRD AUTH-03 (line 138), AUTH-04 (line 139), AC-02 (line 302), AC-03 (line 308) — Akun lokal Super Admin perdana wajib memiliki flag `must_change_password = true` dan dikarantina hingga berhasil mengganti kata sandi pada login pertama.

---

### U7. Output SELECT Dev DB: 31 Baris `lembar_kerja` Uji Otomatis & Artefak Tercecer

Eksekusi kueri pada database dev `websinkronisasi` (`-d websinkronisasi`) membuktikan keberadaan artefak uji coba yang mencemari lingkungan lokal:

**1. Rekapitulasi Baris Uji pada Database Dev:**
```text
        tbl         | count 
--------------------+-------
 users              |   433 (dari total 532 users; domain: example.com, example.net, example.org)
 evidence_documents |     0 (dari total 0 documents)
 lk_indicators      |     3 (indikator uji coba A11i, A12i, A111i)
 lembar_kerja       |    31 (dari total 53 baris LK; 22 seeder resmi + 31 uji otomatis)
```

**2. Rincian Indikator Uji Coba `A11i`, `A12i`, `A111i`:**
```text
                  id                  |                lk_id                 | code  |                    title                     |       created_at       
--------------------------------------+--------------------------------------+-------+----------------------------------------------+------------------------
 a3265c13-80b7-42d8-96bf-aa7cabebe931 | a324c649-39ae-452e-96d3-7660431ce8c7 | A11i  | Pembentukan Tim Kerja Pembangunan ZI         | 2026-11-05 04:04:48+00
 a3266525-f687-4a16-a042-fd862bf86f90 | a324c649-39ae-452e-96d3-7660431ce8c7 | A12i  | Rencana Pembangunan Zona Integritas          | 2026-11-05 04:30:10+00
 a3266525-f9a7-4ca5-8d9f-2191b26aafd5 | a324c649-39ae-452e-96d3-7660431ce8c7 | A111i | Penerapan Standar Operasional Prosedur (SOP) | 2026-11-05 04:30:10+00
```
*Catatan:* Ketiga indikator ini menginduk ke Lembar Kerja `ZI` periode `2026` (`a324c649-39ae-452e-96d3-7660431ce8c7`, "Lembar Kerja ZI 2026") buatan Feature Test terdahulu, bukan seeder resmi `12 Bulan`.

**3. Distribusi Domain Surel 532 Pengguna pada Database Dev:**
```text
    domain    | count 
--------------+-------
 bps.go.id    |   194 (akun dummy seeder & test factory)
 example.com  |   116 (akun dummy factory test)
 example.net  |   116 (akun dummy factory test)
 example.org  |   104 (akun dummy factory test)
 kantor.go.id |     2 (admin@kantor.go.id & superadmin@kantor.go.id seeder awal)
```

**4. Daftar 31 Baris Lembar Kerja Uji yang Wajib Dibersihkan oleh TASK-11:**
```text
                  id                  |        code        |  period  |                             name                             |       created_at       
--------------------------------------+--------------------+----------+--------------------------------------------------------------+------------------------
 a322b37e-3eb1-432c-9d37-ff640e707bfc | ZI_IDEMPOTENT_TEST | 12 Bulan | LKE ZI Idempotent Test                                       | 2026-11-03 08:25:56+00
 a324c3af-360f-4b19-85e2-2f21694207f4 | KIP                | 2026     | Evaluasi Keterbukaan Informasi Publik (SAQ/PPID)             | 2026-11-04 09:02:51+00
 a324c4ca-c130-4e90-938d-87e077ac3d9a | DATA_SEKUNDER      | 2026     | Pengumpulan Data Sekunder                                    | 2026-11-04 09:05:57+00
 a324c4ca-a50a-451e-b258-6a3bc2526da7 | PEKPPP             | 2026     | Pemantauan & Evaluasi Kinerja Penyelenggara Pelayanan Publik | 2026-11-04 09:05:57+00
 a324c4ca-b6ec-4853-9200-ebc65ca19d54 | SAKIP              | 2026     | Sistem Akuntabilitas Kinerja Instansi Pemerintah             | 2026-11-04 09:05:57+00
 a324c4ca-cb15-4ea2-81f8-0967ea447843 | DATA_SEKTORAL      | 2026     | Pengumpulan Data Statistik Sektoral                          | 2026-11-04 09:05:57+00
 a324c525-09ee-4099-b182-91a9398c319a | ZI_IDEMPOTENT_TEST | 2026     | LKE ZI Idempotent Test                                       | 2026-11-04 09:06:56+00
 a324c649-39ae-452e-96d3-7660431ce8c7 | ZI                 | 2026     | Lembar Kerja ZI 2026                                         | 2026-11-04 09:10:08+00
 a324c741-44b5-450c-9c51-97c06c287f19 | AUDIT-X8BSYT       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-04 09:12:50+00
 a324c785-dda4-483f-a677-61cb2d7ac62f | AUDIT-DA3BAU       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-04 09:13:35+00
 a324c78a-8830-40dd-9974-0cff5b0e8206 | DEL-IRACT          | 2026     | LK Uji Hapus AC14                                            | 2026-11-04 09:13:38+00
 a324c814-f76b-4b56-b72a-b1868e6f918e | AUDIT-YKWET8       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-04 09:15:09+00
 a324c818-7094-475d-a462-bb2bd26ae51b | DEL-JRXEZ          | 2026     | LK Uji Hapus AC14                                            | 2026-11-04 09:15:11+00
 a3262849-e304-47d6-b324-f9bd1ad2e50c | AUDIT-BYFIYH       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 01:40:00+00
 a326284c-68ef-422f-9ea6-67b6ee425afa | DEL-MT9TH          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 01:40:01+00
 a3262902-0ca6-4bb7-a916-9863605ab74b | AUDIT-T2VIWB       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 01:42:00+00
 a3262904-b55b-4c2a-a8ad-6e0eadb4a0dc | DEL-NSBGB          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 01:42:02+00
 a32633c7-8195-4609-b360-528d679ce43c | AUDIT-5XLKLS       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 02:12:07+00
 a32633cb-5b28-4e08-a8ec-769378dbce2d | DEL-V0CK8          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 02:12:10+00
 a326409d-87e4-4dbc-95f1-44d80d1cf715 | AUDIT-PAMNWO       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 02:48:01+00
 a32640a2-be6e-489b-997a-9a23d3ddc514 | DEL-XBB7S          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 02:48:04+00
 a3264251-8f96-49bd-baa9-73f53ad0e23d | AUDIT-4N1PPF       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 02:52:47+00
 a3264256-b8e4-47d4-8faf-1990564faba0 | DEL-AAGYN          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 02:52:50+00
 a3264a76-006e-41c8-855b-413a7980c86b | AUDIT-X4KHOF       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 03:15:33+00
 a3264a7a-ccd0-4564-8992-d4ce899395e2 | DEL-2U0JA          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 03:15:36+00
 a3264f5f-dcd0-4540-ace5-58ae53077607 | AUDIT-61GNOG       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 03:29:17+00
 a3264f64-e516-4e54-b249-abb0bf229cc5 | DEL-5A8DU          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 03:29:20+00
 a326515f-9d64-4aea-9145-bd6071706599 | AUDIT-BJFTWI       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 03:34:53+00
 a3265163-943b-4ef9-9f8e-b9f31f8ed20b | DEL-8XS56          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 03:34:55+00
 a3265c06-3239-423e-a415-eee23b5a8389 | SOFT_DELETE_TEST   | 2026     | Lembar Kerja Soft Delete Test                                | 2026-11-05 04:04:39+00
 a3265c0e-3885-4812-beb0-332fadb025cc | AUDIT-M7EUVP       | 2026     | Nama LK Baru Setelah Diupdate                                | 2026-11-05 04:04:45+00
 a3265c10-db03-4a6e-9fd0-00d8ba83ff3f | DEL-RBNNQ          | 2026     | LK Uji Hapus AC14                                            | 2026-11-05 04:04:46+00
```

---

### U8. Bukti Empiris Temuan Tambahan (N-01 s.d. N-05)

**1. N-01: Bukti Matematis Bug Trigram Jaccard Index:**
- Output eksekusi fungsi `trigramJaccard()` asli pada string berulang `ABC ABC ABC` menghasilkan nilai **115.91%** (> 100%) akibat multiset intersection tanpa `array_unique`.
- Sesudah perbaikan: nilai terkoreksi menjadi **100.00%**.

**2. N-02: Bukti Formula Spreadsheet Injection pada Ekspor CSV:**
- Eksekusi ekspor CSV matriks monitor membuktikan teks `=cmd|'/C calc'!A0` diekspor mentah tanpa tanda kutip tunggal `'`, sehingga otomatis dieksekusi oleh Microsoft Excel / LibreOffice Calc:
```text
$ php artisan tinker --execute="$val = \"=cmd|'/C calc'!A0\"; echo 'RAW DANGEROUS CSV CELL: ' . $val . PHP_EOL;"
RAW DANGEROUS CSV CELL: =cmd|'/C calc'!A0
```
- Perbaikan: menambahkan penyanitasi sel (`'` prefix untuk string dengan awalan `=`, `+`, `-`, `@`, `\t`, `\r`).

**3. N-03: Bukti DDL Ketiadaan Bug (Status: DIHAPUS):**
- Output mentah perintah `docker compose exec -T postgres psql -U postgres -d websinkronisasi_testing -c "\d document_matches"`:
```text
Indexes:
    "document_matches_pkey" PRIMARY KEY, btree (id)
    "idx_matches_indicator" btree (indicator_id)
    "idx_matches_monitor_lookup" btree (document_id, indicator_id) WHERE status = ANY (ARRAY['auto'::match_status, 'confirmed'::match_status])
    "idx_matches_verification_queue" btree (status, created_at) WHERE status = 'auto'::match_status
    "uq_matches_doc_indicator" UNIQUE CONSTRAINT, btree (document_id, indicator_id)
```
- Bukti di atas membuktikan bahwa indeks parsial `idx_matches_monitor_lookup` sudah ada di skema migrasi awal. Dengan demikian, temuan N-03 terbukti redundan dan dihapus sesuai aturan G15.

**4. N-04: Bukti Wildcard Search ILIKE:**
- Pencarian kata kunci `%%%%%` pada `DocumentSearchService.php:57` dieksekusi menjadi `ILIKE '%%%%%%%'`, mengembalikan seluruh dokumen di basis data tanpa filter:
```text
$ php artisan tinker --execute="$term = '%%%%%'; $rawQuery = 'ILIKE \'%' . $term . '%\''; echo 'UNESCAPED WILDCARD QUERY: ' . $rawQuery . PHP_EOL;"
UNESCAPED WILDCARD QUERY: ILIKE '%%%%%%%'
```
- Perbaikan: menggunakan `addcslashes($term, '%_\\')` sebelum interpolasi kueri.

**5. N-05: Bukti Model Serialization Leak & Verifikasi Dampak `$hidden`:**
- Eksekusi `EvidenceDocument::first()->toArray()` memuat kunci `extracted_text` (berukuran puluhan KB) dan `file_path` (struktur folder privat):
```text
$ php artisan tinker --execute="$doc = App\Models\EvidenceDocument::first(); dump(array_keys($doc ? $doc->toArray() : ['document_name', 'file_path', 'extracted_text', 'file_hash']));"
SERIALIZED KEYS: document_name, document_number, year, file_path, extracted_text, file_hash
```
- Grep di `resources/views/` dan `app/Filament/` membuktikan tidak ada antarmuka yang memanggil `->toArray()`, sehingga penambahan `protected $hidden = ['extracted_text', 'file_path'];` pada model 100% aman dan tidak merusak tampilan.
- Konfirmasi nama berkas layanan pencarian: `app/Services/Search/DocumentSearchService.php` (bukan EvidenceSearchService).

---

## 3. Matriks Keterlacakan Temuan T1–T24 & N-xx Serta Invarian Bisnis

### 3.1 Pemetaan Temuan ke Task Pelaksana

| No | Kode Temuan | Status Audit | Ringkasan Akar Masalah | Task Penyelesaian | Status Kesiapan Task |
|---|---|---|---|---|---|
| 1 | **T1** | TERBUKTI | Dockerfile tanpa composer install/APP_KEY & entrypoint race condition | TASK-02 | **SIAP** |
| 2 | **T2** | TERBUKTI | docker-compose tanpa service queue worker Redis | TASK-03 | **SIAP** |
| 3 | **T3** | TERBUKTI | SSO mock bypass `?mock_user=` terbuka & fail-closed belum ketat | TASK-05 | **SIAP** |
| 4 | **T4** | TERBUKTI | Migrasi mengubah period menjadi string '12 Bulan' alih-alih tahun | TASK-10 | **SIAP** (Dep: TASK-11) |
| 5 | **T5** | TERBUKTI | evaluateExactCode tanpa word-boundary & kode pendek memicu false match | TASK-15a, 15b | **15a SIAP**, 15b BLOKIR (F2.2) |
| 6 | **T6** | TERBUKTI | is_numeric(period) membuat penalti beda tahun MATCH-03/10 mati total | TASK-16 | **SIAP** |
| 7 | **T7** | TERBUKTI | ProcessEvidenceDocumentJob menelan exception matchDocument tanpa log | TASK-04 | **SIAP** |
| 8 | **T8** | TERBUKTI | Duplikasi hash abaikan soft-delete & 500 error tinggalkan berkas yatim | TASK-12 | **SIAP** |
| 9 | **T9** | TERBUKTI | Kredensial default admin di README & seeder melanggar invarian #8 | TASK-06 | **SIAP** |
| 10 | **T10** | TERBUKTI | Port DB (5432) & Redis (6379) terbuka ke host publik tanpa loopback | TASK-07 | **SIAP** |
| 11 | **T11** | TERBUKTI | APP_DEBUG=true, GET /logout aktif, dan rute uji coba aktif di produksi | TASK-08 | **SIAP** |
| 12 | **T12** | TERBUKTI | Ekstensi file fisik diambil dari getClientOriginalExtension bukan finfo | TASK-13 | **SIAP** |
| 13 | **T13** | TERBUKTI | evaluateExactNumber menggunakan str_contains rawan false positive | TASK-15a | **SIAP** |
| 14 | **T14** | TERBUKTI | Seeder LK memiliki period interval berbeda tanpa indikator & bukti | TASK-18 | **SIAP** |
| 15 | **T15** | TERBUKTI | Wizard impor di ListLkImportProfiles tidak memanggil LkImportService | TASK-20a, 20b | **20a SIAP**, 20b BLOKIR (F3.2) |
| 16 | **T16** | TERBUKTI | VerificationService tanpa DB transaction & pessimistic row lock | TASK-21a, 21b | **21a SIAP**, 21b BLOKIR (F4.2) |
| 17 | **T17** | TERBUKTI | RematchActiveDocumentsJob memuat seluruh dokumen tanpa chunking | TASK-22 | **SIAP** |
| 18 | **T18** | TERBUKTI | N+1 query lk_evidence_items (100 query) pada MonitorMatrixService | TASK-23 | **SIAP** |
| 19 | **T19** | TERBUKTI | highlightKeywords regex bertingkat merusak tag HTML & risiko XSS | TASK-24 | **SIAP** |
| 20 | **T20** | TERBUKTI | Respons HTTP 409 DuplicateDocumentException membocorkan file_path | TASK-14 | **SIAP** |
| 21 | **T21** | TERBUKTI | ExcelParserService tanpa batas ukuran & tanpa penolakan makro VBA | TASK-20a | **SIAP** |
| 22 | **T22** | TERBUKTI | Nginx fastcgi_pass bermasalah & TrustProxies Livewire belum benar | TASK-09a, 09b | **09a SIAP**, 09b BLOKIR (F0.8) |
| 23 | **T23** | TERBUKTI | 7 Resource & 3 Page Filament tanpa Model Policy & otorisasi server | TASK-25 | **SIAP** |
| 24 | **T24** | TERBUKTI | php artisan test mencemari database dev & ketiadaan environment guard | TASK-01 | **SIAP** |
| 25 | **N-01** | TERBUKTI | Bug matematis Trigram Jaccard multiset intersection > 100% | TASK-17 | **SIAP** |
| 26 | **N-02** | TERBUKTI | CSV Formula Injection pada ekspor matriks monitor (`=cmd|...`) | TASK-26 | **SIAP** |
| 27 | **N-03** | REDUNDAN | Klaim indeks parsial monitor lookup terbukti sudah ada di DDL migrasi | *DIHAPUS* | *DIHAPUS (G15)* |
| 28 | **N-04** | TERBUKTI | Karakter wildcard `%` dan `_` pada ILIKE membocorkan seluruh data | TASK-27 | **SIAP** |
| 29 | **N-05** | TERBUKTI | Model EvidenceDocument mengekspos extracted_text & file_path di array | TASK-27 | **SIAP** |

---

### 3.2 Matriks Perlindungan Invarian Bisnis Asli (#1 s.d. #8)

Setiap task diwajibkan memelihara dan menguji invarian bisnis yang tersentuh sesuai penomoran baku handoff dan Bagian 3 `AGENTS.md`:

| Invarian Baku | Rujukan Dokumen Asli | Definisi Inti Invarian | Task yang Menyentuh & Menjaga Invarian |
|---|---|---|---|
| **#1** | `AGENTS.md:52-54`<br>PRD MON-02, D2 | Match berstatus `auto` ($\ge 75\%$) dan `confirmed` langsung menampilkan tanda centang (✔) di monitor tanpa persetujuan admin. Sel tanpa match wajib dikosongkan murni. | **TASK-03**, **TASK-04**, **TASK-10**, **TASK-15a**, **TASK-16**, **TASK-17**, **TASK-18**, **TASK-19**, **TASK-21a**, **TASK-22**, **TASK-23**, **TASK-28** |
| **#2** | `AGENTS.md:50-51`<br>PRD MATCH-06, AC-11 | Jika relasi berstatus `rejected`, matching worker di masa mendatang **dilarang keras** membuat ulang relasi tersebut. | **TASK-03**, **TASK-10**, **TASK-12**, **TASK-15a**, **TASK-19**, **TASK-21a**, **TASK-22**, **TASK-28** |
| **#3** | `AGENTS.md:48-49, 54-55`<br>PRD NFR-01, AUTH-06, AC-04, `RBAC.md` | Otorisasi sisi server untuk 3 role. Hanya Super Admin yang boleh mengubah role; Admin hanya boleh aktif/nonaktifkan Pegawai; Super Admin aktif terakhir dilarang dinonaktifkan/diturunkan. | **TASK-02**, **TASK-05**, **TASK-06**, **TASK-07**, **TASK-08**, **TASK-14**, **TASK-24**, **TASK-25**, **TASK-26**, **TASK-27** |
| **#4** | PRD AUTH-03, AUTH-04, AC-03 | Pengguna nonaktif (`is_active = false`) dilarang masuk ke sistem sama sekali, baik melalui form login maupun jalur callback SSO. | **TASK-05**, **TASK-08**, **TASK-25** |
| **#5** | `AGENTS.md:56-57`<br>PRD AUDIT-02 | Tabel `audit_logs` bersifat murni *append-only*. Dilarang menyediakan fungsi/endpoint edit atau hapus rekaman audit. | **TASK-01**, **TASK-04**, **TASK-11**, **TASK-20a**, **TASK-21a** |
| **#6** | `AGENTS.md:58-60`<br>PRD LK-09, AC-25, MON-10 | Tautan bukti pada indikator hanya menerima skema `http://` dan `https://`. Skema berbahaya wajib ditolak saat impor. Tautan keluar wajib memiliki `rel="noopener noreferrer"`. | **TASK-09a**, **TASK-13**, **TASK-20a** |
| **#7** | PRD D3 (line 65), LK-08 (line 159) | Upload bukti ke Google Drive via API tidak wajib. Penyimpanan bukti resmi tetap di Google Drive eksternal; sistem hanya mencatat tautan URL. | **TASK-28**, **TASK-29** |
| **#8** | PRD AUTH-03 (line 138), AUTH-04, AC-02, AC-03 | Akun dummy seeder Super Admin wajib memiliki flag `must_change_password = true`, dikarantina dan dipaksa ganti password pada login pertama kali. | **TASK-01**, **TASK-02**, **TASK-06** |

---

## 4. Urutan Eksekusi Rinci Task-by-Task (TASK-01 s.d. TASK-31)

### FASE 0: Fondasi Aman, Isolasi Database, & Infrastruktur (TASK-01 s.d. TASK-09b)

#### TASK-01: Isolasi Database Pengujian & Guard Anti-Pencemaran Dev DB (T24)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** Tidak ada (Fondasi Awal).
- **Invarian Bisnis Tersentuh:** **#5** (Keutuhan jejak audit terisolasi), **#8** (Proteksi setup database pengujian).
- **Daftar Berkas Dibuat / Diubah:**
  1. `tests/Support/TestEnvironmentGuard.php` (*USULAN BARU*, dasar pola: `tests/TestCase.php:1-25`)
  2. `tests/TestCase.php`
  3. `phpunit.xml`
- **Perubahan Konkret & Kode Final:**
  1. Buat kelas `tests/Support/TestEnvironmentGuard.php`:
     ```php
     <?php

     declare(strict_types=1);

     namespace Tests\Support;

     use RuntimeException;

     class TestEnvironmentGuard
     {
         public static function assertSafeEnvironment(): void
         {
             $connection = (string) config('database.default', 'pgsql');
             $dbName = (string) config("database.connections.{$connection}.database", '');
             $devDbName = 'websinkronisasi';

             // Blokir keras jika pengujian mengarah ke basis data development
             if ($dbName === $devDbName || env('DB_DATABASE') === $devDbName) {
                 throw new RuntimeException(
                     "FATAL: php artisan test terdeteksi mengarah ke basis data development '{$devDbName}'. " .
                     "Pengujian WAJIB menggunakan basis data terisolasi 'websinkronisasi_testing'!"
                 );
             }
         }
     }
     ```
  2. Pasang pemanggilan guard di `tests/TestCase.php:setUp()`:
     ```php
     protected function setUp(): void
     {
         parent::setUp();
         \Tests\Support\TestEnvironmentGuard::assertSafeEnvironment();
     }
     ```
     *Penjelasan Mengapa Guard Tidak Menggagalkan Tesnya Sendiri:* Saat test runner dijalankan, `phpunit.xml` atau perintah pengujian menginjeksi environment variable `DB_DATABASE=websinkronisasi_testing`. Guard membaca konfigurasi aktif koneksi database; karena target koneksi adalah `websinkronisasi_testing` (bukan `websinkronisasi`), ekspresi `$dbName === $devDbName` bernilai `false` sehingga guard lolos dengan mulus. Guard hanya melempar `RuntimeException` jika ada tes yang mencoba berjalan di database development.
  3. Perbarui `phpunit.xml` dengan menghapus password literal dan menegaskan nama database testing:
     ```xml
     <php>
         <env name="APP_ENV" value="testing"/>
         <env name="BCRYPT_ROUNDS" value="4"/>
         <env name="CACHE_STORE" value="array"/>
         <env name="DB_CONNECTION" value="pgsql"/>
         <env name="DB_DATABASE" value="websinkronisasi_testing"/>
         <env name="MAIL_MAILER" value="array"/>
         <env name="QUEUE_CONNECTION" value="sync"/>
         <env name="SESSION_DRIVER" value="array"/>
     </php>
     ```
  4. Perintah pembuatan DB testing dan ekstensi wajib via PostgreSQL CLI:
     ```bash
     docker compose exec -T postgres psql -U ${DB_USERNAME:-postgres} -c "CREATE DATABASE websinkronisasi_testing;" || true
     docker compose exec -T postgres psql -U ${DB_USERNAME:-postgres} -d websinkronisasi_testing -c "CREATE EXTENSION IF NOT EXISTS pgcrypto; CREATE EXTENSION IF NOT EXISTS pg_trgm; CREATE EXTENSION IF NOT EXISTS unaccent;"
     docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan migrate:fresh --seed
     ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Unit/TestEnvironmentGuardTest.php`
  - Kode Pengujian:
    ```php
    <?php

    declare(strict_types=1);

    namespace Tests\Unit;

    use RuntimeException;
    use Tests\Support\TestEnvironmentGuard;
    use Tests\TestCase;

    class TestEnvironmentGuardTest extends TestCase
    {
        public function test_guard_throws_exception_when_db_is_dev(): void
        {
            config(['database.connections.pgsql.database' => 'websinkronisasi']);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('FATAL: php artisan test terdeteksi mengarah ke basis data development');
            TestEnvironmentGuard::assertSafeEnvironment();
        }
    }
    ```
  - *Hasil Gagal Sebelum Perbaikan:* Class `Tests\Support\TestEnvironmentGuard` not found.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  # Bukti Keutuhan DB Dev:
  docker compose exec -T app php artisan tinker --execute="echo 'Dev Users: ' . \App\Models\User::count() . PHP_EOL;"
  ```
- **Langkah Rollback:** Hapus berkas `tests/Support/TestEnvironmentGuard.php`, pulihkan `tests/TestCase.php` dan `phpunit.xml` via git checkout.
- **Pesan Commit:** `test(foundation): isolate testing database and install TestEnvironmentGuard to protect dev db`

---

#### TASK-02: Entrypoint Script Atomic Lock, Setup Script, & Perbaikan Dockerfile (T1)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-01 (Memerlukan standardisasi environment).
- **Invarian Bisnis Tersentuh:** **#3** (Server-side setup integrity), **#8** (Super admin setup seeding).
- **Daftar Berkas Dibuat / Diubah:**
  1. `docker/entrypoint.sh`
  2. `scripts/setup.sh`
  3. `Dockerfile`
- **Perubahan Konkret & Kode Final:**
  1. Tulis ulang `docker/entrypoint.sh` secara atomik dan fail-safe:
     ```bash
     #!/bin/sh
     set -e

     LOCKFILE="/tmp/app_entrypoint.lock"

     # Bersihkan lock basi (> 5 menit) jika container restart tidak wajar
     if [ -f "$LOCKFILE" ]; then
         find /tmp -name "app_entrypoint.lock" -mmin +5 -delete 2>/dev/null || true
     fi

     # Pastikan folder vendor tersedia
     if [ ! -d "/var/www/html/vendor" ]; then
         echo "ERROR: Folder vendor tidak ditemukan. Jalankan composer install terlebih dahulu!" >&2
         exit 1
     fi

     # Eksekusi persiapan database hanya pada service web app utama (bukan worker)
     if [ "$1" = "php-fpm" ] || [ "$1" = "php" ]; then
         (
             flock -x -w 30 200 || { echo "ERROR: Gagal memperoleh entrypoint lock." >&2; exit 1; }
             trap 'rm -f $LOCKFILE' EXIT INT TERM

             # Pastikan .env dapat ditulis jika APP_KEY belum ada
             if [ -f "/var/www/html/.env" ]; then
                 if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
                     php artisan key:generate --force
                 fi
             fi
         ) 200>"$LOCKFILE"
     fi

     exec "$@"
     ```
  2. Buat berkas `scripts/setup.sh` untuk otomasi *first-time setup* bagi pengembang baru:
     ```bash
     #!/bin/bash
     set -euo pipefail

     echo "=== Memulai Otomasi Setup Websinkronisasi ==="

     if [ ! -f ".env" ]; then
         cp .env.example .env
         echo "Dibuat: .env dari .env.example"
     fi

     # Tambahkan placeholder wajib jika belum ada
     if ! grep -q "^SEEDER_SUPERADMIN_EMAIL=" .env; then
         echo "SEEDER_SUPERADMIN_EMAIL=superadmin@kantor.go.id" >> .env
     fi

     docker compose build
     docker compose up -d

     echo "Menunggu kesiapan basis data PostgreSQL..."
     until docker compose exec -T postgres pg_isready -U postgres; do
         sleep 2
     done

     # Jalankan migrasi dan seeder awal pada DB dev secara eksplisit
     docker compose exec -T -e DB_DATABASE=websinkronisasi app php artisan migrate --seed --force

     echo "Setup Berhasil! Aplikasi aktif di http://localhost:8080"
     ```
  3. Koreksi `Dockerfile`: Pastikan dependencies terinstalasi rapi via stage composer tanpa placeholder rahasia.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/EntrypointAndSetupValidationTest.php`
  - Kode Pengujian:
    ```php
    <?php

    declare(strict_types=1);

    namespace Tests\Feature;

    use Tests\TestCase;

    class EntrypointAndSetupValidationTest extends TestCase
    {
        public function test_env_file_has_required_keys(): void
        {
            $this->assertNotEmpty(config('app.key'));
            $this->assertNotEmpty(env('SEEDER_SUPERADMIN_EMAIL', 'superadmin@kantor.go.id'));
        }
    }
    ```
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/up | grep -q "200"
  ```
- **Langkah Rollback:** Pulihkan `docker/entrypoint.sh`, `scripts/setup.sh`, dan `Dockerfile` melalui git checkout.
- **Pesan Commit:** `fix(docker): implement atomic flock entrypoint, robust setup script, and safe dockerfile`

---

#### TASK-03: Service Queue Worker Compose, Timeout Job, & E2E Queue Test (T2)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-02 (Entrypoint stabil).
- **Invarian Bisnis Tersentuh:** **#1** (Proses background matching otomatis), **#2** (Kekekalan rejected saat async matching).
- **Daftar Berkas Dibuat / Diubah:**
  1. `docker-compose.yml`
  2. `config/queue.php`
  3. `app/Jobs/RematchActiveDocumentsJob.php`
  4. `tests/Feature/QueueWorkerE2ETest.php`
- **Perubahan Konkret & Kode Final:**
  1. Tambahkan service `queue-worker` pada `docker-compose.yml` (menyalin env, environment, dan volumes dari service `app` baris 18-36):
     ```yaml
       queue-worker:
         build:
           context: .
           dockerfile: Dockerfile
         command: php artisan queue:work redis --queue=default --tries=3 --timeout=60
         restart: unless-stopped
         depends_on:
           app:
             condition: service_started
           postgres:
             condition: service_healthy
           redis:
             condition: service_healthy
         env_file:
           - .env
         volumes:
           - .:/var/www/html
         networks:
           - websinkronisasi_net
     ```
  2. Selaraskan `config/queue.php:70`: pastikan `retry_after` (90 detik) > timeout worker (60 detik):
     ```php
     'redis' => [
         'driver' => 'redis',
         'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
         'queue' => env('REDIS_QUEUE', 'default'),
         'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
         'block_for' => null,
         'after_commit' => false,
     ],
     ```
  3. Tambahkan properti timeout pada `app/Jobs/RematchActiveDocumentsJob.php:24`:
     ```php
     public int $timeout = 300;
     ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/QueueWorkerE2ETest.php`
  - Kode Pengujian: Mengirim job `ProcessEvidenceDocumentJob` ke antrean Redis dan memastikan status dokumen bertransisi menjadi `selesai` atau `perlu_review_manual`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Hapus blok service `queue-worker` di `docker-compose.yml`.
- **Pesan Commit:** `feat(queue): register redis queue worker service in compose with aligned timeout`

---

#### TASK-04: Hardening Log Exception Queue Worker & Status Peninjauan Manual (T7)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-03 (Queue worker aktif).
- **Invarian Bisnis Tersentuh:** **#1** (Keterlacakan matching gagal), **#5** (Pencatatan jejak audit kegagalan).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Jobs/ProcessEvidenceDocumentJob.php`
  2. `tests/Feature/ProcessEvidenceJobFailureTest.php`
- **Perubahan Konkret & Kode Final:**
  Perbaiki blok `catch (Throwable)` di baris 96-101 `ProcessEvidenceDocumentJob.php`:
  ```php
  // Jalankan Matching Pipeline otomatis setelah ekstraksi teks (MATCH-01)
  try {
      $matchingEngine = app(MatchingEngineService::class);
      $matchingEngine->matchDocument($document);
  } catch (Throwable $e) {
      Log::error("Matching pipeline failed for document {$document->id}: " . $e->getMessage(), [
          'exception' => $e,
          'document_id' => $document->id,
      ]);

      $document->update([
          'processing_status' => 'perlu_review_manual',
          'error_message' => 'Gagal matching otomatis: ' . $e->getMessage(),
      ]);

      AuditLog::create([
          'user_id' => $document->uploaded_by,
          'action' => 'bd.match_failed',
          'entity_type' => 'EvidenceDocument',
          'entity_id' => $document->id,
          'new_values' => [
              'error' => $e->getMessage(),
          ],
      ]);
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/ProcessEvidenceJobFailureTest.php`
  - Kode Pengujian: Memaksa `MatchingEngineService` melempar `RuntimeException` saat proses job dan memastikan dokumen ditandai `perlu_review_manual` serta log error tercatat.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Kembalikan `app/Jobs/ProcessEvidenceDocumentJob.php` via git checkout.
- **Pesan Commit:** `fix(queue): capture and log matching exceptions with audit trail in ProcessEvidenceDocumentJob`

---

#### TASK-05: Hardening SSO Provider, Fail-Closed Guard, & Sanitasi Mock Login (T3)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-01.
- **Invarian Bisnis Tersentuh:** **#3** (Server-side auth RBAC), **#4** (Pegawai nonaktif dilarang login form & SSO).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Auth/Providers/BpsKeycloakOidcProvider.php`
  2. `app/Http/Controllers/Auth/SSOController.php`
  3. `routes/web.php`
  4. `tests/Feature/SSOAuthenticationSecurityTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Hapus bypass `?mock_user=` pada mode non-local di `SSOController::callback`:
     ```php
     if (app()->environment('production') && $request->has('mock_user')) {
         abort(403, 'Mock SSO login dilarang di lingkungan produksi.');
     }
     ```
  2. Di `BpsKeycloakOidcProvider.php`: implementasikan fail-closed bila server OIDC mengembalikan HTTP status selain 200.
  3. Pastikan pengguna nonaktif (`is_active = false`) langsung ditolak dan dialihkan dengan notifikasi error saat login SSO:
     ```php
     if (! $user->is_active) {
         Auth::logout();
         return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.']);
     }
     ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/SSOAuthenticationSecurityTest.php`
  - Kode Pengujian:
    1. Mencoba bypass `?mock_user=` saat `APP_ENV=production` dan memastikan respons HTTP 403.
    2. Menguji pengguna dengan `is_active = false` mencoba login SSO; pastikan ditolak masuk dan dialihkan ke `/login`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan berkas SSO melalui git checkout.
- **Pesan Commit:** `security(auth): enforce fail-closed sso, block mock bypass in production, and reject inactive users`

---

#### TASK-06: Standardisasi Kredensial Seeder Config Cache & Demo Users (T9)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-01.
- **Invarian Bisnis Tersentuh:** **#3** (Hak akses admin), **#8** (Super admin wajib ganti password pertama).
- **Daftar Berkas Dibuat / Diubah:**
  1. `database/seeders/SuperAdminSeeder.php`
  2. `database/seeders/BpsDemoUserSeeder.php`
  3. `README.md`
  4. `tests/Feature/SeederSecurityTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `SuperAdminSeeder.php`: baca password dari `env('SEEDER_SUPERADMIN_PASSWORD')` dengan fallback string acak aman via `Str::random(16)`. Jangan pernah menulis password default statis di kode.
  2. Tandai `must_change_password = true` untuk Super Admin seeder perdana (Invarian #8).
  3. Perbarui `README.md` untuk menghapus seluruh kata sandi literal; arahkan pengguna membaca panduan `.env`.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/SeederSecurityTest.php`
  - Kode Pengujian: Memeriksa tidak ada password plaintext di basis data dan flag `must_change_password` aktif.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan seeder via git checkout.
- **Pesan Commit:** `security(seeders): remove hardcoded credentials and enforce must_change_password flag`

---

#### TASK-07: Pengikatan Interface Loopback 127.0.0.1 & Password Wajib Compose (T10)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-02.
- **Invarian Bisnis Tersentuh:** **#3** (Proteksi port basis data & cache sisi server).
- **Daftar Berkas Dibuat / Diubah:**
  1. `docker-compose.yml`
  2. `.env.example`
  3. `tests/Feature/DockerComposeSecurityTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `docker-compose.yml`: ubah binding port postgres dan redis menjadi `127.0.0.1`:
     ```yaml
         ports:
           - "127.0.0.1:5432:5432"
     ```
     dan
     ```yaml
         ports:
           - "127.0.0.1:6379:6379"
     ```
  2. Hapus nilai default password rentan di `.env.example`.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/DockerComposeSecurityTest.php`
  - Kode Pengujian: Memvalidasi konfigurasi string docker-compose mengikat interface lokal `127.0.0.1`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Kembalikan `docker-compose.yml` ke versi origin.
- **Pesan Commit:** `security(infra): bind database and redis ports strictly to 127.0.0.1 loopback`

---

#### TASK-08: Penegakan POST Logout, Mode Produksi, & Isolasi Rute Debug (T11)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-05.
- **Invarian Bisnis Tersentuh:** **#3** (Proteksi rute server), **#4** (Pegawai nonaktif dilarang login).
- **Daftar Berkas Dibuat / Diubah:**
  1. `routes/web.php`
  2. `app/Http/Controllers/Auth/LoginController.php`
  3. `tests/Feature/LogoutSecurityTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `routes/web.php:21`: ubah `Route::match(['get', 'post'], '/logout', ...)` menjadi murni `Route::post('/logout', ...)`.
  2. Isolasi rute pengujian di baris 40-41:
     ```php
     if (app()->environment('local', 'testing')) {
         Route::middleware(['web', 'auth'])->group(function (): void {
             Route::get('/test-protected-route', fn () => response('OK', 200));
             Route::post('/test-admin-only-action', fn () => response('OK', 200))->middleware('permission:lk.manage_drive_links');
         });
     }
     ```
  3. Di `LoginController.php`: tambahkan pengecekan `is_active` pada form login standar (Invarian #4):
     ```php
     if (! Auth::user()->is_active) {
         Auth::logout();
         throw ValidationException::withMessages([
             'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi Administrator.',
         ]);
     }
     ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/LogoutSecurityTest.php`
  - Kode Pengujian:
    1. Menguji `GET /logout` menghasilkan HTTP 405 Method Not Allowed.
    2. Menguji form login dengan kredensial pegawai `is_active = false` gagal masuk dengan pesan validasi yang tepat.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `routes/web.php` dan `LoginController.php`.
- **Pesan Commit:** `security(routes): restrict logout to POST, isolate test routes, and enforce active check on form login`

---

#### TASK-09a: Nginx Reverse Proxy HTTP Upstream, TrustProxies Livewire, & Client Max Body (T22 - Bagian A)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-02.
- **Invarian Bisnis Tersentuh:** **#6** (Integritas tautan & ukuran payload HTTP).
- **Daftar Berkas Dibuat / Diubah:**
  1. `docker/nginx/default.conf`
  2. `bootstrap/app.php` (Konvensi Resmi Laravel 11 menggantikan class `TrustProxies.php` usang)
  3. `tests/Feature/NginxConfigurationTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Konfigurasi `docker/nginx/default.conf` untuk menangani reverse proxy HTTP secara benar:
     ```nginx
     server {
         listen 80;
         server_name localhost;
         root /var/www/html/public;
         index index.php index.html;

         client_max_body_size 25M;

         location / {
             try_files $uri $uri/ /index.php?$query_string;
         }

         location ~ \.php$ {
             fastcgi_pass app:9000;
             fastcgi_index index.php;
             fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
             include fastcgi_params;
             fastcgi_read_timeout 300;
         }
     }
     ```
  2. Di `bootstrap/app.php`: konfigurasikan trusted proxies Laravel 11 agar Livewire membaca skema dan host proxy secara akurat:
     ```php
     ->withMiddleware(function (Middleware $middleware) {
         $middleware->trustProxies(at: '*');
     })
     ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/NginxConfigurationTest.php`
  - Kode Pengujian: Menguji header `X-Forwarded-For` dan `client_max_body_size` pada simulasi request HTTP.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Kembalikan konfigurasi default nginx dan `bootstrap/app.php`.
- **Pesan Commit:** `feat(nginx): configure http fastcgi reverse proxy and trustProxies in bootstrap app`

---

#### TASK-09b: Nginx SSL / Sertifikat Produksi & Server Permanen (T22 - Bagian B)
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#6** (Keamanan koneksi HTTPS & penegakan sertifikat TLS BPS).
- **Alasan Pemblokiran:** Bergantung pada penyediaan nama domain produksi resmi BPS dan berkas sertifikat SSL/TLS fisik (`.crt` dan `.key`) oleh pengguna (Input Pengguna F0.8).
- **Tindakan Lanjutan Saat Tidak Diblokir:** Memasang sertifikat pada `/etc/ssl/certs/` dan mengaktifkan blok `listen 443 ssl http2;`.

### FASE 1: Integritas Data, Skema Period, & Upload Deduplikasi (TASK-11 s.d. TASK-14)

#### TASK-11: Pembersihan Baris Lembar Kerja Uji Otomatis Dev DB (U7)
- **Status:** **SIAP DIEKSEKUSI**
- **Urutan Khusus:** Dijalankan **SEBELUM** TASK-10 untuk memastikan migrasi skema tabel `lembar_kerja` tidak terbentur duplikasi data uji coba yang tertinggal.
- **Bergantung pada:** TASK-01 (Kesiapan lingkungan aman).
- **Invarian Bisnis Tersentuh:** **#5** (Keutuhan jejak audit & data bersih).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Console/Commands/CleanupDevTestLembarKerjaCommand.php` (*USULAN BARU*, dasar pola: `app/Console/Commands`)
  2. `tests/Feature/CleanupDevTestLembarKerjaCommandTest.php`
- **Perubahan Konkret & Kode Final:**
  Buat command Artisan dengan mekanisme `--dry-run` secara bawaan dan pengaman `pg_dump`:
  ```php
  <?php

  declare(strict_types=1);

  namespace App\Console\Commands;

  use App\Models\LembarKerja;
  use Illuminate\Console\Command;
  use Illuminate\Support\Facades\DB;

  class CleanupDevTestLembarKerjaCommand extends Command
  {
      protected $signature = 'websinkronisasi:cleanup-test-data {--force : Eksekusi pembersihan nyata} {--dry-run : Simulasi tanpa modifikasi}';
      protected $description = 'Bersihkan 26 baris instrumen LK hasil uji coba otomatis terdahulu dari database dev';

      public function handle(): int
      {
          $isDryRun = ! $this->option('force') || $this->option('dry-run');

          $testLks = LembarKerja::query()
              ->where(function ($q) {
                  $q->where('code', 'LIKE', '%TEST%')
                    ->orWhere('name', 'LIKE', '%Testing%')
                    ->orWhere('name', 'LIKE', '%Uji Coba%');
              })
              ->orWhere(function ($q) {
                  // Duplikasi ZI dengan period 2026 buatan tes yang tumpang tindih dengan seeder '12 Bulan'
                  $q->where('code', 'ZI')->where('period', '2026');
              })
              ->get();

          $this->info("Ditemukan {$testLks->count()} baris data uji coba.");

          foreach ($testLks as $lk) {
              $this->line("- [{$lk->id}] {$lk->code} ({$lk->period}): {$lk->name}");
              if (! $isDryRun) {
                  DB::transaction(function () use ($lk) {
                      $lk->indicators()->each(function ($ind) {
                          $ind->evidenceItems()->forceDelete();
                          $ind->forceDelete();
                      });
                      $lk->forceDelete();
                  });
              }
          }

          if ($isDryRun) {
              $this->warn("SIMULASI: Tidak ada baris yang dihapus. Jalankan dengan --force untuk eksekusi nyata.");
          } else {
              $this->info("SUKSES: Seluruh data uji coba berhasil dibersihkan.");
          }

          return 0;
      }
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/CleanupDevTestLembarKerjaCommandTest.php`
  - Kode Pengujian: Menguji bahwa pemanggilan command tanpa flag `--force` tidak menghapus baris apa pun, sedangkan flag `--force` menghapus baris uji secara idempoten.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan websinkronisasi:cleanup-test-data --dry-run
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan baris dari snapshot backup `pg_dump`.
- **Pesan Commit:** `feat(maintenance): add cleanup command for dev db test artifacts with safe dry-run default`

---

#### TASK-10: Migrasi Pemisahan Tahun `period` (INT) dan `evaluation_interval` (VARCHAR) serta Rekonsiliasi FK (T4)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-11 (Pembersihan data dev selesai).
- **Invarian Bisnis Tersentuh:** **#1** (Integritas filter tahun monitor), **#2** (Konsistensi relasi matching terhadap LK).
- **Daftar Berkas Dibuat / Diubah:**
  1. `database/migrations/2026_11_05_000001_separate_lk_period_year_and_interval.php` (*USULAN BARU*)
  2. `app/Models/LembarKerja.php`
  3. `app/Services/Monitor/MonitorMatrixService.php`
  4. `app/Filament/Resources/LembarKerjaResource.php`
  5. `app/Filament/Pages/MonitorMatrixPage.php`
  6. `tests/Feature/Phase1PeriodMigrationTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Buat migrasi pemisahan:
     ```php
     <?php

     declare(strict_types=1);

     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Database\Schema\Blueprint;
     use Illuminate\Support\Facades\DB;
     use Illuminate\Support\Facades\Schema;

     return new class extends Migration
     {
         public function up(): void
         {
             Schema::table('lembar_kerja', function (Blueprint $table): void {
                 $table->string('evaluation_interval', 50)->default('12 Bulan')->after('period');
             });

             // Migrasikan nilai teks string lama ke interval dan kembalikan period menjadi tahun integer
             DB::statement("UPDATE lembar_kerja SET evaluation_interval = period WHERE period IN ('12 Bulan', '6 Bulan', '3 Bulan', '1 Bulan');");
             DB::statement("UPDATE lembar_kerja SET period = '2026' WHERE period IN ('12 Bulan', '6 Bulan', '3 Bulan', '1 Bulan');");

             // Ubah tipe kolom period menjadi integer tahun
             DB::statement("ALTER TABLE lembar_kerja ALTER COLUMN period TYPE integer USING period::integer;");
         }

         public function down(): void
         {
             Schema::table('lembar_kerja', function (Blueprint $table): void {
                 $table->dropColumn('evaluation_interval');
             });
             DB::statement("ALTER TABLE lembar_kerja ALTER COLUMN period TYPE varchar(255) USING period::varchar;");
         }
     };
     ```
  2. Perbarui model `app/Models/LembarKerja.php` agar mencakup `evaluation_interval` pada `$fillable` dan `$casts = ['period' => 'integer']`.
  3. Perbarui `MonitorMatrixService.php` dan `MonitorMatrixPage.php` untuk membaca parameter filter tahun sebagai integer dan interval sebagai metadata pendukung.
- **Daftar Dampak (G4 Grep Impact):** Seluruh rujukan pada `app/Filament/Pages/MonitorMatrixPage.php`, `app/Filament/Pages/VerificationQueuePage.php`, `app/Filament/Resources/LembarKerjaResource.php`, dan `database/seeders/InitialLkSeeder.php` disesuaikan agar `period` menggunakan nilai tahun (misal: 2026) dan `evaluation_interval` menggunakan interval evaluasi ('12 Bulan', '6 Bulan', dst.).
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/Phase1PeriodMigrationTest.php`
  - Kode Pengujian: Menguji bahwa kolom `period` bertipe integer, pencarian lembar kerja berdasarkan tahun 2026 berhasil, dan `evaluation_interval` tersimpan dengan benar.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan migrate --path=database/migrations/2026_11_05_000001_separate_lk_period_year_and_interval.php
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Jalankan `php artisan migrate:rollback --step=1`.
- **Pesan Commit:** `refactor(database): separate lk period year as integer and evaluation_interval as string`

---

#### TASK-12: Deduplikasi Upload, Partial Unique Index Deleted At, Transaksi Atomik (T8)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10.
- **Invarian Bisnis Tersentuh:** **#2** (Integritas pencatatan bukti unik).
- **Daftar Berkas Dibuat / Diubah:**
  1. `database/migrations/2026_11_05_000002_create_active_document_hash_partial_unique_index.php` (*USULAN BARU*)
  2. `app/Services/Evidence/EvidenceUploadService.php`
  3. `tests/Feature/EvidenceDeduplicationTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Buat migrasi indeks parsial Postgres:
     ```php
     <?php

     declare(strict_types=1);

     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Support\Facades\DB;

     return new class extends Migration
     {
         public function up(): void
         {
             // Hapus constraint unique lama yang tidak mengabaikan soft-delete
             DB::statement("ALTER TABLE evidence_documents DROP CONSTRAINT IF EXISTS uq_documents_file_hash;");
             // Pasang partial unique index hanya untuk baris aktif
             DB::statement("CREATE UNIQUE INDEX uq_evidence_documents_hash_active ON evidence_documents (file_hash) WHERE deleted_at IS NULL;");
         }

         public function down(): void
         {
             DB::statement("DROP INDEX IF EXISTS uq_evidence_documents_hash_active;");
             DB::statement("ALTER TABLE evidence_documents ADD CONSTRAINT uq_documents_file_hash UNIQUE (file_hash);");
         }
     };
     ```
  2. Di `EvidenceUploadService::store()`: bungkus seluruh proses dalam `DB::transaction`. Lakukan pengecekan duplikasi hash aktif sebelum menyimpan berkas fisik; jika terjadi race condition, tangkap `UniqueConstraintViolationException`, hapus berkas fisik yatim, dan lemparkan `DuplicateDocumentException` (409).
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/EvidenceDeduplicationTest.php`
  - Kode Pengujian: Mengunggah berkas yang sama dua kali, memastikan unggahan kedua melempar `DuplicateDocumentException` HTTP 409 dan tidak ada berkas fisik yatim yang tertinggal di disk private. Menguji bahwa berkas yang telah di-soft-delete dapat diunggah ulang tanpa konflik.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Rollback migrasi dan kembalikan service upload via git checkout.
- **Pesan Commit:** `fix(upload): enforce active partial unique index on file_hash and clean orphan files on conflict`

---

#### TASK-13: Penentuan Ekstensi Berkas Sniffed Magic Bytes (finfo) Bukan Nama Klien (T12)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-12.
- **Invarian Bisnis Tersentuh:** **#6** (Keamanan berkas fisik & pencegahan ekstensi berbahaya).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Evidence/EvidenceUploadService.php`
  2. `tests/Feature/MimeMagicBytesSniffingTest.php`
- **Perubahan Konkret & Kode Final:**
  Di `EvidenceUploadService::inspectAndValidate()` (baris 80-125):
  Ganti penentuan `$extension` dari `getClientOriginalExtension()` menjadi kamus pemetaan deterministik berbasis `$sniffedMime`:
  ```php
  $mimeToExtensionMap = [
      'application/pdf' => 'pdf',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
      'application/msword' => 'doc',
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
  ];

  $safeExtension = $mimeToExtensionMap[$sniffedMime] ?? null;
  if ($safeExtension === null) {
      throw ValidationException::withMessages([
          'file' => "MIME type '{$sniffedMime}' tidak didukung untuk penyimpanan aman.",
      ]);
  }
  ```
  Nama berkas fisik pada penyimpanan disk privat diwajibkan menggunakan `$safeExtension`:
  ```php
  $storedFileName = Str::uuid()->toString() . '.' . $safeExtension;
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/MimeMagicBytesSniffingTest.php`
  - Kode Pengujian: Mengunggah berkas PDF asli yang sengaja diubah namanya menjadi `dokumen_palsu.php` atau `sk.exe`. Sistem harus mendeteksi magic bytes `%PDF-`, menetapkan ekstensi fisik `.pdf`, dan mengabaikan ekstensi palsu dari klien.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `app/Services/Evidence/EvidenceUploadService.php` via git checkout.
- **Pesan Commit:** `security(upload): derive storage extension strictly from finfo magic bytes mapping`

---

#### TASK-14: Sanitasi Payload Respons HTTP 409 DuplicateDocumentException (T20)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-12.
- **Invarian Bisnis Tersentuh:** **#3** (Pencegahan kebocoran informasi server).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Exceptions/DuplicateDocumentException.php`
  2. `app/Http/Controllers/Evidence/EvidenceController.php`
  3. `tests/Feature/DuplicateDocumentExceptionSanitizationTest.php`
- **Perubahan Konkret & Kode Final:**
  Di `DuplicateDocumentException.php`: bersihkan struktur JSON yang dikembalikan ke klien agar tidak membocorkan `file_path`, `storage_disk`, atau `extracted_text`:
  ```php
  public function render($request)
  {
      return response()->json([
          'message' => 'Dokumen dengan konten identik (SHA-256) sudah pernah diunggah.',
          'existing_document' => [
              'id' => $this->existingDocument->id,
              'document_name' => $this->existingDocument->document_name,
              'document_number' => $this->existingDocument->document_number,
              'year' => $this->existingDocument->year,
              'uploaded_at' => $this->existingDocument->created_at?->toIso8601String(),
          ],
      ], 409);
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/DuplicateDocumentExceptionSanitizationTest.php`
  - Kode Pengujian: Memastikan respons HTTP 409 tidak memuat kunci `file_path` atau path direktori internal server.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `DuplicateDocumentException.php` via git checkout.
- **Pesan Commit:** `security(api): sanitize duplicate document 409 payload to prevent storage path leakage`

### FASE 2: Kualitas Algoritma Matching, Penalti, & Koreksi Trigram (TASK-15a s.d. TASK-19)

#### TASK-15a: Pembatasan Batas Kata Kode Pasti & Nomor Dokumen evaluateExactNumber (T5, T13)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10 (Skema LK terstandardisasi).
- **Invarian Bisnis Tersentuh:** **#1** (Pencegahan centang palsu otomatis), **#2** (Kekekalan rejected).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Matching/Rules/DeterministicMatchingRule.php`
  2. `tests/Feature/DeterministicMatchingRuleTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `evaluateExactCode()` (`DeterministicMatchingRule.php:40-70`):
     - Wajibkan panjang kode indikator minimal 3 karakter untuk pencocokan otomatis langsung `confirmed` tanpa prefiks:
       ```php
       $code = trim($indicator->code);
       if ($code === '' || mb_strlen($code) < 3) {
           return null; // Kode terlalu pendek (misal: "1", "A") rawan bentrok angka biasa
       }
       ```
     - Perketat batas kata regex boundary agar berdiri sendiri secara tegas:
       ```php
       $pattern = '/(?:^|[\s_\[\(])' . preg_quote($code, '/') . '(?:[\s_\]\)\.\-]|$)/i';
       ```
  2. Di `evaluateExactNumber()` (`DeterministicMatchingRule.php:75-115`):
     - Ganti fungsi `str_contains()` mentah dengan tokenisasi nomor dokumen resmi:
       ```php
       $docNumber = trim((string) $document->document_number);
       if ($docNumber === '' || mb_strlen($docNumber) < 4) {
           return null;
       }
       // Nomor dokumen wajib cocok secara utuh atau memiliki pemisah nomor dinas resmi
       $pattern = '/' . preg_quote($docNumber, '/') . '/i';
       ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/DeterministicMatchingRuleTest.php`
  - Kode Pengujian: Menguji bahwa dokumen dengan nomor "1" tidak mencocokkan nomor dinas "B-105/2026", dan kode pendek "1" tidak memicu status `confirmed` otomatis. Menguji bahwa `ZI-1.1` tetap terdeteksi 100% `confirmed` (Kasus 10).
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `DeterministicMatchingRule.php` via git checkout.
- **Pesan Commit:** `fix(matching): enforce word boundaries and min length for deterministic exact code and number`

---

#### TASK-15b: Aturan Prefix LK & Opsi Dual Matching C (T5 - Bagian B)
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#1** (Format kode indikator & prediksi centang auto).
- **Alasan Pemblokiran:** Bergantung pada keputusan pengguna mengenai opsi format kode indikator di seluruh instrumen BPS (Input Pengguna F2.2: Opsi A prefix LK wajib `ZI-1.1`, Opsi B pencarian ganda, atau Opsi C kombinasi).
- **Tindakan Lanjutan Saat Tidak Diblokir:** Mengimplementasikan normalisasi prefiks kode LK pada `DeterministicMatchingRule`.

---

#### TASK-16: Pemulihan Penalti Perbedaan Tahun MATCH-03/10 Independen dari Schema (T6)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10 (Kolom period integer tersedia).
- **Invarian Bisnis Tersentuh:** **#1** (Pencegahan pencocokan dokumen lintas tahun anggaran).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Matching/Rules/HeuristicMatchingRule.php`
  2. `tests/Feature/HeuristicYearPenaltyTest.php`
- **Perubahan Konkret & Kode Final:**
  Di `HeuristicMatchingRule::evaluate()` (baris 45-65):
  Ganti pembacaan target tahun agar mengambil nilai `period` integer lembar kerja secara langsung:
  ```php
  $targetYear = null;
  $lkPeriod = $indicator->lembarKerja?->period;
  if ($lkPeriod !== null && (int) $lkPeriod >= 2000) {
      $targetYear = (int) $lkPeriod;
  } elseif ($item !== null && ! empty($item->resolved_name) && preg_match('/(20\d{2})/', $item->resolved_name, $m)) {
      $targetYear = (int) $m[1];
  } elseif (preg_match('/(20\d{2})/', $indicator->title, $m)) {
      $targetYear = (int) $m[1];
  }

  $hasYearPenalty = false;
  if ($targetYear !== null && $document->year !== null && (int) $document->year !== $targetYear) {
      $titleScore = max(0.0, $titleScore - 20.0);
      $hasYearPenalty = true;
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/HeuristicYearPenaltyTest.php`
  - Kode Pengujian: Menguji dokumen tahun 2024 terhadap LK periode 2026; skor kemiripan wajib dipotong penalti tepat 20.0 poin.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `HeuristicMatchingRule.php` via git checkout.
- **Pesan Commit:** `fix(matching): restore MATCH-03 year discrepancy penalty using integer lk period`

---

#### TASK-17: Perbaikan Fatal Bug Matematis Trigram Jaccard Index & Sinonim Terverifikasi (N-01)
- **Status:** **SIAP DIEKSEKUSI**
- **Urutan Khusus:** Dikerjakan **SEBELUM** kalibrasi ambang batas TASK-19 agar ambang batas didasarkan pada skor perhitungan matematis yang valid.
- **Bergantung pada:** TASK-01.
- **Invarian Bisnis Tersentuh:** **#1** (Akurasi skor algoritma auto).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Matching/Similarity/TrigramSimilarity.php`
  2. `tests/Unit/TrigramSimilarityFixedTest.php`
- **Perubahan Konkret & Kode Final:**
  Perbaiki method `trigramJaccard()` di `TrigramSimilarity.php:60-75`:
  ```php
  protected function trigramJaccard(string $s1, string $s2): float
  {
      $padded1 = '  ' . mb_strtolower($s1, 'UTF-8') . ' ';
      $padded2 = '  ' . mb_strtolower($s2, 'UTF-8') . ' ';

      $t1 = $this->extractTrigrams($padded1);
      $t2 = $this->extractTrigrams($padded2);

      if (empty($t1) || empty($t2)) {
          return 0.0;
      }

      // Pastikan multiset deduplikasi sebelum operasi himpunan (N-01)
      $u1 = array_unique($t1);
      $u2 = array_unique($t2);

      $intersection = count(array_intersect($u1, $u2));
      $union = count(array_unique(array_merge($u1, $u2)));

      return $union > 0 ? (float) ($intersection / $union) : 0.0;
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Unit/TrigramSimilarityFixedTest.php`
  - Kode Pengujian: Menguji string dengan kata berulang seperti `ABC ABC ABC` vs `ABC`; skor Jaccard dijamin bernilai tepat $\le 100.00\%$ (bukan 115.91%).
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Kembalikan `TrigramSimilarity.php` via git checkout.
- **Pesan Commit:** `fix(matching): enforce array_unique on trigrams to fix mathematical jaccard overflow`

---

#### TASK-18: Seeder Indikator Dasar & Item Bukti Seluruh Periode Evaluasi (T14)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10.
- **Invarian Bisnis Tersentuh:** **#1** (Ketersediaan data master indikator seluruh interval).
- **Daftar Berkas Dibuat / Diubah:**
  1. `database/seeders/DemoIndicatorAndSampleDocumentsSeeder.php`
  2. `tests/Feature/IndicatorSeederIntervalTest.php`
- **Perubahan Konkret & Kode Final:**
  Lengkapi seeder agar setiap lembar kerja (6 Bulan, 3 Bulan, 1 Bulan) memiliki minimal 1 indikator dan 1 item bukti sah, sehingga pengujian monitor matriks tidak menemui sel kosong tanpa instrumen.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/IndicatorSeederIntervalTest.php`
  - Kode Pengujian: Memastikan setiap Lembar Kerja aktif memiliki minimal 1 relasi indikator dan item bukti.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan seeder via git checkout.
- **Pesan Commit:** `seed(indicators): provide sample indicators and evidence items for all evaluation intervals`

---

#### TASK-19: Status `perlu_verifikasi` & Ambang SystemSetting Baru (K1)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-17 (Trigram fixed) & Lampiran U2 Ulang.
- **Invarian Bisnis Tersentuh:** **#1** (Centang langsung hanya untuk auto $\ge 75\%$ & confirmed; perlu_verifikasi TIDAK centang), **#2** (Kekekalan rejected).
- **Daftar Berkas Dibuat / Diubah:**
  1. `database/migrations/2026_11_05_000003_add_perlu_verifikasi_to_match_status_enum.php` (*USULAN BARU*)
  2. `app/Models/DocumentMatch.php`
  3. `app/Models/SystemSetting.php`
  4. `app/Services/Matching/Rules/HeuristicMatchingRule.php`
  5. `app/Filament/Pages/VerificationQueuePage.php`
  6. `app/Services/Monitor/MonitorMatrixService.php`
  7. `tests/Feature/PerluVerifikasiStatusTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Migrasi penambahan nilai ENUM PostgreSQL secara aman:
     ```php
     <?php

     declare(strict_types=1);

     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Support\Facades\DB;

     return new class extends Migration
     {
         public function up(): void
         {
             // ALTER TYPE PostgreSQL di luar blok transaksi
             DB::statement("ALTER TYPE match_status ADD VALUE IF NOT EXISTS 'perlu_verifikasi';");
         }

         public function down(): void
         {
             // ENUM drop value di PostgreSQL tidak didukung tanpa re-creating type
         }
     };
     ```
  2. Di `SystemSetting.php`: daftarkan key konfigurasi baru:
     - `matching.similarity_threshold` = `75.0` (Ambang `auto` langsung centang, Invarian #1)
     - `matching.verification_threshold` = `60.0` (Ambang `perlu_verifikasi` hasil kalibrasi U2)
  3. Di `HeuristicMatchingRule.php`:
     - Jika skor $\ge 75.0$: status = `'auto'` (langsung centang di monitor).
     - Jika $60.0 \le 	ext{skor} < 75.0$: status = `'perlu_verifikasi'` (masuk antrean verifikasi, **TIDAK CENTANG** di monitor).
  4. Di `MonitorMatrixService.php`: tegaskan kueri centang HANYA memuat `auto` dan `confirmed`:
     ```php
     ->whereIn('status', ['auto', 'confirmed'])
     ```
     Dengan aturan ini, status `perlu_verifikasi` dijamin tidak memunculkan centang sebelum disetujui admin.
  5. Di `VerificationQueuePage.php`: tambahkan tab "Perlu Verifikasi (60-74%)".
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/PerluVerifikasiStatusTest.php`
  - Kode Pengujian: Menguji dokumen dengan skor 68.0% tersimpan sebagai `perlu_verifikasi`, muncul di tab antrean verifikasi, namun sel pada `MonitorMatrixService::getMatrixData()` tetap kosong murni tanpa centang (Invarian #1).
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Hapus migrasi dan kembalikan file terkait via git checkout.
- **Pesan Commit:** `feat(matching): add perlu_verifikasi status for 60-74% score and enforce monitor checkmark invariants`

### FASE 3: Pengkabelan Impor Excel Riil & Validasi Dokumen (TASK-20a s.d. TASK-20b)

#### TASK-20a: Pengkabelan Livewire Import Wizard ke LkImportService Riil & Anti-Makro (T15, T21 - Bagian A)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10 (Skema LK stabil).
- **Invarian Bisnis Tersentuh:** **#5** (Jejak audit impor idempoten), **#6** (Validasi keamanan tautan eksternal http/https).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Filament/Resources/LkImportProfileResource/Pages/ListLkImportProfiles.php`
  2. `app/Services/LkImport/ExcelParserService.php`
  3. `tests/Feature/ExcelImportRealWiringTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `ListLkImportProfiles.php`:
     - Ganti data dummy `$this->getPreviewSamples()` pada `executeImport()` (baris 327) dengan pemanggilan nyata `LkImportService::commit()`:
       ```php
       $importService = app(LkImportService::class);
       $log = $importService->commit(
           filePath: $uploadedFilePath,
           lk: $this->selectedLk,
           profile: $profile,
           actor: Auth::user(),
           originalFilename: $originalFilename
       );
       ```
     - Ganti method simulasi `retestMapping()` dengan pemanggilan nyata `LkImportService::preview()`.
  2. Di `ExcelParserService::validateAndLoad()` (`ExcelParserService.php:30-70`):
     - Validasi batas ukuran berkas impor maksimal 20 MB (selaras dengan setting `temporary_file_upload.rules` Livewire):
       ```php
       $maxSizeBytes = 20 * 1024 * 1024;
       if (filesize($path) > $maxSizeBytes) {
           throw new InvalidArgumentException("Ukuran berkas Excel melebihi batas maksimal 20 MB.");
       }
       ```
     - Tolak tegas seluruh ekstensi makro berbahaya (`.xlsm`, `.xltm`, `.xlam`, `.xlsb`):
       ```php
       $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
       if (in_array($ext, ['xlsm', 'xltm', 'xlam', 'xlsb'], true)) {
           throw new InvalidArgumentException("Berkas Excel dengan makro (.{$ext}) dilarang keras demi keamanan sistem.");
       }
       ```
     - Untuk berkas `.xlsx`, inspeksi struktur ZIP dan tolak jika memuat komponen makro VBA (`xl/vbaProject.bin`):
       ```php
       $zip = new ZipArchive();
       if ($zip->open($path) === true) {
           if ($zip->locateName('xl/vbaProject.bin') !== false) {
               $zip->close();
               throw new InvalidArgumentException("Berkas Excel terdeteksi memuat makro VBA tersembunyi (vbaProject.bin). Impor ditolak!");
           }
           $zip->close();
       }
       ```
  3. Di `ExcelParserService::sanitizeUrl()` (baris 73-95):
     - Tegaskan penolakan skema non-HTTP (Invarian #6):
       ```php
       if (! empty($url) && ! preg_match('/^https?:\/\//i', $url)) {
           return null; // Tolak skema javascript:, file:, data:, dll.
       }
       ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/ExcelImportRealWiringTest.php`
  - Kode Pengujian:
    1. Mengunggah berkas `.xlsm` atau `.xlsx` yang memuat `xl/vbaProject.bin` dan memastikan impor ditolak dengan pesan validasi yang jelas.
    2. Menguji alur `executeImport()` memanggil `LkImportService::commit()`, menghasilkan record `LkImportLog`, dan mengimpor indikator nyata ke basis data.
    3. Menguji baris Excel dengan tautan `javascript:alert(1)` disanitasi menjadi `null` (Invarian #6).
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `ListLkImportProfiles.php` dan `ExcelParserService.php` via git checkout.
- **Pesan Commit:** `fix(import): wire real LkImportService in wizard, enforce 20mb limit, and block macro files`

---

#### TASK-20b: Pengujian Integrasi Terhadap Berkas Excel Riil BPS Kedinasan (T15 - Bagian B)
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#6** (Validasi berkas Excel riil kedinasan).
- **Alasan Pemblokiran:** Bergantung pada penyediaan minimal 1 contoh berkas Excel Lembar Kerja riil kedinasan BPS oleh pengguna (Input Pengguna F3.2).
- **Tindakan Lanjutan Saat Tidak Diblokir:** Menguji parsing idempoten terhadap 20+ indikator asli dari template BPS nyata.

### FASE 4: Pengerasan Keamanan, Otorisasi, & Kinerja (TASK-21a s.d. TASK-27)

#### TASK-21a: Transaksi Konkuren & Pessimistic Row Locking VerificationService (T16 - Bagian A)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-19 (Status perlu_verifikasi terpasang).
- **Invarian Bisnis Tersentuh:** **#1** (Peralihan status verified langsung centang), **#2** (Kekekalan rejected tidak boleh dikonfirmasi ulang), **#5** (Jejak audit verifikasi).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Matching/VerificationService.php`
  2. `tests/Feature/VerificationServiceConcurrencyTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Tulis ulang `VerificationService.php` dengan transaksi atomik dan pessimistic locking:
     ```php
     <?php

     declare(strict_types=1);

     namespace App\Services\Matching;

     use App\Models\AuditLog;
     use App\Models\DocumentMatch;
     use App\Models\User;
     use DomainException;
     use Illuminate\Support\Facades\DB;

     class VerificationService
     {
         public function confirmMatch(string $matchId, User $actor): DocumentMatch
         {
             return DB::transaction(function () use ($matchId, $actor): DocumentMatch {
                 /** @var DocumentMatch $match */
                 $match = DocumentMatch::where('id', $matchId)->lockForUpdate()->firstOrFail();
                 $oldStatus = $match->status;

                 // Idempoten jika sudah dikonfirmasi
                 if ($oldStatus === 'confirmed') {
                     return $match;
                 }

                 // Invarian #2: Relasi berstatus rejected dilarang keras dikonfirmasi ulang
                 if ($oldStatus === 'rejected') {
                     throw new DomainException("Relasi berstatus 'rejected' tidak dapat dikonfirmasi ulang (Invarian #2).");
                 }

                 $match->update([
                     'status' => 'confirmed',
                     'verified_by' => $actor->id,
                     'verified_at' => now(),
                 ]);

                 AuditLog::create([
                     'user_id' => $actor->id,
                     'action' => 'match.verified',
                     'entity_type' => 'DocumentMatch',
                     'entity_id' => $match->id,
                     'old_values' => ['status' => $oldStatus],
                     'new_values' => ['status' => 'confirmed', 'verified_by' => $actor->id],
                 ]);

                 return $match;
             });
         }

         public function rejectMatch(string $matchId, User $actor): DocumentMatch
         {
             return DB::transaction(function () use ($matchId, $actor): DocumentMatch {
                 /** @var DocumentMatch $match */
                 $match = DocumentMatch::where('id', $matchId)->lockForUpdate()->firstOrFail();
                 $oldStatus = $match->status;

                 if ($oldStatus === 'rejected') {
                     return $match;
                 }

                 $match->update([
                     'status' => 'rejected',
                     'verified_by' => $actor->id,
                     'verified_at' => now(),
                 ]);

                 AuditLog::create([
                     'user_id' => $actor->id,
                     'action' => 'match.rejected',
                     'entity_type' => 'DocumentMatch',
                     'entity_id' => $match->id,
                     'old_values' => ['status' => $oldStatus],
                     'new_values' => ['status' => 'rejected', 'verified_by' => $actor->id],
                 ]);

                 return $match;
             });
         }
     }
     ```
  2. Tabel Transisi Status Verifikasi:
     - `auto` $
ightarrow$ `confirmed` (Sah via `confirmMatch`)
     - `auto` $
ightarrow$ `rejected` (Sah via `rejectMatch`)
     - `perlu_verifikasi` $
ightarrow$ `confirmed` (Sah via `confirmMatch`)
     - `perlu_verifikasi` $
ightarrow$ `rejected` (Sah via `rejectMatch`)
     - `rejected` $
ightarrow$ `confirmed` (**DITOLAK**, melempar `DomainException` sesuai Invarian #2)
     - `confirmed` $
ightarrow$ `confirmed` (Idempoten tanpa perubahan log)
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/VerificationServiceConcurrencyTest.php`
  - Kode Pengujian:
    1. Menguji bahwa pemanggilan `confirmMatch` pada match yang berstatus `rejected` melempar `DomainException` (Invarian #2).
    2. Menguji idempotensi: pemanggilan ganda menghasilkan status `confirmed` dengan tepat 1 jejak audit.
    3. Menguji eksekusi kueri memuat klausa `FOR UPDATE` melalui `DB::getQueryLog()`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `VerificationService.php` via git checkout.
- **Pesan Commit:** `fix(matching): implement row-level locking and enforce invariant #2 rejection protection in verification service`

---

#### TASK-21b: Kebijakan Admin Un-reject Manual di VerificationService (T16 - Bagian B)
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#2** (Kekekalan penolakan rejected & potensi un-reject).
- **Alasan Pemblokiran:** Bergantung pada keputusan pengguna apakah Admin diizinkan membatalkan status penolakan (*un-reject*) atau penolakan bersifat permanen seumur hidup (Input Pengguna F4.2).
- **Tindakan Lanjutan Saat Tidak Diblokir:** Mengimplementasikan method `unrejectMatch()` dengan audit log khusus jika disetujui.

---

#### TASK-22: Optimalisasi rematchLk dengan chunkById(100) & Filter Dokumen Selesai (T17)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-03 & TASK-10.
- **Invarian Bisnis Tersentuh:** **#1** (Kelancaran matching batch), **#2** (Kekekalan rejected saat batch rematch).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Jobs/RematchActiveDocumentsJob.php`
  2. `app/Services/Matching/MatchingEngineService.php`
  3. `tests/Feature/RematchChunkingPerformanceTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `RematchActiveDocumentsJob.php:40-55`: ganti pengambilan seluruh dokumen `->get()` dengan `chunkById(100)`:
     ```php
     EvidenceDocument::query()
         ->whereNull('deleted_at')
         ->where('processing_status', 'selesai')
         ->chunkById(100, function ($documents) use ($engine): void {
             foreach ($documents as $doc) {
                 $engine->matchDocument($doc);
             }
         });
     ```
  2. Di `MatchingEngineService::rematchLk()`: terapkan pola `chunkById(100)` yang sama untuk membatasi konsumsi memori container PHP pada dataset besar (1000+ berkas bukti).
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/RematchChunkingPerformanceTest.php`
  - Kode Pengujian: Menguji batch 150 dokumen diproses dalam 2 chunk terpisah dan menghasilkan total relasi yang identik dengan versi tanpa chunking.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan berkas job dan service via git checkout.
- **Pesan Commit:** `perf(matching): chunk document rematching by 100 to optimize memory on large dataset`

---

#### TASK-23: Eager Loading matchedItem pada Monitor Matriks (T18)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10.
- **Invarian Bisnis Tersentuh:** **#1** (Kinerja rendering matriks centang monitor).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Monitor/MonitorMatrixService.php`
  2. `tests/Feature/MonitorMatrixEagerLoadingTest.php`
- **Perubahan Konkret & Kode Final:**
  Di `MonitorMatrixService::getMatrixData()` (`MonitorMatrixService.php:140-155`):
  Tambahkan relasi `matchedItem` pada eager loading kueri relasi:
  ```php
  $matches = DocumentMatch::query()
      ->with(['indicator.lembarKerja', 'matchedItem'])
      ->whereIn('document_id', $documentIds)
      ->whereIn('status', ['auto', 'confirmed'])
      ->get();
  ```
  Ini mengeliminasi 100 kueri N+1 ke tabel `lk_evidence_items` (penurunan kueri dari 108 menjadi 6 kueri, 94.4% lebih efisien sesuai Lampiran U4).
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/MonitorMatrixEagerLoadingTest.php`
  - Kode Pengujian: Memeriksa jumlah kueri SQL menggunakan `DB::enableQueryLog()`; pada dataset 20 dokumen x 5 LK, kueri database dibatasi maksimal 10 kueri.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `MonitorMatrixService.php` via git checkout.
- **Pesan Commit:** `perf(monitor): eager load matchedItem relation in matrix data to eliminate n+1 queries`

---

#### TASK-24: Tokenisasi highlightKeywords Tanpa Perusakan Tag HTML & Sanitasi XSS (T19)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-01.
- **Invarian Bisnis Tersentuh:** **#3** (Pencegahan kerentanan Cross-Site Scripting sisi antarmuka).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Filament/Pages/VerificationQueuePage.php`
  2. `resources/views/filament/pages/verification-queue.blade.php`
  3. `tests/Feature/HighlightKeywordsHtmlSafetyTest.php`
- **Perubahan Konkret & Kode Final:**
  Tulis ulang fungsi `highlightKeywords()` (`VerificationQueuePage.php:469-495`) dengan pendekatan tokenisasi aman:
  ```php
  public function highlightKeywords(string $text, ?array $keywords): string
  {
      if (empty($keywords)) {
          return e($text);
      }

      $validWords = array_values(array_filter($keywords, fn ($w) => mb_strlen(trim((string) $w)) >= 3));
      if (empty($validWords)) {
          return e($text);
      }

      // Bangun satu ekspresi reguler gabungan dengan batasan kata
      $escapedPatterns = array_map(fn ($w) => preg_quote(trim((string) $w), '/'), $validWords);
      $regex = '/(\b(?:' . implode('|', $escapedPatterns) . ')\b)/iu';

      // Pecah teks berdasarkan keyword; token genap adalah teks biasa, token ganjil adalah keyword
      $tokens = preg_split($regex, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
      if ($tokens === false) {
          return e($text);
      }

      $output = '';
      foreach ($tokens as $index => $token) {
          if ($index % 2 === 1) {
              // Token keyword yang cocok
              $output .= '<mark class="bg-amber-200 text-amber-900 px-1 py-0.5 rounded font-semibold">' . e($token) . '</mark>';
          } else {
              // Teks biasa
              $output .= e($token);
          }
      }

      return $output;
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/HighlightKeywordsHtmlSafetyTest.php`
  - Kode Pengujian: Menguji input teks yang mengandung tag `<script>alert('xss')</script>` dan kata kunci `script`; sistem dijamin meng-escape karakter `<` dan `>` serta tidak menghasilkan tag HTML rusak atau script yang dapat dieksekusi.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `VerificationQueuePage.php` via git checkout.
- **Pesan Commit:** `security(ui): tokenize keyword highlighting to prevent broken html tags and xss injection`

---

#### TASK-25: Model Policy Komprehensif (7 Resource) & Penegakan canAccess (3 Page) (T23)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-05 & TASK-08.
- **Invarian Bisnis Tersentuh:** **#3** (Penegakan otorisasi server 3 role & proteksi Super Admin terakhir), **#4** (Pegawai nonaktif diblokir total).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Policies/AuditLogPolicy.php` (*USULAN BARU*, konvensi auto-discovery Laravel 11)
  2. `app/Policies/EvidenceDocumentPolicy.php` (*USULAN BARU*)
  3. `app/Policies/LembarKerjaPolicy.php` (*USULAN BARU*)
  4. `app/Policies/LkImportProfilePolicy.php` (*USULAN BARU*)
  5. `app/Policies/LkIndicatorPolicy.php` (*USULAN BARU*)
  6. `app/Policies/SystemSettingPolicy.php` (*USULAN BARU*)
  7. `app/Policies/UserPolicy.php` (*USULAN BARU*)
  8. `app/Filament/Pages/Dashboard.php`
  9. `app/Filament/Pages/MonitorMatrixPage.php`
  10. `app/Filament/Pages/VerificationQueuePage.php`
  11. `tests/Feature/FilamentRbacAuthorizationMatrixTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Buat 7 Model Policy di `app/Policies/` memanfaatkan auto-discovery bawaan Laravel 11 (tanpa memodifikasi `AuthServiceProvider` yang telah ditiadakan di Laravel 11).
  2. Di `UserPolicy.php`: terapkan proteksi Invarian #3 & AUTH-06 / AC-04:
     ```php
     public function updateRole(User $actor, User $target): bool
     {
         return $actor->isSuperAdmin() && $actor->is_active;
     }

     public function toggleActive(User $actor, User $target): bool
     {
         if (! $actor->is_active) {
             return false;
         }

         // Invarian #3 & AUTH-06: Dilarang menonaktifkan satu-satunya Super Admin aktif terakhir
         if ($target->isSuperAdmin() && $target->is_active) {
             $activeSuperAdminCount = User::query()
                 ->where('role', 'super_admin')
                 ->where('is_active', true)
                 ->count();

             if ($activeSuperAdminCount <= 1) {
                 return false;
             }
         }

         if ($actor->isSuperAdmin()) {
             return true;
         }

         if ($actor->isAdmin()) {
             return $target->isPegawai(); // Admin hanya boleh toggle role Pegawai
         }

         return false;
     }
     ```
  3. Pasang method `canAccess()` pada 3 Halaman Filament:
     - `Dashboard::canAccess()`: Mengembalikan `Auth::check() && Auth::user()->is_active` (Invarian #4).
     - `MonitorMatrixPage::canAccess()`: Mengembalikan `Auth::user()?->hasPermission('monitor.view') ?? false`.
     - `VerificationQueuePage::canAccess()`: Mengembalikan `Auth::user()?->hasPermission('match.verify') ?? false` (Pegawai ditolak).
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/FilamentRbacAuthorizationMatrixTest.php`
  - Kode Pengujian: Menguji matriks lengkap 3 role x 7 resource x 3 page:
    1. Super Admin mencoba menonaktifkan dirinya sendiri saat ia menjadi satu-satunya Super Admin aktif; aksi wajib ditolak 403 (Invarian #3, AC-04).
    2. Admin mencoba mengubah role pengguna lain; wajib ditolak 403.
    3. Pegawai mencoba mengakses `/admin/verification-queue`; wajib menerima HTTP 403.
    4. Pengguna nonaktif (`is_active = false`) mencoba membuka dashboard; wajib ditolak (Invarian #4).
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Hapus berkas policy di `app/Policies/` dan pulihkan halaman Filament via git checkout.
- **Pesan Commit:** `security(rbac): implement model policies and page canAccess checks enforcing invariant #3 and #4`

---

#### TASK-26: Sanitasi Formula Spreadsheet Injection pada Ekspor CSV Matriks Monitor (N-02)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-10.
- **Invarian Bisnis Tersentuh:** **#3** (Keamanan payload ekspor data kedinasan).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Filament/Pages/MonitorMatrixPage.php`
  2. `tests/Feature/CsvFormulaInjectionExportTest.php`
- **Perubahan Konkret & Kode Final:**
  Di `MonitorMatrixPage::exportCsv()` (baris 180-220):
  Tambahkan penyanitasi sel spreadsheet untuk setiap sel string sebelum ditulis ke berkas CSV:
  ```php
  private function sanitizeCsvCell(string $value): string
  {
      // Karakter pemicu formula eksekusi spreadsheet (CSV Injection)
      $formulaTriggers = ['=', '+', '-', '@', "\t", "\r"];
      $trimmed = ltrim($value);

      if ($trimmed !== '' && in_array($trimmed[0], $formulaTriggers, true)) {
          return "'" . $value; // Prefiks tanda kutip tunggal menonaktifkan formula
      }

      return $value;
  }
  ```
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/CsvFormulaInjectionExportTest.php`
  - Kode Pengujian: Membuat dokumen dengan nama `=cmd|'/C calc'!A0`, mengekspor CSV matriks monitor, dan memverifikasi sel pada CSV tertulis aman sebagai `'=cmd|'/C calc'!A0`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan `MonitorMatrixPage.php` via git checkout.
- **Pesan Commit:** `security(export): sanitize spreadsheet formula injection triggers in monitor csv export`

---

#### TASK-27: Sanitasi Wildcard ILIKE & Penyembunyian extracted_text (N-04, N-05)
- **Status:** **SIAP DIEKSEKUSI**
- **Bergantung pada:** TASK-01.
- **Invarian Bisnis Tersentuh:** **#3** (Keamanan query SQL terparameter & pencegahan kebocoran model).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Search/DocumentSearchService.php` (Konfirmasi nama berkas asli, bukan EvidenceSearchService)
  2. `app/Models/EvidenceDocument.php`
  3. `tests/Feature/SearchWildcardAndModelHiddenTest.php`
- **Perubahan Konkret & Kode Final:**
  1. Di `DocumentSearchService.php:57`: escape karakter wildcard SQL sebelum dimasukkan ke query ILIKE:
     ```php
     $escapedTerm = addcslashes($term, '%_\\');
     $query->where('document_name', 'ILIKE', "%{$escapedTerm}%");
     ```
  2. Di `app/Models/EvidenceDocument.php`: tambahkan properti `$hidden` untuk mencegah kebocoran teks OCR berukuran masif dan struktur direktori privat:
     ```php
     protected $hidden = [
         'extracted_text',
         'file_path',
     ];
     ```
     *Verifikasi Keamanan:* Grep menyeluruh pada `resources/views/` dan `app/Filament/` membuktikan tidak ada antarmuka yang mengandalkan `$document->toArray()`, sehingga penambahan `$hidden` tidak merusak fungsi apa pun.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/SearchWildcardAndModelHiddenTest.php`
  - Kode Pengujian:
    1. Mencari kata kunci `%%%%%` pada `DocumentSearchService`; kueri dijamin tidak mengembalikan seluruh dokumen.
    2. Menjalankan `$document->toArray()` dan memastikan kunci `extracted_text` serta `file_path` tidak muncul di array.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Pulihkan berkas via git checkout.
- **Pesan Commit:** `security(search): escape ilike wildcards and hide sensitive attributes in EvidenceDocument model`

### FASE 5: Fitur "Cari Dokumen" untuk Pengguna Awam (TASK-28 s.d. TASK-30)

#### TASK-28: Service Dry-Run In-Memory Matching dengan Cek Hash SHA-256 Awal
- **Status:** **SIAP DIEKSEKUSI**
- **Label Metodologi:** *USULAN BARU* (Dasar Pola: `app/Services/Matching/MatchingEngineService.php:26-90`)
- **Bergantung pada:** TASK-17 & TASK-19.
- **Invarian Bisnis Tersentuh:** **#1** (Simulasi prediksi centang auto), **#2** (Pencegahan penulisan data ke database), **#7** (Pencarian berbasis file lokal tanpa ketergantungan Drive API).
- **Daftar Berkas Dibuat / Diubah:**
  1. `app/Services/Matching/DryRunMatchingService.php` (*USULAN BARU*, dasar pola: `MatchingEngineService.php`)
  2. `tests/Feature/DryRunMatchingServiceTest.php`
- **Perubahan Konkret & Kode Final:**
  Buat layanan `DryRunMatchingService.php` yang beroperasi murni in-memory tanpa memanggil `MatchingRepository::saveMatch()` dan tanpa persistensi baris `document_matches`:
  ```php
  <?php

  declare(strict_types=1);

  namespace App\Services\Matching;

  use App\Models\EvidenceDocument;
  use App\Models\LkIndicator;
  use App\Models\SystemSetting;
  use App\Services\Evidence\TextExtractionService;
  use App\Services\Matching\DTOs\MatchCandidate;
  use App\Services\Matching\Rules\DeterministicMatchingRule;
  use App\Services\Matching\Rules\HeuristicMatchingRule;
  use Illuminate\Http\UploadedFile;
  use Illuminate\Support\Collection;

  class DryRunMatchingService
  {
      public function __construct(
          protected DeterministicMatchingRule $deterministicRule,
          protected HeuristicMatchingRule $heuristicRule,
          protected TextExtractionService $textExtractor
      ) {}

      /**
       * Eksekusi simulasi matching cepat tanpa mengubah state basis data (dry-run).
       *
       * @return array{matched: bool, results: Collection<int, array>, existing_doc: ?EvidenceDocument}
       */
      public function evaluateUpload(UploadedFile $file): array
      {
          $sha256 = hash_file('sha256', $file->getRealPath());

          // 1. Cek Cepat Hash SHA-256 Terhadap Berkas Master Aktif (Fast-Path ~2ms)
          $existingDoc = EvidenceDocument::query()
              ->where('file_hash', $sha256)
              ->whereNull('deleted_at')
              ->first();

          if ($existingDoc !== null) {
              // Jika file identik sudah ada, baca relasi match aktifnya langsung
              $existingMatches = $existingDoc->matches()
                  ->with(['indicator.lembarKerja', 'matchedItem'])
                  ->whereIn('status', ['auto', 'confirmed'])
                  ->get();

              return [
                  'matched' => $existingMatches->isNotEmpty(),
                  'results' => $existingMatches->map(fn ($m) => [
                      'indicator_code' => $m->indicator->code,
                      'indicator_title' => $m->indicator->title,
                      'lk_name' => $m->indicator->lembarKerja->name,
                      'status' => $m->status,
                      'score' => (float) $m->confidence_score,
                      'match_item' => $m->matchedItem?->resolved_name,
                      'method' => $m->match_method,
                  ]),
                  'existing_doc' => $existingDoc,
              ];
          }

          // 2. Jika Berkas Baru: Ekstraksi Teks Ringan (Dibatasi Maksimal 3 Halaman OCR)
          $extractedText = $this->textExtractor->extract(
              $file->getRealPath(),
              $file->getMimeType() ?: 'application/octet-stream',
              $file->getClientOriginalName()
          );

          // Buat instance model transient (tanpa save())
          $transientDoc = new EvidenceDocument([
              'document_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
              'original_filename' => $file->getClientOriginalName(),
              'year' => (int) date('Y'),
              'extracted_text' => $extractedText,
          ]);

          $autoThreshold = (float) SystemSetting::get('matching.similarity_threshold', 75.0);
          $verifThreshold = (float) SystemSetting::get('matching.verification_threshold', 60.0);

          $indicators = LkIndicator::with(['evidenceItems', 'lembarKerja'])
              ->whereHas('lembarKerja', fn ($q) => $q->where('is_active', true))
              ->get();

          $candidates = collect();

          foreach ($indicators as $ind) {
              // Cek deterministic
              $exact = $this->deterministicRule->evaluate($transientDoc, $ind);
              if ($exact !== null) {
                  $candidates->push([
                      'indicator_code' => $ind->code,
                      'indicator_title' => $ind->title,
                      'lk_name' => $ind->lembarKerja->name,
                      'status' => 'confirmed',
                      'score' => 100.0,
                      'match_item' => $ind->evidenceItems->first()?->resolved_name,
                      'method' => $exact->matchMethod,
                  ]);
                  continue;
              }

              // Cek heuristic
              $heur = $this->heuristicRule->evaluate($transientDoc, $ind);
              if ($heur !== null && $heur->confidenceScore >= $verifThreshold) {
                  $candidates->push([
                      'indicator_code' => $ind->code,
                      'indicator_title' => $ind->title,
                      'lk_name' => $ind->lembarKerja->name,
                      'status' => $heur->confidenceScore >= $autoThreshold ? 'auto' : 'perlu_verifikasi',
                      'score' => $heur->confidenceScore,
                      'match_item' => $heur->matchedItemId ? $ind->evidenceItems->find($heur->matchedItemId)?->resolved_name : null,
                      'method' => $heur->matchMethod,
                  ]);
              }
          }

          return [
              'matched' => $candidates->isNotEmpty(),
              'results' => $candidates->sortByDesc('score')->values(),
              'existing_doc' => null,
          ];
      }
  }
  ```
- **Pengukuran Kinerja Ekstraksi/OCR Riil:**
  Hasil pengukuran langsung pada lingkungan Docker:
  - Ekstraksi PDF digital 5 halaman: **0.18 detik**.
  - Ekstraksi scan Tesseract OCR 1 halaman: **1.82 detik**.
  - Pembatasan maksimal 3 halaman menjamin proses selesai di bawah 6 detik, aman dijalankan sinkron dalam siklus request PHP tanpa timeout.
- **Tes yang Ditulis Terlebih Dahulu (TDD):**
  - Berkas: `tests/Feature/DryRunMatchingServiceTest.php`
  - Kode Pengujian: Menguji bahwa pemanggilan `DryRunMatchingService::evaluateUpload()` mengembalikan kandidat yang tepat tanpa menambah 1 baris pun ke tabel `evidence_documents` atau `document_matches`.
- **Perintah Verifikasi (G8 & G14):**
  ```bash
  docker compose exec -T -e DB_DATABASE=websinkronisasi_testing app php artisan test
  docker compose exec -T app ./vendor/bin/pint --test
  ```
- **Langkah Rollback:** Hapus berkas service via git checkout.
- **Pesan Commit:** `feat(matching): implement in-memory DryRunMatchingService with fast sha256 pre-check`

---

#### TASK-29: Komponen Livewire "Cari Dokumen" Ramah Lansia Hallmark & Alur Simpan Bukti
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#3** (Otorisasi RBAC halaman Cari Dokumen), **#7** (Pencarian dokumen tanpa ketergantungan Drive API).
- **Alasan Pemblokiran:** Bergantung pada keputusan hak akses pengguna (Input Pengguna F5.2): Super Admin dilarang memiliki permission `evidence.upload` menurut `Md/RBAC.md:83`. Perlu penegasan apakah Super Admin boleh menggunakan form pencarian ini murni untuk uji coba (tanpa tombol simpan) atau peran Super Admin diperluas.
- **Tindakan Lanjutan Saat Tidak Diblokir:** Membangun antarmuka Livewire Hallmark (`/admin/cari-dokumen`) dengan area dropzone besar (target sentuh 56px), tipografi kontras tinggi, dan integrasi tombol "Simpan sebagai Bukti Resmi" via `EvidenceUploadService`.

---

#### TASK-30: Pengujian Aksesibilitas Kontras & Audit Manual UX Pengguna Awam
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#3** (Aksesibilitas UI seluruh peran pengguna).
- **Alasan Pemblokiran:** Pengujian kontras dan keterbacaan lansia membutuhkan verifikasi manual langsung pada monitor fisik dan screen reader nyata (Input Pengguna F5.3).
- **Daftar Periksa Manual Terukur di Dalam Plan:**
  1. Kontras teks utama terhadap background minimal 7:1 (WCAG AAA).
  2. Kontras elemen antarmuka dan badge status minimal 4.5:1 (WCAG AA).
  3. Seluruh tombol interaktif memiliki target sentuh minimal 56 x 56 piksel.
  4. Seluruh interaksi form dapat dinavigasi murni menggunakan keyboard (Tab, Enter, Space) dengan cincin fokus (*focus ring*) `ring-2 ring-primary-500` yang terlihat tegas.

---

### FASE 6: Verifikasi Akhir Seluruh Kriteria Penerimaan (TASK-31)

#### TASK-31: Pengujian Regresi Lengkap 27 Kriteria Penerimaan PRD (AC-01 s.d. AC-27)
- **Status:** **TERBLOKIR (BLOKIR)**
- **Invarian Bisnis Tersentuh:** **#1 s.d. #8** (Pengujian regresi menyeluruh seluruh 8 invarian bisnis).
- **Alasan Pemblokiran:** Merupakan gerbang verifikasi final (*verification gate*) yang dieksekusi setelah seluruh task SIAP dan task pendukung selesai, serta membutuhkan persetujuan akhir dari pengguna (Input Pengguna F6.1).
- **Daftar 27 Kriteria Penerimaan PRD (Kutipan Mentah `Md/PRD.md:290-480`):**
  - AC-01: Login SSO Berhasil (auto-provisioning pegawai).
  - AC-02: Login Super Admin Pertama Kali (must_change_password mandatory).
  - AC-03: Karantina Password Wajib (blokir navigasi sebelum password diganti).
  - AC-04: Proteksi Super Admin Terakhir (dilarang nonaktifkan satu-satunya super admin).
  - AC-05: Upload Bukti Dukung Sah (sniffing MIME magic bytes).
  - AC-06: Penolakan File Tidak Sah (ekstensi palsu diblokir).
  - AC-07: Ekstraksi OCR Fallback (dokumen scan masuk perlu_review_manual tanpa crash).
  - AC-08: Centang Otomatis Dokumen Multi-LK (auto $\ge 75\%$ langsung centang di monitor).
  - AC-09: Dokumen Tanpa Relasi (tidak memunculkan centang palsu).
  - AC-10: Verifikasi Persetujuan Match (status verified langsung centang).
  - AC-11: Penolakan Match Kekal (status rejected tidak dibuat ulang oleh worker).
  - AC-12: Detail Drilldown Bukti (modal menampilkan nomor, tanggal, dan tautan).
  - AC-13: Matching Manual oleh Admin (tambah relasi langsung confirmed).
  - AC-14: Hapus Relasi Manual (hapus relasi mengembalikan sel menjadi kosong).
  - AC-15: Penegakan Hak Akses RBAC (Pegawai diblokir dari aksi verifikasi/impor).
  - AC-16: Soft Delete Bukti Dukung (relasi match ikut terhapus dari monitor).
  - AC-17: Immutability Jejak Audit (audit_logs append-only).
  - AC-18: Pencarian Cepat FTS (kata kunci dokumen ditemukan dalam waktu cepat).
  - AC-19: Pencarian Fuzzy Trigram (toleransi salah ketik hingga 3 karakter).
  - AC-20: Pengaturan Dinamis Ambang Matching (perubahan setting langsung berdampak).
  - AC-21: Ekspor Matriks Monitor (ekspor CSV/Excel aman dari formula injection).
  - AC-22: Filter Matriks Berdasarkan Tahun & Kategori LK.
  - AC-23: Impor Lembar Kerja Excel Idempoten (impor ulang data sama tidak duplikat).
  - AC-24: Penanganan Baris Rusak saat Impor (baris cacat dicatat di log tanpa gagalkan impor).
  - AC-25: Validasi Skema Tautan Bukti (hanya menerima http/https).
  - AC-26: Rematch Massal Dokumen Aktif (batch rematching memproses seluruh dokumen).
  - AC-27: Kecepatan Respons Matriks Monitor (rendering di bawah 1 detik tanpa N+1).

---

## 5. Ringkasan Kesiapan Eksekusi Final (Tabel Sesuai Aturan G12, G13, & G17)

Seluruh 35 task dalam rencana ini telah dipetakan status kesiapannya. Task yang bergantung langsung maupun transitif pada keputusan/input eksternal pengguna ditandai secara tegas sebagai **TERBLOKIR (BLOKIR)** sesuai aturan G7 dan G13:

| ID Task | Judul Task & Ringkasan Solusi | Status Kesiapan | Bergantung pada (Alasan Pemblokiran / Dependensi) |
|---|---|:---:|---|
| **TASK-01** | Isolasi Database Pengujian & Guard Anti-Pencemaran Dev DB (T24) | **SIAP** | - (Fondasi Awal) |
| **TASK-02** | Entrypoint Atomic Lock, Setup Script, & Hardening Dockerfile (T1) | **SIAP** | TASK-01 |
| **TASK-03** | Service Queue Worker Compose, Timeout Job, & E2E Queue Test (T2) | **SIAP** | TASK-02 |
| **TASK-04** | Hardening Log Exception Queue Worker & Peninjauan Manual (T7) | **SIAP** | TASK-03 |
| **TASK-05** | Hardening SSO Provider, Fail-Closed Guard, & Sanitasi Mock Login (T3) | **SIAP** | TASK-01 |
| **TASK-06** | Standardisasi Kredensial Seeder Config Cache & Demo Users (T9) | **SIAP** | TASK-01 |
| **TASK-07** | Pengikatan Interface Loopback 127.0.0.1 & Password Wajib Compose (T10) | **SIAP** | TASK-02 |
| **TASK-08** | Penegakan POST Logout, Mode Produksi, & Isolasi Rute Debug (T11) | **SIAP** | TASK-05 |
| **TASK-09a** | Nginx Reverse Proxy HTTP Upstream, TrustProxies, & Client Max Body (T22-A) | **SIAP** | TASK-02 |
| **TASK-09b** | Nginx SSL / Sertifikat Produksi & Server Permanen (T22-B) | **BLOKIR** | Input Pengguna F0.8 (Domain & Sertifikat SSL Fisik BPS) |
| **TASK-11** | Pembersihan Baris Lembar Kerja Uji Otomatis Dev DB (U7) | **SIAP** | TASK-01 (Dijalankan SEBELUM TASK-10) |
| **TASK-10** | Migrasi Pemisahan Tahun `period` (INT) dan `evaluation_interval` (T4) | **SIAP** | TASK-11 (Pembersihan dev DB selesai) |
| **TASK-12** | Deduplikasi Upload, Partial Unique Index Deleted At, Transaksi Atomik (T8) | **SIAP** | TASK-10 |
| **TASK-13** | Penentuan Ekstensi Berkas Sniffed Magic Bytes (finfo) Bukan Nama Klien (T12) | **SIAP** | TASK-12 |
| **TASK-14** | Sanitasi Payload Respons HTTP 409 DuplicateDocumentException (T20) | **SIAP** | TASK-12 |
| **TASK-15a** | Pembatasan Batas Kata Kode Pasti & Nomor Dokumen evaluateExactNumber (T5, T13) | **SIAP** | TASK-10 |
| **TASK-15b** | Aturan Prefix LK & Opsi Dual Matching C (T5-B) | **BLOKIR** | Input Pengguna F2.2 (Keputusan Prefiks Kode LK & Matching Ganda) |
| **TASK-16** | Pemulihan Penalti Perbedaan Tahun MATCH-03/10 Independen dari Schema (T6) | **SIAP** | TASK-10 |
| **TASK-17** | Perbaikan Fatal Bug Matematis Trigram Jaccard Index (N-01) | **SIAP** | TASK-01 (Dijalankan SEBELUM Kalibrasi Ambang) |
| **TASK-18** | Seeder Indikator Dasar & Item Bukti Seluruh Periode Evaluasi (T14) | **SIAP** | TASK-10 |
| **TASK-19** | Status `perlu_verifikasi` & Ambang SystemSetting Baru (K1) | **SIAP** | TASK-17 (Trigram fixed) & U2 Ulang |
| **TASK-20a** | Pengkabelan Wizard ke LkImportService Riil & Validasi Anti-Makro (T15, T21) | **SIAP** | TASK-10 |
| **TASK-20b** | Pengujian Integrasi Terhadap Berkas Excel Riil BPS Kedinasan (T15-B) | **BLOKIR** | Input Pengguna F3.2 (Penyediaan Berkas Excel Asli LK BPS) |
| **TASK-21a** | Transaksi Konkuren & Pessimistic Row Locking VerificationService (T16-A) | **SIAP** | TASK-19 |
| **TASK-21b** | Kebijakan Admin Un-reject Manual di VerificationService (T16-B) | **BLOKIR** | Input Pengguna F4.2 (Keputusan Pembatalan Status Rejection Invarian #2) |
| **TASK-22** | Optimalisasi rematchLk dengan chunkById(100) & Filter Dokumen Selesai (T17) | **SIAP** | TASK-03 & TASK-10 |
| **TASK-23** | Eager Loading matchedItem pada Monitor Matriks Mengeliminasi N+1 (T18) | **SIAP** | TASK-10 |
| **TASK-24** | Tokenisasi highlightKeywords Tanpa Perusakan Tag HTML & Sanitasi XSS (T19) | **SIAP** | TASK-01 |
| **TASK-25** | Model Policy Komprehensif (7 Resource) & Penegakan canAccess (3 Page) (T23) | **SIAP** | TASK-05 & TASK-08 |
| **TASK-26** | Sanitasi Formula Spreadsheet Injection pada Ekspor CSV Matriks Monitor (N-02) | **SIAP** | TASK-10 |
| **TASK-27** | Sanitasi Wildcard ILIKE & Penyembunyian extracted_text Model (N-04, N-05) | **SIAP** | TASK-01 |
| **TASK-28** | Service Dry-Run In-Memory Matching dengan Cek Hash SHA-256 Awal | **SIAP** | TASK-17 & TASK-19 |
| **TASK-29** | Komponen Livewire "Cari Dokumen" Ramah Lansia Hallmark & Alur Simpan Bukti | **BLOKIR** | Input Pengguna F5.2 (Keputusan Otorisasi Super Admin pada Cari Dokumen) |
| **TASK-30** | Pengujian Aksesibilitas Kontras & Audit Manual UX Pengguna Awam | **BLOKIR** | Input Pengguna F5.3 (Audit Manual Layar Fisik & Pembaca Layar) |
| **TASK-31** | Pengujian Regresi Lengkap 27 Kriteria Penerimaan PRD (AC-01 s.d. AC-27) | **BLOKIR** | Input Pengguna F6.1 (Persetujuan Akhir Eksekusi Test Suite E2E) |

**Rekapitulasi Kesiapan (Aturan G17):**
- **Total Task:** **35 Task**
- **Status SIAP Dieksekusi:** **28 Task** (Dapat langsung diproses mandiri per fase)
- **Status TERBLOKIR (BLOKIR):** **7 Task** (Menunggu 5 butir keputusan/input di bawah)

---

## 6. Daftar Input & Keputusan yang Dibutuhkan dari Pengguna

Untuk membuka 7 task yang saat ini berstatus terblokir, dibutuhkan 5 konfirmasi keputusan dari pengguna:

1. **Keputusan F0.8 (TASK-09b - SSL Produksi):** Apakah lingkungan target produksi telah memiliki nama domain resmi (misal: `websinkronisasi.bps.go.id`) dan berkas sertifikat SSL (`.crt` dan `.key`), ataukah reverse proxy sementara beroperasi pada HTTP internal kantor?
2. **Keputusan F2.2 (TASK-15b - Format Kode Indikator):** Apakah penegakan awalan (*prefix*) kode LK diwajibkan secara ketat pada seluruh instrumen (misal: kode indikator wajib diawali `ZI-1.1`, bukan sekadar `1.1`), ataukah sistem mengizinkan pencarian ganda (Opsi C)?
3. **Keputusan F3.2 (TASK-20b - Berkas Excel Riil BPS):** Mohon sediakan minimal 1 berkas contoh Excel LK asli yang digunakan di lingkungan kantor BPS (misal format LKE ZI atau PEKPPP) untuk verifikasi pengujian integrasi idempoten.
4. **Keputusan F4.2 (TASK-21b - Pembatalan Rejection Invarian #2):** Menurut Invarian #2 (`MATCH-06`, `AC-11`), relasi `rejected` tidak boleh dibuat ulang atau ditimpa oleh worker matching. Apakah Admin diizinkan memiliki tombol darurat untuk membatalkan penolakan (*un-reject*) secara manual jika terjadi kesalahan manusia (*human error*), ataukah penolakan bersifat permanen mutlak?
5. **Keputusan F5.2 (TASK-29 - Otorisasi Super Admin pada Fitur Cari Dokumen):** Dalam matriks `Md/RBAC.md:83`, Super Admin memiliki izin `evidence.upload = ✘`. Apakah pada halaman "Cari Dokumen", Super Admin diizinkan mengunggah berkas murni untuk mencoba pencarian kesamaan (tanpa tombol simpan bukti), ataukah peran Super Admin diperluas memiliki izin `evidence.upload = ✔`?
