<?php

declare(strict_types=1);

namespace App\Services\Monitor;

use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
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
     * Get aggregate KPI statistics for the monitor dashboard.
     *
     * @return array{
     *     total_lks: int,
     *     total_documents: int,
     *     total_links: int
     * }
     */
    public function getMatrixStats(string|int $period = '12 Bulan'): array
    {
        $totalLks = LembarKerja::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($period, fn ($q) => $this->applyPeriodFilter($q, $period))
            ->count();

        $totalDocuments = EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->where('approval_status', 'disetujui')
            ->count();

        $totalLinks = DocumentLkLink::query()
            ->whereIn('link_source', DocumentLkLink::OFFICIAL_SOURCES)
            ->whereHas('evidenceDocument', fn ($q) => $q->whereNull('deleted_at')->where('approval_status', 'disetujui'))
            ->whereHas('lembarKerja', function ($q) use ($period): void {
                $q->where('is_active', true)->whereNull('deleted_at');
                if ($period) {
                    $this->applyPeriodFilter($q, $period);
                }
            })
            ->distinct()
            ->count('document_id');

        return [
            'total_lks' => $totalLks,
            'total_documents' => $totalDocuments,
            'total_links' => $totalLinks,
        ];
    }

    /**
     * Get active Lembar Kerja grouped by internal and external categories.
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

        $all = $query->get();

        return [
            'internal' => $all->filter(fn (LembarKerja $lk) => $lk->category === 'internal')->values(),
            'eksternal' => $all->filter(fn (LembarKerja $lk) => $lk->category === 'eksternal')->values(),
        ];
    }

    /**
     * Get paginated monitor matrix rows: Approved documents x Lembar Kerja.
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
        $period = $filters['period'] ?? '12 Bulan';
        $category = ! empty($filters['category']) ? (string) $filters['category'] : null;
        $columnGroups = $this->getColumnGroups($period, $category);

        $allLks = $columnGroups['internal']->merge($columnGroups['eksternal']);
        $lkCodes = $allLks->pluck('code')->toArray();

        // 1. Query Hanya Dokumen 'disetujui' (AC-08, MON-02, plan.md)
        $docQuery = EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->where('approval_status', 'disetujui');

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

        // 2. Ambil link resmi (admin, drive_sync, backfill) untuk dokumen di halaman ini
        $links = DocumentLkLink::query()
            ->whereIn('document_id', $docIds)
            ->whereIn('link_source', DocumentLkLink::OFFICIAL_SOURCES)
            ->with(['lembarKerja', 'indicator'])
            ->get();

        // Mapping: [doc_id][lk_code] = true (DISTINCT per LK)
        $docLkMap = [];
        $docSelectedLkIds = [];
        $docLkDriveUrls = [];
        foreach ($links as $link) {
            if ($link->lembarKerja) {
                $docLkMap[$link->document_id][$link->lembarKerja->code] = true;
                $docSelectedLkIds[$link->document_id][] = $link->lk_id;
                if (! empty($link->indicator?->evidence_link)) {
                    $docLkDriveUrls[$link->document_id][$link->lembarKerja->code] = $link->indicator->evidence_link;
                }
            }
        }

        // 3. Susun representasi baris & sel
        $rows = [];
        foreach ($paginator->items() as $doc) {
            $cells = [];

            foreach ($lkCodes as $code) {
                $hasMatch = ! empty($docLkMap[$doc->id][$code]);
                $lk = $allLks->firstWhere('code', $code);
                $driveUrl = $docLkDriveUrls[$doc->id][$code] ?? ($doc->external_link ?: null);

                $cells[$code] = [
                    'has_match' => $hasMatch,
                    'symbol' => $hasMatch ? '✔' : '',
                    'lk_id' => $lk?->id,
                    'drive_url' => $hasMatch ? $driveUrl : null,
                ];
            }

            $displayCode = $doc->document_number ?: ('DOK-'.substr($doc->id, 0, 6));

            $rows[] = [
                'document_id' => $doc->id,
                'code' => $displayCode,
                'document_name' => $doc->document_name,
                'document_number' => $doc->document_number,
                'year' => $doc->year,
                'document_type' => $doc->document_type,
                'external_link' => $doc->external_link,
                'selected_lk_ids' => array_values(array_unique($docSelectedLkIds[$doc->id] ?? [])),
                'cells' => $cells,
            ];
        }

        return [
            'columns' => $columnGroups,
            'rows' => $rows,
            'paginator' => $paginator,
        ];
    }
}
