<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CariDokumenPage;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CariDokumenLivewireTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_cari_dok@bps.go.id'],
            [
                'name' => 'Super Admin Cari Dokumen',
                'role' => 'super_admin',
                'nip' => '198001012005011001',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_cari_dok@bps.go.id'],
            [
                'name' => 'Admin Cari Dokumen',
                'role' => 'admin',
                'nip' => '198501012010011002',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['must_change_password' => false, 'is_active' => true]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai_cari_dok@bps.go.id'],
            [
                'name' => 'Pegawai Cari Dokumen',
                'role' => 'pegawai',
                'nip' => '199001012015011003',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->pegawai->update(['must_change_password' => false, 'is_active' => true]);
    }

    public function test_super_admin_is_blocked_from_cari_dokumen_at_server(): void
    {
        // Invarian #1: Server-side authorization check (Md/RBAC.md:83, PRD:90)
        $response = $this->actingAs($this->superAdmin)->get(CariDokumenPage::getUrl());
        $response->assertForbidden();

        $this->assertFalse(CariDokumenPage::canAccess());
    }

    public function test_admin_can_access_cari_dokumen_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(CariDokumenPage::getUrl());
        $responseAdmin->assertOk();
    }

    public function test_pegawai_can_access_cari_dokumen_page(): void
    {
        $responsePegawai = $this->actingAs($this->pegawai)->get(CariDokumenPage::getUrl());
        $responsePegawai->assertOk();
    }

    public function test_inactive_user_is_blocked_from_cari_dokumen(): void
    {
        $inactivePegawai = User::firstOrCreate(
            ['email' => 'inactive_pegawai_cd@bps.go.id'],
            [
                'name' => 'Pegawai Nonaktif',
                'role' => 'pegawai',
                'nip' => '199201012018011009',
                'password_hash' => bcrypt('password'),
                'is_active' => false,
                'must_change_password' => false,
            ]
        );
        $inactivePegawai->update(['is_active' => false]);

        $response = $this->actingAs($inactivePegawai)->get(CariDokumenPage::getUrl());
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302], true));
    }

    public function test_cari_dokumen_searches_and_renders_approved_documents(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI-CD', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $approvedDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Sah Cari Livewire '.Str::random(6),
            'document_number' => 'B-CDL-01/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_cdl.pdf',
            'original_filename' => 'test_cdl.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $approvedDoc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        $pendingDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Belum Disetujui Livewire '.Str::random(6),
            'document_number' => 'B-CDL-02/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_cdl_p.pdf',
            'original_filename' => 'test_cdl_p.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', $approvedDoc->document_name)
            ->call('search')
            ->assertSee($approvedDoc->document_name)
            ->assertSee($lk->code)
            ->assertDontSee($pendingDoc->document_name);
    }

    public function test_super_admin_cannot_initialize_livewire_component(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CariDokumenPage::class)
            ->assertForbidden();
    }

    public function test_cari_dokumen_view_has_elderly_friendly_touch_targets_and_labels(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->assertSeeHtml('min-h-[56px]')
            ->assertSee('Cari Dokumen Bukti Dukung');
    }
}
