<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\EvidenceDocument;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DuplicateDocumentException extends Exception
{
    public function __construct(
        public readonly EvidenceDocument $existingDocument,
        string $message = 'Dokumen dengan konten identik sudah ada di sistem (Duplikat ditolak).'
    ) {
        parent::__construct($message);
    }

    /**
     * Render an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'existing_document' => [
                'id' => $this->existingDocument->id,
                'document_name' => $this->existingDocument->document_name,
                'document_number' => $this->existingDocument->document_number,
                'year' => $this->existingDocument->year,
                'uploaded_at' => $this->existingDocument->created_at?->toIso8601String(),
            ],
        ], 409);
    }
}
