<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $matrixService = app(\App\Services\Monitor\MonitorMatrixService::class);
            $matrixData = $matrixService->getMatrixData([
                'period' => $period,
                'category' => $category,
                'document_type' => $document_type,
                'year' => $year,
                'search' => $search,
                'per_page' => $perPage,
                'page' => $page,
            ]);
            $allLks = $matrixData['columns']['internal']->merge($matrixData['columns']['eksternal']);
            $isSuperAdmin = auth()->user()?->role === 'super_admin';
            $canManageMarkup = auth()->user()?->hasPermission('evidence.manage_markup');
            $storage = $this->getStorageUsage();
        @endphp

        {{-- BANNER STORAGE WARNING KHUSUS SUPER ADMIN --}}
        @if ($isSuperAdmin && $storage['is_warning'])
            <div class="p-4 sm:p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/80 border-2 border-rose-400 dark:border-rose-700 shadow-sm flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold text-lg flex-shrink-0 mt-0.5">
                    ⚠️
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-black text-rose-900 dark:text-rose-100">
                        Peringatan Kapasitas Penyimpanan Disk Server
                    </h3>
                    <p class="text-xs text-rose-700 dark:text-rose-300 mt-1 leading-relaxed">
                        Kapasitas penyimpanan disk server bukti dukung telah mencapai <strong>{{ $storage['percent'] }}%</strong> (Ambang batas sistem: {{ config('evidence.storage_warning_percent') }}%). Segera lakukan pembersihan dokumen kadaluarsa atau hubungi tim infrastruktur IT.
                    </p>
                </div>
            </div>
        @endif

        {{-- Header Controls & Filter Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                    Matriks Pemetaan Bukti Dukung ke Lembar Kerja
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Hanya menampilkan dokumen bukti dukung resmi yang telah disetujui.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Periode / Interval --}}
                <select
                    wire:model.live="period"
                    class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-bold shadow-2xs focus:ring-2 focus:ring-sky-500">
                    @foreach ($this->getAvailablePeriods() as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>

                {{-- Kategori LK --}}
                <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs font-bold">
                    <button
                        wire:click="setCategory(null)"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === null ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Semua LK
                    </button>
                    <button
                        wire:click="setCategory('internal')"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === 'internal' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Internal
                    </button>
                    <button
                        wire:click="setCategory('eksternal')"
                        class="px-3 py-1.5 rounded-lg transition {{ $category === 'eksternal' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400' }}">
                        Eksternal
                    </button>
                </div>

                {{-- Unduh CSV --}}
                <button
                    wire:click="exportCsv"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 hover:bg-slate-700 dark:hover:bg-slate-300 text-xs font-bold shadow-2xs transition active:scale-[0.99] cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh CSV</span>
                </button>
            </div>
        </div>

        {{-- Filter Input Bar --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau nomor dokumen..."
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 text-xs shadow-2xs focus:ring-2 focus:ring-sky-500" />

            <select
                wire:model.live="document_type"
                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium shadow-2xs focus:ring-2 focus:ring-sky-500">
                <option value="">Semua Jenis Dokumen</option>
                <option value="SK">Surat Keputusan (SK)</option>
                <option value="SOP">Standar Operasional Prosedur (SOP)</option>
                <option value="Laporan">Laporan Kinerja / Kegiatan</option>
                <option value="Surat Edaran">Surat Edaran</option>
                <option value="Data">Data / Statistik</option>
                <option value="Lainnya">Lainnya</option>
            </select>

            <select
                wire:model.live="year"
                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium shadow-2xs focus:ring-2 focus:ring-sky-500">
                <option value="">Semua Tahun Anggaran</option>
                @for ($y = (int) date('Y'); $y >= 2020; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>

        {{-- Matrix Table Container --}}
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
                                <td class="p-3 pl-4 sticky left-0 z-10 bg-white dark:bg-slate-900 group-hover:bg-slate-50">
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
                                        {{ $cell['symbol'] }}
                                    </td>
                                @endforeach
                                @if ($canManageMarkup)
                                    <td class="p-3 text-center border-l border-slate-100 dark:border-slate-800">
                                        <button
                                            type="button"
                                            wire:click="openEditMarkup('{{ $row['document_id'] }}', {{ json_encode($row['selected_lk_ids'] ?? []) }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 hover:bg-sky-100 font-bold text-[11px] border border-sky-200 dark:border-sky-800 transition">
                                            Ubah LK
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($allLks) + ($canManageMarkup ? 4 : 3) }}" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    Belum ada bukti dukung resmi yang disetujui untuk ditampilkan di matriks monitor.
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
                            class="text-slate-400 hover:text-slate-600 text-lg font-bold">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                            Pilih Lembar Kerja Resmi yang Terkait:
                        </label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1">
                            @foreach ($this->getAllActiveLks() as $alk)
                                <label class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer text-xs">
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
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveRevisedMarkup"
                            class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
