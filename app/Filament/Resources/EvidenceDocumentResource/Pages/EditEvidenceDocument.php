<?php

declare(strict_types=1);

namespace App\Filament\Resources\EvidenceDocumentResource\Pages;

use App\Exceptions\DuplicateDocumentException;
use App\Filament\Resources\EvidenceDocumentResource;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EditEvidenceDocument extends EditRecord
{
    protected static string $resource = EvidenceDocumentResource::class;

    public function getTitle(): string
    {
        return 'Revisi Pengajuan Bukti Dukung';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var EvidenceDocument $record */
        $record = $this->getRecord();
        $data['lk_ids'] = $record->lkLinks()->pluck('lk_id')->toArray();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var EvidenceDocument $record */
        $uploadService = app(EvidenceUploadService::class);
        $lkIds = (array) ($data['lk_ids'] ?? []);

        $uploadedFile = null;
        $attachmentRelativePath = $data['attachment'] ?? null;
        if (! empty($attachmentRelativePath) && Storage::disk('private')->exists($attachmentRelativePath)) {
            $fullPath = Storage::disk('private')->path($attachmentRelativePath);
            $uploadedFile = new UploadedFile(
                $fullPath,
                basename($attachmentRelativePath),
                null,
                null,
                true
            );
        }

        $metadata = [
            'document_name' => $data['document_name'] ?? $record->document_name,
            'document_number' => $data['document_number'] ?? $record->document_number,
            'year' => (int) ($data['year'] ?? $record->year),
            'document_type' => $data['document_type'] ?? $record->document_type,
            'external_link' => $data['external_link'] ?? $record->external_link,
            'description' => $data['description'] ?? null,
        ];

        try {
            $updated = $uploadService->reviseSubmission($record, $actor, $metadata, $uploadedFile, $lkIds);

            if ($attachmentRelativePath) {
                Storage::disk('private')->delete($attachmentRelativePath);
            }

            Notification::make()
                ->title('Revisi Berhasil Dikirim')
                ->body('Pengajuan Anda telah diperbarui dan dikirim kembali ke antrean verifikasi.')
                ->success()
                ->send();

            return $updated;
        } catch (DuplicateDocumentException $e) {
            if ($attachmentRelativePath) {
                Storage::disk('private')->delete($attachmentRelativePath);
            }

            Notification::make()
                ->title('Revisi Ditolak: Berkas Duplikat')
                ->body('Berkas yang diunggah identik dengan dokumen lain di sistem.')
                ->danger()
                ->send();

            $this->halt();
        } catch (Throwable $e) {
            if ($attachmentRelativePath) {
                Storage::disk('private')->delete($attachmentRelativePath);
            }

            Notification::make()
                ->title('Gagal Memperbarui Pengajuan')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
