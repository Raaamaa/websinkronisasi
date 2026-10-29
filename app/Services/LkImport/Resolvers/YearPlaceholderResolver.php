<?php

declare(strict_types=1);

namespace App\Services\LkImport\Resolvers;

class YearPlaceholderResolver
{
    /**
     * Resolve dynamic year placeholders like [t], [t-1], [t-2], [t+1] into concrete years.
     */
    public function resolve(string $text, int $period): string
    {
        return (string) preg_replace_callback(
            '/\[t(?:\s*([+-])\s*(\d+))?\]/i',
            function (array $matches) use ($period): string {
                if (! isset($matches[1], $matches[2])) {
                    return (string) $period;
                }

                $operator = $matches[1];
                $offset = (int) $matches[2];

                return (string) ($operator === '-' ? $period - $offset : $period + $offset);
            },
            $text
        );
    }
}
