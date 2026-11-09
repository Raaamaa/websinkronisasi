<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Matching\DryRunMatchingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class CariDokumenPage extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Cari Dokumen';

    protected static ?string $title = 'Cari Dokumen & Cek Kecocokan';

    protected static ?string $slug = 'cari-dokumen';

    protected static string $view = 'filament.pages.cari-dokumen';

    protected static ?int $navigationSort = 2;

    public $file = null;

    public bool $hasEvaluated = false;

    public ?array $evaluationResult = null;

    public bool $isSaved = false;

    public ?string $savedDocumentId = null;

    public ?string $errorMessage = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // RBAC.md:83 & PRD:90: Super Admin TIDAK boleh memakai Cari Dokumen (tanpa izin evidence.upload).
        // Hanya Admin dan Pegawai yang memiliki hak akses.
        return $user !== null
            && $user->is_active
            && ! $user->isSuperAdmin()
            && in_array($user->role, ['admin', 'pegawai'], true)
            && $user->hasPermission('evidence.upload');
    }

    public function boot(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki izin untuk fitur Cari Dokumen.');
    }

    public function mount(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki izin untuk fitur Cari Dokumen.');
    }

    protected function authorizeAccess(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki izin untuk fitur Cari Dokumen.');
    }

    public function evaluateDocument(): void
    {
        $this->authorizeAccess();
        $this->resetErrorBag();
        $this->errorMessage = null;

        $this->validate([
            'file' => 'required|file|max:20480',
        ], [
            'file.required' => 'Silakan pilih berkas dokumen terlebih dahulu.',
            'file.file' => 'Berkas yang dipilih tidak valid.',
            'file.max' => 'Ukuran berkas tidak boleh melebihi 20 MB.',
        ]);

        /** @var DryRunMatchingService $service */
        $service = app(DryRunMatchingService::class);
        $result = $service->evaluateUpload($this->file);

        $this->evaluationResult = [
            'matched' => $result['matched'],
            'results' => $result['results']->toArray(),
            'existing_doc' => $result['existing_doc'] ? [
                'id' => $result['existing_doc']->id,
                'document_name' => $result['existing_doc']->document_name,
                'year' => $result['existing_doc']->year,
            ] : null,
        ];
        $this->hasEvaluated = true;
    }

    public function saveAsOfficialEvidence(): void
    {
        $this->authorizeAccess();

        if (! $this->file) {
            $this->addError('file', 'Tidak ada berkas yang dipilih.');

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            /** @var EvidenceUploadService $uploadService */
            $uploadService = app(EvidenceUploadService::class);
            $doc = $uploadService->upload($this->file, [
                'year' => (int) date('Y'),
                'document_name' => pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME),
            ], $user);

            $this->isSaved = true;
            $this->savedDocumentId = $doc->id;

            Notification::make()
                ->title('Dokumen Berhasil Disimpan')
                ->body('Berkas telah disimpan sebagai bukti sah dan sedang diproses oleh antrean pencocokan.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menyimpan Dokumen')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function resetForm(): void
    {
        $this->file = null;
        $this->hasEvaluated = false;
        $this->evaluationResult = null;
        $this->isSaved = false;
        $this->savedDocumentId = null;
        $this->errorMessage = null;
        $this->resetErrorBag();
    }
}
