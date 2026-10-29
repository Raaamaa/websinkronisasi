<?php

namespace Database\Seeders;

use App\Models\Lk;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'KIP', 'scope' => 'internal'],
            ['name' => 'SAKIP', 'scope' => 'internal'],
            ['name' => 'ZI', 'scope' => 'internal'],
            ['name' => 'PEKPPP', 'scope' => 'internal'],
            ['name' => 'Data Sekunder', 'scope' => 'external'],
            ['name' => 'Data Sektoral', 'scope' => 'external'],
        ] as $lk) {
            Lk::firstOrCreate(['name' => $lk['name']], ['scope' => $lk['scope']]);
        }

        $username = trim((string) config('auth.bootstrap_super_admin_username'));
        $passwordHash = trim((string) config('auth.bootstrap_super_admin_password_hash'));

        if ($username === '' || ! preg_match('/^\$2[aby]\$\d{2}\$|^\$argon2/', $passwordHash)) {
            throw new RuntimeException('Set SUPER_ADMIN_USERNAME and a valid SUPER_ADMIN_PASSWORD_HASH before seeding.');
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => $passwordHash,
            ],
        );
    }
}
