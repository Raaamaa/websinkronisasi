<?php

declare(strict_types=1);

namespace App\Services\Evidence\Contracts;

interface TextExtractorInterface
{
    /**
     * Check if this extractor supports the given MIME type or file extension.
     */
    public function supports(string $mimeType, string $extension): bool;

    /**
     * Extract text content from the given physical file path.
     * Returns null if no text could be extracted.
     */
    public function extract(string $filePath): ?string;
}
