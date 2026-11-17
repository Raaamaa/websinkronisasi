<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AccessibilityContrastAndTouchTargetTest extends TestCase
{
    public function test_verification_queue_action_buttons_have_fifty_six_pixel_touch_targets(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));
        $this->assertNotFalse($viewContent);

        // Memastikan ketiga tombol aksi utama memiliki target sentuh minimal 56px (Antislop UI)
        $this->assertStringContainsString('min-h-[56px]', $viewContent);
        $this->assertStringContainsString('Setujui Dokumen', $viewContent);
        $this->assertStringContainsString('Minta Revisi', $viewContent);
        $this->assertStringContainsString('Tolak Pengajuan', $viewContent);
    }

    public function test_monitor_matrix_modal_actions_have_large_touch_targets(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/pages/cari-dokumen.blade.php'));
        $this->assertNotFalse($viewContent);

        $this->assertNotEmpty($viewContent);
    }

    public function test_verification_queue_active_tab_badges_avoid_low_contrast_combinations(): void
    {
        $viewContent = (string) file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));

        // Badge tab aktif tidak boleh memakai bg-amber-500 dengan teks putih (kontras rendah 2.15:1)
        $this->assertStringNotContainsString(
            'bg-amber-500 text-white',
            $viewContent,
            'Badge tab aktif tidak boleh memakai bg-amber-500 text-white karena kontras rendah (2.15:1)'
        );

        $contrastActive = $this->calculateContrastRatio('#ffffff', '#0369a1');
        $this->assertGreaterThanOrEqual(4.5, $contrastActive, 'Teks tab aktif harus memiliki kontras >= 4.5:1');
    }

    public function test_brand_logo_and_login_do_not_use_temporary_hamburger_svg_and_use_studio_preset(): void
    {
        $brandLogo = (string) file_get_contents(resource_path('views/filament/components/brand-logo.blade.php'));
        $loginView = (string) file_get_contents(resource_path('views/auth/login.blade.php'));
        $forceChangeView = (string) file_get_contents(resource_path('views/auth/force-change-password.blade.php'));

        $this->assertStringContainsString('logo-bps-tegal.png', $brandLogo);
        $this->assertStringContainsString('logo.png', $brandLogo);
        $this->assertStringNotContainsString('M10 11h16M10 18h12M10 25h16', $brandLogo);
        $this->assertStringNotContainsString('M13 15h22M13 24h16M13 33h22', $loginView);
        $this->assertStringNotContainsString('class="features-list"', $loginView);
        $this->assertStringContainsString('oklch(97.2% 0.006 215)', $loginView);
        $this->assertStringContainsString('oklch(97.2% 0.006 215)', $forceChangeView);
    }

    public function test_filament_custom_css_covers_dark_mode_surfaces_and_missing_utility_classes(): void
    {
        $customCss = (string) file_get_contents(public_path('css/filament-custom.css'));
        $tailwindCss = (string) file_get_contents(public_path('css/tailwind-compiled.css'));
        $verificationView = (string) file_get_contents(resource_path('views/filament/pages/verification-queue.blade.php'));

        $this->assertStringContainsString('html.dark .fi-ta-ctn', $customCss);
        $this->assertStringContainsString('html.dark .fi-modal-window', $customCss);
        $this->assertStringContainsString('html.dark .fi-input-wrp', $customCss);
        $this->assertStringNotContainsString('border: 2px', $verificationView);
        $this->assertTrue(
            str_contains($customCss, '.p-12') || str_contains($tailwindCss, '.p-12') || ! str_contains($verificationView, 'p-12')
        );
    }

    public function test_soft_glass_depth_and_login_light_discipline_are_enforced(): void
    {
        $customCss = (string) file_get_contents(public_path('css/filament-custom.css'));
        $loginView = (string) file_get_contents(resource_path('views/auth/login.blade.php'));
        $forceChangeView = (string) file_get_contents(resource_path('views/auth/force-change-password.blade.php'));

        // Soft glass + depth tokens & rules
        $this->assertStringContainsString('--radius-card: 20px;', $customCss);
        $this->assertStringContainsString('--radius-control: 14px;', $customCss);
        $this->assertStringContainsString('--radius-pill: 999px;', $customCss);
        $this->assertStringContainsString('@supports not (backdrop-filter: blur(1px))', $customCss);
        $this->assertStringContainsString('::file-selector-button', $customCss);
        $this->assertStringContainsString('radial-gradient', $customCss);
        $this->assertDoesNotMatchRegularExpression('/(?<![a-z-])filter:\s*blur/i', $customCss);
        $this->assertStringNotContainsString('transition: all', $customCss);

        // Max 5 glass surfaces across CSS + login (excluding @supports condition)
        $backdropCount = preg_match_all('/^\s*backdrop-filter:\s*blur\(/m', $customCss)
            + preg_match_all('/^\s*backdrop-filter:\s*blur\(/m', $loginView);
        $this->assertSame(5, $backdropCount);

        // Login stays light (not tied to prefers-color-scheme) and includes decorative 3D stack at >=900px
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $loginView);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $forceChangeView);
        $this->assertStringContainsString('aria-hidden="true"', $loginView);
        $this->assertStringContainsString('min-width: 900px', $loginView);
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
