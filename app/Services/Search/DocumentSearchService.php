<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\EvidenceDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentSearchService
{
    /**
     * Maximum character limit for search queries to prevent memory and CPU exhaustion (SEARCH-05, SEARCH-06).
     */
    public const MAX_QUERY_LENGTH = 500;

    /**
     * Minimum trigram similarity score threshold for typo-tolerant fuzzy matching (SEARCH-02).
     */
    public const TRIGRAM_SIMILARITY_THRESHOLD = 0.25;

    /**
     * Sanitize search input to prevent SQL injection and handle malformed strings cleanly (SEARCH-05, SEARCH-06, AC-13).
     */
    public function sanitizeQuery(?string $rawQuery): string
    {
        if ($rawQuery === null) {
            return '';
        }

        // Strip null bytes and control characters
        $clean = str_replace(chr(0), '', $rawQuery);
        $clean = trim($clean);

        // Truncate to maximum safe length
        if (mb_strlen($clean) > self::MAX_QUERY_LENGTH) {
            $clean = mb_substr($clean, 0, self::MAX_QUERY_LENGTH);
        }

        return $clean;
    }

    /**
     * Apply parameterized FTS, substring, and trigram fuzzy search filters to an Eloquent query builder.
     *
     * @param  Builder<EvidenceDocument>  $builder
     * @return Builder<EvidenceDocument>
     */
    public function applySearchFilter(Builder $builder, ?string $rawQuery): Builder
    {
        $sanitized = $this->sanitizeQuery($rawQuery);

        if ($sanitized === '') {
            return $builder;
        }

        $likeParam = '%'.$sanitized.'%';
        $threshold = self::TRIGRAM_SIMILARITY_THRESHOLD;

        return $builder->where(function (Builder $q) use ($sanitized, $likeParam, $threshold): void {
            // 1. PostgreSQL Full-Text Search (SEARCH-01, Indonesian stemming & stopword dictionary)
            $q->whereRaw(
                "to_tsvector('indonesian', coalesce(document_name, '') || ' ' || coalesce(document_number, '') || ' ' || coalesce(extracted_text, '')) @@ plainto_tsquery('indonesian', ?)",
                [$sanitized]
            )
            // 2. Substring matching for name and document number
                ->orWhere('document_name', 'ilike', $likeParam)
                ->orWhere('document_number', 'ilike', $likeParam)
            // 3. Typo-tolerant Trigram similarity search (SEARCH-02)
                ->orWhereRaw('similarity(document_name, ?) >= ?', [$sanitized, $threshold])
                ->orWhereRaw("similarity(coalesce(document_number, ''), ?) >= ?", [$sanitized, $threshold])
                ->orWhereRaw('word_similarity(?, document_name) >= 0.55', [$sanitized])
                ->orWhereRaw("word_similarity(?, coalesce(document_number, '')) >= 0.55", [$sanitized]);
        });
    }

    /**
     * Perform unified multi-column search on active evidence documents.
     *
     * @return Collection<int, EvidenceDocument>
     */
    public function search(?string $rawQuery, ?int $limit = 50): Collection
    {
        $sanitized = $this->sanitizeQuery($rawQuery);

        if ($sanitized === '') {
            return collect();
        }

        $query = EvidenceDocument::query()
            ->whereNull('deleted_at');

        $this->applySearchFilter($query, $sanitized);

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
