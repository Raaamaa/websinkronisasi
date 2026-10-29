<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Matching\ManualMatchService;
use App\Services\Monitor\MonitorMatrixService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MonitorMatrixPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Matriks Monitor';

    protected static ?string $title = 'Matriks Sinkronisasi Bukti Dukung ke Lembar Kerja';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'monitor-matrix';

    protected static string $view = 'filament.pages.monitor-matrix';

    // Filter properties
    public int $period = 2026;

    public ?string $category = null;

    public ?string $document_type = null;

    public ?int $year = null;

    public ?string $search = null;

    public int $perPage = 25;

    public int $page = 1;

    // Drilldown state
    public bool $showDrilldown = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $drilldownData = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // MON-01, RBAC.md: Seluruh role aktif (Super Admin, Admin, Pegawai) dapat melihat monitor
        return $user !== null && $user->is_active && in_array($user->role, ['super_admin', 'admin', 'pegawai'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $service = app(MonitorMatrixService::class);

        return $service->getMatrixData([
            'period' => $this->period,
            'category' => $this->category,
            'document_type' => $this->document_type,
            'year' => $this->year,
            'search' => $this->search,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ]);
    }

    public function openDrilldown(string $documentId, string $lkId): void
    {
        $service = app(MonitorMatrixService::class);
        $this->drilldownData = $service->getDrilldownDetails($documentId, $lkId);
        $this->showDrilldown = true;
    }

    public function closeDrilldown(): void
    {
        $this->showDrilldown = false;
        $this->drilldownData = null;
    }

    public function removeMatch(string $documentId, string $indicatorId): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! in_array($user->role, ['super_admin', 'admin'], true)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya Administrator yang dapat mencabut relasi bukti dukung.')
                ->danger()
                ->send();

            return;
        }

        $service = app(ManualMatchService::class);
        $service->deleteMatch($documentId, $indicatorId, $user);

        Notification::make()
            ->title('Relasi Dihapus')
            ->body('Kecocokan berhasil dicabut dan berstatus ditolak.')
            ->success()
            ->send();

        // Refresh drilldown data jika masih terbuka
        if ($this->drilldownData !== null) {
            $lkId = (string) $this->drilldownData['lk']['id'];
            $this->openDrilldown($documentId, $lkId);
        }
    }
}
