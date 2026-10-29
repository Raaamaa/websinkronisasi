<?php

declare(strict_types=1);

namespace App\Services\LkImport\Strategies;

use App\Models\LkImportProfile;
use App\Services\LkImport\Contracts\ParsingStrategyInterface;
use App\Services\LkImport\DTOs\ParsedEvidenceItemData;
use App\Services\LkImport\DTOs\ParsedImportResult;
use App\Services\LkImport\DTOs\ParsedIndicatorData;
use App\Services\LkImport\DTOs\SkippedRowData;
use App\Services\LkImport\ExcelParserService;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SingleItemPerRowStrategy implements ParsingStrategyInterface
{
    public function parse(
        Spreadsheet $spreadsheet,
        LkImportProfile $profile,
        int $period,
        YearPlaceholderResolver $yearResolver,
        ExcelParserService $parserService
    ): ParsedImportResult {
        $sheet = $spreadsheet->getSheetByName($profile->sheet_name) ?? $spreadsheet->getActiveSheet();

        $mapping = $profile->column_mapping;
        $codeCol = (string) ($mapping['code_col'] ?? 'A');
        $titleCol = (string) ($mapping['title_col'] ?? 'B');
        $itemCol = isset($mapping['evidence_items_col']) ? (string) $mapping['evidence_items_col'] : null;
        $linkCol = isset($mapping['evidence_link_col']) ? (string) $mapping['evidence_link_col'] : null;
        $picCol = isset($mapping['pic_col']) ? (string) $mapping['pic_col'] : null;
        $unitCol = $profile->target_unit_column ?? ($mapping['target_unit_col'] ?? null);

        $highestRow = $sheet->getHighestRow();
        $startRow = $profile->header_row + 1;

        /** @var array<string, ParsedIndicatorData> $indicatorsByCode */
        $indicatorsByCode = [];
        $skippedRows = [];
        $totalItemsCount = 0;
        $indicatorOrder = 1;
        $lastCode = null;

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $codeVal = trim((string) $sheet->getCell($codeCol.$row)->getFormattedValue());
            $titleVal = trim((string) $sheet->getCell($titleCol.$row)->getFormattedValue());
            $itemVal = $itemCol !== null ? trim((string) $sheet->getCell($itemCol.$row)->getFormattedValue()) : '';

            // If code is empty but we have lastCode and title/item
            if ($codeVal === '' && $lastCode !== null && ($titleVal !== '' || $itemVal !== '')) {
                $codeVal = $lastCode;
            }

            if ($codeVal === '' && $titleVal === '' && $itemVal === '') {
                $skippedRows[] = new SkippedRowData($row, 'Baris kosong', [
                    'code' => $codeVal,
                    'title' => $titleVal,
                ]);

                continue;
            }

            if ($codeVal === '') {
                $skippedRows[] = new SkippedRowData($row, 'Kode indikator kosong atau baris judul/narasi', [
                    'title' => $titleVal,
                ]);

                continue;
            }

            $upperTitle = mb_strtoupper($titleVal);
            $upperCode = mb_strtoupper($codeVal);
            if (
                str_contains($upperTitle, 'TOTAL') ||
                str_contains($upperTitle, 'JUMLAH') ||
                str_contains($upperTitle, 'REKAPITULASI') ||
                str_contains($upperCode, 'TOTAL')
            ) {
                $skippedRows[] = new SkippedRowData($row, 'Baris rekapitulasi / rumus agregasi', [
                    'code' => $codeVal,
                    'title' => $titleVal,
                ]);

                continue;
            }

            $evidenceLink = null;
            if ($linkCol !== null) {
                $lCell = $sheet->getCell($linkCol.$row);
                $rawUrl = $lCell->hasHyperlink()
                    ? $lCell->getHyperlink()->getUrl()
                    : trim((string) $lCell->getFormattedValue());
                $evidenceLink = $parserService->sanitizeUrl($rawUrl);
            }

            $pic = null;
            if ($picCol !== null) {
                $picVal = trim((string) $sheet->getCell($picCol.$row)->getFormattedValue());
                $pic = $picVal !== '' ? $picVal : null;
            }

            $targetUnit = null;
            if ($unitCol !== null) {
                $unitVal = trim((string) $sheet->getCell($unitCol.$row)->getFormattedValue());
                $targetUnit = $unitVal !== '' ? $unitVal : null;
            }

            $rawItemText = $itemVal !== '' ? $itemVal : $titleVal;
            $resolvedItemName = $yearResolver->resolve($rawItemText, $period);

            if (! isset($indicatorsByCode[$codeVal])) {
                $item = new ParsedEvidenceItemData(
                    rawTemplateName: $rawItemText,
                    resolvedName: $resolvedItemName,
                    orderIndex: 1,
                    evidenceLink: $evidenceLink
                );

                $indicatorsByCode[$codeVal] = new ParsedIndicatorData(
                    code: $codeVal,
                    title: $titleVal !== '' ? $titleVal : $rawItemText,
                    orderIndex: $indicatorOrder++,
                    evidenceLink: $evidenceLink,
                    pic: $pic,
                    targetUnit: $targetUnit,
                    items: [$item],
                    metadata: [
                        'sheet' => $sheet->getTitle(),
                        'source_row' => $row,
                    ]
                );
                $totalItemsCount++;
            } else {
                // Indicator already exists, add item to it
                $currentIndicator = $indicatorsByCode[$codeVal];
                $nextIndex = count($currentIndicator->items) + 1;
                $newItem = new ParsedEvidenceItemData(
                    rawTemplateName: $rawItemText,
                    resolvedName: $resolvedItemName,
                    orderIndex: $nextIndex,
                    evidenceLink: $evidenceLink ?? $currentIndicator->evidenceLink
                );

                $newItems = $currentIndicator->items;
                $newItems[] = $newItem;

                $indicatorsByCode[$codeVal] = new ParsedIndicatorData(
                    code: $currentIndicator->code,
                    title: $currentIndicator->title,
                    orderIndex: $currentIndicator->orderIndex,
                    evidenceLink: $currentIndicator->evidenceLink ?? $evidenceLink,
                    pic: $currentIndicator->pic ?? $pic,
                    targetUnit: $currentIndicator->targetUnit ?? $targetUnit,
                    items: $newItems,
                    metadata: $currentIndicator->metadata
                );
                $totalItemsCount++;
            }

            $lastCode = $codeVal;
        }

        return new ParsedImportResult(
            totalRowsRead: max(0, $highestRow - $profile->header_row),
            indicators: array_values($indicatorsByCode),
            skippedRows: $skippedRows,
            totalItemsCount: $totalItemsCount
        );
    }
}
