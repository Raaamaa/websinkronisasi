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

class ChecklistSheetStrategy implements ParsingStrategyInterface
{
    public function parse(
        Spreadsheet $spreadsheet,
        LkImportProfile $profile,
        int $period,
        YearPlaceholderResolver $yearResolver,
        ExcelParserService $parserService
    ): ParsedImportResult {
        $mapping = $profile->column_mapping;
        $sheetName = $profile->sheet_name;

        $mainSheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        // 1. Read secondary checklist sheet if specified/exists
        $checklistSheetName = (string) ($mapping['checklist_sheet'] ?? 'Checklist');
        $checklistSheet = $spreadsheet->getSheetByName($checklistSheetName);

        $checklistItemsByCode = [];
        if ($checklistSheet !== null) {
            $checkCodeCol = (string) ($mapping['checklist_code_col'] ?? 'A');
            $checkItemCol = (string) ($mapping['checklist_item_col'] ?? 'B');
            $checkLinkCol = isset($mapping['checklist_link_col']) ? (string) $mapping['checklist_link_col'] : null;

            $checkHighestRow = $checklistSheet->getHighestRow();
            for ($r = 2; $r <= $checkHighestRow; $r++) {
                $code = trim((string) $checklistSheet->getCell($checkCodeCol.$r)->getFormattedValue());
                $rawItemName = trim((string) $checklistSheet->getCell($checkItemCol.$r)->getFormattedValue());

                if ($code === '' || $rawItemName === '') {
                    continue;
                }

                $link = null;
                if ($checkLinkCol !== null) {
                    $cCell = $checklistSheet->getCell($checkLinkCol.$r);
                    $rawUrl = $cCell->hasHyperlink()
                        ? $cCell->getHyperlink()->getUrl()
                        : trim((string) $cCell->getFormattedValue());
                    $link = $parserService->sanitizeUrl($rawUrl);
                }

                $checklistItemsByCode[$code][] = [
                    'raw_name' => $rawItemName,
                    'link' => $link,
                ];
            }
        }

        // 2. Read indicators from main sheet
        $codeCol = (string) ($mapping['code_col'] ?? 'A');
        $titleCol = (string) ($mapping['title_col'] ?? 'B');
        $linkCol = isset($mapping['evidence_link_col']) ? (string) $mapping['evidence_link_col'] : null;
        $picCol = isset($mapping['pic_col']) ? (string) $mapping['pic_col'] : null;
        $fallbackItemsCol = isset($mapping['evidence_items_col']) ? (string) $mapping['evidence_items_col'] : null;

        $highestRow = $mainSheet->getHighestRow();
        $startRow = $profile->header_row + 1;

        $indicators = [];
        $skippedRows = [];
        $totalItemsCount = 0;
        $orderIndex = 1;

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $codeVal = trim((string) $mainSheet->getCell($codeCol.$row)->getFormattedValue());
            $titleVal = trim((string) $mainSheet->getCell($titleCol.$row)->getFormattedValue());

            if ($codeVal === '' && $titleVal === '') {
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
                $skippedRows[] = new SkippedRowData($row, 'Baris rekapitulasi / agregasi', [
                    'code' => $codeVal,
                    'title' => $titleVal,
                ]);

                continue;
            }

            $evidenceLink = null;
            if ($linkCol !== null) {
                $lCell = $mainSheet->getCell($linkCol.$row);
                $rawUrl = $lCell->hasHyperlink()
                    ? $lCell->getHyperlink()->getUrl()
                    : trim((string) $lCell->getFormattedValue());
                $evidenceLink = $parserService->sanitizeUrl($rawUrl);
            }

            $pic = null;
            if ($picCol !== null) {
                $picVal = trim((string) $mainSheet->getCell($picCol.$row)->getFormattedValue());
                $pic = $picVal !== '' ? $picVal : null;
            }

            // Evidence items
            $parsedItems = [];
            $itemIdx = 1;

            if (isset($checklistItemsByCode[$codeVal])) {
                foreach ($checklistItemsByCode[$codeVal] as $itemData) {
                    $resolved = $yearResolver->resolve($itemData['raw_name'], $period);
                    $parsedItems[] = new ParsedEvidenceItemData(
                        rawTemplateName: $itemData['raw_name'],
                        resolvedName: $resolved,
                        orderIndex: $itemIdx++,
                        evidenceLink: $itemData['link'] ?? $evidenceLink
                    );
                }
            } elseif ($fallbackItemsCol !== null) {
                $fallbackText = trim((string) $mainSheet->getCell($fallbackItemsCol.$row)->getFormattedValue());
                if ($fallbackText !== '') {
                    $resolved = $yearResolver->resolve($fallbackText, $period);
                    $parsedItems[] = new ParsedEvidenceItemData(
                        rawTemplateName: $fallbackText,
                        resolvedName: $resolved,
                        orderIndex: 1,
                        evidenceLink: $evidenceLink
                    );
                }
            }

            $indicators[] = new ParsedIndicatorData(
                code: $codeVal,
                title: $titleVal,
                orderIndex: $orderIndex++,
                evidenceLink: $evidenceLink,
                pic: $pic,
                targetUnit: null,
                items: $parsedItems,
                metadata: [
                    'sheet' => $mainSheet->getTitle(),
                    'source_row' => $row,
                ]
            );

            $totalItemsCount += count($parsedItems);
        }

        return new ParsedImportResult(
            totalRowsRead: max(0, $highestRow - $profile->header_row),
            indicators: $indicators,
            skippedRows: $skippedRows,
            totalItemsCount: $totalItemsCount
        );
    }
}
