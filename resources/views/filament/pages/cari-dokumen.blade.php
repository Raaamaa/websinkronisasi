<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $matrixData = $this->getMatrixData();
            $allLks = $matrixData['columns']['internal']->merge($matrixData['columns']['eksternal']);
            $canManageMarkup = auth()->user()?->hasPermission('evidence.manage_markup');
            $ownSummary = $this->getOwnSubmissionSummary();
        @endphp

        {{-- Header --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                    Cari Dokumen Bukti Dukung
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Cari dokumen resmi yang telah disetujui atau klik tanda centang (✔) pada kolom Lembar Kerja untuk mengunduh berkas.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs font-bold">
                    <button
                        type="button"
                        wire:click="setCategory(null)"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === null ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Semua LK
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('internal')"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === 'internal' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Internal
                    </button>
                    <button
                        type="button"
                        wire:click="setCategory('eksternal')"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === 'eksternal' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Eksternal
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Input & Filter Bar --}}
        <form wire:submit.prevent="search" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-stretch">
            <div class="md:col-span-6">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="Ketikkan nama dokumen, nomor SK, atau kata kunci..."
                    class="w-full min-h-[56px] px-5 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 text-sm shadow-xs focus:ring-2 focus:ring-sky-500" />
            </div>

            <div class="md:col-span-2">
                <select
                    wire:model.live="document_type"
                    class="w-full h-full min-h-[56px] px-3 py-2.5 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium shadow-xs focus:ring-2 focus:ring-sky-500">
                    <option value="">Semua Jenis</option>
                    <option value="SK">Surat Keputusan (SK)</option>
                    <option value="SOP">SOP</option>
                    <option value="Laporan">Laporan</option>
                    <option value="Surat Edaran">Surat Edaran</option>
                    <option value="Data">Data / Statistik</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <select
                    wire:model.live="year"
                    class="w-full h-full min-h-[56px] px-3 py-2.5 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium shadow-xs focus:ring-2 focus:ring-sky-500">
                    <option value="">Semua Tahun</option>
                    @for ($y = (int) date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="md:col-span-2">
                <button
                    type="submit"
                    class="w-full min-h-[56px] px-6 py-3 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm shadow-sm transition active:scale-[0.99] inline-flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Cari Dokumen</span>
                </button>
            </div>
        </form>

        {{-- Cek Berkas Sebelum Unggah (Tanpa Simpan Permanen) --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                        Cek Berkas Sebelum Unggah
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Periksa apakah dokumen sudah ada di perpustakaan (tanpa menyimpan berkas ke server).
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <input
                    type="file"
                    wire:model="checkFile"
                    accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png"
                    class="flex-1 text-xs text-slate-700 dark:text-slate-200 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-xl p-1.5 bg-white dark:bg-slate-950" />

                <button
                    type="button"
                    wire:click="checkUploadedFile"
                    wire:loading.attr="disabled"
                    class="min-h-[56px] px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 dark:bg-slate-200 dark:hover:bg-slate-300 text-white dark:text-slate-900 font-bold text-xs sm:text-sm transition cursor-pointer inline-flex items-center justify-center">
                    <span>Periksa Berkas</span>
                </button>
            </div>

            @error('checkFile')
                <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
            @enderror

            @if ($checkStatus === 'identical' && $checkIdentical)
                <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-800 text-xs text-rose-900 dark:text-rose-200 space-y-1.5">
                    <div class="font-extrabold text-sm">Ditolak — Dokumen Sudah Ada</div>
                    <div><span class="font-bold">Judul:</span> {{ $checkIdentical['judul'] }}</div>
                    <div><span class="font-bold">Pengunggah:</span> {{ $checkIdentical['uploader_name'] }}</div>
                    <div><span class="font-bold">Status:</span> {{ $checkIdentical['approval_status'] }}</div>
                    <div>
                        <span class="font-bold">Lembar Kerja:</span>
                        {{ ! empty($checkIdentical['lks']) ? implode(', ', $checkIdentical['lks']) : 'Belum terhubung ke LK resmi' }}
                    </div>
                </div>
            @elseif ($checkStatus === 'similar')
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 space-y-2">
                    <div class="font-extrabold text-sm">Peringatan: Ditemukan Dokumen Mirip (Non-Blocking)</div>
                    <p>{{ $checkMessage }}</p>
                    <ul class="space-y-1.5 list-disc pl-4">
                        @foreach ($checkSimilar as $cand)
                            <li>
                                <span class="font-bold">{{ $cand['document_name'] }}</span>
                                — Kemiripan {{ $cand['similarity_score'] }}%
                                • Status: {{ $cand['approval_status'] }}
                                @if (! empty($cand['lks']))
                                    • LK: {{ implode(', ', $cand['lks']) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="pt-1">
                        <a href="/admin/evidence-documents/create" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-700 hover:bg-amber-800 text-white font-bold text-xs transition">
                            <span>Tetap Lanjutkan ke Halaman Unggah</span>
                        </a>
                    </div>
                </div>
            @elseif ($checkStatus === 'clear')
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="font-bold text-sm">
                        {{ $checkMessage }}
                    </div>
                    <a href="/admin/evidence-documents/create" class="inline-flex items-center justify-center px-5 py-2.5 min-h-[44px] rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition">
                        Unggah Dokumen Baru
                    </a>
                </div>
            @endif
        </div>

        {{-- Ringkasan Status Pengajuan Milik Sendiri (Tanpa Centang di Matriks Resmi) --}}
        @if ($ownSummary['total'] > 0)
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="text-slate-700 dark:text-slate-300">
                    <span class="font-bold">Status Pengajuan Anda (belum masuk matriks resmi tanpa centang):</span>
                    Menunggu ({{ $ownSummary['menunggu'] }}),
                    Perlu Revisi ({{ $ownSummary['revisi'] }}),
                    Ditolak ({{ $ownSummary['ditolak'] }}).
                </div>
                <a href="/admin/evidence-documents" class="font-bold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap">
                    Lihat Pengajuan Saya &rarr;
                </a>
            </div>
        @endif

        {{-- Matrix Table 6 LK --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/90 dark:bg-slate-800/90 border-b border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            <th class="p-3 pl-4 sticky left-0 z-10 bg-slate-100 dark:bg-slate-800 min-w-[240px]">
                                Dokumen Bukti Dukung Resmi
                            </th>
                            <th class="p-3 text-center min-w-[80px]">Tahun</th>
                            <th class="p-3 text-center min-w-[90px]">Jenis</th>
                            @foreach ($allLks as $lk)
                                <th class="p-3 text-center min-w-[100px] border-l border-slate-200 dark:border-slate-700/60" title="{{ $lk->name }}">
                                    <div>{{ $lk->code }}</div>
                                    <div class="text-[9px] font-normal text-slate-500 lowercase truncate max-w-[90px]">
                                        {{ $lk->category }}
                                    </div>
                                </th>
                            @endforeach
                            @if ($canManageMarkup)
                                <th class="p-3 text-center min-w-[90px] border-l border-slate-200 dark:border-slate-700">
                                    Aksi
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse ($matrixData['rows'] as $row)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <td class="p-3 pl-4 sticky left-0 z-10 bg-white dark:bg-slate-900">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $row['document_name'] }}
                                    </div>
                                    @if ($row['document_number'])
                                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ $row['document_number'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $row['year'] }}
                                </td>
                                <td class="p-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $row['document_type'] }}
                                    </span>
                                </td>
                                @foreach ($allLks as $lk)
                                    @php
                                        $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => ''];
                                    @endphp
                                    <td class="p-3 text-center border-l border-slate-100 dark:border-slate-800 font-bold {{ $cell['has_match'] ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/20' : '' }}">
                                        @if ($cell['has_match'])
                                            <button
                                                type="button"
                                                wire:click="toggleDownloadSection('{{ $row['document_id'] }}')"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold cursor-pointer"
                                                title="Klik untuk membuka bagian unduh">
                                                {{ $cell['symbol'] }}
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                                @if ($canManageMarkup)
                                    <td class="p-3 text-center border-l border-slate-100 dark:border-slate-800">
                                        <button
                                            type="button"
                                            wire:click="openEditMarkup('{{ $row['document_id'] }}', {{ json_encode($row['selected_lk_ids'] ?? []) }})"
                                            class="px-3 py-2 rounded-lg bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 hover:bg-sky-100 font-bold text-[11px] border border-sky-200 dark:border-sky-800 transition cursor-pointer">
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
                                <tr class="bg-sky-50/60 dark:bg-sky-950/30">
                                    <td colspan="{{ count($allLks) + ($canManageMarkup ? 4 : 3) }}" class="p-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-slate-100">
                                                    Unduh / Akses Dokumen: {{ $row['document_name'] }}
                                                </div>
                                                @if ($row['document_number'])
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                                        Nomor: {{ $row['document_number'] }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2.5">
                                                @if (! empty($row['has_file']))
                                                    <a
                                                        href="{{ route('evidence.download', $row['document_id']) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 min-h-[44px] rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition">
                                                        <span>Unduh Dokumen</span>
                                                    </a>
                                                @endif

                                                @if ($safeExternalLink !== null)
                                                    <a
                                                        href="{{ $safeExternalLink }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 min-h-[44px] rounded-xl bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 hover:bg-slate-100 font-bold text-xs border border-sky-200 dark:border-sky-800 transition">
                                                        <span>Buka Tautan</span>
                                                    </a>
                                                @endif

                                                <button
                                                    type="button"
                                                    wire:click="toggleDownloadSection('{{ $row['document_id'] }}')"
                                                    class="px-3.5 py-2.5 min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
                                                    Tutup
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ count($allLks) + ($canManageMarkup ? 4 : 3) }}" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    Tidak ditemukan dokumen resmi yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $matrixData['paginator']->links() }}
            </div>
        </div>

        {{-- MODAL UBAH MARKUP LK OLEH ADMIN --}}
        @if ($editingDocId)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl p-6 space-y-4">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                                Ubah Pemetaan Lembar Kerja
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate max-w-md">
                                {{ $editingDocName }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeEditMarkup"
                            class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                            Pilih Lembar Kerja Resmi yang Terkait:
                        </label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1">
                            @foreach ($this->getAllActiveLks() as $alk)
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer text-xs">
                                    <input
                                        type="checkbox"
                                        wire:model="editingLkIds"
                                        value="{{ $alk->id }}"
                                        class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 w-4 h-4" />
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $alk->code }}</span>
                                    <span class="text-slate-500 truncate text-[11px]">({{ $alk->name }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('editingLkIds')
                            <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            wire:click="closeEditMarkup"
                            class="px-4 py-2.5 min-h-[44px] rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveRevisedMarkup"
                            class="px-5 py-2.5 min-h-[44px] rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
