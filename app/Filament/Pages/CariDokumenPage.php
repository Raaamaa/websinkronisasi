<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Matching\DryRunMatchingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    public int|string|null $year = null;

    public bool $hasEvaluated = false;

    public ?array $evaluationResult = null;

    public bool $isSaved = false;

    public ?string $savedDocumentId = null;

    public ?string $errorMessage = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null
            && $user->is_active
            && $user->deleted_at === null
            && $user->hasPermission('evidence.search');
    }

    public function boot(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki izin untuk fitur Cari Dokumen.');
    }

    public function mount(): void
    {
        abort_if(! static::canAccess(), 403, 'Akses Ditolak: Super Admin tidak memiliki izin untuk fitur Cari Dokumen.');

        $this->year = (int) date('Y');
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

        $this->validate([
            'year' => 'required|digits:4|integer|min:2000|max:2099',
        ], [
            'year.required' => 'Tahun anggaran dokumen wajib diisi.',
            'year.digits' => 'Tahun harus berupa 4 digit angka (misal: 2026).',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal adalah tahun 2000.',
            'year.max' => 'Tahun maksimal adalah tahun 2099.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        try {
            /** @var EvidenceUploadService $uploadService */
            $uploadService = app(EvidenceUploadService::class);
            $doc = $uploadService->upload($this->file, [
                'year' => (int) $this->year,
                'document_name' => pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME),
            ], $user);

            $this->isSaved = true;
            $this->savedDocumentId = $doc->id;

            Notification::make()
                ->title('Dokumen Berhasil Disimpan')
                ->body('Berkas telah disimpan sebagai bukti sah dan sedang diproses oleh antrean pencocokan.')
                ->success()
                ->send();
        } catch (DuplicateDocumentException $e) {
            Log::warning('Percobaan unggah berkas duplikat pada Cari Dokumen: '.$e->getMessage(), [
                'user_id' => $user->id,
                'filename' => $this->file?->getClientOriginalName(),
                'year' => $this->year,
            ]);

            $friendlyMessage = 'Berkas ini sudah pernah terdaftar di sistem sebagai bukti resmi. Sistem mencegah penyimpanan berkas ganda yang identik.';
            $this->errorMessage = $friendlyMessage;

            Notification::make()
                ->title('Dokumen Sudah Ada')
                ->body($friendlyMessage)
                ->warning()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan dokumen bukti pada Cari Dokumen: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
                'filename' => $this->file?->getClientOriginalName(),
                'year' => $this->year,
            ]);

            $friendlyMessage = 'Terjadi kendala saat menyimpan berkas ke sistem. Silakan periksa kembali berkas Anda atau hubungi administrator.';
            $this->errorMessage = $friendlyMessage;

            Notification::make()
                ->title('Gagal Menyimpan Dokumen')
                ->body($friendlyMessage)
                ->danger()
                ->send();
        }
    }

    public function resetForm(): void
    {
        $this->file = null;
        $this->year = (int) date('Y');
        $this->hasEvaluated = false;
        $this->evaluationResult = null;
        $this->isSaved = false;
        $this->savedDocumentId = null;
        $this->errorMessage = null;
        $this->resetErrorBag();
    }
}
