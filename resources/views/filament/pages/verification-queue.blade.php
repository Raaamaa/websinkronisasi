<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $stats = $this->getStats();
            $documents = $this->getDocuments();
            $availableLks = $this->getAvailableLembarKerjas();
            $isSuperAdmin = auth()->user()?->role === 'super_admin';
            $storage = $this->getStorageUsage();
        @endphp

        {{-- BANNER STORAGE WARNING KHUSUS SUPER ADMIN --}}
        @if ($isSuperAdmin && $storage['is_warning'])
            <div class="p-5 studio-status-box flex items-start gap-4" style="background-color: var(--status-rejected-bg); border: var(--rule-hair) solid var(--status-rejected-border); color: var(--status-rejected-text);">
                <div class="studio-icon-badge text-lg" style="background-color: #991B1B; color: #FFFFFF;">
                    !
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold vintage-serif-heading" style="color: var(--status-rejected-text);">
                        Peringatan Kapasitas Penyimpanan Disk Server
                    </h3>
                    <p class="text-sm mt-1 leading-relaxed">
                        Kapasitas penyimpanan disk server bukti dukung telah mencapai <strong>{{ $storage['percent'] }}%</strong> (Ambang batas sistem: {{ config('evidence.storage_warning_percent') }}%). Segera lakukan pembersihan dokumen kadaluarsa atau hubungi tim pengelola server.
                    </p>
                </div>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 studio-header-band">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold vintage-serif-heading">
                    Antrean Verifikasi Bukti Dukung
                </h1>
                <p class="text-base vintage-muted mt-1">
                    Periksa kiriman dokumen pegawai, tentukan Lembar Kerja resmi, atau kembalikan untuk diperbaiki.
                </p>
            </div>

            <div class="studio-segmented">
                <button
                    type="button"
                    wire:click="setFilter('menunggu')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold flex items-center gap-2 cursor-pointer {{ $statusFilter === 'menunggu' ? 'vintage-btn-warning' : 'vintage-btn-secondary' }}">
                    <span>Menunggu</span>
                    <span>({{ $stats['menunggu'] }})</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilter('revisi')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold flex items-center gap-2 cursor-pointer {{ $statusFilter === 'revisi' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}">
                    <span>Perlu Revisi</span>
                    <span>({{ $stats['revisi'] }})</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilter('ditolak')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold flex items-center gap-2 cursor-pointer {{ $statusFilter === 'ditolak' ? 'vintage-btn-danger' : 'vintage-btn-secondary' }}">
                    <span>Ditolak</span>
                    <span>({{ $stats['ditolak'] }})</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilter('all')"
                    class="studio-tab px-4 py-2.5 text-sm font-bold flex items-center gap-2 cursor-pointer {{ $statusFilter === 'all' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}">
                    <span>Semua</span>
                </button>
            </div>
        </div>

        {{-- Kotak Pencarian --}}
        <div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari berdasarkan nama atau nomor dokumen..."
                class="w-full min-h-[56px] px-4 py-3 vintage-input" />
        </div>

        {{-- Daftar Kartu Dokumen --}}
        @if ($documents->isEmpty())
            <div class="p-12 studio-empty-state vintage-card studio-fade-in">
                <h3 class="text-lg font-bold vintage-serif-heading">
                    Tidak ada dokumen dalam antrean
                </h3>
                <p class="text-sm vintage-muted mt-1">
                    Semua pengajuan sudah diproses atau tidak ada dokumen yang cocok dengan pilihan saat ini.
                </p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($documents as $doc)
                    @php
                        $proposedLks = $doc->lkLinks->where('link_source', 'proposed')->map(fn ($l) => $l->lembarKerja)->filter()->unique('id');
                        $badgeClass = match ($doc->approval_status) {
                            'disetujui' => 'vintage-badge-disetujui',
                            'revisi' => 'vintage-badge-revisi',
                            'ditolak' => 'vintage-badge-ditolak',
                            default => 'vintage-badge-menunggu',
                        };
                        $statusLabel = match ($doc->approval_status) {
                            'disetujui' => 'Disetujui',
                            'revisi' => 'Perlu Revisi',
                            'ditolak' => 'Ditolak',
                            default => 'Menunggu Verifikasi',
                        };
                    @endphp

                    <div class="p-6 vintage-card space-y-5 studio-fade-in">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4" style="border-bottom: 1px solid var(--border-warm);">
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap mb-1.5">
                                    <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>
                                    <span class="text-sm vintage-muted">
                                        Tahun {{ $doc->year }} • Jenis: {{ $doc->document_type }}
                                    </span>
                                </div>

                                <h2 class="text-xl font-bold vintage-serif-heading leading-snug">
                                    {{ $doc->document_name }}
                                </h2>

                                @if ($doc->document_number)
                                    <div class="text-sm vintage-muted mt-0.5">
                                        Nomor: {{ $doc->document_number }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto text-sm">
                                <div class="text-right">
                                    <div class="font-bold">
                                        {{ $doc->uploader?->name ?? 'Pengunggah Tidak Tercatat' }}
                                    </div>
                                    <div class="text-xs vintage-muted">
                                        {{ $doc->created_at->format('d M Y H:i') }}
                                    </div>
                                </div>

                                @if ($doc->file_path)
                                    <a
                                        href="{{ route('evidence.download', $doc->id) }}"
                                        target="_blank"
                                        class="vintage-btn-secondary"
                                        style="min-height: 44px !important; padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <span>Unduh Berkas</span>
                                    </a>
                                @endif

                                @if ($doc->external_link)
                                    <a
                                        href="{{ $doc->external_link }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="vintage-btn-secondary"
                                        style="min-height: 44px !important; padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <span>Buka Tautan</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="vintage-section p-4">
                            <span class="text-sm font-bold block mb-2">
                                Usulan Lembar Kerja dari Pengirim:
                            </span>
                            <div class="flex flex-wrap gap-2">
                                @forelse ($proposedLks as $plk)
                                    <span class="vintage-badge-menunggu" data-lk="{{ $plk->code }}">
                                        {{ $plk->code }} - {{ $plk->name }}
                                    </span>
                                @empty
                                    <span class="text-sm vintage-muted italic">Tidak ada usulan LK spesifik</span>
                                @endforelse
                            </div>
                        </div>

                        @if ($doc->approval_notes)
                            <div class="p-3.5 studio-status-box text-sm" style="background-color: var(--status-rejected-bg); border: var(--rule-hair) solid var(--status-rejected-border); color: var(--status-rejected-text);">
                                <strong>Catatan Verifikasi Terakhir:</strong> {{ $doc->approval_notes }}
                            </div>
                        @endif

                        <div class="pt-2 space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">
                                    Pilih Lembar Kerja Resmi yang Disetujui (wajib sebelum klik Setujui):
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                    @foreach ($availableLks as $alk)
                                        <label class="flex items-center gap-2.5 p-3 vintage-section cursor-pointer text-sm" data-lk="{{ $alk->code }}">
                                            <input
                                                type="checkbox"
                                                wire:model="selectedLks.{{ $doc->id }}"
                                                value="{{ $alk->id }}"
                                                class="w-5 h-5 rounded" />
                                            <span class="font-bold">{{ $alk->code }}</span>
                                            <span class="vintage-muted truncate text-xs">({{ $alk->name }})</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error("selectedLks.{$doc->id}")
                                    <p class="text-sm font-bold mt-1" style="color: #991B1B;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold mb-1.5">
                                    Catatan atau Alasan (wajib diisi bila meminta revisi atau menolak):
                                </label>
                                <input
                                    type="text"
                                    wire:model="notes.{{ $doc->id }}"
                                    placeholder="Contoh: Dokumen belum bertanda tangan atau masa berlaku SK sudah habis..."
                                    class="w-full min-h-[56px] px-4 py-3 vintage-input" />
                                @error("notes.{$doc->id}")
                                    <p class="text-sm font-bold mt-1" style="color: #991B1B;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                                <button
                                    type="button"
                                    wire:click="approveDocument('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full vintage-btn-success">
                                    <span>Setujui Dokumen</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="requestRevision('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full vintage-btn-warning">
                                    <span>Minta Revisi</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="rejectDocument('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full vintage-btn-danger">
                                    <span>Tolak Pengajuan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="pt-4">
                    {{ $documents->links() }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
