<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Services\Evidence\Extractors\DocxTextExtractor;
use App\Services\Evidence\Extractors\PdfTextExtractor;
use App\Services\Evidence\Extractors\TesseractOcrExtractor;
use Throwable;

class TextExtractionService
{
    public function __construct(
        protected PdfTextExtractor $pdfExtractor,
        protected DocxTextExtractor $docxExtractor,
        protected TesseractOcrExtractor $ocrExtractor
    ) {}

    /**
     * Extract text from evidence document based on MIME and content structure.
     * Guarantees graceful degradation without throwing unhandled exceptions (BD-08, AC-07).
     */
    public function extract(string $filePath, string $mimeType, string $originalFilename): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        try {
            // 1. Dokumen PDF (Digital Native atau Scanned Raster Fallback)
            if ($this->pdfExtractor->supports($mimeType, $extension)) {
                $digitalText = $this->pdfExtractor->extract($filePath);

                // Jika ekstraksi digital menghasilkan teks memadai, gunakan langsung
                if ($digitalText !== null && mb_strlen(trim($digitalText)) >= 20) {
                    return $digitalText;
                }

                // Dokumen PDF berupa scan raster / teks digital kosong -> Fallback ke Tesseract OCR (BD-07, A5)
                $ocrText = $this->ocrExtractor->extractFromPdf($filePath);
                if ($ocrText !== null && mb_strlen(trim($ocrText)) > 0) {
                    return $ocrText;
                }

                return $digitalText; // Kembalikan teks digital jika ada, atau null
            }

            // 2. Dokumen Microsoft Word (DOCX)
            if ($this->docxExtractor->supports($mimeType, $extension)) {
                return $this->docxExtractor->extract($filePath);
            }

            // 3. Dokumen Gambar / Scan Murni (JPG, PNG)
            if ($this->ocrExtractor->supports($mimeType, $extension)) {
                return $this->ocrExtractor->extract($filePath);
            }
        } catch (Throwable) {
            // Tangkap semua kesalahan tanpa crash sistem
            return null;
        }

        return null;
    }
}
