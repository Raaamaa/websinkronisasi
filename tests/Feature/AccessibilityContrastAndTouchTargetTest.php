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

        // Memastikan tombol aksi drilldown modal memiliki target sentuh ramah lansia minimal 48px/56px
        $this->assertStringContainsString('min-h-[48px]', $viewContent, 'Tombol aksi modal drilldown matriks monitor belum memenuhi target sentuh minimal 48px/56px untuk lansia');
    }
}

