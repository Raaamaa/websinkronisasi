# PRD — Sistem Matching Bukti Dukung ke Lembar Kerja (LK)

| Atribut | Nilai |
|---|---|
| Versi dokumen | 1.1 (DRAFT, menunggu konfirmasi) |
| Tanggal | 29 Oktober 2026 |
| Penulis | Akhmad Rizki Ramadhani (dibantu Claude) |
| Status | DRAFT — menunggu konfirmasi. Bagian 11.3 (Pertanyaan Terbuka) tidak menghalangi `DATABASE.md`, tetapi menghalangi `RBAC.md` dan `ARCHITECTURE.md` |
| Dokumen turunan | DATABASE.md, RBAC.md, MATCHING_ALGORITHM.md, ARCHITECTURE.md, TASKS.md, AGENTS.md |

> **Konvensi penandaan.** Teks bertanda **[Dn]** adalah keputusan yang sudah dikonfirmasi pengguna. Teks bertanda **[ASUMSI An]** adalah keputusan sementara yang belum dijawab eksplisit. Semua keputusan dan asumsi dirangkum di bagian 11. Jika ada yang berubah, dokumen ini dan dokumen turunannya HARUS diperbarui bersama.

---

## 1. Latar Belakang

Pegawai kantor memerlukan bukti dukung (SK, laporan, data, dan sejenisnya) untuk mengisi beberapa Lembar Kerja (LK) penilaian dan pelaporan. Satu dokumen bisa dipakai di banyak LK. Tanpa sistem, pencarian dilakukan manual lewat folder Drive, sehingga:

1. sulit mengetahui sebuah dokumen sudah dipakai di LK mana saja;
2. dokumen yang sama tersimpan berulang di banyak folder;
3. kesalahan upload atau penempatan dokumen sulit dilacak.

### 1.1 Temuan dari sampel LK nyata

Tiga file LK nyata sudah dianalisis. Strukturnya berbeda-beda, dan ini menentukan desain impor, model data, dan matching.

| LK | Satuan baris | Jumlah (sampel) | Sumber daftar bukti yang diharapkan | Tautan bukti |
|---|---|---|---|---|
| ZI (LKE Zona Integritas) | Butir, hierarki 4 level (area, komponen, sub-komponen, butir), kode seperti `AI1i` | 139 butir, 109 di antaranya berisi bukti | Teks bebas bernomor dalam satu sel ("1. SK Tim ... 2. SK Perubahan ...") | Tautan folder Drive per butir |
| PEKPPP | Indikator dalam 7 aspek, kode `1a`..`1g` | 31 indikator | Sheet terpisah berisi checklist sekitar 115 item dokumen dengan placeholder tahun `[t]` dan `[t-1]` | Tautan folder Drive per indikator |
| KIP (SAQ/PPID) | Butir pertanyaan, sebagian besar dalam beberapa sub-bagian | sekitar 59 butir berisi bukti | Kolom nama dokumen per butir; sebagian nama berawalan kode butir (contoh `1.2.a ...pdf`) | Tautan per butir, **mayoritas halaman web dan media sosial**, bukan Drive |

Catatan penting dari sampel:

- Kode indikator hanya unik di dalam satu LK (dan satu periode), bukan secara global.
- Tautan bukti ada di tingkat **indikator/butir**, bukan di tingkat LK.
- Tautan bukti tidak selalu Drive. KIP berisi tautan situs web, Instagram, dan YouTube.
- Satu file Excel bisa berisi beberapa sheet, termasuk sheet pembantu, sheet tersembunyi, dan pivot. KIP juga memiliki dua kelompok kolom bukti untuk dua satuan kerja.
- Setiap file memuat baris yang bukan indikator (narasi, rekap, rumus, judul) yang harus dilewati saat impor.
- Placeholder tahun pada nama item (`[t]`, `[t-1]`) membuat **periode menjadi wajib**.

## 2. Tujuan

| ID | Tujuan | Ukuran keberhasilan |
|---|---|---|
| G1 | Pegawai dapat mencari bukti dukung dan langsung melihat dokumen itu terpakai di LK mana saja | Hasil pencarian menampilkan matriks LK dalam satu layar |
| G2 | Pencocokan dokumen ke LK dilakukan otomatis saat upload | Setelah upload, relasi ke LK terbentuk tanpa input manual dan langsung tampil di monitor **[D2]** |
| G3 | Kesalahan pencocokan dapat dikoreksi | Admin dapat menambah atau menghapus relasi, dan koreksi tidak tertimpa oleh matching otomatis berikutnya |
| G4 | Akses dibatasi ketat per role | Pegawai tidak dapat mengubah data apa pun, meskipun memanggil endpoint secara langsung |
| G5 | Setiap centang terhubung ke lokasi bukti | Setiap centang dapat diklik menuju tautan bukti milik indikator yang cocok |
| G6 | LK dimuat ke sistem dari file Excel yang sudah ada | Super admin dapat mengimpor LK tanpa mengetik ulang indikator dan item bukti |

## 3. Di Luar Lingkup (Out of Scope) Versi 1.1

- Pengisian atau pengeditan isi LK di dalam sistem (sistem hanya memetakan, bukan mengisi LK).
- Penilaian atau perhitungan skor LK. Kolom nilai, komentar reviewer, dan sejenisnya pada file Excel **tidak diimpor**.
- Persetujuan berjenjang (workflow approval) selain verifikasi hasil matching oleh admin.
- Aplikasi mobile native.
- Penyuntingan dokumen di dalam sistem.
- Integrasi Google Drive API dan penyimpanan otomatis dokumen ke Drive **[D3]**. Sistem hanya menyimpan tautan.
- Sinkronisasi dua arah dengan Drive atau situs web: perubahan di sumber tautan tidak otomatis terbaca.
- Bukti yang hanya berupa halaman web atau media sosial (tanpa file) tidak dibuat menjadi Bukti Dukung. Tautan itu hanya disimpan sebagai tautan pada indikator.

## 4. Glosarium

| Istilah | Definisi |
|---|---|
| LK | Lembar Kerja. Satu entitas penilaian atau pelaporan yang menjadi tujuan pencocokan. Dimuat per periode (tahun). |
| Kategori LK | Pengelompokan LK: **Internal** (KIP, SAKIP, ZI, PEKPPP) dan **Eksternal** (Data Sekunder, Data Sektoral). |
| Periode | Tahun berlakunya sebuah LK, misalnya ZI 2025 dan PEKPPP 2026 **[ASUMSI A8]**. |
| Indikator LK | Butir persyaratan di dalam sebuah LK (baris ZI, indikator PEKPPP, butir KIP). Memiliki kode, uraian, dan tautan bukti. |
| Item Bukti LK | Satu dokumen yang diharapkan ada untuk sebuah indikator, misalnya "SK Tim Kerja ZI". Satu indikator memiliki nol atau lebih item. Item inilah yang dicocokkan dengan dokumen yang diunggah. |
| Tautan Bukti | URL yang menunjuk ke folder atau file di Drive, atau ke halaman web. Disimpan pada indikator. |
| Profil Impor | Konfigurasi per LK yang menjelaskan cara membaca file Excel: nama sheet, baris header, pemetaan kolom, dan cara memecah daftar item bukti. |
| Bukti Dukung (BD) | Dokumen atau file yang diunggah dan dicocokkan, misalnya SK. |
| Matching | Proses menentukan item bukti (dan indikator) LK mana yang dipenuhi oleh sebuah BD. |
| Relasi / Match | Satu pasangan (BD, Indikator LK) beserta statusnya. |
| Monitor | Tabel matriks yang menampilkan BD pada baris dan LK pada kolom dengan tanda centang. |
| Super Admin | Role tertinggi (akun awal berupa akun dummy). |
| Admin | Role pengelola data operasional. |
| Pegawai | Role pembaca (pencarian saja). |
| Akun nonaktif | Akun yang tidak dapat login sama sekali, dan hak pengelolaannya dicabut. Dapat diaktifkan kembali **[D4]**. |

## 5. Pengguna dan Role

### 5.1 Super Admin
- Mengelola akun admin (tambah admin, nonaktifkan admin, kelola peran).
- Menambah, mengubah, dan menghapus link Google Drive yang berisikan bukti dukung LK di database.
- Mengelola master Lembar Kerja, melihat jejak audit, dan mengatur parameter sistem.
- DILARANG mengunggah berkas SK/bukti untuk pencocokan dokumen (Super Admin fokus pada tata kelola akun & master link).
- Akun awal dibuat lewat seeder sebagai akun dummy, memakai password lokal (di luar SSO), dan wajib ganti password saat login pertama **[D6]**.

### 5.2 Admin
- Menambah, mengubah, dan menghapus link Google Drive bukti dukung LK di database (seperti Super Admin).
- Mengunggah berkas SK atau bukti dukung lainnya untuk mencari kesamaan dengan kebutuhan LK / link Drive.
- Memverifikasi hasil matching otomatis.
- TIDAK DAPAT menambahkan atau mengangkat orang lain untuk menjadi admin (hanya melalui Super Admin).

### 5.3 Pegawai
- Mengunggah berkas SK atau bukti dukung untuk mencari kesamaan (matching) dengan indikator LK secara otomatis.
- Melihat matriks monitor LK dan mengklik simbol centang (✔) untuk langsung membuka Google Drive bukti dukung.
- TIDAK DAPAT mengunggah, menambah, atau menghapus link Google Drive LK di database.
- TIDAK DAPAT mengelola akun pengguna lain.
- Login memakai akun SSO kantor **[D7]**.

## 6. Matriks Hak Akses (ringkas)

Rincian lengkap ada di `RBAC.md`.

| Aksi | Super Admin | Admin | Pegawai |
|---|:---:|:---:|:---:|
| Lihat/cari BD dan monitor | ✔ | ✔ | ✔ |
| Buka tautan Drive langsung dari centang | ✔ | ✔ | ✔ |
| Upload dokumen untuk matching | ✘ | ✔ | ✔ |
| Kelola link Drive LK di database (tambah/hapus) | ✔ | ✔ | ✘ |
| Tambah/hapus relasi BD ↔ LK manual | ✔ | ✔ | ✘ |
| Verifikasi hasil matching otomatis | ✔ | ✔ | ✘ |
| Hapus master BD | ✔ | ✘ | ✘ |
| Tambah/ubah/hapus master LK, indikator, item bukti | ✔ | ✘ | ✘ |
| Impor LK dari Excel | ✔ | ✘ | ✘ |
| Kelola & angkat role Admin | ✔ | ✘ | ✘ |
| Nonaktifkan/aktifkan akun Pegawai | ✔ | ✔ | ✘ |
| Nonaktifkan/aktifkan akun Admin | ✔ | ✘ | ✘ |
| Lihat audit log | ✔ | ✘ | ✘ |

## 7. Kebutuhan Fungsional

Prioritas: **M** = Must (wajib v1.1), **S** = Should, **C** = Could.

### 7.1 Autentikasi dan Akun (AUTH)

Aplikasi menggunakan **1 Portal Terpadu (*Unified Single Portal*)** di rute `/login`. Seluruh Pegawai dan Admin BPS masuk lewat SSO Keycloak BPS (OIDC) yang diakses melalui FortiClient VPN bila di luar kantor BPS. Super Admin login via formulir pengelola lokal pada portal tunggal yang sama. Lapisan SSO dibuat di balik antarmuka `SSOProviderInterface` sehingga fleksibel, terabstraksi, dan dapat disimulasikan secara lokal (Mock SSO driver).

| ID | Kebutuhan | Prio |
|---|---|:---:|
| AUTH-01 | Pegawai dan Admin login lewat SSO kantor. Alamat dan parameter SSO dibaca dari variabel environment, bukan ditulis di kode **[D7]** | M |
| AUTH-02 | Pengguna dikenali lewat identitas stabil dari SSO (email, atau NIP bila tersedia). Identitas ini unik di tabel pengguna | M |
| AUTH-03 | Akun super admin awal (dummy) dibuat lewat seeder, login memakai password lokal (di luar SSO), password disimpan dalam bentuk hash (bcrypt atau argon2), dengan flag `must_change_password = true`. Sistem memaksa ganti password sebelum fitur lain dapat diakses **[D6]** | M |
| AUTH-04 | Kredensial seeder TIDAK ditulis di kode sumber, tetapi dibaca dari variabel environment. Password asli tidak boleh dicatat di log | M |
| AUTH-05 | Super admin dapat mengubah role pengguna (Pegawai ↔ Admin). Tidak ada role lain yang dapat mengubah role **[D5]** | M |
| AUTH-06 | Sistem menolak penonaktifan atau penghapusan super admin terakhir, dan menolak pengguna menonaktifkan dirinya sendiri | M |
| AUTH-07 | Akun nonaktif tidak dapat login sama sekali, walaupun autentikasi SSO berhasil, dan sesi aktifnya diputus. Status aktif dicek di sisi server pada setiap permintaan, bukan hanya saat login. Pesan yang tampil: akun dinonaktifkan, hubungi admin **[D4]** | M |
| AUTH-08 | Super admin dapat menonaktifkan dan mengaktifkan kembali semua akun. Admin dapat melakukannya hanya untuk akun Pegawai **[D4, D5]** | M |
| AUTH-09 | Pembatasan percobaan login (rate limit) untuk login password lokal. Sesi kedaluwarsa setelah tidak aktif dalam waktu yang dikonfigurasi (default 120 menit) | S |
| AUTH-10 | Akun Pegawai dibuat otomatis saat login SSO pertama berhasil, dengan role Pegawai dan status aktif **[ASUMSI A11]** | M |
| AUTH-11 | Bila SSO tidak tersedia, Pegawai tidak dapat login. Tidak ada jalur login password lokal selain akun super admin dummy | M |

### 7.2 Master LK, Indikator, dan Item Bukti (LK)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| LK-01 | Sistem menyediakan 6 LK awal lewat seeder: KIP, SAKIP, ZI, PEKPPP (Internal), Data Sekunder, Data Sektoral (Eksternal) | M |
| LK-02 | Super admin dapat menambah, mengubah, dan menghapus LK beserta kategorinya | M |
| LK-03 | Setiap LK memiliki kode, nama, dan periode (tahun). Kombinasi kode dan periode unik | M |
| LK-04 | Setiap LK memiliki indikator dengan kode, uraian, dan tautan bukti. Kode indikator unik dalam satu LK dan periode, bukan secara global | M |
| LK-05 | Setiap indikator memiliki nol atau lebih Item Bukti LK (nama dokumen yang diharapkan). Item dapat berisi placeholder tahun `[t]` dan `[t-1]`, yang dihitung menurut periode LK | M |
| LK-06 | Penghapusan LK memakai soft delete. Sistem menampilkan jumlah relasi yang terdampak dan meminta konfirmasi sebelum menghapus | M |
| LK-07 | Perubahan indikator atau item bukti memicu matching ulang terhadap BD yang ada (lihat MATCH-07) | M |
| LK-08 | Indikator mendukung hierarki induk-anak opsional (contoh: area, komponen, butir pada ZI) untuk pengelompokan tampilan | S |
| LK-09 | Tautan bukti pada indikator hanya menerima skema http dan https. Skema lain (misalnya `javascript:`, `file:`, `chrome-extension:`) ditolak | M |
| LK-10 | Penandaan PIC pada indikator bersifat opsional. Bila disimpan, hanya sebagai teks. Nilai, skor, dan komentar reviewer tidak disimpan | C |

### 7.3 Impor LK dari Excel (LKIMP)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| LKIMP-01 | Super admin dapat mengimpor sebuah LK dari file `.xlsx` untuk satu LK dan satu periode | M |
| LKIMP-02 | Setiap LK memiliki Profil Impor: nama sheet, baris header, pemetaan kolom (kode, uraian, item bukti, tautan bukti, PIC opsional), dan aturan pemecahan item bukti. Aturan yang didukung: (a) daftar bernomor dalam satu sel, (b) sheet checklist terpisah, (c) satu item per baris | M |
| LKIMP-03 | Profil awal disediakan untuk tiga LK yang sudah punya sampel (ZI, PEKPPP, KIP). LK tanpa sampel (SAKIP, Data Sekunder, Data Sektoral) memakai profil generik yang dipetakan lewat antarmuka, atau diisi manual | M |
| LKIMP-04 | Sistem menampilkan pratinjau hasil impor dan laporan baris bermasalah sebelum data disimpan. Penyimpanan baru terjadi setelah super admin mengonfirmasi | M |
| LKIMP-05 | Baris yang bukan indikator (judul, narasi, rekap, baris kosong) dilewati dan dilaporkan, bukan dianggap indikator. Sheet pembantu, sheet tersembunyi, dan pivot diabaikan kecuali dipilih dalam profil | M |
| LKIMP-06 | Pada file dengan kelompok kolom bukti untuk lebih dari satu satuan kerja (contoh KIP), profil menentukan kelompok kolom yang dibaca | M |
| LKIMP-07 | Impor bersifat idempoten: mengimpor file yang sama dua kali tidak menggandakan data. Pencocokan baris memakai (LK, periode, kode indikator) | M |
| LKIMP-08 | Impor ulang yang mengubah item bukti memicu matching ulang tanpa menyentuh match `confirmed` dan `rejected` (MATCH-07) | M |
| LKIMP-09 | Importer membaca teks sel dan juga target hyperlink sel, karena tautan bisa berupa teks atau objek hyperlink | M |
| LKIMP-10 | Importer hanya membaca nilai, tidak menjalankan rumus atau makro. File `.xlsm` dan tipe lain ditolak. Ukuran dan struktur file dibatasi untuk mencegah file berbahaya | M |
| LKIMP-11 | Seluruh impor tercatat di audit log (siapa, kapan, file, jumlah baris diterima dan dilewati) | M |

### 7.4 Bukti Dukung (BD)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| BD-01 | Admin dan super admin dapat mengunggah dokumen. Format yang diterima: PDF, DOCX, JPG, PNG **[ASUMSI A5]** | M |
| BD-02 | Batas ukuran file default 20 MB (dapat dikonfigurasi). File melebihi batas ditolak dengan pesan jelas | M |
| BD-03 | Validasi tipe file berdasarkan isi (MIME sniffing), bukan berdasarkan ekstensi saja | M |
| BD-04 | Saat upload, sistem menghitung hash SHA-256 file | M |
| BD-05 | Jika hash sudah ada di sistem, upload ditolak sebagai duplikat dan sistem menampilkan BD yang sudah ada beserta relasinya (tidak membuat BD ganda) | M |
| BD-06 | Metadata BD: nama dokumen, nomor dokumen (opsional), tahun, jenis dokumen (opsional), tautan (opsional) | M |
| BD-07 | Sistem mengekstrak teks dokumen. PDF digital memakai ekstraksi teks langsung. Dokumen scan atau gambar memakai OCR **[ASUMSI A5]** | M |
| BD-08 | Jika ekstraksi teks gagal atau kosong, BD tetap tersimpan dengan status `perlu_review_manual`, dan sistem tidak boleh berhenti dengan error tak tertangani | M |
| BD-09 | Penghapusan master BD hanya oleh super admin, memakai soft delete, dan relasinya ikut disembunyikan | M |
| BD-10 | File fisik disimpan di penyimpanan privat pada server kantor (bukan folder publik web) dan diakses hanya lewat otorisasi **[D3, D8]** | M |

### 7.5 Matching (MATCH)

Aturan detail ada di `MATCHING_ALGORITHM.md`. Di PRD ini hanya perilaku yang terlihat oleh pengguna. Objek yang dicocokkan adalah BD terhadap **Item Bukti LK** dan indikatornya.

| ID | Kebutuhan | Prio |
|---|---|:---:|
| MATCH-01 | Matching berjalan otomatis setelah upload berhasil, secara asinkron (queue). Antarmuka tidak boleh macet menunggu proses | M |
| MATCH-02 | Hasil matching memiliki status: `auto` (hasil algoritma, belum diperiksa admin), `confirmed` (diperiksa admin atau dibuat manual), `rejected` (ditolak atau dihapus) | M |
| MATCH-03 | Match dibuat hanya bila skor kecocokan mencapai ambang. Match berbasis bukti pasti (nomor dokumen identik, atau kode indikator pada nama file, misalnya `1.2.a ...pdf`) langsung `confirmed`. Match berbasis kemiripan nama atau isi berstatus `auto`. **Keduanya langsung tampil di monitor** **[D2]** | M |
| MATCH-04 | Admin dapat mengonfirmasi atau menolak match `auto` melalui antrean verifikasi. Konfirmasi tidak mengubah tampilan ✔. Penolakan membuat match `rejected` dan ✔ hilang | M |
| MATCH-05 | Admin dapat menambah match secara manual (status `confirmed`, metode `manual`) dan menghapus match | M |
| MATCH-06 | Match yang pernah `rejected` atau dihapus manual TIDAK boleh dibuat ulang oleh matching otomatis. Matching ulang harus menghormati keputusan admin | M |
| MATCH-07 | Matching ulang dijalankan saat indikator atau item bukti LK ditambah, diubah, atau diimpor ulang. Hasil `confirmed` dan `rejected` tidak tersentuh. Hasil `auto` yang tidak lagi memenuhi ambang dihapus atau diperbarui | M |
| MATCH-08 | Setiap perubahan match tercatat di audit log (siapa atau sistem, kapan, aksi, sebelum dan sesudah) | M |
| MATCH-09 | Admin dapat memicu matching ulang manual untuk satu BD | S |
| MATCH-10 | Sistem menampilkan skor dan alasan kecocokan (kata, nomor, atau kode yang cocok) pada antrean verifikasi dan pada detail sel monitor | M |
| MATCH-11 | Ambang skor dapat dikonfigurasi oleh super admin. Nilai awal dibuat konservatif | S |

### 7.6 Monitor Matriks (MON)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| MON-01 | Baris = Indikator Kesamaan / Kebutuhan Bukti LK. Kolom = Lembar Kerja (LK KIP, ZI, PEKPPP, dll.), dikelompokkan di bawah header Internal dan Eksternal | M |
| MON-02 | Sel diberi tanda ✔ jika ada match berstatus `auto` atau `confirmed` untuk indikator tersebut pada LK terkait. Jika tidak ada kecocokan, sel DIKOSONGKAN murni, tanpa simbol apa pun **[D2]** | M |
| MON-03 | Match `rejected` tidak pernah tampil di monitor | M |
| MON-04 | Sel ✔ dapat diklik: langsung membuka Google Drive bukti dukung di tab baru (`target="_blank" rel="noopener noreferrer"`) | M |
| MON-05 | Tautan Google Drive disimpan dan dikelola langsung di database oleh Super Admin dan Admin | M |
| MON-06 | Jika tautan belum tersedia atau belum ada bukti cocok, sel dikosongkan murni | M |
| MON-07 | Monitor mendukung penyaringan berdasarkan kategori LK, LK tertentu, periode, dan jenis dokumen | M |
| MON-08 | Monitor memakai paginasi di sisi server. Tidak memuat seluruh data sekaligus | M |
| MON-09 | Ekspor monitor ke Excel atau CSV | C |
| MON-10 | Tautan eksternal dibuka di tab baru dengan `rel="noopener noreferrer"`, dan hanya tautan http atau https yang dibuat dapat diklik | M |

### 7.7 Pencarian (SEARCH)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| SEARCH-01 | Satu kolom pencarian untuk nama dokumen, nomor dokumen, dan isi dokumen (full-text) | M |
| SEARCH-02 | Pencarian toleran terhadap salah ketik ringan (fuzzy) | S |
| SEARCH-03 | Hasil pencarian menampilkan baris monitor (centang LK) untuk setiap dokumen yang ditemukan | M |
| SEARCH-04 | Pencarian hanya mencari di BD yang aktif (tidak terhapus) | M |
| SEARCH-05 | Input pencarian dibersihkan (sanitasi) dan memakai query terparameter untuk mencegah SQL injection | M |
| SEARCH-06 | Pencarian tidak boleh mengembalikan error pada input kosong, berisi karakter khusus, atau sangat panjang | M |

### 7.8 Audit Log (AUDIT)

| ID | Kebutuhan | Prio |
|---|---|:---:|
| AUDIT-01 | Mencatat: login dan logout, pembuatan akun otomatis, perubahan role, aktivasi dan penonaktifan akun, CRUD master LK, impor LK, upload dan hapus BD, serta perubahan match | M |
| AUDIT-02 | Catatan audit tidak dapat diubah atau dihapus lewat antarmuka | M |
| AUDIT-03 | Super admin dapat melihat dan menyaring audit log berdasarkan pengguna, aksi, dan tanggal | S |

## 8. Alur Utama

### 8.1 Alur Impor LK
1. Super admin memilih LK, periode, dan file `.xlsx`, lalu memilih atau memetakan Profil Impor.
2. Sistem memvalidasi file (LKIMP-10) dan membaca sheet sesuai profil.
3. Sistem menampilkan pratinjau: indikator dan item bukti yang diterima, baris yang dilewati, dan baris bermasalah (LKIMP-04, LKIMP-05).
4. Super admin mengonfirmasi. Sistem menyimpan data secara idempoten (LKIMP-07).
5. Sistem menjalankan matching ulang terhadap BD yang ada (MATCH-07).

### 8.2 Alur Upload dan Matching
1. Admin membuka halaman Upload dan memilih file serta mengisi metadata.
2. Sistem memvalidasi tipe, ukuran, dan MIME (BD-02, BD-03).
3. Sistem menghitung SHA-256. Jika duplikat: berhenti dan tampilkan BD yang sudah ada (BD-05).
4. File disimpan dan record BD dibuat dengan status `memproses`.
5. Job di queue mengekstrak teks (OCR jika perlu) lalu menjalankan matching terhadap item bukti.
6. Match bukti pasti dibuat `confirmed`. Match kemiripan dibuat `auto` bila skor mencapai ambang (MATCH-03). Keduanya langsung tampil ✔ di monitor.
7. Status BD menjadi `selesai` atau `perlu_review_manual` (BD-08).
8. Admin dapat memeriksa match `auto` di antrean verifikasi dan mengonfirmasi atau menolaknya.

### 8.3 Alur Pencarian oleh Pegawai
1. Pegawai membuka aplikasi dan login lewat SSO.
2. Pegawai mengetik kata kunci atau memakai filter pada halaman Monitor atau Pencarian.
3. Sistem menampilkan daftar BD beserta centang LK.
4. Pegawai mengklik ✔ untuk melihat indikator yang cocok dan membuka tautan bukti.

### 8.4 Alur Koreksi oleh Admin
1. Admin menemukan BD yang salah ditempatkan.
2. Admin menghapus match yang salah atau menambah match yang benar secara manual.
3. Sistem mencatat audit log dan menandai pasangan itu agar tidak dibuat ulang otomatis (MATCH-06).

### 8.5 Alur Pegawai Pindah atau Pensiun
1. Super admin atau admin menonaktifkan akun Pegawai tersebut (AUTH-08).
2. Sesi aktif diputus. Login berikutnya ditolak walau SSO berhasil (AUTH-07).
3. Bila perlu diaktifkan kembali, pegawai meminta ke admin atau super admin, yang mengaktifkan akun.

## 9. Kebutuhan Non-Fungsional

| ID | Kategori | Kebutuhan |
|---|---|---|
| NFR-01 | Keamanan | Otorisasi dicek di sisi server pada setiap endpoint. Menyembunyikan tombol di antarmuka TIDAK dianggap sebagai pengamanan |
| NFR-02 | Keamanan | Perlindungan CSRF dan XSS aktif. Output dari data pengguna di-escape, termasuk teks dan tautan hasil impor Excel |
| NFR-03 | Keamanan | Seluruh akses melalui HTTPS. Sertifikat harus dipercaya oleh perangkat pegawai (misalnya sertifikat dari CA internal) |
| NFR-04 | Keamanan | Nama file asli dari pengguna tidak dipakai langsung sebagai path penyimpanan (cegah path traversal). Gunakan nama acak, simpan nama asli sebagai metadata |
| NFR-05 | Kinerja | Halaman monitor dan pencarian merespons ≤ 2 detik untuk 10.000 BD **[ASUMSI A6, target dapat berubah setelah jumlah data dikonfirmasi]** |
| NFR-06 | Kinerja | Upload, impor LK, dan matching tidak memblokir antarmuka (asinkron) |
| NFR-07 | Keandalan | Job yang gagal dicoba ulang maksimal 3 kali, lalu ditandai gagal dan terlihat oleh admin. Tidak boleh gagal diam-diam |
| NFR-08 | Keandalan | Backup database dan penyimpanan file terjadwal. Prosedur ada di DEPLOYMENT.md |
| NFR-09 | Integritas data | Relasi dan constraint ditegakkan di tingkat database (foreign key, unique), bukan hanya di kode aplikasi |
| NFR-10 | Integritas data | Pasangan (BD, Indikator) dijamin unik oleh unique constraint, dan indikator unik per (LK, periode, kode), sehingga race condition dan impor ulang tidak menghasilkan data ganda |
| NFR-11 | Kegunaan | Antarmuka responsif (desktop dan tablet utama, ponsel dapat dipakai) |
| NFR-12 | Bahasa | Antarmuka berbahasa Indonesia |
| NFR-13 | Observabilitas | Error dicatat ke log dengan konteks. Pesan error ke pengguna tidak membocorkan detail internal |
| NFR-14 | Zona waktu | Disimpan dalam UTC, ditampilkan dalam Asia/Jakarta |
| NFR-15 | Deployment | Aplikasi di-host di server kantor dan dijangkau melalui jaringan kantor atau VPN. Autentikasi VPN (termasuk OTP) berada di luar aplikasi dan tidak diimplementasikan di dalamnya **[D8]** |
| NFR-16 | Keamanan | Ketergantungan pada SSO dibungkus satu antarmuka sehingga protokol (OIDC, SAML, atau lainnya) dapat diganti tanpa mengubah modul lain |

## 10. Kriteria Penerimaan (Acceptance Criteria)

Versi 1.1 dinyatakan selesai jika SEMUA butir berikut lolos:

| ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| AC-01 | Pegawai memanggil endpoint tambah/hapus match secara langsung | Ditolak (HTTP 403), data tidak berubah |
| AC-02 | Admin mencoba membuka halaman kelola LK, impor LK, atau ubah role | Ditolak (HTTP 403) |
| AC-03 | Login super admin seeder pertama kali | Dipaksa ganti password sebelum mengakses fitur lain |
| AC-04 | Super admin terakhir mencoba menonaktifkan atau menghapus dirinya | Ditolak dengan pesan jelas |
| AC-05 | Upload file yang sama dua kali | Upload kedua ditolak sebagai duplikat, BD tidak ganda |
| AC-06 | Upload file .exe yang diganti nama menjadi .pdf | Ditolak oleh validasi MIME |
| AC-07 | Upload PDF scan tanpa teks yang dapat diekstrak | BD tersimpan berstatus `perlu_review_manual`, tidak ada error 500 |
| AC-08 | Dokumen cocok dengan LK 1, 2, dan 3 (status `auto` atau `confirmed`) | Monitor menampilkan ✔ pada kolom LK 1, 2, 3 dan sel lain kosong sepenuhnya |
| AC-09 | Dokumen tanpa kecocokan | Seluruh sel LK pada baris itu kosong |
| AC-10 | Match `auto` baru dibuat dan belum diverifikasi | Langsung tampil ✔ di monitor untuk semua role, tampil di antrean verifikasi admin, dan detail sel menunjukkan status Otomatis |
| AC-11 | Admin menolak atau menghapus match, lalu LK di-matching ulang | Match yang ditolak tidak muncul kembali, dan ✔ hilang dari monitor |
| AC-12 | Klik ✔ pada sel | Tampil indikator yang cocok dan tautan bukti yang valid, atau keterangan "tautan belum tersedia" |
| AC-13 | Pencarian dengan input kosong, `'; DROP TABLE--`, atau 10.000 karakter | Tidak ada error 500, tidak ada injeksi, hasil kosong atau pesan validasi |
| AC-14 | Super admin menghapus LK yang punya relasi | Muncul konfirmasi dengan jumlah relasi terdampak. Setelah hapus, LK hilang dari monitor dan data dapat dipulihkan (soft delete) |
| AC-15 | Akun dinonaktifkan saat penggunanya sedang login | Sesi diputus dan akses berikutnya ditolak |
| AC-16 | Dua admin menambah match yang sama bersamaan | Hanya satu record tersimpan (unique constraint), tidak ada error 500 |
| AC-17 | Setiap aksi pada AC-05 s.d. AC-16 yang mengubah data | Tercatat di audit log |
| AC-18 | Pengguna nonaktif berhasil autentikasi di SSO lalu membuka aplikasi | Akses ditolak dengan pesan akun dinonaktifkan, tidak ada sesi yang terbentuk |
| AC-19 | Admin mengubah role seorang pegawai, atau menonaktifkan akun Admin lain | Ditolak (HTTP 403) |
| AC-20 | Admin menonaktifkan lalu mengaktifkan kembali akun Pegawai | Berhasil dan tercatat di audit log |
| AC-21 | Pengguna SSO yang belum punya akun login pertama kali | Akun terbentuk otomatis dengan role Pegawai (bila A11 disetujui) |
| AC-22 | Impor file LKE ZI, PEKPPP, dan KIP sampel memakai profil masing-masing | Jumlah indikator dan item bukti sesuai sampel (ZI: 139 butir, PEKPPP: 31 indikator, KIP: sekitar 59 butir dengan bukti), baris narasi dan rekap dilewati |
| AC-23 | Impor file yang sama dua kali | Tidak ada data ganda. Jumlah indikator dan item tetap |
| AC-24 | Impor file `.xlsm`, file bukan Excel, atau file melebihi batas | Ditolak dengan pesan jelas, tidak ada error 500 |
| AC-25 | Sel tautan berisi `javascript:alert(1)` atau skema selain http/https | Tautan ditolak pada impor dan tidak pernah dapat diklik |
| AC-26 | Item bukti `Formulir Evaluasi SP tahun [t-1]` pada LK periode 2026 | Item dihitung sebagai tahun 2025 saat dicocokkan |
| AC-27 | Impor ulang mengubah item bukti | Matching ulang berjalan, match `confirmed` dan `rejected` tidak berubah |

## 11. Keputusan, Asumsi, dan Pertanyaan Terbuka

### 11.1 Keputusan yang sudah dikonfirmasi pengguna

| ID | Keputusan | Menggantikan |
|---|---|---|
| D1 | LK berupa file Excel berisi indikator dan daftar bukti yang diharapkan (sampel: ZI, PEKPPP, KIP). Matching dilakukan terhadap item bukti dan indikator | A1 |
| D2 | Semua hasil algoritma (di atas ambang) langsung tampil ✔ di monitor tanpa menunggu verifikasi. Admin tetap dapat mengonfirmasi, menolak, atau menghapus | A2 |
| D3 | Upload otomatis ke Drive tidak wajib. Sistem hanya menyimpan tautan bukti pada indikator | A3 |
| D4 | Akun nonaktif tidak bisa login sama sekali dan hak pengelolaannya dicabut. Dapat diaktifkan kembali lewat permintaan ke admin atau super admin | baru |
| D5 | Hanya super admin yang dapat mengubah role. Super admin mengelola semua pegawai. Admin dapat mengaktifkan dan menonaktifkan akun Pegawai | A7 |
| D6 | Akun super admin awal adalah akun dummy, login memakai password lokal ter-hash di luar SSO, dengan nilai apa pun yang diatur lewat environment, dan wajib diganti saat login pertama | baru |
| D7 | Pegawai dan Admin login lewat SSO kantor. URL dan parameter SSO diberikan lewat environment | A7 |
| D8 | Aplikasi di-host di server kantor. VPN FortiClient dan OTP berada di luar aplikasi. Bila VPN tersambung, login SSO otomatis memakai akun yang sama | baru |

### 11.2 Asumsi yang berlaku (belum dijawab eksplisit)

Dianggap berlaku kecuali ada keberatan. Setiap perubahan harus memperbarui dokumen ini dan dokumen turunannya.

| ID | Asumsi saat ini | Dampak jika salah |
|---|---|---|
| A4 | Admin boleh upload BD baru. Hanya super admin yang boleh menghapus master BD | Mengubah matriks hak akses |
| A5 | Dokumen berupa PDF digital, DOCX, dan sebagian scan (JPG/PNG/PDF scan), sehingga OCR diperlukan. Bahasa dokumen: Indonesia | Beban OCR dan akurasi matching |
| A6 | Skala indikator: sampel tiga LK saja sudah sekitar 229 indikator/butir dan ratusan item bukti, jadi estimasi awal "puluhan" diganti "ratusan". Jumlah BD belum diketahui, dipakai target 10.000 untuk kinerja | Pilihan desain pencarian dan kinerja |
| A8 | Periode (tahun) adalah atribut LK dan wajib, diturunkan dari sampel (nama file 2025/2026 dan placeholder `[t]`) | Perlu kolom dan filter periode di semua tabel |
| A9 | Satu baris monitor = satu BD (dokumen). Indikator yang cocok tampil saat sel diklik | Mengubah struktur tampilan utama |
| A11 | Akun Pegawai dibuat otomatis saat login SSO pertama dengan role Pegawai. Alternatif: hanya pengguna yang sudah didaftarkan super admin yang boleh masuk | Risiko akses oleh pengguna SSO di luar kantor ini bila SSO bersifat lintas instansi |

### 11.3 Pertanyaan terbuka

| ID | Pertanyaan | Menghalangi |
|---|---|---|
| Q1 | **[RESOLVED - SELESAI]** Protokol SSO BPS adalah Keycloak OpenID Connect (OIDC 1.0) di `https://sso.bps.go.id`, realm `pegawai-bps`, client id format `{kode_satker}-elka-nv1`, scope `openid profile email`, pengayaan NIP & nama via endpoint `api-pegawai`, serta membutuhkan FortiClient VPN bila diakses dari luar kantor BPS. | Selesai diimplementasikan di `BpsKeycloakOidcProvider` |
| Q2 | Spesifikasi server kantor: sistem operasi, apakah boleh memasang Docker/PostgreSQL/PHP, kapasitas disk, siapa yang mengelola akses dan sertifikat HTTPS | `ARCHITECTURE.md`, `DEPLOYMENT.md` |
| Q3 | Format SAKIP, Data Sekunder, dan Data Sektoral belum ada sampelnya. Sementara dipakai profil impor generik (LKIMP-03) | `DATABASE.md` (pengaruh kecil), `TASKS.md` |
| Q4 | **[RESOLVED - SELESAI]** Tech stack resmi terkonfirmasi: PHP 8.3 / Laravel 11 + Filament v3 + PostgreSQL 15 + Redis 7. Desain antarmuka login menggunakan skill Hallmark (tema Cobalt modern-minimal). | Selesai diimplementasikan |

## 12. Risiko

| ID | Risiko | Dampak | Mitigasi |
|---|---|---|---|
| R1 | Matching berbasis teks menghasilkan centang salah, dan karena langsung tampil, Pegawai bisa memakai bukti yang tidak tepat | Pegawai memakai bukti yang tidak tepat | Ambang skor konservatif (MATCH-03, MATCH-11), skor dan alasan terlihat (MATCH-10), antrean verifikasi, penghapusan cepat oleh admin, aturan tidak dibuat ulang (MATCH-06) |
| R2 | OCR gagal pada scan buram | Dokumen tidak ditemukan lewat pencarian isi | Status `perlu_review_manual` dan metadata manual (BD-08) |
| R3 | Akun dummy super admin tidak diganti | Pembobolan akun tertinggi | Paksa ganti password (AUTH-03), kredensial dari environment (AUTH-04) |
| R4 | Tautan bukti berubah atau dokumen dipindah di sumbernya | Tautan rusak | Keterangan jelas, fitur validasi tautan berkala (S) |
| R5 | Data hilang karena penghapusan keliru | Relasi dan dokumen hilang | Soft delete, audit log, backup terjadwal |
| R6 | Nama item bukti generik atau mirip antar indikator | Banyak match palsu atau match ganda | Ambang skor, bukti pasti lewat kode atau nomor, verifikasi admin |
| R7 | Format file LK berubah dari tahun ke tahun, atau LK baru memakai tata letak berbeda | Impor gagal atau salah baca | Profil Impor yang dapat dikonfigurasi, pratinjau wajib sebelum simpan (LKIMP-04) |
| R8 | File Excel berbahaya atau berisi tautan berbahaya | Eksploitasi saat impor atau klik tautan | Validasi file, hanya baca nilai, tautan http/https saja (LKIMP-10, LK-09, MON-10) |
| R9 | Pengguna nonaktif masih bisa masuk karena SSO tetap valid | Pegawai yang sudah pindah mengakses data | Status aktif dicek di sisi server pada setiap permintaan (AUTH-07) |
| R10 | Auto-provision memberi akses ke pengguna SSO yang bukan pegawai kantor ini | Akses data oleh pihak tak berwenang | Konfirmasi A11, pembatasan oleh jaringan VPN, opsi daftar putih |

## 13. Riwayat Revisi

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 2026-10-29 | Draft awal, menunggu konfirmasi |
| 1.1 | 2026-10-29 | Menerapkan keputusan D1 sampai D8. Menambah temuan tiga sampel LK (bagian 1.1), istilah Item Bukti LK, Tautan Bukti, dan Profil Impor, grup kebutuhan LKIMP, tautan bukti per indikator, periode wajib. AUTH ditulis ulang untuk SSO dan nonaktif (AUTH-01 sampai AUTH-11, ID lama tidak berlaku). MATCH-02/03, MON-02/03, AC-08/10 diubah karena hasil `auto` langsung tampil. Matriks hak akses diperluas. Menambah AC-18 sampai AC-27, NFR-15/16, risiko R7 sampai R10. Bagian 11 dipecah menjadi keputusan, asumsi, dan pertanyaan terbuka |
