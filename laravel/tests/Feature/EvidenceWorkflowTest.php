<?php

namespace Tests\Feature;

use App\Livewire\EvidenceSearch;
use App\Models\Evidence;
use App\Models\Lk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EvidenceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_search_shows_only_lks_with_an_identical_file(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $kip = Lk::create(['name' => 'KIP', 'scope' => 'internal']);
        $sakip = Lk::create(['name' => 'SAKIP', 'scope' => 'internal']);
        Lk::create(['name' => 'ZI', 'scope' => 'internal']);
        $hash = hash('sha256', 'same document bytes');

        foreach ([$kip, $sakip] as $lk) {
            Evidence::create([
                'lk_id' => $lk->id,
                'uploaded_by' => User::factory()->admin()->create()->id,
                'title' => 'SK Kepala Unit',
                'document_number' => '001/SK/2026',
                'original_filename' => 'sk-kepala.pdf',
                'sha256' => $hash,
                'drive_url' => 'https://drive.google.com/drive/folders/example',
            ]);
        }

        $this->actingAs($employee)
            ->get('/search?q=SK%20Kepala')
            ->assertOk()
            ->assertSee('SK Kepala Unit')
            ->assertSee('KIP')
            ->assertSee('SAKIP')
            ->assertSee('ZI')
            ->assertSee('aria-label="Cocok"', false);

        $component = Livewire::actingAs($employee)
            ->test(EvidenceSearch::class)
            ->set('query', 'SK Kepala');

        $this->assertSame(2, substr_count($component->html(), 'aria-label="Cocok"'));
    }

    public function test_admin_uploads_are_hashed_and_not_saved_as_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $firstLk = Lk::create(['name' => 'KIP', 'scope' => 'internal']);
        $secondLk = Lk::create(['name' => 'SAKIP', 'scope' => 'internal']);
        $content = "%PDF-1.4\nproof bytes\n";

        foreach ([$firstLk, $secondLk] as $lk) {
            $this->actingAs($admin)->post('/manage/evidence', [
                'lk_id' => $lk->id,
                'title' => 'SK Kepala Unit',
                'document_number' => '001/SK/2026',
                'drive_url' => 'https://drive.google.com/drive/folders/example',
                'file' => UploadedFile::fake()->createWithContent('sk-kepala.pdf', $content),
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('evidences', 2);
        $this->assertDatabaseHas('evidences', [
            'lk_id' => $firstLk->id,
            'sha256' => hash('sha256', $content),
        ]);
        $this->assertSame(
            Evidence::query()->value('sha256'),
            Evidence::query()->orderByDesc('id')->value('sha256'),
        );
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_admin_cannot_register_the_same_file_twice_in_one_lk(): void
    {
        $admin = User::factory()->admin()->create();
        $lk = Lk::create(['name' => 'KIP', 'scope' => 'internal']);
        $upload = fn () => UploadedFile::fake()->createWithContent('sk-kepala.pdf', "%PDF-1.4\nproof\n");

        $data = [
            'lk_id' => $lk->id,
            'title' => 'SK Kepala Unit',
            'drive_url' => 'https://drive.google.com/drive/folders/example',
        ];

        $this->actingAs($admin)->post('/manage/evidence', $data + ['file' => $upload()])
            ->assertRedirect();
        $this->actingAs($admin)->post('/manage/evidence', $data + ['file' => $upload()])
            ->assertRedirect()
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('evidences', 1);
    }

    public function test_employee_cannot_manage_evidence_or_lks(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->get('/manage/evidence')->assertForbidden();
        $this->actingAs($employee)->get('/manage/lks')->assertForbidden();
    }

    public function test_admin_cannot_manage_users_or_lk_catalog(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/manage/users')->assertForbidden();
        $this->actingAs($admin)->get('/manage/lks')->assertForbidden();
    }

    public function test_super_admin_can_remove_an_account_without_losing_its_evidence_record(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->admin()->create();
        $lk = Lk::create(['name' => 'KIP', 'scope' => 'internal']);
        $evidence = Evidence::create([
            'lk_id' => $lk->id,
            'uploaded_by' => $admin->id,
            'title' => 'SK Kepala Unit',
            'document_number' => null,
            'original_filename' => 'sk-kepala.pdf',
            'sha256' => hash('sha256', 'proof'),
            'drive_url' => 'https://drive.google.com/drive/folders/example',
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('manage.users.destroy', $admin))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('evidences', ['id' => $evidence->id, 'uploaded_by' => null]);
    }

    public function test_login_uses_username_and_password(): void
    {
        User::factory()->create([
            'username' => 'pegawai01',
            'password' => 'a-long-test-password',
        ]);

        $this->post('/login', [
            'username' => 'pegawai01',
            'password' => 'a-long-test-password',
        ])->assertRedirect('/search');

        $this->assertAuthenticated();
    }
}
