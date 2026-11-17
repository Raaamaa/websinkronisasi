<?php

declare(strict_types=1);

namespace App\Filament\Resources\EvidenceDocumentResource\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Filament\Resources\EvidenceDocumentResource;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateEvidenceDocument extends CreateRecord
{
    protected static string $resource = EvidenceDocumentResource::class;

    protected static string $view = 'filament.resources.evidence-document-resource.pages.create-evidence-document';

    public function getTitle(): string
    {
        return 'Unggah Bukti';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    /**
     * @return Collection<int, EvidenceDocument>
     */
    public function getRecentUploads(): Collection
    {
        /** @var User|null $actor */
        $actor = Auth::user();

        $query = EvidenceDocument::query()
            ->whereNull('deleted_at');

        if ($actor !== null && $actor->role === 'pegawai') {
            $query->where('uploaded_by', $actor->id);
        }

        return $query
            ->withCount([
                'officialLkLinks as official_lks_count' => function ($q): void {
                    $q->select(DB::raw('count(distinct lk_id)'));
                },
            ])
            ->orderByDesc('created_at')
            ->take(3)
            ->get();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = Auth::user();
        $sourceType = $data['source_type'] ?? 'upload';
        $lkIds = array_values(array_unique(array_filter((array) ($data['lk_ids'] ?? []))));

        if (empty($lkIds)) {
            Notification::make()
                ->title('Usulan LK Wajib Dipilih')
                ->body('Wajib memilih minimal satu Lembar Kerja (LK) tujuan.')
                ->danger()
                ->send();

            $this->halt();
        }

        $uploadService = app(EvidenceUploadService::class);

        if ($sourceType === 'link') {
            $metadata = [
                'document_name' => $data['document_name'],
                'document_number' => $data['document_number'] ?? null,
                'year' => (int) $data['year'],
                'document_type' => $data['document_type'] ?? 'Lainnya',
                'external_link' => $data['external_link'],
                'description' => $data['description'] ?? null,
            ];

            try {
                return $uploadService->createLinkSubmission($metadata, $actor, $lkIds);
            } catch (Throwable $e) {
                Notification::make()
                    ->title('Gagal Menyimpan Pengajuan Tautan')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $attachmentRelativePath = $data['attachment'] ?? null;

        if ($attachmentRelativePath === null || ! Storage::disk('private')->exists($attachmentRelativePath)) {
            Notification::make()
                ->title('Gagal Mengunggah')
                ->body('Berkas fisik lampiran tidak ditemukan di penyimpanan sementara.')
                ->danger()
                ->send();

            $this->halt();
        }

        $fullPath = Storage::disk('private')->path($attachmentRelativePath);
        $originalFilename = basename($attachmentRelativePath);

        // Bungkus sebagai UploadedFile agar dapat divalidasi oleh EvidenceUploadService
        $uploadedFile = new UploadedFile(
            $fullPath,
            $originalFilename,
            null,
            null,
            true // mode test / bypass is_uploaded_file check untuk file lokal yang dipindahkan Filament
        );

        $metadata = [
            'document_name' => $data['document_name'],
            'document_number' => $data['document_number'] ?? null,
            'year' => (int) $data['year'],
            'document_type' => $data['document_type'] ?? 'Lainnya',
            'external_link' => $data['external_link'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        try {
            $document = $uploadService->upload($uploadedFile, $metadata, $actor, $lkIds);

            Storage::disk('private')->delete($attachmentRelativePath);

            return $document;
        } catch (DuplicateDocumentException $e) {
            Storage::disk('private')->delete($attachmentRelativePath);

            $existingDocName = $e->existingDocument?->document_name ?? 'Dokumen Terdaftar';
            Notification::make()
                ->title('Unggah Ditolak (Duplikasi Dokumen)')
                ->body("Dokumen identik sudah ada di sistem dengan nama: '{$existingDocName}'.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        } catch (ValidationException $e) {
            Storage::disk('private')->delete($attachmentRelativePath);

            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Validasi berkas gagal.';
            Notification::make()
                ->title('Validasi Berkas Gagal')
                ->body($errorMessage)
                ->danger()
                ->send();

            $this->halt();
        } catch (Throwable $e) {
            Storage::disk('private')->delete($attachmentRelativePath);

            Notification::make()
                ->title('Terjadi Kesalahan Server')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
