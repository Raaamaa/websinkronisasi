<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class EvidenceUploadService
{
    private const MIME_TO_EXTENSION_MAP = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Allowed MIME types and their associated validation handlers.
     */
    protected array $allowedMimes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'image/jpeg',
        'image/png',
    ];

    public function __construct(
        protected ?DocumentSimilarityService $similarityService = null
    ) {
        $this->similarityService = $this->similarityService ?? app(DocumentSimilarityService::class);
    }

    /**
     * Inspect file integrity, size, and MIME magic bytes sniffing (BD-02, BD-03, AC-06).
     *
     * @return array{mime: string, size: int, hash: string, extension: string}
     */
    public function inspectAndValidate(UploadedFile $file, ?string $excludeId = null): array
    {
        $realPath = $file->getRealPath();
        $fileSize = $file->getSize();

        // 1. Validasi Batas Ukuran Berkas dari konfigurasi dinamis (BD-02)
        $maxSizeMb = (int) config('evidence.max_file_size_mb', 20);
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

        // Jika terdeteksi kontainer ZIP, periksa struktur internal DOCX atau XLSX
        if ($sniffedMime === 'application/zip') {
            $zip = new ZipArchive;
            if ($zip->open($realPath) === true) {
                $hasWordXml = $zip->locateName('word/document.xml') !== false;
                $hasExcelXml = $zip->locateName('xl/workbook.xml') !== false;
                $zip->close();

                if ($hasWordXml) {
                    $sniffedMime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                } elseif ($hasExcelXml) {
                    $sniffedMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                } else {
                    throw ValidationException::withMessages([
                        'file' => 'Berkas ZIP yang diunggah bukan dokumen Microsoft Office (.docx/.xlsx) yang sah.',
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'file' => 'Format arsip berkas rusak atau tidak dapat diverifikasi.',
                ]);
            }
        }

        // 3. Tentukan Ekstensi Aman Berbasis Sniffed Magic Bytes (NFR-04, BD-03, T12)
        if (! isset(self::MIME_TO_EXTENSION_MAP[$sniffedMime])) {
            throw ValidationException::withMessages([
                'file' => "Tipe berkas '{$sniffedMime}' tidak didukung atau tidak dikenal.",
            ]);
        }

        $safeExtension = self::MIME_TO_EXTENSION_MAP[$sniffedMime];

        // 4. Kalkulasi SHA-256 Hash Berkas (BD-04)
        $fileHash = (string) hash_file('sha256', $realPath);

        // 5. Periksa Duplikasi Dokumen Berdasarkan Hash Aktif via DocumentSimilarityService (BD-05, AC-05)
        $duplicate = $this->similarityService->findIdenticalByHash($fileHash, $excludeId);
        if ($duplicate !== null) {
            throw new DuplicateDocumentException($duplicate['document'], $duplicate);
        }

        return [
            'mime' => $sniffedMime,
            'size' => $fileSize,
            'hash' => $fileHash,
            'extension' => $safeExtension,
        ];
    }

    /**
     * Validasi tautan eksternal hanya menerima skema http:// dan https:// (LK-09, AC-25).
     */
    public function validateExternalLink(?string $link): string
    {
        $clean = trim((string) $link);
        if ($clean === '') {
            throw ValidationException::withMessages([
                'external_link' => 'Tautan eksternal wajib diisi.',
            ]);
        }

        if (! preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(\/.*)?$/', $clean)) {
            throw ValidationException::withMessages([
                'external_link' => 'Tautan eksternal harus berupa URL dengan skema http:// atau https:// yang valid.',
            ]);
        }

        return $clean;
    }

    /**
     * Upload and store evidence document with metadata, private storage, and optional proposed LKs (BD-01, BD-06, BD-10).
     */
    public function upload(
        UploadedFile $file,
        array $metadata,
        User $actor,
        array $lkIds = []
    ): EvidenceDocument {
        // Otorisasi server-side: Hanya Admin & Pegawai yang boleh mengunggah bukti (NFR-01, BD-01)
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
            $document = DB::transaction(function () use ($uuid, $metadata, $relativeFilePath, $file, $inspection, $actor, $lkIds): EvidenceDocument {
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
                    'approval_status' => 'menunggu',
                    'source_type' => 'upload',
                ]);

                // Simpan usulan Lembar Kerja jika diberikan (link_source='proposed')
                $targetLkIds = $lkIds ?: ($metadata['lk_ids'] ?? []);
                foreach (array_unique(array_filter($targetLkIds)) as $lkId) {
                    DocumentLkLink::create([
                        'document_id' => $document->id,
                        'lk_id' => $lkId,
                        'indicator_id' => null,
                        'link_source' => 'proposed',
                        'created_by' => $actor->id,
                    ]);
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
                        'approval_status' => 'menunggu',
                        'source_type' => 'upload',
                    ],
                ]);

                return $document;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Bersihkan berkas fisik yatim saat terjadi konflik duplikasi bersamaan (race condition)
            Storage::disk('private')->delete($relativeFilePath);

            $duplicate = $this->similarityService->findIdenticalByHash($inspection['hash']);
            if ($duplicate === null) {
                throw $e;
            }

            throw new DuplicateDocumentException($duplicate['document'], $duplicate);
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($relativeFilePath);

            throw $e;
        }

        // Dispatch job pemrosesan teks di antrean asinkron Redis (TSK-404, MATCH-01, NFR-06)
        ProcessEvidenceDocumentJob::dispatch($document->id);

        return $document;
    }

    /**
     * Daftarkan bukti dukung berbasis tautan eksternal (source_type='link') untuk berkas besar / Google Drive.
     */
    public function createLinkSubmission(
        array $metadata,
        User $actor,
        array $lkIds = []
    ): EvidenceDocument {
        if (! $actor->hasPermission('evidence.upload')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah bukti dukung.');
        }

        $link = $this->validateExternalLink($metadata['external_link'] ?? null);

        if (empty($metadata['document_name'])) {
            throw ValidationException::withMessages([
                'document_name' => 'Nama dokumen wajib diisi.',
            ]);
        }

        $year = isset($metadata['year']) ? (int) $metadata['year'] : (int) date('Y');
        $uuid = (string) Str::uuid();

        return DB::transaction(function () use ($uuid, $metadata, $link, $year, $actor, $lkIds): EvidenceDocument {
            $document = EvidenceDocument::create([
                'id' => $uuid,
                'document_name' => $metadata['document_name'],
                'document_number' => $metadata['document_number'] ?? null,
                'year' => $year,
                'document_type' => $metadata['document_type'] ?? null,
                'external_link' => $link,
                'source_type' => 'link',
                'approval_status' => 'menunggu',
                'processing_status' => 'selesai',
                'uploaded_by' => $actor->id,
            ]);

            $targetLkIds = $lkIds ?: ($metadata['lk_ids'] ?? []);
            foreach (array_unique(array_filter($targetLkIds)) as $lkId) {
                DocumentLkLink::create([
                    'document_id' => $document->id,
                    'lk_id' => $lkId,
                    'indicator_id' => null,
                    'link_source' => 'proposed',
                    'created_by' => $actor->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.link_submitted',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $document->id,
                'new_values' => [
                    'document_name' => $document->document_name,
                    'external_link' => $document->external_link,
                    'source_type' => 'link',
                    'approval_status' => 'menunggu',
                ],
            ]);

            return $document;
        });
    }

    /**
     * Bangun nama berkas unduhan terstandar berawalan kode LK resmi (maks 4 kode, urut abjad) atau DRAFT,
     * tersanitasi dari karakter ilegal/kontrol, panjang total <= 150 karakter dengan ekstensi utuh.
     */
    public function buildDownloadFilename(EvidenceDocument $document): string
    {
        $codes = $document->lembarKerja()
            ->orderBy('lembar_kerja.code')
            ->pluck('lembar_kerja.code')
            ->map(fn (mixed $code): string => $this->sanitizeFilenameSegment((string) $code))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique()
            ->sort()
            ->values()
            ->take(4)
            ->all();

        $prefix = $codes !== [] ? implode('_', $codes) : 'DRAFT';
        $extension = $this->resolveSafeDownloadExtension($document);

        $rawName = trim((string) $document->document_name);
        if ($rawName === '') {
            $rawName = (string) pathinfo((string) $document->original_filename, PATHINFO_FILENAME);
        }

        $dotExt = '.'.$extension;
        if (strlen($rawName) > strlen($dotExt) && str_ends_with(strtolower($rawName), strtolower($dotExt))) {
            $rawName = substr($rawName, 0, -strlen($dotExt));
        }

        $cleanName = $this->sanitizeFilenameSegment($rawName);
        if ($cleanName === '') {
            $cleanName = 'Dokumen';
        }

        $body = $prefix.'_'.$cleanName;
        $maxBodyBytes = 150 - strlen($dotExt);
        if (strlen($body) > $maxBodyBytes) {
            $body = rtrim(mb_strcut($body, 0, $maxBodyBytes, 'UTF-8'), '._ ');
        }

        return $body.$dotExt;
    }

    private function sanitizeFilenameSegment(string $value): string
    {
        $sanitized = (string) preg_replace('/[\/\\\\:*?"<>|\x00-\x1F\x7F]+/u', '_', $value);
        $sanitized = (string) preg_replace('/[\s_]+/u', '_', $sanitized);

        return trim($sanitized, '._ ');
    }

    private function resolveSafeDownloadExtension(EvidenceDocument $document): string
    {
        $allowedExtensions = ['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png'];

        $origExt = strtolower((string) pathinfo((string) $document->original_filename, PATHINFO_EXTENSION));
        $mimeExt = self::MIME_TO_EXTENSION_MAP[(string) $document->mime_type] ?? '';
        $pathExt = strtolower((string) pathinfo((string) $document->file_path, PATHINFO_EXTENSION));

        if ($mimeExt !== '') {
            return ($origExt === 'jpeg' && $mimeExt === 'jpg') ? 'jpeg' : $mimeExt;
        }

        if (in_array($origExt, $allowedExtensions, true)) {
            return $origExt;
        }

        if (in_array($pathExt, $allowedExtensions, true)) {
            return $pathExt;
        }

        return 'pdf';
    }

    /**
     * Revisi pengajuan bukti dukung oleh pemilik asli saat status='revisi'.
     */
    public function reviseSubmission(
        EvidenceDocument $doc,
        User $actor,
        array $metadata = [],
        ?UploadedFile $file = null,
        ?array $lkIds = null
    ): EvidenceDocument {
        // 1. Validasi Pemilik Dokumen
        if ($doc->uploaded_by !== $actor->id) {
            abort(403, 'Hanya pengunggah asli yang berhak merevisi dokumen ini.');
        }

        // 2. Validasi Status Dokumen
        if ($doc->approval_status !== 'revisi') {
            throw ValidationException::withMessages([
                'approval_status' => 'Dokumen hanya dapat direvisi jika berstatus revisi.',
            ]);
        }

        $uploadedFile = $file ?? ($metadata['file'] ?? null);
        $inspection = null;
        $newRelativePath = null;

        if ($uploadedFile instanceof UploadedFile) {
            $inspection = $this->inspectAndValidate($uploadedFile, $doc->id);
            $uuid = (string) Str::uuid();
            $storedFilename = "{$uuid}.{$inspection['extension']}";
            $newRelativePath = "evidence/{$storedFilename}";

            $fileContent = (string) file_get_contents($uploadedFile->getRealPath());
            Storage::disk('private')->put($newRelativePath, $fileContent);
        }

        try {
            return DB::transaction(function () use ($doc, $actor, $metadata, $uploadedFile, $inspection, $newRelativePath, $lkIds): EvidenceDocument {
                $oldFilePath = $doc->file_path;
                $oldValues = $doc->only(['document_name', 'document_number', 'year', 'file_hash', 'approval_status']);

                $updateData = [
                    'approval_status' => 'menunggu',
                    'approval_notes' => null,
                ];

                if (isset($metadata['document_name'])) {
                    $updateData['document_name'] = $metadata['document_name'];
                }
                if (array_key_exists('document_number', $metadata)) {
                    $updateData['document_number'] = $metadata['document_number'];
                }
                if (isset($metadata['year'])) {
                    $updateData['year'] = (int) $metadata['year'];
                }
                if (array_key_exists('document_type', $metadata)) {
                    $updateData['document_type'] = $metadata['document_type'];
                }

                if ($uploadedFile instanceof UploadedFile && $inspection !== null && $newRelativePath !== null) {
                    $updateData['file_path'] = $newRelativePath;
                    $updateData['original_filename'] = $uploadedFile->getClientOriginalName();
                    $updateData['mime_type'] = $inspection['mime'];
                    $updateData['file_size_bytes'] = $inspection['size'];
                    $updateData['file_hash'] = $inspection['hash'];
                    $updateData['processing_status'] = 'memproses';
                    $updateData['extracted_text'] = null;
                    $updateData['source_type'] = 'upload';
                } elseif (array_key_exists('external_link', $metadata) && $metadata['external_link'] !== null) {
                    $link = $this->validateExternalLink($metadata['external_link']);
                    $updateData['external_link'] = $link;
                }

                $doc->update($updateData);

                $targetLkIds = $lkIds ?? ($metadata['lk_ids'] ?? null);
                if ($targetLkIds !== null) {
                    DocumentLkLink::where('document_id', $doc->id)
                        ->where('link_source', 'proposed')
                        ->delete();

                    foreach (array_unique(array_filter($targetLkIds)) as $lkId) {
                        DocumentLkLink::create([
                            'document_id' => $doc->id,
                            'lk_id' => $lkId,
                            'indicator_id' => null,
                            'link_source' => 'proposed',
                            'created_by' => $actor->id,
                        ]);
                    }
                }

                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'bd.revised',
                    'entity_type' => 'EvidenceDocument',
                    'entity_id' => $doc->id,
                    'old_values' => $oldValues,
                    'new_values' => [
                        'document_name' => $doc->document_name,
                        'approval_status' => $doc->approval_status,
                        'has_new_file' => $uploadedFile !== null,
                    ],
                ]);

                if ($newRelativePath !== null && $oldFilePath) {
                    DB::afterCommit(function () use ($oldFilePath): void {
                        Storage::disk('private')->delete($oldFilePath);
                    });
                }

                if ($uploadedFile !== null) {
                    DB::afterCommit(function () use ($doc): void {
                        ProcessEvidenceDocumentJob::dispatch($doc->id);
                    });
                }

                return $doc->fresh();
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($newRelativePath !== null) {
                Storage::disk('private')->delete($newRelativePath);
            }

            $lookupHash = (string) ($doc->file_hash ?: ($inspection['hash'] ?? ''));
            if ($lookupHash !== '') {
                $duplicate = $this->similarityService->findIdenticalByHash($lookupHash, $doc->id);
                if ($duplicate !== null) {
                    throw new DuplicateDocumentException($duplicate['document'], $duplicate);
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            if ($newRelativePath !== null) {
                Storage::disk('private')->delete($newRelativePath);
            }

            throw $e;
        }
    }
}
