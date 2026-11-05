<x-filament-panels::page>
    @php
        $matrix = $this->getViewData();
        $stats = $this->getStats();
        $internalLks = $matrix['columns']['internal'];
        $eksternalLks = $matrix['columns']['eksternal'];
        $allLks = $internalLks->merge($eksternalLks);
        $rows = $matrix['rows'];
        $paginator = $matrix['paginator'];
        $totalItems = $paginator->total();
        $allLksCount = $allLks->count();
        $hasActiveFilter = !empty($this->search) || !empty($this->category) || !empty($this->document_type) || ($this->statusFilter !== 'all');
    @endphp

    <div class="space-y-5" x-data @keydown.escape.window="$wire.closeDrilldown(); $wire.closeIndicatorDrilldown()">
        {{-- Modern Executive Header BPS RI --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                        Badan Pusat Statistik RI
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-mono">
                        {{ $period }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                    Matriks Pemetaan Bukti Dukung
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Pemantauan otomatis pemenuhan dokumen eviden digital pada {{ $allLksCount }} Lembar Kerja evaluasi kinerja BPS.
                </p>
            </div>

            {{-- Action Toolbar --}}
            <div class="flex items-center gap-2 self-start lg:self-center">
                {{-- Toggle Simulasi Sandbox --}}
                <button
                    type="button"
                    wire:click="toggleTestingPanel"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition border {{ $showTestingPanel ? 'bg-sky-50 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border-sky-300 dark:border-sky-700 shadow-2xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                    title="Buka atau tutup simulator uji cepat"
                >
                    <svg class="w-4 h-4 {{ $showTestingPanel ? 'text-sky-600 dark:text-sky-400' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span>{{ $showTestingPanel ? 'Tutup Simulator' : 'Mode Uji Cepat' }}</span>
                    <span class="w-1.5 h-1.5 rounded-full {{ $showTestingPanel ? 'bg-sky-500 animate-pulse' : 'bg-slate-400' }}"></span>
                </button>

                {{-- Ekspor CSV --}}
                <button
                    type="button"
                    wire:click="exportMatrix"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition shadow-2xs"
                    title="Unduh seluruh data matriks ke file CSV"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Ekspor CSV</span>
                </button>

                {{-- Unggah Bukti Baru (Primary) --}}
                <a
                    href="{{ url('/admin/evidence-documents/create') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white transition shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Unggah Bukti</span>
                </a>
            </div>
        </div>

        {{-- SIMULATOR & PENGUJIAN CEPAT EVIDEN (CLEAN EXECUTIVE SANDBOX) --}}
        @if($showTestingPanel)
            <div class="rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-900/60 p-4 transition-all space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Simulasi Ekstraksi &amp; Matching Instan
                            </span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded font-medium bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                Siap Eksekusi
                            </span>
                        </div>
                    </div>

                    {{-- Clean micro-links for sample files --}}
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-medium text-slate-500">Unduh Sampel:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <a href="/sample-documents/SK_Tim_Pembangunan_Zona_Integritas_2026.pdf" download class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 hover:border-sky-300 transition">
                                <span>SK ZI</span>
                            </a>
                            <a href="/sample-documents/Standar_Pelayanan_Publik_BPS_2026.pdf" download class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 hover:border-sky-300 transition">
                                <span>Standar Pelayanan</span>
                            </a>
                            <a href="/sample-documents/Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf" download class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 hover:border-sky-300 transition">
                                <span>LKjIP</span>
                            </a>
                            <a href="/sample-documents/Daftar_Informasi_Publik_DIP_PPID_2026.docx" download class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 hover:border-sky-300 transition">
                                <span>DIP PPID</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 4 Sleek Interactive Tiles --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    {{-- Tile 1: SK ZI --}}
                    <button
                        type="button"
                        wire:click="runSampleTest('zi')"
                        wire:loading.attr="disabled"
                        class="group relative p-3 rounded-lg border border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-sky-500 dark:hover:border-sky-400 hover:shadow-xs transition-all text-left flex flex-col justify-between cursor-pointer disabled:opacity-50"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                    SK
                                </span>
                                <span wire:loading.remove wire:target="runSampleTest('zi')" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 group-hover:underline flex items-center gap-0.5">
                                    <span>Uji 1-Klik</span>
                                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <span wire:loading wire:target="runSampleTest('zi')" class="text-[11px] text-sky-600 dark:text-sky-400 font-bold animate-pulse flex items-center gap-1">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memproses...
                                </span>
                            </div>
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                SK Tim Pembangunan ZI
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Target: LK ZI (ZI-1.1)
                            </div>
                        </div>
                    </button>

                    {{-- Tile 2: PEKPPP --}}
                    <button
                        type="button"
                        wire:click="runSampleTest('pekppp')"
                        wire:loading.attr="disabled"
                        class="group relative p-3 rounded-lg border border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-sky-500 dark:hover:border-sky-400 hover:shadow-xs transition-all text-left flex flex-col justify-between cursor-pointer disabled:opacity-50"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    SOP
                                </span>
                                <span wire:loading.remove wire:target="runSampleTest('pekppp')" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 group-hover:underline flex items-center gap-0.5">
                                    <span>Uji 1-Klik</span>
                                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <span wire:loading wire:target="runSampleTest('pekppp')" class="text-[11px] text-sky-600 dark:text-sky-400 font-bold animate-pulse flex items-center gap-1">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memproses...
                                </span>
                            </div>
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                Standar Pelayanan PST
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Target: LK PEKPPP (PEKPPP-01)
                            </div>
                        </div>
                    </button>

                    {{-- Tile 3: SAKIP --}}
                    <button
                        type="button"
                        wire:click="runSampleTest('sakip')"
                        wire:loading.attr="disabled"
                        class="group relative p-3 rounded-lg border border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-sky-500 dark:hover:border-sky-400 hover:shadow-xs transition-all text-left flex flex-col justify-between cursor-pointer disabled:opacity-50"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    Laporan
                                </span>
                                <span wire:loading.remove wire:target="runSampleTest('sakip')" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 group-hover:underline flex items-center gap-0.5">
                                    <span>Uji 1-Klik</span>
                                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <span wire:loading wire:target="runSampleTest('sakip')" class="text-[11px] text-sky-600 dark:text-sky-400 font-bold animate-pulse flex items-center gap-1">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memproses...
                                </span>
                            </div>
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                Laporan Kinerja LKjIP
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Target: LK SAKIP (SAKIP-01)
                            </div>
                        </div>
                    </button>

                    {{-- Tile 4: KIP --}}
                    <button
                        type="button"
                        wire:click="runSampleTest('kip')"
                        wire:loading.attr="disabled"
                        class="group relative p-3 rounded-lg border border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-sky-500 dark:hover:border-sky-400 hover:shadow-xs transition-all text-left flex flex-col justify-between cursor-pointer disabled:opacity-50"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    Data (DOCX)
                                </span>
                                <span wire:loading.remove wire:target="runSampleTest('kip')" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 group-hover:underline flex items-center gap-0.5">
                                    <span>Uji 1-Klik</span>
                                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <span wire:loading wire:target="runSampleTest('kip')" class="text-[11px] text-sky-600 dark:text-sky-400 font-bold animate-pulse flex items-center gap-1">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memproses...
                                </span>
                            </div>
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                Daftar Informasi Publik PPID
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Target: LK KIP (KIP-01)
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        @endif

        {{-- 4 INTERACTIVE KPI METRIC CARDS (STRIPE/LINEAR GRADE) --}}
        <div class="space-y-2.5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                {{-- Card 1: Total Indikator LK --}}
                <div 
                    wire:click="setStatusFilter('all')" 
                    class="gov-metric-card cursor-pointer {{ $statusFilter === 'all' ? 'is-active' : '' }}"
                    title="Klik untuk menampilkan seluruh butir indikator"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Total Indikator LK
                        </span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            {{ $allLksCount }} LK
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-900 dark:text-slate-100 tracking-tight">
                            {{ $stats['total_indicators'] }}
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                            Instrumen evaluasi terdaftar
                        </p>
                    </div>
                </div>

                {{-- Card 2: Bukti Terpenuhi (✔) --}}
                <div 
                    wire:click="setStatusFilter('fulfilled')" 
                    class="gov-metric-card cursor-pointer {{ $statusFilter === 'fulfilled' ? 'is-active-emerald' : '' }}"
                    title="Klik untuk hanya menampilkan butir yang sudah ada tanda centang (✔)"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Bukti Terpenuhi (✔)
                        </span>
                        <span class="text-[11px] font-extrabold px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-mono">
                            {{ $stats['fulfilled_percentage'] }}%
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-2xl sm:text-3xl font-extrabold font-sans text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ $stats['fulfilled_indicators'] }}
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                            Eviden terpetakan &amp; terverifikasi
                        </p>
                    </div>
                </div>

                {{-- Card 3: Belum Ada Bukti --}}
                <div 
                    wire:click="setStatusFilter('unfulfilled')" 
                    class="gov-metric-card cursor-pointer {{ $statusFilter === 'unfulfilled' ? 'is-active-amber' : '' }}"
                    title="Klik untuk menyaring butir yang masih kosong / belum ada bukti"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Belum Ada Bukti
                        </span>
                        <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                            {{ $stats['unfulfilled_percentage'] }}%
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-800 dark:text-slate-200 tracking-tight">
                            {{ $stats['unfulfilled_indicators'] }}
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                            Menunggu pemenuhan dokumen
                        </p>
                    </div>
                </div>

                {{-- Card 4: Perlu Verifikasi Admin --}}
                <div class="gov-metric-card">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Perlu Verifikasi Admin
                        </span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            Antrean
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-900 dark:text-slate-100 tracking-tight">
                            {{ $stats['pending_verification'] }}
                        </div>
                        <div class="text-xs mt-1">
                            @if(in_array(auth()->user()?->role, ['super_admin', 'admin'], true))
                                <a href="{{ url('/admin/verification-queue') }}" class="font-bold text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                                    <span>Tinjau antrean</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="text-slate-500 font-normal">Rekomendasi sistem</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Progress Bar Pemenuhan Eviden --}}
            <div class="w-full bg-slate-200/80 dark:bg-slate-700/60 h-1.5 rounded-full overflow-hidden">
                <div 
                    class="bg-emerald-500 h-1.5 transition-all duration-500 rounded-full" 
                    style="width: {{ $stats['fulfilled_percentage'] }}%"
                ></div>
            </div>
        </div>

        {{-- TOOLBAR CONTROL: DUAL VIEW MODE, CATEGORY, DAN FILTER --}}
        <div class="bg-white dark:bg-slate-800 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-3 transition-colors shadow-2xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- DUAL VIEW MODE SWITCHER (INDIKATOR VS DOKUMEN) --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tampilan:</span>
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 gap-1 text-xs font-semibold">
                        <button
                            type="button"
                            wire:click="setViewMode('indicator')"
                            class="px-3 py-1.5 rounded-md transition flex items-center gap-1.5 {{ $viewMode === 'indicator' ? 'bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 font-bold border border-slate-300 dark:border-slate-700 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Butir Indikator LK</span>
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('document')"
                            class="px-3 py-1.5 rounded-md transition flex items-center gap-1.5 {{ $viewMode === 'document' ? 'bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 font-bold border border-slate-300 dark:border-slate-700 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Berkas Dokumen</span>
                        </button>
                    </div>
                </div>

                {{-- Segmented Category Tabs Formal --}}
                <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 gap-1 text-xs font-semibold">
                    <button
                        type="button"
                        wire:click="setCategory(null)"
                        class="px-3 py-1.5 rounded-md transition {{ $category === null ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-700 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        Semua LK ({{ $allLksCount }})
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('internal')"
                        class="px-3 py-1.5 rounded-md transition {{ $category === 'internal' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-700 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        Internal ({{ $internalLks->count() }})
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('eksternal')"
                        class="px-3 py-1.5 rounded-md transition {{ $category === 'eksternal' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-700 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        Eksternal ({{ $eksternalLks->count() }})
                    </button>
                </div>
            </div>

            {{-- Filter Grid Formal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                {{-- Search Box --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Cari kode, nomor SK, atau judul..."
                        class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-sky-600 shadow-2xs"
                    />
                    @if(!empty($search))
                        <button 
                            type="button" 
                            wire:click="$set('search', '')" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            &times;
                        </button>
                    @endif
                </div>

                {{-- Filter Jenis Dokumen --}}
                <div>
                    <select
                        wire:model.live="document_type"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-sky-600 shadow-2xs">
                        <option value="">Jenis Dokumen: Semua</option>
                        <option value="SK">Surat Keputusan (SK)</option>
                        <option value="SOP">Standar Operasional (SOP)</option>
                        <option value="Laporan">Laporan Kinerja</option>
                        <option value="Surat Edaran">Surat Edaran</option>
                        <option value="Data">Data / Matriks</option>
                    </select>
                </div>

                {{-- Filter Periode Dinamis --}}
                <div>
                    <select
                        wire:model.live="period"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-sky-600 shadow-2xs">
                        @foreach($this->getAvailablePeriods() as $p)
                            <option value="{{ $p }}">
                                @if($p === '12 Bulan')
                                    Periode: 12 Bulan (Tahunan)
                                @elseif($p === '6 Bulan')
                                    Periode: 6 Bulan (Semesteran)
                                @elseif($p === '3 Bulan')
                                    Periode: 3 Bulan (Triwulanan)
                                @elseif($p === '1 Bulan')
                                    Periode: 1 Bulan (Bulanan)
                                @else
                                    Periode: TA {{ $p }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter Quick Chips --}}
                <div class="flex items-center gap-1.5">
                    <select
                        wire:model.live="statusFilter"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-sky-600 shadow-2xs">
                        <option value="all">Status: Semua Butir</option>
                        <option value="fulfilled">Hanya Bukti Terpenuhi (✔)</option>
                        <option value="unfulfilled">Hanya Belum Ada Bukti</option>
                    </select>

                    @if($hasActiveFilter)
                        <button
                            type="button"
                            wire:click="$set('search', ''); $set('category', null); $set('document_type', null); $set('statusFilter', 'all')"
                            class="px-2.5 py-2 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 whitespace-nowrap shadow-2xs"
                            title="Reset semua filter"
                        >
                            Reset
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- MATRIKS TABEL UTAMA (ZERO AI SLOP & SENIOR GOVERNMENT GRADE) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors shadow-2xs">
            <div class="overflow-x-auto relative">
                <table class="matrix-table">
                    <thead>
                        {{-- Super Header Kategori LK (MON-01) Formal --}}
                        <tr class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                            <th rowspan="2" class="matrix-col-code p-3 border-r border-b border-slate-300 dark:border-slate-700 text-center text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Kode
                            </th>
                            <th rowspan="2" class="matrix-col-desc p-3 border-r border-b border-slate-300 dark:border-slate-700 text-left text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                {{ $viewMode === 'indicator' ? 'Uraian Indikator & Kebutuhan Bukti' : 'Nama Dokumen Bukti & Deskripsi' }}
                            </th>
                            <th rowspan="2" class="w-32 min-w-[120px] p-3 border-r border-b border-slate-300 dark:border-slate-700 text-center text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                PIC Satker
                            </th>
                            @if($internalLks->isNotEmpty())
                                <th colspan="{{ $internalLks->count() }}" class="p-2.5 text-center bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 border-r border-b border-slate-300 dark:border-slate-700 font-bold text-xs uppercase tracking-wider">
                                    Lembar Kerja Internal BPS RI
                                </th>
                            @endif
                            @if($eksternalLks->isNotEmpty())
                                <th colspan="{{ $eksternalLks->count() }}" class="p-2.5 text-center bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 border-r border-b border-slate-300 dark:border-slate-700 font-bold text-xs uppercase tracking-wider">
                                    Lembar Kerja Eksternal
                                </th>
                            @endif
                        </tr>

                        {{-- Sub Header Kolom Kode LK Formal --}}
                        <tr class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-center">
                            @foreach($internalLks as $lk)
                                <th class="p-2.5 min-w-[110px] border-r border-b border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold" title="{{ $lk->name }}">
                                    <span class="block text-xs font-mono font-bold">{{ $lk->code }}</span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal truncate max-w-[100px] mx-auto">{{ $lk->name }}</span>
                                </th>
                            @endforeach

                            @foreach($eksternalLks as $lk)
                                <th class="p-2.5 min-w-[110px] border-r border-b border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold" title="{{ $lk->name }}">
                                    <span class="block text-xs font-mono font-bold">{{ $lk->code }}</span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal truncate max-w-[100px] mx-auto">{{ $lk->name }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750 transition duration-75">
                                {{-- Kolom 1: Kode --}}
                                <td class="matrix-col-code p-3 border-r border-b border-slate-200 dark:border-slate-700 text-center font-bold text-slate-900 dark:text-slate-100 font-mono text-xs sm:text-sm">
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                                        {{ $row['code'] ?? 'DOK' }}
                                    </span>
                                </td>

                                {{-- Kolom 2: Uraian & Kebutuhan Bukti --}}
                                <td class="matrix-col-desc p-3.5 border-r border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-start gap-2">
                                            <span class="matrix-doc-title text-slate-900 dark:text-slate-100 font-bold text-sm leading-snug">
                                                {{ $row['document_name'] }}
                                            </span>
                                            @if(!empty($row['document_type']))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 flex-shrink-0">
                                                    {{ $row['document_type'] }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Tampilan Ringkasan Bukti yang Diharapkan / Terpetakan --}}
                                        @if(($row['row_type'] ?? '') === 'indicator')
                                            @if(!empty($row['evidence_items']))
                                                <div class="text-[11px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-200 dark:border-slate-700/80 space-y-0.5">
                                                    <span class="font-bold text-slate-700 dark:text-slate-300 block">Kebutuhan Dokumen Bukti:</span>
                                                    <ul class="list-disc list-inside space-y-0.5 text-slate-600 dark:text-slate-400">
                                                        @foreach(array_slice($row['evidence_items'], 0, 2) as $item)
                                                            <li class="truncate">{{ $item }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if(!empty($row['matched_doc_name']))
                                                <div class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                                                    <span>✔ Terpetakan ke:</span>
                                                    <span class="underline decoration-emerald-400 truncate max-w-[260px]">{{ $row['matched_doc_name'] }}</span>
                                                </div>
                                            @else
                                                <div class="text-[11px] text-slate-400 dark:text-slate-500 italic">
                                                    Belum ada berkas bukti yang terpetakan
                                                </div>
                                            @endif
                                        @else
                                            @if(!empty($row['indicators_summary']))
                                                <p class="matrix-doc-desc text-slate-600 dark:text-slate-400 line-clamp-2 text-xs">
                                                    {{ $row['indicators_summary'] }}
                                                </p>
                                            @endif

                                            @if(!empty($row['document_number']))
                                                <span class="text-[11px] font-mono font-medium text-slate-500 dark:text-slate-400">
                                                    No Dokumen: {{ $row['document_number'] }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- Kolom 3: PIC Satker Formal --}}
                                <td class="p-3 border-r border-b border-slate-200 dark:border-slate-700 text-center whitespace-nowrap align-middle">
                                    <span class="inline-block px-2.5 py-1 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-2xs">
                                        {{ $row['pic'] ?? 'Subbagian Umum' }}
                                    </span>
                                </td>

                                {{-- Kolom Lembar Kerja Internal --}}
                                @foreach($internalLks as $lk)
                                    @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null, 'is_target_lk' => true]; @endphp
                                    <td class="p-2.5 text-center border-r border-b border-slate-200 dark:border-slate-700 align-middle">
                                        @if($cell['has_match'])
                                            @if(!empty($cell['drive_url']))
                                                <a 
                                                    href="{{ $cell['drive_url'] }}" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    class="gov-matrix-check-btn"
                                                    aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                    title="Bukti Terpetakan: Klik untuk buka Google Drive ({{ $lk->code }})">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <span class="text-[11px] font-bold">Drive</span>
                                                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            @else
                                                <button 
                                                    type="button"
                                                    wire:click="{{ ($row['row_type'] ?? '') === 'indicator' ? 'openIndicatorDrilldown(\'' . ($row['indicator_id'] ?? '') . '\')' : 'openDrilldown(\'' . ($row['document_id'] ?? '') . '\', \'' . ($cell['lk_id'] ?? '') . '\')' }}"
                                                    class="gov-matrix-check-btn cursor-pointer"
                                                    aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                    title="Klik untuk melihat rincian bukti cocok">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <span class="text-[11px] font-bold">Rincian</span>
                                                </button>
                                            @endif
                                        @else
                                            @if(($row['row_type'] ?? '') === 'indicator' && ($cell['is_target_lk'] ?? false))
                                                <button
                                                    type="button"
                                                    wire:click="openIndicatorDrilldown('{{ $row['indicator_id'] }}')"
                                                    class="px-2 py-1 text-[11px] text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 font-semibold rounded hover:bg-slate-100 dark:hover:bg-slate-700/60 transition"
                                                    title="Belum ada bukti • Klik untuk melihat kebutuhan dokumen"
                                                >
                                                    <span class="text-xs">+ Butuh Bukti</span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach

                                {{-- Kolom Lembar Kerja Eksternal --}}
                                @foreach($eksternalLks as $lk)
                                    @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null, 'is_target_lk' => true]; @endphp
                                    <td class="p-2.5 text-center border-r border-b border-slate-200 dark:border-slate-700 align-middle">
                                        @if($cell['has_match'])
                                            @if(!empty($cell['drive_url']))
                                                <a 
                                                    href="{{ $cell['drive_url'] }}" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    class="gov-matrix-check-btn"
                                                    aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                    title="Bukti Terpetakan: Klik untuk buka Google Drive ({{ $lk->code }})">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <span class="text-[11px] font-bold">Drive</span>
                                                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            @else
                                                <button 
                                                    type="button"
                                                    wire:click="{{ ($row['row_type'] ?? '') === 'indicator' ? 'openIndicatorDrilldown(\'' . ($row['indicator_id'] ?? '') . '\')' : 'openDrilldown(\'' . ($row['document_id'] ?? '') . '\', \'' . ($cell['lk_id'] ?? '') . '\')' }}"
                                                    class="gov-matrix-check-btn cursor-pointer"
                                                    aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                    title="Klik untuk melihat rincian bukti cocok">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <span class="text-[11px] font-bold">Rincian</span>
                                                </button>
                                            @endif
                                        @else
                                            @if(($row['row_type'] ?? '') === 'indicator' && ($cell['is_target_lk'] ?? false))
                                                <button
                                                    type="button"
                                                    wire:click="openIndicatorDrilldown('{{ $row['indicator_id'] }}')"
                                                    class="px-2 py-1 text-[11px] text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 font-semibold rounded hover:bg-slate-100 dark:hover:bg-slate-700/60 transition"
                                                    title="Belum ada bukti • Klik untuk melihat kebutuhan dokumen"
                                                >
                                                    <span class="text-xs">+ Butuh Bukti</span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + $internalLks->count() + $eksternalLks->count() }}" class="p-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2 max-w-md mx-auto">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                            Tidak Ada Data yang Sesuai Filter
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed">
                                            Tidak ditemukan butir pada periode <strong>{{ $period }}</strong> dengan filter saat ini. Coba pilih periode lain atau tekan tombol reset filter di bawah.
                                        </p>
                                        @if($hasActiveFilter)
                                            <button
                                                type="button"
                                                wire:click="$set('search', ''); $set('category', null); $set('document_type', null); $set('statusFilter', 'all')"
                                                class="mt-2 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-sky-600 hover:bg-sky-700 text-white transition shadow-2xs"
                                            >
                                                Reset Semua Filter
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer Pagination Formal --}}
            <div class="p-3.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <strong class="text-slate-900 dark:text-slate-100">{{ $paginator->firstItem() ?? 0 }}</strong> s.d. <strong class="text-slate-900 dark:text-slate-100">{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-900 dark:text-slate-100">{{ $totalItems }}</strong> {{ $viewMode === 'indicator' ? 'Indikator' : 'Dokumen' }}
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label for="select-perpage" class="text-slate-500 dark:text-slate-400 font-medium">Baris per halaman:</label>
                        <select
                            id="select-perpage"
                            wire:model.live="perPage"
                            class="py-1 pl-2.5 pr-8 text-xs font-bold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 min-w-[4.5rem] cursor-pointer focus:outline-none focus:border-sky-600 shadow-2xs">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-center md:self-auto whitespace-nowrap">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($paginator->onFirstPage())
                        class="px-3 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Halaman Sebelumnya"
                        aria-label="Sebelumnya"
                    >
                        &lsaquo; Sebelumnya
                    </button>

                    <span class="px-2 py-1 text-xs font-semibold text-slate-600 dark:text-slate-400">
                        Halaman <strong class="text-slate-900 dark:text-slate-100">{{ $paginator->currentPage() }}</strong> dari <strong class="text-slate-900 dark:text-slate-100">{{ max(1, $paginator->lastPage()) }}</strong>
                    </span>

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(!$paginator->hasMorePages())
                        class="px-3 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Halaman Selanjutnya"
                        aria-label="Selanjutnya"
                    >
                        Berikutnya &rsaquo;
                    </button>
                </div>
            </div>
        </div>

        {{-- Legenda Matriks Formal --}}
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 pt-1 px-1">
            <div class="flex flex-wrap items-center gap-4">
                <span class="font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">Keterangan Matriks:</span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 font-extrabold text-xs">✔ Terpetakan</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">Eviden Terhubung &amp; Langsung Buka Drive</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">Sel Kosong = Belum Ada Bukti</span>
                </div>
            </div>

            <div>
                <span class="font-medium text-slate-400 dark:text-slate-500">ELKA &bull; Badan Pusat Statistik RI</span>
            </div>
        </div>

        {{-- MODAL DRILLDOWN 1: DETAIL DOKUMEN BUKTI DUKUNG --}}
        @if($showDrilldown && $drilldownData)
            <div 
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                wire:click.self="closeDrilldown"
                role="dialog"
                aria-modal="true"
            >
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200 dark:border-slate-700 transition-colors">
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-600">
                                {{ $drilldownData['lk']['code'] }} &bull; Periode {{ $drilldownData['lk']['period'] }}
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $drilldownData['document']['name'] }}
                            </h3>
                            @if(!empty($drilldownData['document']['number']))
                                <p class="text-xs text-slate-600 dark:text-slate-400 font-mono mt-0.5">
                                    Nomor SK: {{ $drilldownData['document']['number'] }}
                                </p>
                            @endif
                        </div>
                        <button 
                            type="button"
                            wire:click="closeDrilldown" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                            aria-label="Tutup modal rincian"
                        >
                            &times;
                        </button>
                    </div>

                    {{-- Daftar Indikator Cocok --}}
                    <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Indikator Penilaian yang Cocok:
                        </p>

                        @foreach($drilldownData['indicators'] as $ind)
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-sm text-slate-900 dark:text-slate-100">
                                        <span class="text-sky-600 dark:text-sky-400 font-mono">[{{ $ind['code'] }}]</span> {{ $ind['title'] }}
                                    </span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded {{ $ind['status'] === 'confirmed' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-600' }}">
                                        {{ $ind['status_label'] }} ({{ $ind['confidence_score'] }}%)
                                    </span>
                                </div>

                                @if(!empty($ind['matched_item_name']))
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        <strong class="text-slate-700 dark:text-slate-300">Butir Bukti:</strong> {{ $ind['matched_item_name'] }}
                                    </p>
                                @endif

                                {{-- Tautan Bukti (MON-05, MON-06, MON-10, AC-12) --}}
                                <div class="pt-2.5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                                    <div>
                                        @if($ind['has_valid_link'])
                                            <a 
                                                href="{{ $ind['evidence_link'] }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold transition shadow-2xs"
                                            >
                                                <span>Buka Tautan Google Drive</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-slate-400 dark:text-slate-500 italic">
                                                {{ $ind['link_placeholder'] }}
                                            </span>
                                        @endif
                                    </div>

                                    @if(in_array(Auth::user()?->role, ['super_admin', 'admin'], true))
                                        <button 
                                            type="button"
                                            wire:click="removeMatch('{{ $drilldownData['document']['id'] }}', '{{ $ind['indicator_id'] }}')"
                                            wire:confirm="Yakin ingin mencabut relasi kecocokan ini? Tindakan ini akan menolak match secara permanen."
                                            class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 font-bold text-xs cursor-pointer underline"
                                        >
                                            Cabut Relasi
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <button 
                            type="button"
                            wire:click="closeDrilldown" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL DRILLDOWN 2: DETAIL INDIKATOR & PANDUAN KEBUTUHAN BUKTI --}}
        @if($showIndicatorDrilldown && $indicatorDrilldownData)
            <div 
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                wire:click.self="closeIndicatorDrilldown"
                role="dialog"
                aria-modal="true"
            >
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-700 transition-colors">
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                                    {{ $indicatorDrilldownData['lk']['code'] ?? 'LK' }} &bull; Periode {{ $indicatorDrilldownData['lk']['period'] ?? $period }}
                                </span>
                                <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-400">
                                    Kode: {{ $indicatorDrilldownData['indicator']['code'] }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-1.5 leading-snug">
                                {{ $indicatorDrilldownData['indicator']['title'] }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                PIC Satker: <strong class="text-slate-700 dark:text-slate-300">{{ $indicatorDrilldownData['indicator']['pic'] }}</strong>
                            </p>
                        </div>
                        <button 
                            type="button"
                            wire:click="closeIndicatorDrilldown" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                            aria-label="Tutup rincian indikator"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                        {{-- Kebutuhan Dokumen Bukti yang Diharapkan --}}
                        <div class="space-y-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 block">
                                Dokumen Bukti yang Diharapkan:
                            </span>
                            @if(!empty($indicatorDrilldownData['indicator']['items']))
                                <div class="bg-slate-50 dark:bg-slate-900/70 p-3 rounded-xl border border-slate-200 dark:border-slate-700 space-y-1.5">
                                    @foreach($indicatorDrilldownData['indicator']['items'] as $item)
                                        <div class="flex items-start gap-2 text-xs text-slate-800 dark:text-slate-200">
                                            <span class="text-sky-600 dark:text-sky-400 font-bold">&bull;</span>
                                            <span>{{ $item }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500 italic">Belum ada item bukti template khusus untuk indikator ini.</p>
                            @endif
                        </div>

                        {{-- Status Pemenuhan Saat Ini --}}
                        <div class="space-y-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 block">
                                Status Pemenuhan Dokumen:
                            </span>
                            @if(!empty($indicatorDrilldownData['matched_document']))
                                <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/70 dark:bg-emerald-950/40 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Sudah Terpenuhi (✔)</span>
                                        </span>
                                        <span class="text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                            Skor: {{ $indicatorDrilldownData['matched_document']['match_score'] }}%
                                        </span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-slate-100">
                                        {{ $indicatorDrilldownData['matched_document']['name'] }}
                                    </div>
                                    @if(!empty($indicatorDrilldownData['matched_document']['number']))
                                        <div class="text-[11px] font-mono text-slate-600 dark:text-slate-400">
                                            Nomor: {{ $indicatorDrilldownData['matched_document']['number'] }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/30 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between">
                                    <span>Belum ada dokumen bukti yang terpetakan ke indikator ini.</span>
                                    <a href="{{ url('/admin/evidence-documents/create') }}" class="font-bold underline hover:text-amber-900 dark:hover:text-amber-200">
                                        Unggah Bukti &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Tautan Google Drive Resmi --}}
                        @if($indicatorDrilldownData['indicator']['has_valid_link'])
                            <div class="pt-2">
                                <a 
                                    href="{{ $indicatorDrilldownData['indicator']['evidence_link'] }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition shadow-2xs"
                                >
                                    <span>Buka Folder Google Drive Resmi</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <button 
                            type="button"
                            wire:click="closeIndicatorDrilldown" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>