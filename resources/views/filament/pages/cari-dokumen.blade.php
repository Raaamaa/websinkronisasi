<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                Cari Dokumen Bukti Dukung
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                Pencarian cerdas multi-kolom lintas nama, nomor SK, dan teks digital dokumen resmi yang telah disetujui.
            </p>
        </div>

        {{-- Search Input & Button --}}
        <form wire:submit.prevent="search" class="flex flex-col sm:flex-row items-stretch gap-3">
            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model="query"
                    placeholder="Ketikkan nama dokumen, nomor SK, atau kata kunci..."
                    class="w-full h-full min-h-[56px] px-5 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 text-sm shadow-xs focus:ring-2 focus:ring-sky-500" />
            </div>

            <button
                type="submit"
                class="min-h-[56px] px-8 py-3 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm shadow-sm transition active:scale-[0.99] inline-flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari Dokumen</span>
            </button>
        </form>

        {{-- Search Results --}}
        @php
            $results = $this->getSearchResults();
        @endphp

        @if ($hasSearched && $results->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 text-xl mb-3">
                    🔍
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                    Tidak ditemukan dokumen yang cocok
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Coba kata kunci lain atau periksa ejaan nomor dokumen.
                </p>
            </div>
        @elseif (! $results->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                        Ditemukan {{ $results->count() }} dokumen resmi:
                    </span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($results as $item)
                        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        Disetujui
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500">
                                        Tahun {{ $item->year }} • {{ $item->document_type }}
                                    </span>
                                </div>

                                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                                    {{ $item->document_name }}
                                </h2>

                                @if ($item->document_number)
                                    <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                        Nomor: {{ $item->document_number }}
                                    </div>
                                @endif

                                {{-- Lembar Kerja Resmi Terkait --}}
                                <div class="flex items-center gap-1.5 pt-1 flex-wrap">
                                    <span class="text-xs text-slate-500 font-medium">Lembar Kerja:</span>
                                    @forelse ($item->lembarKerja as $lk)
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            {{ $lk->code }} - {{ $lk->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Belum terhubung ke LK</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 self-start md:self-auto flex-shrink-0">
                                @if ($item->file_path)
                                    <a
                                        href="{{ route('evidence.download', $item->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Unduh</span>
                                    </a>
                                @endif

                                @if ($item->external_link)
                                    <a
                                        href="{{ $item->external_link }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 hover:bg-sky-100 font-bold text-xs border border-sky-200 dark:border-sky-800 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        <span>Buka Tautan</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
