<?php

declare(strict_types=1);

namespace App\Filament\Resources\LkImportProfileResource\Pages;

use App\Filament\Resources\LkImportProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLkImportProfile extends EditRecord
{
    protected static string $resource = LkImportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
