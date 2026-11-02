<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\UserManagerService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Tambah Admin / Pengguna')
                ->using(function (array $data, UserManagerService $manager): User {
                    /** @var User $actor */
                    $actor = auth()->user();

                    return $manager->createUser($actor, $data);
                }),
        ];
    }
}
