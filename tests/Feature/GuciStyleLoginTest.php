<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\UserManagerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GuciStyleLoginTest extends TestCase
{
    /**
     * 1. Halaman root / menampilkan halaman login modern bergaya GUCI bagi tamu.
     */
    public function test_guest_visiting_root_receives_login_page(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('ELKA');
        $response->assertSee('BPS Kabupaten Tegal');
    }

    /**
     * 2. Halaman /login menampilkan halaman login modern bergaya GUCI.
     */
    public function test_login_page_renders_with_guci_branding(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('ELKA');
        $response->assertSee('BPS Kabupaten Tegal');
        $response->assertSee('Login SSO BPS');
        $response->assertSee('VPN FortiClient');
    }

    /**
     * 3. Login gagal dengan kredensial salah.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'salah@kantor.go.id',
            'password' => 'passwordsalah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Kombinasi email/NIP dan kata sandi tidak cocok.');
        $this->assertFalse(Auth::check());
    }

    /**
     * 4. Login berhasil dengan akun Admin lokal yang sah.
     */
    public function test_login_succeeds_with_valid_credentials(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin.test@bps.go.id'],
            [
                'name' => 'Admin Test',
                'password_hash' => bcrypt('password123'),
                'role' => 'admin',
                'auth_provider' => 'local',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $admin->update([
            'password_hash' => bcrypt('password123'),
            'role' => 'admin',
            'auth_provider' => 'local',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->flushSession();

        $response = $this->post('/login', [
            'email' => 'admin.test@bps.go.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(Auth::check());
        $this->assertSame($admin->id, Auth::id());
    }

    /**
     * 5. Penegakan AUTH-11: Pegawai dilarang login via form lokal.
     */
    public function test_pegawai_cannot_login_using_local_form_enforcing_auth11(): void
    {
        $pegawai = User::firstOrCreate(
            ['email' => 'pegawai.operasional@bps.go.id'],
            [
                'name' => 'Pegawai Operasional',
                'role' => 'pegawai',
                'auth_provider' => 'sso',
                'is_active' => true,
            ]
        );
        $pegawai->update([
            'role' => 'pegawai',
            'auth_provider' => 'sso',
            'is_active' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'pegawai.operasional@bps.go.id',
            'password' => 'cobaPassword123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Akun Pegawai wajib masuk menggunakan tombol Login SSO BPS (Opsi 1).');
        $this->assertFalse(Auth::check());

        // Pastikan jejak audit terekam
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $pegawai->id,
            'action' => 'auth.login_blocked_pegawai_local',
        ]);
    }

    /**
     * 6. Login ditolak untuk akun nonaktif (AUTH-07, AC-18).
     */
    public function test_login_blocked_for_inactive_account(): void
    {
        $inactiveAdmin = User::firstOrCreate(
            ['email' => 'admin.inactive@bps.go.id'],
            [
                'name' => 'Admin Inactive',
                'password_hash' => bcrypt('password123'),
                'role' => 'admin',
                'auth_provider' => 'local',
                'is_active' => false,
            ]
        );
        $inactiveAdmin->update([
            'password_hash' => bcrypt('password123'),
            'role' => 'admin',
            'auth_provider' => 'local',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin.inactive@bps.go.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.');
        $this->assertFalse(Auth::check());
    }

    /**
     * 7. Super Admin dengan must_change_password diarahkan ke layar karantina (AUTH-03, AC-03).
     */
    public function test_superadmin_quarantined_when_must_change_password_is_true(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $email = $superAdmin->email;
        $origHash = $superAdmin->password_hash;

        try {
            $superAdmin->update([
                'password_hash' => bcrypt('123456789'),
                'must_change_password' => true,
                'is_active' => true,
            ]);

            $this->flushSession();

            $response = $this->post('/login', [
                'email' => $email,
                'password' => '123456789',
            ]);

            $response->assertRedirect(route('auth.password.change'));
            $this->assertTrue(Auth::check());
        } finally {
            $superAdmin->update([
                'password_hash' => $origHash,
                'must_change_password' => false,
            ]);
        }
    }

    /**
     * 8. Rate Limiting mengunci login setelah 5 kali gagal (AUTH-09).
     */
    public function test_login_rate_limiting_locks_out_after_max_attempts(): void
    {
        $testEmail = 'target.bruteforce@bps.go.id';
        RateLimiter::clear(strtolower($testEmail).'|127.0.0.1');

        // Lakukan 5 kali percobaan gagal
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $testEmail,
                'password' => 'passwordSalah'.$i,
            ]);
        }

        // Percobaan ke-6 wajib terblokir oleh RateLimiter
        $response = $this->from('/login')->post('/login', [
            'email' => $testEmail,
            'password' => 'passwordSalahLagi',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', session('error'));
        $this->assertFalse(Auth::check());

        // Bersihkan kembali rate limiter untuk pengujian lainnya
        RateLimiter::clear(strtolower($testEmail).'|127.0.0.1');
    }

    /**
     * 9. Logout berhasil dan mengarahkan kembali ke /login.
     */
    public function test_logout_redirects_to_login(): void
    {
        $user = User::where('email', 'admin.test@bps.go.id')->firstOrFail();

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    /**
     * 10. Tamu tak terautentikasi yang mengakses /admin dialihkan tanpa memicu error 500 RouteNotFound.
     */
    public function test_unauthenticated_guest_accessing_admin_is_redirected_cleanly(): void
    {
        $response = $this->get('/admin');
        // Harus mengembalikan 302 redirect, bukan 500 RouteNotFoundException
        $this->assertContains($response->getStatusCode(), [302]);
        $targetLocation = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_contains($targetLocation, '/login') || str_contains($targetLocation, '/admin/login'),
            'Tamu harus dialihkan ke halaman login.'
        );
    }

    /**
     * 11. Login case-insensitive: email dengan huruf kapital tetap berhasil autentikasi di PostgreSQL.
     */
    public function test_login_succeeds_with_case_insensitive_email(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin.case@bps.go.id'],
            [
                'name' => 'Admin Case',
                'password_hash' => bcrypt('password123'),
                'role' => 'admin',
                'auth_provider' => 'local',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $admin->update([
            'password_hash' => bcrypt('password123'),
            'role' => 'admin',
            'auth_provider' => 'local',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->flushSession();

        // Coba login dengan kombinasi huruf kapital
        $response = $this->post('/login', [
            'email' => 'ADMIN.CASE@BPS.GO.ID',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(Auth::check());
        $this->assertSame($admin->id, Auth::id());
    }

    /**
     * 12. Admin baru yang dibuat manual oleh Super Admin otomatis ber-provider 'local' dan bisa login via form lokal.
     */
    public function test_admin_created_by_superadmin_can_login_via_local_form(): void
    {
        $superAdmin = User::where('role', 'super_admin')->whereNull('deleted_at')->firstOrFail();
        $manager = app(UserManagerService::class);

        $uniqueEmail = 'admin.baru.'.bin2hex(random_bytes(4)).'@bps.go.id';

        $newAdmin = $manager->createUser($superAdmin, [
            'name' => 'Admin Baru Ditambah',
            'email' => $uniqueEmail,
            'role' => 'admin',
            'password' => 'adminBaruPass123',
            'is_active' => true,
        ]);

        $this->assertSame('local', $newAdmin->auth_provider);

        $this->flushSession();

        $response = $this->post('/login', [
            'email' => $uniqueEmail,
            'password' => 'adminBaruPass123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(Auth::check());
        $this->assertSame($newAdmin->id, Auth::id());
    }

    /**
     * 13. Pengguna yang terkena karantina must_change_password dapat logout tanpa terjebak redirect loop.
     */
    public function test_quarantined_user_can_logout_safely(): void
    {
        $quarantinedUser = User::firstOrCreate(
            ['email' => 'admin.quarantined@bps.go.id'],
            [
                'name' => 'Quarantined Admin',
                'password_hash' => bcrypt('initialPass123'),
                'role' => 'admin',
                'auth_provider' => 'local',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );
        $quarantinedUser->update(['must_change_password' => true, 'is_active' => true, 'role' => 'admin']);

        $response = $this->actingAs($quarantinedUser)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    /**
     * 14. Akses ke /auth/sso/callback?mock_user=... ditolak 403 saat driver Keycloak aktif (T3).
     */
    public function test_mock_user_parameter_is_rejected_with_403_when_driver_is_keycloak(): void
    {
        config(['services.sso.driver' => 'keycloak']);

        $response = $this->get('/auth/sso/callback?mock_user=pegawai_sample');
        $response->assertStatus(403);
    }

    /**
     * 15. Halaman /auth/sso/mock ditolak 403 atau 404 saat bukan di lingkungan local/testing (T3).
     */
    public function test_mock_login_view_is_rejected_outside_local_or_testing(): void
    {
        $origEnv = app()->environment();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $response = $this->get('/auth/sso/mock');
            $this->assertContains($response->getStatusCode(), [403, 404]);
        } finally {
            $this->app->detectEnvironment(fn () => $origEnv);
        }
    }

    /**
     * 16. Resolving SSOProviderInterface with mock driver throws DomainException outside local or testing.
     */
    public function test_mock_driver_throws_domain_exception_outside_local_or_testing(): void
    {
        $origEnv = app()->environment();
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.sso.driver' => 'mock']);

        try {
            $this->expectException(\DomainException::class);
            app(SSOProviderInterface::class);
        } finally {
            $this->app->detectEnvironment(fn () => $origEnv);
            config(['services.sso.driver' => 'mock']);
        }
    }

    /**
     * 17. GET /logout is rejected with 405 Method Not Allowed (only POST allowed to prevent CSRF).
     */
    public function test_get_logout_is_rejected_with_method_not_allowed(): void
    {
        $response = $this->get('/logout');
        $response->assertStatus(405);
    }
}
