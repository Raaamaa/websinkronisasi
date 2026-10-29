<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EvidenceDocumentResource\Pages;
use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EvidenceDocumentResource extends Resource
{
    protected static ?string $model = EvidenceDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Bukti Dukung';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Daftar Bukti Dukung';

    protected static ?string $navigationGroup = 'Bukti Dukung';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && in_array($user->role, ['super_admin', 'admin', 'pegawai'], true) && $user->is_active;
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // BD-01: Admin dan Super Admin dapat mengunggah dokumen
        return $user !== null && in_array($user->role, ['super_admin', 'admin'], true) && $user->is_active;
    }

    public static function canEdit($record): bool
    {
        return false; // Dokumen bersifat immutable setelah diunggah demi integritas audit
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        // BD-09: Penghapusan master BD hanya oleh super admin
        return $user !== null && $user->role === 'super_admin' && $user->is_active;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Metadata Dokumen')
                    ->description('Lengkapi identitas bukti dukung resmi.')
                    ->schema([
                        Forms\Components\TextInput::make('document_name')
                            ->label('Nama Dokumen')
                            ->placeholder('Contoh: SK Tim Reformasi Birokrasi BPS')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Nomor Dokumen / Surat')
                            ->placeholder('Contoh: 01/BPS/2026')
                            ->maxLength(128),

                        Forms\Components\TextInput::make('year')
                            ->label('Tahun Dokumen')
                            ->numeric()
                            ->default((int) date('Y'))
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->required(),

                        Forms\Components\Select::make('document_type')
                            ->label('Jenis Dokumen')
                            ->options([
                                'SK' => 'Surat Keputusan (SK)',
                                'SOP' => 'Standar Operasional Prosedur (SOP)',
                                'Laporan' => 'Laporan Kinerja / Kegiatan',
                                'Surat Edaran' => 'Surat Edaran / Nota Dinas',
                                'Data' => 'Data / Statistik / Matriks',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->searchable(),

                        Forms\Components\TextInput::make('external_link')
                            ->label('Tautan Sumber Dokumen (Opsional)')
                            ->placeholder('https://bps.go.id/docs/sk.pdf')
                            ->url()
                            ->regex('/^https?:\/\/.+/i')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Berkas Fisik Dokumen')
                    ->description('Format diizinkan: PDF, DOCX, JPG, PNG (Maks. 20 MB).')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Pilih Berkas')
                            ->disk('private')
                            ->directory('evidence_temp')
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_name')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Jenis')
                    ->badge()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('file_size_bytes')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Status Ekstraksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'selesai' => 'success',
                        'perlu_review_manual' => 'warning',
                        'memproses' => 'gray',
                        'gagal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'selesai' => 'Selesai',
                        'perlu_review_manual' => 'Perlu Review Manual',
                        'memproses' => 'Memproses',
                        'gagal' => 'Gagal',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Pengunggah')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Unggah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn () => EvidenceDocument::query()->distinct()->pluck('year', 'year')->toArray()),

                Tables\Filters\SelectFilter::make('processing_status')
                    ->label('Status Proses')
                    ->options([
                        'memproses' => 'Memproses',
                        'selesai' => 'Selesai',
                        'perlu_review_manual' => 'Perlu Review Manual',
                        'gagal' => 'Gagal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (EvidenceDocument $record): string => route('evidence.download', $record->id))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (): bool => Auth::user()?->role === 'super_admin')
                    ->before(function (EvidenceDocument $record): void {
                        /** @var User $actor */
                        $actor = Auth::user();
                        AuditLog::create([
                            'user_id' => $actor->id,
                            'action' => 'bd.deleted',
                            'entity_type' => 'EvidenceDocument',
                            'entity_id' => $record->id,
                            'old_values' => [
                                'document_name' => $record->document_name,
                                'file_hash' => $record->file_hash,
                            ],
                        ]);
                    })
                    ->modalHeading('Hapus Bukti Dukung')
                    ->modalDescription('Dokumen akan dihapus secara soft-delete. Seluruh relasi kecocokan indikator yang terkait dokumen ini akan disembunyikan.'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Rincian Dokumen')
                    ->schema([
                        Infolists\Components\TextEntry::make('document_name')->label('Nama Dokumen'),
                        Infolists\Components\TextEntry::make('document_number')->label('Nomor Dokumen')->placeholder('-'),
                        Infolists\Components\TextEntry::make('year')->label('Tahun Dokumen'),
                        Infolists\Components\TextEntry::make('document_type')->label('Jenis Dokumen')->placeholder('-'),
                        Infolists\Components\TextEntry::make('original_filename')->label('Nama Berkas Asli'),
                        Infolists\Components\TextEntry::make('file_size_bytes')
                            ->label('Ukuran Berkas')
                            ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                        Infolists\Components\TextEntry::make('file_hash')->label('SHA-256 Hash')->fontFamily('mono'),
                        Infolists\Components\TextEntry::make('processing_status')
                            ->label('Status Pemrosesan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'selesai' => 'success',
                                'perlu_review_manual' => 'warning',
                                'memproses' => 'gray',
                                'gagal' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Catatan Pemrosesan')
                            ->visible(fn ($record) => ! empty($record->error_message))
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('external_link')
                            ->label('Tautan Dokumen Eksternal')
                            ->url(fn ($record) => $record->external_link, true)
                            ->visible(fn ($record) => ! empty($record->external_link)),
                    ])->columns(2),

                Infolists\Components\Section::make('Teks Hasil Ekstraksi (Digital / OCR)')
                    ->schema([
                        Infolists\Components\TextEntry::make('extracted_text')
                            ->label('')
                            ->placeholder('Teks belum diekstrak atau dokumen tidak mengandung teks terbaca.')
                            ->columnSpanFull()
                            ->fontFamily('mono'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvidenceDocuments::route('/'),
            'create' => Pages\CreateEvidenceDocument::route('/create'),
            'view' => Pages\ViewEvidenceDocument::route('/{record}'),
        ];
    }
}
