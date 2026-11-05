<?php

declare(strict_types=1);

namespace App\Filament\Resources\LembarKerjaResource\Pages;

use App\Filament\Resources\LembarKerjaResource;
use App\Jobs\RematchActiveDocumentsJob;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ListLembarKerjas extends ListRecords
{
    protected static string $resource = LembarKerjaResource::class;

    protected static string $view = 'filament.resources.lembar-kerja-resource.pages.list-lembar-kerjas';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public ?string $selectedLkId = null;

    public ?string $search = null;

    public ?string $areaFilter = null;

    public ?string $linkStatusFilter = null; // null, 'has_link', 'no_link'

    public int $perPage = 25;

    public int $page = 1;

    // Drawer State
    public ?string $editingIndicatorId = null;

    public string $indicatorCode = '';

    public string $indicatorArea = '';

    public string $indicatorTitle = '';

    /** @var array<string> */
    public array $indicatorItems = [];

    public string $newItemText = '';

    public ?string $indicatorDriveLink = null;

    public ?string $indicatorPic = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Lembar Kerja Baru')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => Auth::user()?->role === 'super_admin'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        if (empty($this->selectedLkId)) {
            $defaultLk = LembarKerja::where('code', 'ZI')->where('is_active', true)->first()
                ?? LembarKerja::where('is_active', true)->first();
            $this->selectedLkId = $defaultLk ? (string) $defaultLk->id : null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLksData(): array
    {
        $lks = LembarKerja::query()
            ->where('is_active', true)
            ->withCount(['indicators'])
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        $data = [];
        foreach ($lks as $lk) {
            $totalInd = $lk->indicators_count;

            $itemsCount = LkEvidenceItem::whereHas('indicator', fn ($q) => $q->where('lk_id', $lk->id))->count();

            // Total terpenuhi di matriks (auto atau confirmed)
            $fulfilledCount = LkIndicator::where('lk_id', $lk->id)
                ->whereHas('matches', fn ($mq) => $mq->whereIn('status', ['auto', 'confirmed']))
                ->count();

            $pct = $totalInd > 0 ? (int) round(($fulfilledCount / $totalInd) * 100) : 0;

            $data[] = [
                'id' => (string) $lk->id,
                'code' => $lk->code,
                'name' => $lk->name,
                'category' => $lk->category,
                'period' => $lk->period,
                'is_active' => $lk->is_active,
                'indicators_count' => $totalInd,
                'items_count' => $itemsCount,
                'fulfilled_count' => $fulfilledCount,
                'percentage' => $pct,
                'is_selected' => $this->selectedLkId === (string) $lk->id,
            ];
        }

        return $data;
    }

    public function getSelectedLkProperty(): ?LembarKerja
    {
        if (empty($this->selectedLkId)) {
            return null;
        }

        return LembarKerja::find($this->selectedLkId);
    }

    public function selectLk(string $lkId): void
    {
        $this->selectedLkId = $lkId;
        $this->page = 1;
        $this->search = null;
        $this->areaFilter = null;
        $this->linkStatusFilter = null;
    }

    public function getFilteredIndicators(): LengthAwarePaginator
    {
        if (empty($this->selectedLkId)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage, 1);
        }

        $query = LkIndicator::query()
            ->with(['evidenceItems', 'lembarKerja'])
            ->where('lk_id', $this->selectedLkId);

        if (! empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'ilike', $term)
                    ->orWhere('title', 'ilike', $term)
                    ->orWhereHas('evidenceItems', function ($iq) use ($term) {
                        $iq->where('raw_template_name', 'ilike', $term)
                            ->orWhere('resolved_name', 'ilike', $term);
                    });
            });
        }

        if (! empty($this->areaFilter)) {
            $query->where(function ($q) {
                $q->where('metadata->area', 'ilike', '%' . $this->areaFilter . '%')
                    ->orWhere('title', 'ilike', '%' . $this->areaFilter . '%');
            });
        }

        if ($this->linkStatusFilter === 'has_link') {
            $query->whereNotNull('evidence_link')->where('evidence_link', '!=', '');
        } elseif ($this->linkStatusFilter === 'no_link') {
            $query->where(function ($q) {
                $q->whereNull('evidence_link')->orWhere('evidence_link', '');
            });
        }

        return $query->orderBy('order_index')
            ->orderBy('code')
            ->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    /**
     * @return array<string, string>
     */
    public function getAreaOptions(): array
    {
        return [
            'Area I' => 'Area I — Manajemen Perubahan',
            'Area II' => 'Area II — Penataan Tatalaksana',
            'Area III' => 'Area III — Penataan Sistem Manajemen SDM',
            'Area IV' => 'Area IV — Penguatan Akuntabilitas',
            'Area V' => 'Area V — Penguatan Pengawasan',
            'Area VI' => 'Area VI — Peningkatan Kualitas Pelayanan Publik',
        ];
    }

    public function openIndicatorDrawer(string $indicatorId): void
    {
        $indicator = LkIndicator::with('evidenceItems')->find($indicatorId);
        if (! $indicator) {
            return;
        }

        $this->editingIndicatorId = $indicatorId;
        $this->indicatorCode = $indicator->code;
        $this->indicatorArea = $indicator->metadata['area'] ?? $this->inferAreaFromCode($indicator->code);
        $this->indicatorTitle = $indicator->title;
        $this->indicatorDriveLink = $indicator->evidence_link;
        $this->indicatorPic = $indicator->pic ?? 'Bagian Umum';

        $this->indicatorItems = $indicator->evidenceItems
            ->pluck('raw_template_name')
            ->filter()
            ->values()
            ->toArray();

        if (empty($this->indicatorItems)) {
            $this->indicatorItems = [$indicator->title . ' [t]'];
        }

        $this->newItemText = '';
    }

    public function closeIndicatorDrawer(): void
    {
        $this->editingIndicatorId = null;
        $this->newItemText = '';
    }

    public function addItemToken(): void
    {
        $text = trim($this->newItemText);
        if ($text !== '' && ! in_array($text, $this->indicatorItems, true)) {
            $this->indicatorItems[] = $text;
            $this->newItemText = '';
        }
    }

    public function removeItemToken(int $index): void
    {
        if (isset($this->indicatorItems[$index])) {
            unset($this->indicatorItems[$index]);
            $this->indicatorItems = array_values($this->indicatorItems);
        }
    }

    public function inferAreaFromCode(string $code): string
    {
        if (preg_match('/^A?1/i', $code)) {
            return 'Area I — Manajemen Perubahan > Pembentukan Tim Kerja';
        }
        if (preg_match('/^A?2/i', $code)) {
            return 'Area II — Penataan Tatalaksana';
        }
        if (preg_match('/^A?3/i', $code)) {
            return 'Area III — Penataan Sistem Manajemen SDM';
        }
        if (preg_match('/^A?4/i', $code)) {
            return 'Area IV — Penguatan Akuntabilitas';
        }
        if (preg_match('/^A?5/i', $code)) {
            return 'Area V — Penguatan Pengawasan';
        }
        if (preg_match('/^A?6/i', $code)) {
            return 'Area VI — Peningkatan Kualitas Pelayanan Publik';
        }

        return 'Komponen Penilaian Utama';
    }

    public function saveIndicator(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! in_array($user->role, ['super_admin', 'admin'], true) || ! $user->is_active) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya Administrator yang memiliki wewenang mengubah indikator.')
                ->danger()
                ->send();

            return;
        }

        $indicator = LkIndicator::find($this->editingIndicatorId);
        if (! $indicator) {
            return;
        }

        // Validate code uniqueness per LK
        $exists = LkIndicator::where('lk_id', $indicator->lk_id)
            ->where('code', $this->indicatorCode)
            ->where('id', '!=', $indicator->id)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Kode Indikator Duplikat')
                ->body("Kode indikator '{$this->indicatorCode}' sudah digunakan dalam instrumen ini.")
                ->danger()
                ->send();

            return;
        }

        // Validate drive link if provided (LK-09, AC-25)
        $link = trim((string) $this->indicatorDriveLink);
        if ($link !== '') {
            if (! preg_match('/^https?:\/\/.+/i', $link)) {
                Notification::make()
                    ->title('Format Tautan Tidak Valid')
                    ->body('Tautan Google Drive wajib diawali protokol resmi http:// atau https://.')
                    ->danger()
                    ->send();

                return;
            }
        } else {
            $link = null;
        }

        // Prepare metadata
        $metadata = $indicator->metadata ?? [];
        $metadata['area'] = $this->indicatorArea;

        $oldValues = [
            'code' => $indicator->code,
            'title' => $indicator->title,
            'evidence_link' => $indicator->evidence_link,
            'pic' => $indicator->pic,
        ];

        // Update indicator
        $indicator->update([
            'code' => trim($this->indicatorCode),
            'title' => trim($this->indicatorTitle),
            'evidence_link' => $link,
            'pic' => trim((string) $this->indicatorPic),
            'metadata' => $metadata,
        ]);

        // Resolve tokens with YearPlaceholderResolver (AC-26)
        /** @var YearPlaceholderResolver $yearResolver */
        $yearResolver = app(YearPlaceholderResolver::class);
        $period = $indicator->lembarKerja->period ?? 2026;

        // Keep track of resolved names to delete removed ones
        $activeResolvedNames = [];
        $idx = 1;

        foreach ($this->indicatorItems as $rawTemplate) {
            $rawTemplate = trim($rawTemplate);
            if ($rawTemplate === '') {
                continue;
            }

            $resolvedName = $yearResolver->resolve($rawTemplate, $period);
            $activeResolvedNames[] = $resolvedName;

            LkEvidenceItem::updateOrCreate(
                [
                    'indicator_id' => $indicator->id,
                    'resolved_name' => $resolvedName,
                ],
                [
                    'raw_template_name' => $rawTemplate,
                    'order_index' => $idx++,
                ]
            );
        }

        // Clean up items removed from the list
        if (! empty($activeResolvedNames)) {
            LkEvidenceItem::where('indicator_id', $indicator->id)
                ->whereNotIn('resolved_name', $activeResolvedNames)
                ->delete();
        }

        // Log audit (AUDIT-02)
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'indicator.updated',
            'entity_type' => 'LkIndicator',
            'entity_id' => $indicator->id,
            'old_values' => $oldValues,
            'new_values' => [
                'code' => $indicator->code,
                'title' => $indicator->title,
                'evidence_link' => $indicator->evidence_link,
                'pic' => $indicator->pic,
                'items_count' => count($activeResolvedNames),
            ],
        ]);

        // Trigger rematch queue (LK-07)
        RematchActiveDocumentsJob::dispatch($indicator->lk_id);

        $this->closeIndicatorDrawer();

        Notification::make()
            ->title('Indikator Berhasil Disimpan')
            ->body('Perubahan indikator telah disimpan dan antrean pencocokan ulang (rematch) otomatis dipicu.')
            ->success()
            ->send();
    }

    public function gotoIndicatorPage(int $page): void
    {
        $this->page = $page;
    }

    public function nextIndicatorPage(): void
    {
        $this->page++;
    }

    public function previousIndicatorPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedAreaFilter(): void
    {
        $this->page = 1;
    }

    public function updatedLinkStatusFilter(): void
    {
        $this->page = 1;
    }
}
