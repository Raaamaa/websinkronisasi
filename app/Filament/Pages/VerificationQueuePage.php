<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Jobs\SyncGoogleDriveJob;
use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\DriveSyncFolder;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Drive\GoogleDriveSyncService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerificationQueuePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Antrean Verifikasi';

    protected static ?string $title = 'Antrean Verifikasi Bukti Dukung';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'verification-queue';

    protected static string $view = 'filament.pages.verification-queue';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = EvidenceDocument::whereNull('deleted_at')->where('approval_status', 'menunggu')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public string $statusFilter = 'menunggu'; // 'menunggu', 'revisi', 'ditolak', 'all'

    public ?string $search = null;

    public int $perPage = 10;

    public int $page = 1;

    public bool $showDriveFoldersPanel = false;

    public ?string $newDriveFolderInput = null;

    public ?string $newDriveFolderLabel = null;

    public ?string $newDriveFolderLkId = null;

    /** @var array<string, list<string>> */
    public array $selectedLks = [];

    /** @var array<string, string> */
    public array $notes = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->is_active && $user->deleted_at === null && $user->hasPermission('evidence.verify');
    }

    public function mount(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Anda tidak memiliki akses ke fitur Antrean Verifikasi.');
    }

    /**
     * @return array{menunggu: int, revisi: int, ditolak: int, disetujui: int}
     */
    public function getStats(): array
    {
        return [
            'menunggu' => EvidenceDocument::whereNull('deleted_at')->where('approval_status', 'menunggu')->count(),
            'revisi' => EvidenceDocument::whereNull('deleted_at')->where('approval_status', 'revisi')->count(),
            'ditolak' => EvidenceDocument::whereNull('deleted_at')->where('approval_status', 'ditolak')->count(),
            'disetujui' => EvidenceDocument::whereNull('deleted_at')->where('approval_status', 'disetujui')->count(),
        ];
    }

    public function setFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    /**
     * @return Collection<int, LembarKerja>
     */
    public function getAvailableLembarKerjas(): Collection
    {
        return LembarKerja::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();
    }

    public function getDocuments(): LengthAwarePaginator
    {
        $query = EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->with([
                'uploader',
                'lkLinks.lembarKerja',
                'matches.indicator.lembarKerja',
            ]);

        if ($this->statusFilter !== 'all') {
            $query->where('approval_status', $this->statusFilter);
        }

        if (! empty($this->search)) {
            $escaped = addcslashes(trim($this->search), '%_\\');
            $query->where(function ($q) use ($escaped): void {
                $q->where('document_name', 'ilike', "%{$escaped}%")
                    ->orWhere('document_number', 'ilike', "%{$escaped}%");
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($this->perPage, ['*'], 'page', $this->page);

        // Inisialisasi selectedLks awal dari usulan LK (proposed) jika belum ada di state
        foreach ($paginator->items() as $doc) {
            if (! isset($this->selectedLks[$doc->id])) {
                $this->selectedLks[$doc->id] = $doc->lkLinks->pluck('lk_id')->toArray();
            }
        }

        return $paginator;
    }

    public function approveDocument(string $docId, array $lkIds = []): void
    {
        abort_if(! static::canAccess(), 403);

        // Jika lkIds kosong dari parameter, ambil dari state form
        if (empty($lkIds)) {
            $lkIds = $this->selectedLks[$docId] ?? [];
        }

        if (empty($lkIds)) {
            $this->addError("selectedLks.{$docId}", 'Pilih minimal satu Lembar Kerja untuk menyetujui dokumen.');
            Notification::make()
                ->title('Persetujuan Gagal')
                ->body('Wajib memilih minimal satu Lembar Kerja sebelum menyetujui.')
                ->danger()
                ->send();

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        $doc = EvidenceDocument::findOrFail($docId);

        DB::transaction(function () use ($doc, $lkIds, $actor): void {
            // Hapus link yang tidak dipilih (termasuk usulan 'proposed')
            DocumentLkLink::where('document_id', $doc->id)
                ->whereNotIn('lk_id', $lkIds)
                ->delete();

            // Pilihan akhir diset menjadi link_source = 'admin'
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

            $doc->update([
                'approval_status' => 'disetujui',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_notes' => null,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.approved',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $doc->id,
                'new_values' => [
                    'approval_status' => 'disetujui',
                    'approved_by' => $actor->id,
                    'approved_at' => now()->toIso8601String(),
                    'lk_ids' => $lkIds,
                ],
            ]);
        });

        Notification::make()
            ->title('Dokumen Berhasil Disetujui')
            ->body("Dokumen '{$doc->document_name}' resmi disetujui untuk LK terkait.")
            ->success()
            ->send();
    }

    public function requestRevision(string $docId, string $notes = ''): void
    {
        abort_if(! static::canAccess(), 403);

        if (empty($notes)) {
            $notes = (string) ($this->notes[$docId] ?? '');
        }

        $trimmed = trim($notes);
        if ($trimmed === '') {
            $this->addError("notes.{$docId}", 'Catatan revisi wajib diisi agar pengunggah memahami kekurangan.');
            Notification::make()
                ->title('Catatan Wajib Diisi')
                ->body('Catatan revisi wajib diisi untuk meminta perbaikan.')
                ->danger()
                ->send();

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        $doc = EvidenceDocument::findOrFail($docId);

        DB::transaction(function () use ($doc, $trimmed, $actor): void {
            $doc->update([
                'approval_status' => 'revisi',
                'approval_notes' => $trimmed,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.revision_requested',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $doc->id,
                'new_values' => [
                    'approval_status' => 'revisi',
                    'approval_notes' => $trimmed,
                ],
            ]);
        });

        Notification::make()
            ->title('Permintaan Revisi Terkirim')
            ->body("Pengajuan '{$doc->document_name}' dikembalikan ke pengunggah.")
            ->warning()
            ->send();
    }

    public function rejectDocument(string $docId, string $notes = ''): void
    {
        abort_if(! static::canAccess(), 403);

        if (empty($notes)) {
            $notes = (string) ($this->notes[$docId] ?? '');
        }

        $trimmed = trim($notes);
        if ($trimmed === '') {
            $this->addError("notes.{$docId}", 'Catatan penolakan wajib diisi.');
            Notification::make()
                ->title('Catatan Wajib Diisi')
                ->body('Catatan penolakan wajib diisi untuk menolak pengajuan.')
                ->danger()
                ->send();

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        $doc = EvidenceDocument::findOrFail($docId);

        DB::transaction(function () use ($doc, $trimmed, $actor): void {
            $doc->update([
                'approval_status' => 'ditolak',
                'approval_notes' => $trimmed,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.rejected',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => $doc->id,
                'new_values' => [
                    'approval_status' => 'ditolak',
                    'approval_notes' => $trimmed,
                ],
            ]);
        });

        Notification::make()
            ->title('Pengajuan Ditolak')
            ->body("Pengajuan '{$doc->document_name}' ditolak.")
            ->danger()
            ->send();
    }

    public function highlightKeywords(string $text, ?array $keywords): string
    {
        if (empty($keywords)) {
            return e($text);
        }

        $validWords = array_values(array_filter($keywords, fn ($w) => mb_strlen(trim((string) $w)) >= 3));
        if (empty($validWords)) {
            return e($text);
        }

        // Bangun satu ekspresi reguler gabungan dengan batasan kata
        $escapedPatterns = array_map(fn ($w) => preg_quote(trim((string) $w), '/'), $validWords);
        $regex = '/(\b(?:'.implode('|', $escapedPatterns).')\b)/iu';

        // Pecah teks berdasarkan keyword; token genap adalah teks biasa, token ganjil adalah keyword
        $tokens = preg_split($regex, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return e($text);
        }

        $output = '';
        foreach ($tokens as $index => $token) {
            if ($index % 2 === 1) {
                // Token keyword yang cocok
                $output .= '<mark class="bg-amber-200 text-amber-900 px-1 py-0.5 rounded font-semibold">'.e($token).'</mark>';
            } else {
                // Teks biasa
                $output .= e($token);
            }
        }

        return $output;
    }

    public function toggleDriveFoldersPanel(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! $user->hasPermission('drive.sync')) {
            abort(403, 'Akses ditolak.');
        }

        $this->showDriveFoldersPanel = ! $this->showDriveFoldersPanel;
    }

    public function syncGoogleDrive(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! $user->hasPermission('drive.sync')) {
            abort(403, 'Akses ditolak.');
        }

        // Jalankan via queue job agar tidak timeout
        SyncGoogleDriveJob::dispatch($user->id);

        Notification::make()
            ->title('Sinkronisasi Google Drive Berjalan')
            ->body('Proses sinkronisasi telah masuk antrean.')
            ->success()
            ->send();
    }

    public function addDriveFolder(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! $user->hasPermission('drive.sync')) {
            abort(403, 'Akses ditolak.');
        }

        $extracted = GoogleDriveSyncService::extractFolderId($this->newDriveFolderInput) ?? trim((string) $this->newDriveFolderInput);

        if ($extracted === '') {
            $this->addError('newDriveFolderInput', 'Tautan atau ID folder Google Drive tidak valid.');

            return;
        }

        DriveSyncFolder::create([
            'folder_id' => $extracted,
            'label' => $this->newDriveFolderLabel ? trim($this->newDriveFolderLabel) : null,
            'lk_id' => $this->newDriveFolderLkId ?: null,
            'is_active' => true,
        ]);

        $this->reset(['newDriveFolderInput', 'newDriveFolderLabel', 'newDriveFolderLkId']);

        Notification::make()
            ->title('Folder Google Drive Berhasil Ditambahkan')
            ->success()
            ->send();
    }

    public function toggleDriveFolderActive(string $folderId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! $user->hasPermission('drive.sync')) {
            abort(403, 'Akses ditolak.');
        }

        $folder = DriveSyncFolder::findOrFail($folderId);
        $folder->update(['is_active' => ! $folder->is_active]);

        Notification::make()
            ->title($folder->is_active ? 'Folder Diaktifkan' : 'Folder Dinonaktifkan')
            ->success()
            ->send();
    }

    public function deleteDriveFolder(string $folderId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null || ! $user->hasPermission('drive.sync')) {
            abort(403, 'Akses ditolak.');
        }

        $folder = DriveSyncFolder::findOrFail($folderId);
        $folder->delete();

        Notification::make()
            ->title('Folder Google Drive Dihapus')
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, DriveSyncFolder>
     */
    public function getDriveSyncFolders(): Collection
    {
        return DriveSyncFolder::with('lembarKerja')->orderBy('created_at', 'desc')->get();
    }
}
