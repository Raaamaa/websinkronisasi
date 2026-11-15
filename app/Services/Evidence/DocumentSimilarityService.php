<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Models\EvidenceDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentSimilarityService
{
    /**
     * Cari dokumen aktif dengan hash SHA-256 yang identik (BD-05, AC-05).
     *
     * @return array{id: string, judul: string, uploader: array{name: string}, approval_status: string, rujukan: string}|null
     */
    public function findIdenticalByHash(string $fileHash, ?string $excludeId = null): ?array
    {
        $cleanHash = trim($fileHash);
        if ($cleanHash === '') {
            return null;
        }

        /** @var EvidenceDocument|null $existing */
        $existing = EvidenceDocument::query()
            ->with(['uploader'])
            ->whereNull('deleted_at')
            ->where('file_hash', $cleanHash)
            ->when($excludeId !== null, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($existing === null) {
            return null;
        }

        $uploaderName = $existing->uploader?->name ?? 'Sistem';
        $rujukan = $existing->document_number
            ?: ($existing->external_link
                ?: ($existing->original_filename ?: '-'));

        return [
            'id' => (string) $existing->id,
            'judul' => (string) $existing->document_name,
            'document_name' => (string) $existing->document_name,
            'uploader' => [
                'name' => $uploaderName,
            ],
            'uploader_name' => $uploaderName,
            'approval_status' => (string) $existing->approval_status,
            'rujukan' => (string) $rujukan,
            'document_number' => $existing->document_number,
            'external_link' => $existing->external_link,
            'document' => $existing,
        ];
    }

    /**
     * Cari kandidat dokumen mirip menggunakan indeks trigram dan FTS yang sudah ada.
     * Hasil bersifat non-blocking, teks dipotong maks 1500 karakter, dan dibatasi maks 5 kandidat + skor.
     *
     * @return Collection<int, EvidenceDocument>
     */
    public function findSimilarCandidates(
        string|EvidenceDocument $documentOrTitle,
        ?string $documentNumber = null,
        ?string $text = null,
        ?string $excludeId = null,
        int $limit = 5
    ): Collection {
        if ($documentOrTitle instanceof EvidenceDocument) {
            $title = (string) $documentOrTitle->document_name;
            $documentNumber = $documentNumber ?? $documentOrTitle->document_number;
            $text = $text ?? $documentOrTitle->extracted_text;
            $excludeId = $excludeId ?? (string) $documentOrTitle->id;
        } else {
            $title = (string) $documentOrTitle;
        }

        $title = trim($title);
        $docNumber = $documentNumber !== null ? trim($documentNumber) : '';
        $truncatedText = $text !== null ? mb_substr(trim($text), 0, 1500) : '';

        if ($title === '' && $docNumber === '' && $truncatedText === '') {
            return collect();
        }

        $safeLimit = max(1, min($limit, 5));

        $query = EvidenceDocument::query()
            ->with(['uploader', 'officialLkLinks.lembarKerja'])
            ->whereNull('deleted_at')
            ->when($excludeId !== null, fn (Builder $q) => $q->where('id', '!=', $excludeId));

        // Filter kandidat memanfaatkan indeks GIN trigram dan FTS yang sudah terpasang
        $query->where(function (Builder $q) use ($title, $docNumber, $truncatedText): void {
            $hasCondition = false;

            if ($title !== '') {
                $q->whereRaw('similarity(document_name, ?) >= 0.2', [$title])
                    ->orWhereRaw('word_similarity(?, document_name) >= 0.4', [$title]);
                $hasCondition = true;
            }

            if ($docNumber !== '') {
                $q->orWhereRaw("similarity(coalesce(document_number, ''), ?) >= 0.25", [$docNumber]);
                $hasCondition = true;
            }

            if ($truncatedText !== '') {
                $q->orWhereRaw(
                    "to_tsvector('indonesian', coalesce(document_name, '') || ' ' || coalesce(document_number, '') || ' ' || coalesce(extracted_text, '')) @@ plainto_tsquery('indonesian', ?)",
                    [$truncatedText]
                );
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });

        // Hitung skor kemiripan terpadu
        $query->select('evidence_documents.*');
        $query->selectRaw(
            'GREATEST(
                CASE WHEN ? != \'\' THEN similarity(document_name, ?) ELSE 0.0 END,
                CASE WHEN ? != \'\' AND document_number IS NOT NULL THEN similarity(document_number, ?) ELSE 0.0 END,
                CASE WHEN ? != \'\' THEN ts_rank(
                    to_tsvector(\'indonesian\', coalesce(document_name, \'\') || \' \' || coalesce(document_number, \'\') || \' \' || coalesce(extracted_text, \'\')),
                    plainto_tsquery(\'indonesian\', ?)
                ) ELSE 0.0 END
            ) AS score',
            [
                $title, $title,
                $docNumber, $docNumber,
                $truncatedText, $truncatedText,
            ]
        );

        $query->orderByRaw('score DESC');
        $query->limit($safeLimit);

        /** @var Collection<int, EvidenceDocument> $candidates */
        $candidates = $query->get();

        $candidates->each(function (EvidenceDocument $candidate): void {
            $rawScore = (float) ($candidate->getAttribute('score') ?? 0.0);
            $candidate->setAttribute('score', round($rawScore, 4));
            $candidate->setAttribute('similarity_score', round($rawScore, 4));
        });

        return $candidates;
    }
}
