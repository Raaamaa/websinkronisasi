<?php

declare(strict_types=1);

namespace App\Services\LkImport;

use App\Models\LkImportProfile;
use App\Services\LkImport\Contracts\ParsingStrategyInterface;
use App\Services\LkImport\DTOs\ParsedImportResult;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use App\Services\LkImport\Strategies\ChecklistSheetStrategy;
use App\Services\LkImport\Strategies\NumberedListSingleCellStrategy;
use App\Services\LkImport\Strategies\SingleItemPerRowStrategy;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelParserService
{
    public function __construct(
        protected YearPlaceholderResolver $yearResolver
    ) {}

    /**
     * Validate file format, reject macros, and load PhpSpreadsheet instance.
     *
     * @throws ValidationException
     */
    public function validateAndLoad(string $path, string $originalFilename): Spreadsheet
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        // Strict rejection of macro-enabled files (AC-24, T21)
        if (in_array($extension, ['xlsm', 'xltm', 'xlam', 'xlsb'], true)) {
            throw ValidationException::withMessages([
                'file' => "File dengan format makro (.{$extension}) ditolak demi keamanan server.",
            ]);
        }

        if (file_exists($path)) {
            // Batas ukuran berkas impor maksimal 20 MB (T21)
            $maxSizeBytes = 20 * 1024 * 1024;
            if (filesize($path) > $maxSizeBytes) {
                throw ValidationException::withMessages([
                    'file' => 'Ukuran berkas Excel melebihi batas maksimal 20 MB.',
                ]);
            }

            // MIME sniffing untuk file makro
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $path) : null;
            if ($finfo) {
                finfo_close($finfo);
            }

            if ($mime === 'application/vnd.ms-excel.sheet.macroEnabled.12') {
                throw ValidationException::withMessages([
                    'file' => 'Berkas Excel dengan format makro (.xlsm) ditolak demi keamanan server.',
                ]);
            }

            // Deteksi komponen makro VBA tersembunyi pada struktur ZIP berkas Excel (vbaProject.bin)
            if (class_exists(\ZipArchive::class)) {
                $zip = new \ZipArchive;
                if ($zip->open($path) === true) {
                    if ($zip->locateName('xl/vbaProject.bin') !== false) {
                        $zip->close();
                        throw ValidationException::withMessages([
                            'file' => 'Berkas Excel terdeteksi memuat makro VBA tersembunyi (vbaProject.bin). Impor ditolak!',
                        ]);
                    }
                    $zip->close();
                }
            }
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(false); // Preserve hyperlinks

            return $reader->load($path);
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'file' => 'Gagal membaca berkas Excel: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Sanitize URL ensuring strict http/https scheme and rejection of javascript/file schemes (AC-25).
     */
    public function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Scheme must be strictly http or https
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        // Validate structure
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    /**
     * Resolve parsing strategy instance by profile strategy enum.
     */
    public function resolveStrategy(string $strategy): ParsingStrategyInterface
    {
        return match ($strategy) {
            'numbered_list_single_cell' => new NumberedListSingleCellStrategy,
            'checklist_sheet' => new ChecklistSheetStrategy,
            'single_item_per_row' => new SingleItemPerRowStrategy,
            default => throw new InvalidArgumentException("Strategi parsing tidak dikenal: {$strategy}"),
        };
    }

    /**
     * Parse excel file using given profile and period.
     */
    public function parse(
        string $path,
        LkImportProfile $profile,
        int|string $period,
        ?string $originalFilename = null
    ): ParsedImportResult {
        $spreadsheet = $this->validateAndLoad($path, $originalFilename ?? basename($path));
        $strategy = $this->resolveStrategy($profile->parsing_strategy);

        return $strategy->parse($spreadsheet, $profile, $period, $this->yearResolver, $this);
    }
}
