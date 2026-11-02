<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\Auth\UserManagerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Daftar Pengguna';

    protected static ?string $navigationGroup = 'Manajemen Sistem';

    protected static ?int $navigationSort = 1;

    /**
     * Pegawai tidak berwenang melihat menu Pengguna (RBAC.md).
     */
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && in_array($user->role, ['super_admin', 'admin'], true) && $user->is_active;
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Super Admin dapat menambahkan akun admin/pengguna baru secara manual
        return $user !== null && $user->isSuperAdmin() && $user->is_active;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (string $context): bool => $context !== 'create'),
                Forms\Components\TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (string $context): bool => $context !== 'create'),
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->maxLength(64)
                    ->disabled(fn (string $context): bool => $context !== 'create'),
                Forms\Components\Select::make('role')
                    ->label('Peran (Role)')
                    ->options([
                        'admin' => 'Admin (Operasional & Kelola Link Drive)',
                        'pegawai' => 'Pegawai (Matching & Monitor)',
                    ])
                    ->required()
                    ->default('admin')
                    ->disabled(fn (): bool => ! Auth::user()?->isSuperAdmin()),
                Forms\Components\TextInput::make('password')
                    ->label('Kata Sandi Awal')
                    ->password()
                    ->visible(fn (string $context): bool => $context === 'create')
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Alamat Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'pegawai' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'pegawai' => 'Pegawai',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('auth_provider')
                    ->label('Metode Login')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'sso' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'pegawai' => 'Pegawai',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Keaktifan Akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                // Aksi Aktif / Nonaktifkan Akun
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        /** @var User|null $actor */
                        $actor = Auth::user();
                        if ($actor === null) {
                            return false;
                        }

                        // Dilarang menonaktifkan akun sendiri
                        if ($actor->id === $record->id) {
                            return false;
                        }

                        // Super admin bisa toggle semua
                        if ($actor->isSuperAdmin()) {
                            return true;
                        }

                        // Admin HANYA bisa toggle Pegawai (AUTH-08, AC-19, AC-20)
                        return $actor->isAdmin() && $record->role === 'pegawai';
                    })
                    ->action(function (User $record, UserManagerService $manager): void {
                        /** @var User $actor */
                        $actor = Auth::user();
                        $newStatus = ! $record->is_active;

                        try {
                            $manager->toggleActiveStatus($actor, $record, $newStatus);

                            Notification::make()
                                ->title($newStatus ? 'Akun Berhasil Diaktifkan' : 'Akun Berhasil Dinonaktifkan')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Aksi Ditolak')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Aksi Ubah Peran (Hanya Super Admin)
                Tables\Actions\Action::make('change_role')
                    ->label('Ubah Peran')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function (User $record): bool {
                        /** @var User|null $actor */
                        $actor = Auth::user();

                        return $actor !== null && $actor->isSuperAdmin() && $actor->id !== $record->id;
                    })
                    ->form([
                        Forms\Components\Select::make('new_role')
                            ->label('Pilih Peran Baru')
                            ->options([
                                'admin' => 'Admin (Operasional Harian)',
                                'pegawai' => 'Pegawai (Read-Only)',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, UserManagerService $manager): void {
                        /** @var User $actor */
                        $actor = Auth::user();

                        try {
                            $manager->changeUserRole($actor, $record, (string) $data['new_role']);

                            Notification::make()
                                ->title('Peran Pengguna Berhasil Diperbarui')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Mengubah Peran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
