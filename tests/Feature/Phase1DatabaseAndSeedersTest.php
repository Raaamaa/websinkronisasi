<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1DatabaseAndSeedersTest extends TestCase
{
    /**
     * Test super admin seeder results and properties.
     */
    public function test_super_admin_seeder_creates_expected_account(): void
    {
        Artisan::call('db:seed', ['--class' => 'SuperAdminSeeder']);

        $expectedEmail = (string) config('services.seeder.superadmin.email', env('SEEDER_SUPERADMIN_EMAIL', 'superadmin@kantor.local'));
        $expectedPassword = (string) config('services.seeder.superadmin.password', env('SEEDER_SUPERADMIN_PASSWORD', 'SecretSuperAdmin123!'));

        $superAdmin = User::where('email', $expectedEmail)->first();

        $this->assertNotNull($superAdmin, 'Super admin user must exist');
        $this->assertSame($expectedEmail, $superAdmin->email);
        $this->assertTrue($superAdmin->must_change_password);
        $this->assertTrue($superAdmin->is_active, 'is_active should be true');
        $this->assertSame('local', $superAdmin->auth_provider);
        $this->assertTrue(Hash::check($expectedPassword, $superAdmin->password_hash), 'Password hash must match config password');
    }

    /**
     * Test super admin seeder throws RuntimeException if password is empty (no fallback allowed).
     */
    public function test_super_admin_seeder_throws_when_password_is_empty(): void
    {
        $origPassword = config('services.seeder.superadmin.password');
        config(['services.seeder.superadmin.password' => '']);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('SEEDER_SUPERADMIN_PASSWORD');
            (new SuperAdminSeeder)->run();
        } finally {
            config(['services.seeder.superadmin.password' => $origPassword]);
        }
    }

    /**
     * Test initial LK seeder creates 6 LK and corresponding profiles.
     */
    public function test_initial_lk_seeder_creates_all_six_master_lks(): void
    {
        $expectedCodes = ['ZI', 'PEKPPP', 'KIP', 'SAKIP', 'DATA_SEKUNDER', 'DATA_SEKTORAL'];

        foreach ($expectedCodes as $code) {
            $lk = LembarKerja::where('code', $code)->first();
            $this->assertNotNull($lk, "LK with code {$code} must exist");
            $this->assertNotNull($lk->default_profile_id, "LK with code {$code} must have default_profile_id");

            $profile = LkImportProfile::find($lk->default_profile_id);
            $this->assertNotNull($profile, "Import profile for {$code} must exist");
            $this->assertSame($code, $profile->lk_code);
            $this->assertIsArray($profile->column_mapping);
        }
    }

    /**
     * Test initial system settings are populated.
     */
    public function test_system_settings_contain_baseline_keys(): void
    {
        $requiredKeys = [
            'matching.similarity_threshold',
            'matching.exact_code_bonus',
            'upload.max_file_size_mb',
            'session.idle_timeout_minutes',
        ];

        foreach ($requiredKeys as $key) {
            $setting = SystemSetting::find($key);
            $this->assertNotNull($setting, "System setting key {$key} must exist");
        }
    }

    /**
     * Test audit_logs immutability trigger blocks UPDATE and DELETE.
     */
    public function test_audit_logs_trigger_blocks_update_and_delete(): void
    {
        $logId = DB::table('audit_logs')->insertGetId([
            'action' => 'test.action',
            'entity_type' => 'test_entity',
            'entity_id' => '123',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'created_at' => now(),
        ]);

        $this->assertNotNull($logId);

        // Attempt UPDATE
        $updateBlocked = false;
        try {
            DB::table('audit_logs')->where('id', $logId)->update(['action' => 'mutated.action']);
        } catch (QueryException $e) {
            $updateBlocked = str_contains($e->getMessage(), 'dilarang demi integritas audit trail') || $e->getCode() === 'P0002';
        }
        $this->assertTrue($updateBlocked, 'Trigger must block UPDATE on audit_logs');

        // Attempt DELETE
        $deleteBlocked = false;
        try {
            DB::table('audit_logs')->where('id', $logId)->delete();
        } catch (QueryException $e) {
            $deleteBlocked = str_contains($e->getMessage(), 'dilarang demi integritas audit trail') || $e->getCode() === 'P0002';
        }
        $this->assertTrue($deleteBlocked, 'Trigger must block DELETE on audit_logs');
    }

    /**
     * Test last super admin protection trigger blocks deletion and deactivation.
     */
    public function test_last_super_admin_cannot_be_deleted_or_deactivated(): void
    {
        $superAdmin = User::where('email', env('SEEDER_SUPERADMIN_EMAIL', 'admin@kantor.go.id'))->first()
            ?? User::where('role', 'super_admin')->firstOrFail();

        $otherSuperAdmins = User::where('role', 'super_admin')->where('id', '!=', $superAdmin->id)->get();
        User::where('role', 'super_admin')->where('id', '!=', $superAdmin->id)->delete();

        try {
            // 1. Attempt deleting the only super admin
            $deleteBlocked = false;
            try {
                DB::table('users')->where('id', $superAdmin->id)->delete();
            } catch (QueryException $e) {
                $deleteBlocked = str_contains($e->getMessage(), 'Sistem harus memiliki setidaknya satu Super Admin aktif') || $e->getCode() === 'P0001';
            }
            $this->assertTrue($deleteBlocked, 'Trigger must block deleting the last active super admin');

            // 2. Attempt deactivating the only super admin
            $deactivateBlocked = false;
            try {
                DB::table('users')->where('id', $superAdmin->id)->update(['is_active' => false]);
            } catch (QueryException $e) {
                $deactivateBlocked = str_contains($e->getMessage(), 'Sistem harus memiliki setidaknya satu Super Admin aktif') || $e->getCode() === 'P0001';
            }
            $this->assertTrue($deactivateBlocked, 'Trigger must block deactivating the last active super admin');

            // 3. Create a second super admin and verify one CAN be deactivated safely
            $secondAdmin = User::create([
                'email' => 'second_admin@kantor.go.id',
                'name' => 'Second Admin',
                'role' => 'super_admin',
                'password_hash' => Hash::make('password123'),
                'must_change_password' => false,
                'is_active' => true,
                'auth_provider' => 'local',
            ]);

            $deactivated = DB::table('users')->where('id', $secondAdmin->id)->update(['is_active' => false]);
            $this->assertSame(1, $deactivated, 'Deactivating a second super admin when another remains active must succeed');

            // Clean up second admin (since it is inactive, deleting it is allowed by trigger)
            DB::table('users')->where('id', $secondAdmin->id)->delete();
        } finally {
            foreach ($otherSuperAdmins as $other) {
                User::withTrashed()->where('id', $other->id)->restore();
            }
        }
    }
}
