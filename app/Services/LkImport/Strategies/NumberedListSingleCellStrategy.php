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

class NumberedListSingleCellStrategy implements ParsingStrategyInterface
{
    public function parse(
        Spreadsheet $spreadsheet,
        LkImportProfile $profile,
        int $period,
        YearPlaceholderResolver $yearResolver,
        ExcelParserService $parserService
    ): ParsedImportResult {
        $sheet = $spreadsheet->getSheetByName($profile->sheet_name);
        if ($sheet === null) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $mapping = $profile->column_mapping;
        $codeCol = (string) ($mapping['code_col'] ?? 'B');
        $titleCol = (string) ($mapping['title_col'] ?? 'C');
        $itemsCol = (string) ($mapping['evidence_items_col'] ?? 'D');
        $linkCol = isset($mapping['evidence_link_col']) ? (string) $mapping['evidence_link_col'] : null;
        $picCol = isset($mapping['pic_col']) ? (string) $mapping['pic_col'] : null;

        $highestRow = $sheet->getHighestRow();
        $startRow = $profile->header_row + 1;

        $indicators = [];
        $skippedRows = [];
        $totalItemsCount = 0;
        $indicatorOrder = 1;

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $codeVal = trim((string) $sheet->getCell($codeCol.$row)->getFormattedValue());
            $titleVal = trim((string) $sheet->getCell($titleCol.$row)->getFormattedValue());

            // 1. Skip completely empty rows
            if ($codeVal === '' && $titleVal === '') {
                $skippedRows[] = new SkippedRowData($row, 'Baris kosong', [
                    'code' => $codeVal,
                    'title' => $titleVal,
                ]);

                continue;
            }

            // 2. Skip rows without indicator code (e.g. section headers, narratives)
            if ($codeVal === '') {
                $skippedRows[] = new SkippedRowData($row, 'Kode indikator kosong atau baris judul/narasi', [
                    'title' => $titleVal,
                ]);

                continue;
            }

            // 3. Skip summary / aggregate rows
            $upperTitle = mb_strtoupper($titleVal);
            $upperCode = mb_strtoupper($codeVal);
            if (
                str_contains($upperTitle, 'TOTAL') ||
                str_contains($upperTitle, 'JUMLAH') ||
                str_contains($upperTitle, 'REKAPITULASI') ||
                str_contains($upperTitle, 'RATA-RATA') ||
                str_contains($upperCode, 'TOTAL')
            ) {
                $skippedRows[] = new SkippedRowData($row, 'Baris rekapitulasi / rumus agregasi', [
                    'code' => $codeVal,
                    'title' => $titleVal,
                ]);

                continue;
            }

            // Evidence link
            $evidenceLink = null;
            if ($linkCol !== null) {
                $linkCell = $sheet->getCell($linkCol.$row);
                $rawLink = $linkCell->hasHyperlink()
                    ? $linkCell->getHyperlink()->getUrl()
                    : trim((string) $linkCell->getFormattedValue());
                $evidenceLink = $parserService->sanitizeUrl($rawLink);
            }

            // PIC
            $pic = null;
            if ($picCol !== null) {
                $picVal = trim((string) $sheet->getCell($picCol.$row)->getFormattedValue());
                $pic = $picVal !== '' ? $picVal : null;
            }

            // Parse evidence items
            $itemsCell = $sheet->getCell($itemsCol.$row);
            $itemsText = trim((string) $itemsCell->getFormattedValue());
            $parsedItems = $this->extractNumberedItems($itemsText, $period, $yearResolver, $evidenceLink);

            $indicators[] = new ParsedIndicatorData(
                code: $codeVal,
                title: $titleVal,
                orderIndex: $indicatorOrder++,
                evidenceLink: $evidenceLink,
                pic: $pic,
                targetUnit: null,
                items: $parsedItems,
                metadata: [
                    'sheet' => $sheet->getTitle(),
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

    /**
     * @return list<ParsedEvidenceItemData>
     */
    private function extractNumberedItems(
        string $text,
        int $period,
        YearPlaceholderResolver $yearResolver,
        ?string $defaultLink
    ): array {
        if ($text === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        if ($lines === false) {
            $lines = [$text];
        }

        $items = [];
        $currentIndex = 1;
        $currentBuffer = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Match numbered bullets like "1.", "1)", "[1]", "1 -"
            if (preg_match('/^(?:\[?\d+[\]\.\)]|\d+\s*-)\s*(.+)$/u', $line, $matches)) {
                if ($currentBuffer !== '') {
                    $resolved = $yearResolver->resolve($currentBuffer, $period);
                    $items[] = new ParsedEvidenceItemData(
                        rawTemplateName: $currentBuffer,
                        resolvedName: $resolved,
                        orderIndex: $currentIndex++,
                        evidenceLink: $defaultLink
                    );
                }
                $currentBuffer = trim($matches[1]);
            } else {
                if ($currentBuffer === '') {
                    $currentBuffer = $line;
                } else {
                    $currentBuffer .= ' '.$line;
                }
            }
        }

        if ($currentBuffer !== '') {
            $resolved = $yearResolver->resolve($currentBuffer, $period);
            $items[] = new ParsedEvidenceItemData(
                rawTemplateName: $currentBuffer,
                resolvedName: $resolved,
                orderIndex: $currentIndex++,
                evidenceLink: $defaultLink
            );
        }

        return $items;
    }
}
