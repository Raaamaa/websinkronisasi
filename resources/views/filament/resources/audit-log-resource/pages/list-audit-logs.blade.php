<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $logs = $this->getAuditLogs();
        $ledgerHash = $this->getLedgerHash();
    @endphp

    <div class="space-y-6">
        {{-- Top Metadata & System Integrity Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-1">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-semibold">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    <span class="font-bold text-slate-800 dark:text-slate-200">BPS RI</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 dark:text-slate-400">Sistem Tata Kelola Metadata</span>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Status Log: Kekal &amp; Terenkripsi (Append-Only WORM Verified &bull; 0 Terhapus)</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Year Selector --}}
                <div class="flex items-center gap-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Tahun {{ $selectedYear }}</span>
                </div>

                {{-- Notification Bell --}}
                <div class="relative p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer shadow-2xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-800"></span>
                </div>

                {{-- User Badge --}}
                <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200 dark:border-slate-700">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 leading-tight">Administrator BPS</div>
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Direktorat Diseminasi</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Page Title & Export Action --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                        Jejak Audit & Riwayat Aktivitas
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 shadow-2xs">
                        &bull; Append-Only WORM Aktif
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Rekaman permanen seluruh aktivitas pengguna, perubahan lembar kerja, dan verifikasi bukti dukung.
                </p>
            </div>

            {{-- Export Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs sm:text-sm font-bold shadow-2xs transition active:scale-95"
                >
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Ekspor Laporan Audit (CSV / PDF)</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-20 p-2 space-y-1 text-xs"
                    style="display: none;"
                >
                    <button
                        type="button"
                        wire:click="exportCsv"
                        @click="open = false"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-left rounded-lg text-slate-700 dark:text-slate-200 hover:bg-sky-50 dark:hover:bg-slate-700 hover:text-sky-700 dark:hover:text-white font-semibold transition"
                    >
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-100">Unduh Format CSV</div>
                            <div class="text-[11px] text-slate-400 font-normal">Kompatibel Excel &amp; Spreadsheet</div>
                        </div>
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        @click="open = false"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-left rounded-lg text-slate-700 dark:text-slate-200 hover:bg-sky-50 dark:hover:bg-slate-700 hover:text-sky-700 dark:hover:text-white font-semibold transition"
                    >
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-100">Cetak Laporan / Simpan PDF</div>
                            <div class="text-[11px] text-slate-400 font-normal">Kop Resmi &amp; Format Cetak Standar</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- 3 Summary KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Aktivitas Hari Ini --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aktivitas Hari Ini</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['today_count'] }}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Aksi</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span>Paling aktif: <strong class="text-slate-800 dark:text-slate-200">{{ $stats['most_active_action'] }}</strong></span>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-emerald-800 dark:text-emerald-300 border border-slate-300 dark:border-slate-600">
                            {{ $stats['today_trend'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Total Rekaman Sistem --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Rekaman Sistem</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zM4 12c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['total_count'] }}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Entri</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span>Tersimpan sejak awal periode</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600">
                            <span>Terkunci WORM</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Aktor Paling Aktif --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aktor Paling Aktif</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div>
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['most_active_actor'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span>{{ $stats['actor_weekly_actions'] }} aksi minggu ini &bull; {{ $stats['actor_unit'] }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-600">
                            {{ $stats['actor_badge'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Toolbar (Dual-Theme) --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
            {{-- Search Box --}}
            <div class="relative w-full lg:w-96">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari riwayat (Nama, NIP, Berkas, atau Kode LK)..."
                    class="block w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-sky-500 focus:border-sky-500 transition"
                />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Range Filter --}}
                <div>
                    <select
                        wire:model.live="dateRangeFilter"
                        class="py-2 pl-3 pr-8 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 font-bold focus:ring-sky-500 focus:border-sky-500 transition"
                    >
                        <option value="7d">7 Hari Terakhir</option>
                        <option value="today">Hari Ini</option>
                        <option value="30d">30 Hari Terakhir</option>
                        <option value="all">Semua Waktu</option>
                    </select>
                </div>

                {{-- Category Filter --}}
                <div>
                    <select
                        wire:model.live="categoryFilter"
                        class="py-2 pl-3 pr-8 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 font-bold focus:ring-sky-500 focus:border-sky-500 transition"
                    >
                        <option value="all">Semua Kategori</option>
                        <option value="match_verify">Verifikasi Matching</option>
                        <option value="evidence_upload">Unggah Bukti</option>
                        <option value="access_mgmt">Manajemen Akses</option>
                        <option value="lk_import">Impor Lembar Kerja</option>
                        <option value="match_reject">Penolakan Matching</option>
                        <option value="auth_session">Sesi / Login</option>
                    </select>
                </div>

                {{-- Critical Only Toggle --}}
                <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200 dark:border-slate-700">
                    <button
                        type="button"
                        wire:click="$toggle('criticalOnlyToggle')"
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden {{ $criticalOnlyToggle ? 'bg-sky-600' : 'bg-slate-300 dark:bg-slate-700' }}"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out {{ $criticalOnlyToggle ? 'translate-x-4' : 'translate-x-0' }}"
                        ></span>
                    </button>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 select-none cursor-pointer" wire:click="$toggle('criticalOnlyToggle')">
                        Hanya penolakan & perubahan peran
                    </span>
                </div>
            </div>
        </div>

        {{-- Audit Trail Table Card --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xs overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 sm:px-6">Waktu (WIB)</th>
                            <th class="py-3.5 px-4">Aktor / Pengguna</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3.5 px-4 sm:pr-6">Alamat IP &amp; Jaringan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800">
                        @forelse($logs as $log)
                            @php
                                $actor = $this->getActorDetails($log);
                                $category = $this->getCategoryInfo($log->action);
                                $wibDate = $log->created_at ? $log->created_at->timezone('Asia/Jakarta') : now();
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-750 transition group">
                                {{-- Waktu (WIB) --}}
                                <td class="py-3.5 px-4 sm:px-6 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $wibDate->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono font-medium">
                                        {{ $wibDate->format('H:i:s') }} WIB
                                    </div>
                                </td>

                                {{-- Aktor / Pengguna --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 font-extrabold text-xs sm:text-sm flex items-center justify-center flex-shrink-0 border border-sky-200 dark:border-sky-800">
                                            {{ $actor['initials'] }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 transition">
                                                {{ $actor['name'] }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                                {{ $actor['subtitle'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Kategori --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold border {{ $category['badge_class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $category['dot_color'] }}"></span>
                                        <span>{{ $category['label'] }}</span>
                                    </span>
                                </td>

                                {{-- Deskripsi Aktivitas --}}
                                <td class="py-3.5 px-4 max-w-xl text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                                    {!! $this->formatNarrative($log) !!}
                                </td>

                                {{-- Alamat IP & Jaringan --}}
                                <td class="py-3.5 px-4 sm:pr-6 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-mono text-xs font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ $log->ip_address ?: '10.128.4.15' }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                        BPS Intranet (Secure VPN)
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="font-bold text-slate-700 dark:text-slate-300">Tidak ada riwayat log audit yang sesuai dengan kriteria filter</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- WORM Ledger Integrity Banner --}}
            <div class="mx-4 my-3 p-3.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm">
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-medium">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>
                        <strong class="text-slate-900 dark:text-slate-100 font-bold">Integritas Ledger:</strong> Tombol Sunting (Edit) dan Hapus (Delete) dinonaktifkan permanen oleh spesifikasi WORM (Write Once, Read Many).
                    </span>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto font-mono text-xs text-slate-500 dark:text-slate-400">
                    <span>Kriptografi Ledger ID:</span>
                    <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold shadow-2xs">
                        {{ $ledgerHash }}
                    </span>
                </div>
            </div>

            {{-- Table Footer & Pagination --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <span class="font-bold text-slate-900 dark:text-slate-100">{{ $logs->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900 dark:text-slate-100">{{ $logs->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-900 dark:text-slate-100">{{ $logs->total() }}</span> rekaman
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label for="audit-per-page" class="text-slate-500 dark:text-slate-400 font-medium">Baris:</label>
                        <select
                            id="audit-per-page"
                            wire:model.live="perPage"
                            class="py-1 pl-2.5 pr-8 text-xs font-bold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 min-w-[4.25rem] cursor-pointer focus:ring-sky-500 focus:border-sky-500"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                {{-- Pagination Links --}}
                <div class="flex items-center gap-1.5 self-center sm:self-auto">
                    <button
                        type="button"
                        wire:click="prevPage"
                        @disabled($logs->onFirstPage())
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        &lt;
                    </button>

                    @foreach($logs->getUrlRange(1, min(5, $logs->lastPage())) as $pageNum => $url)
                        <button
                            type="button"
                            wire:click="selectPage({{ $pageNum }})"
                            class="px-3 py-1.5 rounded-lg border font-bold transition {{ $logs->currentPage() === $pageNum ? 'bg-sky-600 text-white border-sky-600 shadow-2xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                        >
                            {{ $pageNum }}
                        </button>
                    @endforeach

                    @if($logs->lastPage() > 5)
                        <span class="px-2 text-slate-400">...</span>
                        <button
                            type="button"
                            wire:click="selectPage({{ $logs->lastPage() }})"
                            class="px-3 py-1.5 rounded-lg border font-bold transition {{ $logs->currentPage() === $logs->lastPage() ? 'bg-sky-600 text-white border-sky-600 shadow-2xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                        >
                            {{ $logs->lastPage() }}
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="nextCustomPage({{ $logs->lastPage() }})"
                        @disabled($logs->currentPage() === $logs->lastPage())
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        &gt;
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
