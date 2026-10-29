<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\EvidenceDocument;
use Exception;

class DuplicateDocumentException extends Exception
{
    public function __construct(
        public readonly EvidenceDocument $existingDocument,
        string $message = 'Dokumen dengan konten identik sudah ada di sistem (Duplikat ditolak).'
    ) {
        parent::__construct($message);
    }
}
