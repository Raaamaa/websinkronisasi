<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Monitor\MonitorMatrixService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitorMatrixEagerLoadingTest extends TestCase
{
    protected array $createdDocIds = [];

    protected function tearDown(): void
    {
        if (! empty($this->createdDocIds)) {
            EvidenceDocument::whereIn('id', $this->createdDocIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_matrix_data_eager_loads_matched_item_eliminating_n_plus_one_queries(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'user.eagerloading@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'User Eager Loading Test',
                'role' => 'admin',
                'is_active' => true,
                'password' => bcrypt('password'),
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        for ($i = 1; $i <= 15; $i++) {
            $doc = EvidenceDocument::create([
                'id' => (string) Str::uuid(),
                'document_name' => "Dokumen Eager Loading Ke-{$i}",
                'year' => 2026,
                'approval_status' => 'disetujui',
                'file_path' => "evidence/test_eager_{$i}.pdf",
                'original_filename' => "doc_eager_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1024,
                'file_hash' => hash('sha256', "hash_eager_{$i}_".uniqid()),
                'processing_status' => 'selesai',
                'uploaded_by' => $user->id,
            ]);

            $this->createdDocIds[] = $doc->id;

            DocumentLkLink::create([
                'document_id' => $doc->id,
                'lk_id' => $lk->id,
                'link_source' => 'admin',
            ]);
        }

        $service = app(MonitorMatrixService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $matrixData = $service->getMatrixData(['period' => 2026, 'per_page' => 15, 'search' => 'Dokumen Eager Loading']);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($matrixData['rows']);

        $this->assertLessThanOrEqual(
            10,
            count($queries),
            'Total query melebihi ambang batas efisiensi monitor: '.count($queries).' query.'
        );
    }
}
