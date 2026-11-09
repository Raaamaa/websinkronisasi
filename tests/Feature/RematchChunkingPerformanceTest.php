<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RematchActiveDocumentsJob;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Support\Str;
use Tests\TestCase;

class RematchChunkingPerformanceTest extends TestCase
{
    protected MatchingEngineService $engine;

    protected User $admin;

    protected LembarKerja $lk;

    protected LkIndicator $indicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(MatchingEngineService::class);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.rematch.chunk@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Chunk Test',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'TEST-CHUNK', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'LK Chunk Performance Test',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lk->id, 'code' => 'CHUNK-01'],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
                'order_index' => 1,
            ]
        );

        LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $this->indicator->id, 'resolved_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS'],
            [
                'id' => (string) Str::uuid(),
                'raw_template_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
                'order_index' => 1,
            ]
        );
    }

    protected function tearDown(): void
    {
        DocumentMatch::where('indicator_id', $this->indicator->id)->delete();
        $this->indicator->evidenceItems()->delete();
        $this->indicator->forceDelete();
        parent::tearDown();
    }

    public function test_rematch_lk_processes_only_completed_documents_using_chunking(): void
    {
        // 1. Dokumen 1: Status 'selesai' (Harus diproses)
        $docSelesai = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
            'original_filename' => 'chunk_selesai.pdf',
            'file_hash' => hash('sha256', Str::random(20)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $this->admin->id,
            'processing_status' => 'selesai',
            'year' => 2026,
        ]);

        // 2. Dokumen 2: Status 'memproses' (DILARANG diproses sebelum ekstraksi selesai)
        $docMemproses = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
            'original_filename' => 'chunk_memproses.pdf',
            'file_hash' => hash('sha256', Str::random(20)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $this->admin->id,
            'processing_status' => 'memproses',
            'year' => 2026,
        ]);

        // 3. Dokumen 3: Soft-deleted (DILARANG diproses)
        $docDeleted = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
            'original_filename' => 'chunk_deleted.pdf',
            'file_hash' => hash('sha256', Str::random(20)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $this->admin->id,
            'processing_status' => 'selesai',
            'year' => 2026,
        ]);
        $docDeleted->delete();

        // Jalankan rematch LK
        $matchedCount = $this->engine->rematchLk($this->lk->id);

        // Hanya dokumen selesai yang diproses
        $this->assertDatabaseHas('document_matches', [
            'document_id' => $docSelesai->id,
            'indicator_id' => $this->indicator->id,
        ]);

        $this->assertDatabaseMissing('document_matches', [
            'document_id' => $docMemproses->id,
            'indicator_id' => $this->indicator->id,
        ]);

        $this->assertDatabaseMissing('document_matches', [
            'document_id' => $docDeleted->id,
            'indicator_id' => $this->indicator->id,
        ]);

        // Bersihkan
        $docSelesai->forceDelete();
        $docMemproses->forceDelete();
        $docDeleted->forceDelete();
    }

    public function test_rematch_active_documents_job_chunking_handles_all_eligible_documents(): void
    {
        $doc1 = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Hasil Pengujian Chunking Kinerja BPS',
            'original_filename' => 'job_chunk1.pdf',
            'file_hash' => hash('sha256', Str::random(20)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $this->admin->id,
            'processing_status' => 'selesai',
            'year' => 2026,
        ]);

        $job = new RematchActiveDocumentsJob;
        $job->handle($this->engine);

        $this->assertDatabaseHas('document_matches', [
            'document_id' => $doc1->id,
            'indicator_id' => $this->indicator->id,
        ]);

        $doc1->forceDelete();
    }
}
