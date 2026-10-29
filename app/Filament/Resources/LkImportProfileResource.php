<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LkImportProfileResource\Pages;
use App\Models\LkImportProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LkImportProfileResource extends Resource
{
    protected static ?string $model = LkImportProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Profil Impor';

    protected static ?string $modelLabel = 'Profil Impor';

    protected static ?string $pluralModelLabel = 'Daftar Profil Impor';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

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

        return $user !== null && $user->role === 'super_admin' && $user->is_active;
    }

    public static function canEdit($record): bool
    {
        return self::canCreate();
    }

    public static function canDelete($record): bool
    {
        return self::canCreate();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profil Impor')
                    ->description('Konfigurasi pembacaan berkas Excel untuk Lembar Kerja')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Profil')
                            ->placeholder('Contoh: Profil Standar LKE ZI')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('lk_code')
                            ->label('Kode LK Terkait')
                            ->placeholder('ZI, PEKPPP, KIP, dll.')
                            ->required()
                            ->maxLength(32),

                        Forms\Components\TextInput::make('sheet_name')
                            ->label('Nama Sheet Excel')
                            ->placeholder('Contoh: LKE, Penilaian, Sheet1')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('header_row')
                            ->label('Baris Header (1-based index)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Select::make('parsing_strategy')
                            ->label('Strategi Parsing')
                            ->options([
                                'numbered_list_single_cell' => 'Daftar Bernomor 1 Sel (Contoh: LKE ZI)',
                                'checklist_sheet' => 'Sheet Checklist Terpisah (Contoh: PEKPPP)',
                                'single_item_per_row' => 'Satu Item Per Baris (Contoh: KIP / Generik)',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('target_unit_column')
                            ->label('Kolom Satuan Kerja (Opsional)')
                            ->placeholder('Contoh: F')
                            ->maxLength(64)
                            ->helperText('Kolom pembeda unit kerja jika terdapat beberapa satker dalam satu LK'),

                        Forms\Components\KeyValue::make('column_mapping')
                            ->label('Pemetaan Kolom (Column Mapping)')
                            ->keyLabel('Kunci Pemetaan')
                            ->valueLabel('Huruf Kolom / Nilai')
                            ->helperText('Contoh kunci: code_col, title_col, evidence_items_col, evidence_link_col, pic_col')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Profil')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('lk_code')
                    ->label('Kode LK')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sheet_name')
                    ->label('Nama Sheet')
                    ->searchable(),

                Tables\Columns\TextColumn::make('header_row')
                    ->label('Baris Header')
                    ->sortable(),

                Tables\Columns\TextColumn::make('parsing_strategy')
                    ->label('Strategi Parsing')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'numbered_list_single_cell' => 'Daftar Bernomor (ZI)',
                        'checklist_sheet' => 'Checklist Terpisah (PEKPPP)',
                        'single_item_per_row' => '1 Item Per Baris (KIP)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'numbered_list_single_cell' => 'success',
                        'checklist_sheet' => 'info',
                        'single_item_per_row' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('target_unit_column')
                    ->label('Kolom Satker')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLkImportProfiles::route('/'),
            'create' => Pages\CreateLkImportProfile::route('/create'),
            'edit' => Pages\EditLkImportProfile::route('/{record}/edit'),
        ];
    }
}
