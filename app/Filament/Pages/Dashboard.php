<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        redirect()->to('/admin/monitor-matrix');
    }
}

