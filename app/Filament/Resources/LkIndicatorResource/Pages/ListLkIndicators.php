<?php

declare(strict_types=1);

namespace App\Filament\Resources\LkIndicatorResource\Pages;

use App\Filament\Resources\LkIndicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLkIndicators extends ListRecords
{
    protected static string $resource = LkIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Tambah Indikator Baru'),
        ];
    }
}

