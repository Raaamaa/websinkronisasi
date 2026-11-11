<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Matching\Rules\DeterministicMatchingRule;
use App\Services\Matching\Rules\HeuristicMatchingRule;
use App\Services\Matching\Similarity\TrigramSimilarity;
use App\Services\Matching\TextPreprocessor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase0ConfigAndLimitsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_evidence_config_loads_expected_defaults(): void
    {
        $this->assertSame(50, config('evidence.max_file_size_mb'));
        $this->assertSame(80, config('evidence.storage_warning_percent'));
        $this->assertSame(75.0, (float) config('evidence.matching_suggest_threshold'));
        $this->assertSame(60.0, (float) config('evidence.matching_review_threshold'));
        $this->assertSame(5, config('evidence.drive_sync_max_depth'));
        $this->assertFalse((bool) config('evidence.drive_sync_schedule_enabled'));
    }

    public function test_livewire_config_is_fully_published_with_50mb_rules_and_preserved_subkeys(): void
    {
        $this->assertFileExists(config_path('livewire.php'));

        $rules = config('livewire.temporary_file_upload.rules');
        $this->assertIsArray($rules);
        $this->assertContains('max:51200', $rules);

        $previewMimes = config('livewire.temporary_file_upload.preview_mimes');
        $this->assertIsArray($previewMimes);
        $this->assertNotEmpty($previewMimes);
        $this->assertSame(5, config('livewire.temporary_file_upload.max_upload_time'));
        $this->assertTrue((bool) config('livewire.temporary_file_upload.cleanup'));
    }

    public function test_evidence_upload_service_reads_limit_from_config_not_system_setting(): void
    {
        SystemSetting::updateOrCreate(['key' => 'upload.max_file_size_mb'], ['value' => 1]);
        Config::set('evidence.max_file_size_mb', 50);

        $tmpPath = tempnam(sys_get_temp_dir(), 'phase0_pdf_');
        $this->assertNotFalse($tmpPath);
        file_put_contents($tmpPath, "%PDF-1.4\n".str_repeat('A', 2 * 1024 * 1024));

        try {
            $uploaded = new UploadedFile($tmpPath, 'sample_2mb.pdf', 'application/pdf', null, true);
            $service = new EvidenceUploadService;

            $result = $service->inspectAndValidate($uploaded);
            $this->assertSame('application/pdf', $result['mime']);

            Config::set('evidence.max_file_size_mb', 1);
            SystemSetting::updateOrCreate(['key' => 'upload.max_file_size_mb'], ['value' => 50]);

            $this->expectException(ValidationException::class);
            $service->inspectAndValidate($uploaded);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_matching_rules_read_thresholds_from_config_not_system_setting(): void
    {
        SystemSetting::updateOrCreate(['key' => 'matching.review_threshold'], ['value' => 99.0]);
        SystemSetting::updateOrCreate(['key' => 'matching.similarity_threshold'], ['value' => 99.0]);

        Config::set('evidence.matching_review_threshold', 65.0);
        Config::set('evidence.matching_suggest_threshold', 75.0);

        $deterministicRule = new DeterministicMatchingRule(new TextPreprocessor);
        $indicator = new LkIndicator([
            'code' => 'ZI-1.1',
            'title' => 'Pembangunan Zona Integritas Menuju WBK',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect());

        $doc = new EvidenceDocument([
            'document_name' => 'Laporan Monitoring Zona ZI-1.1',
            'original_filename' => 'laporan_zona_ZI-1.1.pdf',
        ]);
        $doc->id = (string) Str::uuid();

        $candidate = $deterministicRule->evaluateExactCode($doc, $indicator);
        $this->assertNotNull($candidate);
        $this->assertSame(65.0, $candidate->confidenceScore);

        Config::set('evidence.matching_review_threshold', 60.0);
        $lk = new LembarKerja(['period' => 2026]);
        $heuristicDoc = new EvidenceDocument([
            'document_name' => 'SK Penetapan Pengelola Informasi dan Dokumentasi',
            'year' => 2026,
        ]);
        $heuristicDoc->id = (string) Str::uuid();

        $heuristicInd = new LkIndicator([
            'code' => 'KIP-TEST-REV',
            'title' => 'Penetapan Struktur PPID dan Penetapan Daftar Informasi Publik',
            'order_index' => 1,
        ]);
        $heuristicInd->id = (string) Str::uuid();
        $heuristicInd->setRelation('lembarKerja', $lk);
        $heuristicInd->setRelation('evidenceItems', collect());

        $heuristicRule = new HeuristicMatchingRule(new TextPreprocessor, new TrigramSimilarity);
        $heuristicCandidate = $heuristicRule->evaluate($heuristicDoc, $heuristicInd);
        $this->assertNotNull($heuristicCandidate);
        $this->assertSame('perlu_verifikasi', $heuristicCandidate->status);
    }

    public function test_infra_upload_configs_and_php_settings_are_updated_to_50mb_plus_overhead(): void
    {
        $nginxConf = file_get_contents(base_path('deploy/nginx/websinkronisasi.conf'));
        $this->assertNotFalse($nginxConf);
        $this->assertStringContainsString('client_max_body_size 60M;', $nginxConf);
        $this->assertStringNotContainsString('client_max_body_size 25M;', $nginxConf);

        $uploadsIniPath = base_path('docker/app/uploads.ini');
        $this->assertFileExists($uploadsIniPath);
        $uploadsIni = file_get_contents($uploadsIniPath);
        $this->assertNotFalse($uploadsIni);
        $this->assertStringContainsString('upload_max_filesize = 55M', $uploadsIni);
        $this->assertStringContainsString('post_max_size = 60M', $uploadsIni);

        foreach ([
            app_path('Services/Evidence/EvidenceUploadService.php'),
            app_path('Services/Matching/Rules/DeterministicMatchingRule.php'),
            app_path('Services/Matching/Rules/HeuristicMatchingRule.php'),
        ] as $serviceFile) {
            $contents = file_get_contents($serviceFile);
            $this->assertNotFalse($contents);
            $this->assertStringNotContainsString('SystemSetting::', $contents);
        }
    }
}

