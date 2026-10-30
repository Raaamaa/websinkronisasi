<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Jejak Audit';

    protected static ?string $modelLabel = 'Jejak Audit';

    protected static ?string $pluralModelLabel = 'Catatan Jejak Audit';

    protected static ?string $navigationGroup = 'Manajemen Sistem';

    protected static ?int $navigationSort = 3;

    /**
     * Jejak audit HANYA dapat diakses oleh Super Admin (AUDIT-03, RBAC.md).
     */
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->isSuperAdmin() && $user->is_active;
    }

    public static function canCreate(): bool
    {
        return false; // Rekaman audit bersifat otomatis dan append-only (AUDIT-02)
    }

    public static function canEdit($record): bool
    {
        return false; // Rekaman audit dilarang diubah (AUDIT-02)
    }

    public static function canDelete($record): bool
    {
        return false; // Rekaman audit dilarang dihapus (AUDIT-02)
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Aksi')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->searchable()
                    ->default('Sistem / Tamu')
                    ->description(fn (AuditLog $record): ?string => $record->user?->email),

                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'auth.') => 'info',
                        str_ends_with($state, '.deleted') => 'danger',
                        str_ends_with($state, '.created') || str_ends_with($state, '.activated') => 'success',
                        str_ends_with($state, '.updated') || str_contains($state, 'role_changed') => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entitas')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('entity_id')
                    ->label('ID Target')
                    ->fontFamily('mono')
                    ->limit(16)
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Jenis Aksi')
                    ->options(fn () => AuditLog::query()->distinct()->pluck('action', 'action')->toArray())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pengguna Pelaku')
                    ->relationship('user', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Log Audit')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Aksi')
                            ->dateTime('d F Y, H:i:s'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Nama Pengguna')
                            ->default('Sistem / Anonim'),

                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email Pengguna')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('action')
                            ->label('Aksi')
                            ->badge(),

                        Infolists\Components\TextEntry::make('entity_type')
                            ->label('Tipe Entitas'),

                        Infolists\Components\TextEntry::make('entity_id')
                            ->label('ID Entitas Target')
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('Alamat IP')
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->color('gray'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Data Perubahan (Diff State)')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('old_values')
                            ->label('Nilai Sebelum (Old Values)')
                            ->columnSpan(1),

                        Infolists\Components\KeyValueEntry::make('new_values')
                            ->label('Nilai Sesudah (New Values)')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
