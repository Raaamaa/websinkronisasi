<x-filament-panels::page>
    @php
        $matrix = $this->getViewData();
        $internalLks = $matrix['columns']['internal'];
        $eksternalLks = $matrix['columns']['eksternal'];
        $rows = $matrix['rows'];
        $paginator = $matrix['paginator'];
    @endphp

    {{-- Filter Toolbar --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Periode Lembar Kerja</label>
                <input type="number" wire:model.live.debounce.500ms="period" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" min="2020" max="2100" />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cari Dokumen / Nomor</label>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Ketik kata kunci..." class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Dokumen</label>
                <select wire:model.live="document_type" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">Semua Jenis</option>
                    <option value="SK">Surat Keputusan (SK)</option>
                    <option value="SOP">Standar Operasional Prosedur (SOP)</option>
                    <option value="Laporan">Laporan Kinerja / Kegiatan</option>
                    <option value="Surat Edaran">Surat Edaran</option>
                    <option value="Data">Data / Statistik</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun Dokumen</label>
                <input type="number" wire:model.live.debounce.500ms="year" placeholder="Tahun terbit" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
            </div>
        </div>
    </div>

    {{-- Matriks Tabel Monitor --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                {{-- Super Header Kategori LK (MON-01) --}}
                <tr class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-center">
                    <th colspan="3" class="p-3 text-left border-r border-gray-200 dark:border-gray-700">
                        Bukti Dukung Terunggah
                    </th>
                    @if($internalLks->isNotEmpty())
                        <th colspan="{{ $internalLks->count() }}" class="p-3 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-r border-gray-200 dark:border-gray-700">
                            Internal (BPS)
                        </th>
                    @endif
                    @if($eksternalLks->isNotEmpty())
                        <th colspan="{{ $eksternalLks->count() }}" class="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                            Eksternal
                        </th>
                    @endif
                </tr>

                {{-- Sub Header Kolom Kode LK --}}
                <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300">
                    <th class="p-3 min-w-[260px] border-r border-gray-200 dark:border-gray-700">Nama Dokumen</th>
                    <th class="p-3 min-w-[140px] border-r border-gray-200 dark:border-gray-700">Nomor Dokumen</th>
                    <th class="p-3 w-16 text-center border-r border-gray-200 dark:border-gray-700">Tahun</th>

                    @foreach($internalLks as $lk)
                        <th class="p-3 text-center min-w-[80px] border-r border-gray-200 dark:border-gray-700 bg-blue-50/50 dark:bg-blue-950/20" title="{{ $lk->name }}">
                            {{ $lk->code }}
                        </th>
                    @endforeach

                    @foreach($eksternalLks as $lk)
                        <th class="p-3 text-center min-w-[80px] border-r border-gray-200 dark:border-gray-700 bg-emerald-50/50 dark:bg-emerald-950/20" title="{{ $lk->name }}">
                            {{ $lk->code }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <td class="p-3 font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                            {{ $row['document_name'] }}
                            @if(!empty($row['document_type']))
                                <span class="ml-1 inline-block text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $row['document_type'] }}
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-gray-600 dark:text-gray-300 text-xs border-r border-gray-200 dark:border-gray-700">
                            {{ $row['document_number'] ?? '-' }}
                        </td>
                        <td class="p-3 text-center text-xs text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700">
                            {{ $row['year'] }}
                        </td>

                        {{-- Kolom Internal --}}
                        @foreach($internalLks as $lk)
                            @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '']; @endphp
                            <td class="p-3 text-center border-r border-gray-200 dark:border-gray-700">
                                @if($cell['has_match'])
                                    <button 
                                        type="button"
                                        wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold hover:scale-110 transition shadow-sm cursor-pointer"
                                        title="Klik untuk melihat rincian indikator cocok">
                                        {{ $cell['symbol'] }}
                                    </button>
                                @endif
                                {{-- Sel Kosong Murni jika tidak cocok (MON-02, AC-09) --}}
                            </td>
                        @endforeach

                        {{-- Kolom Eksternal --}}
                        @foreach($eksternalLks as $lk)
                            @php $cell = $row['cells'][$lk->code] ?? ['has_match' => false, 'symbol' => '']; @endphp
                            <td class="p-3 text-center border-r border-gray-200 dark:border-gray-700">
                                @if($cell['has_match'])
                                    <button 
                                        type="button"
                                        wire:click="openDrilldown('{{ $row['document_id'] }}', '{{ $cell['lk_id'] }}')"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold hover:scale-110 transition shadow-sm cursor-pointer"
                                        title="Klik untuk melihat rincian indikator cocok">
                                        {{ $cell['symbol'] }}
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + $internalLks->count() + $eksternalLks->count() }}" class="p-8 text-center text-gray-500">
                            Belum ada dokumen bukti dukung yang terdaftar untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($paginator->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Drilldown Detail Centang (MON-04, MON-05, MON-06, MON-10, AC-12) --}}
    @if($showDrilldown && $drilldownData)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-start justify-between border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            {{ $drilldownData['lk']['code'] }} (Periode {{ $drilldownData['lk']['period'] }})
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $drilldownData['document']['name'] }}
                        </h3>
                        @if(!empty($drilldownData['document']['number']))
                            <p class="text-xs text-gray-500">No: {{ $drilldownData['document']['number'] }}</p>
                        @endif
                    </div>
                    <button wire:click="closeDrilldown" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">
                        &times;
                    </button>
                </div>

                {{-- Daftar Indikator Cocok --}}
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        Dokumen ini cocok dengan indikator-indikator berikut:
                    </p>

                    @foreach($drilldownData['indicators'] as $ind)
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                    [{{ $ind['code'] }}] {{ $ind['title'] }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $ind['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' }}">
                                        {{ $ind['status_label'] }} ({{ $ind['confidence_score'] }}%)
                                    </span>
                                </div>
                            </div>

                            @if(!empty($ind['matched_item_name']))
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    <span class="font-medium">Item Bukti Cocok:</span> {{ $ind['matched_item_name'] }}
                                </p>
                            @endif

                            {{-- Tautan Bukti (MON-05, MON-06, MON-10, AC-12) --}}
                            <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-700/60 flex items-center justify-between text-xs">
                                <div>
                                    @if($ind['has_valid_link'])
                                        <a href="{{ $ind['evidence_link'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-semibold underline">
                                            Buka Tautan Bukti &rarr;
                                        </a>
                                    @else
                                        <span class="text-gray-400 italic">
                                            {{ $ind['link_placeholder'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Aksi Hapus Relasi untuk Admin --}}
                                @if(in_array(Auth::user()?->role, ['super_admin', 'admin'], true))
                                    <button 
                                        type="button"
                                        wire:click="removeMatch('{{ $drilldownData['document']['id'] }}', '{{ $ind['indicator_id'] }}')"
                                        wire:confirm="Yakin ingin mencabut relasi kecocokan ini? Tindakan ini akan menolak match secara permanen."
                                        class="text-red-600 hover:text-red-800 text-xs font-medium cursor-pointer">
                                        Cabut Relasi
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="closeDrilldown" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-300 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

