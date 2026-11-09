<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MonitorMatrixPage;
use App\Filament\Pages\VerificationQueuePage;
use App\Models\AuditLog;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class FilamentRbacAuthorizationMatrixTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin.rbac.matrix@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Admin RBAC Matrix',
                'role' => 'super_admin',
                'is_active' => true,
                'password' => bcrypt('password'),
            ]
        );
        $this->superAdmin->update(['is_active' => true, 'role' => 'super_admin']);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.rbac.matrix@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin RBAC Matrix',
                'role' => 'admin',
                'is_active' => true,
                'password' => bcrypt('password'),
            ]
        );
        $this->admin->update(['is_active' => true, 'role' => 'admin']);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.rbac.matrix@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pegawai RBAC Matrix',
                'role' => 'pegawai',
                'is_active' => true,
                'password' => bcrypt('password'),
            ]
        );
        $this->pegawai->update(['is_active' => true, 'role' => 'pegawai']);

        $this->inactiveUser = User::firstOrCreate(
            ['email' => 'inactive.rbac.matrix@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Inactive User RBAC Matrix',
                'role' => 'pegawai',
                'is_active' => false,
                'password' => bcrypt('password'),
            ]
        );
        $this->inactiveUser->update(['is_active' => false]);
    }

    public function test_dashboard_can_access_blocks_inactive_user_enforcing_invariant_4(): void
    {
        Auth::login($this->inactiveUser);
        $this->assertFalse(Dashboard::canAccess(), 'Pengguna nonaktif dilarang mengakses Dashboard (Invarian #4)');

        Auth::login($this->pegawai);
        $this->assertTrue(Dashboard::canAccess());
    }

    public function test_page_can_access_enforces_permissions(): void
    {
        Auth::login($this->pegawai);
        $this->assertTrue(MonitorMatrixPage::canAccess());
        $this->assertFalse(VerificationQueuePage::canAccess(), 'Pegawai dilarang mengakses VerificationQueuePage');

        Auth::login($this->admin);
        $this->assertTrue(MonitorMatrixPage::canAccess());
        $this->assertTrue(VerificationQueuePage::canAccess());

        Auth::login($this->superAdmin);
        $this->assertTrue(MonitorMatrixPage::canAccess());
        $this->assertTrue(VerificationQueuePage::canAccess());
    }

    public function test_audit_log_policy_restricts_access_and_enforces_immutability(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', AuditLog::class));
        $this->assertFalse(Gate::forUser($this->admin)->allows('viewAny', AuditLog::class));
        $this->assertFalse(Gate::forUser($this->pegawai)->allows('viewAny', AuditLog::class));

        $log = new AuditLog;
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('update', $log), 'Audit log tidak boleh diubah (Invarian #5)');
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('delete', $log), 'Audit log tidak boleh dihapus (Invarian #5)');
    }

    public function test_evidence_document_policy_enforces_rbac_upload_and_delete(): void
    {
        // Upload permission: Pegawai and Admin can upload, Super Admin cannot
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('create', EvidenceDocument::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', EvidenceDocument::class));
        $this->assertTrue(Gate::forUser($this->pegawai)->allows('create', EvidenceDocument::class));

        // Delete permission: Super Admin only
        $doc = new EvidenceDocument;
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('delete', $doc));
        $this->assertFalse(Gate::forUser($this->admin)->allows('delete', $doc));
        $this->assertFalse(Gate::forUser($this->pegawai)->allows('delete', $doc));
    }

    public function test_user_policy_enforces_super_admin_protection_and_role_management(): void
    {
        // Admin cannot update role
        $this->assertFalse(Gate::forUser($this->admin)->allows('updateRole', [$this->pegawai]));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('updateRole', [$this->pegawai]));

        // Admin can toggle Pegawai, but cannot toggle Admin or SuperAdmin
        $this->assertTrue(Gate::forUser($this->admin)->allows('toggleActive', [$this->pegawai]));
        $this->assertFalse(Gate::forUser($this->admin)->allows('toggleActive', [$this->superAdmin]));

        // Sole active super admin cannot deactivate self (Invariant #3 & AC-04)
        $soleAdmin = User::where('role', 'super_admin')->where('is_active', true)->first();
        if (User::where('role', 'super_admin')->where('is_active', true)->count() === 1) {
            $this->assertFalse(Gate::forUser($soleAdmin)->allows('toggleActive', [$soleAdmin]));
        }
    }

    public function test_master_lk_and_system_settings_policies_are_super_admin_only(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', SystemSetting::class));
        $this->assertFalse(Gate::forUser($this->admin)->allows('viewAny', SystemSetting::class));
        $this->assertFalse(Gate::forUser($this->pegawai)->allows('viewAny', SystemSetting::class));

        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('create', LembarKerja::class));
        $this->assertFalse(Gate::forUser($this->admin)->allows('create', LembarKerja::class));
        $this->assertFalse(Gate::forUser($this->pegawai)->allows('create', LembarKerja::class));

        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('viewAny', LkImportProfile::class));
        $this->assertFalse(Gate::forUser($this->admin)->allows('viewAny', LkImportProfile::class));
    }
}
