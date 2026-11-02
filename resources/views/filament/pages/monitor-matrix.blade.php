<x-filament-panels::page>
    @php
        $matrix = $this->getViewData();
        $internalLks = $matrix['columns']['internal'];
        $eksternalLks = $matrix['columns']['eksternal'];
        $rows = $matrix['rows'];
        $paginator = $matrix['paginator'];
        $totalDocs = $paginator->total();
    @endphp

    <div class="space-y-6" x-data @keydown.escape.window="$wire.closeDrilldown()">
        {{-- Banner Identitas Resmi & Ringkasan (BPS / ELKA) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-slate-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-200">
                        BPS • ELKA
                    </span>
                    <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                        Elektronik Lembar Kerja & Sinkronisasi
                    </span>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                    Matriks Sinkronisasi Bukti Dukung ke Lembar Kerja
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Pemetaan dokumen bukti dukung SK, SOP, dan Laporan terhadap indikator Lembar Kerja Internal BPS &amp; Eksternal.
                </p>
            </div>

            {{-- Counter Ringkas --}}
            <div class="flex items-center gap-3 self-start md:self-auto">
                <div class="px-3.5 py-2 rounded-lg bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-center">
                    <span class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Total Dokumen</span>
                    <span class="text-base font-extrabold text-slate-900 dark:text-slate-100">{{ $totalDocs }}</span>
                </div>
                <div class="px-3.5 py-2 rounded-lg bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 text-center">
                    <span class="block text-xs font-semibold text-sky-800 dark:text-sky-300">LK Internal</span>
                    <span class="text-base font-extrabold text-sky-900 dark:text-sky-100">{{ $internalLks->count() }}</span>
                </div>
                <div class="px-3.5 py-2 rounded-lg bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 text-center">
                    <span class="block text-xs font-semibold text-teal-800 dark:text-teal-300">LK Eksternal</span>
                    <span class="text-base font-extrabold text-teal-900 dark:text-teal-100">{{ $eksternalLks->count() }}</span>
                </div>
            </div>
        </div>

        {{-- Panduan Interaktif & Legenda (Ramah Boomer & Milenial) --}}
        <div class="bg-slate-50 dark:bg-gray-850 p-4 rounded-xl border border-slate-200 dark:border-gray-700 text-xs text-slate-700 dark:text-slate-300">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-700 dark:text-sky-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Panduan Cepat:
                    </span>
                    <span class="text-slate-600 dark:text-slate-300">
                        Klik tombol tanda centang untuk membuka berkas bukti dukung langsung di Google Drive.
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs font-medium">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-emerald-100 dark:bg-emerald-900/60 border border-emerald-400 text-emerald-800 dark:text-emerald-200 font-extrabold text-xs">
                            ✔
                        </span>
                        <span>Cocok (Tersedia di Drive)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-6 h-6 rounded bg-white dark:bg-gray-700 border border-slate-300 dark:border-gray-600 text-center"></span>
                        <span class="text-slate-500 dark:text-slate-400">Sel Kosong = Belum Cocok</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Toolbar --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label for="filter-period" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                        Periode Lembar Kerja
                    </label>
                    <input 
                        id="filter-period"
                        type="number" 
                        wire:model.live.debounce.500ms="period" 
                        class="w-full text-sm font-medium rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm" 
                        min="2020" 
                        max="2100" 
                    />
                </div>

                <div>
                    <label for="filter-search" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                        Cari Dokumen / Nomor SK
                    </label>
                    <input 
                        id="filter-search"
                        type="text" 
                        wire:model.live.debounce.500ms="search" 
                        placeholder="Ketik kata kunci atau nomor..." 
                        class="w-full text-sm font-medium rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm placeholder:text-slate-400" 
                    />
                </div>

                <div>
                    <label for="filter-doc-type" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                        Jenis Dokumen
                    </label>
                    <select 
                        id="filter-doc-type"
                        wire:model.live="document_type" 
                        class="w-full text-sm font-medium rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm"
                    >
                        <option value="">Semua Jenis Dokumen</option>
                        <option value="SK">Surat Keputusan (SK)</option>
                        <option value="SOP">Standar Operasional Prosedur (SOP)</option>
                        <option value="Laporan">Laporan Kinerja / Kegiatan</option>
                        <option value="Surat Edaran">Surat Edaran</option>
                        <option value="Data">Data / Statistik</option>
                    </select>
                </div>

                <div>
                    <label for="filter-year" class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                        Tahun Terbit Dokumen
                    </label>
                    <input 
                        id="filter-year"
                        type="number" 
                        wire:model.live.debounce.500ms="year" 
                        placeholder="Contoh: 2026" 
                        class="w-full text-sm font-medium rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm placeholder:text-slate-400" 
                    />
                </div>
            </div>
        </div>

        {{-- Matriks Tabel Monitor --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-x-auto relative">
            <table class="w-full text-sm text-left border-collapse min-w-[700px]">
                <thead>
                    {{-- Super Header Kategori LK (MON-01) --}}
                    <tr class="bg-slate-100 dark:bg-gray-900 border-b border-slate-300 dark:border-gray-700 text-xs font-bold uppercase tracking-wider text-center">
                        <th class="p-3.5 text-left border-r border-slate-300 dark:border-gray-700 min-w-[340px] sticky left-0 z-20 bg-slate-100 dark:bg-gray-900 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)] text-slate-700 dark:text-slate-200">
                            Indikator Kesamaan / Dokumen Bukti Dukung
                        </th>
                        @if($internalLks->isNotEmpty())
                            <th colspan="{{ $internalLks->count() }}" class="p-3 bg-sky-50 dark:bg-sky-950/50 text-sky-900 dark:text-sky-200 border-r border-slate-300 dark:border-gray-700 font-extrabold">
                                Lembar Kerja Internal (BPS)
                            </th>
                        @endif
                        @if($eksternalLks->isNotEmpty())
                            <th colspan="{{ $eksternalLks->count() }}" class="p-3 bg-teal-50 dark:bg-teal-950/50 text-teal-900 dark:text-teal-200 font-extrabold">
                                Lembar Kerja Eksternal
                            </th>
                        @endif
                    </tr>

                    {{-- Sub Header Kolom Kode LK --}}
                    <tr class="bg-slate-50 dark:bg-gray-800 border-b border-slate-300 dark:border-gray-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <th class="p-3 min-w-[340px] border-r border-slate-300 dark:border-gray-700 sticky left-0 z-20 bg-slate-50 dark:bg-gray-800 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)] text-slate-800 dark:text-slate-200 font-bold">
                            Nama Dokumen &amp; Butir Kebutuhan Bukti
                        </th>

                        @foreach($internalLks as $lk)
                            <th class="p-3 text-center min-w-[90px] border-r border-slate-300 dark:border-gray-700 bg-sky-50/70 dark:bg-sky-950/30 text-sky-950 dark:text-sky-100 font-bold" title="{{ $lk->name }}">
                                <div class="flex flex-col items-center">
                                    <span class="font-extrabold text-xs text-sky-900 dark:text-sky-200">{{ $lk->code }}</span>
                                    <span class="text-[10px] text-sky-700 dark:text-sky-400 font-normal truncate max-w-[80px]" title="{{ $lk->name }}">{{ $lk->name }}</span>
                                </div>
                            </th>
                        @endforeach

                        @foreach($eksternalLks as $lk)
                            <th class="p-3 text-center min-w-[90px] border-r border-slate-300 dark:border-gray-700 bg-teal-50/70 dark:bg-teal-950/30 text-teal-950 dark:text-teal-100 font-bold" title="{{ $lk->name }}">
                                <div class="flex flex-col items-center">
                                    <span class="font-extrabold text-xs text-teal-900 dark:text-teal-200">{{ $lk->code }}</span>
                                    <span class="text-[10px] text-teal-700 dark:text-teal-400 font-normal truncate max-w-[80px]" title="{{ $lk->name }}">{{ $lk->name }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                    @forelse($rows as $row)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/40 transition">
                            {{-- Sticky Kolom Nama Dokumen & Indikator --}}
                            <td class="p-3.5 border-r border-slate-200 dark:border-gray-700 sticky left-0 z-10 bg-white dark:bg-gray-800 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)]">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                            {{ $row['document_name'] }}
                                        </span>
                                        @if(!empty($row['document_type']))
                                            <span class="text-[11px] px-2 py-0.5 rounded-md font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-200 border border-sky-300 dark:border-sky-800">
                                                {{ $row['document_type'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if(!empty($row['indicators_summary']))
                                        <div class="text-xs text-slate-700 dark:text-slate-300 font-normal bg-slate-50 dark:bg-gray-900/60 p-2 rounded-lg border border-slate-200 dark:border-gray-700">
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">Indikator Terhubung:</span> 
                                            {{ $row['indicators_summary'] }}
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                        @if(!empty($row['document_number']))
                                            <span>No: <strong class="text-slate-800 dark:text-slate-200">{{ $row['document_number'] }}</strong></span>
                                        @endif
                                        <span>Tahun: <strong class="text-slate-800 dark:text-slate-200">{{ $row['year'] }}</strong></span>
                                    </div>
                                </div>
                            </td>

                            {{-- Kolom Internal --}}
                            @foreach($internalLks as $lk)
                                @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null]; @endphp
                                <td class="p-3 text-center border-r border-slate-200 dark:border-gray-700 align-middle">
                                    @if($cell['has_match'])
                                        @if(!empty($cell['drive_url']))
                                            <a 
                                                href="{{ $cell['drive_url'] }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-800 dark:bg-emerald-950/70 dark:hover:bg-emerald-900 dark:text-emerald-200 font-black text-base border-2 border-emerald-500 hover:border-emerald-600 dark:border-emerald-400 shadow-sm hover:scale-105 active:scale-95 transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                                                aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                title="Buka Google Drive Bukti Dukung ({{ $lk->code }})">
                                                <span class="flex items-center gap-0.5">
                                                    {{ $cell['symbol'] }}
                                                    <svg class="w-3 h-3 ml-0.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </span>
                                            </a>
                                        @else
                                            <button 
                                                type="button"
                                                wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                                class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-800 dark:bg-emerald-950/70 dark:hover:bg-emerald-900 dark:text-emerald-200 font-black text-base border-2 border-emerald-500 hover:border-emerald-600 dark:border-emerald-400 shadow-sm hover:scale-105 active:scale-95 transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                                                aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                title="Klik untuk melihat rincian indikator cocok">
                                                {{ $cell['symbol'] }}
                                            </button>
                                        @endif
                                    @endif
                                    {{-- Sel Kosong Murni jika tidak cocok (MON-02, AC-09) --}}
                                </td>
                            @endforeach

                            {{-- Kolom Eksternal --}}
                            @foreach($eksternalLks as $lk)
                                @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null]; @endphp
                                <td class="p-3 text-center border-r border-slate-200 dark:border-gray-700 align-middle">
                                    @if($cell['has_match'])
                                        @if(!empty($cell['drive_url']))
                                            <a 
                                                href="{{ $cell['drive_url'] }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-800 dark:bg-emerald-950/70 dark:hover:bg-emerald-900 dark:text-emerald-200 font-black text-base border-2 border-emerald-500 hover:border-emerald-600 dark:border-emerald-400 shadow-sm hover:scale-105 active:scale-95 transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                                                aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                title="Buka Google Drive Bukti Dukung ({{ $lk->code }})">
                                                <span class="flex items-center gap-0.5">
                                                    {{ $cell['symbol'] }}
                                                    <svg class="w-3 h-3 ml-0.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </span>
                                            </a>
                                        @else
                                            <button 
                                                type="button"
                                                wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                                class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-800 dark:bg-emerald-950/70 dark:hover:bg-emerald-900 dark:text-emerald-200 font-black text-base border-2 border-emerald-500 hover:border-emerald-600 dark:border-emerald-400 shadow-sm hover:scale-105 active:scale-95 transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                                                aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                title="Klik untuk melihat rincian indikator cocok">
                                                {{ $cell['symbol'] }}
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 1 + $internalLks->count() + $eksternalLks->count() }}" class="p-12 text-center text-slate-600 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak ada dokumen yang ditemukan</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Silakan sesuaikan kata kunci atau bersihkan filter di atas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($paginator->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-800">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>

        {{-- Modal Drilldown Detail Centang (MON-04, MON-05, MON-06, MON-10, AC-12) --}}
        @if($showDrilldown && $drilldownData)
            <div 
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
                wire:click.self="closeDrilldown"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full p-6 border border-slate-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-gray-700 pb-4 mb-4">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200 border border-sky-300 dark:border-sky-800">
                                {{ $drilldownData['lk']['code'] }} • Periode {{ $drilldownData['lk']['period'] }}
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-1.5">
                                {{ $drilldownData['document']['name'] }}
                            </h3>
                            @if(!empty($drilldownData['document']['number']))
                                <p class="text-xs text-slate-600 dark:text-slate-400 font-medium mt-0.5">
                                    Nomor SK: {{ $drilldownData['document']['number'] }}
                                </p>
                            @endif
                        </div>
                        <button 
                            type="button"
                            wire:click="closeDrilldown" 
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"
                            aria-label="Tutup modal rincian"
                        >
                            &times;
                        </button>
                    </div>

                    {{-- Daftar Indikator Cocok --}}
                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Dokumen ini cocok dengan indikator penilaian berikut:
                        </p>

                        @foreach($drilldownData['indicators'] as $ind)
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-900/40">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-sm text-slate-900 dark:text-slate-100">
                                        [{{ $ind['code'] }}] {{ $ind['title'] }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $ind['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 border border-emerald-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200 border border-sky-300' }}">
                                            {{ $ind['status_label'] }} ({{ $ind['confidence_score'] }}%)
                                        </span>
                                    </div>
                                </div>

                                @if(!empty($ind['matched_item_name']))
                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">
                                        <strong class="text-slate-700 dark:text-slate-300">Item Bukti Cocok:</strong> {{ $ind['matched_item_name'] }}
                                    </p>
                                @endif

                                {{-- Tautan Bukti (MON-05, MON-06, MON-10, AC-12) --}}
                                <div class="mt-3 pt-2.5 border-t border-slate-200 dark:border-gray-700/60 flex items-center justify-between text-xs">
                                    <div>
                                        @if($ind['has_valid_link'])
                                            <a 
                                                href="{{ $ind['evidence_link'] }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold transition shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                                            >
                                                <span>Buka Tautan Google Drive</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400 italic">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                                {{ $ind['link_placeholder'] }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Aksi Hapus Relasi untuk Admin --}}
                                    @if(in_array(Auth::user()?->role, ['super_admin', 'admin'], true))
                                        <button 
                                            type="button"
                                            wire:click="removeMatch('{{ $drilldownData['document']['id'] }}', '{{ $ind['indicator_id'] }}')"
                                            wire:confirm="Yakin ingin mencabut relasi kecocokan ini? Tindakan ini akan menolak match secara permanen."
                                            class="text-rose-600 hover:text-rose-800 dark:text-rose-400 font-semibold cursor-pointer underline focus:outline-none"
                                        >
                                            Cabut Relasi
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button 
                            type="button"
                            wire:click="closeDrilldown" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-slate-800 dark:text-slate-200 rounded-lg text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-slate-400"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>