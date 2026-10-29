<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\DocumentMatch;
use App\Models\LembarKerja;
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

    protected static ?string $title = 'Antrean Verifikasi Pencocokan Otomatis';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'verification-queue';

    protected static string $view = 'filament.pages.verification-queue';

    // Filter properties
    public ?int $period = 2026;

    public ?string $lkId = null;

    public ?string $search = null;

    public int $perPage = 15;

    public int $page = 1;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // RBAC.md & MATCH-04: Hanya Super Admin dan Admin yang dapat mengakses antrean verifikasi
        return $user !== null && $user->is_active && $user->hasPermission('match.verify');
    }

    public function getMatches(): LengthAwarePaginator
    {
        $query = DocumentMatch::query()
            ->with([
                'document',
                'indicator.lembarKerja',
                'matchedItem',
            ])
            ->where('status', 'auto');

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
}
