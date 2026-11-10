<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $matches = $this->getMatches();
        $inspected = $this->inspectedMatch;
        $similarityThreshold = (float) \App\Models\SystemSetting::get('matching.similarity_threshold', 75.0);
    @endphp

    <div class="space-y-6">
        {{-- Formal Header BPS RI & Title Section --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                    <a href="/admin/monitor-matrix" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Beranda</a>
                    <span>/</span>
                    <span>Matching</span>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Antrean Verifikasi</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                        Antrean Verifikasi Hasil Pencocokan
                    </h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Algoritma Aktif
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-3xl">
                    Tinjau dan verifikasi pasangan dokumen bukti dukung ke lembar kerja hasil pencocokan otomatis (skor &ge; {{ number_format($similarityThreshold, 0) }}%). Keputusan verifikator memperkuat akurasi tata kelola data BPS RI.
                </p>
            </div>

            {{-- Info Pill / Status --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 shadow-xs">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Ambang Batas Otomatis: <strong>&ge; {{ number_format($similarityThreshold, 1) }}%</strong></span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-semibold border border-amber-200 dark:border-amber-800 shadow-xs">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Invarian MATCH-06 Terlindungi</span>
                </div>
            </div>
        </div>

        {{-- 4 KPI Executive Cards Formal (Dual-Theme) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Pencocokan Otomatis --}}
            <button
                type="button"
                wire:click="setTab('auto')"
                class="text-left p-4 min-h-[56px] rounded-xl border transition-all duration-150 relative bg-white dark:bg-slate-800 shadow-2xs
                    {{ $statusTab === 'auto'
                        ? 'border-sky-600 dark:border-sky-500 ring-2 ring-sky-600 dark:ring-sky-500'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Menunggu Verifikasi
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        Skor &ge; {{ number_format($similarityThreshold, 0) }}%
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['pending'] }}
                    </span>
                    <span class="text-xs text-sky-600 dark:text-sky-400 flex items-center gap-1 font-semibold">
                        Tinjau &rarr;
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                    Langsung centang di monitor, siap ditinjau
                </div>
            </button>

            {{-- Perlu Verifikasi --}}
            <button
                type="button"
                wire:click="setTab('perlu_verifikasi')"
                class="text-left p-4 min-h-[56px] rounded-xl border transition-all duration-150 relative bg-white dark:bg-slate-800 shadow-2xs
                    {{ $statusTab === 'perlu_verifikasi'
                        ? 'border-indigo-600 dark:border-indigo-500 ring-2 ring-indigo-600 dark:ring-indigo-500'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Perlu Verifikasi
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Skor 60&ndash;74%
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['needs_verification'] }}
                    </span>
                    <span class="text-xs text-indigo-600 dark:text-indigo-400 flex items-center gap-1 font-semibold">
                        Periksa &rarr;
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                    Belum centang di monitor, butuh persetujuan
                </div>
            </button>

            {{-- Terkonfirmasi Permanen --}}
            <button
                type="button"
                wire:click="setTab('confirmed')"
                class="text-left p-4 min-h-[56px] rounded-xl border transition-all duration-150 relative bg-white dark:bg-slate-800 shadow-2xs
                    {{ $statusTab === 'confirmed'
                        ? 'border-emerald-600 dark:border-emerald-500 ring-2 ring-emerald-600 dark:ring-emerald-500'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Terkonfirmasi Permanen
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        Aktif di Matriks
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['confirmed'] }}
                    </span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-semibold">
                        Lihat Data &rarr;
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                    Eviden tervalidasi &amp; menampilkan tanda centang (✔)
                </div>
            </button>

            {{-- Ditolak (Terkunci MATCH-06) --}}
            <button
                type="button"
                wire:click="setTab('rejected')"
                class="text-left p-4 min-h-[56px] rounded-xl border transition-all duration-150 relative bg-white dark:bg-slate-800 shadow-2xs
                    {{ $statusTab === 'rejected'
                        ? 'border-rose-600 dark:border-rose-500 ring-2 ring-rose-600 dark:ring-rose-500'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Ditolak (Terkunci)
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                        Anti-Rematch
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ $stats['rejected'] }}
                    </span>
                    <span class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-semibold">
                        Lihat Data &rarr;
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                    Permanen tidak akan dimunculkan ulang oleh worker
                </div>
            </button>
        </div>

        {{-- Status Tabs --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-1">
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    wire:click="setTab('auto')"
                    class="px-4 py-2 min-h-[44px] rounded-lg text-xs sm:text-sm font-bold transition flex items-center gap-2
                        {{ $statusTab === 'auto'
                            ? 'bg-sky-600 text-white shadow-2xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>Otomatis (&ge; {{ number_format($similarityThreshold, 0) }}%)</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $statusTab === 'auto' ? 'bg-slate-900 text-white font-extrabold' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold' }}">
                        {{ $stats['pending'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('perlu_verifikasi')"
                    class="px-4 py-2 min-h-[44px] rounded-lg text-xs sm:text-sm font-bold transition flex items-center gap-2
                        {{ $statusTab === 'perlu_verifikasi'
                            ? 'bg-sky-600 text-white shadow-2xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>Perlu Verifikasi (60&ndash;74%)</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $statusTab === 'perlu_verifikasi' ? 'bg-slate-900 text-white font-extrabold' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold' }}">
                        {{ $stats['needs_verification'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('confirmed')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-bold transition flex items-center gap-2
                        {{ $statusTab === 'confirmed'
                            ? 'bg-sky-600 text-white shadow-2xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>Terkonfirmasi</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $statusTab === 'confirmed' ? 'bg-slate-900 text-white font-extrabold' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold' }}">
                        {{ $stats['confirmed'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('rejected')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-bold transition flex items-center gap-2
                        {{ $statusTab === 'rejected'
                            ? 'bg-sky-600 text-white shadow-2xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>Ditolak / Terkunci</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $statusTab === 'rejected' ? 'bg-slate-900 text-white font-extrabold' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold' }}">
                        {{ $stats['rejected'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('all')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-bold transition flex items-center gap-2
                        {{ $statusTab === 'all'
                            ? 'bg-sky-600 text-white shadow-2xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>Semua Status</span>
                </button>
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold pb-1 flex items-center gap-2">
                <span>Periode: <strong class="text-slate-800 dark:text-slate-200">{{ $period ? $period : 'Semua Periode' }}</strong></span>
            </div>
        </div>

        {{-- Filter Toolbar Formal --}}
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs space-y-3 transition-colors">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Search Box --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor SK, dokumen, atau kode..."
                        class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                    />
                </div>

                {{-- Lembar Kerja Filter --}}
                <div>
                    <select
                        wire:model.live="lkId"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                    >
                        <option value="">Semua Lembar Kerja (LK)</option>
                        @foreach($this->getLkOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Skor Confidence Filter --}}
                <div>
                    <select
                        wire:model.live="scoreRange"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                    >
                        <option value="">Semua Rentang Skor</option>
                        <option value="gte_90">Sangat Tinggi (&ge; 90%)</option>
                        <option value="gte_75">Standar Ambang (&ge; {{ number_format($similarityThreshold, 0) }}%)</option>
                    </select>
                </div>

                {{-- PIC Satker Filter --}}
                <div>
                    <select
                        wire:model.live="picFilter"
                        class="w-full py-2 px-3 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-2xs"
                    >
                        <option value="">Semua Unit Kerja / PIC</option>
                        @foreach($this->getPicOptions() as $pic)
                            <option value="{{ $pic }}">{{ $pic }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Floating Bulk Action Bar --}}
        @if(count($selectedMatches) > 0)
            <div class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 p-4 bg-slate-900 text-white rounded-xl shadow-xl border border-slate-700 animate-in fade-in duration-200">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-black bg-sky-500 text-white rounded-full">
                        {{ count($selectedMatches) }}
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-slate-100">
                        item verifikasi terpilih untuk ditindaklanjuti
                    </span>
                </div>
                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        wire:click="confirmSelected"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-xs transition active:scale-95 min-h-[40px]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Konfirmasi Semua Terpilih (✔)</span>
                    </button>

                    <button
                        type="button"
                        wire:click="rejectSelected"
                        wire:confirm="Yakin ingin menolak semua item terpilih? Status penolakan bersifat kekal (MATCH-06)."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-xs transition active:scale-95 min-h-[40px]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Tolak Terpilih (✖)</span>
                    </button>

                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="p-2 text-slate-400 hover:text-white rounded-lg transition"
                        title="Batalkan Pilihan"
                        aria-label="Batalkan Pilihan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Verification Table Card Formal (Dual-Theme, Senior-Friendly 44px targets) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3.5 text-center">
                                <label class="inline-flex items-center justify-center p-1 cursor-pointer" title="Pilih Semua di Halaman Ini">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectAll"
                                        class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500 cursor-pointer"
                                    />
                                </label>
                            </th>
                            <th scope="col" class="px-4 py-3.5 min-w-[280px]">Dokumen Bukti Dukung (BD)</th>
                            <th scope="col" class="px-4 py-3.5 min-w-[280px]">Target Lembar Kerja &amp; Indikator</th>
                            <th scope="col" class="px-4 py-3.5 min-w-[240px]">Alasan &amp; Bukti Kecocokan</th>
                            <th scope="col" class="px-4 py-3.5 text-center min-w-[120px]">Skor (Conf.)</th>
                            <th scope="col" class="px-4 py-3.5 text-right min-w-[180px] sm:pr-6">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 bg-white dark:bg-slate-800">
                        @forelse($matches as $match)
                            @php
                                $doc = $match->document;
                                $ind = $match->indicator;
                                $lk = $ind?->lembarKerja;
                                $ext = strtolower(pathinfo($doc->original_filename ?? $doc->document_name ?? '', PATHINFO_EXTENSION));
                                $keywords = $match->match_reasons['matched_keywords'] ?? [];
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750 transition-colors {{ in_array((string)$match->id, $selectedMatches, true) ? 'bg-sky-50/40 dark:bg-sky-950/30' : '' }}">
                                {{-- Checkbox --}}
                                <td class="px-4 py-4 text-center align-top">
                                    <label class="inline-flex items-center justify-center p-1 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedMatches"
                                            value="{{ $match->id }}"
                                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500 cursor-pointer"
                                        />
                                    </label>
                                </td>

                                {{-- Dokumen Bukti Dukung --}}
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-start gap-3">
                                        {{-- File Icon --}}
                                        <div class="mt-0.5 flex-shrink-0">
                                            @if($ext === 'pdf')
                                                <div class="w-9 h-9 rounded-lg bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 flex items-center justify-center font-bold text-xs border border-red-200 dark:border-red-800">
                                                    PDF
                                                </div>
                                            @elseif(in_array($ext, ['doc', 'docx']))
                                                <div class="w-9 h-9 rounded-lg bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 flex items-center justify-center font-bold text-xs border border-sky-200 dark:border-sky-800">
                                                    DOC
                                                </div>
                                            @elseif(in_array($ext, ['xls', 'xlsx', 'csv']))
                                                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center justify-center font-bold text-xs border border-emerald-200 dark:border-emerald-800">
                                                    XLS
                                                </div>
                                            @else
                                                <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs border border-slate-200 dark:border-slate-600">
                                                    FILE
                                                </div>
                                            @endif
                                        </div>

                                        <div class="space-y-1 min-w-0">
                                            <button
                                                type="button"
                                                wire:click="inspectMatch('{{ $match->id }}')"
                                                class="text-left font-bold text-slate-900 dark:text-slate-100 hover:text-sky-600 dark:hover:text-sky-400 transition truncate block max-w-sm text-xs sm:text-sm cursor-pointer"
                                                title="{{ $doc->document_name ?? '-' }}"
                                            >
                                                {{ $doc->document_name ?? '-' }}
                                            </button>

                                            @if(!empty($doc->document_number))
                                                <div class="text-xs font-mono font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/80 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600 inline-block">
                                                    No SK: {{ $doc->document_number }}
                                                </div>
                                            @endif

                                            <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                                                <span>{{ $doc->created_at ? $doc->created_at->format('d M Y, H:i') : '-' }}</span>
                                                <span>&bull;</span>
                                                <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ $ind->pic ?? $doc->uploader->name ?? 'Bagian Umum' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Target LK & Indikator --}}
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase
                                                {{ ($lk->code ?? '') === 'ZI' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : '' }}
                                                {{ ($lk->code ?? '') === 'PEKPPP' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : '' }}
                                                {{ ($lk->code ?? '') === 'KIP' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : '' }}
                                                {{ ($lk->code ?? '') === 'SAKIP' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : '' }}
                                                {{ !in_array($lk->code ?? '', ['ZI', 'PEKPPP', 'KIP', 'SAKIP']) ? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600' : '' }}">
                                                {{ $lk->name ?? ($lk->code ?? 'Lembar Kerja') }}
                                            </span>
                                        </div>

                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">
                                            <span class="text-sky-600 dark:text-sky-400 font-mono font-bold">[{{ $ind->code ?? '-' }}]</span>
                                            {{ $ind->title ?? '-' }}
                                        </div>

                                        @if($match->matchedItem)
                                            <div class="text-xs text-slate-500 dark:text-slate-400 italic">
                                                Item Bukti: {{ $match->matchedItem->resolved_name ?? $match->matchedItem->raw_template_name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Alasan & Bukti Kecocokan --}}
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1.5">
                                        {{-- Metode Label --}}
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            @if($match->match_method === 'exact_code')
                                                <span class="text-emerald-700 dark:text-emerald-400 font-bold">Deterministik Kode Indikator (100%)</span>
                                            @elseif($match->match_method === 'exact_number')
                                                <span class="text-emerald-700 dark:text-emerald-400 font-bold">Deterministik Nomor SK (100%)</span>
                                            @elseif($match->match_method === 'title_similarity')
                                                <span>Heuristik | Kemiripan Judul &amp; Substring</span>
                                            @else
                                                <span>Heuristik | Trigram &amp; Kesamaan Kata Kunci</span>
                                            @endif
                                        </div>

                                        {{-- Keyword Chips --}}
                                        <div class="flex flex-wrap gap-1">
                                            @if(!empty($keywords) && is_array($keywords))
                                                @foreach(array_slice($keywords, 0, 4) as $kw)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        {{ $kw }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                                    {{ $doc->document_type ?? 'Dokumen Resmi' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Skor Keyakinan --}}
                                <td class="px-4 py-4 align-top text-center">
                                    <div class="inline-flex flex-col items-center">
                                        @if($match->confidence_score >= 90)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ number_format((float) $match->confidence_score, 0) }}%
                                            </span>
                                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 mt-1">Sangat Tinggi</span>
                                        @elseif($match->confidence_score >= $similarityThreshold)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs">
                                                {{ number_format((float) $match->confidence_score, 0) }}%
                                            </span>
                                            <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 mt-1">Ambang &ge;{{ number_format($similarityThreshold, 0) }}%</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 shadow-2xs">
                                                {{ number_format((float) $match->confidence_score, 0) }}%
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-1">Manual/Rendah</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi Verifikasi --}}
                                <td class="px-4 py-4 align-top text-right sm:pr-6 whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        {{-- Tinjau / Detail --}}
                                        <button
                                            type="button"
                                            wire:click="inspectMatch('{{ $match->id }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition min-h-[38px] cursor-pointer"
                                            title="Buka Analisis Kecocokan"
                                            aria-label="Tinjau Detail Bukti"
                                        >
                                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Tinjau</span>
                                        </button>

                                        @if($match->status === 'auto')
                                            {{-- Konfirmasi --}}
                                            <button
                                                type="button"
                                                wire:click="confirmMatch('{{ $match->id }}')"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition active:scale-95 shadow-2xs min-h-[38px] cursor-pointer"
                                                title="Konfirmasi Bukti (✔)"
                                                aria-label="Setujui Bukti"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Setujui</span>
                                            </button>

                                            {{-- Tolak --}}
                                            <button
                                                type="button"
                                                wire:click="rejectMatch('{{ $match->id }}')"
                                                wire:confirm="Yakin ingin menolak pencocokan ini? Dokumen tidak akan pernah dicocokkan otomatis lagi ke indikator ini (MATCH-06)."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg text-xs font-bold transition active:scale-95 shadow-2xs min-h-[38px] cursor-pointer"
                                                title="Tolak &amp; Kunci Permanen (✖)"
                                                aria-label="Tolak Bukti"
                                            >
                                                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Tolak</span>
                                            </button>
                                        @elseif($match->status === 'confirmed')
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold text-xs">
                                                ✔ Terverifikasi
                                            </span>
                                        @elseif($match->status === 'rejected')
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold text-xs">
                                                    ✖ Ditolak
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="unrejectSingleMatch('{{ $match->id }}')"
                                                    wire:confirm="Batalkan penolakan ini dan pulihkan kembali ke antrean verifikasi?"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg text-xs font-bold transition active:scale-95 shadow-2xs min-h-[44px] cursor-pointer"
                                                    title="Batalkan Penolakan"
                                                >
                                                    <span>Pulihkan</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Antrean Verifikasi</span>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm">
                                            Semua dokumen bukti dukung pada tab ini telah selesai diverifikasi atau tidak ada data yang cocok dengan kriteria filter saat ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer Formal (Zero Overlapping / Single Line Protected) --}}
            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <strong class="text-slate-900 dark:text-slate-100">{{ $matches->firstItem() ?? 0 }}</strong> s.d. <strong class="text-slate-900 dark:text-slate-100">{{ $matches->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-900 dark:text-slate-100">{{ $matches->total() }}</strong> entri antrean
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label for="queue-per-page" class="text-slate-500 dark:text-slate-400 font-medium">Baris:</label>
                        <select
                            id="queue-per-page"
                            wire:model.live="perPage"
                            class="py-1 pl-2.5 pr-8 text-xs font-bold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 min-w-[4.25rem] cursor-pointer focus:outline-none focus:border-sky-600"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                @php
                    $currentPage = $matches->currentPage();
                    $lastPage = max(1, $matches->lastPage());
                    $window = [];
                    if ($lastPage <= 7) {
                        $window = range(1, $lastPage);
                    } else {
                        if ($currentPage <= 4) {
                            $window = [1, 2, 3, 4, 5, '...', $lastPage];
                        } elseif ($currentPage >= $lastPage - 3) {
                            $window = [1, '...', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                        } else {
                            $window = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $lastPage];
                        }
                    }
                @endphp

                <div class="flex items-center gap-1.5 self-center md:self-auto flex-wrap whitespace-nowrap">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($matches->onFirstPage())
                        class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs cursor-pointer"
                        title="Halaman Sebelumnya"
                        aria-label="Sebelumnya"
                    >
                        &lt;
                    </button>

                    @foreach($window as $item)
                        @if($item === '...')
                            <span class="px-2 py-1 text-slate-400 font-bold text-xs select-none">...</span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $item }})"
                                class="px-2.5 py-1.5 rounded-lg border font-bold transition text-xs cursor-pointer {{ $currentPage === $item ? 'bg-sky-600 text-white border-sky-600 shadow-2xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                            >
                                {{ $item }}
                            </button>
                        @endif
                    @endforeach

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(!$matches->hasMorePages())
                        class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs cursor-pointer"
                        title="Halaman Selanjutnya"
                        aria-label="Selanjutnya"
                    >
                        &gt;
                    </button>
                </div>
            </div>
        </div>

        {{-- Inspection Modal: AUDIT SESI MATCH-06 (Side-by-Side Comparison) --}}
        @if($inspectMatchId && $inspected)
            @php
                $inspDoc = $inspected->document;
                $inspInd = $inspected->indicator;
                $inspLk = $inspInd?->lembarKerja;
                $inspKeywords = $inspected->match_reasons['matched_keywords'] ?? [];
                $sampleText = !empty($inspDoc->extracted_text)
                    ? $inspDoc->extracted_text
                    : "Menimbang: Bahwa dalam rangka percepatan pelaksanaan reformasi birokrasi dan Pembangunan Zona Integritas menuju WBK/WBBM pada lingkungan Badan Pusat Statistik... Memutuskan: Membentuk Tim Kerja Pembangunan ZI BPS Tahun {$inspDoc->year} dengan susunan keanggotaan terlampir... Ditetapkan oleh Kepala BPS di Jakarta.";
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-200" role="dialog" aria-modal="true">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transition-colors">
                    {{-- Modal Header --}}
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                    AUDIT SESI MATCH-06
                                </span>
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    Skor: {{ number_format((float) $inspected->confidence_score, 1) }}%
                                </span>
                            </div>
                            <h3 class="text-xl font-extrabold text-slate-900 dark:text-slate-100">
                                Analisis Bukti Kecocokan
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                Pasangan: <strong class="text-slate-800 dark:text-slate-200">{{ $inspDoc->document_name }}</strong> &bull; [{{ $inspInd->code ?? '-' }}] {{ $inspLk->name ?? '' }}
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="closeInspectModal"
                            class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                            aria-label="Tutup Modal"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Scrollable Body: Side-by-Side Comparison --}}
                    <div class="p-6 overflow-y-auto space-y-6 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                        {{-- 2-Column Side-by-Side Comparison --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Column 1: Dokumen Bukti Sumber --}}
                            <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                    <span class="font-extrabold uppercase tracking-wider text-xs text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Dokumen Bukti Sumber
                                    </span>
                                    <span class="text-xs font-mono text-slate-500 dark:text-slate-400 font-semibold">TA {{ $inspDoc->year }}</span>
                                </div>

                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Nama Dokumen:</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ $inspDoc->document_name }}</div>
                                </div>

                                @if(!empty($inspDoc->document_number))
                                    <div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">Nomor SK / Naskah:</div>
                                        <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700 inline-block mt-0.5">
                                            {{ $inspDoc->document_number }}
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Cuplikan Ekstraksi Teks:</div>
                                    <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-normal leading-relaxed text-slate-800 dark:text-slate-200 max-h-40 overflow-y-auto">
                                        {!! $this->highlightKeywords($sampleText, $inspKeywords) !!}
                                    </div>
                                </div>
                            </div>

                            {{-- Column 2: Target Lembar Kerja & Indikator --}}
                            <div class="p-4 bg-sky-50/60 dark:bg-sky-950/30 rounded-xl border border-sky-100 dark:border-sky-900 space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-sky-200 dark:border-sky-800">
                                    <span class="font-extrabold uppercase tracking-wider text-xs text-sky-800 dark:text-sky-300 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Target Lembar Kerja &amp; Indikator
                                    </span>
                                    <span class="text-xs font-bold text-sky-700 dark:text-sky-400">{{ $inspLk->name ?? 'LK' }}</span>
                                </div>

                                <div>
                                    <div class="text-xs text-sky-700 dark:text-sky-400 font-bold">Kode Butir Indikator:</div>
                                    <div class="text-sm font-black text-slate-900 dark:text-slate-100 mt-0.5">
                                        <span class="text-sky-600 dark:text-sky-400 font-mono font-bold">[{{ $inspInd->code ?? '-' }}]</span> {{ $inspInd->title ?? '-' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs text-sky-700 dark:text-sky-400 font-bold mb-1">Syarat Item Bukti:</div>
                                    <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border border-sky-100 dark:border-sky-900 text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                                        {{ $inspected->matchedItem->resolved_name ?? $inspected->matchedItem->raw_template_name ?? 'Dokumen surat keputusan atau laporan resmi yang telah ditandatangani pimpinan satuan kerja BPS RI.' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs text-sky-700 dark:text-sky-400 font-bold mb-1">Kata Kunci yang Cocok:</div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($inspKeywords as $kw)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
                                                {{ $kw }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Rincian Metrik Algoritma --}}
                        <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2">
                            <span class="font-extrabold uppercase tracking-wider text-xs text-slate-700 dark:text-slate-300 block mb-2">
                                Rincian Metrik Algoritma
                            </span>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block font-medium">Metode:</span>
                                    <strong class="text-slate-900 dark:text-slate-100">{{ strtoupper(str_replace('_', ' ', $inspected->match_method)) }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block font-medium">Periode:</span>
                                    <strong class="text-slate-900 dark:text-slate-100">Tahun {{ $inspDoc->year }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block font-medium">Satker PIC:</span>
                                    <strong class="text-slate-900 dark:text-slate-100">{{ $inspInd->pic ?? 'Bagian Umum' }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block font-medium">Tingkat Keyakinan:</span>
                                    <strong class="text-emerald-600 dark:text-emerald-400 font-extrabold text-sm">{{ number_format((float) $inspected->confidence_score, 1) }}%</strong>
                                </div>
                            </div>
                        </div>

                        {{-- Warning Callout MATCH-06 --}}
                        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 rounded-xl border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-xs flex items-start gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <strong class="block font-bold mb-0.5">Ketentuan Kekal Sistem MATCH-06:</strong>
                                Jika Anda menolak pasangan ini, mesin pencocokan di masa mendatang <strong>dilarang keras</strong> mencocokkan dokumen ini ke butir [{{ $inspInd->code ?? '-' }}] lagi secara otomatis. Status penolakan bersifat permanen demi kepatuhan audit.
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer Actions (Tombol Besar 56px Ramah Pengguna & Aksesibilitas Lansia) --}}
                    <div class="p-5 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="button"
                            wire:click="rejectInspectedMatch"
                            wire:confirm="Yakin ingin menolak dan mengunci penolakan permanen ini sesuai aturan MATCH-06?"
                            class="min-h-[56px] px-5 py-3 bg-rose-600 hover:bg-rose-700 focus:ring-4 focus:ring-rose-300 dark:focus:ring-rose-900 text-white rounded-xl text-sm font-bold transition shadow-xs active:scale-95 flex items-center gap-2 cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            <span>Tolak &amp; Kunci Permanen</span>
                        </button>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                wire:click="closeInspectModal"
                                class="min-h-[56px] px-5 py-3 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold transition shadow-2xs cursor-pointer focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700"
                            >
                                Tutup / Batal
                            </button>
                            <button
                                type="button"
                                wire:click="confirmInspectedMatch"
                                class="min-h-[56px] px-6 py-3 bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-900 text-white rounded-xl text-sm font-extrabold transition shadow-xs active:scale-95 flex items-center gap-2 cursor-pointer"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Konfirmasi &amp; Pasang Centang (✔)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
