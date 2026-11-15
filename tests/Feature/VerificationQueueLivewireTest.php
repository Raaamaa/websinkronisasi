<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\VerificationQueuePage;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VerificationQueueLivewireTest extends TestCase
{
    protected User $admin;

    protected User $pegawai;

    protected LembarKerja $lk;

    /** @var list<string> */
    protected array $createdDocumentIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.test.queue@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Test Queue',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.test.queue@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pegawai Test Queue',
                'role' => 'pegawai',
                'is_active' => true,
            ]
        );

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Zona Integritas 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdDocumentIds)) {
            EvidenceDocument::whereIn('id', $this->createdDocumentIds)->forceDelete();
        }

        parent::tearDown();
    }

    private function createPendingDocument(): EvidenceDocument
    {
        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Tim Kerja '.uniqid(),
            'document_number' => 'B-'.rand(100, 999).'/0200/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'file_path' => 'evidence/test_'.uniqid().'.pdf',
            'original_filename' => 'sk_tim.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'file_hash' => hash('sha256', 'hash_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->pegawai->id,
        ]);

        $this->createdDocumentIds[] = $doc->id;

        return $doc;
    }

    public function test_page_renders_document_cards_and_stats(): void
    {
        $doc1 = $this->createPendingDocument();

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->assertSee('Antrean Verifikasi Bukti Dukung')
            ->assertSee($doc1->document_name)
            ->assertSee('Menunggu')
            ->assertSee('Perlu Revisi')
            ->assertSee('Ditolak')
            ->assertSet('statusFilter', 'menunggu');
    }

    public function test_admin_can_approve_document_with_lk(): void
    {
        $doc = $this->createPendingDocument();

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->call('approveDocument', $doc->id, [$this->lk->id]);

        $this->assertEquals('disetujui', $doc->fresh()->approval_status);
        $this->assertEquals($this->admin->id, $doc->fresh()->approved_by);
        $this->assertNotNull($doc->fresh()->approved_at);
    }

    public function test_admin_can_request_revision_with_notes(): void
    {
        $doc = $this->createPendingDocument();

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->call('requestRevision', $doc->id, 'Lampiran belum ditandatangani');

        $this->assertEquals('revisi', $doc->fresh()->approval_status);
        $this->assertEquals('Lampiran belum ditandatangani', $doc->fresh()->approval_notes);
    }

    public function test_admin_can_reject_document_with_notes(): void
    {
        $doc = $this->createPendingDocument();

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->call('rejectDocument', $doc->id, 'Dokumen tidak valid');

        $this->assertEquals('ditolak', $doc->fresh()->approval_status);
        $this->assertEquals('Dokumen tidak valid', $doc->fresh()->approval_notes);
    }

    public function test_pegawai_cannot_render_verification_queue(): void
    {
        $this->actingAs($this->pegawai);

        $this->get('/admin/verification-queue')
            ->assertStatus(403);
    }
}
