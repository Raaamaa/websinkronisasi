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
        $email = (string) (config('services.seeder.superadmin.email') ?? env('SEEDER_SUPERADMIN_EMAIL', ''));
        $password = (string) (config('services.seeder.superadmin.password') ?? env('SEEDER_SUPERADMIN_PASSWORD', ''));
        $name = (string) (config('services.seeder.superadmin.name') ?? env('SEEDER_SUPERADMIN_NAME', 'Super Administrator Awal'));
        $nip = (string) (config('services.seeder.superadmin.nip') ?? env('SEEDER_SUPERADMIN_NIP', '198501012010011001'));

        if ($email === '') {
            throw new \RuntimeException('SEEDER_SUPERADMIN_EMAIL is required to seed the super admin account.');
        }

        if ($password === '') {
            throw new \RuntimeException('SEEDER_SUPERADMIN_PASSWORD is required to seed the super admin account.');
        }

        $user = User::where('email', $email)->orWhere('nip', $nip)->first() ?? new User;
        $user->fill([
            'email' => $email,
            'name' => $name,
            'nip' => $nip,
            'role' => 'super_admin',
            'password_hash' => Hash::make($password),
            'must_change_password' => true,
            'is_active' => true,
            'auth_provider' => 'local',
            'auth_provider_id' => null,
        ]);
        $user->save();
    }
}
