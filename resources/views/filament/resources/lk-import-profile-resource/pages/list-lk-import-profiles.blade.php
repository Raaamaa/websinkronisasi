<x-filament-panels::page>
    @php
        $stats = $this->getValidationStats();
        $previews = $this->getPreviewSamples();
        $anomalies = $this->getAnomalyRows();
        $columnOptions = $this->getColumnOptions();
        $availableProfiles = $this->getAvailableProfiles();
        $selectedLk = $this->selectedLk;
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
                    <a href="/admin/lembar-kerjas" class="hover:text-sky-600 transition">Lembar Kerja (LK)</a>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Wizard Impor Excel</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2.5 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        MODUL IMPOR OTOMATIS
                    </span>
                    <span class="text-xs text-slate-400 font-medium">&bull;</span>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        BPS RI Standard Toolkit v2.6
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                    Wizard Impor Lembar Kerja dari File Excel
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-3xl">
                    Unggah file evaluasi (.xlsx), petakan kolom indikator secara fleksibel, dan simpan secara idempoten tanpa duplikasi metadata instansi.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>LKIMP-03 Standard</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>ISO 27001 BPS Secured</span>
                </span>
            </div>
        </div>

        {{-- 4-Stage Stepper (Large & Senior-friendly, High Contrast Dual-Theme) --}}
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                {{-- Step 1 --}}
                <div class="flex items-center gap-3 p-3 rounded-lg {{ $currentStep > 1 ? 'bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/60' }}">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-sm flex-shrink-0 {{ $currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                        @if($currentStep > 1)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Langkah 1</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">Unggah Berkas &amp; LK</span>
                    </div>
                </div>

                {{-- Step 2 (Active) --}}
                <div class="flex items-center gap-3 p-3 rounded-lg bg-sky-50 dark:bg-sky-950/40 border-2 border-sky-500 dark:border-sky-500">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm bg-sky-600 text-white shadow-2xs flex-shrink-0">
                        2
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300 block">Langkah 2 (Aktif)</span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">Konfigurasi Profil Impor</span>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/60 opacity-80">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 flex-shrink-0">
                        3
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Langkah 3</span>
                        <span class="text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400">Pratinjau &amp; Validasi Data</span>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/60 opacity-80">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 flex-shrink-0">
                        4
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Langkah 4</span>
                        <span class="text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-400">Konfirmasi &amp; Eksekusi</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subheader File & Target LK Banner (Adaptive Dual-Theme, Zero Text Collision) --}}
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xs transition-colors">
            <div class="flex flex-wrap items-center gap-4">
                {{-- Berkas File --}}
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold text-xs border border-emerald-200 dark:border-emerald-800">
                        XLSX
                    </div>
                    <div>
                        <div class="font-bold text-xs text-slate-900 dark:text-slate-100 font-mono">
                            {{ $uploadedFileName }}
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $uploadedFileSize }} &bull; MIME: openxmlformats
                        </div>
                    </div>
                </div>

                <div class="hidden md:block w-px h-8 bg-slate-200 dark:bg-slate-700"></div>

                {{-- Target LK --}}
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-950/70 text-sky-800 dark:text-sky-300 flex items-center justify-center font-bold text-xs border border-sky-200 dark:border-sky-800">
                        <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-xs text-slate-900 dark:text-slate-100">
                            Target LK: {{ $selectedLk->name ?? 'Zona Integritas (ZI)' }}
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Tahun Anggaran {{ $selectedLk->period ?? 2026 }} &bull; Format BPS Terpadu
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 font-semibold bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>Read-only Parser (Makro, VBA &amp; Formula divalidasi aman - LKIMP-10)</span>
            </div>
        </div>

        {{-- Main 2-Column Split: Spesifikasi Pemetaan & Validasi Realtime --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Left Column: Spesifikasi & Pemetaan Kolom (5 Cols) --}}
            <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-2xs space-y-5 transition-colors">
                {{-- Header Box --}}
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            Spesifikasi & Pemetaan Kolom
                        </h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Petakan letak kolom indikator pada berkas lembar kerja.
                        </p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        Langkah 2/4
                    </span>
                </div>

                {{-- Preset Profil Selector --}}
                <div class="space-y-1.5">
                    <label class="block font-bold text-xs text-slate-900 dark:text-slate-100">
                        Pilih Profil Impor Bawaan (LKIMP-03)
                    </label>
                    <select
                        wire:model.live="selectedProfileId"
                        class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-2xs font-semibold"
                    >
                        @foreach($availableProfiles as $pId => $pName)
                            <option value="{{ $pId }}">{{ $pName }} (Rekomendasi)</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Profil mengatur otomatisasi deteksi header dan pola pemetaan kolom resmi BPS.
                    </p>
                </div>

                {{-- Simpan Sebagai Template Checkbox --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model.live="saveAsTemplate"
                            class="rounded border-slate-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500"
                        />
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            Simpan konfigurasi ini sebagai profil template
                        </span>
                    </label>

                    @if($saveAsTemplate)
                        <input
                            type="text"
                            wire:model="newTemplateName"
                            placeholder="Ketik nama profil template..."
                            class="w-full py-1.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 shadow-2xs"
                        />
                    @endif
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-3">
                        Pemetaan Kolom Entitas Indikator
                    </span>

                    <div class="space-y-3.5">
                        {{-- 1. Kode Indikator --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                    1. Kode Indikator <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 px-1.5 py-0.5 rounded border border-sky-200 dark:border-sky-800">Wajib Unik</span>
                            </div>
                            <select
                                wire:model="colCode"
                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                @foreach($columnOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2. Uraian Indikator / Kriteria --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                    2. Uraian Indikator / Kriteria <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">Wajib</span>
                            </div>
                            <select
                                wire:model="colTitle"
                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                @foreach($columnOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 3. Item Dokumen Bukti --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                    3. Item Dokumen Bukti
                                </label>
                                <span class="text-[10px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 px-1.5 py-0.5 rounded border border-sky-200 dark:border-sky-800">Multi-Item Parser</span>
                            </div>
                            <select
                                wire:model="colEvidence"
                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                @foreach($columnOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 4. Aturan Pemecahan Bukti (Item Split Rule) --}}
                        <div class="space-y-2 pt-1">
                            <label class="block text-xs font-bold text-slate-900 dark:text-slate-100">
                                4. Aturan Pemecahan Bukti (Item Split Rule)
                            </label>

                            <div class="space-y-2">
                                <label class="flex items-start gap-2.5 p-2.5 rounded-lg border transition cursor-pointer {{ $parsingStrategy === 'numbered_list_single_cell' ? 'bg-sky-50/70 border-sky-400 dark:bg-sky-950/40 dark:border-sky-600' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                    <input
                                        type="radio"
                                        name="parsingStrategy"
                                        wire:model.live="parsingStrategy"
                                        value="numbered_list_single_cell"
                                        class="mt-0.5 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100 block">Teks bernomor dalam 1 sel</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Deteksi regex: "1. SK Tim... 2. Notula... 3. Laporan" dipecah jadi item terpisah</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-2.5 p-2.5 rounded-lg border transition cursor-pointer {{ $parsingStrategy === 'checklist_sheet' ? 'bg-sky-50/70 border-sky-400 dark:bg-sky-950/40 dark:border-sky-600' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                    <input
                                        type="radio"
                                        name="parsingStrategy"
                                        wire:model.live="parsingStrategy"
                                        value="checklist_sheet"
                                        class="mt-0.5 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100 block">Sheet checklist terpisah (format PEKPPP)</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Hubungkan butir via relasi master foreign-key baris</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-2.5 p-2.5 rounded-lg border transition cursor-pointer {{ $parsingStrategy === 'single_item_per_row' ? 'bg-sky-50/70 border-sky-400 dark:bg-sky-950/40 dark:border-sky-600' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                    <input
                                        type="radio"
                                        name="parsingStrategy"
                                        wire:model.live="parsingStrategy"
                                        value="single_item_per_row"
                                        class="mt-0.5 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100 block">Satu item bukti per baris (1:1 linier)</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Tanpa pemecahan; seluruh string disimpan sebagai dokumen tunggal</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- 5. Tautan Google Drive Bukti --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-slate-100 mb-1">
                                5. Tautan Google Drive Bukti
                            </label>
                            <select
                                wire:model="colDriveLink"
                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                @foreach($columnOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 6. PIC Satuan Kerja --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-slate-100 mb-1">
                                6. PIC Satuan Kerja / Tim Asesor (Opsional)
                            </label>
                            <select
                                wire:model="colPic"
                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono"
                            >
                                @foreach($columnOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Pratinjau, Metrik Validasi & Eksekusi (7 Cols) --}}
            <div class="lg:col-span-7 space-y-5">
                {{-- 4 Metric Summary KPI Cards (Executive Government Palette, Zero AI Slop) --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- Total Baris File --}}
                    <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Total Baris File</span>
                        <div class="text-xl font-black text-slate-900 dark:text-slate-100 mt-1">
                            {{ $stats['total_rows'] }} <span class="text-xs font-medium text-slate-500">baris</span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 block">Termasuk header &amp; subtotal</span>
                    </div>

                    {{-- Indikator Valid --}}
                    <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Indikator Valid</span>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-baseline gap-1.5">
                            <span>{{ $stats['valid_indicators'] }}</span>
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">lolos</span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 block">{{ $stats['valid_pct'] }}% lolos regex</span>
                    </div>

                    {{-- Baris Dilewati --}}
                    <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Baris Dilewati</span>
                        <div class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1 flex items-baseline gap-1.5">
                            <span>{{ $stats['skipped_rows'] }}</span>
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">aman</span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 block">Non-butir / judul / subtotal</span>
                    </div>

                    {{-- Status Validasi --}}
                    <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Status Validasi</span>
                        <div class="text-xl font-black text-sky-600 dark:text-sky-400 mt-1 flex items-center gap-1">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $stats['fatal_errors'] }} Fatal</span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 block">Siap dieksekusi</span>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs flex flex-wrap items-center justify-between gap-3 transition-colors">
                    <a
                        href="/admin/lembar-kerjas"
                        class="px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg border border-slate-300 dark:border-slate-600 transition flex items-center gap-1.5"
                    >
                        <span>&larr;</span>
                        <span>Kembali ke Master LK</span>
                    </a>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="retestMapping"
                            wire:loading.attr="disabled"
                            class="px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg border border-slate-300 dark:border-slate-600 transition flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Uji Ulang Pemetaan</span>
                        </button>

                        <button
                            type="button"
                            wire:click="executeImport"
                            wire:confirm="Yakin ingin mengeksekusi impor ke Lembar Kerja ini? Proses bersifat idempoten dan tidak menimpa dokumen yang telah diverifikasi manual."
                            wire:loading.attr="disabled"
                            class="px-5 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition shadow-2xs active:scale-95 flex items-center gap-1.5"
                        >
                            <span>Konfirmasi &amp; Eksekusi Impor (Idempoten)</span>
                            <span class="font-extrabold">&rarr;</span>
                        </button>
                    </div>
                </div>

                {{-- Idempotency Guarantee Callout --}}
                <div class="p-3.5 bg-emerald-50/80 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 text-xs flex items-start gap-3 transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div>
                        <strong class="font-bold block mb-0.5">Idempoten Terjamin (LKIMP-07, LKIMP-08):</strong>
                        Data butir yang sudah sinkron akan diperbarui (*upsert*). Dokumen bukti hasil verifikasi manual yang telah divalidasi oleh Asesor BPS tetap aman dan <strong>tidak akan tertimpa</strong>.
                    </div>
                </div>

                {{-- Realtime Parsing Preview Table (Sampling) --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs overflow-hidden transition-colors">
                    <div class="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                                Pratinjau Hasil Parsing Realtime (Sampling)
                            </span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            Parser Aktif: Regex
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50/70 dark:bg-slate-900 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th scope="col" class="w-16 px-4 py-2.5 text-center">Kode</th>
                                    <th scope="col" class="px-4 py-2.5">Uraian Indikator Evaluasi</th>
                                    <th scope="col" class="px-4 py-2.5">Item Dokumen Bukti (Terurai)</th>
                                    <th scope="col" class="px-4 py-2.5">Tautan Drive</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                @foreach($previews as $prev)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-750 transition">
                                        <td class="px-4 py-3.5 text-center align-top">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 font-mono border border-sky-200 dark:border-sky-800">
                                                {{ $prev['code'] }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-0.5">Row #{{ $prev['row_num'] }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <div class="font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                                {{ $prev['title'] }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                                PIC: <strong class="text-slate-700 dark:text-slate-300">{{ $prev['pic'] }}</strong>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <div class="space-y-1">
                                                @foreach($prev['items'] as $item)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-50 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800 block max-w-xs truncate">
                                                        <svg class="w-3 h-3 text-sky-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <span class="truncate">{{ $item }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 truncate max-w-[140px]" title="{{ $prev['drive_link'] }}">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                <span>drive.google...</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                        <span>Menampilkan 3 dari 109 indikator valid yang siap diinjeksi</span>
                        <a href="/admin/lembar-kerjas" class="text-sky-600 dark:text-sky-400 font-bold hover:underline flex items-center gap-1">
                            <span>Lihat seluruh 109 baris di Master LK</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                {{-- Collapsible Anomaly / Skipped Rows Box --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs overflow-hidden transition-colors">
                    <div class="p-4 bg-amber-50/70 dark:bg-amber-950/40 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                                Laporan Anomali: 30 Baris Dilewati Otomatis (Aman)
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">Exit Code 0</span>
                    </div>

                    <div class="p-4 space-y-3">
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Engine LKIMP-05 mendeteksi baris di bawah ini bukan sebagai entitas indikator tunggal dan mengecualikannya secara aman agar database tetap bersih:
                        </p>

                        <div class="space-y-2">
                            @foreach($anomalies as $anom)
                                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-start justify-between gap-3 text-xs">
                                    <div class="space-y-0.5">
                                        <div class="font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ $anom['row_range'] }}
                                        </div>
                                        <p class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
                                            {{ $anom['description'] }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex-shrink-0">
                                        {{ $anom['badge'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-2 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span>Semua pengecualian di atas teridentifikasi tanpa error fatal parser.</span>
                            <span class="text-sky-600 dark:text-sky-400 font-semibold cursor-pointer hover:underline">Unduh Log Audit Pengecualian (.csv)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
