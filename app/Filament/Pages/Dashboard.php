<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->is_active;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        redirect()->to($user?->homeUrl() ?? '/login');
    }
}
