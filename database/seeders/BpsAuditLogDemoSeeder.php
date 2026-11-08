<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BpsAuditLogDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure representative demo users exist
        $usersData = [
            'rere' => [
                'name' => 'Rere Adelina, S.Tr.Stat.',
                'email' => 'rere.adelina@bps.go.id',
                'nip' => '199408152018012003',
                'role' => 'admin',
                'auth_provider' => 'sso',
                'is_active' => true,
            ],
            'bambang' => [
                'name' => 'Bambang Sugiharto, M.Si.',
                'email' => 'bambang.sugiharto@bps.go.id',
                'nip' => '198503122008121001',
                'role' => 'pegawai',
                'auth_provider' => 'sso',
                'is_active' => true,
            ],
            'dodi' => [
                'name' => 'Dodi Anwar, S.Kom.',
                'email' => 'dodi.anwar@bps.go.id',
                'nip' => '198904212014021002',
                'role' => 'super_admin',
                'auth_provider' => 'sso',
                'is_active' => true,
            ],
            'hendra_f' => [
                'name' => 'Hendra Fathurrahman, S.Si.',
                'email' => 'hendra.fathurrahman@bps.go.id',
                'nip' => '199106192015031004',
                'role' => 'admin',
                'auth_provider' => 'sso',
                'is_active' => true,
            ],
            'system' => [
                'name' => 'Sistem Gate BPS SSO',
                'email' => 'sso-gate@bps.go.id',
                'nip' => null,
                'role' => 'admin',
                'auth_provider' => 'sso',
                'is_active' => true,
            ],
        ];

        $userIds = [];
        foreach ($usersData as $key => $data) {
            $user = User::withTrashed()->where('email', $data['email'])->first();
            if (! $user) {
                $user = User::create(array_merge($data, [
                    'id' => (string) Str::uuid(),
                    'password_hash' => null,
                    'must_change_password' => false,
                ]));
            } elseif ($user->trashed()) {
                $user->restore();
            }
            $userIds[$key] = $user->id;
        }

        // 2. Check if mockup audit logs already exist
        $existing = DB::table('audit_logs')
            ->where('ip_address', '10.128.4.15')
            ->count();

        if ($existing >= 2) {
            return;
        }

        // 3. Insert the exact 6 mockup audit log records
        $now = Carbon::parse('2026-11-02 14:15:08');

        $logs = [
            // Row 1: Verifikasi Matching
            [
                'user_id' => $userIds['rere'],
                'action' => 'match.confirmed',
                'entity_type' => 'DocumentMatch',
                'entity_id' => (string) Str::uuid(),
                'old_values' => json_encode(['status' => 'auto', 'similarity_score' => 0.92]),
                'new_values' => json_encode([
                    'status' => 'confirmed',
                    'file_name' => 'SK-Tim-ZI-2026.pdf',
                    'indicator_code' => 'A11i',
                    'similarity_score' => 0.92,
                    'actor_title' => 'Admin Operasional',
                ]),
                'ip_address' => '10.128.4.15',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36',
                'created_at' => '2026-11-02 14:15:08',
            ],
            // Row 2: Unggah Bukti
            [
                'user_id' => $userIds['bambang'],
                'action' => 'evidence.uploaded',
                'entity_type' => 'EvidenceDocument',
                'entity_id' => (string) Str::uuid(),
                'old_values' => null,
                'new_values' => json_encode([
                    'file_name' => 'Laporan_Evaluasi_PST_Triwulan_IV.pdf',
                    'file_size' => '12.4 MB',
                    'file_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                    'actor_title' => 'Operator ZI',
                ]),
                'ip_address' => '10.128.12.88',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Edge/122.0.0.0',
                'created_at' => '2026-11-02 13:48:22',
            ],
            // Row 3: Manajemen Akses
            [
                'user_id' => $userIds['dodi'],
                'action' => 'user.role_changed',
                'entity_type' => 'User',
                'entity_id' => (string) Str::uuid(),
                'old_values' => json_encode(['role' => 'pegawai', 'role_label' => 'Pegawai (Operator)']),
                'new_values' => json_encode([
                    'target_name' => 'Siti Rahmawati',
                    'old_role_label' => 'Pegawai (Operator)',
                    'new_role_label' => 'Admin (Verifikator)',
                    'role' => 'admin',
                    'actor_title' => 'Super Administrator',
                ]),
                'ip_address' => '10.128.2.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) Safari/605.1.15',
                'created_at' => '2026-11-02 11:02:14',
            ],
            // Row 4: Impor Lembar Kerja
            [
                'user_id' => $userIds['hendra_f'],
                'action' => 'lk.imported',
                'entity_type' => 'LembarKerja',
                'entity_id' => (string) Str::uuid(),
                'old_values' => null,
                'new_values' => json_encode([
                    'file_name' => 'LKE_ZI_BPS_2026_Final.xlsx',
                    'valid_count' => 109,
                    'skipped_count' => 30,
                    'actor_title' => 'Tim Teknis SAKIP',
                ]),
                'ip_address' => '10.128.8.22',
                'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0',
                'created_at' => '2026-11-02 10:14:55',
            ],
            // Row 5: Penolakan Matching
            [
                'user_id' => $userIds['rere'],
                'action' => 'match.rejected',
                'entity_type' => 'DocumentMatch',
                'entity_id' => (string) Str::uuid(),
                'old_values' => json_encode(['status' => 'auto']),
                'new_values' => json_encode([
                    'status' => 'rejected',
                    'file_name' => 'Renstra_BPS_Revisi.pdf',
                    'indicator_code' => 'SAKIP-01',
                    'rejection_note' => 'Kunci permanen MATCH-06: format tgl usang',
                    'actor_title' => 'Admin Operasional',
                ]),
                'ip_address' => '10.128.4.15',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36',
                'created_at' => '2026-11-02 09:41:20',
            ],
            // Row 6: Sesi / Login
            [
                'user_id' => $userIds['system'],
                'action' => 'auth.login_sso',
                'entity_type' => 'User',
                'entity_id' => $userIds['rere'],
                'old_values' => null,
                'new_values' => json_encode([
                    'email' => 'rere.adelina@bps.go.id',
                    'protocol' => 'OAuth2/OpenID Connect',
                    'identity_provider' => 'Keycloak Identity Service • BPS RI',
                    'actor_title' => 'Keycloak Identity Service • BPS RI',
                ]),
                'ip_address' => '10.128.1.1',
                'user_agent' => 'Keycloak-SSO-Agent/24.0.1 (BPS Infrastructure)',
                'created_at' => '2026-11-02 08:00:12',
            ],
        ];

        foreach ($logs as $log) {
            DB::table('audit_logs')->insert($log);
        }
    }
}
