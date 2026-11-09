<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EvidenceDocument;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RematchActiveDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?string $lkId = null,
        public readonly ?string $documentId = null
    ) {}

    public function handle(MatchingEngineService $engine): void
    {
        if ($this->documentId !== null) {
            $document = EvidenceDocument::find($this->documentId);
            if ($document !== null && ! $document->trashed()) {
                $engine->matchDocument($document, $this->lkId);
            }

            return;
        }

        if ($this->lkId !== null) {
            $engine->rematchLk($this->lkId);

            return;
        }

        // Rematch seluruh dokumen selesai terhadap seluruh LK aktif dengan chunkById(100)
        EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->where('processing_status', 'selesai')
            ->chunkById(100, function ($documents) use ($engine): void {
                foreach ($documents as $doc) {
                    $engine->matchDocument($doc);
                }
            });
    }
}
