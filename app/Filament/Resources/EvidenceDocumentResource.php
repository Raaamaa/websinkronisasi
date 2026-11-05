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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Unggah Bukti Dukung';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Daftar Bukti Dukung';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;

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

        // Seluruh peran aktif (super_admin, admin, pegawai) dapat mengunggah bukti dukung
        return $user !== null && in_array($user->role, ['super_admin', 'admin', 'pegawai'], true) && $user->is_active;
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
                Forms\Components\Section::make('Pilih Berkas Dokumen Fisik / Digital')
                    ->description('Maksimal 1 file per unggahan • Format: PDF, DOCX, JPG, PNG (Maks. 20 MB)')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Unggah Berkas Bukti')
                            ->disk('private')
                            ->directory('evidence_temp')
                            ->preserveFilenames()
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->required()
                            ->helperText('Validasi otomatis MIME-sniffing & SHA-256 Hash anti-duplikasi aktif.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Atribut Metadata Dokumen')
                    ->description('Lengkapi identitas bukti dukung resmi (* Wajib diisi).')
                    ->schema([
                        Forms\Components\TextInput::make('document_name')
                            ->label('Nama Dokumen')
                            ->placeholder('Contoh: SK Tim Kerja Pembangunan Zona Integritas Tahun 2026')
                            ->helperText('Nama deskriptif resmi yang akan dicantumkan dalam lembar kerja evaluasi.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Nomor Dokumen / Nomor SK (Opsional)')
                            ->placeholder('Contoh: B-104/0200/KP.010/2026')
                            ->helperText('Membantu pencocokan deterministik instan 100% terhadap butir instrumen ZI & PEKPPP.')
                            ->maxLength(128)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('year')
                            ->label('Tahun / Periode')
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
                            ->default('SK')
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('external_link')
                            ->label('Tautan Google Drive (Opsional)')
                            ->placeholder('https://drive.google.com/drive/folders/...')
                            ->helperText('Hanya menerima protokol resmi https:// (Akses izin diset BPS Domain Internal)')
                            ->url()
                            ->regex('/^https?:\/\/.+/i')
                            ->columnSpanFull(),
                    ])->columns(2),
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
            ->emptyStateHeading('Belum Ada Bukti Dukung Terunggah')
            ->emptyStateDescription('Mulai unggah dokumen SK, Laporan, atau Data resmi untuk memicu pencocokan otomatis ke Lembar Kerja.')
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Unggah Bukti Dukung Sekarang')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->button(),
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
