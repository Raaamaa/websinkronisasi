<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EvidenceDocumentResource\Pages;
use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Evidence\DocumentSimilarityService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EvidenceDocumentResource extends Resource
{
    protected static ?string $model = EvidenceDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Unggah Bukti';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Unggah Bukti';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null
            && $user->is_active
            && $user->deleted_at === null
            && $user->hasPermission('evidence.upload');
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->hasPermission('evidence.upload');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();
        if ($user !== null && $user->role === 'pegawai') {
            $query->where('uploaded_by', $user->id);
        }

        return $query;
    }

    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        return static::getModel()::query()->where(static::getRecordRouteKeyName() ?? 'id', $key)->first();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Metode Pengajuan Bukti Dukung')
                    ->description('Berkas lebih dari 20 MB (mis. video): unggah dulu ke Google Drive, lalu kirim tautannya.')
                    ->schema([
                        Forms\Components\Radio::make('source_type')
                            ->label('Pilih Jenis Pengajuan')
                            ->options([
                                'upload' => 'Unggah Berkas (maks 20 MB)',
                                'link' => 'Tempel Tautan (untuk berkas >20 MB / video)',
                            ])
                            ->default('upload')
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Unggah Berkas Bukti')
                            ->disk('private')
                            ->directory('evidence_temp')
                            ->preserveFilenames()
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('source_type') !== 'link')
                            ->required(fn (Forms\Get $get): bool => $get('source_type') !== 'link')
                            ->helperText('Format: PDF, DOCX, XLSX, JPG, PNG. Maksimal 20 MB per berkas.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('external_link')
                            ->label('Tautan Dokumen / Video')
                            ->placeholder('https://drive.google.com/file/d/... atau https://...')
                            ->helperText('Hanya menerima protokol resmi http:// atau https://')
                            ->url()
                            ->regex('/^https?:\/\/.+/i')
                            ->visible(fn (Forms\Get $get): bool => $get('source_type') === 'link')
                            ->required(fn (Forms\Get $get): bool => $get('source_type') === 'link')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Atribut Identitas Dokumen')
                    ->description('Lengkapi identitas bukti dukung resmi.')
                    ->schema([
                        Forms\Components\TextInput::make('document_name')
                            ->label('Nama Dokumen')
                            ->placeholder('Contoh: SK Tim Kerja Pembangunan Zona Integritas Tahun 2026')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Nomor Dokumen / SK (Opsional)')
                            ->placeholder('Contoh: B-104/0200/KP.010/2026')
                            ->maxLength(128)
                            ->live(onBlur: true)
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

                        Forms\Components\Select::make('lk_ids')
                            ->label('Pilih Lembar Kerja (LK)')
                            ->placeholder('Pilih satu atau lebih LK tujuan usulan bukti...')
                            ->options(fn () => LembarKerja::where('is_active', true)->whereNull('deleted_at')->pluck('name', 'id')->toArray())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->minItems(1)
                            ->validationMessages([
                                'required' => 'Wajib memilih minimal satu Lembar Kerja (LK).',
                                'min' => 'Wajib memilih minimal satu Lembar Kerja (LK).',
                            ])
                            ->helperText('Dokumen akan diajukan ke Lembar Kerja yang dipilih sebagai usulan resmi.')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('similar_warning')
                            ->label('Pendeteksi Kemiripan Dokumen')
                            ->content(function (Forms\Get $get, ?EvidenceDocument $record): HtmlString {
                                $name = (string) ($get('document_name') ?? '');
                                if (mb_strlen(trim($name)) < 4) {
                                    return new HtmlString('<span class="text-xs text-slate-500">Ketik nama dokumen untuk mendeteksi kemiripan otomatis.</span>');
                                }

                                /** @var DocumentSimilarityService $simService */
                                $simService = app(DocumentSimilarityService::class);
                                $excludeId = $record?->id;
                                $candidates = $simService->findSimilarCandidates($name, $get('document_number'), null, $excludeId, 5);

                                if (empty($candidates)) {
                                    return new HtmlString('<span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">✓ Belum ada dokumen serupa yang terdaftar. Dokumen aman diajukan.</span>');
                                }

                                $html = '<div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200">';
                                $html .= '<div class="font-bold mb-1">Perhatian: Ditemukan dokumen mirip di sistem (Maks. 5 kandidat):</div><ul class="list-disc pl-4 space-y-0.5">';
                                foreach ($candidates as $cand) {
                                    $html .= '<li><strong>'.e($cand['document_name']).'</strong> ('.round($cand['similarity_score'], 1).'%) '.($cand['document_number'] ? '• No: '.e($cand['document_number']) : '').'</li>';
                                }
                                $html .= '</ul></div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Rinci (opsional)')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Catatan / Keterangan Tambahan')
                            ->placeholder('Tambahkan catatan konteks bukti dukung ini bila diperlukan...')
                            ->rows(3)
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

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upload' => 'info',
                        'link' => 'warning',
                        'drive' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'upload' => 'Unggah Berkas',
                        'link' => 'Tautan Luar',
                        'drive' => 'Google Drive',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('lkLinks.lembarKerja.code')
                    ->label('Usulan LK')
                    ->badge()
                    ->getStateUsing(fn (EvidenceDocument $record): array => $record->lkLinks->pluck('lembarKerja.code')->filter()->unique()->values()->all())
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status Pengajuan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disetujui' => 'success',
                        'revisi' => 'warning',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disetujui' => 'Disetujui',
                        'revisi' => 'Perlu Revisi',
                        'ditolak' => 'Ditolak',
                        default => 'Menunggu Verifikasi',
                    }),

                Tables\Columns\TextColumn::make('approval_notes')
                    ->label('Catatan Admin')
                    ->placeholder('-')
                    ->wrap()
                    ->color('danger')
                    ->visible(fn (): bool => true),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Pengunggah')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Unggah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Status Pengajuan')
                    ->options([
                        'menunggu' => 'Menunggu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'revisi' => 'Perlu Revisi',
                        'ditolak' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn () => EvidenceDocument::query()->distinct()->pluck('year', 'year')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->label('Revisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(function (EvidenceDocument $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user !== null
                            && $record->approval_status === 'revisi'
                            && $record->uploaded_by !== null
                            && (string) $record->uploaded_by === (string) $user->id
                            && $user->can('update', $record);
                    }),

                Tables\Actions\Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (EvidenceDocument $record): bool => ! empty($record->file_path))
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
            ->emptyStateHeading('Belum Ada Bukti Dukung')
            ->emptyStateDescription('Mulai ajukan dokumen bukti dukung resmi untuk diverifikasi oleh admin.')
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
                        Infolists\Components\TextEntry::make('source_type')->label('Sumber Pengajuan')->badge(),
                        Infolists\Components\TextEntry::make('approval_status')
                            ->label('Status Verifikasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'disetujui' => 'success',
                                'revisi' => 'warning',
                                'ditolak' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('approval_notes')
                            ->label('Catatan Admin')
                            ->visible(fn ($record) => ! empty($record->approval_notes))
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('original_filename')->label('Nama Berkas Asli')->placeholder('-'),
                        Infolists\Components\TextEntry::make('file_size_bytes')
                            ->label('Ukuran Berkas')
                            ->formatStateUsing(fn ($state): string => $state ? number_format($state / 1024, 1).' KB' : '-'),
                        Infolists\Components\TextEntry::make('external_link')
                            ->label('Tautan Dokumen Eksternal')
                            ->url(fn ($record) => $record->external_link, true)
                            ->visible(fn ($record) => ! empty($record->external_link)),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvidenceDocuments::route('/'),
            'create' => Pages\CreateEvidenceDocument::route('/create'),
            'edit' => Pages\EditEvidenceDocument::route('/{record}/edit'),
            'view' => Pages\ViewEvidenceDocument::route('/{record}'),
        ];
    }
}
