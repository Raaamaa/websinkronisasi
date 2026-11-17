<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CariDokumenPage;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CariDokumenLivewireTest extends TestCase
{
    use DatabaseTransactions;
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_cari_dok@bps.go.id'],
            [
                'name' => 'Super Admin Cari Dokumen',
                'role' => 'super_admin',
                'nip' => '198001012005011001',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_cari_dok@bps.go.id'],
            [
                'name' => 'Admin Cari Dokumen',
                'role' => 'admin',
                'nip' => '198501012010011002',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['must_change_password' => false, 'is_active' => true]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai_cari_dok@bps.go.id'],
            [
                'name' => 'Pegawai Cari Dokumen',
                'role' => 'pegawai',
                'nip' => '199001012015011003',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->pegawai->update(['must_change_password' => false, 'is_active' => true]);
    }

    public function test_super_admin_is_blocked_from_cari_dokumen_at_server(): void
    {
        // Invarian #1: Server-side authorization check (Md/RBAC.md:83, PRD:90)
        $response = $this->actingAs($this->superAdmin)->get(CariDokumenPage::getUrl());
        $response->assertForbidden();

        $this->assertFalse(CariDokumenPage::canAccess());
    }

    public function test_admin_can_access_cari_dokumen_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(CariDokumenPage::getUrl());
        $responseAdmin->assertOk();
    }

    public function test_pegawai_can_access_cari_dokumen_page(): void
    {
        $responsePegawai = $this->actingAs($this->pegawai)->get(CariDokumenPage::getUrl());
        $responsePegawai->assertOk();
    }

    public function test_inactive_user_is_blocked_from_cari_dokumen(): void
    {
        $inactivePegawai = User::firstOrCreate(
            ['email' => 'inactive_pegawai_cd@bps.go.id'],
            [
                'name' => 'Pegawai Nonaktif',
                'role' => 'pegawai',
                'nip' => '199201012018011009',
                'password_hash' => bcrypt('password'),
                'is_active' => false,
                'must_change_password' => false,
            ]
        );
        $inactivePegawai->update(['is_active' => false]);

        $response = $this->actingAs($inactivePegawai)->get(CariDokumenPage::getUrl());
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302], true));
    }

    public function test_cari_dokumen_searches_and_renders_approved_documents(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI-CD', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $approvedDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Sah Cari Livewire '.Str::random(6),
            'document_number' => 'B-CDL-01/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_cdl.pdf',
            'original_filename' => 'test_cdl.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $approvedDoc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        $pendingDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Belum Disetujui Livewire '.Str::random(6),
            'document_number' => 'B-CDL-02/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_cdl_p.pdf',
            'original_filename' => 'test_cdl_p.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', $approvedDoc->document_name)
            ->call('search')
            ->assertSee($approvedDoc->document_name)
            ->assertSee($lk->code)
            ->assertDontSee($pendingDoc->document_name);
    }

    public function test_super_admin_cannot_initialize_livewire_component(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CariDokumenPage::class)
            ->assertForbidden();
    }

    public function test_cari_dokumen_view_has_elderly_friendly_touch_targets_and_labels(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->assertSeeHtml('min-h-[56px]')
            ->assertSee('Cari Dokumen Bukti Dukung');
    }

    public function test_cari_dokumen_renders_six_lk_matrix_with_distinct_official_links(): void
    {
        $lks = [
            ['code' => 'KIP', 'name' => 'KIP', 'category' => 'internal', 'interval' => '3 Bulan'],
            ['code' => 'PEKPPP', 'name' => 'PEKPPP', 'category' => 'internal', 'interval' => '6 Bulan'],
            ['code' => 'ZI', 'name' => 'ZI', 'category' => 'internal', 'interval' => '12 Bulan'],
            ['code' => 'SAKIP', 'name' => 'SAKIP', 'category' => 'internal', 'interval' => '12 Bulan'],
            ['code' => 'DATA_SEKTORAL', 'name' => 'Data Sektoral', 'category' => 'eksternal', 'interval' => '1 Bulan'],
            ['code' => 'DATA_SEKUNDER', 'name' => 'Data Sekunder', 'category' => 'eksternal', 'interval' => '1 Bulan'],
        ];

        $lkModels = [];
        foreach ($lks as $item) {
            $lkModels[$item['code']] = LembarKerja::firstOrCreate(
                ['code' => $item['code'], 'period' => 2026],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'evaluation_interval' => $item['interval'],
                    'is_active' => true,
                ]
            );
        }

        $docTitle = 'SK Tim Matriks Enam LK '.Str::random(6);
        $doc = EvidenceDocument::create([
            'document_name' => $docTitle,
            'document_number' => 'B-6LK/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/matriks_6lk.pdf',
            'original_filename' => 'matriks_6lk.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $ind1 = LkIndicator::firstOrCreate(
            ['lk_id' => $lkModels['ZI']->id, 'code' => 'ZI-MAT-1'],
            ['title' => 'Indikator ZI 1', 'order_index' => 1]
        );
        $ind2 = LkIndicator::firstOrCreate(
            ['lk_id' => $lkModels['ZI']->id, 'code' => 'ZI-MAT-2'],
            ['title' => 'Indikator ZI 2', 'order_index' => 2]
        );

        // Dua baris backfill pada LK yang sama (DISTINCT lk_id -> 1 baris di matriks)
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkModels['ZI']->id,
            'indicator_id' => $ind1->id,
            'link_source' => 'backfill',
        ]);
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkModels['ZI']->id,
            'indicator_id' => $ind2->id,
            'link_source' => 'backfill',
        ]);

        // Usulan proposed pada SAKIP BUKAN centang resmi
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkModels['SAKIP']->id,
            'link_source' => 'proposed',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', $docTitle)
            ->call('search')
            ->assertSee('KIP')
            ->assertSee('PEKPPP')
            ->assertSee('ZI')
            ->assertSee('SAKIP')
            ->assertSee('DATA_SEKTORAL')
            ->assertSee('DATA_SEKUNDER')
            ->assertSeeHtml('data-lk="KIP"')
            ->assertSeeHtml('data-lk="PEKPPP"')
            ->assertSeeHtml('data-lk="ZI"')
            ->assertSeeHtml('data-lk="SAKIP"')
            ->assertSeeHtml('data-lk="DATA_SEKTORAL"')
            ->assertSeeHtml('data-lk="DATA_SEKUNDER"')
            ->assertSee($docTitle)
            ->assertSee('✔');
    }

    public function test_clicking_checkmark_toggles_download_section_with_file_and_external_link(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Klik Centang '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/klik_centang.pdf',
            'original_filename' => 'klik_centang.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'external_link' => 'https://drive.google.com/file/d/contoh123/view',
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        $downloadUrl = route('evidence.download', $doc->id);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', $doc->document_name)
            ->call('toggleDownloadSection', $doc->id)
            ->assertSet('expandedDocId', $doc->id)
            ->assertSeeHtml($downloadUrl)
            ->assertSeeHtml('rel="noopener noreferrer"')
            ->assertSee('Unduh Dokumen')
            ->assertSee('Buka Tautan')
            ->call('toggleDownloadSection', $doc->id)
            ->assertSet('expandedDocId', null);
    }

    public function test_check_uploaded_file_rejects_identical_sha256_with_document_details(): void
    {
        Storage::fake('local');

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $content = "%PDF-1.4\nKonten Identik Uji Cek Berkas ".Str::random(10);
        $existingDoc = EvidenceDocument::create([
            'document_name' => 'SK Identik Terdaftar '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->admin->id,
            'file_hash' => hash('sha256', $content),
            'file_path' => 'evidence/identik.pdf',
            'original_filename' => 'identik.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $existingDoc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        $file = UploadedFile::fake()->createWithContent('cek_identik.pdf', $content);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('checkFile', $file)
            ->call('checkUploadedFile')
            ->assertSet('checkStatus', 'identical')
            ->assertSee('Ditolak')
            ->assertSee($existingDoc->document_name)
            ->assertSee($this->admin->name)
            ->assertSee('disetujui')
            ->assertSee($lk->code);
    }

    public function test_check_uploaded_file_shows_up_to_5_similar_candidates_with_scores_non_blocking(): void
    {
        Storage::fake('local');

        for ($i = 1; $i <= 6; $i++) {
            EvidenceDocument::create([
                'document_name' => "SK Pembentukan Tim Zona Integritas Khusus {$i}",
                'year' => 2026,
                'source_type' => 'upload',
                'approval_status' => 'disetujui',
                'uploaded_by' => $this->admin->id,
                'file_hash' => hash('sha256', "sim_cek_{$i}_".Str::random(8)),
                'file_path' => "evidence/sim_cek_{$i}.pdf",
                'original_filename' => "sim_cek_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1024,
                'processing_status' => 'selesai',
            ]);
        }

        $file = UploadedFile::fake()->createWithContent(
            'SK_Pembentukan_Tim_Zona_Integritas_Khusus.pdf',
            "%PDF-1.4\nDokumen baru yang mirip namun berbeda konten ".Str::random(12)
        );

        $component = Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('checkFile', $file)
            ->call('checkUploadedFile')
            ->assertSet('checkStatus', 'similar')
            ->assertSee('%')
            ->assertSeeHtml('/admin/evidence-documents/create');

        $similar = $component->get('checkSimilar');
        $this->assertNotEmpty($similar);
        $this->assertLessThanOrEqual(5, count($similar));
    }

    public function test_check_uploaded_file_shows_green_box_when_not_found_and_deletes_temp_file(): void
    {
        Storage::fake('local');

        $uniqueToken = Str::random(24);
        $file = UploadedFile::fake()->createWithContent(
            "Dokumen_Unik_Belum_Ada_{$uniqueToken}.pdf",
            "%PDF-1.4\nIsi dokumen benar-benar unik {$uniqueToken}"
        );

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('checkFile', $file)
            ->call('checkUploadedFile')
            ->assertSet('checkStatus', 'clear')
            ->assertSet('checkFile', null)
            ->assertSee('Dokumen belum ada di perpustakaan. Silakan unggah.')
            ->assertSeeHtml('/admin/evidence-documents/create');
    }

    public function test_check_uploaded_file_never_creates_new_evidence_document_row(): void
    {
        Storage::fake('local');

        $beforeCount = EvidenceDocument::withTrashed()->count();

        $file = UploadedFile::fake()->createWithContent(
            'Cek_Tanpa_Simpan_'.Str::random(8).'.pdf',
            "%PDF-1.4\nTidak boleh tersimpan ke database ".Str::random(16)
        );

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('checkFile', $file)
            ->call('checkUploadedFile')
            ->assertSet('checkStatus', 'clear');

        $afterCount = EvidenceDocument::withTrashed()->count();
        $this->assertSame($beforeCount, $afterCount);
    }

    public function test_admin_can_revise_lk_markup_from_cari_dokumen_and_pegawai_is_forbidden(): void
    {
        $lkX = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );
        $lkY = LembarKerja::firstOrCreate(
            ['code' => 'SAKIP', 'period' => 2026],
            ['name' => 'SAKIP', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Ubah LK Cari '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/ubah_lk_cari.pdf',
            'original_filename' => 'ubah_lk_cari.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkX->id,
            'link_source' => 'admin',
        ]);

        // Wajib >= 1 LK
        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->call('openEditMarkup', $doc->id, [$lkX->id])
            ->set('editingLkIds', [])
            ->call('saveRevisedMarkup')
            ->assertHasErrors('editingLkIds');

        // Admin mengubah LK ke SAKIP
        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->call('openEditMarkup', $doc->id, [$lkX->id])
            ->set('editingLkIds', [$lkY->id])
            ->call('saveRevisedMarkup')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $lkX->id,
        ]);
        $this->assertDatabaseHas('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $lkY->id,
            'link_source' => 'admin',
        ]);

        // Pegawai ditolak 403 saat mencoba ubah LK
        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->call('reviseMarkup', $doc->id, [$lkX->id])
            ->assertForbidden();
    }

    public function test_cari_dokumen_hides_matrix_and_shows_hint_card_when_keyword_is_empty(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Tersembunyi Saat Kosong '.Str::random(6),
            'year' => 2026,
            'document_type' => 'SK',
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/kosong_tersembunyi.pdf',
            'original_filename' => 'kosong_tersembunyi.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->assertSee('Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.')
            ->assertSee('Cek Berkas Sebelum Unggah')
            ->assertDontSee('Dokumen Bukti Dukung Resmi')
            ->assertDontSee($doc->document_name)
            ->set('query', '   ')
            ->assertSee('Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.')
            ->assertDontSee('Dokumen Bukti Dukung Resmi');
    }

    public function test_cari_dokumen_shows_matrix_and_checkmark_when_keyword_matches(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'SK Pencarian Cocok Matriks '.Str::random(6),
            'year' => 2026,
            'document_type' => 'SK',
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/cocok_matriks.pdf',
            'original_filename' => 'cocok_matriks.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', $doc->document_name)
            ->set('category', 'internal')
            ->set('document_type', 'SK')
            ->set('year', 2026)
            ->assertSee('Dokumen Bukti Dukung Resmi')
            ->assertSee($doc->document_name)
            ->assertSee('✔')
            ->assertDontSee('Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.');
    }

    public function test_cari_dokumen_shows_not_found_upload_prompt_when_keyword_has_no_matches(): void
    {
        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('query', 'KataKunciTidakPernahAdaXYZ99999')
            ->assertSee('Dokumen Bukti Dukung Resmi')
            ->assertSee('Dokumen belum ada di perpustakaan. Silakan unggah lewat menu Unggah Bukti.');
    }

    public function test_cari_dokumen_filters_without_keyword_do_not_show_matrix(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'SK Filter Tanpa Keyword '.Str::random(6),
            'year' => 2026,
            'document_type' => 'SK',
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/filter_no_kw.pdf',
            'original_filename' => 'filter_no_kw.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->call('setCategory', 'internal')
            ->set('document_type', 'SK')
            ->set('year', 2026)
            ->assertSee('Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.')
            ->assertDontSee('Dokumen Bukti Dukung Resmi')
            ->assertDontSee($doc->document_name);
    }

    public function test_cari_dokumen_renders_query_loading_indicator_and_preserves_matrix_hint_and_empty_messages(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'ZI', 'category' => 'internal', 'evaluation_interval' => '12 Bulan', 'is_active' => true]
        );

        $doc = EvidenceDocument::create([
            'document_name' => 'SK Uji Indikator Loading '.Str::random(6),
            'year' => 2026,
            'document_type' => 'SK',
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/loading_test.pdf',
            'original_filename' => 'loading_test.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->assertSeeHtml('wire:loading')
            ->assertSeeHtml('wire:target="query"')
            ->assertSeeHtml('Mencari...')
            ->assertSeeHtml('aria-hidden="true"')
            ->assertSeeHtml('studio-doc-stack')
            ->assertSee('Ketik nama dokumen di kotak pencarian untuk melihat dokumen itu ada di Lembar Kerja mana saja.')
            ->set('query', $doc->document_name)
            ->assertSee('Dokumen Bukti Dukung Resmi')
            ->assertSee($doc->document_name)
            ->set('query', 'TidakAdaDokumenApapunZZZ98765')
            ->assertSee('Dokumen belum ada di perpustakaan. Silakan unggah lewat menu Unggah Bukti.');
    }
}
