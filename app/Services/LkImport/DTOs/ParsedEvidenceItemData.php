<?php

declare(strict_types=1);

namespace App\Services\LkImport\DTOs;

readonly class ParsedEvidenceItemData
{
    public function __construct(
        public string $rawTemplateName,
        public string $resolvedName,
        public int $orderIndex,
        public ?string $evidenceLink = null,
    ) {}
}
