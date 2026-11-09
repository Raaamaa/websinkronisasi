<x-filament-panels::page>
    <div class="space-y-6 max-w-5xl mx-auto">
        {{-- Kartu Panduan Penggunaan --}}
        <div class="rounded-xl border border-sky-200 bg-sky-50/80 p-6 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/30">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-white shadow-sm">
                    <x-heroicon-o-magnifying-glass class="h-7 w-7" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Cari Dokumen &amp; Cek Kecocokan
                    </h2>
                    <p class="mt-1 text-base text-slate-700 dark:text-slate-300">
                        Pilih berkas dari komputer Anda untuk mengecek apakah dokumen tersebut cocok dengan indikator Lembar Kerja. Sistem akan memeriksa berkas tanpa mengubah data apa pun sampai Anda menekan tombol simpan.
                    </p>
                </div>
            </div>
        </div>

        {{-- Form Unggah Berkas --}}
        @if (! $hasEvaluated)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form wire:submit="evaluateDocument" class="space-y-6">
                    <div>
                        <label class="block text-lg font-semibold text-slate-900 dark:text-white mb-2">
                            Pilih Berkas Dokumen Bukti
                        </label>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Menerima format PDF, DOCX (Word), atau Gambar (JPG/PNG) dengan ukuran maksimal 20 MB.
                        </p>

                        <div class="mt-2 flex justify-center rounded-xl border-2 border-dashed border-slate-300 px-6 py-10 transition-colors hover:border-sky-500 dark:border-slate-700 dark:hover:border-sky-400">
                            <div class="text-center space-y-3">
                                <x-heroicon-o-arrow-up-tray class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" />
                                <div class="flex text-base text-slate-700 dark:text-slate-300 justify-center items-center">
                                    <label for="file-upload" class="relative cursor-pointer rounded-lg bg-transparent font-semibold text-sky-600 hover:text-sky-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-sky-500 dark:text-sky-400">
                                        <span>Klik di sini untuk memilih berkas</span>
                                        <input id="file-upload" wire:model="file" type="file" class="sr-only" accept=".pdf,.docx,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    PDF, DOCX, JPG, PNG hingga 20 MB
                                </p>
                            </div>
                        </div>

                        @error('file')
                            <p class="mt-2 text-base font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @if ($file)
                            <div class="mt-4 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-document-check class="h-7 w-7 text-emerald-600 dark:text-emerald-400" />
                                    <div>
                                        <p class="text-base font-semibold text-slate-900 dark:text-white">
                                            {{ $file->getClientOriginalName() }}
                                        </p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">
                                            Ukuran: {{ number_format($file->getSize() / 1024, 1) }} KB
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex min-h-[56px] items-center justify-center rounded-xl bg-sky-600 px-8 py-3 text-base font-bold text-white shadow-sm hover:bg-sky-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 disabled:opacity-50 transition-all cursor-pointer">
                            <span wire:loading.remove wire:target="evaluateDocument" class="flex items-center gap-2">
                                <x-heroicon-o-sparkles class="h-5 w-5" />
                                Periksa Kecocokan Dokumen
                            </span>
                            <span wire:loading wire:target="evaluateDocument" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sedang Memeriksa Berkas...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @else
            {{-- Hasil Pemeriksaan Berkas --}}
            <div class="space-y-6">
                {{-- Banner Ringkasan --}}
                @if ($evaluationResult && $evaluationResult['existing_doc'])
                    <div class="rounded-xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/40">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-information-circle class="h-6 w-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div>
                                <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">
                                    Berkas Sudah Terdaftar di Sistem
                                </h3>
                                <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                                    Dokumen ini sudah pernah disimpan resmi dengan nama &ldquo;{{ $evaluationResult['existing_doc']['document_name'] }}&rdquo; (Tahun {{ $evaluationResult['existing_doc']['year'] }}).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isSaved)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-6 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/40">
                        <div class="flex items-center gap-4">
                            <x-heroicon-o-check-circle class="h-10 w-10 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <div>
                                <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-200">
                                    Dokumen Berhasil Disimpan sebagai Bukti Sah!
                                </h3>
                                <p class="text-base text-emerald-800 dark:text-emerald-300 mt-1">
                                    Berkas telah tersimpan ke direktori bukti privat dan antrean pencocokan otomatis sedang menghubungkan dokumen ke lembar kerja terkait.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errorMessage)
                    <div class="rounded-xl border border-red-200 bg-red-50/90 p-5 shadow-sm dark:border-red-900/50 dark:bg-red-950/40">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
                            <div>
                                <h3 class="text-base font-bold text-red-900 dark:text-red-200">
                                    Peringatan Penyimpanan
                                </h3>
                                <p class="text-sm text-red-800 dark:text-red-300 mt-1">
                                    {{ $errorMessage }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Daftar Kartu Hasil --}}
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                        Hasil Pemeriksaan Kecocokan
                    </h3>

                    @if ($evaluationResult && count($evaluationResult['results']) > 0)
                        <div class="grid grid-cols-1 gap-4">
                            @foreach ($evaluationResult['results'] as $item)
                                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="space-y-2 flex-1">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1 text-sm font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                                    {{ $item['lk_name'] ?? 'Lembar Kerja' }}
                                                </span>
                                                <span class="inline-flex items-center rounded-md bg-sky-100 px-3 py-1 text-sm font-bold text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                                                    Kode: {{ $item['indicator_code'] }}
                                                </span>

                                                @if ($item['status'] === 'confirmed')
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3.5 py-1 text-sm font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                        <x-heroicon-s-check-circle class="h-4 w-4" />
                                                        Pasti Cocok (Langsung Centang ✔)
                                                    </span>
                                                @elseif ($item['status'] === 'auto')
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-3.5 py-1 text-sm font-bold text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                                                        <x-heroicon-s-check-circle class="h-4 w-4" />
                                                        Otomatis Cocok (Langsung Centang ✔)
                                                    </span>
                                                @elseif ($item['status'] === 'perlu_verifikasi')
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3.5 py-1 text-sm font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                        <x-heroicon-s-clock class="h-4 w-4" />
                                                        Perlu Diperiksa Petugas (Belum Centang)
                                                    </span>
                                                @endif
                                            </div>

                                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">
                                                {{ $item['indicator_title'] }}
                                            </h4>

                                            @if (! empty($item['match_item']))
                                                <p class="text-base text-slate-600 dark:text-slate-400">
                                                    <span class="font-medium text-slate-700 dark:text-slate-300">Item Bukti Terkait:</span> {{ $item['match_item'] }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex items-center md:flex-col justify-between md:justify-center rounded-xl bg-slate-50 p-4 text-center dark:bg-slate-800/50 md:min-w-[130px]">
                                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">
                                                Skor Kecocokan
                                            </span>
                                            <span class="text-2xl font-black text-slate-900 dark:text-white">
                                                {{ number_format((float) $item['score'], 1) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <x-heroicon-o-document-magnifying-glass class="mx-auto h-16 w-16 text-slate-400" />
                            <h4 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">
                                Belum Ada Indikator yang Cocok
                            </h4>
                            <p class="mt-2 text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto">
                                Berkas ini tidak memiliki kemiripan yang cukup dengan indikator lembar kerja aktif saat ini.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Tombol Aksi Ramah Lansia (min-h-[56px]) --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        wire:click="resetForm"
                        class="inline-flex min-h-[56px] w-full sm:w-auto items-center justify-center rounded-xl border border-slate-300 bg-white px-8 py-3 text-base font-bold text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer">
                        <x-heroicon-o-arrow-path class="mr-2 h-5 w-5" />
                        Periksa Berkas Lain
                    </button>

                    @if (! $isSaved)
                        <button
                            type="button"
                            wire:click="saveAsOfficialEvidence"
                            wire:loading.attr="disabled"
                            class="inline-flex min-h-[56px] w-full sm:w-auto items-center justify-center rounded-xl bg-emerald-600 px-8 py-3 text-base font-bold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:opacity-50 transition-all cursor-pointer">
                            <span wire:loading.remove wire:target="saveAsOfficialEvidence" class="flex items-center gap-2">
                                <x-heroicon-o-arrow-up-on-square class="h-5 w-5" />
                                Simpan sebagai Bukti Sah
                            </span>
                            <span wire:loading wire:target="saveAsOfficialEvidence" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sedang Menyimpan...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

