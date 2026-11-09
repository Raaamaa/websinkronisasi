<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
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
            DocumentMatch::whereIn('document_id', $this->createdDocIds)->forceDelete();
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

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-EAGER-01'],
            [
                'title' => 'Indikator Utama ZI Eager Load',
                'order_index' => 999,
            ]
        );

        $item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'raw_template_name' => 'SK Tim Kerja Eager Load'],
            [
                'resolved_name' => 'SK Tim Kerja Eager Load 2026',
                'order_index' => 1,
            ]
        );

        for ($i = 1; $i <= 15; $i++) {
            $doc = EvidenceDocument::create([
                'id' => (string) Str::uuid(),
                'document_name' => "Dokumen Eager Loading Ke-{$i}",
                'year' => 2026,
                'file_path' => "evidence/test_eager_{$i}.pdf",
                'original_filename' => "doc_eager_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1024,
                'file_hash' => hash('sha256', "hash_eager_{$i}_".uniqid()),
                'processing_status' => 'selesai',
                'uploaded_by' => $user->id,
            ]);

            $this->createdDocIds[] = $doc->id;

            DocumentMatch::create([
                'id' => (string) Str::uuid(),
                'document_id' => $doc->id,
                'indicator_id' => $indicator->id,
                'matched_item_id' => $item->id,
                'status' => 'auto',
                'confidence_score' => 85.0,
                'match_method' => 'title_similarity',
            ]);
        }

        $service = app(MonitorMatrixService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $matrixData = $service->getMatrixData(['period' => 2026, 'per_page' => 15, 'search' => 'Dokumen Eager Loading']);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($matrixData['rows']);

        $evidenceItemQueries = array_filter(
            $queries,
            fn (array $query): bool => str_contains((string) $query['query'], 'lk_evidence_items')
        );

        $this->assertLessThanOrEqual(
            1,
            count($evidenceItemQueries),
            'Terjadi N+1 query ke lk_evidence_items! Ditemukan '.count($evidenceItemQueries).' query.'
        );

        $this->assertLessThanOrEqual(
            10,
            count($queries),
            'Total query melebihi ambang batas efisiensi monitor: '.count($queries).' query.'
        );
    }
}
