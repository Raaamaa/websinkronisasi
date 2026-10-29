<?php

declare(strict_types=1);

namespace App\Services\Evidence\Extractors;

use App\Services\Evidence\Contracts\TextExtractorInterface;
use Illuminate\Support\Facades\Process;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfTextExtractor implements TextExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return $mimeType === 'application/pdf' || strtolower($extension) === 'pdf';
    }

    public function extract(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        // 1. Ekstraksi utama menggunakan utilitas native Poppler pdftotext
        try {
            $result = Process::run(['pdftotext', '-layout', $filePath, '-']);
            if ($result->successful()) {
                $text = trim($result->output());
                if (! empty($text)) {
                    return $text;
                }
            }
        } catch (Throwable) {
            // Lanjut ke fallback Smalot jika pdftotext gagal
        }

        // 2. Ekstraksi sekunder menggunakan pustaka PHP Smalot PdfParser
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);
            $text = trim($pdf->getText());

            if (! empty($text)) {
                return $text;
            }
        } catch (Throwable) {
            // Teks digital tidak dapat diekstrak (kemungkinan file scan/raster)
        }

        return null;
    }
}
