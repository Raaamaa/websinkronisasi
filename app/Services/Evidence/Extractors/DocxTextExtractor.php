<?php

declare(strict_types=1);

namespace App\Services\Evidence\Extractors;

use App\Services\Evidence\Contracts\TextExtractorInterface;
use Throwable;
use ZipArchive;

class DocxTextExtractor implements TextExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/zip',
        ];

        return in_array($mimeType, $allowedMimes, true) || strtolower($extension) === 'docx';
    }

    public function extract(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($filePath) !== true) {
                return null;
            }

            // Baca konten XML dokumen utama
            $contentXml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($contentXml === false || empty($contentXml)) {
                return null;
            }

            // Normalisasi paragraf dan buang tag XML
            $formattedXml = str_replace(['</w:p>', '</w:tr>'], "\n", $contentXml);
            $cleanText = strip_tags($formattedXml);
            $decodedText = html_entity_decode($cleanText, ENT_QUOTES | ENT_XML1, 'UTF-8');

            $trimmedText = trim($decodedText);

            return ! empty($trimmedText) ? $trimmedText : null;
        } catch (Throwable) {
            return null;
        }
    }
}
