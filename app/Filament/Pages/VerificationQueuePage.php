<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\DocumentMatch;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\VerificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class VerificationQueuePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Antrean Verifikasi';

    protected static ?string $title = 'Antrean Verifikasi Hasil Pencocokan';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = DocumentMatch::where('status', 'auto')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static ?string $slug = 'verification-queue';

    protected static string $view = 'filament.pages.verification-queue';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    // Filter properties
    public ?int $period = 2026;

    public ?string $lkId = null;

    public ?string $search = null;

    public string $statusTab = 'auto'; // 'auto', 'confirmed', 'rejected', 'all'

    public ?string $scoreRange = null; // null, 'gte_90', 'gte_75'

    public ?string $picFilter = null;

    public int $perPage = 10;

    public int $page = 1;

    // Selection properties
    /** @var array<string> */
    public array $selectedMatches = [];

    public bool $selectAll = false;

    // Modal inspection properties
    public ?string $inspectMatchId = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // RBAC.md & MATCH-04: Hanya Super Admin dan Admin yang dapat mengakses antrean verifikasi
        return $user !== null && $user->is_active && $user->hasPermission('match.verify');
    }

    /**
     * @return array{pending: int, confirmed: int, rejected: int}
     */
    public function getStats(): array
    {
        $baseQuery = DocumentMatch::query()
            ->when($this->period !== null, function ($q) {
                $q->whereHas('indicator.lembarKerja', fn ($lq) => $lq->where('period', $this->period));
            });

        return [
            'pending' => (clone $baseQuery)->where('status', 'auto')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->statusTab = $tab;
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $matches = $this->getMatches();
            $this->selectedMatches = $matches->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedMatches = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedPeriod(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedLkId(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedScoreRange(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedPicFilter(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function gotoPage(int $page): void
    {
        $this->page = $page;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->selectedMatches = [];
        $this->selectAll = false;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->selectedMatches = [];
            $this->selectAll = false;
        }
    }

    public function getMatches(): LengthAwarePaginator
    {
        $query = DocumentMatch::query()
            ->with([
                'document.uploader',
                'indicator.lembarKerja',
                'matchedItem',
                'verifier',
            ]);

        if ($this->statusTab !== 'all') {
            $query->where('status', $this->statusTab);
        }

        if ($this->period !== null) {
            $query->whereHas('indicator.lembarKerja', function ($q) {
                $q->where('period', $this->period);
            });
        }

        if (! empty($this->lkId)) {
            $query->whereHas('indicator', function ($q) {
                $q->where('lk_id', $this->lkId);
            });
        }

        if (! empty($this->picFilter)) {
            $query->whereHas('indicator', function ($q) {
                $q->where('pic', $this->picFilter);
            });
        }

        if ($this->scoreRange === 'gte_90') {
            $query->where('confidence_score', '>=', 90);
        } elseif ($this->scoreRange === 'gte_75') {
            $query->where('confidence_score', '>=', 75);
        }

        if (! empty($this->search)) {
            $searchTerm = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('document', function ($docQ) use ($searchTerm) {
                    $docQ->where('document_name', 'ilike', $searchTerm)
                        ->orWhere('document_number', 'ilike', $searchTerm);
                })->orWhereHas('indicator', function ($indQ) use ($searchTerm) {
                    $indQ->where('code', 'ilike', $searchTerm)
                        ->orWhere('title', 'ilike', $searchTerm);
                });
            });
        }

        return $query->orderByDesc('confidence_score')
            ->orderBy('created_at')
            ->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    public function confirmMatch(string $matchId): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->hasPermission('match.verify')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki wewenang memverifikasi pencocokan.')
                ->danger()
                ->send();

            return;
        }

        try {
            $verificationService = app(VerificationService::class);
            $verificationService->confirmMatch($matchId, $user);

            Notification::make()
                ->title('Pencocokan Dikonfirmasi')
                ->body('Status pencocokan telah diubah menjadi Terverifikasi (confirmed).')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memverifikasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function rejectMatch(string $matchId): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->hasPermission('match.verify')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki wewenang menolak pencocokan.')
                ->danger()
                ->send();

            return;
        }

        try {
            $verificationService = app(VerificationService::class);
            $verificationService->rejectMatch($matchId, $user);

            Notification::make()
                ->title('Pencocokan Ditolak')
                ->body('Pencocokan telah ditolak (rejected) dan tidak akan dicocokkan otomatis lagi.')
                ->warning()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menolak')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function confirmSelected(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->hasPermission('match.verify')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki wewenang memverifikasi pencocokan.')
                ->danger()
                ->send();

            return;
        }

        if (empty($this->selectedMatches)) {
            return;
        }

        $verificationService = app(VerificationService::class);
        $count = 0;

        foreach ($this->selectedMatches as $matchId) {
            try {
                $verificationService->confirmMatch((string) $matchId, $user);
                $count++;
            } catch (\Throwable) {
                // Lanjutkan jika ada yang gagal
            }
        }

        $this->selectedMatches = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Verifikasi Berhasil')
            ->body("{$count} pencocokan berhasil dikonfirmasi permanen (confirmed).")
            ->success()
            ->send();
    }

    public function rejectSelected(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->hasPermission('match.verify')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki wewenang menolak pencocokan.')
                ->danger()
                ->send();

            return;
        }

        if (empty($this->selectedMatches)) {
            return;
        }

        $verificationService = app(VerificationService::class);
        $count = 0;

        foreach ($this->selectedMatches as $matchId) {
            try {
                $verificationService->rejectMatch((string) $matchId, $user);
                $count++;
            } catch (\Throwable) {
                // Lanjutkan jika ada yang gagal
            }
        }

        $this->selectedMatches = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Penolakan Berhasil (MATCH-06)')
            ->body("{$count} pencocokan ditolak (rejected) dan dikunci permanen.")
            ->warning()
            ->send();
    }

    public function inspectMatch(string $matchId): void
    {
        $this->inspectMatchId = $matchId;
    }

    public function closeInspectModal(): void
    {
        $this->inspectMatchId = null;
    }

    public function getInspectedMatchProperty(): ?DocumentMatch
    {
        if (empty($this->inspectMatchId)) {
            return null;
        }

        return DocumentMatch::with([
            'document.uploader',
            'indicator.lembarKerja',
            'matchedItem',
            'verifier',
        ])->find($this->inspectMatchId);
    }

    public function confirmInspectedMatch(): void
    {
        if ($this->inspectMatchId !== null) {
            $id = $this->inspectMatchId;
            $this->closeInspectModal();
            $this->confirmMatch($id);
        }
    }

    public function rejectInspectedMatch(): void
    {
        if ($this->inspectMatchId !== null) {
            $id = $this->inspectMatchId;
            $this->closeInspectModal();
            $this->rejectMatch($id);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getLkOptions(): array
    {
        return LembarKerja::query()
            ->when($this->period !== null, fn ($q) => $q->where('period', $this->period))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function getPicOptions(): array
    {
        return LkIndicator::query()
            ->whereNotNull('pic')
            ->where('pic', '!=', '')
            ->distinct()
            ->orderBy('pic')
            ->pluck('pic', 'pic')
            ->toArray();
    }

    /**
     * Highlight matched keywords in text preview for inspection drawer.
     *
     * @param  array<string>|null  $keywords
     */
    public function highlightKeywords(string $text, ?array $keywords): string
    {
        if (empty($keywords)) {
            return e($text);
        }

        $escaped = e($text);
        foreach ($keywords as $word) {
            $word = trim((string) $word);
            if (mb_strlen($word) < 3) {
                continue;
            }
            $pattern = '/(' . preg_quote($word, '/') . ')/i';
            $escaped = preg_replace(
                $pattern,
                '<mark class="bg-amber-200 text-amber-900 px-1 py-0.5 rounded font-semibold">$1</mark>',
                $escaped
            ) ?? $escaped;
        }

        return $escaped;
    }
}
