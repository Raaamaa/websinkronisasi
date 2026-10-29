<?php

declare(strict_types=1);

namespace App\Filament\Resources\EvidenceDocumentResource\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Filament\Resources\EvidenceDocumentResource;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateEvidenceDocument extends CreateRecord
{
    protected static string $resource = EvidenceDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = Auth::user();

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
            mime_content_type($fullPath) ?: null,
            null,
            true // mode test / bypass is_uploaded_file check untuk file lokal yang dipindahkan Filament
        );

        $uploadService = app(EvidenceUploadService::class);

        try {
            $document = $uploadService->upload($uploadedFile, $data, $actor);

            // Bersihkan berkas sementara dari directory evidence_temp
            Storage::disk('private')->delete($attachmentRelativePath);

            return $document;
        } catch (DuplicateDocumentException $e) {
            Storage::disk('private')->delete($attachmentRelativePath);

            Notification::make()
                ->title('Unggah Ditolak (Duplikasi Dokumen)')
                ->body("Dokumen identik sudah ada di sistem dengan nama: '{$e->existingDocument->document_name}'.")
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
