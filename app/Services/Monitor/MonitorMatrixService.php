<?php

declare(strict_types=1);

namespace App\Services\Monitor;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Services\Search\DocumentSearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonitorMatrixService
{
    /**
     * Get active Lembar Kerja grouped by internal and external categories (MON-01).
     *
     * @return array{internal: Collection<int, LembarKerja>, eksternal: Collection<int, LembarKerja>}
     */
    public function getColumnGroups(int $period = 2026): array
    {
        $lks = LembarKerja::query()
            ->where('period', $period)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();

        return [
            'internal' => $lks->where('category', 'internal')->values(),
            'eksternal' => $lks->where('category', 'eksternal')->values(),
        ];
    }

    /**
     * Get paginated monitor matrix rows with JSON aggregated matches (MON-02, MON-03, MON-08, NFR-05).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     columns: array{internal: Collection<int, LembarKerja>, eksternal: Collection<int, LembarKerja>},
     *     rows: array<int, array<string, mixed>>,
     *     paginator: LengthAwarePaginator
     * }
     */
    public function getMatrixData(array $filters = []): array
    {
        $period = (int) ($filters['period'] ?? date('Y'));
        $columnGroups = $this->getColumnGroups($period);

        $allLks = $columnGroups['internal']->merge($columnGroups['eksternal']);
        $lkCodes = $allLks->pluck('code')->toArray();

        // 1. Query Dokumen Berpaginasi di Sisi Server (MON-08)
        $docQuery = EvidenceDocument::query()
            ->whereNull('deleted_at');

        if (! empty($filters['year'])) {
            $docQuery->where('year', (int) $filters['year']);
        }

        if (! empty($filters['document_type'])) {
            $docQuery->where('document_type', $filters['document_type']);
        }

        if (! empty($filters['search'])) {
            /** @var DocumentSearchService $searchService */
            $searchService = app(DocumentSearchService::class);
            $searchService->applySearchFilter($docQuery, (string) $filters['search']);
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = (int) ($filters['page'] ?? 1);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $docQuery->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        $docIds = collect($paginator->items())->pluck('id')->toArray();

        // 2. Ambil seluruh match aktif ('auto' & 'confirmed') untuk dokumen di halaman ini (MON-02, MON-03)
        $matches = DocumentMatch::query()
            ->with(['indicator.lembarKerja'])
            ->whereIn('document_id', $docIds)
            ->whereIn('status', ['auto', 'confirmed'])
            ->whereHas('indicator.lembarKerja', function ($q) use ($period): void {
                $q->where('period', $period)
                    ->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->get();

        // Grouping matches: [doc_id][lk_code] = array of matches
        $groupedMatches = [];
        foreach ($matches as $match) {
            $lk = $match->indicator?->lembarKerja;
            if ($lk !== null) {
                $groupedMatches[$match->document_id][$lk->code][] = $match;
            }
        }

        // 3. Susun representasi baris & sel
        $rows = [];
        foreach ($paginator->items() as $doc) {
            $cells = [];
            $matchedIndicatorsText = [];

            foreach ($lkCodes as $code) {
                /** @var list<DocumentMatch> $lkMatches */
                $lkMatches = $groupedMatches[$doc->id][$code] ?? [];
                $hasMatch = ! empty($lkMatches);

                $driveUrl = null;
                if ($hasMatch) {
                    $firstMatch = $lkMatches[0];
                    $ind = $firstMatch->indicator;
                    if ($ind !== null) {
                        $driveUrl = $ind->evidence_link ?: ($doc->external_link ?: null);
                        $matchedIndicatorsText[] = "[{$code}] " . ($firstMatch->matchedItem?->resolved_name ?? $ind->title);
                    }
                }

                // MON-02, AC-08, AC-09: Jika ada match -> ✔, jika tidak ada -> KOSONG MURNI ""
                $cells[$code] = [
                    'has_match' => $hasMatch,
                    'symbol' => $hasMatch ? '✔' : '',
                    'count' => count($lkMatches),
                    'lk_id' => $allLks->firstWhere('code', $code)?->id,
                    'drive_url' => $driveUrl,
                ];
            }

            $rows[] = [
                'document_id' => $doc->id,
                'document_name' => $doc->document_name,
                'document_number' => $doc->document_number,
                'year' => $doc->year,
                'document_type' => $doc->document_type,
                'external_link' => $doc->external_link,
                'indicators_summary' => implode(' • ', array_unique($matchedIndicatorsText)),
                'cells' => $cells,
            ];
        }

        return [
            'columns' => $columnGroups,
            'rows' => $rows,
            'paginator' => $paginator,
        ];
    }

    /**
     * Get drill-down modal details when user clicks checkmark (MON-04, MON-05, MON-06, MON-10, AC-12).
     *
     * @return array<string, mixed>
     */
    public function getDrilldownDetails(string $documentId, string $lkId): array
    {
        $document = EvidenceDocument::findOrFail($documentId);
        $lk = LembarKerja::findOrFail($lkId);

        $matches = DocumentMatch::query()
            ->with(['indicator.evidenceItems', 'matchedItem'])
            ->where('document_id', $documentId)
            ->whereHas('indicator', function ($q) use ($lkId): void {
                $q->where('lk_id', $lkId)->whereNull('deleted_at');
            })
            ->whereIn('status', ['auto', 'confirmed'])
            ->get();

        $indicators = $matches->map(function (DocumentMatch $m) use ($document): array {
            $rawLink = $m->indicator->evidence_link ?: $document->external_link;
            $hasValidLink = ! empty($rawLink) && (bool) preg_match('/^https?:\/\/.+/i', $rawLink);

            return [
                'match_id' => $m->id,
                'indicator_id' => $m->indicator_id,
                'code' => $m->indicator->code,
                'title' => $m->indicator->title,
                'status' => $m->status,
                'status_label' => $m->status === 'confirmed' ? 'Terverifikasi' : 'Otomatis',
                'confidence_score' => $m->confidence_score,
                'matched_item_name' => $m->matchedItem?->resolved_name ?: $m->indicator->evidenceItems->first()?->resolved_name,
                'evidence_link' => $hasValidLink ? $rawLink : null,
                'has_valid_link' => $hasValidLink,
                'link_placeholder' => 'tautan belum tersedia',
                'match_reasons' => $m->match_reasons,
            ];
        })->values()->toArray();

        return [
            'document' => [
                'id' => $document->id,
                'name' => $document->document_name,
                'number' => $document->document_number,
                'year' => $document->year,
                'type' => $document->document_type,
                'external_link' => $document->external_link,
            ],
            'lk' => [
                'id' => $lk->id,
                'code' => $lk->code,
                'name' => $lk->name,
                'period' => $lk->period,
            ],
            'indicators' => $indicators,
        ];
    }
}
