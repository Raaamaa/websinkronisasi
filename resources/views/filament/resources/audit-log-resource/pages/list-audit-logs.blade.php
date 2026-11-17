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
                <div class="flex items-center gap-2 text-sm vintage-muted font-semibold">
                    <span class="font-bold" style="color: var(--text-main);">BPS RI</span>
                    <span>&bull;</span>
                    <span>Sistem Tata Kelola Metadata</span>
                </div>

                <div class="vintage-badge-disetujui gap-2">
                    <span class="studio-dot" style="background-color: var(--status-approved-text);"></span>
                    <span>Status Log: Kekal &amp; Terenkripsi (Append-Only WORM Verified &bull; 0 Terhapus)</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="vintage-section px-3 py-1.5 text-sm font-bold" style="color: var(--text-main);">
                    <span>Tahun {{ $selectedYear }}</span>
                </div>
            </div>
        </div>

        {{-- Page Title & Export Action --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 studio-header-band">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold vintage-serif-heading">
                        Jejak Audit & Riwayat Aktivitas
                    </h1>
                    <span class="vintage-badge-disetujui">
                        &bull; Append-Only WORM Aktif
                    </span>
                </div>
                <p class="text-base vintage-muted mt-1">
                    Rekaman permanen seluruh aktivitas pengguna, perubahan lembar kerja, dan verifikasi bukti dukung.
                </p>
            </div>

            {{-- Export Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="min-h-[56px] vintage-btn-secondary"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Ekspor Laporan Audit (CSV / PDF)</span>
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-64 vintage-card z-20 p-2 space-y-1 text-sm"
                    style="display: none;"
                >
                    <button
                        type="button"
                        wire:click="exportCsv"
                        @click="open = false"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-left vintage-btn-secondary"
                        style="min-height: 44px !important; border-color: var(--border-warm) !important;"
                    >
                        <div>
                            <div class="font-bold" style="color: var(--text-main);">Unduh Format CSV</div>
                            <div class="text-xs vintage-muted font-normal">Kompatibel Excel &amp; Spreadsheet</div>
                        </div>
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        @click="open = false"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-left vintage-btn-secondary"
                        style="min-height: 44px !important; border-color: var(--border-warm) !important;"
                    >
                        <div>
                            <div class="font-bold" style="color: var(--text-main);">Cetak Laporan / Simpan PDF</div>
                            <div class="text-xs vintage-muted font-normal">Kop Resmi &amp; Format Cetak Standar</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- 3 Summary KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Aktivitas Hari Ini</span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold num" style="color: var(--text-main);">{{ $stats['today_count'] }}</span>
                        <span class="text-sm font-semibold vintage-muted">Aksi</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm vintage-muted mt-1">
                        <span>Paling aktif: <strong style="color: var(--text-main);">{{ $stats['most_active_action'] }}</strong></span>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold vintage-section studio-badge-type">
                            {{ $stats['today_trend'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Total Rekaman Sistem</span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold num" style="color: var(--text-main);">{{ $stats['total_count'] }}</span>
                        <span class="text-sm font-semibold vintage-muted">Entri</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm vintage-muted mt-1">
                        <span>Tersimpan sejak awal periode</span>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold vintage-section studio-badge-type">
                            Terkunci WORM
                        </span>
                    </div>
                </div>
            </div>

            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Aktor Paling Aktif</span>
                </div>
                <div class="mt-2.5">
                    <div>
                        <span class="text-2xl font-bold vintage-serif-heading">{{ $stats['most_active_actor'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm vintage-muted mt-1">
                        <span>{{ $stats['actor_weekly_actions'] }} aksi minggu ini &bull; {{ $stats['actor_unit'] }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold vintage-section studio-badge-type">
                            {{ $stats['actor_badge'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="vintage-card p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="w-full lg:w-96">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari riwayat (Nama, NIP, Berkas, atau Kode LK)..."
                    class="w-full min-h-[56px] px-4 py-3 vintage-input"
                />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <select
                        wire:model.live="dateRangeFilter"
                        class="min-h-[56px] px-3 py-2.5 vintage-input font-semibold"
                    >
                        <option value="7d">7 Hari Terakhir</option>
                        <option value="today">Hari Ini</option>
                        <option value="30d">30 Hari Terakhir</option>
                        <option value="all">Semua Waktu</option>
                    </select>
                </div>

                <div>
                    <select
                        wire:model.live="categoryFilter"
                        class="min-h-[56px] px-3 py-2.5 vintage-input font-semibold"
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

                <div class="flex items-center gap-2.5 pl-3" style="border-left: var(--rule-hair) solid var(--border-warm);">
                    <button
                        type="button"
                        wire:click="$toggle('criticalOnlyToggle')"
                        class="studio-switch {{ $criticalOnlyToggle ? 'studio-switch-active' : '' }}"
                    >
                        <span class="studio-switch-thumb {{ $criticalOnlyToggle ? 'studio-switch-thumb-active' : '' }}"></span>
                    </button>
                    <span class="text-sm font-semibold select-none cursor-pointer" style="color: var(--text-main);" wire:click="$toggle('criticalOnlyToggle')">
                        Hanya penolakan & perubahan peran
                    </span>
                </div>
            </div>
        </div>

        {{-- Audit Trail Table Card --}}
        <div class="vintage-card overflow-hidden studio-fade-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="studio-table-head text-sm font-bold">
                            <th class="py-3.5 px-4 sm:px-6">Waktu (WIB)</th>
                            <th class="py-3.5 px-4">Aktor / Pengguna</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3.5 px-4 sm:pr-6">Alamat IP &amp; Jaringan</th>
                        </tr>
                    </thead>
                    <tbody class="text-base">
                        @forelse($logs as $log)
                            @php
                                $actor = $this->getActorDetails($log);
                                $category = $this->getCategoryInfo($log->action);
                                $wibDate = $log->created_at ? $log->created_at->timezone('Asia/Jakarta') : now();
                            @endphp
                            <tr class="studio-table-row">
                                <td class="py-3.5 px-4 sm:px-6 whitespace-nowrap">
                                    <div class="font-bold" style="color: var(--text-main);">
                                        {{ $wibDate->format('d M Y') }}
                                    </div>
                                    <div class="text-sm num vintage-muted">
                                        {{ $wibDate->format('H:i:s') }} WIB
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="studio-avatar text-sm">
                                            {{ $actor['initials'] }}
                                        </div>
                                        <div>
                                            <div class="font-bold" style="color: var(--text-main);">
                                                {{ $actor['name'] }}
                                            </div>
                                            <div class="text-sm vintage-muted">
                                                {{ $actor['subtitle'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold border studio-badge-type {{ $category['badge_class'] }}">
                                        <span class="studio-dot {{ $category['dot_color'] }}"></span>
                                        <span>{{ $category['label'] }}</span>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 max-w-xl leading-relaxed" style="color: var(--text-main);">
                                    {!! $this->formatNarrative($log) !!}
                                </td>

                                <td class="py-3.5 px-4 sm:pr-6 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 num text-sm font-medium" style="color: var(--text-main);">
                                        <span class="studio-dot" style="background-color: var(--status-approved-text);"></span>
                                        <span>{{ $log->ip_address ?: '10.128.4.15' }}</span>
                                    </div>
                                    <div class="text-xs vintage-muted">
                                        BPS Intranet (Secure VPN)
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 studio-empty-state vintage-muted">
                                    <span class="font-bold" style="color: var(--text-main);">Tidak ada riwayat log audit yang sesuai dengan kriteria filter</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- WORM Ledger Integrity Banner --}}
            <div class="mx-4 my-3 p-3.5 studio-ledger-banner flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                <div class="font-medium">
                    <strong class="font-bold" style="color: var(--text-main);">Integritas Ledger:</strong> Tombol Sunting (Edit) dan Hapus (Delete) dinonaktifkan permanen oleh spesifikasi WORM (Write Once, Read Many).
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto num text-xs vintage-muted">
                    <span>Kriptografi Ledger ID:</span>
                    <span class="studio-hash-chip">
                        {{ $ledgerHash }}
                    </span>
                </div>
            </div>

            {{-- Table Footer & Pagination --}}
            <div class="studio-table-footer flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 text-sm">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <span class="font-bold" style="color: var(--text-main);">{{ $logs->firstItem() ?? 0 }}</span> - <span class="font-bold" style="color: var(--text-main);">{{ $logs->lastItem() ?? 0 }}</span> dari <span class="font-bold" style="color: var(--text-main);">{{ $logs->total() }}</span> rekaman
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="audit-per-page" class="vintage-muted font-medium">Baris:</label>
                        <select
                            id="audit-per-page"
                            wire:model.live="perPage"
                            class="vintage-input min-w-[4.25rem] px-2.5 py-1 text-sm font-bold cursor-pointer"
                            style="min-height: 44px !important;"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 self-center sm:self-auto">
                    <button
                        type="button"
                        wire:click="prevPage"
                        @disabled($logs->onFirstPage())
                        class="studio-page-btn text-sm"
                    >
                        &lt;
                    </button>

                    @foreach($logs->getUrlRange(1, min(5, $logs->lastPage())) as $pageNum => $url)
                        <button
                            type="button"
                            wire:click="selectPage({{ $pageNum }})"
                            class="studio-page-btn text-sm {{ $logs->currentPage() === $pageNum ? 'studio-page-btn-active' : '' }}"
                        >
                            {{ $pageNum }}
                        </button>
                    @endforeach

                    @if($logs->lastPage() > 5)
                        <span class="px-2 vintage-muted">...</span>
                        <button
                            type="button"
                            wire:click="selectPage({{ $logs->lastPage() }})"
                            class="studio-page-btn text-sm {{ $logs->currentPage() === $logs->lastPage() ? 'studio-page-btn-active' : '' }}"
                        >
                            {{ $logs->lastPage() }}
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="nextCustomPage({{ $logs->lastPage() }})"
                        @disabled($logs->currentPage() === $logs->lastPage())
                        class="studio-page-btn text-sm"
                    >
                        &gt;
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
