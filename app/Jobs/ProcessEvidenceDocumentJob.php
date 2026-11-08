<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Services\Evidence\TextExtractionService;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessEvidenceDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $documentId
    ) {}

    public function handle(TextExtractionService $extractor, ?MatchingEngineService $matchingEngine = null): void
    {
        $document = EvidenceDocument::find($this->documentId);

        if ($document === null || $document->trashed()) {
            return;
        }

        $tempFile = null;

        try {
            // Tentukan jalur file fisik
            $disk = Storage::disk('private');
            $filePath = $document->file_path;

            if (! $disk->exists($filePath)) {
                $document->update([
                    'processing_status' => 'perlu_review_manual',
                    'error_message' => 'Berkas fisik tidak ditemukan di penyimpanan.',
                ]);

                return;
            }

            $absolutePath = $disk->path($filePath);

            // Jika adapter storage bukan local direct (atau di test fake memory), buat temp file untuk parser CLI
            if (! file_exists($absolutePath)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'evid_job_');
                file_put_contents($tempFile, $disk->get($filePath));
                $absolutePath = $tempFile;
            }

            // Jalankan ekstraksi teks
            $extractedText = $extractor->extract(
                $absolutePath,
                $document->mime_type,
                $document->original_filename
            );

            if ($extractedText !== null && mb_strlen(trim($extractedText)) > 0) {
                $document->update([
                    'extracted_text' => $extractedText,
                    'processing_status' => 'selesai',
                    'error_message' => null,
                ]);
            } else {
                // AC-07: Scan tanpa teks atau OCR gagal -> status perlu_review_manual
                $document->update([
                    'extracted_text' => null,
                    'processing_status' => 'perlu_review_manual',
                    'error_message' => 'Teks tidak dapat diekstrak secara otomatis. Memerlukan peninjauan manual.',
                ]);
            }
        } catch (Throwable $e) {
            // Tangkap semua exception agar antrean tidak gagal fatal / 500
            $document->update([
                'processing_status' => 'perlu_review_manual',
                'error_message' => 'Kegagalan pemrosesan dokumen: '.$e->getMessage(),
            ]);
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        // Catat jejak audit pemrosesan
        try {
            AuditLog::create([
                'user_id' => $document->uploaded_by,
                'action' => 'bd.processed',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $document->id,
                'new_values' => [
                    'status' => $document->processing_status,
                    'has_text' => ! empty($document->extracted_text),
                ],
            ]);
        } catch (Throwable) {
            // Jangan gagalkan alur jika logging mengalami kendala
        }

        // Jalankan Matching Pipeline otomatis setelah ekstraksi teks (MATCH-01)
        try {
            $engine = $matchingEngine ?? app(MatchingEngineService::class);
            $engine->matchDocument($document);
        } catch (Throwable $e) {
            $document->update([
                'processing_status' => 'perlu_review_manual',
                'error_message' => 'Kegagalan matching engine: '.$e->getMessage(),
            ]);

            Log::error('ProcessEvidenceDocumentJob matching failure: '.$e->getMessage(), [
                'document_id' => $document->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
