<x-filament-panels::page>
    @php
        $lks = $this->getLksData();
        $selectedLk = $this->selectedLk;
        $indicators = $this->getFilteredIndicators();
    @endphp

    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                    <a href="/admin/monitor-matrix" class="hover:text-sky-600 transition">Beranda</a>
                    <span>/</span>
                    <span>Master Data</span>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Lembar Kerja (LK)</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                    Master Lembar Kerja &amp; Indikator
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-3xl">
                    Kelola 6 instrumen evaluasi LK (Internal &amp; Eksternal), pemetaan butir penilaian, perumusan token bukti dinamis ([t], [t-1]), dan integrasi tautan Google Drive berkas pemantauan.
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if(auth()->user()?->role === 'super_admin')
                    <a
                        href="/admin/lembar-kerjas/create"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-2xs transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Lembar Kerja Baru</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- 6 LK Switcher Cards (Overview Grid) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lks as $lk)
                <div
                    wire:click="selectLk('{{ $lk['id'] }}')"
                    class="p-5 rounded-xl border text-left cursor-pointer transition-all duration-200 relative overflow-hidden group shadow-2xs
                        {{ $lk['is_selected']
                            ? 'bg-sky-50/50 border-sky-500 dark:bg-sky-950/30 dark:border-sky-500 ring-2 ring-sky-300 dark:ring-sky-700'
                            : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-700 hover:border-sky-400 dark:hover:border-sky-600' }}"
                >
                    {{-- Card Badges --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase border
                                {{ $lk['category'] === 'internal'
                                    ? 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/70 dark:text-sky-300 dark:border-sky-800'
                                    : 'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800' }}">
                                {{ ucfirst($lk['category']) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                TA {{ $lk['period'] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            @if($lk['is_active'])
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="text-[11px] font-semibold text-slate-400">Nonaktif</span>
                            @endif

                            @if($lk['is_selected'])
                                <svg class="w-4 h-4 text-sky-600 dark:text-sky-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                    </div>

                    {{-- Title & Item Count --}}
                    <div class="mt-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight group-hover:text-sky-600 dark:group-hover:text-sky-400 transition truncate">
                            {{ $lk['name'] }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $lk['indicators_count'] }} Indikator &bull; {{ $lk['items_count'] }} Item Bukti
                        </p>
                    </div>

                    {{-- Progress Bar Kelengkapan --}}
                    <div class="mt-4 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-600 dark:text-slate-400">Kelengkapan Bukti</span>
                            <span class="font-extrabold {{ $lk['percentage'] >= 75 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ $lk['percentage'] }}% Terpenuhi ({{ $lk['fulfilled_count'] }}/{{ $lk['indicators_count'] }})
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div
                                class="h-2 rounded-full transition-all duration-500 {{ $lk['percentage'] >= 75 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                style="width: {{ min(100, $lk['percentage']) }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Card Footer Status --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        @if($lk['is_selected'])
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-sky-600 dark:text-sky-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Terpilih di Editor</span>
                            </span>
                            <span class="text-xs font-semibold text-sky-600 dark:text-sky-400">&bull;&bull;&bull;</span>
                        @else
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition flex items-center gap-1">
                                <span>Kelola Indikator</span>
                                <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                        Indikator Penilaian:
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        {{ $selectedLk->name ?? 'Pilih Lembar Kerja' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1 max-w-2xl">
                    {{-- Search Input --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari kode butir atau uraian bukti..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                        />
                    </div>

                    {{-- Area Filter --}}
                    <div>
                        <select
                            wire:model.live="areaFilter"
                            class="w-full py-1.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                        >
                            <option value="">Semua Area Penilaian</option>
                            @foreach($this->getAreaOptions() as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Link Status Filter --}}
                    <div>
                        <select
                            wire:model.live="linkStatusFilter"
                            class="w-full py-1.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                        >
                            <option value="">Status Link: Semua (Valid / Kosong)</option>
                            <option value="has_link">Tautan Drive Tersedia</option>
                            <option value="no_link">Tautan Belum Diatur</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hierarchical Indicator Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300 divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50/90 dark:bg-slate-900 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="w-24 px-4 py-3 text-center">Kode</th>
                            <th scope="col" class="px-4 py-3 min-w-[320px]">Uraian Butir Evaluasi / Kriteria Bukti</th>
                            <th scope="col" class="px-4 py-3 min-w-[240px]">Item Dokumen yang Diharapkan</th>
                            <th scope="col" class="px-4 py-3 min-w-[200px]">Tautan Google Drive</th>
                            <th scope="col" class="px-4 py-3 min-w-[120px]">PIC Satker</th>
                            <th scope="col" class="px-4 py-3 text-right min-w-[100px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                        @php
                            $currentArea = null;
                        @endphp

                        @forelse($indicators as $ind)
                            @php
                                $areaName = $ind->metadata['area'] ?? $this->inferAreaFromCode($ind->code);
                            @endphp

                            {{-- Group Header for Area --}}
                            @if($currentArea !== $areaName)
                                @php $currentArea = $areaName; @endphp
                                <tr class="bg-slate-100/80 dark:bg-slate-900/80 font-bold text-slate-800 dark:text-slate-200">
                                    <td colspan="6" class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                            <span class="tracking-wide">{{ $areaName }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-750 transition-colors">
                                {{-- Kode Indikator --}}
                                <td class="px-4 py-4 text-center align-top">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 font-mono border border-sky-200 dark:border-sky-800">
                                        {{ $ind->code }}
                                    </span>
                                </td>

                                {{-- Uraian Butir Evaluasi --}}
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1">
                                        <p class="font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                            {{ $ind->title }}
                                        </p>
                                        @if(!empty($ind->metadata['target_unit']))
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                                Target Unit: <strong class="text-slate-700 dark:text-slate-300">{{ $ind->metadata['target_unit'] }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Item Dokumen yang Diharapkan --}}
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($ind->evidenceItems as $item)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-50 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800">
                                                <svg class="w-3 h-3 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>{{ $item->raw_template_name }}</span>
                                            </span>
                                        @empty
                                            <span class="text-slate-400 italic text-[11px]">Belum ada item bukti terurai</span>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- Tautan Google Drive --}}
                                <td class="px-4 py-4 align-top">
                                    @if(!empty($ind->evidence_link))
                                        <a
                                            href="{{ $ind->evidence_link }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition max-w-[220px] truncate"
                                            title="{{ $ind->evidence_link }}"
                                        >
                                            <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            <span class="truncate">{{ $ind->evidence_link }}</span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                            <span>Belum Diatur</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- PIC Satker --}}
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                        {{ $ind->pic ?? 'Bagian Umum' }}
                                    </span>
                                </td>

                                {{-- Aksi Edit / Drawer --}}
                                <td class="px-4 py-4 align-top text-right">
                                    <button
                                        type="button"
                                        wire:click="openIndicatorDrawer('{{ $ind->id }}')"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/40 dark:hover:bg-sky-900/60 text-sky-700 dark:text-sky-300 rounded-lg text-xs font-bold transition active:scale-95 shadow-2xs border border-sky-200 dark:border-sky-800"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Atur</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-base font-bold text-slate-700 dark:text-slate-300">Tidak Ada Indikator yang Cocok</span>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm">
                                            Tidak ditemukan butir indikator dengan kata kunci pencarian atau filter yang dipilih.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                <div>
                    Menampilkan <strong class="text-slate-900 dark:text-slate-100">{{ $indicators->firstItem() ?? 0 }}</strong> s.d. <strong class="text-slate-900 dark:text-slate-100">{{ $indicators->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-900 dark:text-slate-100">{{ $indicators->total() }}</strong> Indikator
                </div>

                @if($indicators->hasPages())
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousIndicatorPage"
                            @disabled($indicators->onFirstPage())
                            class="px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            &larr; Sebelumnya
                        </button>

                        <button
                            type="button"
                            wire:click="nextIndicatorPage"
                            @disabled(!$indicators->hasMorePages())
                            class="px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            Selanjutnya &rarr;
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Slide-over Drawer: Pengaturan Indikator Lembar Kerja --}}
        @if($editingIndicatorId)
            <div class="fixed inset-0 z-50 overflow-hidden animate-in fade-in duration-200">
                {{-- Backdrop --}}
                <div
                    wire:click="closeIndicatorDrawer"
                    class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
                ></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <div class="w-screen max-w-xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col overflow-hidden border-l border-slate-200 dark:border-slate-700 transition-colors">
                        {{-- Drawer Header --}}
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900 flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-black bg-sky-600 text-white font-mono">
                                        {{ $indicatorCode }}
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        {{ $selectedLk->name ?? 'LKE ZONA INTEGRITAS 2026' }}
                                    </span>
                                </div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                                    Pengaturan Indikator Lembar Kerja
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Konfigurasi standar butir penilaian, perumusan item token bukti, dan direktori penyimpanan Google Drive.
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="closeIndicatorDrawer"
                                class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Drawer Body (Scrollable Form) --}}
                        <div class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700 dark:text-slate-300">
                            {{-- 1. Kode Indikator --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-bold text-slate-900 dark:text-slate-100">
                                        Kode Indikator <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Unik dalam satu periode LK</span>
                                </div>
                                <input
                                    type="text"
                                    wire:model="indicatorCode"
                                    placeholder="Contoh: A11i"
                                    class="w-full px-3.5 py-2 text-xs font-mono font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                                />
                            </div>

                            {{-- 2. Area / Komponen Induk --}}
                            <div>
                                <label class="block font-bold text-slate-900 dark:text-slate-100 mb-1">
                                    Area / Komponen Induk <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="indicatorArea"
                                    list="areaList"
                                    placeholder="Contoh: Area I — Manajemen Perubahan > Pembentukan Tim Kerja"
                                    class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                                />
                                <datalist id="areaList">
                                    @foreach($this->getAreaOptions() as $k => $v)
                                        <option value="{{ $v }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            {{-- 3. Uraian Indikator Evaluasi --}}
                            <div>
                                <label class="block font-bold text-slate-900 dark:text-slate-100 mb-1">
                                    Uraian Indikator Evaluasi <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    wire:model="indicatorTitle"
                                    rows="3"
                                    placeholder="Tuliskan rumusan pertanyaan atau kriteria evaluasi..."
                                    class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs leading-relaxed"
                                ></textarea>
                            </div>

                            {{-- 4. Item Dokumen Bukti yang Diharapkan (Token chips) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="font-bold text-slate-900 dark:text-slate-100">
                                        Item Dokumen Bukti yang Diharapkan <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500">Bisa lebih dari 1 dokumen</span>
                                </div>

                                {{-- Active Chips --}}
                                <div class="flex flex-wrap gap-2 mb-2 p-3 bg-slate-50 dark:bg-slate-900/60 rounded-lg border border-slate-200 dark:border-slate-700 min-h-[48px]">
                                    @foreach($indicatorItems as $idx => $token)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-sky-100 text-sky-900 dark:bg-sky-950/70 dark:text-sky-200 border border-sky-300 dark:border-sky-800 shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>{{ $token }}</span>
                                            <button
                                                type="button"
                                                wire:click="removeItemToken({{ $idx }})"
                                                class="ml-1 text-sky-600 hover:text-rose-600 focus:outline-none"
                                            >
                                                &times;
                                            </button>
                                        </span>
                                    @endforeach

                                    @if(empty($indicatorItems))
                                        <span class="text-slate-400 dark:text-slate-500 italic text-xs">Belum ada token bukti yang ditambahkan.</span>
                                    @endif
                                </div>

                                {{-- Add Item Input --}}
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        wire:model="newItemText"
                                        wire:keydown.enter.prevent="addItemToken"
                                        placeholder="Ketik nama bukti (misal: Undangan & Notula Rapat [t])..."
                                        class="flex-1 px-3.5 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                                    />
                                    <button
                                        type="button"
                                        wire:click="addItemToken"
                                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition shadow-2xs"
                                    >
                                        + Tambah
                                    </button>
                                </div>

                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1 leading-normal">
                                    <span class="text-sky-600 font-bold">&bull;</span>
                                    Gunakan token <strong>[t]</strong> untuk mewakili tahun berjalan ({{ $selectedLk->period ?? 2026 }}) dan <strong>[t-1]</strong> untuk tahun sebelumnya saat verifikasi dokumen.
                                </p>
                            </div>

                            {{-- 5. Tautan Google Drive Folder / Berkas --}}
                            <div>
                                <label class="block font-bold text-slate-900 dark:text-slate-100 mb-1">
                                    Tautan Google Drive Folder / Berkas <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-emerald-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </div>
                                    <input
                                        type="url"
                                        wire:model.live.debounce.300ms="indicatorDriveLink"
                                        placeholder="https://drive.google.com/drive/folders/..."
                                        class="w-full pl-9 pr-3.5 py-2 text-xs font-mono rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                                    />
                                </div>

                                <div class="flex items-center justify-between text-[11px] mt-1.5">
                                    @if(!empty($indicatorDriveLink))
                                        @if(preg_match('/^https?:\/\/.+/i', $indicatorDriveLink))
                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Format URL Google Drive Valid
                                            </span>
                                        @else
                                            <span class="text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Wajib diawali http:// atau https://
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">Tautan opsional / belum diatur</span>
                                    @endif
                                    <span class="text-slate-400">&bull; Protokol resmi https://</span>
                                </div>
                            </div>

                            {{-- 6. PIC / Satker Penanggung Jawab --}}
                            <div>
                                <label class="block font-bold text-slate-900 dark:text-slate-100 mb-1">
                                    PIC / Satuan Kerja Penanggung Jawab <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="indicatorPic"
                                    list="picList"
                                    placeholder="Contoh: Bagian Umum, IPDS, Humas / PPID"
                                    class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                                />
                                <datalist id="picList">
                                    <option value="Bagian Umum"></option>
                                    <option value="IPDS"></option>
                                    <option value="Humas / PPID"></option>
                                    <option value="Nerwilis"></option>
                                    <option value="Diseminasi"></option>
                                </datalist>
                            </div>

                            {{-- 7. Warning Box: Catatan Rematch Otomatis (LK-07) --}}
                            <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <strong class="block font-bold mb-0.5">Catatan Rematch Otomatis (LK-07):</strong>
                                    Menyimpan perubahan pada item bukti akan otomatis memicu antrean pencocokan ulang (*Rematch Queue*) terhadap seluruh dokumen bukti aktif tanpa mengubah hasil verifikasi manual.
                                </div>
                            </div>
                        </div>

                        {{-- Drawer Footer Actions --}}
                        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                wire:click="closeIndicatorDrawer"
                                class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-semibold transition"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                wire:click="saveIndicator"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition shadow-2xs active:scale-95 flex items-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <span>Simpan Perubahan Indikator</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
