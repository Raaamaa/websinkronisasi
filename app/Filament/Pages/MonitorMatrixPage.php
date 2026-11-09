<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Evidence\TextExtractionService;
use App\Services\Matching\ManualMatchService;
use App\Services\Matching\MatchingEngineService;
use App\Services\Monitor\MonitorMatrixService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MonitorMatrixPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Matriks Monitor';

    protected static ?string $title = 'Matriks Pemetaan Bukti Dukung ke Lembar Kerja Penilaian';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'monitor-matrix';

    protected static string $view = 'filament.pages.monitor-matrix';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    // Filter properties
    public string $period = '12 Bulan';

    public string $viewMode = 'indicator'; // 'indicator' (Kebutuhan LK) or 'document' (Berkas Dokumen)

    public string $statusFilter = 'all'; // 'all', 'fulfilled', 'unfulfilled'

    public bool $showTestingPanel = true;

    public ?string $category = null;

    public ?string $document_type = null;

    public ?int $year = null;

    public ?string $search = null;

    public int $perPage = 15;

    public int $page = 1;

    // Drilldown state (Document-centric)
    public bool $showDrilldown = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $drilldownData = null;

    // Drilldown state (Indicator-centric)
    public bool $showIndicatorDrilldown = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $indicatorDrilldownData = null;

    public function mount(): void
    {
        $available = $this->getAvailablePeriods();
        if (! in_array($this->period, $available, true) && count($available) > 0) {
            $this->period = $available[0];
        }
    }

    /**
     * @return list<string>
     */
    public function getAvailablePeriods(): array
    {
        $dbIntervals = LembarKerja::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('evaluation_interval')
            ->toArray();

        $defaultIntervals = ['12 Bulan', '6 Bulan', '3 Bulan', '1 Bulan'];
        $merged = array_unique(array_merge($defaultIntervals, $dbIntervals));

        return array_values($merged);
    }

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
    public function exportMatrix(): StreamedResponse
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
        $filename = 'matriks-monitor-elka-'.$this->period.'-'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($allLks, $data): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // BOM untuk UTF-8 Excel
            fwrite($output, "\xEF\xBB\xBF");

            $headers = ['Kode', 'Uraian Dokumen / Bukti Dukung', 'Nomor Dokumen', 'Tahun', 'Jenis Dokumen', 'PIC Satker'];
            foreach ($allLks as $lk) {
                $headers[] = $lk->code.' ('.$lk->name.')';
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

                $sanitizedLine = array_map([$this, 'sanitizeCsvCell'], $line);
                fputcsv($output, $sanitizedLine);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Alias untuk exportMatrix sesuai spesifikasi rencana implementasi.
     */
    public function exportCsv(): StreamedResponse
    {
        return $this->exportMatrix();
    }

    /**
     * Sanitasi karakter pemicu spreadsheet formula injection (N-02).
     */
    private function sanitizeCsvCell(mixed $value): string
    {
        $val = (string) ($value ?? '');
        $formulaTriggers = ['=', '+', '-', '@', "\t", "\r"];
        $trimmed = ltrim($val);

        if ($trimmed !== '' && in_array($trimmed[0], $formulaTriggers, true)) {
            return "'".$val;
        }

        return $val;
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // MON-01, RBAC.md: Seluruh role aktif dengan permission monitor.view dapat melihat monitor
        return $user !== null && $user->is_active && $user->hasPermission('monitor.view');
    }

    public function toggleTestingPanel(): void
    {
        $this->showTestingPanel = ! $this->showTestingPanel;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['indicator', 'document'], true) ? $mode : 'indicator';
        $this->page = 1;
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = in_array($status, ['all', 'fulfilled', 'unfulfilled'], true) ? $status : 'all';
        $this->page = 1;
    }

    /**
     * Uji Cepat Berkas Sampel 1-Detik Langsung di Monitor
     */
    public function runSampleTest(string $sampleKey): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! in_array($user->role, ['super_admin', 'admin', 'pegawai'], true)) {
            Notification::make()->title('Akses Ditolak')->danger()->send();

            return;
        }

        $samples = [
            'zi' => [
                'file' => 'SK_Tim_Pembangunan_Zona_Integritas_2026.pdf',
                'name' => 'SK Tim Kerja Pembangunan ZI 2026 [ZI-1.1]',
                'number' => 'B-105/3328/KP.010/2026',
                'type' => 'SK',
                'label' => 'SK Tim Kerja ZI (ZI-1.1)',
            ],
            'pekppp' => [
                'file' => 'Standar_Pelayanan_Publik_BPS_2026.pdf',
                'name' => 'Standar Pelayanan Publik PST BPS 2026 [PEKPPP-01]',
                'number' => 'B-201/3328/VS.020/2026',
                'type' => 'SOP',
                'label' => 'Standar Pelayanan PST (PEKPPP-01)',
            ],
            'sakip' => [
                'file' => 'Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf',
                'name' => 'Laporan Kinerja LKjIP SAKIP 2026 [SAKIP-01]',
                'number' => 'B-305/3328/OT.010/2026',
                'type' => 'Laporan',
                'label' => 'Laporan LKjIP (SAKIP-01)',
            ],
            'kip' => [
                'file' => 'Daftar_Informasi_Publik_DIP_PPID_2026.docx',
                'name' => 'Daftar Informasi Publik DIP PPID 2026 [KIP-01]',
                'number' => 'B-402/3328/HM.100/2026',
                'type' => 'Data',
                'label' => 'DIP PPID (KIP-01)',
            ],
        ];

        if (! isset($samples[$sampleKey])) {
            return;
        }

        $sample = $samples[$sampleKey];
        $filePath = public_path('sample-documents/'.$sample['file']);

        if (! file_exists($filePath)) {
            Notification::make()
                ->title('Berkas Contoh Belum Tersedia')
                ->body("Berkas {$sample['file']} belum digenerate di server.")
                ->danger()
                ->send();

            return;
        }

        $uploadService = app(EvidenceUploadService::class);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $uploadedFile = new UploadedFile($filePath, $sample['file'], $mime, null, true);

        $uploader = $user->hasPermission('evidence.upload')
            ? $user
            : (User::query()->where('role', 'admin')->where('is_active', true)->first() ?? $user);

        try {
            $doc = $uploadService->upload($uploadedFile, [
                'document_name' => $sample['name'],
                'document_number' => $sample['number'],
                'year' => 2026,
                'document_type' => $sample['type'],
            ], $uploader);

            // Ekstraksi dan matching sinkron
            $extractor = app(TextExtractionService::class);
            (new ProcessEvidenceDocumentJob($doc->id))->handle($extractor);

            Notification::make()
                ->title('Uji Cepat Berhasil Terpetakan')
                ->body("Berkas '{$sample['label']}' berhasil diunggah dan langsung menghasilkan tanda centang (✔) pada matriks.")
                ->success()
                ->send();
        } catch (DuplicateDocumentException $e) {
            // Rematch ulang dokumen jika sudah pernah diunggah
            $matchingEngine = app(MatchingEngineService::class);
            $matchingEngine->matchDocument($e->existingDocument);

            Notification::make()
                ->title('Dokumen Siap & Terpetakan')
                ->body("Berkas '{$sample['label']}' sudah ada di database dan telah disegarkan pemetaannya (✔).")
                ->info()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Kendala Saat Uji Cepat')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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
            'view_mode' => $this->viewMode,
            'status_filter' => $this->statusFilter,
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

    public function openIndicatorDrilldown(string $indicatorId): void
    {
        $service = app(MonitorMatrixService::class);
        $this->indicatorDrilldownData = $service->getIndicatorDrilldownDetails($indicatorId);
        $this->showIndicatorDrilldown = true;
    }

    public function closeIndicatorDrilldown(): void
    {
        $this->showIndicatorDrilldown = false;
        $this->indicatorDrilldownData = null;
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
