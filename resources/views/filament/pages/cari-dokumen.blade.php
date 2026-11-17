<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $hasQuery = $this->hasSearchQuery();
            $canManageMarkup = auth()->user()?->hasPermission('evidence.manage_markup');
            $ownSummary = $this->getOwnSubmissionSummary();
        @endphp

        {{-- Judul Halaman & Pilihan Kategori LK --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 studio-header-band">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold vintage-serif-heading">
                    Cari Dokumen Bukti Dukung
                </h1>
                <p class="text-base vintage-muted mt-1">
                    Ketik nama atau nomor dokumen. Klik tanda centang hijau (✔) untuk mengunduh berkas.
                </p>
            </div>

            <div class="studio-segmented">
                <button
                    type="button"
                    wire:click="setCategory(null)"
                    class="studio-tab px-4 py-2.5 text-sm font-bold cursor-pointer {{ $category === null ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}">
                    Semua LK
                </button>
                <button
                    type="button"
                    wire:click="setCategory('internal')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold cursor-pointer {{ $category === 'internal' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}">
                    Internal
                </button>
                <button
                    type="button"
                    wire:click="setCategory('eksternal')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold cursor-pointer {{ $category === 'eksternal' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}">
                    Eksternal
                </button>
            </div>
        </div>

        {{-- Kotak Pencarian & Filter --}}
        <form wire:submit.prevent="search" class="vintage-card studio-glass-search studio-fade-in studio-search-card">
            <div class="studio-search-wrap">
                <svg class="studio-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="Ketikkan nama dokumen, nomor SK, atau kata kunci..."
                    class="w-full min-h-[56px] px-4 py-3 vintage-input studio-search-input studio-search-input-with-icon" />
                <div
                    wire:loading.flex
                    wire:target="query"
                    role="status"
                    aria-live="polite"
                    class="studio-search-loading">
                    <span class="studio-spinner" aria-hidden="true"></span>
                    <span>Mencari...</span>
                </div>
            </div>

            <div>
                <select
                    wire:model.live="document_type"
                    class="w-full min-h-[56px] px-3 py-2.5 vintage-input studio-filter-pill font-semibold">
                    <option value="">Semua Jenis</option>
                    <option value="SK">Surat Keputusan (SK)</option>
                    <option value="SOP">SOP</option>
                    <option value="Laporan">Laporan</option>
                    <option value="Surat Edaran">Surat Edaran</option>
                    <option value="Data">Data / Statistik</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div>
                <select
                    wire:model.live="year"
                    class="w-full min-h-[56px] px-3 py-2.5 vintage-input studio-filter-pill font-semibold">
                    <option value="">Semua Tahun</option>
                    @for ($y = (int) date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full min-h-[56px] vintage-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Cari Dokumen</span>
                </button>
            </div>
        </form>

        {{-- Cek Berkas Sebelum Unggah --}}
        <div class="vintage-card studio-glass-check studio-fade-in studio-stagger-1 studio-check-card">
            <div class="studio-check-head">
                <span class="studio-icon-tile studio-icon-tile-blue" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <h2 class="studio-card-title">
                        Cek Berkas Sebelum Unggah
                    </h2>
                    <p class="studio-card-note">
                        Pilih berkas untuk mengecek apakah sudah ada di perpustakaan. Berkas tidak disimpan.
                    </p>
                </div>
            </div>

            <div class="studio-check-row">
                <input
                    type="file"
                    wire:model="checkFile"
                    accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png"
                    class="flex-1 text-base vintage-input p-2" />

                <button
                    type="button"
                    wire:click="checkUploadedFile"
                    wire:loading.attr="disabled"
                    class="min-h-[56px] vintage-btn-secondary">
                    <span>Periksa Berkas</span>
                </button>
            </div>

            @error('checkFile')
                <p class="text-sm font-bold" style="color: #991B1B;">{{ $message }}</p>
            @enderror

            @if ($checkStatus === 'identical' && $checkIdentical)
                <div class="p-4 rounded-xl text-sm space-y-1.5 studio-status-box studio-fade-in" style="background-color: var(--status-rejected-bg); border: 1px solid var(--status-rejected-border); color: var(--status-rejected-text);">
                    <div class="font-bold text-base">Ditolak — Dokumen Sudah Ada</div>
                    <div><strong>Judul:</strong> {{ $checkIdentical['judul'] }}</div>
                    <div><strong>Pengunggah:</strong> {{ $checkIdentical['uploader_name'] }}</div>
                    <div><strong>Status:</strong> {{ $checkIdentical['approval_status'] }}</div>
                    <div>
                        <strong>Lembar Kerja:</strong>
                        {{ ! empty($checkIdentical['lks']) ? implode(', ', $checkIdentical['lks']) : 'Belum terhubung ke LK resmi' }}
                    </div>
                </div>
            @elseif ($checkStatus === 'similar')
                <div class="p-4 rounded-xl text-sm space-y-2 studio-status-box studio-fade-in" style="background-color: var(--status-pending-bg); border: 1px solid var(--status-pending-border); color: var(--status-pending-text);">
                    <div class="font-bold text-base">Peringatan: Ditemukan Dokumen Mirip (Boleh Tetap Unggah)</div>
                    <p>{{ $checkMessage }}</p>
                    <ul class="space-y-1.5 list-disc pl-5">
                        @foreach ($checkSimilar as $cand)
                            <li>
                                <strong>{{ $cand['document_name'] }}</strong>
                                — Kemiripan {{ $cand['similarity_score'] }}%
                                • Status: {{ $cand['approval_status'] }}
                                @if (! empty($cand['lks']))
                                    • LK: {{ implode(', ', $cand['lks']) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="pt-2">
                        <a href="/admin/evidence-documents/create" class="vintage-btn-primary">
                            Tetap Lanjutkan ke Halaman Unggah
                        </a>
                    </div>
                </div>
            @elseif ($checkStatus === 'clear')
                <div class="p-4 rounded-xl text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 studio-status-box studio-fade-in" style="background-color: var(--status-approved-bg); border: 1px solid var(--status-approved-border); color: var(--status-approved-text);">
                    <div class="font-bold text-base">
                        {{ $checkMessage }}
                    </div>
                    <a href="/admin/evidence-documents/create" class="vintage-btn-success">
                        Unggah Dokumen Baru
                    </a>
                </div>
            @endif
        </div>

        {{-- Ringkasan Status Pengajuan Milik Sendiri (Tanpa Centang di Matriks Resmi) --}}
        @if ($ownSummary['total'] > 0)
            <div class="p-4 vintage-section flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                <div>
                    <strong>Status Pengajuan Anda (belum masuk matriks resmi tanpa centang):</strong>
                    Menunggu ({{ $ownSummary['menunggu'] }}),
                    Perlu Revisi ({{ $ownSummary['revisi'] }}),
                    Ditolak ({{ $ownSummary['ditolak'] }}).
                </div>
                <a href="/admin/evidence-documents" class="vintage-link whitespace-nowrap">
                    Lihat Pengajuan Saya &rarr;
                </a>
            </div>
        @endif

        {{-- Tabel Matriks 6 Lembar Kerja (Hanya tampil bila kata kunci pencarian terisi) --}}
        @if (! $hasQuery)
            <div class="vintage-hint-card studio-fade-in studio-stagger-2">
                <div class="studio-doc-stack" aria-hidden="true">
                    <span class="studio-doc-sheet studio-doc-sheet-1"></span>
                    <span class="studio-doc-sheet studio-doc-sheet-2"></span>
                    <span class="studio-doc-sheet studio-doc-sheet-3"></span>
                </div>
                Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.
            </div>
        @else
            @php
                $matrixData = $this->getMatrixData();
                $allLks = $matrixData['columns']['internal']->merge($matrixData['columns']['eksternal']);
            @endphp
            <div class="vintage-card overflow-hidden studio-fade-in studio-stagger-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="background-color: var(--matrix-header-bg); border-bottom: 1px solid var(--border-warm-strong); color: var(--text-main);" class="text-sm font-bold">
                                <th class="p-3.5 sticky left-0 z-10 studio-doc-name-cell" style="background-color: var(--matrix-header-bg);">
                                    Dokumen Bukti Dukung Resmi
                                </th>
                                <th class="p-3 text-center studio-col-year">Tahun</th>
                                <th class="p-3 text-center studio-col-type">Jenis</th>
                                @foreach ($allLks as $lk)
                                    <th class="studio-lk-col" title="{{ $lk->name }} ({{ ucfirst($lk->category) }})">
                                        <div class="inline-flex text-xs font-bold" data-lk="{{ $lk->code }}">{{ $lk->code }}</div>
                                    </th>
                                @endforeach
                                @if ($canManageMarkup)
                                    <th class="p-3 text-center studio-col-action" style="border-left: 1px solid var(--border-warm);">
                                        Aksi
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-base">
                            @forelse ($matrixData['rows'] as $row)
                                <tr class="studio-table-row" style="border-bottom: 1px solid var(--border-warm);">
                                    <td class="p-3.5 sticky left-0 z-10 studio-doc-name-cell" style="background-color: var(--matrix-sticky-bg);">
                                        <div class="font-bold leading-snug" style="color: var(--text-main);">
                                            {{ $row['document_name'] }}
                                        </div>
                                        @if ($row['document_number'])
                                            <div class="text-sm vintage-muted mt-0.5">
                                                Nomor: {{ $row['document_number'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center font-semibold num">
                                        {{ $row['year'] }}
                                    </td>
                                    <td class="p-4 text-center">
                                        @if (filled($row['document_type']))
                                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold vintage-section studio-badge-type">
                                                {{ $row['document_type'] }}
                                            </span>
                                        @else
                                            <span class="vintage-muted">-</span>
                                        @endif
                                    </td>
                                    @foreach ($allLks as $lk)
                                        @php
                                            $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => ''];
                                        @endphp
                                        <td class="p-3 text-center" style="border-left: 1px solid var(--border-warm);">
                                            @if ($cell['has_match'])
                                                <button
                                                    type="button"
                                                    wire:click="toggleDownloadSection('{{ $row['document_id'] }}')"
                                                    class="vintage-check-btn"
                                                    title="Klik untuk mengunduh atau membuka tautan dokumen">
                                                    {{ $cell['symbol'] }}
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach
                                    @if ($canManageMarkup)
                                        <td class="p-3 text-center" style="border-left: 1px solid var(--border-warm);">
                                            <button
                                                type="button"
                                                wire:click="openEditMarkup('{{ $row['document_id'] }}', {{ json_encode($row['selected_lk_ids'] ?? []) }})"
                                                class="vintage-btn-secondary"
                                                style="min-height: 44px !important; padding: 0.5rem 0.875rem; font-size: 0.875rem;">
                                                Ubah LK
                                            </button>
                                        </td>
                                    @endif
                                </tr>

                                @if ($expandedDocId === $row['document_id'])
                                    @php
                                        $safeExternalLink = (! empty($row['external_link']) && preg_match('/^https?:\/\//i', (string) $row['external_link']))
                                            ? (string) $row['external_link']
                                            : null;
                                    @endphp
                                    <tr style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border-warm-strong);">
                                        <td colspan="{{ count($allLks) + ($canManageMarkup ? 4 : 3) }}" class="p-5">
                                            <div class="studio-panel-fade flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                <div>
                                                    <div class="font-bold text-base" style="color: var(--text-main);">
                                                        Unduh / Akses Dokumen: {{ $row['document_name'] }}
                                                    </div>
                                                    @if ($row['document_number'])
                                                        <div class="text-sm vintage-muted">
                                                            Nomor: {{ $row['document_number'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    @if (! empty($row['has_file']))
                                                        <a
                                                            href="{{ route('evidence.download', $row['document_id']) }}"
                                                            target="_blank"
                                                            class="vintage-btn-primary">
                                                            <span>Unduh Dokumen</span>
                                                        </a>
                                                    @endif

                                                    @if ($safeExternalLink !== null)
                                                        <a
                                                            href="{{ $safeExternalLink }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="vintage-btn-secondary">
                                                            <span>Buka Tautan</span>
                                                        </a>
                                                    @endif

                                                    <button
                                                        type="button"
                                                        wire:click="toggleDownloadSection('{{ $row['document_id'] }}')"
                                                        class="vintage-btn-secondary">
                                                        Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ count($allLks) + ($canManageMarkup ? 4 : 3) }}" class="p-10 text-center vintage-muted">
                                        Dokumen belum ada di perpustakaan. Silakan unggah lewat menu Unggah Bukti.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($matrixData['paginator']->hasPages())
                    <div class="p-4" style="border-top: 1px solid var(--border-warm);">
                        {{ $matrixData['paginator']->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Modal Ubah Pemetaan LK oleh Admin --}}
        @if ($editingDocId)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 studio-modal-backdrop">
                <div class="w-full max-w-lg vintage-card p-6 space-y-4 studio-fade-in">
                    <div class="flex items-start justify-between gap-3 pb-3" style="border-bottom: 1px solid var(--border-warm);">
                        <div>
                            <h3 class="text-lg font-bold vintage-serif-heading">
                                Ubah Pilihan Lembar Kerja
                            </h3>
                            <p class="text-sm vintage-muted mt-0.5 truncate max-w-md">
                                {{ $editingDocName }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeEditMarkup"
                            class="vintage-btn-secondary"
                            style="min-height: 44px !important; padding: 0.25rem 0.75rem;">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-bold" style="color: var(--text-main);">
                            Pilih Lembar Kerja Resmi yang Terkait:
                        </label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1">
                            @foreach ($this->getAllActiveLks() as $alk)
                                <label class="flex items-center gap-2.5 p-3 vintage-section cursor-pointer text-sm" data-lk="{{ $alk->code }}">
                                    <input
                                        type="checkbox"
                                        wire:model="editingLkIds"
                                        value="{{ $alk->id }}"
                                        class="w-5 h-5 rounded" />
                                    <span class="font-bold">{{ $alk->code }}</span>
                                    <span class="vintage-muted truncate text-xs">({{ $alk->name }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('editingLkIds')
                            <p class="text-sm font-bold" style="color: #991B1B;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3" style="border-top: 1px solid var(--border-warm);">
                        <button
                            type="button"
                            wire:click="closeEditMarkup"
                            class="vintage-btn-secondary">
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveRevisedMarkup"
                            class="vintage-btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
