<?php

declare(strict_types=1);

namespace App\Filament\Resources\LkImportProfileResource\Pages;

use App\Filament\Resources\LkImportProfileResource;
use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\User;
use App\Services\LkImport\DTOs\ParsedImportResult;
use App\Services\LkImport\LkImportService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListLkImportProfiles extends ListRecords
{
    protected static string $resource = LkImportProfileResource::class;

    protected static string $view = 'filament.resources.lk-import-profile-resource.pages.list-lk-import-profiles';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    // Wizard Stage (1 to 4)
    public int $currentStep = 2;

    // File and LK Info
    public ?string $uploadedFileName = null;

    public ?string $uploadedFileSize = null;

    public ?string $uploadedFilePath = null;

    public ?string $selectedLkId = null;

    public ?string $selectedProfileId = null;

    // Column Entity Mappings
    public string $colCode = 'B';

    public string $colTitle = 'C';

    public string $colEvidence = 'D';

    public string $parsingStrategy = 'numbered_list_single_cell';

    public string $colDriveLink = 'E';

    public string $colPic = 'F';

    // Template preservation options
    public bool $saveAsTemplate = false;

    public string $newTemplateName = 'Profil ZI BPS v2';

    // Toggles for expanding sections
    public bool $showAllAnomalies = false;

    public bool $showAllPreviews = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Profil Baru')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => Auth::user()?->role === 'super_admin'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Default LK: ZI atau LK aktif pertama
        if (empty($this->selectedLkId)) {
            $defaultLk = LembarKerja::where('is_active', true)->where('code', 'ZI')->first()
                ?? LembarKerja::where('is_active', true)->first();
            $this->selectedLkId = $defaultLk ? (string) $defaultLk->id : null;
        }

        // Default Profile: Profil ZI
        if (empty($this->selectedProfileId)) {
            $defaultProfile = LkImportProfile::where('lk_code', 'ZI')->first()
                ?? LkImportProfile::first();
            $this->selectedProfileId = $defaultProfile ? (string) $defaultProfile->id : null;
        }

        if ($this->selectedProfileId) {
            $this->applyProfilePreset($this->selectedProfileId);
        }
    }

    public function getSelectedLkProperty(): ?LembarKerja
    {
        if (empty($this->selectedLkId)) {
            return null;
        }

        return LembarKerja::find($this->selectedLkId);
    }

    public function getSelectedProfileProperty(): ?LkImportProfile
    {
        if (empty($this->selectedProfileId)) {
            return null;
        }

        return LkImportProfile::find($this->selectedProfileId);
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableLks(): array
    {
        return LembarKerja::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (LembarKerja $lk) => [$lk->id => "{$lk->code} - {$lk->name} ({$lk->period})"])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableProfiles(): array
    {
        return LkImportProfile::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function getColumnOptions(): array
    {
        return [
            'A' => '[ Kolom A ] - No Urut / Indeks',
            'B' => '[ Kolom B ] - No / Kode Butir',
            'C' => '[ Kolom C ] - Kriteria & Uraian Penilaian',
            'D' => '[ Kolom D ] - Dokumen Bukti yang Dibutuhkan',
            'E' => '[ Kolom E ] - Hyperlink Drive / Web BPS',
            'F' => '[ Kolom F ] - Satuan Kerja PIC Pelaksana',
            'G' => '[ Kolom G ] - Bobot Nilai Standar',
            'H' => '[ Kolom H ] - Catatan Asesor',
        ];
    }

    public function updatedSelectedProfileId(?string $value): void
    {
        if (! empty($value)) {
            $this->applyProfilePreset($value);
        }
    }

    public function applyProfilePreset(string $profileId): void
    {
        $profile = LkImportProfile::find($profileId);
        if (! $profile) {
            return;
        }

        $mapping = $profile->column_mapping ?? [];
        $this->colCode = $mapping['code_col'] ?? 'B';
        $this->colTitle = $mapping['title_col'] ?? 'C';
        $this->colEvidence = $mapping['evidence_items_col'] ?? 'D';
        $this->colDriveLink = $mapping['evidence_link_col'] ?? 'E';
        $this->colPic = $mapping['pic_col'] ?? 'F';
        $this->parsingStrategy = $profile->parsing_strategy ?? 'numbered_list_single_cell';

        Notification::make()
            ->title('Profil Dimuat')
            ->body("Konfigurasi pemetaan kolom diperbarui dari '{$profile->name}'.")
            ->info()
            ->send();
    }

    public function updatedUploadedFilePath(?string $path): void
    {
        if (! empty($path) && file_exists($path)) {
            if (empty($this->uploadedFileName)) {
                $this->uploadedFileName = basename($path);
            }
            $bytes = filesize($path) ?: 0;
            if ($bytes >= 1048576) {
                $this->uploadedFileSize = round($bytes / 1048576, 1).' MB';
            } else {
                $this->uploadedFileSize = round($bytes / 1024, 1).' KB';
            }
        }
    }

    protected function resolveActiveProfile(?LembarKerja $lk): LkImportProfile
    {
        $profile = null;
        if ($this->selectedProfileId) {
            $profile = LkImportProfile::find($this->selectedProfileId);
        }

        if ($profile === null) {
            $profile = new LkImportProfile([
                'lk_code' => $lk?->code ?? 'ZI',
                'sheet_name' => 'LKE',
                'header_row' => 1,
                'parsing_strategy' => $this->parsingStrategy,
                'column_mapping' => [
                    'code_col' => $this->colCode,
                    'title_col' => $this->colTitle,
                    'evidence_items_col' => $this->colEvidence,
                    'evidence_link_col' => $this->colDriveLink,
                    'pic_col' => $this->colPic,
                ],
            ]);
            $profile->id = (string) Str::uuid();
        }

        return $profile;
    }

    protected function getParsedImportResult(): ?ParsedImportResult
    {
        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            return null;
        }

        $lk = $this->selectedLk;
        $profile = $this->resolveActiveProfile($lk);

        /** @var LkImportService $importService */
        $importService = app(LkImportService::class);

        return $importService->preview(
            filePath: $this->uploadedFilePath,
            lk: $lk ?? new LembarKerja(['period' => 2026, 'code' => 'DEFAULT']),
            profile: $profile,
            originalFilename: $this->uploadedFileName
        );
    }

    /**
     * @return array{total_rows: int, valid_indicators: int, valid_pct: float, skipped_rows: int, fatal_errors: int}
     */
    public function getValidationStats(): array
    {
        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            return [
                'total_rows' => 0,
                'valid_indicators' => 0,
                'valid_pct' => 0.0,
                'skipped_rows' => 0,
                'fatal_errors' => 0,
            ];
        }

        try {
            $parsed = $this->getParsedImportResult();
            if ($parsed === null) {
                return [
                    'total_rows' => 0,
                    'valid_indicators' => 0,
                    'valid_pct' => 0.0,
                    'skipped_rows' => 0,
                    'fatal_errors' => 0,
                ];
            }

            return [
                'total_rows' => $parsed->totalRowsRead,
                'valid_indicators' => count($parsed->indicators),
                'valid_pct' => $parsed->totalRowsRead > 0
                    ? round((count($parsed->indicators) / $parsed->totalRowsRead) * 100, 1)
                    : 0.0,
                'skipped_rows' => count($parsed->skippedRows),
                'fatal_errors' => 0,
            ];
        } catch (\Throwable) {
            return [
                'total_rows' => 0,
                'valid_indicators' => 0,
                'valid_pct' => 0.0,
                'skipped_rows' => 0,
                'fatal_errors' => 1,
            ];
        }
    }

    /**
     * @return array<int, array{code: string, row_num: int, title: string, items: array<string>, drive_link: string, pic: string}>
     */
    public function getPreviewSamples(): array
    {
        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            return [];
        }

        try {
            $parsed = $this->getParsedImportResult();
            if ($parsed === null) {
                return [];
            }

            $samples = [];
            foreach ($parsed->indicators as $ind) {
                $samples[] = [
                    'code' => $ind->code,
                    'row_num' => $ind->orderIndex,
                    'title' => $ind->title,
                    'items' => array_map(fn ($item) => $item->resolvedName, $ind->items),
                    'drive_link' => $ind->evidenceLink ?? '',
                    'pic' => $ind->pic ?? '-',
                ];
            }

            return $samples;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{row_range: string, description: string, badge: string}>
     */
    public function getAnomalyRows(): array
    {
        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            return [];
        }

        try {
            $parsed = $this->getParsedImportResult();
            if ($parsed === null) {
                return [];
            }

            $anomalies = [];
            foreach ($parsed->skippedRows as $skipped) {
                $anomalies[] = [
                    'row_range' => "Baris {$skipped->rowIndex}",
                    'description' => $skipped->reason,
                    'badge' => 'Dilewati',
                ];
            }

            return $anomalies;
        } catch (\Throwable) {
            return [];
        }
    }

    public function retestMapping(): void
    {
        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            Notification::make()
                ->title('Pilih berkas Excel dulu')
                ->body('Pilih berkas Excel dulu sebelum menguji pemetaan.')
                ->warning()
                ->send();

            return;
        }

        try {
            $parsed = $this->getParsedImportResult();
            $count = $parsed ? count($parsed->indicators) : 0;

            Notification::make()
                ->title('Uji Pemetaan Berhasil')
                ->body("Simulasi parsing berhasil. {$count} indikator valid terdeteksi.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Uji Pemetaan Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function executeImport(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! in_array($user->role, ['super_admin', 'admin'], true) || ! $user->is_active) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya Administrator yang memiliki wewenang mengeksekusi impor.')
                ->danger()
                ->send();

            return;
        }

        if (empty($this->uploadedFilePath) || ! file_exists($this->uploadedFilePath)) {
            Notification::make()
                ->title('Pilih berkas Excel dulu')
                ->body('Pilih berkas Excel dulu sebelum memulai impor.')
                ->warning()
                ->send();

            return;
        }

        $lk = $this->selectedLk;
        if (! $lk) {
            Notification::make()
                ->title('Target LK Belum Dipilih')
                ->body('Silakan pilih Lembar Kerja target terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $profile = $this->resolveActiveProfile($lk);

        if ($this->saveAsTemplate && ! empty($this->newTemplateName)) {
            $profile->name = trim($this->newTemplateName);
            $profile->save();
        }

        $importService = app(LkImportService::class);
        $log = $importService->commit(
            filePath: $this->uploadedFilePath,
            lk: $lk,
            profile: $profile,
            actor: $user,
            originalFilename: $this->uploadedFileName
        );

        $this->currentStep = 4;

        Notification::make()
            ->title('Impor Lembar Kerja Berhasil!')
            ->body("{$log->imported_indicators} indikator dan {$log->imported_items} butir bukti berhasil disinkronkan ke {$lk->name}. Verifikasi manual tetap utuh (LKIMP-07).")
            ->success()
            ->send();
    }

    public function setStep(int $step): void
    {
        if ($step >= 1 && $step <= 4) {
            $this->currentStep = $step;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }
}
