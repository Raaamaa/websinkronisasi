<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\VerificationQueuePage;
use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\VerificationService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VerificationServiceUnrejectTest extends TestCase
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
            ['email' => 'admin.unreject.test@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Unreject Test',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'TEST-UNREJ', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'LK Unreject Test',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'UNREJ-01'],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Indikator Uji Unreject Match',
                'order_index' => 1,
            ]
        );

        $this->doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'original_filename' => 'dok_unreject.pdf',
            'document_name' => 'Dokumen Uji Unreject',
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

    public function test_admin_can_unreject_match_restoring_to_verification_queue(): void
    {
        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $this->doc->id,
            'indicator_id' => $this->indicator->id,
            'status' => 'rejected',
            'match_method' => 'title_similarity',
            'confidence_score' => 68.0,
            'match_reasons' => ['test' => true],
        ]);

        $unrejected = $this->service->unrejectMatch($match->id, $this->admin);

        $this->assertSame('perlu_verifikasi', $unrejected->status);

        $log = AuditLog::where('entity_id', $match->id)
            ->where('action', 'match.unrejected')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('rejected', $log->old_values['status']);
        $this->assertSame('perlu_verifikasi', $log->new_values['status']);
        $this->assertSame($this->admin->id, $log->user_id);
    }

    public function test_livewire_verification_queue_can_execute_unreject_action(): void
    {
        $this->actingAs($this->admin);

        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $this->doc->id,
            'indicator_id' => $this->indicator->id,
            'status' => 'rejected',
            'match_method' => 'title_similarity',
            'confidence_score' => 78.0,
            'match_reasons' => ['test' => true],
        ]);

        Livewire::test(VerificationQueuePage::class)
            ->call('unrejectSingleMatch', $match->id)
            ->assertNotified();

        $match->refresh();
        $this->assertNotSame('rejected', $match->status);
    }
}
