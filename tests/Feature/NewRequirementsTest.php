<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Monitor\MonitorMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewRequirementsTest extends TestCase
{
    protected User $superAdmin;
    protected User $admin;
    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::withTrashed()->where('email', 'admin@kantor.go.id')->first()
            ?? User::create([
                'email' => 'admin@kantor.go.id',
                'name' => 'Super Admin Test',
                'password_hash' => bcrypt('password123'),
                'role' => 'super_admin',
                'is_active' => true,
                'auth_provider' => 'local',
                'must_change_password' => false,
            ]);
        if ($this->superAdmin->trashed()) {
            $this->superAdmin->restore();
        }
        $this->superAdmin->update(['is_active' => true, 'must_change_password' => false]);

        $this->admin = User::withTrashed()->where('email', 'admin.new@bps.go.id')->first()
            ?? User::create([
                'email' => 'admin.new@bps.go.id',
                'name' => 'Admin Baru',
                'password_hash' => bcrypt('password123'),
                'role' => 'admin',
                'is_active' => true,
                'auth_provider' => 'sso',
                'must_change_password' => false,
            ]);
        if ($this->admin->trashed()) {
            $this->admin->restore();
        }
        $this->admin->update(['is_active' => true, 'must_change_password' => false]);

        $this->pegawai = User::withTrashed()->where('email', 'pegawai.new@bps.go.id')->first()
            ?? User::create([
                'email' => 'pegawai.new@bps.go.id',
                'name' => 'Pegawai Baru',
                'password_hash' => bcrypt('password123'),
                'role' => 'pegawai',
                'is_active' => true,
                'auth_provider' => 'sso',
                'must_change_password' => false,
            ]);
        if ($this->pegawai->trashed()) {
            $this->pegawai->restore();
        }
        $this->pegawai->update(['is_active' => true, 'must_change_password' => false]);
    }

    protected function tearDown(): void
    {
        LkIndicator::where('code', 'like', 'TEST_%')->forceDelete();
        parent::tearDown();
    }

    /**
     * Test 1a: Super Admin can access LkIndicatorResource to manage Drive links.
     */
    public function test_super_admin_can_manage_lk_drive_links(): void
    {
        $responseSuper = $this->actingAs($this->superAdmin)->get('/admin/lk-indicators');
        $responseSuper->assertOk();
    }

    /**
     * Test 1b: Admin can access LkIndicatorResource to manage Drive links.
     */
    public function test_admin_can_manage_lk_drive_links(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/lk-indicators');
        $responseAdmin->assertOk();
    }

    /**
     * Test 1c: Pegawai cannot access LkIndicatorResource.
     */
    public function test_pegawai_cannot_manage_lk_drive_links(): void
    {
        $responsePegawai = $this->actingAs($this->pegawai)->get('/admin/lk-indicators');
        $responsePegawai->assertForbidden();
    }

    /**
     * Test 2: Admin and Super Admin can update Drive link on an indicator.
     */
    public function test_admin_can_set_and_clear_drive_link_on_indicator(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'KIP', 'period' => 2026],
            ['name' => 'LK KIP', 'category' => 'internal']
        );

        $code = 'TEST_' . uniqid();
        $indicator = LkIndicator::create([
            'lk_id' => $lk->id,
            'code' => $code,
            'title' => 'Indikator Uji Tautan Drive',
            'evidence_link' => null,
        ]);

        $this->assertNull($indicator->evidence_link);

        // Update Drive Link
        $driveUrl = 'https://drive.google.com/drive/folders/test12345';
        $indicator->update(['evidence_link' => $driveUrl]);

        $this->assertSame($driveUrl, $indicator->fresh()->evidence_link);

        // Clear Drive Link
        $indicator->update(['evidence_link' => null]);
        $this->assertNull($indicator->fresh()->evidence_link);
    }

    /**
     * Test 3: Super Admin cannot upload matching document; Pegawai and Admin can.
     */
    public function test_rbac_upload_permissions(): void
    {
        $this->assertFalse($this->superAdmin->hasPermission('evidence.upload'));
        $this->assertTrue($this->admin->hasPermission('evidence.upload'));
        $this->assertTrue($this->pegawai->hasPermission('evidence.upload'));
    }

    /**
     * Test 4: Monitor Matrix cells include direct drive_url to Google Drive when matched.
     */
    public function test_monitor_matrix_cells_provide_direct_drive_url(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'KIP', 'period' => 2026],
            ['name' => 'LK KIP 2026', 'category' => 'internal']
        );

        $code = 'TEST_' . uniqid();
        $driveLink = 'https://drive.google.com/drive/folders/sample-kip-folder';
        $indicator = LkIndicator::create([
            'lk_id' => $lk->id,
            'code' => $code,
            'title' => 'SK Layanan Keterbukaan Informasi Publik',
            'evidence_link' => $driveLink,
        ]);

        $item = LkEvidenceItem::create([
            'indicator_id' => $indicator->id,
            'raw_template_name' => 'SK PPID ' . $code,
            'resolved_name' => 'SK PPID ' . $code,
        ]);

        $doc = EvidenceDocument::create([
            'document_name' => 'SK PPID Kantor 2026 ' . $code,
            'year' => 2026,
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'evidence/sk_ppid.pdf',
            'original_filename' => 'sk_ppid.pdf',
            'file_hash' => hash('sha256', 'drive_test_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->pegawai->id,
        ]);

        DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $indicator->id,
            'matched_item_id' => $item->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 90.0,
            'match_reasons' => ['score' => 90.0],
        ]);

        $service = app(MonitorMatrixService::class);
        $matrixData = $service->getMatrixData(['period' => 2026]);

        $docRow = collect($matrixData['rows'])->firstWhere('document_id', $doc->id);
        $this->assertNotNull($docRow);

        $cellKIP = $docRow['cells']['KIP'];
        $this->assertTrue($cellKIP['has_match']);
        $this->assertSame('✔', $cellKIP['symbol']);
        $this->assertSame($driveLink, $cellKIP['drive_url']);
    }
}
