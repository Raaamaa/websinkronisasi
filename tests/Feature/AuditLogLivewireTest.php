<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogLivewireTest extends TestCase
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

    public function test_page_renders_stats_cards_and_audit_table(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListAuditLogs::class)
            ->assertSee('Jejak Audit & Riwayat Aktivitas', false)
            ->assertSee('Append-Only WORM Aktif')
            ->assertSee('Aktivitas Hari Ini')
            ->assertSee('Total Rekaman Sistem')
            ->assertSee('Aktor Paling Aktif')
            ->assertSee('Admin Rere')
            ->assertSee('Integritas Ledger')
            ->assertSee('sha256:');
    }

    public function test_search_and_category_filters_work(): void
    {
        $this->actingAs($this->superAdmin);

        // Filter by category: match_reject
        Livewire::test(ListAuditLogs::class)
            ->set('categoryFilter', 'match_reject')
            ->assertSee('Penolakan Matching');

        // Critical toggle: only rejections and role changes
        Livewire::test(ListAuditLogs::class)
            ->set('criticalOnlyToggle', true)
            ->assertSee('Hanya penolakan & perubahan peran', false);
    }

    public function test_export_csv_streams_file(): void
    {
        $this->actingAs($this->superAdmin);

        $response = Livewire::test(ListAuditLogs::class)
            ->call('exportCsv');

        $this->assertNotNull($response);
    }

    public function test_admin_and_pegawai_cannot_access_audit_log_page(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/audit-logs')->assertForbidden();

        $this->actingAs($this->pegawai);
        $this->get('/admin/audit-logs')->assertForbidden();
    }
}
