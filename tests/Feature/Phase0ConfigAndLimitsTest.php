<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase0ConfigAndLimitsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_evidence_config_loads_expected_defaults(): void
    {
        $this->assertSame(['max_file_size_mb', 'storage_warning_percent'], array_keys(config('evidence')));
        $this->assertSame(20, config('evidence.max_file_size_mb'));
        $this->assertSame(80, config('evidence.storage_warning_percent'));
    }

    public function test_livewire_config_is_fully_published_with_20mb_rules_and_preserved_subkeys(): void
    {
        $this->assertFileExists(config_path('livewire.php'));

        $rules = config('livewire.temporary_file_upload.rules');
        $this->assertIsArray($rules);
        $this->assertContains('max:20480', $rules);

        $previewMimes = config('livewire.temporary_file_upload.preview_mimes');
        $this->assertIsArray($previewMimes);
        $this->assertNotEmpty($previewMimes);
        $this->assertSame(5, config('livewire.temporary_file_upload.max_upload_time'));
        $this->assertTrue((bool) config('livewire.temporary_file_upload.cleanup'));
    }

    public function test_evidence_upload_service_reads_limit_from_config_not_system_setting(): void
    {
        SystemSetting::updateOrCreate(['key' => 'upload.max_file_size_mb'], ['value' => 1]);
        Config::set('evidence.max_file_size_mb', 20);

        $tmpPath = tempnam(sys_get_temp_dir(), 'phase0_pdf_');
        $this->assertNotFalse($tmpPath);
        file_put_contents($tmpPath, "%PDF-1.4\n".str_repeat('A', 2 * 1024 * 1024));

        try {
            $uploaded = new UploadedFile($tmpPath, 'sample_2mb.pdf', 'application/pdf', null, true);
            $service = new EvidenceUploadService;

            $result = $service->inspectAndValidate($uploaded);
            $this->assertSame('application/pdf', $result['mime']);

            Config::set('evidence.max_file_size_mb', 1);
            SystemSetting::updateOrCreate(['key' => 'upload.max_file_size_mb'], ['value' => 20]);

            $this->expectException(ValidationException::class);
            $service->inspectAndValidate($uploaded);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_infra_upload_configs_and_php_settings_are_updated_to_20mb_plus_overhead(): void
    {
        $nginxConf = file_get_contents(base_path('deploy/nginx/websinkronisasi.conf'));
        $this->assertNotFalse($nginxConf);
        $this->assertStringContainsString('client_max_body_size 25M;', $nginxConf);

        $uploadsIniPath = base_path('docker/app/uploads.ini');
        $this->assertFileExists($uploadsIniPath);
        $uploadsIni = file_get_contents($uploadsIniPath);
        $this->assertNotFalse($uploadsIni);
        $this->assertStringContainsString('upload_max_filesize = 25M', $uploadsIni);
        $this->assertStringContainsString('post_max_size = 30M', $uploadsIni);

        $contents = file_get_contents(app_path('Services/Evidence/EvidenceUploadService.php'));
        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString('SystemSetting::', $contents);
    }
}
