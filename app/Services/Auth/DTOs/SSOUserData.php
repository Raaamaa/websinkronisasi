<?php

declare(strict_types=1);

namespace App\Services\Auth\DTOs;

readonly class SSOUserData
{
    /**
     * @param  array<string, mixed>  $rawAttributes
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public ?string $nip = null,
        public array $rawAttributes = [],
    ) {}
}
