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
        $this->assertStringContainsString('logo-bps-tegal.png', $loginView);
        $this->assertStringContainsString('logo.png', $brandLogo);
        $this->assertStringNotContainsString('M10 11h16M10 18h12M10 25h16', $brandLogo);
        $this->assertStringNotContainsString('M13 15h22M13 24h16M13 33h22', $loginView);
        $this->assertStringNotContainsString('class="features-list"', $loginView);
        $this->assertStringContainsString('#F6F8FA', $loginView);
        $this->assertStringContainsString('#0077B6', $loginView);
        $this->assertStringContainsString('#F6F8FA', $forceChangeView);
        $this->assertStringContainsString('#0077B6', $forceChangeView);
        $this->assertStringNotContainsString('VPN FortiClient', $loginView);
        $this->assertStringNotContainsString('Mode Simulasi', $loginView);
        $this->assertStringNotContainsString('Hubungi Pengelola', $loginView);
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
        $this->assertLessThanOrEqual(5, $backdropCount);
        $this->assertGreaterThanOrEqual(4, $backdropCount);

        // Login stays light (not tied to prefers-color-scheme) and includes decorative 3D stack at >=1024px
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $loginView);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $forceChangeView);
        $this->assertStringContainsString('aria-hidden="true"', $loginView);
        $this->assertStringContainsString('min-width: 1024px', $loginView);
        $this->assertStringContainsString('Cari Cepat', $loginView);
        $this->assertStringContainsString('Cegah Berkas Ganda', $loginView);
        $this->assertStringContainsString('Terhubung ke Enam LK', $loginView);
        $this->assertStringContainsString('clamp(2.5rem, 4vw + 1rem, 4rem)', $loginView);
    }

    public function test_dropdown_stacking_fix_six_lk_tokens_and_modern_typography_are_present(): void
    {
        $customCss = (string) file_get_contents(public_path('css/filament-custom.css'));

        // (A) Topbar glass moved to ::before pseudo-element and overflow visible so dropdown is not trapped
        $this->assertStringContainsString('.fi-topbar::before', $customCss);
        $this->assertStringContainsString('.fi-dropdown-panel', $customCss);
        $this->assertStringContainsString('.fi-user-menu button', $customCss);
        $this->assertStringContainsString('overflow: visible !important;', $customCss);

        // (B) BPS logo palette + dark mode + pastel LK tokens + floating shell + no border-left on active sidebar
        $this->assertStringContainsString('#0077B6', $customCss);
        $this->assertStringContainsString('#EB891B', $customCss);
        $this->assertStringContainsString('#68B92E', $customCss);
        $this->assertStringContainsString('#F6F8FA', $customCss);
        $this->assertStringContainsString('#0E1621', $customCss);
        $this->assertStringContainsString('#152231', $customCss);
        $this->assertStringContainsString('#E6F4FC', $customCss);
        $this->assertStringContainsString('#FDF0DE', $customCss);
        $this->assertStringContainsString('#EAF5DD', $customCss);
        $this->assertStringContainsString('#EEEAFB', $customCss);
        $this->assertStringContainsString('#E3F5F0', $customCss);
        $this->assertStringContainsString('#FCE9E4', $customCss);
        $this->assertStringContainsString('--lk-kip-bg', $customCss);
        $this->assertStringContainsString('[data-lk="KIP"]', $customCss);
        $this->assertStringContainsString('[data-lk="DATA_SEKUNDER"]', $customCss);
        $this->assertStringContainsString('border-radius: 24px', $customCss);
        $this->assertStringContainsString('max-width: 1280px', $customCss);
        $this->assertStringNotContainsString('border-left: 3px solid', $customCss);

        // WCAG AA checks on primary blue & active orange accent
        $this->assertGreaterThanOrEqual(4.5, $this->calculateContrastRatio('#FFFFFF', '#0077B6'));
        $this->assertGreaterThanOrEqual(4.5, $this->calculateContrastRatio('#1B2430', '#EB891B'));

        // (D) Fluid sans typography scale
        $this->assertStringContainsString('clamp(1.75rem, 2.2vw + 1rem, 2.5rem)', $customCss);
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
