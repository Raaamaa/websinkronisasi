<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CariDokumenPage;
use App\Filament\Pages\VerificationQueuePage;
use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Auth\UserManagerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase2PivotRbacTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser(string $role, bool $active = true): User
    {
        return User::create([
            'name' => Str::headline($role).' '.Str::random(4),
            'email' => Str::lower($role).'.'.Str::random(6).'@bps.go.id',
            'role' => $role,
            'is_active' => $active,
            'must_change_password' => false,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_new_rbac_permission_matrix_across_roles_and_inactive_users(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $admin = $this->createUser('admin');
        $pegawai = $this->createUser('pegawai');
        $inactiveSuperAdmin = $this->createUser('super_admin', false);
        $deletedSuperAdmin = $this->createUser('super_admin');
        $deletedSuperAdmin->delete();

        // Super Admin: evidence.verify true; evidence.upload, evidence.search, drive.sync false
        $this->assertTrue($superAdmin->hasPermission('evidence.verify'));
        $this->assertTrue($superAdmin->hasPermission('evidence.delete'));
        $this->assertTrue($superAdmin->hasPermission('lk.manage'));
        $this->assertTrue($superAdmin->hasPermission('audit.view'));
        $this->assertTrue($superAdmin->hasPermission('settings.manage'));
        $this->assertTrue($superAdmin->hasPermission('monitor.view'));
        $this->assertFalse($superAdmin->hasPermission('evidence.upload'));
        $this->assertFalse($superAdmin->hasPermission('evidence.search'));
        $this->assertFalse($superAdmin->hasPermission('drive.sync'));
        $this->assertFalse($superAdmin->hasPermission('match'.'.verify'));

        // Admin: operational permissions + drive.sync + evidence.manage_markup + lk.manage_drive_links
        $this->assertTrue($admin->hasPermission('evidence.verify'));
        $this->assertTrue($admin->hasPermission('evidence.manage_markup'));
        $this->assertTrue($admin->hasPermission('lk.manage_drive_links'));
        $this->assertTrue($admin->hasPermission('drive.sync'));
        $this->assertTrue($admin->hasPermission('evidence.upload'));
        $this->assertTrue($admin->hasPermission('evidence.search'));
        $this->assertTrue($admin->hasPermission('monitor.view'));
        $this->assertFalse($admin->hasPermission('match'.'.verify'));
        $this->assertFalse($admin->hasPermission('lk.manage'));
        $this->assertFalse($admin->hasPermission('audit.view'));
        $this->assertFalse($admin->hasPermission('evidence.delete'));

        // Pegawai: upload, search, view_link, monitor.view, evidence.revise_own
        $this->assertTrue($pegawai->hasPermission('evidence.revise_own'));
        $this->assertTrue($pegawai->hasPermission('evidence.upload'));
        $this->assertTrue($pegawai->hasPermission('evidence.search'));
        $this->assertTrue($pegawai->hasPermission('evidence.view_link'));
        $this->assertTrue($pegawai->hasPermission('monitor.view'));
        $this->assertFalse($pegawai->hasPermission('evidence.verify'));
        $this->assertFalse($pegawai->hasPermission('evidence.manage_markup'));
        $this->assertFalse($pegawai->hasPermission('lk.manage_drive_links'));
        $this->assertFalse($pegawai->hasPermission('drive.sync'));
        $this->assertFalse($pegawai->hasPermission('match'.'.verify'));

        // Inactive & soft-deleted users never have permissions
        $this->assertFalse($inactiveSuperAdmin->hasPermission('evidence.verify'));
        $this->assertFalse($deletedSuperAdmin->hasPermission('evidence.verify'));
    }

    public function test_super_admin_access_matrix_enforced_server_side(): void
    {
        $superAdmin = $this->createUser('super_admin');

        $this->actingAs($superAdmin)->get('/admin/verification-queue')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/monitor-matrix')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/users')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/lembar-kerjas')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/lk-import-profiles')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/lk-indicators')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/evidence-documents')->assertOk();

        // Super Admin dilarang unggah & cari dokumen (bukan hanya disembunyikan di UI)
        $this->actingAs($superAdmin);
        $this->assertFalse(CariDokumenPage::canAccess());
        $this->assertTrue(VerificationQueuePage::canAccess());
        $this->actingAs($superAdmin)->get('/admin/cari-dokumen')->assertForbidden();
        $this->actingAs($superAdmin)->get('/admin/evidence-documents/create')->assertForbidden();
        $this->actingAs($superAdmin)->post('/evidence/upload', [
            'document_name' => 'Dokumen Uji Super Admin',
            'year' => 2026,
            'file' => UploadedFile::fake()->create('test.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_admin_access_matrix_enforced_server_side(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->get('/admin/evidence-documents/create')->assertOk();
        $this->actingAs($admin)->get('/admin/cari-dokumen')->assertOk();
        $this->actingAs($admin)->get('/admin/verification-queue')->assertOk();
        $this->actingAs($admin)->get('/admin/lk-indicators')->assertOk();

        // Admin dilarang akses Master Pengguna, Master LK, Import Profil, Audit, serta create/delete LK indicator
        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/lembar-kerjas')->assertForbidden();
        $this->actingAs($admin)->get('/admin/lk-import-profiles')->assertForbidden();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertForbidden();
        $this->assertFalse(Gate::forUser($admin)->allows('create', LkIndicator::class));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', new LkIndicator));
    }

    public function test_pegawai_access_and_evidence_document_policy_enforced_server_side(): void
    {
        $pegawaiA = $this->createUser('pegawai');
        $pegawaiB = $this->createUser('pegawai');

        $approvedDoc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Disetujui Milik B',
            'year' => 2026,
            'file_path' => 'evidence/approved.pdf',
            'original_filename' => 'approved.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'approved'),
            'processing_status' => 'selesai',
            'approval_status' => 'disetujui',
            'uploaded_by' => $pegawaiB->id,
        ]);

        $otherPendingDoc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Menunggu Milik B',
            'year' => 2026,
            'file_path' => 'evidence/pending-b.pdf',
            'original_filename' => 'pending-b.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'pending-b'),
            'processing_status' => 'selesai',
            'approval_status' => 'menunggu',
            'uploaded_by' => $pegawaiB->id,
        ]);

        $ownPendingDoc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Menunggu Milik A',
            'year' => 2026,
            'file_path' => 'evidence/pending-a.pdf',
            'original_filename' => 'pending-a.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'pending-a'),
            'processing_status' => 'selesai',
            'approval_status' => 'menunggu',
            'uploaded_by' => $pegawaiA->id,
        ]);

        $ownRevisionDoc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Revisi Milik A',
            'year' => 2026,
            'file_path' => 'evidence/revision-a.pdf',
            'original_filename' => 'revision-a.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'revision-a'),
            'processing_status' => 'selesai',
            'approval_status' => 'revisi',
            'uploaded_by' => $pegawaiA->id,
        ]);

        $this->actingAs($pegawaiA)->get('/admin/evidence-documents/create')->assertOk();
        $this->actingAs($pegawaiA)->get('/admin/cari-dokumen')->assertOk();
        $this->actingAs($pegawaiA)->get("/admin/evidence-documents/{$approvedDoc->id}")->assertOk();
        $this->actingAs($pegawaiA)->get("/admin/evidence-documents/{$ownPendingDoc->id}")->assertOk();
        $this->actingAs($pegawaiA)->get("/admin/evidence-documents/{$otherPendingDoc->id}")->assertForbidden();

        // evidence.revise_own hanya berlaku untuk dokumen sendiri berstatus revisi
        $this->assertTrue(Gate::forUser($pegawaiA)->allows('update', $ownRevisionDoc));
        $this->assertFalse(Gate::forUser($pegawaiA)->allows('update', $ownPendingDoc));
        $this->assertFalse(Gate::forUser($pegawaiA)->allows('update', $approvedDoc));
        $this->assertFalse(Gate::forUser($pegawaiA)->allows('update', $otherPendingDoc));

        // Pegawai dilarang ke antrean, LK, Master Pengguna, Audit
        $this->actingAs($pegawaiA)->get('/admin/verification-queue')->assertForbidden();
        $this->actingAs($pegawaiA)->get('/admin/lk-indicators')->assertForbidden();
        $this->actingAs($pegawaiA)->get('/admin/users')->assertForbidden();
        $this->actingAs($pegawaiA)->get('/admin/lembar-kerjas')->assertForbidden();
        $this->actingAs($pegawaiA)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_mock_login_returns_404_in_production_and_test_routes_removed(): void
    {
        $user = $this->createUser('admin');

        $this->actingAs($user)->get('/test-protected-route')->assertNotFound();
        $this->actingAs($user)->post('/test-admin-only-action')->assertNotFound();

        $originalEnv = $this->app->environment();
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $this->get('/auth/sso/mock-login')->assertNotFound();
        } finally {
            $this->app->detectEnvironment(fn (): string => $originalEnv);
        }
    }

    public function test_last_active_super_admin_protection_remains_intact(): void
    {
        $onlySuperAdmin = $this->createUser('super_admin');

        User::query()
            ->where('role', 'super_admin')
            ->where('id', '!=', $onlySuperAdmin->id)
            ->update(['is_active' => false]);

        $this->assertFalse(Gate::forUser($onlySuperAdmin)->allows('delete', $onlySuperAdmin));
        $this->assertFalse(Gate::forUser($onlySuperAdmin)->allows('toggleActive', $onlySuperAdmin));

        $manager = app(UserManagerService::class);

        try {
            $manager->changeUserRole($onlySuperAdmin, $onlySuperAdmin, 'admin');
            $this->fail('Expected HttpException when demoting last active super_admin.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }
}
