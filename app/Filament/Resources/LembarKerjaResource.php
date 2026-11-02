<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LembarKerjaResource\Pages;
use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\User;
use App\Services\LkImport\LkImportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LembarKerjaResource extends Resource
{
    protected static ?string $model = LembarKerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Lembar Kerja';

    protected static ?string $modelLabel = 'Lembar Kerja';

    protected static ?string $pluralModelLabel = 'Daftar Lembar Kerja';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Section::make('Informasi Master Lembar Kerja')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode LK')
                            ->placeholder('Contoh: ZI, PEKPPP, KIP')
                            ->required()
                            ->maxLength(32),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lembar Kerja')
                            ->placeholder('Contoh: LKE Zona Integritas Menuju WBK')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('Kategori LK')
                            ->options([
                                'internal' => 'Internal (BPS)',
                                'eksternal' => 'Eksternal (KemenPAN-RB dsb.)',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('period')
                            ->label('Periode Tahun')
                            ->numeric()
                            ->default((int) date('Y'))
                            ->minValue(2020)
                            ->maxValue(2099)
                            ->required(),

                        Forms\Components\Select::make('default_profile_id')
                            ->label('Profil Impor Default')
                            ->relationship('defaultProfile', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi / Catatan')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode LK')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lembar Kerja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Internal',
                        'eksternal' => 'Eksternal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'internal' => 'success',
                        'eksternal' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('defaultProfile.name')
                    ->label('Profil Default')
                    ->placeholder('Belum diatur'),

                Tables\Columns\TextColumn::make('indicators_count')
                    ->counts('indicators')
                    ->label('Indikator')
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                // Impor Excel Action (LKIMP-01 s.d. LKIMP-07, AC-22, AC-23)
                Tables\Actions\Action::make('import_excel')
                    ->label('Impor Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn (): bool => Auth::user()?->role === 'super_admin')
                    ->modalHeading('Impor Lembar Kerja dari Berkas Excel (.xlsx)')
                    ->modalDescription('Unggah berkas Excel resmi untuk memperbarui indikator dan butir bukti secara idempoten.')
                    ->modalSubmitActionLabel('Mulai Proses Impor')
                    ->form([
                        Forms\Components\Select::make('profile_id')
                            ->label('Profil Impor yang Digunakan')
                            ->options(function (LembarKerja $record): array {
                                $options = LkImportProfile::where('lk_code', $record->code)->pluck('name', 'id')->all();
                                if (empty($options)) {
                                    $options = LkImportProfile::pluck('name', 'id')->all();
                                }

                                return $options;
                            })
                            ->default(fn (LembarKerja $record) => $record->default_profile_id)
                            ->required(),

                        Forms\Components\FileUpload::make('file')
                            ->label('Berkas Excel (.xlsx)')
                            ->helperText('Hanya menerima format .xlsx. File makro (.xlsm) akan ditolak secara otomatis.')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('imports')
                            ->maxSize(20 * 1024) // 20 MB
                            ->required(),
                    ])
                    ->action(function (LembarKerja $record, array $data): void {
                        $profile = LkImportProfile::findOrFail($data['profile_id']);
                        $filePath = Storage::disk('local')->path($data['file']);
                        $originalFilename = basename($data['file']);

                        /** @var User $actor */
                        $actor = Auth::user();

                        try {
                            /** @var LkImportService $importService */
                            $importService = app(LkImportService::class);
                            $log = $importService->commit(
                                filePath: $filePath,
                                lk: $record,
                                profile: $profile,
                                actor: $actor,
                                originalFilename: $originalFilename
                            );

                            Notification::make()
                                ->title('Impor Berhasil Disimpan!')
                                ->body("Berhasil memproses {$log->imported_indicators} indikator dan {$log->imported_items} butir bukti dukung.")
                                ->success()
                                ->send();
                        } finally {
                            if (file_exists($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }),

                // Kelola Tautan Drive LK Langsung
                Tables\Actions\Action::make('manage_drive_links')
                    ->label('Tautan Drive')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->url(fn (LembarKerja $record): string => LkIndicatorResource::getUrl('index', [
                        'tableFilters' => ['lk_id' => ['value' => $record->id]],
                    ])),

                Tables\Actions\EditAction::make(),

                // Protected Soft Delete Guard (AC-14)
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Konfirmasi Hapus Lembar Kerja')
                    ->modalDescription(function (LembarKerja $record): string {
                        $count = $record->indicators()->count();

                        return "Lembar kerja '{$record->name}' memiliki {$count} indikator terkait. Penghapusan akan memindahkannya ke arsip (soft delete) sehingga relasi historis tetap terjaga.";
                    })
                    ->successNotificationTitle('Lembar Kerja berhasil dihapus (soft delete).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLembarKerjas::route('/'),
            'create' => Pages\CreateLembarKerja::route('/create'),
            'edit' => Pages\EditLembarKerja::route('/{record}/edit'),
        ];
    }
}
