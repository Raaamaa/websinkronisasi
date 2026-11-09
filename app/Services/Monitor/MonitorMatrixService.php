<?php

declare(strict_types=1);

namespace App\Services\Monitor;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Services\Search\DocumentSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonitorMatrixService
{
    /**
     * Apply period (year integer) or evaluation_interval (string) filter on Lembar Kerja query.
     */
    protected function applyPeriodFilter(\Illuminate\Contracts\Database\Query\Builder|Builder $query, string|int $period): void
    {
        $raw = (string) $period;
        if (is_numeric($raw)) {
            $query->where('period', (int) $raw);
        } else {
            $query->where('evaluation_interval', $raw);
        }
    }

    /**
     * Get aggregate KPI statistics for the monitor dashboard (Total, Terpenuhi, Belum, Perlu Verifikasi).
     *
     * @return array{
     *     total_indicators: int,
     *     fulfilled_indicators: int,
     *     fulfilled_percentage: float,
     *     unfulfilled_indicators: int,
     *     unfulfilled_percentage: float,
     *     pending_verification: int
     * }
     */
    public function getMatrixStats(string|int $period = '12 Bulan'): array
    {
        $totalIndicators = LkIndicator::query()
            ->whereHas('lembarKerja', function ($q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
                $q->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->count();

        $fulfilledIndicators = DocumentMatch::query()
            ->whereIn('status', ['auto', 'confirmed'])
            ->whereHas('indicator.lembarKerja', function ($q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
                $q->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->distinct('indicator_id')
            ->count('indicator_id');

        $fulfilledPercentage = $totalIndicators > 0 ? round(($fulfilledIndicators / $totalIndicators) * 100, 1) : 0.0;
        $unfulfilledIndicators = max(0, $totalIndicators - $fulfilledIndicators);
        $unfulfilledPercentage = $totalIndicators > 0 ? round(($unfulfilledIndicators / $totalIndicators) * 100, 1) : 0.0;

        $pendingVerification = DocumentMatch::query()
            ->where('status', 'auto')
            ->whereHas('indicator.lembarKerja', function ($q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
                $q->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->count();

        return [
            'total_indicators' => $totalIndicators,
            'fulfilled_indicators' => $fulfilledIndicators,
            'fulfilled_percentage' => $fulfilledPercentage,
            'unfulfilled_indicators' => $unfulfilledIndicators,
            'unfulfilled_percentage' => $unfulfilledPercentage,
            'pending_verification' => $pendingVerification,
        ];
    }

    /**
     * Get active Lembar Kerja grouped by internal and external categories (MON-01).
     *
     * @return array{internal: Collection<int, LembarKerja>, eksternal: Collection<int, LembarKerja>}
     */
    public function getColumnGroups(string|int $period = '12 Bulan', ?string $category = null): array
    {
        $query = LembarKerja::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('code');

        $this->applyPeriodFilter($query, $period);

        if (! empty($category) && in_array($category, ['internal', 'eksternal'], true)) {
            $query->where('category', $category);
        }

        $lks = $query->get();

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
        if (($filters['view_mode'] ?? 'document') === 'indicator') {
            return $this->getIndicatorMatrixData($filters);
        }

        $period = $filters['period'] ?? '12 Bulan';
        $category = ! empty($filters['category']) ? (string) $filters['category'] : null;
        $columnGroups = $this->getColumnGroups($period, $category);

        $allLks = $columnGroups['internal']->merge($columnGroups['eksternal']);
        $lkCodes = $allLks->pluck('code')->toArray();

        // 1. Query Dokumen Berpaginasi di Sisi Server (MON-08)
        $docQuery = EvidenceDocument::query()
            ->whereNull('deleted_at');

        if (! empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
            if ($filters['status_filter'] === 'fulfilled') {
                $docQuery->whereHas('matches', function ($q) use ($period): void {
                    $q->whereIn('status', ['auto', 'confirmed'])
                        ->whereHas('indicator.lembarKerja', function ($lq) use ($period): void {
                            $this->applyPeriodFilter($lq, $period);
                            $lq->where('is_active', true)->whereNull('deleted_at');
                        });
                });
            } elseif ($filters['status_filter'] === 'unfulfilled') {
                $docQuery->whereDoesntHave('matches', function ($q) use ($period): void {
                    $q->whereIn('status', ['auto', 'confirmed'])
                        ->whereHas('indicator.lembarKerja', function ($lq) use ($period): void {
                            $this->applyPeriodFilter($lq, $period);
                            $lq->where('is_active', true)->whereNull('deleted_at');
                        });
                });
            }
        }

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

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $docQuery->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        $docIds = collect($paginator->items())->pluck('id')->toArray();

        // 2. Ambil seluruh match aktif ('auto' & 'confirmed') untuk dokumen di halaman ini (MON-02, MON-03)
        $matches = DocumentMatch::query()
            ->with(['indicator.lembarKerja', 'matchedItem'])
            ->whereIn('document_id', $docIds)
            ->whereIn('status', ['auto', 'confirmed'])
            ->whereHas('indicator.lembarKerja', function ($q) use ($period): void {
                $this->applyPeriodFilter($q, $period);
                $q->where('is_active', true)
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
            $firstPic = null;
            $firstCode = null;

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
                        $matchedIndicatorsText[] = "[{$code}] ".($firstMatch->matchedItem?->resolved_name ?? $ind->title);
                        if ($firstPic === null && ! empty($ind->pic)) {
                            $firstPic = $ind->pic;
                        }
                        if ($firstCode === null && ! empty($ind->code)) {
                            $firstCode = $ind->code;
                        }
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

            if ($firstPic === null) {
                $firstPic = match ($doc->document_type) {
                    'SOP' => 'Humas / IPDS',
                    'Laporan' => 'IPDS',
                    'Data' => 'Nerwilis',
                    default => 'Bagian Umum',
                };
            }

            $displayCode = $doc->document_number ?: ($firstCode ?: 'DOK-'.substr($doc->id, 0, 6));

            $rows[] = [
                'document_id' => $doc->id,
                'code' => $displayCode,
                'pic' => $firstPic,
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

    /**
     * Get paginated monitor matrix rows grouped by Indicator & LK requirement (MON-01, MON-02, MON-03).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     columns: array{internal: Collection<int, LembarKerja>, eksternal: Collection<int, LembarKerja>},
     *     rows: array<int, array<string, mixed>>,
     *     paginator: LengthAwarePaginator
     * }
     */
    public function getIndicatorMatrixData(array $filters = []): array
    {
        $period = $filters['period'] ?? '12 Bulan';
        $category = ! empty($filters['category']) ? (string) $filters['category'] : null;
        $columnGroups = $this->getColumnGroups($period, $category);

        $allLks = $columnGroups['internal']->merge($columnGroups['eksternal']);
        $lkCodes = $allLks->pluck('code')->toArray();
        $lkIds = $allLks->pluck('id')->toArray();

        $query = LkIndicator::query()
            ->with([
                'lembarKerja',
                'evidenceItems',
                'matches' => function ($q): void {
                    $q->whereIn('status', ['auto', 'confirmed'])->with('document');
                },
            ])
            ->whereIn('lk_id', $lkIds)
            ->whereNull('deleted_at');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%")
                    ->orWhere('pic', 'ilike', "%{$search}%")
                    ->orWhereHas('evidenceItems', fn ($iq) => $iq->where('resolved_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('matches.document', fn ($dq) => $dq->where('document_name', 'ilike', "%{$search}%")->orWhere('document_number', 'ilike', "%{$search}%"));
            });
        }

        if (! empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
            if ($filters['status_filter'] === 'fulfilled') {
                $query->whereHas('matches', fn ($mq) => $mq->whereIn('status', ['auto', 'confirmed']));
            } elseif ($filters['status_filter'] === 'unfulfilled') {
                $query->whereDoesntHave('matches', fn ($mq) => $mq->whereIn('status', ['auto', 'confirmed']));
            }
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->orderBy('order_index')->orderBy('code')->paginate($perPage, ['*'], 'page', $page);

        $rows = [];
        foreach ($paginator->items() as $ind) {
            $cells = [];
            $activeMatches = $ind->matches;
            $hasMatch = $activeMatches->isNotEmpty();
            $firstMatch = $activeMatches->first();
            $driveUrl = $ind->evidence_link ?: ($firstMatch?->document?->external_link ?: null);

            foreach ($lkCodes as $code) {
                $isTargetLk = ($ind->lembarKerja?->code === $code);
                $cellHasMatch = $isTargetLk && $hasMatch;

                $cells[$code] = [
                    'has_match' => $cellHasMatch,
                    'symbol' => $cellHasMatch ? '✔' : '',
                    'count' => $cellHasMatch ? $activeMatches->count() : 0,
                    'lk_id' => $allLks->firstWhere('code', $code)?->id,
                    'drive_url' => $cellHasMatch ? $driveUrl : null,
                    'is_target_lk' => $isTargetLk,
                    'matched_doc_name' => $cellHasMatch ? $firstMatch?->document?->document_name : null,
                    'document_id' => $cellHasMatch ? $firstMatch?->document_id : null,
                ];
            }

            $evidenceItemList = $ind->evidenceItems->pluck('resolved_name')->filter()->toArray();

            $rows[] = [
                'row_type' => 'indicator',
                'indicator_id' => $ind->id,
                'document_id' => $firstMatch?->document_id,
                'code' => $ind->code,
                'pic' => $ind->pic ?: 'Subbagian Umum',
                'title' => $ind->title,
                'document_name' => $ind->title,
                'document_number' => $firstMatch?->document?->document_number,
                'year' => $firstMatch?->document?->year ?? (is_numeric($period) ? (int) $period : date('Y')),
                'document_type' => $firstMatch?->document?->document_type ?? 'Indikator LK',
                'external_link' => $driveUrl,
                'has_match' => $hasMatch,
                'matched_doc_name' => $firstMatch?->document?->document_name,
                'matched_score' => $firstMatch?->confidence_score,
                'matched_status' => $firstMatch?->status,
                'matched_document' => $firstMatch?->document,
                'evidence_items' => $evidenceItemList,
                'indicators_summary' => ! empty($evidenceItemList) ? implode(' • ', $evidenceItemList) : null,
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
     * Get drill-down modal details for an indicator.
     *
     * @return array<string, mixed>
     */
    public function getIndicatorDrilldownDetails(string $indicatorId): array
    {
        $indicator = LkIndicator::with(['lembarKerja', 'evidenceItems', 'matches.document'])->findOrFail($indicatorId);
        $lk = $indicator->lembarKerja;

        $activeMatch = $indicator->matches
            ->whereIn('status', ['auto', 'confirmed'])
            ->first();

        $document = $activeMatch?->document;
        $rawLink = $indicator->evidence_link ?: ($document?->external_link ?: null);
        $hasValidLink = ! empty($rawLink) && (bool) preg_match('/^https?:\/\/.+/i', $rawLink);

        return [
            'indicator' => [
                'id' => $indicator->id,
                'code' => $indicator->code,
                'title' => $indicator->title,
                'pic' => $indicator->pic ?: 'Subbagian Umum',
                'evidence_link' => $hasValidLink ? $rawLink : null,
                'has_valid_link' => $hasValidLink,
                'items' => $indicator->evidenceItems->pluck('resolved_name')->filter()->values()->toArray(),
            ],
            'lk' => [
                'id' => $lk?->id,
                'code' => $lk?->code,
                'name' => $lk?->name,
                'period' => $lk?->period,
            ],
            'matched_document' => $document ? [
                'id' => $document->id,
                'name' => $document->document_name,
                'number' => $document->document_number,
                'year' => $document->year,
                'type' => $document->document_type,
                'match_status' => $activeMatch->status,
                'match_score' => $activeMatch->confidence_score,
                'match_method' => $activeMatch->match_method,
            ] : null,
        ];
    }
}
