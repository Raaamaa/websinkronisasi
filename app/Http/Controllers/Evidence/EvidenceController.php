<?php

declare(strict_types=1);

namespace App\Http\Controllers\Evidence;

use App\Exceptions\DuplicateDocumentException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EvidenceController extends Controller
{
    public function __construct(
        protected EvidenceUploadService $uploadService
    ) {}

    /**
     * Upload an evidence document (BD-01, BD-06).
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission('evidence.upload')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah bukti dukung.');
        }

        $validated = $request->validate([
            'document_name' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:128'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'document_type' => ['nullable', 'string', 'max:64'],
            'external_link' => ['nullable', 'url', 'regex:/^https?:\/\/.+/i'],
            'file' => ['required', 'file'],
        ]);

        try {
            $document = $this->uploadService->upload($request->file('file'), $validated, $user);

            return response()->json([
                'message' => 'Bukti dukung berhasil diunggah dan sedang diproses.',
                'data' => $document,
            ], 201);
        } catch (DuplicateDocumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'existing_document' => $e->existingDocument,
            ], 409);
        }
    }

    /**
     * Download or stream private evidence file (BD-10).
     */
    public function download(string $id, Request $request): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission('evidence.view_link')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh bukti dukung.');
        }

        $document = EvidenceDocument::findOrFail($id);

        if (! Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('private')->download($document->file_path, $document->original_filename);
    }

    /**
     * Soft delete evidence document (BD-09 - Super Admin only).
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $user = $request->user();

        // BD-09: Penghapusan master BD HANYA oleh Super Admin
        if ($user === null || ! $user->isSuperAdmin()) {
            abort(403, 'Penghapusan bukti dukung hanya diizinkan untuk Super Admin.');
        }

        $document = EvidenceDocument::findOrFail($id);
        $document->delete();

        // Catat jejak audit penghapusan (AC-17)
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'bd.deleted',
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $document->id,
            'old_values' => [
                'document_name' => $document->document_name,
                'file_hash' => $document->file_hash,
                'file_path' => $document->file_path,
            ],
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus secara soft delete.',
        ]);
    }
}
