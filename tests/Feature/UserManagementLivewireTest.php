<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementLivewireTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin@kantor.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Administrator Awal',
                'nip' => '198501012010011001',
                'role' => 'super_admin',
                'auth_provider' => 'local',
                'password_hash' => Hash::make('SuperAdmin2026!'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        if ($this->superAdmin->trashed()) {
            $this->superAdmin->restore();
        }
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::withTrashed()->firstOrCreate(
            ['email' => 'siti.rahmawati@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Siti Rahmawati, S.Stat.',
                'nip' => '199511032018012001',
                'role' => 'admin',
                'auth_provider' => 'sso',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        if ($this->admin->trashed()) {
            $this->admin->restore();
        }

        $this->pegawai = User::withTrashed()->firstOrCreate(
            ['email' => 'nurul.hidayah@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Nurul Hidayah, S.Tr.Stat.',
                'nip' => '199801252020122004',
                'role' => 'pegawai',
                'auth_provider' => 'sso',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        if ($this->pegawai->trashed()) {
            $this->pegawai->restore();
        }
    }

    public function test_page_renders_stats_cards_and_user_table(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Total Pengguna')
            ->assertSee('Pengelola Sistem')
            ->assertSee('Akun Nonaktif')
            ->assertSee('Siti Rahmawati, S.Stat.')
            ->assertSee('199511032018012001')
            ->assertSee('Nurul Hidayah, S.Tr.Stat.')
            ->assertSee('199801252020122004');
    }

    public function test_role_and_search_filters_work(): void
    {
        $this->actingAs($this->superAdmin);

        // Search by NIP
        Livewire::test(ListUsers::class)
            ->set('search', '199511032018012001')
            ->assertSee('Siti Rahmawati, S.Stat.')
            ->assertDontSee('Nurul Hidayah, S.Tr.Stat.');

        // Filter by role pegawai
        Livewire::test(ListUsers::class)
            ->call('setRoleFilter', 'pegawai')
            ->assertSee('Nurul Hidayah, S.Tr.Stat.')
            ->assertDontSee('Siti Rahmawati, S.Stat.');
    }

    public function test_super_admin_can_open_drawer_and_update_access(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->call('openEditDrawer', (string) $this->pegawai->id)
            ->assertSet('editingUserId', (string) $this->pegawai->id)
            ->assertSet('editingUserName', 'Nurul Hidayah, S.Tr.Stat.')
            ->assertSet('editingRole', 'pegawai')
            ->assertSet('editingIsActive', true)
            ->set('editingRole', 'admin')
            ->call('saveUserAccess')
            ->assertSet('editingUserId', null)
            ->assertHasNoErrors();

        $this->pegawai->refresh();
        $this->assertSame('admin', $this->pegawai->role);

        // Revert role back to pegawai for consistency
        $this->pegawai->update(['role' => 'pegawai']);
    }

    public function test_admin_cannot_edit_super_admin(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/users')->assertForbidden();
    }

    public function test_super_admin_can_add_new_user(): void
    {
        $this->actingAs($this->superAdmin);

        $testEmail = 'test.livewire.user.'.Str::random(6).'@bps.go.id';

        Livewire::test(ListUsers::class)
            ->call('openCreateModal')
            ->set('newName', 'User Uji Baru')
            ->set('newEmail', $testEmail)
            ->set('newNip', '199901012022011009')
            ->set('newRole', 'pegawai')
            ->set('newAuthProvider', 'sso')
            ->call('createUser')
            ->assertSet('showCreateModal', false)
            ->assertHasNoErrors();

        $newUser = User::where('email', $testEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertSame('User Uji Baru', $newUser->name);

        // Clean up
        $newUser->forceDelete();
    }
}
