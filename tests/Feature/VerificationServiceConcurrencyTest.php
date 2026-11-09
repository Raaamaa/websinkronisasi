<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\VerificationService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationServiceConcurrencyTest extends TestCase
{
    protected VerificationService $service;

    protected User $admin;

    protected EvidenceDocument $doc;

    protected LkIndicator $indicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VerificationService;

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.verify.concurrency@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Concurrency Test',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'TEST-VERIF', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'LK Verification Concurrency Test',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'V-01'],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Indikator Uji Verifikasi Konkuren',
                'order_index' => 1,
            ]
        );

        $this->doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'original_filename' => 'dok_concurrency.pdf',
            'document_name' => 'Dokumen Uji Konkuren',
            'file_hash' => hash('sha256', Str::random(25)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $this->admin->id,
            'processing_status' => 'selesai',
            'year' => 2026,
        ]);
    }

    protected function tearDown(): void
    {
        DocumentMatch::where('document_id', $this->doc->id)->delete();
        $this->doc->forceDelete();
        $this->indicator->forceDelete();
        parent::tearDown();
    }

    public function test_confirm_match_on_rejected_relation_throws_domain_exception(): void
    {
        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $this->doc->id,
            'indicator_id' => $this->indicator->id,
            'status' => 'rejected',
            'match_method' => 'title_similarity',
            'confidence_score' => 70.0,
            'match_reasons' => ['test' => true],
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invarian #2');

        $this->service->confirmMatch($match->id, $this->admin);
    }

    public function test_confirm_match_is_idempotent_and_does_not_duplicate_audit_logs(): void
    {
        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $this->doc->id,
            'indicator_id' => $this->indicator->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 80.0,
            'match_reasons' => ['test' => true],
        ]);

        $initialAuditCount = AuditLog::where('entity_id', $match->id)->count();

        // Panggilan pertama: konfirmasi
        $res1 = $this->service->confirmMatch($match->id, $this->admin);
        $this->assertSame('confirmed', $res1->status);

        $afterFirstCount = AuditLog::where('entity_id', $match->id)->count();
        $this->assertSame($initialAuditCount + 1, $afterFirstCount);

        // Panggilan kedua: idempoten, tidak menambah log audit
        $res2 = $this->service->confirmMatch($match->id, $this->admin);
        $this->assertSame('confirmed', $res2->status);

        $afterSecondCount = AuditLog::where('entity_id', $match->id)->count();
        $this->assertSame($afterFirstCount, $afterSecondCount, 'Panggilan konfirmasi berulang harus idempoten.');
    }

    public function test_verification_methods_execute_with_pessimistic_row_locking(): void
    {
        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $this->doc->id,
            'indicator_id' => $this->indicator->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 80.0,
            'match_reasons' => ['test' => true],
        ]);

        DB::enableQueryLog();

        $this->service->confirmMatch($match->id, $this->admin);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $foundLock = false;
        foreach ($queries as $q) {
            if (str_contains(strtolower($q['query']), 'for update')) {
                $foundLock = true;
                break;
            }
        }

        $this->assertTrue($foundLock, 'Kueri verifikasi wajib memuat klausa FOR UPDATE (pessimistic locking).');
    }
}
