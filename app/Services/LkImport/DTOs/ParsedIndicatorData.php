<?php

declare(strict_types=1);

namespace App\Services\LkImport\DTOs;

readonly class ParsedIndicatorData
{
    /**
     * @param  list<ParsedEvidenceItemData>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $code,
        public string $title,
        public int $orderIndex,
        public ?string $evidenceLink = null,
        public ?string $pic = null,
        public ?string $targetUnit = null,
        public array $items = [],
        public array $metadata = [],
    ) {}
}
