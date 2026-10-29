<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\DTOs\SSOUserData;
use App\Services\Auth\Providers\MockSSOProvider;
use App\Services\Auth\UserManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase2RbacAndAuthTest extends TestCase
{
    /**
     * Slice 1: TSK-201 - SSO Provider contract and Mock implementation.
     */
    public function test_sso_provider_interface_is_bound_and_mock_returns_valid_data(): void
    {
        $provider = app(SSOProviderInterface::class);

        $this->assertInstanceOf(SSOProviderInterface::class, $provider);
        $this->assertInstanceOf(MockSSOProvider::class, $provider);

        // Test redirect response
        $redirectResponse = $provider->getRedirectResponse();
        $this->assertInstanceOf(Response::class, $redirectResponse);

        // Test callback resolution
        $request = Request::create('/auth/sso/callback', 'GET', [
            'mock_user' => 'pegawai_sample',
        ]);

        $userData = $provider->handleCallback($request);
        $this->assertInstanceOf(SSOUserData::class, $userData);
        $this->assertNotEmpty($userData->email);
        $this->assertNotEmpty($userData->name);
        $this->assertNotEmpty($userData->nip);
    }

    /**
     * Slice 2: TSK-202 - Auto-provisioning Pegawai on first SSO login (AUTH-10, AC-21).
     */
    public function test_first_time_sso_login_auto_provisions_pegawai_account_and_logs_audit(): void
    {
        User::where('email', 'pegawai.sample@bps.go.id')->update(['is_active' => true]);

        $response = $this->get('/auth/sso/callback?mock_user=pegawai_sample');

        $response->assertRedirect('/admin');

        $this->assertAuthenticated();

        $user = User::where('email', 'pegawai.sample@bps.go.id')->first();
        $this->assertNotNull($user);
        $this->assertSame('pegawai', $user->role);
        $this->assertSame('sso', $user->auth_provider);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->password_hash);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_sso',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);
    }

    /**
     * Slice 2: TSK-202 - Inactive account rejected without creating session (AUTH-07, AC-18).
     */
    public function test_inactive_account_logging_in_via_sso_is_rejected_without_session(): void
    {
        // Pre-create or update inactive user
        User::updateOrCreate(
            ['email' => 'pegawai.inactive@bps.go.id'],
            [
                'name' => 'Pegawai Nonaktif',
                'nip' => '199201012018011003',
                'role' => 'pegawai',
                'is_active' => false,
                'auth_provider' => 'sso',
                'auth_provider_id' => 'mock-sso-inactive-003',
                'must_change_password' => false,
            ]
        );

        $response = $this->get('/auth/sso/callback?mock_user=inactive_sample');

        $response->assertStatus(403);
        $this->assertGuest();
    }

    /**
     * Slice 3: TSK-203 - Force change password flow for dummy super admin (AUTH-03, AUTH-04, D6, AC-03).
     */
    public function test_super_admin_can_access_change_password_form(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $superAdmin->update(['must_change_password' => true]);

        $response = $this->actingAs($superAdmin)->get('/auth/password/change');
        $response->assertStatus(200);
        $response->assertSee('Ganti Kata Sandi');
    }

    public function test_super_admin_can_successfully_change_password_and_clears_quarantine_flag(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $superAdmin->update(['must_change_password' => true]);

        $newPassword = 'NewSecret2026!'.bin2hex(random_bytes(4));

        $response = $this->actingAs($superAdmin)->post('/auth/password/change', [
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect('/admin');

        $superAdmin->refresh();
        $this->assertFalse($superAdmin->must_change_password);
        $this->assertTrue(Hash::check($newPassword, $superAdmin->password_hash));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'auth.password_changed',
            'entity_type' => 'users',
            'entity_id' => $superAdmin->id,
        ]);
    }

    /**
     * Slice 4: TSK-204 - Middleware EnsurePasswordChanged redirects quarantined users (AC-03).
     */
    public function test_user_with_must_change_password_is_redirected_when_accessing_admin(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $superAdmin->update(['must_change_password' => true]);

        $response = $this->actingAs($superAdmin)->get('/admin');
        $response->assertRedirect('/auth/password/change');
    }

    /**
     * Slice 4: TSK-204 - Middleware EnsureAccountIsActive invalidates session immediately (AC-15).
     */
    public function test_active_user_deactivated_by_admin_is_rejected_with_403_on_subsequent_request(): void
    {
        $pegawai = User::updateOrCreate(
            ['email' => 'pegawai.active@bps.go.id'],
            [
                'name' => 'Pegawai Aktif',
                'nip' => '199501012020011005',
                'role' => 'pegawai',
                'is_active' => true,
                'auth_provider' => 'sso',
                'must_change_password' => false,
            ]
        );

        // First request is allowed
        $this->actingAs($pegawai)->get('/test-protected-route')->assertStatus(200);

        // Admin deactivates pegawai
        $pegawai->update(['is_active' => false]);

        // Subsequent request immediately rejected with 403
        $this->actingAs($pegawai)->get('/test-protected-route')->assertStatus(403);
        $this->assertGuest();
    }

    /**
     * Slice 4: TSK-204 - Middleware AuthorizePermission blocks Pegawai from admin actions (AC-01).
     */
    public function test_pegawai_cannot_access_endpoint_requiring_admin_permission_returns_403(): void
    {
        $pegawai = User::where('email', 'pegawai.sample@bps.go.id')->firstOrFail();
        $pegawai->update(['is_active' => true]);

        // Permission 'evidence.upload' is only for admin & super_admin
        $response = $this->actingAs($pegawai)->post('/test-admin-only-action');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_endpoint_requiring_admin_permission(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin.test@bps.go.id'],
            [
                'name' => 'Admin Tester',
                'nip' => '198701012010011007',
                'role' => 'admin',
                'is_active' => true,
                'auth_provider' => 'sso',
                'must_change_password' => false,
            ]
        );

        $response = $this->actingAs($admin)->post('/test-admin-only-action');
        $response->assertStatus(200);
    }

    /**
     * Slice 5: TSK-205 & TSK-206 - User management guard rails (AC-04, AC-19, AC-20).
     */
    public function test_admin_can_only_toggle_pegawai_and_is_blocked_from_mutating_admin_or_super_admin(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();

        $manager = app(UserManagerService::class);

        // 1. Admin can deactivate Pegawai
        $manager->toggleActiveStatus($admin, $pegawai, false);
        $this->assertFalse($pegawai->fresh()->is_active);

        // Reactivate for test isolation
        $manager->toggleActiveStatus($admin, $pegawai, true);

        // 2. Admin CANNOT deactivate Super Admin (must throw 403)
        $this->expectException(HttpException::class);
        $manager->toggleActiveStatus($admin, $superAdmin, false);
    }

    public function test_admin_cannot_change_any_user_role(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        $manager = app(UserManagerService::class);

        $this->expectException(HttpException::class);
        $manager->changeUserRole($admin, $pegawai, 'admin');
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $manager = app(UserManagerService::class);

        $this->expectException(\InvalidArgumentException::class);
        $manager->toggleActiveStatus($superAdmin, $superAdmin, false);
    }
}
