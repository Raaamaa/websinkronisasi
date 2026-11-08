<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class EvidenceUploadService
{
    /**
     * Allowed MIME types and their associated validation handlers.
     */
    protected array $allowedMimes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'image/jpeg',
        'image/png',
    ];

    /**
     * Inspect file integrity, size, and MIME magic bytes sniffing (BD-02, BD-03, AC-06).
     *
     * @return array{mime: string, size: int, hash: string, extension: string}
     */
    public function inspectAndValidate(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $fileSize = $file->getSize();

        // 1. Validasi Batas Ukuran Berkas (BD-02)
        $maxSizeMb = (int) SystemSetting::get('upload.max_file_size_mb', 20);
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;

        if ($fileSize <= 0 || $fileSize > $maxSizeBytes) {
            throw ValidationException::withMessages([
                'file' => "Ukuran berkas ({$fileSize} bytes) tidak valid atau melebihi batas maksimal {$maxSizeMb} MB.",
            ]);
        }

        // 2. Validasi MIME Sniffing Berbasis Magic Bytes (BD-03, AC-06)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $sniffedMime = $finfo !== false ? (string) finfo_file($finfo, $realPath) : $file->getMimeType();
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! in_array($sniffedMime, $this->allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => "Tipe berkas '{$sniffedMime}' tidak diizinkan. Berkas terindikasi manipulasi ekstensi atau tidak valid.",
            ]);
        }

        // Jika terdeteksi application/zip, pastikan berkas merupakan kontainer DOCX sah
        $extension = strtolower($file->getClientOriginalExtension());
        if ($sniffedMime === 'application/zip') {
            $zip = new ZipArchive;
            if ($zip->open($realPath) === true) {
                $hasWordXml = $zip->locateName('word/document.xml') !== false;
                $zip->close();

                if (! $hasWordXml) {
                    throw ValidationException::withMessages([
                        'file' => 'Berkas ZIP yang diunggah bukan dokumen Microsoft Word (.docx) yang sah.',
                    ]);
                }
                $sniffedMime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                $extension = 'docx';
            } else {
                throw ValidationException::withMessages([
                    'file' => 'Format arsip berkas rusak atau tidak dapat diverifikasi.',
                ]);
            }
        }

        // 3. Tentukan Ekstensi Aman Berbasis Sniffed Magic Bytes (NFR-04, BD-03, T12)
        $mimeToExtensionMap = [
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        $safeExtension = $mimeToExtensionMap[$sniffedMime] ?? 'bin';

        // 4. Kalkulasi SHA-256 Hash Berkas (BD-04)
        $fileHash = (string) hash_file('sha256', $realPath);

        // 5. Periksa Duplikasi Dokumen Berdasarkan Hash Aktif (BD-05, AC-05)
        $existingDoc = EvidenceDocument::where('file_hash', $fileHash)->first();
        if ($existingDoc !== null) {
            throw new DuplicateDocumentException($existingDoc);
        }

        return [
            'mime' => $sniffedMime,
            'size' => $fileSize,
            'hash' => $fileHash,
            'extension' => $safeExtension,
        ];
    }

    /**
     * Upload and store evidence document with metadata and private storage (BD-01, BD-06, BD-10).
     */
    public function upload(UploadedFile $file, array $metadata, User $actor): EvidenceDocument
    {
        // Otorisasi server-side: Hanya Admin & Super Admin yang boleh mengunggah bukti (NFR-01, BD-01)
        if (! $actor->hasPermission('evidence.upload')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah bukti dukung.');
        }

        // Jalankan inspeksi keamanan berkas
        $inspection = $this->inspectAndValidate($file);

        $uuid = (string) Str::uuid();
        $storedFilename = "{$uuid}.{$inspection['extension']}";
        $relativeFilePath = "evidence/{$storedFilename}";

        // Simpan berkas fisik ke disk privat terisolasi (BD-10, NFR-04)
        $fileContent = (string) file_get_contents($file->getRealPath());
        Storage::disk('private')->put($relativeFilePath, $fileContent);

        try {
            // Simpan record master EvidenceDocument
            $document = EvidenceDocument::create([
                'id' => $uuid,
                'document_name' => $metadata['document_name'],
                'document_number' => $metadata['document_number'] ?? null,
                'year' => (int) $metadata['year'],
                'document_type' => $metadata['document_type'] ?? null,
                'external_link' => $metadata['external_link'] ?? null,
                'file_path' => $relativeFilePath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $inspection['mime'],
                'file_size_bytes' => $inspection['size'],
                'file_hash' => $inspection['hash'],
                'processing_status' => 'memproses',
                'uploaded_by' => $actor->id,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Bersihkan berkas fisik yatim saat terjadi konflik duplikasi bersamaan (race condition)
            Storage::disk('private')->delete($relativeFilePath);

            $existingDoc = EvidenceDocument::where('file_hash', $inspection['hash'])->first();
            if ($existingDoc === null) {
                $existingDoc = new EvidenceDocument([
                    'file_hash' => $inspection['hash'],
                    'document_name' => $metadata['document_name'],
                ]);
            }

            throw new DuplicateDocumentException($existingDoc);
        }

        // Catat jejak audit tak terhapuskan (AUDIT-01, AC-17)
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'bd.uploaded',
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $document->id,
            'new_values' => [
                'document_name' => $document->document_name,
                'document_number' => $document->document_number,
                'year' => $document->year,
                'file_hash' => $document->file_hash,
                'file_size_bytes' => $document->file_size_bytes,
            ],
        ]);

        // Dispatch job pemrosesan teks di antrean asinkron Redis (TSK-404, MATCH-01, NFR-06)
        ProcessEvidenceDocumentJob::dispatch($document->id);

        return $document;
    }
}
