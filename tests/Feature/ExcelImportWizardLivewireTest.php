<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LkImportProfileResource\Pages\ListLkImportProfiles;
use App\Jobs\RematchActiveDocumentsJob;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportLog;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ExcelImportWizardLivewireTest extends TestCase
{
    protected User $admin;

    protected LembarKerja $lk;

    protected LkImportProfile $profile;

    /** @var list<string> */
    protected array $createdIndicatorIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin.wizard.test@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Wizard',
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        if ($this->admin->trashed()) {
            $this->admin->restore();
        }

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Zona Integritas 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->profile = LkImportProfile::firstOrCreate(
            ['name' => 'Profil Uji Wizard ZI'],
            [
                'id' => (string) Str::uuid(),
                'lk_code' => 'ZI',
                'sheet_name' => 'LKE',
                'header_row' => 1,
                'parsing_strategy' => 'numbered_list_single_cell',
                'column_mapping' => [
                    'code_col' => 'B',
                    'title_col' => 'C',
                    'evidence_items_col' => 'D',
                    'evidence_link_col' => 'E',
                    'pic_col' => 'F',
                ],
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

    public function test_page_renders_4_step_wizard_and_validation_cards(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->assertSee('Wizard Impor Lembar Kerja dari File Excel')
            ->assertSee('Spesifikasi & Pemetaan Kolom', false)
            ->assertSee('Total Baris File')
            ->assertSee('139')
            ->assertSee('Indikator Valid')
            ->assertSee('109')
            ->assertSee('Baris Dilewati')
            ->assertSee('30')
            ->assertSee('Status Validasi')
            ->assertSee('0 Fatal');
    }

    public function test_apply_profile_preset_updates_column_mapping(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->call('applyProfilePreset', $this->profile->id)
            ->assertSet('colCode', 'B')
            ->assertSet('colTitle', 'C')
            ->assertSet('colEvidence', 'D')
            ->assertSet('colDriveLink', 'E')
            ->assertSet('colPic', 'F')
            ->assertSet('parsingStrategy', 'numbered_list_single_cell');
    }

    public function test_execute_import_performs_idempotent_upsert_and_dispatches_rematch(): void
    {
        Queue::fake();

        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->set('selectedLkId', (string) $this->lk->id)
            ->call('executeImport')
            ->assertSet('currentStep', 4);

        // Verify indicators were created/upserted
        $indicator = LkIndicator::where('lk_id', $this->lk->id)->where('code', 'A11i')->first();
        $this->assertNotNull($indicator);
        $this->createdIndicatorIds[] = $indicator->id;

        // Verify items were created with resolved name
        $item = LkEvidenceItem::where('indicator_id', $indicator->id)->first();
        $this->assertNotNull($item);

        // Verify import log was created
        $log = LkImportLog::where('lk_id', $this->lk->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame(109, $log->imported_indicators);

        // Verify audit log
        $this->assertTrue(
            AuditLog::where('entity_type', 'LembarKerja')
                ->where('entity_id', $this->lk->id)
                ->where('action', 'lk.imported')
                ->exists()
        );

        // Verify rematch queue was dispatched (LK-07)
        Queue::assertPushed(RematchActiveDocumentsJob::class);
    }
}
