<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AccessibilityContrastAndTouchTargetTest extends TestCase
{
    public function test_verification_queue_inspection_modal_has_fifty_six_pixel_touch_targets(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));
        $this->assertNotFalse($viewContent);

        // Memastikan tombol aksi konfirmasi & tolak di modal inspeksi memiliki target sentuh minimal 56px (Antislop UI)
        $this->assertStringContainsString('min-h-[56px]', $viewContent);
        $this->assertStringContainsString('Konfirmasi &amp; Pasang Centang (✔)', $viewContent);
        $this->assertStringContainsString('Tolak &amp; Kunci Permanen', $viewContent);
        $this->assertStringContainsString('Tutup / Batal', $viewContent);

        // Memastikan fokus ring tegas (WCAG keyboard accessibility)
        $this->assertStringContainsString('focus:ring-4', $viewContent);
    }

    public function test_monitor_matrix_modal_actions_have_large_touch_targets_and_focus_rings(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/pages/monitor-matrix.blade.php'));
        $this->assertNotFalse($viewContent);

        // Memastikan tombol aksi drilldown modal memiliki target sentuh ramah lansia minimal 56px (Antislop UI)
        $this->assertStringContainsString('min-h-[56px]', $viewContent, 'Tombol aksi modal drilldown matriks monitor belum memenuhi target sentuh minimal 56px untuk lansia');
    }

    public function test_verification_queue_status_tabs_have_valid_contrast_and_compiled_classes(): void
    {
        $viewContent = (string) file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));
        $compiledCss = (string) file_get_contents(public_path('css/tailwind-compiled.css'));
        $customCss = (string) file_get_contents(public_path('css/filament-custom.css'));
        $allCss = $compiledCss."\n".$customCss;

        // 1. Tab aktif perlu_verifikasi tidak boleh menggunakan bg-indigo-600 yang tidak ada di CSS
        $this->assertStringNotContainsString(
            'bg-indigo-600',
            $viewContent,
            'Tab aktif perlu_verifikasi tidak boleh memakai bg-indigo-600 karena tidak terkompilasi dan memicu teks putih di latar putih'
        );

        // 2. Ekstrak kelas tab aktif perlu_verifikasi dan pastikan kelas background terdefinisi di CSS
        preg_match('/wire:click="setTab\(\'perlu_verifikasi\'\)"\s+class="[^"]*min-h-\[44px\][^"]*\{\{\s*\$statusTab === \'perlu_verifikasi\'\s*\?\s*\'([^\']+)\'/s', $viewContent, $matches);
        $this->assertNotEmpty($matches, 'Blok statusTab perlu_verifikasi harus ditemukan di view');
        $activeClasses = explode(' ', $matches[1]);
        $bgClasses = array_values(array_filter($activeClasses, fn ($c) => str_starts_with($c, 'bg-')));
        $this->assertNotEmpty($bgClasses, 'Tab aktif harus memiliki kelas background');

        foreach ($bgClasses as $bgClass) {
            $hasBgInCss = str_contains($allCss, '.'.$bgClass);
            $this->assertTrue(
                $hasBgInCss,
                "Kelas background '{$bgClass}' untuk tab aktif tidak ditemukan di stylesheet compiled maupun custom"
            );
        }

        // 3. Verifikasi rasio kontras WCAG AA (minimal 4.5:1 untuk normal text)
        // Hitung kontras teks non-aktif (slate-600 #475569) terhadap latar terang (#ffffff)
        $contrastNonActive = $this->calculateContrastRatio('#475569', '#ffffff');
        $this->assertGreaterThanOrEqual(4.5, $contrastNonActive, 'Teks tab non-aktif harus memiliki kontras >= 4.5:1');

        // Hitung kontras teks aktif tab (Sky-700 #0369a1 dengan teks #ffffff)
        $contrastActive = $this->calculateContrastRatio('#ffffff', '#0369a1');
        $this->assertGreaterThanOrEqual(4.5, $contrastActive, 'Teks tab aktif harus memiliki kontras >= 4.5:1');
    }

    public function test_verification_queue_active_tab_badges_have_high_contrast_and_compiled_classes(): void
    {
        $viewContent = (string) file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));
        $compiledCss = (string) file_get_contents(public_path('css/tailwind-compiled.css'));
        $customCss = (string) file_get_contents(public_path('css/filament-custom.css'));
        $allCss = $compiledCss."\n".$customCss;

        // 1. Badge aktif otomatis tidak boleh memakai bg-amber-500 dengan teks putih (kontras rendah 2.15:1)
        $this->assertStringNotContainsString(
            'bg-amber-500 text-white',
            $viewContent,
            'Badge tab aktif otomatis tidak boleh memakai bg-amber-500 text-white karena kontras rendah (2.15:1)'
        );

        // 2. Ekstrak semua badge counter tab status (auto, perlu_verifikasi, confirmed, rejected)
        preg_match_all(
            '/<span class="px-2 py-0\.5 text-xs rounded-full \{\{\s*\$statusTab === \'([a-z_]+)\'\s*\?\s*\'([^\']+)\'/s',
            $viewContent,
            $matches,
            PREG_SET_ORDER
        );

        $this->assertCount(4, $matches, 'Keempat badge counter tab status harus ditemukan');

        foreach ($matches as $item) {
            $tabName = $item[1];
            $activeClassString = $item[2];

            // Pastikan menggunakan gaya kontras tinggi bg-slate-900
            $this->assertStringContainsString(
                'bg-slate-900',
                $activeClassString,
                "Badge tab aktif '{$tabName}' harus menggunakan bg-slate-900 untuk kontras tinggi ramah lansia"
            );

            // Pastikan kelas terdaftar di CSS terpakai
            $this->assertTrue(
                str_contains($allCss, '.bg-slate-900'),
                'Kelas bg-slate-900 harus terdaftar di stylesheet'
            );

            // Verifikasi rasio kontras teks putih terhadap bg-slate-900 (#0f172a) >= 4.5:1
            $contrast = $this->calculateContrastRatio('#ffffff', '#0f172a');
            $this->assertGreaterThanOrEqual(4.5, $contrast, "Kontras badge tab aktif '{$tabName}' harus >= 4.5:1");
        }
    }

    private function calculateContrastRatio(string $hex1, string $hex2): float
    {
        $l1 = $this->relativeLuminance($hex1);
        $l2 = $this->relativeLuminance($hex2);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4;
        $g = $g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4;
        $b = $b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
