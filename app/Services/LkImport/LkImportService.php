<?php

declare(strict_types=1);

namespace App\Services\LkImport;

use App\Jobs\RematchActiveDocumentsJob;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportLog;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\LkImport\DTOs\ParsedImportResult;
use Illuminate\Support\Facades\DB;

class LkImportService
{
    public function __construct(
        protected ExcelParserService $excelParser
    ) {}

    /**
     * Preview import without committing to database (Preview Gate - LKIMP-04).
     */
    public function preview(
        string $filePath,
        LembarKerja $lk,
        LkImportProfile $profile,
        ?string $originalFilename = null,
        ?User $actor = null
    ): ParsedImportResult {
        $filename = $originalFilename ?? basename($filePath);
        $fileHash = hash_file('sha256', $filePath) ?: '';

        $result = $this->excelParser->parse($filePath, $profile, $lk->period, $filename);

        if ($actor !== null) {
            LkImportLog::create([
                'lk_id' => $lk->id,
                'user_id' => $actor->id,
                'profile_id' => $profile->id,
                'file_name' => $filename,
                'file_hash' => $fileHash,
                'status' => 'previewed',
                'total_rows_read' => $result->totalRowsRead,
                'imported_indicators' => count($result->indicators),
                'imported_items' => $result->totalItemsCount,
                'skipped_rows' => count($result->skippedRows),
                'summary_details' => [
                    'skipped' => array_map(
                        fn ($r) => ['row' => $r->rowIndex, 'reason' => $r->reason],
                        $result->skippedRows
                    ),
                ],
            ]);
        }

        return $result;
    }

    /**
     * Execute idempotent transactional import of LK (LKIMP-07, AC-23).
     */
    public function commit(
        string $filePath,
        LembarKerja $lk,
        LkImportProfile $profile,
        User $actor,
        ?string $originalFilename = null
    ): LkImportLog {
        $filename = $originalFilename ?? basename($filePath);
        $fileHash = hash_file('sha256', $filePath) ?: '';

        $result = $this->excelParser->parse($filePath, $profile, $lk->period, $filename);

        $log = DB::transaction(function () use ($filename, $fileHash, $lk, $profile, $actor, $result) {
            $importedIndicatorsCount = 0;
            $importedItemsCount = 0;

            foreach ($result->indicators as $indData) {
                // 1. Idempotent upsert indicator by (lk_id, code)
                $indicator = LkIndicator::updateOrCreate(
                    [
                        'lk_id' => $lk->id,
                        'code' => $indData->code,
                    ],
                    [
                        'title' => $indData->title,
                        'order_index' => $indData->orderIndex,
                        'evidence_link' => $indData->evidenceLink,
                        'pic' => $indData->pic,
                        'metadata' => array_merge($indData->metadata, [
                            'target_unit' => $indData->targetUnit,
                        ]),
                    ]
                );
                $importedIndicatorsCount++;

                // 2. Idempotent upsert evidence items by (indicator_id, resolved_name)
                foreach ($indData->items as $itemData) {
                    LkEvidenceItem::updateOrCreate(
                        [
                            'indicator_id' => $indicator->id,
                            'resolved_name' => $itemData->resolvedName,
                        ],
                        [
                            'raw_template_name' => $itemData->rawTemplateName,
                            'order_index' => $itemData->orderIndex,
                        ]
                    );
                    $importedItemsCount++;
                }
            }

            // 3. Record import session log
            $importLog = LkImportLog::create([
                'lk_id' => $lk->id,
                'user_id' => $actor->id,
                'profile_id' => $profile->id,
                'file_name' => $filename,
                'file_hash' => $fileHash,
                'status' => 'committed',
                'total_rows_read' => $result->totalRowsRead,
                'imported_indicators' => $importedIndicatorsCount,
                'imported_items' => $importedItemsCount,
                'skipped_rows' => count($result->skippedRows),
                'summary_details' => [
                    'indicators_count' => $importedIndicatorsCount,
                    'items_count' => $importedItemsCount,
                    'skipped_rows_count' => count($result->skippedRows),
                ],
            ]);

            // 4. Record audit log (append-only trail)
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'lk.imported',
                'entity_type' => 'LembarKerja',
                'entity_id' => (string) $lk->id,
                'new_values' => [
                    'import_log_id' => $importLog->id,
                    'file_name' => $filename,
                    'file_hash' => $fileHash,
                    'indicators_imported' => $importedIndicatorsCount,
                    'items_imported' => $importedItemsCount,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $importLog;
        });

        // Memicu pencocokan ulang otomatis untuk seluruh dokumen terhadap LK ini (MATCH-07, AC-27)
        RematchActiveDocumentsJob::dispatch($lk->id);

        return $log;
    }
}
