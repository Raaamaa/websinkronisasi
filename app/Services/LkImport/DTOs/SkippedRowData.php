<?php

declare(strict_types=1);

namespace App\Services\LkImport\DTOs;

readonly class SkippedRowData
{
    /**
     * @param  array<string, mixed>  $rowValues
     */
    public function __construct(
        public int $rowIndex,
        public string $reason,
        public array $rowValues = [],
    ) {}
}
