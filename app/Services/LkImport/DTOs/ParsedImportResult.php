<?php

declare(strict_types=1);

namespace App\Services\LkImport\DTOs;

readonly class ParsedImportResult
{
    /**
     * @param  list<ParsedIndicatorData>  $indicators
     * @param  list<SkippedRowData>  $skippedRows
     */
    public function __construct(
        public int $totalRowsRead,
        public array $indicators,
        public array $skippedRows,
        public int $totalItemsCount,
    ) {}
}
