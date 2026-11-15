<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EvidenceDocument;
use App\Services\Evidence\Extractors\TesseractOcrExtractor;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDriveFileOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $documentId
    ) {}

    public function handle(?TesseractOcrExtractor $extractor = null, ?MatchingEngineService $matchingEngine = null): void
    {
        // Antrean serial: kunci 1 berkas per waktu
        Cache::lock('drive_ocr_serial_lock', 180)->block(120, function () use ($extractor, $matchingEngine): void {
            $this->processOcr($extractor, $matchingEngine);
        });
    }

    protected function processOcr(?TesseractOcrExtractor $extractor, ?MatchingEngineService $matchingEngine): void
    {
        /** @var EvidenceDocument|null $document */
        $document = EvidenceDocument::find($this->documentId);

        if ($document === null || $document->trashed() || $document->source_type !== 'drive' || empty($document->drive_file_id)) {
            return;
        }

        // Batas ukuran 50 MB
        if ($document->file_size_bytes !== null && $document->file_size_bytes > 52428800) {
            $document->update([
                'processing_status' => 'perlu_review_manual',
                'error_message' => 'Berkas melebihi 50 MB, OCR otomatis dilewati.',
            ]);

            return;
        }

        $apiKey = (string) Config::get('evidence.drive_api_key', '');
        if (trim($apiKey) === '') {
            $document->update([
                'processing_status' => 'perlu_review_manual',
                'error_message' => 'Google Drive API Key belum dikonfigurasi.',
            ]);

            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'drive_ocr_');

        try {
            // Unduh sementara via HTTP GET (alt=media)
            $response = Http::timeout(60)->get("https://www.googleapis.com/drive/v3/files/{$document->drive_file_id}", [
                'alt' => 'media',
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                $document->update([
                    'processing_status' => 'perlu_review_manual',
                    'error_message' => 'Gagal mengunduh berkas Drive untuk OCR: HTTP '.$response->status(),
                ]);

                return;
            }

            if ($tempFile === false) {
                return;
            }

            file_put_contents($tempFile, $response->body());

            $ocr = $extractor ?? app(TesseractOcrExtractor::class);
            $extension = strtolower(pathinfo((string) $document->original_filename, PATHINFO_EXTENSION));
            $extractedText = null;

            if ($extension === 'pdf' || $document->mime_type === 'application/pdf') {
                // PDF: maks 5 halaman
                $extractedText = $ocr->extractFromPdf($tempFile, maxPages: 5);
            } else {
                // Gambar JPG/PNG
                $extractedText = $ocr->extract($tempFile);
            }

            if ($extractedText !== null && mb_strlen(trim($extractedText)) > 0) {
                $document->update([
                    'extracted_text' => $extractedText,
                    'processing_status' => 'selesai',
                    'error_message' => null,
                ]);

                try {
                    $engine = $matchingEngine ?? app(MatchingEngineService::class);
                    $engine->matchDocument($document);
                } catch (Throwable $e) {
                    Log::warning('Gagal matching saran setelah Drive OCR: '.$e->getMessage(), [
                        'document_id' => $document->id,
                    ]);
                }
            } else {
                $document->update([
                    'extracted_text' => null,
                    'processing_status' => 'perlu_review_manual',
                    'error_message' => 'OCR tidak mendeteksi teks.',
                ]);
            }
        } catch (Throwable $e) {
            // Sanitize exception message to never show API key
            $cleanMsg = str_replace($apiKey, '[REDACTED]', $e->getMessage());
            $document->update([
                'processing_status' => 'perlu_review_manual',
                'error_message' => 'Kegagalan pemrosesan OCR Drive: '.$cleanMsg,
            ]);
        } finally {
            // Berkas sementara hasil unduh Drive dihapus seketika setelah ekstraksi (juga saat gagal)
            if ($tempFile !== false && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
