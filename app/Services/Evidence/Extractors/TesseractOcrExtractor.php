<?php

declare(strict_types=1);

namespace App\Services\Evidence\Extractors;

use App\Services\Evidence\Contracts\TextExtractorInterface;
use Illuminate\Support\Facades\Process;
use Throwable;

class TesseractOcrExtractor implements TextExtractorInterface
{
    protected string $language;

    public function __construct(string $language = 'ind')
    {
        $this->language = $language;
    }

    public function supports(string $mimeType, string $extension): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        return in_array($mimeType, $allowedMimes, true) || in_array(strtolower($extension), $allowedExtensions, true);
    }

    /**
     * OCR on single image file.
     */
    public function extract(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        try {
            $result = Process::run(['tesseract', $filePath, 'stdout', '-l', $this->language]);

            if ($result->successful()) {
                $text = trim($result->output());

                return ! empty($text) ? $text : null;
            }
        } catch (Throwable) {
            // Tangani kegagalan OCR tanpa melempar exception fatal
        }

        return null;
    }

    /**
     * Rasterize PDF pages and run OCR on each page image.
     */
    public function extractFromPdf(string $pdfPath, int $maxPages = 3): ?string
    {
        if (! file_exists($pdfPath)) {
            return null;
        }

        $tempPrefix = sys_get_temp_dir().'/pdf_ocr_'.uniqid('', true);

        try {
            // Konversi PDF halaman 1 s.d. $maxPages menjadi gambar PNG resolusi 150 DPI
            $process = Process::run([
                'pdftoppm',
                '-png',
                '-r',
                '150',
                '-f',
                '1',
                '-l',
                (string) $maxPages,
                $pdfPath,
                $tempPrefix,
            ]);

            $generatedImages = glob("{$tempPrefix}*.png") ?: [];
            sort($generatedImages);

            $combinedText = [];
            foreach ($generatedImages as $imageFile) {
                $pageText = $this->extract($imageFile);
                if (! empty($pageText)) {
                    $combinedText[] = $pageText;
                }
                @unlink($imageFile);
            }

            if (! empty($combinedText)) {
                return implode("\n\n", $combinedText);
            }
        } catch (Throwable) {
            // Graceful fallback
        } finally {
            // Bersihkan sisa gambar temporer
            $remainingImages = glob("{$tempPrefix}*.png") ?: [];
            foreach ($remainingImages as $img) {
                @unlink($img);
            }
        }

        return null;
    }
}
