<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LembarKerjaResource\Pages\ListLembarKerjas;
use App\Jobs\RematchActiveDocumentsJob;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MasterLembarKerjaLivewireTest extends TestCase
{
    protected User $admin;

    protected LembarKerja $lk;

    protected LkIndicator $indicator;

    protected LkEvidenceItem $item;

    /** @var list<string> */
    protected array $createdIndicatorIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin.lk.test@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Admin Master LK',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        if ($this->admin->trashed()) {
            $this->admin->restore();
        }
        $this->admin->update(['role' => 'super_admin', 'is_active' => true]);

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'LKE Zona Integritas 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lk->id, 'code' => 'A11i-TEST'],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Pembentukan Tim Kerja Pembangunan ZI Uji',
                'order_index' => 1,
                'pic' => 'Bagian Umum',
                'metadata' => ['area' => 'Area I — Manajemen Perubahan > Pembentukan Tim Kerja'],
            ]
        );

        $this->createdIndicatorIds[] = $this->indicator->id;

        $this->item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $this->indicator->id, 'resolved_name' => 'SK Tim Kerja Pembangunan ZI Tahun 2026'],
            [
                'id' => (string) Str::uuid(),
                'raw_template_name' => 'SK Tim Kerja Pembangunan ZI [t]',
                'order_index' => 1,
            ]
        );
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdIndicatorIds)) {
            LkEvidenceItem::whereIn('indicator_id', $this->createdIndicatorIds)->delete();
            LkIndicator::whereIn('id', $this->createdIndicatorIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_page_renders_lk_switcher_cards_and_indicators(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLembarKerjas::class)
            ->assertSee('Master Lembar Kerja')
            ->assertSee('LKE Zona Integritas')
            ->assertSee('Kelengkapan Bukti')
            ->assertSee('A11i-TEST')
            ->assertSee('Pembentukan Tim Kerja Pembangunan ZI Uji');
    }

    public function test_select_lk_switches_active_instrument(): void
    {
        $this->actingAs($this->admin);

        $sakip = LembarKerja::firstOrCreate(
            ['code' => 'SAKIP', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'SAKIP Kinerja 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        Livewire::test(ListLembarKerjas::class)
            ->call('selectLk', (string) $sakip->id)
            ->assertSet('selectedLkId', (string) $sakip->id);
    }

    public function test_open_drawer_loads_indicator_data_and_tokens(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLembarKerjas::class)
            ->call('openIndicatorDrawer', $this->indicator->id)
            ->assertSet('editingIndicatorId', $this->indicator->id)
            ->assertSet('indicatorCode', 'A11i-TEST')
            ->assertSet('indicatorPic', 'Bagian Umum')
            ->assertSee('Pengaturan Indikator Lembar Kerja')
            ->assertSee('SK Tim Kerja Pembangunan ZI [t]');
    }

    public function test_save_indicator_updates_tokens_and_dispatches_rematch_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin);

        Livewire::test(ListLembarKerjas::class)
            ->call('openIndicatorDrawer', $this->indicator->id)
            ->set('indicatorTitle', 'Pembentukan Tim Kerja Diperbarui')
            ->set('indicatorDriveLink', 'https://drive.google.com/drive/folders/test12345')
            ->set('indicatorPic', 'IPDS')
            ->set('newItemText', 'Notula Rapat Pembentukan [t]')
            ->call('addItemToken')
            ->call('saveIndicator')
            ->assertSet('editingIndicatorId', null);

        $this->indicator->refresh();
        $this->assertSame('Pembentukan Tim Kerja Diperbarui', $this->indicator->title);
        $this->assertSame('https://drive.google.com/drive/folders/test12345', $this->indicator->evidence_link);
        $this->assertSame('IPDS', $this->indicator->pic);

        // Verify resolved token item for 2026
        $resolvedItem = LkEvidenceItem::where('indicator_id', $this->indicator->id)
            ->where('raw_template_name', 'Notula Rapat Pembentukan [t]')
            ->first();
        $this->assertNotNull($resolvedItem);
        $this->assertSame('Notula Rapat Pembentukan 2026', $resolvedItem->resolved_name);

        // Verify rematch queue was dispatched (LK-07)
        Queue::assertPushed(RematchActiveDocumentsJob::class);

        // Verify audit log was recorded (AUDIT-02)
        $this->assertTrue(
            AuditLog::where('entity_type', 'LkIndicator')
                ->where('entity_id', $this->indicator->id)
                ->where('action', 'indicator.updated')
                ->exists()
        );
    }

    public function test_save_indicator_rejects_invalid_link_protocol(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLembarKerjas::class)
            ->call('openIndicatorDrawer', $this->indicator->id)
            ->set('indicatorDriveLink', 'javascript:alert(1)')
            ->call('saveIndicator')
            ->assertSet('editingIndicatorId', $this->indicator->id);

        $this->indicator->refresh();
        $this->assertNotSame('javascript:alert(1)', $this->indicator->evidence_link);
    }
}
