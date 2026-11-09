@php
    $data = $this->getViewData();
    $user = $data['user'];
    $roleLabel = $data['roleLabel'];
    $totalDocs = $data['totalDocs'];
    $totalLks = $data['totalLks'];
    $totalMatches = $data['totalMatches'];
    $totalIndicatorsWithDrive = $data['totalIndicatorsWithDrive'];
@endphp

<div class="space-y-6">
    {{-- Banner Selamat Datang & Ringkasan Cepat --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 p-6 text-white shadow-md border border-sky-800/40">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-sky-500/20 text-sky-300 border border-sky-400/30">
                        BPS Kabupaten Tegal • ELKA
                    </span>
                    <span class="text-xs text-slate-300">
                        Peran Anda: <strong class="text-white">{{ $roleLabel }}</strong>
                    </span>
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight mt-2 text-white sm:text-3xl">
                    Selamat Datang, {{ $user?->name ?? 'Pegawai' }}!
                </h1>
                <p class="text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Portal terpadu untuk memetakan dan menyinkronkan berkas Bukti Dukung (SK, SOP, Laporan) ke seluruh Lembar Kerja (LK) internal BPS dan eksternal secara otomatis.
                </p>
            </div>

            {{-- Statistik Cepat --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10 text-center">
                    <span class="block text-[11px] font-semibold text-slate-300">Dokumen</span>
                    <span class="text-xl font-black text-white">{{ $totalDocs }}</span>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10 text-center">
                    <span class="block text-[11px] font-semibold text-slate-300">Master LK</span>
                    <span class="text-xl font-black text-white">{{ $totalLks }}</span>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10 text-center">
                    <span class="block text-[11px] font-semibold text-sky-300">Kecocokan</span>
                    <span class="text-xl font-black text-sky-200">{{ $totalMatches }}</span>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10 text-center">
                    <span class="block text-[11px] font-semibold text-emerald-300">Link Drive</span>
                    <span class="text-xl font-black text-emerald-200">{{ $totalIndicatorsWithDrive }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tiga Langkah Kerja Mudah (Alur Kerja Nyata) --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300 text-xs font-black">
                    i
                </span>
                Alur Praktis Penggunaan ELKA (3 Langkah Sederhana)
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">Pilih salah satu langkah di bawah untuk langsung mulai</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- LANGKAH 1 --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-slate-200 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:border-sky-400 dark:hover:border-sky-500 transition-all group">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/60 px-2.5 py-1 rounded-md border border-sky-200 dark:border-sky-800">
                            Langkah 1
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-900/50 flex items-center justify-center text-sky-700 dark:text-sky-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-3 group-hover:text-sky-600 transition">
                        Unggah Bukti Dukung
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                        Unggah berkas SK, SOP, atau Laporan Kinerja (PDF, DOCX, atau Foto). Sistem otomatis membaca isi teks dokumen dan mendeteksi berkas ganda.
                    </p>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-gray-700/60">
                    @if(in_array($user?->role, ['admin', 'pegawai'], true))
                        <a href="{{ url('/admin/evidence-documents/create') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-sm transition">
                            <span>+ Unggah Dokumen Baru</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    @else
                        <a href="{{ url('/admin/evidence-documents') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-slate-800 dark:text-slate-200 font-bold text-xs transition">
                            <span>Lihat Daftar Dokumen</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- LANGKAH 2 --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-slate-200 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:border-amber-400 dark:hover:border-amber-500 transition-all group">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 rounded-md border border-amber-200 dark:border-amber-800">
                            Langkah 2
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-3 group-hover:text-amber-600 transition">
                        Pencocokan Cerdas &amp; Verifikasi
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                        Mesin pintar ELKA membaca nomor SK dan kemiripan uraian teks. Jika skor &ge; {{ number_format(\App\Models\SystemSetting::get('matching.similarity_threshold', 75.0), 0) }}%, dokumen langsung terpetakan dan siap diverifikasi oleh tim penilai.
                    </p>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-gray-700/60">
                    @if(in_array($user?->role, ['super_admin', 'admin'], true))
                        <a href="{{ url('/admin/verification-queue') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition">
                            <span>Buka Antrean Verifikasi</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    @else
                        <a href="{{ url('/admin/evidence-documents') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-slate-800 dark:text-slate-200 font-bold text-xs transition">
                            <span>Cari Dokumen Terdaftar</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- LANGKAH 3 --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-slate-200 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:border-emerald-400 dark:hover:border-emerald-500 transition-all group">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-md border border-emerald-200 dark:border-emerald-800">
                            Langkah 3
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-3 group-hover:text-emerald-600 transition">
                        Pantau Matriks &amp; Buka Google Drive
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                        Lihat pemenuhan bukti di Lembar Kerja ZI, SAKIP, KIP, PEKPPP, dll. Klik tanda centang (✔) untuk membuka berkas resmi langsung di Google Drive.
                    </p>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-gray-700/60">
                    <a href="{{ url('/admin/monitor-matrix') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                        <span>Buka Matriks Monitor ↗</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

