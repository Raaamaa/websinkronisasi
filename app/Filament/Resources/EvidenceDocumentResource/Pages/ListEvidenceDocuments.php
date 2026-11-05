<?php

declare(strict_types=1);

namespace App\Filament\Resources\EvidenceDocumentResource\Pages;

use App\Filament\Resources\EvidenceDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvidenceDocuments extends ListRecords
{
    protected static string $resource = EvidenceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Unggah Bukti Dukung')
                ->icon('heroicon-o-arrow-up-tray')
                ->button(),
        ];
    }
}
