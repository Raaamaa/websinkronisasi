<?php

declare(strict_types=1);

namespace App\Services\Drive;

use App\Jobs\ProcessDriveFileOcrJob;
use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\DriveSyncFolder;
use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleDriveSyncService
{
    /**
     * Pola nama tidak informatif untuk OCR selektif (IMG_, WA, WhatsApp Image, Screenshot, SCAN).
     */
    public static function isUninformativeFilename(string $filename): bool
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        return (bool) preg_match('/^(img[-_]|wa[-_]|wa\b|whatsapp[\s_-]*image|screenshot|scan)/i', $name);
    }

    /**
     * Ekstrak ID folder Google Drive dari tautan URL atau teks ID murni.
     */
    public static function extractFolderId(?string $link): ?string
    {
        if (empty($link)) {
            return null;
        }

        $link = trim($link);

        // Jika sudah berupa ID alphanumeric tanpa protokol
        if (preg_match('/^[a-zA-Z0-9_-]{15,}$/', $link)) {
            return $link;
        }

        // Tautan /drive/folders/<id> atau /drive/u/N/folders/<id>
        if (preg_match('#(?:/drive(?:/u/\d+)?/folders/|folders/)([a-zA-Z0-9_-]+)#i', $link, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Jalankan sinkronisasi Google Drive baca-saja.
     *
     * @return array{new: int, updated: int, skipped: int}
     */
    public function sync(?User $actor = null): array
    {
        $apiKey = (string) Config::get('evidence.drive_api_key', '');

        if (trim($apiKey) === '') {
            throw new RuntimeException('Google Drive API Key belum dikonfigurasi.');
        }

        $maxDepth = (int) Config::get('evidence.drive_sync_max_depth', 5);

        // Bangun peta folderId => list<string> lk_id (DISTINCT)
        $folderMap = $this->buildFolderMap();

        $stats = [
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $visitedFolderIds = [];

        foreach ($folderMap as $folderId => $lkIds) {
            $this->syncFolder(
                folderId: $folderId,
                lkIds: $lkIds,
                apiKey: $apiKey,
                depth: 1,
                maxDepth: $maxDepth,
                visitedFolderIds: $visitedFolderIds,
                stats: $stats,
                actor: $actor
            );
        }

        return $stats;
    }

    /**
     * Bangun peta folder sumber dari indikator LK dan drive_sync_folders aktif.
     *
     * @return array<string, list<string>>
     */
    protected function buildFolderMap(): array
    {
        /** @var array<string, list<string>> $map */
        $map = [];

        // 1. Ambil folder dari indikator LK aktif (DISTINCT lk_id, evidence_link)
        $indicators = LkIndicator::query()
            ->whereNull('deleted_at')
            ->whereNotNull('evidence_link')
            ->where('evidence_link', '!=', '')
            ->select('lk_id', 'evidence_link')
            ->distinct()
            ->get();

        foreach ($indicators as $indicator) {
            $folderId = self::extractFolderId((string) $indicator->evidence_link);
            if ($folderId !== null && $folderId !== '') {
                $map[$folderId][] = (string) $indicator->lk_id;
            }
        }

        // 2. Ambil folder manual dari drive_sync_folders aktif
        $manualFolders = DriveSyncFolder::query()
            ->where('is_active', true)
            ->get();

        foreach ($manualFolders as $manualFolder) {
            $folderId = self::extractFolderId((string) $manualFolder->folder_id) ?? (string) $manualFolder->folder_id;
            if ($folderId !== '') {
                if ($manualFolder->lk_id !== null) {
                    $map[$folderId][] = (string) $manualFolder->lk_id;
                } elseif (! isset($map[$folderId])) {
                    $map[$folderId] = [];
                }
            }
        }

        // 3. Unikkan lk_id per folder
        foreach ($map as $folderId => $lkIds) {
            $map[$folderId] = array_values(array_unique(array_filter($lkIds)));
        }

        return $map;
    }

    /**
     * Rekursi pembacaan folder Drive dengan batas kedalaman dan pencegahan siklus.
     *
     * @param  list<string>  $lkIds
     * @param  array<string, bool>  $visitedFolderIds
     * @param  array{new: int, updated: int, skipped: int}  $stats
     */
    protected function syncFolder(
        string $folderId,
        array $lkIds,
        string $apiKey,
        int $depth,
        int $maxDepth,
        array &$visitedFolderIds,
        array &$stats,
        ?User $actor
    ): void {
        if ($depth > $maxDepth || isset($visitedFolderIds[$folderId])) {
            return;
        }

        $visitedFolderIds[$folderId] = true;

        $pageToken = null;

        do {
            $params = [
                'q' => sprintf("'%s' in parents and trashed = false", $folderId),
                'fields' => 'nextPageToken, files(id, name, webViewLink, mimeType, size, md5Checksum, modifiedTime)',
                'pageSize' => 1000,
                'key' => $apiKey,
            ];

            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            try {
                $response = Http::timeout(30)->get('https://www.googleapis.com/drive/v3/files', $params);

                if (! $response->successful()) {
                    throw new RuntimeException("HTTP {$response->status()} saat memanggil Drive API.");
                }
            } catch (Throwable $e) {
                // Jangan bocorkan API key di exception message
                $sanitized = preg_replace('/([?&]key=)[^&]+/', '$1[REDACTED]', $e->getMessage()) ?? 'Gagal Drive API';
                $sanitized = str_replace($apiKey, '[REDACTED]', $sanitized);
                throw new RuntimeException("Gagal menghubungi Google Drive: {$sanitized}");
            }

            $data = $response->json();
            $files = $data['files'] ?? [];
            $pageToken = $data['nextPageToken'] ?? null;

            foreach ($files as $file) {
                $fileMime = $file['mimeType'] ?? '';

                if ($fileMime === 'application/vnd.google-apps.folder') {
                    // Subfolder: rekursi ke dalam subfolder jika belum melebihi batas
                    if ($depth < $maxDepth && ! isset($visitedFolderIds[$file['id']])) {
                        $this->syncFolder(
                            folderId: (string) $file['id'],
                            lkIds: $lkIds,
                            apiKey: $apiKey,
                            depth: $depth + 1,
                            maxDepth: $maxDepth,
                            visitedFolderIds: $visitedFolderIds,
                            stats: $stats,
                            actor: $actor
                        );
                    }
                } else {
                    // Berkas fisik: proses simpan/perbarui
                    $this->processDriveFile($file, $folderId, $lkIds, $stats, $actor);
                }
            }
        } while ($pageToken !== null);

        // Perbarui timestamp last_synced_at jika folder ada di drive_sync_folders
        DriveSyncFolder::where('folder_id', $folderId)->update(['last_synced_at' => now()]);
    }

    /**
     * Simpan dokumen baru atau perbarui metadata dokumen Drive yang sudah ada.
     *
     * @param  array<string, mixed>  $file
     * @param  list<string>  $lkIds
     * @param  array{new: int, updated: int, skipped: int}  $stats
     */
    protected function processDriveFile(
        array $file,
        string $folderId,
        array $lkIds,
        array &$stats,
        ?User $actor
    ): void {
        $driveFileId = (string) $file['id'];

        /** @var EvidenceDocument|null $existing */
        $existing = EvidenceDocument::withTrashed()->where('drive_file_id', $driveFileId)->first();

        // 1. Dokumen soft-deleted TIDAK dihidupkan
        if ($existing !== null && $existing->trashed()) {
            $stats['skipped']++;

            return;
        }

        $fileSize = isset($file['size']) ? (int) $file['size'] : null;
        $modifiedTime = $file['modifiedTime'] ?? null;
        $year = $modifiedTime ? (int) date('Y', strtotime((string) $modifiedTime)) : (int) date('Y');
        $externalLink = $file['webViewLink'] ?? "https://drive.google.com/file/d/{$driveFileId}/view";
        $md5Checksum = $file['md5Checksum'] ?? null;
        $mimeType = $file['mimeType'] ?? null;
        $fileName = (string) $file['name'];

        // 2. Dokumen yang sudah ada: HANYA update metadata Drive
        if ($existing !== null) {
            $existing->update([
                'document_name' => $fileName,
                'original_filename' => $fileName,
                'year' => $year ?: $existing->year,
                'external_link' => $externalLink,
                'drive_folder_id' => $folderId,
                'drive_md5_checksum' => $md5Checksum ?? $existing->drive_md5_checksum,
                'drive_modified_at' => $modifiedTime ? date('Y-m-d H:i:s', strtotime((string) $modifiedTime)) : $existing->drive_modified_at,
                'mime_type' => $mimeType ?? $existing->mime_type,
                'file_size_bytes' => $fileSize ?? $existing->file_size_bytes,
            ]);

            // Tambahkan link ke LK jika belum terhubung, tanpa menimpa link 'admin'
            foreach ($lkIds as $lkId) {
                $existingLink = DocumentLkLink::where('document_id', $existing->id)
                    ->where('lk_id', $lkId)
                    ->whereNull('indicator_id')
                    ->first();

                if ($existingLink === null) {
                    DocumentLkLink::create([
                        'document_id' => $existing->id,
                        'lk_id' => $lkId,
                        'indicator_id' => null,
                        'link_source' => 'drive_sync',
                        'created_by' => $actor?->id,
                    ]);
                }
            }

            $stats['updated']++;

            return;
        }

        // 3. Dokumen BARU
        $hasLk = count($lkIds) >= 1;
        $approvalStatus = $hasLk ? 'disetujui' : 'menunggu';

        /** @var EvidenceDocument $doc */
        $doc = DB::transaction(function () use (
            $fileName,
            $year,
            $externalLink,
            $driveFileId,
            $folderId,
            $md5Checksum,
            $modifiedTime,
            $mimeType,
            $fileSize,
            $approvalStatus,
            $hasLk,
            $actor,
            $lkIds
        ) {
            $createdDoc = EvidenceDocument::create([
                'document_name' => $fileName,
                'original_filename' => $fileName,
                'year' => $year,
                'source_type' => 'drive',
                'drive_file_id' => $driveFileId,
                'drive_folder_id' => $folderId,
                'drive_md5_checksum' => $md5Checksum,
                'drive_modified_at' => $modifiedTime ? date('Y-m-d H:i:s', strtotime((string) $modifiedTime)) : null,
                'mime_type' => $mimeType,
                'file_size_bytes' => $fileSize,
                'external_link' => $externalLink,
                'approval_status' => $approvalStatus,
                'approved_at' => $hasLk ? now() : null,
                'approved_by' => $hasLk ? $actor?->id : null,
                'processing_status' => 'selesai',
            ]);

            if ($hasLk) {
                foreach ($lkIds as $lkId) {
                    DocumentLkLink::firstOrCreate([
                        'document_id' => $createdDoc->id,
                        'lk_id' => $lkId,
                        'indicator_id' => null,
                    ], [
                        'link_source' => 'drive_sync',
                        'created_by' => $actor?->id,
                    ]);
                }

                AuditLog::create([
                    'user_id' => $actor?->id,
                    'action' => 'bd.drive_sync_auto',
                    'entity_type' => 'EvidenceDocument',
                    'entity_id' => $createdDoc->id,
                    'new_values' => [
                        'note' => 'auto dari sinkron Drive',
                        'approval_status' => 'disetujui',
                        'source_type' => 'drive',
                        'drive_file_id' => $driveFileId,
                        'lk_ids' => $lkIds,
                    ],
                ]);
            }

            return $createdDoc;
        });

        // 4. OCR Selektif untuk nama tidak informatif & ukuran <= 50 MB
        if (
            self::isUninformativeFilename($fileName)
            && ($fileSize === null || $fileSize <= 52428800)
            && ($mimeType === 'application/pdf' || str_starts_with((string) $mimeType, 'image/'))
        ) {
            $doc->update(['processing_status' => 'memproses']);
            ProcessDriveFileOcrJob::dispatch($doc->id);
        }

        $stats['new']++;
    }
}
