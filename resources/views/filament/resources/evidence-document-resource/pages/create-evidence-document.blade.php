<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Navigation & Title --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                    <a href="/admin/monitor-matrix" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Beranda</a>
                    <span>/</span>
                    <a href="{{ $this->getResource()::getUrl('index') }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Bukti Dukung</a>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Unggah Berkas Baru</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                        Unggah Dokumen Bukti Dukung
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        TA {{ date('Y') }}
                    </span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 max-w-3xl">
                    Unggah berkas resmi instansi (SK, SOP, Laporan, Data). Berkas otomatis diekstraksi teks/OCR dan dipetakan ke indikator lembar kerja (LK) secara idempoten.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 self-start lg:self-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 rounded-xl shadow-2xs">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                <span>SHA-256 &amp; Ekstraksi Teks Siap</span>
            </div>
        </div>

        {{-- BANNER SAMPEL DOKUMEN UJI --}}
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-sky-50 via-slate-50 to-indigo-50 dark:from-slate-800/90 dark:via-slate-800/80 dark:to-slate-800/90 border border-sky-200/80 dark:border-sky-900/60 shadow-2xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                        📁
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                            Berkas Sampel Siap Uji (Langsung Unduh &amp; Unggah)
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Klik tombol di bawah untuk mengunduh dokumen contoh resmi BPS, lalu pilih berkas tersebut pada formulir unggah di bawah ini:
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 self-start md:self-auto">
                    4 Berkas Contoh Tersedia
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-1">
                <a href="/sample-documents/SK_Tim_Pembangunan_Zona_Integritas_2026.pdf" download class="flex items-center gap-2 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-xs transition group">
                    <span class="text-rose-500 font-bold text-xs bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">PDF</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-600 truncate">SK Tim ZI 2026</div>
                        <div class="text-[10px] text-slate-500 truncate">Target: LK ZI (ZI-1.1)</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>

                <a href="/sample-documents/Standar_Pelayanan_Publik_BPS_2026.pdf" download class="flex items-center gap-2 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-xs transition group">
                    <span class="text-rose-500 font-bold text-xs bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">PDF</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-600 truncate">Standar Pelayanan PST</div>
                        <div class="text-[10px] text-slate-500 truncate">Target: LK PEKPPP</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>

                <a href="/sample-documents/Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf" download class="flex items-center gap-2 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-xs transition group">
                    <span class="text-rose-500 font-bold text-xs bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">PDF</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-600 truncate">Laporan LKjIP 2026</div>
                        <div class="text-[10px] text-slate-500 truncate">Target: LK SAKIP</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>

                <a href="/sample-documents/Daftar_Informasi_Publik_DIP_PPID_2026.docx" download class="flex items-center gap-2 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-xs transition group">
                    <span class="text-blue-500 font-bold text-xs bg-blue-50 dark:bg-blue-950/60 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-900">DOCX</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-600 truncate">Daftar Informasi DIP</div>
                        <div class="text-[10px] text-slate-500 truncate">Target: LK KIP</div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
            </div>
        </div>

        {{-- 2-Column Responsive Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Kolom Kiri: Form Upload & Metadata (65%) --}}
            <div class="lg:col-span-7 xl:col-span-8">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                    <x-filament-panels::form wire:submit="create">
                        {{ $this->form }}

                        <div class="flex items-center gap-3 pt-6 border-t border-slate-200 dark:border-slate-700 mt-6">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 min-h-[44px] rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs sm:text-sm shadow-xs transition hover:scale-[1.01] active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-sky-500 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Simpan &amp; Jalankan Matching</span>
                            </button>

                            <a
                                href="{{ $this->getResource()::getUrl('index') }}"
                                class="inline-flex items-center justify-center px-4 py-2.5 min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs sm:text-sm font-bold transition shadow-2xs cursor-pointer">
                                Batal
                            </a>
                        </div>
                    </x-filament-panels::form>
                </div>
            </div>

            {{-- Kolom Kanan: Status Pipeline & Riwayat (35%) Formal --}}
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                {{-- Card 1: Status Pipeline Pemrosesan Otomatis --}}
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                    <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Pipeline Pemrosesan Berkas
                            </h3>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            Aktif
                        </span>
                    </div>

                    {{-- Stepper List Formal --}}
                    <div class="space-y-3.5">
                        {{-- Step 1 --}}
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-emerald-700 dark:text-emerald-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                ✔
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Validasi File &amp; Sniffing MIME</span>
                                    <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">Tahap 1</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    MIME application/pdf, docx, jpeg, png divalidasi berbasis magic bytes.
                                </p>
                            </div>
                        </div>

                        {{-- Step 2 --}}
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-emerald-700 dark:text-emerald-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                ✔
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Kalkulasi SHA-256 Hash</span>
                                    <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">Tahap 2</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Deduplikasi identik untuk menjamin integritas data (Aturan BD-05).
                                </p>
                            </div>
                        </div>

                        {{-- Step 3 --}}
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-sky-700 dark:text-sky-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                3
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Ekstraksi Teks / OCR</span>
                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Otomatis</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Parser digital cepat atau OCR Tesseract bahasa Indonesia untuk pindaian.
                                </p>
                            </div>
                        </div>

                        {{-- Step 4 --}}
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                4
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Pencocokan Heuristik (Trigram)</span>
                                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Antrean Queue</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Memetakan ke butir LK ZI, PEKPPP, KIP, dan SAKIP.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Peringatan Dokumen Duplikat (BD-05) --}}
                <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 rounded-xl p-4 shadow-2xs transition-colors">
                    <div class="flex items-start gap-2.5">
                        <div class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">
                                Proteksi Dokumen Duplikat (Aturan BD-05)
                            </h4>
                            <p class="text-xs text-amber-800 dark:text-amber-300/90 mt-1 leading-relaxed">
                                Sistem secara ketat menolak dokumen yang identik berdasarkan kalkulasi hash SHA-256 untuk menjaga integritas data penilaian.
                            </p>
                            <a href="{{ url('/admin/monitor-matrix') }}" class="inline-flex items-center gap-1 text-xs font-bold text-amber-900 dark:text-amber-200 hover:underline mt-2">
                                <span>Lihat dokumen yang sudah ada di Monitor</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Riwayat Unggahan Terakhir --}}
                <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Riwayat Unggahan Terakhir
                        </h3>
                        <a href="{{ $this->getResource()::getUrl('index') }}" class="text-xs font-semibold text-sky-600 dark:hover:text-sky-400 hover:underline">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($this->getRecentUploads() as $recent)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-900 transition">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate" title="{{ $recent->document_name }}">
                                            {{ $recent->document_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                            {{ $recent->created_at?->diffForHumans() ?? 'Baru saja' }} &bull; {{ number_format(($recent->file_size_bytes ?? 0) / 1024 / 1024, 1) }} MB
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ✔ {{ $recent->active_matches_count }} Match LK
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 dark:text-slate-500 text-center py-4">
                                Belum ada berkas yang diunggah.
                            </p>
                        @endforelse
                    </div>

                    <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
                            Sistem Ingesti ELKA BPS RI Terpadu
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
