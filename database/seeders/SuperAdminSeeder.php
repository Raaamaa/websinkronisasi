<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = (string) env('SEEDER_SUPERADMIN_EMAIL', 'admin@kantor.go.id');
        $password = (string) env('SEEDER_SUPERADMIN_PASSWORD', 'SuperAdmin2026!');
        $name = (string) env('SEEDER_SUPERADMIN_NAME', 'Super Administrator Awal');
        $nip = (string) env('SEEDER_SUPERADMIN_NIP', '198501012010011001');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'nip' => $nip,
                'role' => 'super_admin',
                'password_hash' => Hash::make($password),
                'must_change_password' => true,
                'is_active' => true,
                'auth_provider' => 'local',
                'auth_provider_id' => null,
            ]
        );
    }
}
