<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickWorkflowWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-workflow-widget';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'roleLabel' => match ($user?->role) {
                'super_admin' => 'Super Administrator',
                'admin' => 'Admin Operasional',
                default => 'Pegawai Kantor',
            },
            'totalDocs' => EvidenceDocument::query()->whereNull('deleted_at')->count(),
            'totalLks' => LembarKerja::query()->where('is_active', true)->whereNull('deleted_at')->count(),
            'totalMatches' => DocumentMatch::query()->whereIn('status', ['auto', 'confirmed'])->count(),
            'totalIndicatorsWithDrive' => LkIndicator::query()->whereNotNull('evidence_link')->where('evidence_link', '!=', '')->count(),
        ];
    }
}
