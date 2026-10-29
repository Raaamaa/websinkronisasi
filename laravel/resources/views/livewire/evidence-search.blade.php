<section class="panel">
    <label class="search-label" for="evidence-search">Kata kunci</label>
    <input id="evidence-search" class="search-input" type="search" wire:model.live.debounce.300ms="query" placeholder="Contoh: SK Kepala, 001/SK/2026">
    <p class="field-help">Pencocokan indikator memakai hash SHA-256; hanya file identik yang dianggap cocok.</p>
    <div wire:loading class="muted">Mencari...</div>
    @if (trim($query) === '')
        <div class="empty-state"><strong>Mulai dengan kata kunci</strong><span>Hasil akan menampilkan LK yang memiliki file identik.</span></div>
    @elseif ($groups->isEmpty())
        <div class="empty-state"><strong>Belum ada kecocokan</strong><span>Coba kata kunci lain atau hubungi admin.</span></div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Nomor</th>
                    @foreach ($lks as $lk)
                        <th>{{ $lk->name }}<small class="table-subhead">{{ $lk->scope === 'internal' ? 'Internal' : 'Eksternal' }}</small></th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($groups as $hash => $records)
                    @php($document = $records->first())
                    <tr wire:key="hash-{{ $hash }}">
                        <td><strong>{{ $document->title }}</strong><small class="table-subhead">{{ $document->original_filename }}</small></td>
                        <td>{{ $document->document_number ?: '—' }}</td>
                        @foreach ($lks as $lk)
                            @php($match = $records->firstWhere('lk_id', $lk->id))
                            <td class="match-cell">
                                @if ($match)
                                    <span class="match-check" aria-label="Cocok">✓</span>
                                    <a href="{{ $match->drive_url }}" target="_blank" rel="noopener noreferrer">Drive</a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="field-help">Maksimal 100 hasil. Tautan Drive mengikuti akses yang diberikan pemilik folder.</p>
    @endif
</section>
