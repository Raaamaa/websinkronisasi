<?php

declare(strict_types=1);

namespace App\Filament\Resources\EvidenceDocumentResource\Pages;

use App\Filament\Resources\EvidenceDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvidenceDocument extends ViewRecord
{
    protected static string $resource = EvidenceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Unduh Berkas')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(fn (): string => route('evidence.download', $this->record->id))
                ->openUrlInNewTab(),
        ];
    }
}
