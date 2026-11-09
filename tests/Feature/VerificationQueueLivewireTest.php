<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\VerificationQueuePage;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VerificationQueueLivewireTest extends TestCase
{
    protected User $admin;

    protected User $pegawai;

    protected LembarKerja $lk;

    protected LkIndicator $indicator;

    protected LkEvidenceItem $item;

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

        $this->indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lk->id, 'code' => 'A11i'],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Pembentukan Tim Kerja Pembangunan ZI',
                'order_index' => 1,
                'pic' => 'Bagian Umum',
            ]
        );

        $this->item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $this->indicator->id, 'raw_template_name' => 'SK Tim Kerja Pembangunan ZI'],
            [
                'id' => (string) Str::uuid(),
                'resolved_name' => 'SK Tim Kerja Pembangunan ZI Tahun 2026',
                'order_index' => 1,
            ]
        );
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdDocumentIds)) {
            DocumentMatch::whereIn('document_id', $this->createdDocumentIds)->forceDelete();
            EvidenceDocument::whereIn('id', $this->createdDocumentIds)->forceDelete();
        }

        parent::tearDown();
    }

    private function createMatch(string $status = 'auto', float $score = 88.0): DocumentMatch
    {
        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Tim Kerja '.uniqid(),
            'document_number' => 'B-'.rand(100, 999).'/0200/2026',
            'year' => 2026,
            'file_path' => 'evidence/test_'.uniqid().'.pdf',
            'original_filename' => 'sk_tim.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'file_hash' => hash('sha256', 'hash_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->createdDocumentIds[] = $doc->id;

        return DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $this->indicator->id,
            'matched_item_id' => $this->item->id,
            'status' => $status,
            'match_method' => 'title_similarity',
            'confidence_score' => $score,
            'match_reasons' => [
                'matched_keywords' => ['Tim Kerja', 'Zona Integritas', '2026'],
            ],
        ]);
    }

    public function test_page_renders_kpi_stats_correctly(): void
    {
        $this->createMatch('auto');
        $this->createMatch('auto');
        $this->createMatch('confirmed');
        $this->createMatch('rejected');

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->assertSee('Antrean Verifikasi Hasil Pencocokan')
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Terkonfirmasi Permanen')
            ->assertSee('Ditolak (Terkunci)')
            ->assertSet('statusTab', 'auto');
    }

    public function test_bulk_confirm_selected_matches(): void
    {
        $match1 = $this->createMatch('auto');
        $match2 = $this->createMatch('auto');

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->set('selectedMatches', [$match1->id, $match2->id])
            ->call('confirmSelected')
            ->assertSet('selectedMatches', []);

        $this->assertEquals('confirmed', $match1->fresh()->status);
        $this->assertEquals('confirmed', $match2->fresh()->status);
    }

    public function test_bulk_reject_selected_matches_adheres_to_match_06(): void
    {
        $match1 = $this->createMatch('auto');
        $match2 = $this->createMatch('auto');

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->set('selectedMatches', [$match1->id, $match2->id])
            ->call('rejectSelected')
            ->assertSet('selectedMatches', []);

        $this->assertEquals('rejected', $match1->fresh()->status);
        $this->assertEquals('rejected', $match2->fresh()->status);
    }

    public function test_inspection_modal_opens_and_confirms_single_match(): void
    {
        $match = $this->createMatch('auto', 92.5);

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->call('inspectMatch', $match->id)
            ->assertSet('inspectMatchId', $match->id)
            ->assertSee('Analisis Bukti Kecocokan')
            ->assertSee('AUDIT SESI MATCH-06')
            ->call('confirmInspectedMatch')
            ->assertSet('inspectMatchId', null);

        $this->assertEquals('confirmed', $match->fresh()->status);
    }

    public function test_pegawai_cannot_render_verification_queue(): void
    {
        $this->actingAs($this->pegawai);

        $this->get('/admin/verification-queue')
            ->assertStatus(403);
    }

    public function test_dynamic_similarity_threshold_updates_badge_and_tab_labels(): void
    {
        $this->actingAs($this->admin);

        SystemSetting::updateOrCreate(
            ['key' => 'matching.similarity_threshold'],
            ['value' => 80.0, 'description' => 'Ambang batas skor minimal']
        );

        try {
            Livewire::test(VerificationQueuePage::class)
                ->assertSee('80%')
                ->assertSee('Otomatis (&ge; 80%)', false)
                ->assertDontSee('&ge; 75%', false);
        } finally {
            SystemSetting::updateOrCreate(
                ['key' => 'matching.similarity_threshold'],
                ['value' => 75.0, 'description' => 'Ambang batas skor minimal']
            );
        }
    }
}
