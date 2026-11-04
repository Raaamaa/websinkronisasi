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

    protected static ?string $title = 'Matriks Pemetaan Bukti Dukung ke Lembar Kerja Penilaian';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'monitor-matrix';

    protected static string $view = 'filament.pages.monitor-matrix';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    // Filter properties
    public int $period = 2026;

    public ?string $category = null;

    public ?string $document_type = null;

    public ?int $year = null;

    public ?string $search = null;

    public int $perPage = 15;

    public int $page = 1;

    // Drilldown state
    public bool $showDrilldown = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $drilldownData = null;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedDocumentType(): void
    {
        $this->page = 1;
    }

    public function updatedPeriod(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function gotoPage(int $page): void
    {
        if ($page >= 1) {
            $this->page = $page;
        }
    }

    /**
     * @return array{
     *     total_indicators: int,
     *     fulfilled_indicators: int,
     *     fulfilled_percentage: float,
     *     unfulfilled_indicators: int,
     *     unfulfilled_percentage: float,
     *     pending_verification: int
     * }
     */
    public function getStats(): array
    {
        $service = app(MonitorMatrixService::class);

        return $service->getMatrixStats($this->period);
    }

    /**
     * Export current matrix view to CSV file for Excel
     */
    public function exportMatrix(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $service = app(MonitorMatrixService::class);
        $data = $service->getMatrixData([
            'period' => $this->period,
            'category' => $this->category,
            'document_type' => $this->document_type,
            'year' => $this->year,
            'search' => $this->search,
            'per_page' => 1000,
            'page' => 1,
        ]);

        $allLks = $data['columns']['internal']->merge($data['columns']['eksternal']);
        $filename = 'matriks-monitor-elka-' . $this->period . '-' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($allLks, $data): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // BOM untuk UTF-8 Excel
            fputs($output, "\xEF\xBB\xBF");

            $headers = ['Kode', 'Uraian Dokumen / Bukti Dukung', 'Nomor Dokumen', 'Tahun', 'Jenis Dokumen', 'PIC Satker'];
            foreach ($allLks as $lk) {
                $headers[] = $lk->code . ' (' . $lk->name . ')';
            }
            fputcsv($output, $headers);

            foreach ($data['rows'] as $row) {
                $line = [
                    $row['code'] ?? '',
                    $row['document_name'],
                    $row['document_number'] ?? '',
                    $row['year'],
                    $row['document_type'] ?? '',
                    $row['pic'] ?? '',
                ];

                foreach ($allLks as $lk) {
                    $cell = $row['cells'][$lk->code] ?? ['has_match' => false];
                    $line[] = $cell['has_match'] ? 'Terpenuhi (✔)' : '';
                }

                fputcsv($output, $line);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

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
