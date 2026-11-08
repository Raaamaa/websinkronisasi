<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BpsDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Akhmad Rizki, S.Tr.Stat.',
                'email' => 'akhmad.rizki@bps.go.id',
                'nip' => '199304122015021001',
                'role' => 'super_admin',
                'auth_provider' => 'sso',
                'password_hash' => null,
                'is_active' => true,
                'must_change_password' => false,
            ],
            [
                'name' => 'Dr. Hendra Gunawan, M.Si.',
                'email' => 'hendra.gunawan@bps.go.id',
                'nip' => '198506242008011003',
                'role' => 'super_admin',
                'auth_provider' => 'sso',
                'password_hash' => null,
                'is_active' => true,
                'must_change_password' => false,
            ],
            [
                'name' => 'Siti Rahmawati, S.Stat.',
                'email' => 'siti.rahmawati@bps.go.id',
                'nip' => '199511032018012001',
                'role' => 'admin',
                'auth_provider' => 'sso',
                'password_hash' => null,
                'is_active' => true,
                'must_change_password' => false,
            ],
            [
                'name' => 'Dimas Pratama, S.Kom.',
                'email' => 'dimas.pratama@bps.go.id',
                'nip' => '199602182019011002',
                'role' => 'admin',
                'auth_provider' => 'local',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => true,
                'must_change_password' => false,
            ],
            [
                'name' => 'Nurul Hidayah, S.Tr.Stat.',
                'email' => 'nurul.hidayah@bps.go.id',
                'nip' => '199801252020122004',
                'role' => 'pegawai',
                'auth_provider' => 'sso',
                'password_hash' => null,
                'is_active' => true,
                'must_change_password' => false,
            ],
            [
                'name' => 'Bayu Wicaksono, A.Md.',
                'email' => 'bayu.wicaksono@bps.go.id',
                'nip' => '199709052019021002',
                'role' => 'pegawai',
                'auth_provider' => 'sso',
                'password_hash' => null,
                'is_active' => false,
                'must_change_password' => false,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                array_merge($u, [
                    'id' => User::where('email', $u['email'])->value('id') ?? (string) Str::uuid(),
                ])
            );
        }
    }
}
