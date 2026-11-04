<x-filament-panels::page>
    @php
        $matrix = $this->getViewData();
        $stats = $this->getStats();
        $internalLks = $matrix['columns']['internal'];
        $eksternalLks = $matrix['columns']['eksternal'];
        $allLks = $internalLks->merge($eksternalLks);
        $rows = $matrix['rows'];
        $paginator = $matrix['paginator'];
        $totalDocs = $paginator->total();
        $allLksCount = $allLks->count();
    @endphp

    <div class="space-y-5" x-data @keydown.escape.window="$wire.closeDrilldown()">
        {{-- Formal Header BPS RI --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-1 border-b border-slate-200 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                        Matriks Pemetaan Bukti Dukung ke Lembar Kerja Penilaian
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700">
                        TA {{ $period }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Monitoring pemenuhan dokumen eviden digital pada {{ $allLksCount }} instrumen evaluasi kinerja BPS secara terpadu.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 px-3 py-1.5 rounded-md border border-slate-200 dark:border-slate-700 self-start md:self-auto">
                <span class="w-2 h-2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                <span>Sinkronisasi Otomatis Aktif</span>
            </div>
        </div>

        {{-- 4 Formal Executive KPI Stat Cards (Zero AI Slop / Formal Standard) --}}
        <div class="monitor-kpi-grid">
            {{-- Card 1: Total Indikator LK --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Total Indikator LK
                    </span>
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['total_indicators'] }}
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        Tersebar di {{ $allLksCount }} Lembar Kerja Evaluasi
                    </p>
                </div>
            </div>

            {{-- Card 2: Bukti Terpenuhi (✔) --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Bukti Terpenuhi (✔)
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700">
                        {{ $stats['fulfilled_percentage'] }}%
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['fulfilled_indicators'] }}
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        Target kelengkapan dokumen evaluasi
                    </p>
                </div>
            </div>

            {{-- Card 3: Belum Ada Bukti --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Belum Ada Bukti
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700">
                        {{ $stats['unfulfilled_percentage'] }}%
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['unfulfilled_indicators'] }}
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        Perlu pemenuhan eviden pendukung
                    </p>
                </div>
            </div>

            {{-- Card 4: Perlu Verifikasi Admin --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Perlu Verifikasi Admin
                    </span>
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['pending_verification'] }}
                    </div>
                    <div class="text-xs mt-1">
                        @if(in_array(auth()->user()?->role, ['super_admin', 'admin'], true))
                            <a href="{{ url('/admin/verification-queue') }}" class="font-bold text-sky-700 dark:text-sky-400 hover:text-sky-900 dark:hover:text-sky-300 hover:underline inline-flex items-center gap-1">
                                <span>Tinjau Antrean</span>
                                <span>&rarr;</span>
                            </a>
                        @else
                            <span class="text-slate-500 font-normal">Rekomendasi algoritma &ge; 75%</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Toolbar: Kategori Tabs & Filter Formal --}}
        <div class="bg-white dark:bg-slate-800 p-3.5 rounded-lg border border-slate-200 dark:border-slate-700 space-y-3 transition-colors">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- Segmented Category Tabs Formal --}}
                <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900 rounded-md border border-slate-200 dark:border-slate-700 gap-1 text-xs font-semibold">
                    <button
                        type="button"
                        wire:click="setCategory(null)"
                        class="px-3.5 py-1.5 rounded transition {{ $category === null ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-600 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                        Semua LK ({{ $allLksCount }})
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('internal')"
                        class="px-3.5 py-1.5 rounded transition {{ $category === 'internal' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-600 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                        Internal (ZI, PEKPPP, KIP, SAKIP)
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('eksternal')"
                        class="px-3.5 py-1.5 rounded transition {{ $category === 'eksternal' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold border border-slate-300 dark:border-slate-600 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                        Eksternal (Data Sekunder)
                    </button>
                </div>

                {{-- Action: Ekspor Data Formal --}}
                <div>
                    <button
                        type="button"
                        wire:click="exportMatrix"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        <svg class="w-4 h-4 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Matriks (CSV/XLSX)</span>
                    </button>
                </div>
            </div>

            {{-- Filter Grid Formal --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2.5 border-t border-slate-100 dark:border-slate-700">
                <div class="sm:col-span-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Cari nomor SK, nama dokumen, atau indikator..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs sm:text-sm rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-sky-600"
                        />
                    </div>
                </div>

                <div>
                    <select
                        wire:model.live="document_type"
                        class="w-full py-1.5 px-3 text-xs sm:text-sm font-semibold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-sky-600">
                        <option value="">Jenis Dokumen: Semua</option>
                        <option value="SK">Surat Keputusan (SK)</option>
                        <option value="SOP">Standar Operasional Prosedur (SOP)</option>
                        <option value="Laporan">Laporan Kinerja / Kegiatan</option>
                        <option value="Surat Edaran">Surat Edaran</option>
                        <option value="Data">Data / Statistik</option>
                    </select>
                </div>

                <div>
                    <select
                        wire:model.live="period"
                        class="w-full py-1.5 px-3 text-xs sm:text-sm font-semibold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-sky-600">
                        <option value="2026">Periode Evaluasi: 2026</option>
                        <option value="2025">Periode Evaluasi: 2025</option>
                        <option value="2024">Periode Evaluasi: 2024</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Matriks Tabel Monitor Formal --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="overflow-x-auto relative">
                <table class="matrix-table">
                    <thead>
                        {{-- Super Header Kategori LK (MON-01) Formal --}}
                        <tr class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                            <th rowspan="2" class="matrix-col-code p-3 border-r border-b border-slate-300 dark:border-slate-700 text-center text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Kode
                            </th>
                            <th rowspan="2" class="matrix-col-desc p-3 border-r border-b border-slate-300 dark:border-slate-700 text-left text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Uraian Indikator &amp; Kebutuhan Bukti
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
                                <th class="p-2.5 min-w-[105px] border-r border-b border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold" title="{{ $lk->name }}">
                                    <span class="block text-xs font-mono font-bold">{{ $lk->code }}</span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal truncate max-w-[95px] mx-auto">{{ $lk->name }}</span>
                                </th>
                            @endforeach

                            @foreach($eksternalLks as $lk)
                                <th class="p-2.5 min-w-[105px] border-r border-b border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold" title="{{ $lk->name }}">
                                    <span class="block text-xs font-mono font-bold">{{ $lk->code }}</span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal truncate max-w-[95px] mx-auto">{{ $lk->name }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750 transition duration-75">
                                {{-- Kolom 1: Kode Dokumen --}}
                                <td class="matrix-col-code p-3 border-r border-b border-slate-200 dark:border-slate-700 text-center font-bold text-slate-900 dark:text-slate-100 font-mono text-xs sm:text-sm">
                                    {{ $row['code'] ?? 'DOK' }}
                                </td>

                                {{-- Kolom 2: Uraian & Kebutuhan Bukti --}}
                                <td class="matrix-col-desc p-3.5 border-r border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="matrix-doc-title text-slate-900 dark:text-slate-100">
                                                {{ $row['document_name'] }}
                                            </span>
                                            @if(!empty($row['document_type']))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600">
                                                    {{ $row['document_type'] }}
                                                </span>
                                            @endif
                                        </div>

                                        @if(!empty($row['indicators_summary']))
                                            <p class="matrix-doc-desc text-slate-600 dark:text-slate-400 line-clamp-2">
                                                {{ $row['indicators_summary'] }}
                                            </p>
                                        @endif

                                        @if(!empty($row['document_number']))
                                            <span class="text-[11px] font-mono font-medium text-slate-500 dark:text-slate-400">
                                                No SK: {{ $row['document_number'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Kolom 3: PIC Satker Formal --}}
                                <td class="p-3 border-r border-b border-slate-200 dark:border-slate-700 text-center whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600">
                                        {{ $row['pic'] ?? 'Bagian Umum' }}
                                    </span>
                                </td>

                                {{-- Kolom Internal --}}
                                @foreach($internalLks as $lk)
                                    @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null]; @endphp
                                    <td class="p-2.5 text-center border-r border-b border-slate-200 dark:border-slate-700 align-middle">
                                        @if($cell['has_match'])
                                            @if(!empty($cell['drive_url']))
                                                <a 
                                                    href="{{ $cell['drive_url'] }}" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    class="gov-matrix-check-btn gap-1"
                                                    aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                    title="Buka Google Drive Bukti Dukung ({{ $lk->code }})">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            @else
                                                <button 
                                                    type="button"
                                                    wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                                    class="gov-matrix-check-btn cursor-pointer"
                                                    aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                    title="Klik untuk melihat rincian indikator cocok">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach

                                {{-- Kolom Eksternal --}}
                                @foreach($eksternalLks as $lk)
                                    @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '', 'drive_url' => null]; @endphp
                                    <td class="p-2.5 text-center border-r border-b border-slate-200 dark:border-slate-700 align-middle">
                                        @if($cell['has_match'])
                                            @if(!empty($cell['drive_url']))
                                                <a 
                                                    href="{{ $cell['drive_url'] }}" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    class="gov-matrix-check-btn gap-1"
                                                    aria-label="Buka Google Drive Bukti Dukung {{ $lk->code }} untuk {{ $row['document_name'] }}"
                                                    title="Buka Google Drive Bukti Dukung ({{ $lk->code }})">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            @else
                                                <button 
                                                    type="button"
                                                    wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                                    class="gov-matrix-check-btn cursor-pointer"
                                                    aria-label="Lihat rincian indikator cocok {{ $lk->code }}"
                                                    title="Klik untuk melihat rincian indikator cocok">
                                                    <span>{{ $cell['symbol'] }}</span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + $internalLks->count() + $eksternalLks->count() }}" class="p-10 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada dokumen yang ditemukan</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Silakan sesuaikan kata kunci pencarian atau reset filter di atas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer Pagination Formal --}}
            <div class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <strong class="text-slate-900 dark:text-slate-100">{{ $paginator->firstItem() ?? 0 }}</strong> s.d. <strong class="text-slate-900 dark:text-slate-100">{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-900 dark:text-slate-100">{{ $totalDocs }}</strong> Dokumen
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
                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
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
                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Halaman Selanjutnya"
                        aria-label="Selanjutnya"
                    >
                        Berikutnya &rsaquo;
                    </button>
                </div>
            </div>
        </div>

        {{-- Legenda Matriks Formal --}}
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 pt-1 px-0.5">
            <div class="flex items-center gap-4">
                <span class="font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">Keterangan Matriks:</span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-emerald-700 dark:text-emerald-400 font-extrabold text-xs">✔</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">Eviden Terhubung &amp; Terpetakan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"></span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">Sel Kosong = Belum Ada Bukti</span>
                </div>
            </div>

            <div>
                <span class="font-medium text-slate-400 dark:text-slate-500">Websinkronisasi &bull; Badan Pusat Statistik RI</span>
            </div>
        </div>

        {{-- Modal Drilldown Detail Centang Formal --}}
        @if($showDrilldown && $drilldownData)
            <div 
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                wire:click.self="closeDrilldown"
                role="dialog"
                aria-modal="true"
            >
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200 dark:border-slate-700 transition-colors">
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
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700 transition focus:outline-none focus:ring-1 focus:ring-slate-400"
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
                            <div class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 space-y-2">
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
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold transition focus:outline-none focus:ring-1 focus:ring-sky-500 shadow-2xs"
                                            >
                                                <span>Buka Tautan Google Drive</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-slate-400 dark:text-slate-500 italic">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            class="text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 font-bold text-xs cursor-pointer underline focus:outline-none"
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
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition focus:outline-none"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>