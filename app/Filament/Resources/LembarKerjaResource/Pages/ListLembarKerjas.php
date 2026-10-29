<?php

declare(strict_types=1);

namespace App\Filament\Resources\LembarKerjaResource\Pages;

use App\Filament\Resources\LembarKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLembarKerjas extends ListRecords
{
    protected static string $resource = LembarKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
