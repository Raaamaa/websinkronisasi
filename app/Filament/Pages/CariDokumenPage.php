<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Search\DocumentSearchService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CariDokumenPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Cari Dokumen';

    protected static ?string $title = 'Cari Dokumen Bukti Dukung';

    protected static ?string $slug = 'cari-dokumen';

    protected static string $view = 'filament.pages.cari-dokumen';

    protected static ?int $navigationSort = 2;

    public string $query = '';

    public ?int $limit = 50;

    public bool $hasSearched = false;

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // RBAC: Hanya Admin dan Pegawai yang dapat mengakses Cari Dokumen. Super Admin tetap 403.
        return $user !== null
            && $user->is_active
            && $user->deleted_at === null
            && $user->role !== 'super_admin'
            && $user->hasPermission('evidence.search');
    }

    public function boot(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki akses ke fitur Cari Dokumen.');
    }

    public function mount(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki akses ke fitur Cari Dokumen.');
    }

    public function search(): void
    {
        $this->hasSearched = true;
    }

    /**
     * @return Collection<int, EvidenceDocument>
     */
    public function getSearchResults(): Collection
    {
        if (trim($this->query) === '') {
            return collect();
        }

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);

        return $searchService->search($this->query, $this->limit);
    }
}
