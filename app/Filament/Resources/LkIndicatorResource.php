<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LkIndicatorResource\Pages;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LkIndicatorResource extends Resource
{
    protected static ?string $model = LkIndicator::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Tautan Drive LK';

    protected static ?string $modelLabel = 'Tautan Drive Indikator';

    protected static ?string $pluralModelLabel = 'Kelola Tautan Drive LK';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 8;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Hanya Super Admin dan Admin yang dapat mengelola link drive LK (RBAC.md)
        return $user !== null && in_array($user->role, ['super_admin', 'admin'], true) && $user->is_active;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->role === 'super_admin' && $user->is_active;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Indikator & Tautan Google Drive')
                    ->description('Tentukan lembar kerja, kode butir, dan tautan Google Drive penyimpanan bukti dukung.')
                    ->schema([
                        Forms\Components\Select::make('lk_id')
                            ->label('Lembar Kerja')
                            ->relationship('lembarKerja', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Indikator')
                            ->placeholder('Contoh: 1.1.a atau AI1i')
                            ->required()
                            ->maxLength(64),

                        Forms\Components\Textarea::make('title')
                            ->label('Judul Indikator / Kebutuhan Bukti')
                            ->placeholder('Tuliskan butir indikator penilaian...')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('evidence_link')
                            ->label('Tautan Google Drive Bukti Dukung')
                            ->placeholder('https://drive.google.com/drive/folders/...')
                            ->helperText('Wajib skema https:// atau http:// (misal Google Drive folder atau file resmi).')
                            ->url()
                            ->regex('/^https?:\/\/.+/i')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('pic')
                            ->label('PIC / Penanggung Jawab')
                            ->placeholder('Nama seksi atau pegawai PIC')
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lembarKerja.code')
                    ->label('LK')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Indikator / Kebutuhan Bukti Dukung')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('evidence_link')
                    ->label('Tautan Google Drive')
                    ->formatStateUsing(fn (?string $state): string => ! empty($state) ? 'Buka Google Drive ↗' : 'Belum Diatur')
                    ->badge()
                    ->color(fn (?string $state): string => ! empty($state) ? 'success' : 'gray')
                    ->url(fn (LkIndicator $record): ?string => $record->evidence_link)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lk_id')
                    ->label('Filter Lembar Kerja')
                    ->relationship('lembarKerja', 'code'),

                Tables\Filters\TernaryFilter::make('evidence_link')
                    ->label('Status Tautan Drive')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Ada Link Drive')
                    ->falseLabel('Belum Ada Link Drive')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('evidence_link')->where('evidence_link', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('evidence_link')->orWhere('evidence_link', ''),
                    ),
            ])
            ->actions([
                // Aksi Cepat Atur / Ubah Link Drive
                Tables\Actions\Action::make('set_drive_link')
                    ->label('Atur Link Drive')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('evidence_link')
                            ->label('URL Google Drive Bukti Dukung')
                            ->placeholder('https://drive.google.com/drive/folders/...')
                            ->url()
                            ->regex('/^https?:\/\/.+/i')
                            ->default(fn (LkIndicator $record): ?string => $record->evidence_link)
                            ->required(),
                    ])
                    ->action(function (LkIndicator $record, array $data): void {
                        $oldLink = $record->evidence_link;
                        $record->update(['evidence_link' => $data['evidence_link']]);

                        AuditLog::log(
                            Auth::user(),
                            'lk_indicator.update_drive_link',
                            $record,
                            ['evidence_link' => $oldLink],
                            ['evidence_link' => $data['evidence_link']]
                        );

                        Notification::make()
                            ->title('Tautan Google Drive Berhasil Disimpan')
                            ->success()
                            ->send();
                    }),

                // Aksi Hapus Link Drive
                Tables\Actions\Action::make('clear_drive_link')
                    ->label('Hapus Link')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LkIndicator $record): bool => ! empty($record->evidence_link))
                    ->action(function (LkIndicator $record): void {
                        $oldLink = $record->evidence_link;
                        $record->update(['evidence_link' => null]);

                        AuditLog::log(
                            Auth::user(),
                            'lk_indicator.clear_drive_link',
                            $record,
                            ['evidence_link' => $oldLink],
                            ['evidence_link' => null]
                        );

                        Notification::make()
                            ->title('Tautan Google Drive Berhasil Dihapus')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLkIndicators::route('/'),
        ];
    }
}

