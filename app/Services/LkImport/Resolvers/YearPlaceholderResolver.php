<?php

declare(strict_types=1);

namespace App\Services\LkImport\Resolvers;

class YearPlaceholderResolver
{
    /**
     * Resolve dynamic year placeholders like [t], [t-1], [t-2], [t+1] into concrete years.
     */
    public function resolve(string $text, int|string|null $period = null): string
    {
        $baseYear = (is_numeric($period) && (int) $period > 1900) ? (int) $period : (int) date('Y');

        return (string) preg_replace_callback(
            '/\[t(?:\s*([+-])\s*(\d+))?\]/i',
            function (array $matches) use ($baseYear): string {
                if (! isset($matches[1], $matches[2])) {
                    return (string) $baseYear;
                }

                $operator = $matches[1];
                $offset = (int) $matches[2];

                return (string) ($operator === '-' ? $baseYear - $offset : $baseYear + $offset);
            },
            $text
        );
    }
}
