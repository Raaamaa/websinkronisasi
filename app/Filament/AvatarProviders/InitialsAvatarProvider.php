<?php

declare(strict_types=1);

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar inisial berwarna biru BPS, dibuat lokal sebagai data URI
 * sehingga tidak ada permintaan ke layanan luar (ui-avatars.com).
 */
final class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = collect(preg_split('/\s+/', trim(Filament::getNameForDefaultAvatar($record))) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="#0093DD"/><stop offset="1" stop-color="#00629A"/>'
            .'</linearGradient></defs>'
            .'<rect width="64" height="64" fill="url(#g)"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" fill="#FFFFFF" '
            .'font-family="system-ui,Segoe UI,Roboto,Arial,sans-serif" font-size="26" font-weight="700">'
            .e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
