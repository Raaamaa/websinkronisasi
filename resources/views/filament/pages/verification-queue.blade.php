<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header & Penjelasan Antrean --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Antrean Verifikasi Pencocokan</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Daftar dokumen bukti dukung yang dicocokkan otomatis oleh sistem dengan skor keyakinan &ge; 75%. Lakukan konfirmasi atau penolakan dengan satu klik.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">
                    Status: Menunggu Verifikasi (Auto)
                </span>
            </div>
        </div>

        {{-- Filter Panel --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Periode --}}
                <div>
                    <label for="periodFilter" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Periode LK
                    </label>
                    <select
                        id="periodFilter"
                        wire:model.live="period"
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                    >
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>

                {{-- Lembar Kerja Filter --}}
                <div>
                    <label for="lkFilter" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Lembar Kerja (LK)
                    </label>
                    <select
                        id="lkFilter"
                        wire:model.live="lkId"
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                    >
                        <option value="">-- Semua Lembar Kerja --</option>
                        @foreach($this->getLkOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Box --}}
                <div>
                    <label for="searchFilter" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Cari Dokumen / Indikator
                    </label>
                    <input
                        type="search"
                        id="searchFilter"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik nama dokumen atau kode indikator..."
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                    />
                </div>
            </div>
        </div>

        {{-- Verification Table --}}
        @php
            $matches = $this->getMatches();
        @endphp

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3">Dokumen Bukti Dukung</th>
                            <th scope="col" class="px-5 py-3">Lembar Kerja & Indikator</th>
                            <th scope="col" class="px-5 py-3 text-center">Skor Keyakinan</th>
                            <th scope="col" class="px-5 py-3">Metode & Alasan</th>
                            <th scope="col" class="px-5 py-3 text-right">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($matches as $match)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-750 transition-colors">
                                {{-- Dokumen --}}
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $match->document->document_name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap gap-2 items-center">
                                        @if(!empty($match->document->document_number))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">
                                                No: {{ $match->document->document_number }}
                                            </span>
                                        @endif
                                        <span>Tahun: {{ $match->document->year ?? '-' }}</span>
                                    </div>
                                </td>

                                {{-- Lembar Kerja & Indikator --}}
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            {{ $match->indicator->lembarKerja->code ?? 'LK' }}
                                        </span>
                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $match->indicator->lembarKerja->name ?? '' }}
                                        </span>
                                    </div>
                                    <div class="font-medium text-gray-900 dark:text-white mt-1.5">
                                        <span class="font-bold text-primary-600 dark:text-primary-400">[{{ $match->indicator->code ?? '-' }}]</span>
                                        {{ $match->indicator->title ?? '-' }}
                                    </div>
                                    @if($match->matchedItem)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">
                                            Butir: {{ $match->matchedItem->resolved_name ?? $match->matchedItem->raw_template_name }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Skor Keyakinan --}}
                                <td class="px-5 py-4 align-top text-center">
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm
                                        {{ $match->confidence_score >= 90
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                        {{ number_format((float) $match->confidence_score, 1) }}%
                                    </div>
                                </td>

                                {{-- Metode & Alasan --}}
                                <td class="px-5 py-4 align-top">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ str_replace('_', ' ', $match->match_method) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs truncate" title="{{ json_encode($match->match_reasons) }}">
                                        @if(is_array($match->match_reasons))
                                            @foreach($match->match_reasons as $k => $v)
                                                <span>{{ $k }}: {{ is_array($v) ? json_encode($v) : $v }}</span><br>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi Verifikasi --}}
                                <td class="px-5 py-4 align-top text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        {{-- Tombol Konfirmasi --}}
                                        <button
                                            type="button"
                                            wire:click="confirmMatch('{{ $match->id }}')"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 transition"
                                            title="Konfirmasi Pencocokan (Confirmed)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Konfirmasi</span>
                                        </button>

                                        {{-- Tombol Tolak --}}
                                        <button
                                            type="button"
                                            wire:click="rejectMatch('{{ $match->id }}')"
                                            wire:confirm="Yakin ingin menolak pencocokan ini? Dokumen tidak akan dicocokkan otomatis lagi ke indikator ini."
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1 transition"
                                            title="Tolak Pencocokan (Rejected)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span>Tolak</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-base font-semibold">Tidak Ada Antrean Verifikasi</span>
                                        <span class="text-xs text-gray-400 max-w-sm">Semua pencocokan otomatis telah diverifikasi atau tidak ada data yang cocok dengan kriteria pencarian saat ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            @if($matches->hasPages())
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
                    {{ $matches->links() }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

