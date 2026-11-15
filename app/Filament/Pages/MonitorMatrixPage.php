<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Monitor\MonitorMatrixService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public string $period = '12 Bulan';

    public ?string $category = null;

    public ?string $document_type = null;

    public ?int $year = null;

    public ?string $search = null;

    public int $perPage = 15;

    public int $page = 1;

    // State untuk modal edit markup LK oleh Admin
    public ?string $editingDocId = null;

    public ?string $editingDocName = null;

    /** @var list<string> */
    public array $editingLkIds = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->is_active && $user->hasPermission('monitor.view');
    }

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
     * @return Collection<int, LembarKerja>
     */
    public function getAllActiveLks(): Collection
    {
        return LembarKerja::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();
    }

    /**
     * @return array{
     *     total: float|int,
     *     used: float|int,
     *     free: float|int,
     *     percent: float,
     *     is_warning: bool
     * }
     */
    public function getStorageUsage(): array
    {
        $storagePath = storage_path('app');
        $total = @disk_total_space($storagePath) ?: 1;
        $free = @disk_free_space($storagePath) ?: 0;
        $used = max(0, $total - $free);
        $percent = round(($used / $total) * 100, 1);
        $warningThreshold = (int) config('evidence.storage_warning_percent', 80);

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $percent,
            'is_warning' => $percent >= $warningThreshold,
        ];
    }

    public function openEditMarkup(string $docId, array $currentLkIds = []): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if($user === null || ! $user->hasPermission('evidence.manage_markup'), 403);

        $doc = EvidenceDocument::findOrFail($docId);
        $this->editingDocId = $doc->id;
        $this->editingDocName = $doc->document_name;
        $this->editingLkIds = $currentLkIds;
    }

    public function closeEditMarkup(): void
    {
        $this->editingDocId = null;
        $this->editingDocName = null;
        $this->editingLkIds = [];
    }

    public function saveRevisedMarkup(): void
    {
        if ($this->editingDocId === null) {
            return;
        }

        $this->reviseMarkup($this->editingDocId, $this->editingLkIds);
        $this->closeEditMarkup();
    }

    public function reviseMarkup(string $documentId, array $lkIds): void
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        abort_if($actor === null || ! $actor->hasPermission('evidence.manage_markup'), 403);

        if (empty($lkIds)) {
            $this->addError('editingLkIds', 'Pilih minimal satu Lembar Kerja untuk bukti dukung.');
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Wajib memilih minimal satu Lembar Kerja.')
                ->danger()
                ->send();

            return;
        }

        $doc = EvidenceDocument::findOrFail($documentId);

        DB::transaction(function () use ($doc, $lkIds, $actor): void {
            // Hapus link yang tidak dipilih
            DocumentLkLink::where('document_id', $doc->id)
                ->whereNotIn('lk_id', $lkIds)
                ->delete();

            // Pilihan akhir disimpan sebagai 'admin'
            foreach ($lkIds as $lkId) {
                $existing = DocumentLkLink::where('document_id', $doc->id)->where('lk_id', $lkId)->first();
                if ($existing) {
                    $existing->update([
                        'link_source' => 'admin',
                        'verified_at' => now(),
                    ]);
                } else {
                    DocumentLkLink::create([
                        'document_id' => $doc->id,
                        'lk_id' => $lkId,
                        'link_source' => 'admin',
                        'verified_at' => now(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.markup_revised',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $doc->id,
                'new_values' => [
                    'lk_ids' => $lkIds,
                    'updated_by' => $actor->id,
                ],
            ]);
        });

        Notification::make()
            ->title('Markup LK Diperbarui')
            ->body("Pemetaan Lembar Kerja untuk '{$doc->document_name}' berhasil diperbarui.")
            ->success()
            ->send();
    }

    /**
     * Sanitasi karakter pemicu formula injection (N-02).
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

            fwrite($output, "\xEF\xBB\xBF");

            $headers = ['Kode', 'Nama Dokumen', 'Nomor Dokumen', 'Tahun', 'Jenis Dokumen'];
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

    public function exportCsv(): StreamedResponse
    {
        return $this->exportMatrix();
    }
}
