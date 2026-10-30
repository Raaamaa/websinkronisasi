<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static ?string $modelLabel = 'Pengaturan Sistem';

    protected static ?string $pluralModelLabel = 'Pengaturan Sistem';

    protected static ?string $navigationGroup = 'Manajemen Sistem';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->isSuperAdmin() && $user->is_active;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Parameter Konfigurasi')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Kunci Parameter')
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi Fungsi')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('value')
                            ->label('Nilai Pengaturan')
                            ->helperText('Masukkan nilai konfigurasi (angka ambang batas, batas MB, durasi menit, dsb.)')
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? (float) $state : $state),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Kunci Konfigurasi')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap(),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diperbarui Oleh')
                    ->default('Bawaan Sistem')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemSettings::route('/'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
}
