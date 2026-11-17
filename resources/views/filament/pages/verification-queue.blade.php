<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header & Stats Summary --}}
        @php
            $stats = $this->getStats();
            $documents = $this->getDocuments();
            $availableLks = $this->getAvailableLembarKerjas();
        @endphp

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                    Antrean Verifikasi Bukti Dukung
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Tinjau pengajuan dokumen bukti dukung dari pegawai, tetapkan lembar kerja resmi, atau minta perbaikan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Filter Status Tabs --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="setFilter('menunggu')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $statusFilter === 'menunggu' ? 'bg-amber-700 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>Menunggu</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'menunggu' ? 'bg-amber-900 text-amber-100' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                            {{ $stats['menunggu'] }}
                        </span>
                    </button>

                    <button
                        wire:click="setFilter('revisi')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $statusFilter === 'revisi' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>Perlu Revisi</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'revisi' ? 'bg-sky-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                            {{ $stats['revisi'] }}
                        </span>
                    </button>

                    <button
                        wire:click="setFilter('ditolak')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $statusFilter === 'ditolak' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>Ditolak</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'ditolak' ? 'bg-rose-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                            {{ $stats['ditolak'] }}
                        </span>
                    </button>

                    <button
                        wire:click="setFilter('all')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $statusFilter === 'all' ? 'bg-slate-800 text-white dark:bg-slate-200 dark:text-slate-900 shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>Semua</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Bar --}}
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari berdasarkan nama atau nomor dokumen..."
                class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-sky-500 text-sm shadow-2xs" />
        </div>

        {{-- Document Cards List --}}
        @if ($documents->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 text-xl mb-3">
                    ✓
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                    Tidak ada dokumen dalam antrean
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Semua pengajuan telah diproses atau tidak ada dokumen yang cocok dengan filter.
                </p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($documents as $doc)
                    @php
                        $proposedLks = $doc->lkLinks->where('link_source', 'proposed')->map(fn ($l) => $l->lembarKerja)->filter()->unique('id');
                        $officialLks = $doc->lkLinks->whereIn('link_source', ['admin', 'drive_sync', 'backfill'])->map(fn ($l) => $l->lembarKerja)->filter()->unique('id');
                        $systemLks = $doc->matches->map(fn ($m) => $m->indicator?->lembarKerja)->filter()->unique('id');
                    @endphp

                    <div class="p-6 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xs space-y-5">
                        {{-- Top Header Info --}}
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ match ($doc->approval_status) {
                                        'disetujui' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                        'revisi' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                                        'ditolak' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                                        default => 'bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-700'
                                    } }}">
                                        {{ match ($doc->approval_status) {
                                            'disetujui' => 'Disetujui',
                                            'revisi' => 'Perlu Revisi',
                                            'ditolak' => 'Ditolak',
                                            default => 'Menunggu Verifikasi'
                                        } }}
                                    </span>

                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        Tahun {{ $doc->year }} • Jenis: {{ $doc->document_type }}
                                    </span>
                                </div>

                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                    {{ $doc->document_name }}
                                </h2>

                                @if ($doc->document_number)
                                    <div class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                        Nomor: {{ $doc->document_number }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 self-start lg:self-auto text-xs">
                                <div class="text-right">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $doc->uploader?->name ?? 'Pengunggah Tidak Tercatat' }}
                                    </div>
                                    <div class="text-slate-400">
                                        {{ $doc->created_at->format('d M Y H:i') }}
                                    </div>
                                </div>

                                @if ($doc->file_path)
                                    <a
                                        href="{{ route('evidence.download', $doc->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold transition">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Unduh Berkas</span>
                                    </a>
                                @endif

                                @if ($doc->external_link)
                                    <a
                                        href="{{ $doc->external_link }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 hover:bg-sky-100 font-bold border border-sky-200 dark:border-sky-800 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        <span>Buka Tautan</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- LK Mapping Badges: Usulan Pegawai vs Saran Sistem --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/60">
                            <div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1.5">
                                    📌 Usulan Pegawai (Tujuan LK):
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($proposedLks as $plk)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
                                            {{ $plk->code }} - {{ $plk->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Tidak ada usulan LK spesifik</span>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1.5">
                                    💡 Saran Sistem (Kecocokan Otomatis):
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($systemLks as $slk)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200 border border-sky-300 dark:border-sky-700">
                                            {{ $slk->code }} - {{ $slk->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Belum ada indikator cocok otomatis</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Catatan Sebelumnya Jika Ada --}}
                        @if ($doc->approval_notes)
                            <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-xs text-rose-900 dark:text-rose-200">
                                <span class="font-bold">Catatan Verifikasi Terakhir:</span> {{ $doc->approval_notes }}
                            </div>
                        @endif

                        {{-- Form Verifikasi & Pemilih LK --}}
                        <div class="pt-2 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                                    Pilih Lembar Kerja Resmi yang Disetujui (* Wajib sebelum klik Setujui):
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    @foreach ($availableLks as $alk)
                                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer text-xs transition">
                                            <input
                                                type="checkbox"
                                                wire:model="selectedLks.{{ $doc->id }}"
                                                value="{{ $alk->id }}"
                                                class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 w-4 h-4" />
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $alk->code }}</span>
                                            <span class="text-slate-500 truncate text-[11px]">({{ $alk->name }})</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error("selectedLks.{$doc->id}")
                                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                    Catatan / Alasan (Wajib diisi jika Minta Revisi atau Tolak):
                                </label>
                                <input
                                    type="text"
                                    wire:model="notes.{{ $doc->id }}"
                                    placeholder="Contoh: Dokumen belum ditandatangani basah / masa berlaku SK sudah habis..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-xs shadow-2xs focus:ring-2 focus:ring-sky-500" />
                                @error("notes.{$doc->id}")
                                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- EXACTLY 3 BUTTONS (min-h-[56px]) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                                {{-- Tombol 1: Setujui --}}
                                <button
                                    type="button"
                                    wire:click="approveDocument('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-sm transition active:scale-[0.99] cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Setujui Dokumen</span>
                                </button>

                                {{-- Tombol 2: Minta Revisi --}}
                                <button
                                    type="button"
                                    wire:click="requestRevision('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm shadow-sm transition active:scale-[0.99] cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Minta Revisi</span>
                                </button>

                                {{-- Tombol 3: Tolak --}}
                                <button
                                    type="button"
                                    wire:click="rejectDocument('{{ $doc->id }}')"
                                    class="min-h-[56px] w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-sm transition active:scale-[0.99] cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span>Tolak Pengajuan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Pagination Links --}}
                <div class="pt-4">
                    {{ $documents->links() }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
