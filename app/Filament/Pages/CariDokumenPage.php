<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Evidence\DocumentSimilarityService;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Evidence\TextExtractionService;
use App\Services\Monitor\MonitorMatrixService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class CariDokumenPage extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Cari Dokumen';

    protected static ?string $title = 'Cari Dokumen Bukti Dukung';

    protected static ?string $slug = 'cari-dokumen';

    protected static string $view = 'filament.pages.cari-dokumen';

    protected static ?int $navigationSort = 1;

    public string $query = '';

    public ?string $category = null;

    public ?string $document_type = null;

    public ?int $year = null;

    public int $perPage = 15;

    public int $page = 1;

    public ?int $limit = 50;

    public bool $hasSearched = false;

    public ?string $expandedDocId = null;

    public mixed $checkFile = null;

    public ?string $checkStatus = null;

    public ?string $checkMessage = null;

    /** @var array{id: string, judul: string, uploader_name: string, approval_status: string, lks: list<string>}|null */
    public ?array $checkIdentical = null;

    /** @var list<array{id: string, document_name: string, similarity_score: float, approval_status: string, lks: list<string>}> */
    public array $checkSimilar = [];

    // State untuk modal ubah markup LK oleh Admin
    public ?string $editingDocId = null;

    public ?string $editingDocName = null;

    /** @var list<string> */
    public array $editingLkIds = [];

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

    public function updatedQuery(): void
    {
        $this->page = 1;
        $this->expandedDocId = null;
    }

    public function updatedDocumentType(): void
    {
        $this->page = 1;
        $this->expandedDocId = null;
    }

    public function updatedYear(): void
    {
        $this->page = 1;
        $this->expandedDocId = null;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->expandedDocId = null;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
        $this->page = 1;
        $this->expandedDocId = null;
    }

    public function search(): void
    {
        $this->hasSearched = true;
        $this->page = 1;
        $this->expandedDocId = null;
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

    public function toggleDownloadSection(string $docId): void
    {
        $this->expandedDocId = $this->expandedDocId === $docId ? null : $docId;
    }

    /**
     * @return array{
     *     columns: array{internal: Collection<int, LembarKerja>, eksternal: Collection<int, LembarKerja>},
     *     rows: array<int, array<string, mixed>>,
     *     paginator: \Illuminate\Pagination\LengthAwarePaginator
     * }
     */
    public function getMatrixData(): array
    {
        return app(MonitorMatrixService::class)->getMatrixData([
            'category' => $this->category,
            'document_type' => $this->document_type,
            'year' => $this->year,
            'search' => trim($this->query) !== '' ? $this->query : null,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ]);
    }

    /**
     * Ringkasan status pengajuan milik pengguna yang belum disetujui (tanpa centang di matriks resmi).
     *
     * @return array{menunggu: int, revisi: int, ditolak: int, total: int}
     */
    public function getOwnSubmissionSummary(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return ['menunggu' => 0, 'revisi' => 0, 'ditolak' => 0, 'total' => 0];
        }

        $counts = EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->where('uploaded_by', $user->id)
            ->whereIn('approval_status', ['menunggu', 'revisi', 'ditolak'])
            ->selectRaw('approval_status, count(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        $menunggu = (int) ($counts['menunggu'] ?? 0);
        $revisi = (int) ($counts['revisi'] ?? 0);
        $ditolak = (int) ($counts['ditolak'] ?? 0);

        return [
            'menunggu' => $menunggu,
            'revisi' => $revisi,
            'ditolak' => $ditolak,
            'total' => $menunggu + $revisi + $ditolak,
        ];
    }

    public function checkUploadedFile(): void
    {
        abort_if(! static::canAccess(), 403);

        $this->resetErrorBag('checkFile');
        $this->checkStatus = null;
        $this->checkMessage = null;
        $this->checkIdentical = null;
        $this->checkSimilar = [];

        if ($this->checkFile === null) {
            $this->addError('checkFile', 'Pilih berkas terlebih dahulu untuk diperiksa.');

            return;
        }

        $realPath = null;

        try {
            $realPath = $this->checkFile->getRealPath();
            $originalFilename = $this->checkFile->getClientOriginalName();

            /** @var EvidenceUploadService $uploadService */
            $uploadService = app(EvidenceUploadService::class);
            /** @var DocumentSimilarityService $similarityService */
            $similarityService = app(DocumentSimilarityService::class);
            /** @var TextExtractionService $extractionService */
            $extractionService = app(TextExtractionService::class);

            $inspection = $uploadService->inspectAndValidate($this->checkFile);
            $fileHash = (string) ($inspection['hash'] ?? (is_string($realPath) && $realPath !== '' ? hash_file('sha256', $realPath) : ''));

            $identical = $similarityService->findIdenticalByHash($fileHash);
            if ($identical !== null) {
                $this->setIdenticalCheckResult($identical);

                return;
            }

            $extractedText = is_string($realPath) && $realPath !== ''
                ? $extractionService->extract($realPath, $inspection['mime'], $originalFilename)
                : null;

            $searchTitle = trim(str_replace(['_', '-'], ' ', (string) pathinfo($originalFilename, PATHINFO_FILENAME)));
            $candidates = $similarityService->findSimilarCandidates(
                $searchTitle,
                null,
                $extractedText,
                null,
                5
            );

            if ($candidates->isNotEmpty()) {
                $this->checkStatus = 'similar';
                $this->checkMessage = 'Ditemukan dokumen mirip di perpustakaan (peringatan non-blocking). Anda tetap dapat mengunggah jika ini dokumen berbeda.';
                $this->checkSimilar = $candidates->take(5)->map(function (EvidenceDocument $doc): array {
                    $lks = $doc->officialLkLinks
                        ->map(fn (DocumentLkLink $link): ?string => $link->lembarKerja?->code)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $rawScore = (float) ($doc->getAttribute('similarity_score') ?? $doc->getAttribute('score') ?? 0.0);

                    return [
                        'id' => (string) $doc->id,
                        'document_name' => (string) $doc->document_name,
                        'similarity_score' => round(min(100.0, $rawScore * 100), 1),
                        'approval_status' => (string) $doc->approval_status,
                        'lks' => $lks,
                    ];
                })->values()->all();

                return;
            }

            $this->checkStatus = 'clear';
            $this->checkMessage = 'Dokumen belum ada di perpustakaan. Silakan unggah.';
        } catch (DuplicateDocumentException $e) {
            $identical = $e->existingData;
            if ($identical === null && is_string($realPath) && $realPath !== '' && file_exists($realPath)) {
                $identical = app(DocumentSimilarityService::class)->findIdenticalByHash((string) hash_file('sha256', $realPath));
            }

            if ($identical !== null) {
                $this->setIdenticalCheckResult($identical);
            } else {
                $this->checkStatus = 'identical';
                $this->checkMessage = 'Ditolak — Dokumen Sudah Ada.';
            }
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Berkas tidak valid atau melebihi batas maksimal 20 MB.';
            $this->checkStatus = 'error';
            $this->checkMessage = (string) $firstError;
            $this->addError('checkFile', (string) $firstError);
        } finally {
            if (is_string($realPath) && $realPath !== '' && file_exists($realPath)) {
                @unlink($realPath);
            }
            $this->reset('checkFile');
        }
    }

    /**
     * @param  array<string, mixed>  $identical
     */
    private function setIdenticalCheckResult(array $identical): void
    {
        $lkCodes = DocumentLkLink::query()
            ->where('document_id', $identical['id'])
            ->whereIn('link_source', DocumentLkLink::OFFICIAL_SOURCES)
            ->with('lembarKerja')
            ->get()
            ->pluck('lembarKerja.code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->checkStatus = 'identical';
        $this->checkMessage = 'Ditolak — Dokumen Sudah Ada';
        $this->checkIdentical = [
            'id' => (string) $identical['id'],
            'judul' => (string) ($identical['judul'] ?? $identical['document_name'] ?? '-'),
            'uploader_name' => (string) ($identical['uploader_name'] ?? $identical['uploader']['name'] ?? 'Sistem'),
            'approval_status' => (string) ($identical['approval_status'] ?? '-'),
            'lks' => $lkCodes,
        ];
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

    public function openEditMarkup(string $docId, array $currentLkIds = []): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if($user === null || ! $user->hasPermission('evidence.manage_markup'), 403);

        $doc = EvidenceDocument::findOrFail($docId);
        $this->editingDocId = $doc->id;
        $this->editingDocName = $doc->document_name;
        $this->editingLkIds = array_values(array_unique(array_filter($currentLkIds)));
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

        $lkIds = array_values(array_unique(array_filter($this->editingLkIds)));
        $this->reviseMarkup($this->editingDocId, $lkIds);

        if ($this->getErrorBag()->has('editingLkIds')) {
            return;
        }

        $this->closeEditMarkup();
    }

    public function reviseMarkup(string $documentId, array $lkIds): void
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        abort_if($actor === null || ! $actor->hasPermission('evidence.manage_markup'), 403);

        $lkIds = array_values(array_unique(array_filter($lkIds)));

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
            DocumentLkLink::where('document_id', $doc->id)
                ->whereNotIn('lk_id', $lkIds)
                ->delete();

            foreach ($lkIds as $lkId) {
                $existing = DocumentLkLink::where('document_id', $doc->id)->where('lk_id', $lkId)->first();
                if ($existing) {
                    $existing->update([
                        'link_source' => 'admin',
                    ]);
                } else {
                    DocumentLkLink::create([
                        'document_id' => $doc->id,
                        'lk_id' => $lkId,
                        'indicator_id' => null,
                        'link_source' => 'admin',
                        'created_by' => $actor->id,
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
}
