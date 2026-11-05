<?php

declare(strict_types=1);

namespace App\Services\LkImport\Contracts;

use App\Models\LkImportProfile;
use App\Services\LkImport\DTOs\ParsedImportResult;
use App\Services\LkImport\ExcelParserService;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface ParsingStrategyInterface
{
    /**
     * Parse spreadsheet using import profile configuration for a specific period.
     */
    public function parse(
        Spreadsheet $spreadsheet,
        LkImportProfile $profile,
        int|string $period,
        YearPlaceholderResolver $yearResolver,
        ExcelParserService $parserService
    ): ParsedImportResult;
}
