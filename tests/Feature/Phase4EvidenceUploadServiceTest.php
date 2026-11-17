<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase4EvidenceUploadServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $pegawai;

    private User $otherPegawai;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.phase4@bps.go.id'],
            [
                'name' => 'Pegawai Phase 4',
                'role' => 'pegawai',
                'is_active' => true,
                'must_change_password' => false,
                'password' => bcrypt('password'),
            ]
        );

        $this->otherPegawai = User::firstOrCreate(
            ['email' => 'other.phase4@bps.go.id'],
            [
                'name' => 'Other Pegawai Phase 4',
                'role' => 'pegawai',
                'is_active' => true,
                'must_change_password' => false,
                'password' => bcrypt('password'),
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.phase4@bps.go.id'],
            [
                'name' => 'Admin Phase 4',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
                'password' => bcrypt('password'),
            ]
        );
    }

    private function createDigitalPdf(string $title = 'Surat Keputusan'): UploadedFile
    {
        $unique = Str::random(8);
        $content = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Title ({$title} {$unique}) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

        return UploadedFile::fake()->createWithContent("doc_{$unique}.pdf", $content);
    }

    private function createSampleLk(): LembarKerja
    {
        $unique = Str::random(6);

        return LembarKerja::create([
            'code' => 'LK_'.$unique,
            'name' => 'Lembar Kerja Phase 4 '.$unique,
            'category' => 'internal',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);
    }

    /**
     * 1. MIME palsu (.pdf yang berisi berkas executable DOS/MZ) wajib ditolak.
     */
    public function test_fake_mime_executable_disguised_as_pdf_is_rejected(): void
    {
        $exeContent = "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff\x00\x00This program cannot be run in DOS mode.";
        $fakePdf = UploadedFile::fake()->createWithContent('kamuflase.pdf', $exeContent);

        $this->expectException(ValidationException::class);

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);
        $service->upload($fakePdf, [
            'document_name' => 'Malware Kamuflase',
            'year' => 2026,
        ], $this->pegawai);
    }

    /**
     * 2. Ekstensi menyesatkan / tipe MIME tidak terdaftar dalam whitelist wajib ditolak.
     */
    public function test_misleading_extension_or_disallowed_mime_is_rejected(): void
    {
        $shContent = "#!/bin/bash\necho 'bahaya'";
        $fakeFile = UploadedFile::fake()->createWithContent('skrip_palsu.pdf', $shContent);

        $this->expectException(ValidationException::class);

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);
        $service->upload($fakeFile, [
            'document_name' => 'Skrip Shell Disamarkan',
            'year' => 2026,
        ], $this->pegawai);
    }

    /**
     * 3. Kontainer ZIP yang rusak atau bukan kontainer Office valid wajib ditolak.
     */
    public function test_corrupted_or_non_office_zip_is_rejected(): void
    {
        // Buat berkas ZIP murni tapi tidak memuat word/document.xml atau xl/workbook.xml
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        $zip = new \ZipArchive;
        $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('random_file.txt', 'Hanya teks biasa, bukan DOCX atau XLSX');
        $zip->close();

        $zipFile = new UploadedFile($tempZip, 'laporan_bukan_docx.docx', 'application/zip', null, true);

        $this->expectException(ValidationException::class);

        try {
            /** @var EvidenceUploadService $service */
            $service = app(EvidenceUploadService::class);
            $service->upload($zipFile, [
                'document_name' => 'ZIP Rusak',
                'year' => 2026,
            ], $this->pegawai);
        } finally {
            @unlink($tempZip);
        }
    }

    /**
     * 4. Berkas melebihi batas ukuran config('evidence.max_file_size_mb') wajib ditolak.
     */
    public function test_file_exceeding_max_size_from_config_is_rejected(): void
    {
        config(['evidence.max_file_size_mb' => 2]); // Batasi ke 2 MB

        // Berkas ukuran 3 MB
        $largePdf = UploadedFile::fake()->create('dokumen_besar.pdf', 3 * 1024);

        $this->expectException(ValidationException::class);

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);
        $service->upload($largePdf, [
            'document_name' => 'Dokumen Terlalu Besar',
            'year' => 2026,
        ], $this->pegawai);
    }

    /**
     * 5. Hash SHA-256 identik dengan dokumen aktif wajib ditolak dengan DuplicateDocumentException
     * yang memuat data dokumen eksisting dari DocumentSimilarityService.
     */
    public function test_identical_sha256_hash_is_rejected_with_duplicate_exception_carrying_existing_data(): void
    {
        Queue::fake();
        Storage::fake('private');

        $file1 = $this->createDigitalPdf('SK Pelayanan Publik');
        $file2 = clone $file1;

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc1 = $service->upload($file1, [
            'document_name' => 'SK Pelayanan Publik Asli',
            'document_number' => 'SK/PP/2026/001',
            'year' => 2026,
        ], $this->pegawai);

        try {
            $service->upload($file2, [
                'document_name' => 'SK Pelayanan Publik Duplikat',
                'year' => 2026,
            ], $this->admin);

            $this->fail('Seharusnya melempar DuplicateDocumentException');
        } catch (DuplicateDocumentException $e) {
            $this->assertSame($doc1->id, $e->existingDocument->id);
            $this->assertSame('SK Pelayanan Publik Asli', $e->existingDocument->document_name);
            $this->assertNotNull($e->existingData);
            $this->assertSame($doc1->id, $e->existingData['id']);
            $this->assertSame('SK Pelayanan Publik Asli', $e->existingData['judul']);
            $this->assertSame('menunggu', $e->existingData['approval_status']);
        }
    }

    /**
     * 6. Hash dari dokumen yang sudah di-soft-delete tidak menghalangi unggahan baru.
     */
    public function test_identical_hash_of_soft_deleted_document_does_not_block_new_upload(): void
    {
        Queue::fake();
        Storage::fake('private');

        $file1 = $this->createDigitalPdf('SK Penghapusan Arsip');
        $file2 = clone $file1;

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc1 = $service->upload($file1, [
            'document_name' => 'Dokumen Pertama',
            'year' => 2026,
        ], $this->pegawai);

        // Soft delete dokumen pertama
        $doc1->delete();

        // Unggah ulang dengan berkas sama harus sukses karena dokumen pertama deleted_at != null
        $doc2 = $service->upload($file2, [
            'document_name' => 'Dokumen Kedua Pengganti',
            'year' => 2026,
        ], $this->pegawai);

        $this->assertNotNull($doc2);
        $this->assertNotSame($doc1->id, $doc2->id);
        $this->assertSame($doc1->file_hash, $doc2->file_hash);
    }

    /**
     * 7. Mode tautan menolak skema berbahaya (javascript:, data:, file:).
     */
    public function test_link_mode_rejects_dangerous_javascript_and_invalid_schemes(): void
    {
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $dangerousLinks = [
            'javascript:alert("xss")',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'file:///etc/passwd',
            'ftp://ftp.example.com/file.pdf',
            'not-a-valid-url',
        ];

        foreach ($dangerousLinks as $badLink) {
            try {
                $service->createLinkSubmission([
                    'document_name' => 'Uji Link Bahaya',
                    'external_link' => $badLink,
                    'year' => 2026,
                ], $this->pegawai);

                $this->fail("Tautan berbahaya '{$badLink}' seharusnya ditolak.");
            } catch (ValidationException $e) {
                $this->assertTrue($e->validator->errors()->has('external_link'));
            }
        }
    }

    /**
     * 8. Mode tautan menerima URL valid (http/https) dengan status 'menunggu' dan tanpa berkas fisik.
     */
    public function test_link_mode_accepts_valid_http_and_https_with_status_menunggu_and_no_local_file(): void
    {
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc = $service->createLinkSubmission([
            'document_name' => 'Video Sosialisasi Kebijakan 2026',
            'document_number' => 'VID/2026/001',
            'external_link' => 'https://drive.google.com/file/d/video123/view',
            'year' => 2026,
        ], $this->pegawai);

        $this->assertNotNull($doc);
        $this->assertSame('link', $doc->source_type);
        $this->assertSame('menunggu', $doc->approval_status);
        $this->assertSame($this->pegawai->id, $doc->uploaded_by);
        $this->assertNull($doc->file_path);
        $this->assertNull($doc->file_hash);
        $this->assertNull($doc->file_size_bytes);
    }

    /**
     * 9. Usulan LK tersimpan sebagai link_source='proposed' dan tidak masuk relasi centang resmi.
     */
    public function test_proposed_lk_is_stored_as_proposed_and_excluded_from_official_links(): void
    {
        Storage::fake('private');
        Queue::fake();

        $lk1 = $this->createSampleLk();
        $lk2 = $this->createSampleLk();

        $file = $this->createDigitalPdf('Dokumen Dengan Usulan LK');

        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);
        $doc = $service->upload(
            $file,
            [
                'document_name' => 'Dokumen Usulan LK Multi',
                'year' => 2026,
            ],
            $this->pegawai,
            [$lk1->id, $lk2->id]
        );

        $proposedLinks = DocumentLkLink::where('document_id', $doc->id)->get();
        $this->assertCount(2, $proposedLinks);
        $this->assertTrue($proposedLinks->every(fn ($l) => $l->link_source === 'proposed'));

        // Pastikan tidak masuk ke officialLkLinks dan tidak masuk ke relasi lembarKerja resmi
        $docFresh = $doc->fresh();
        $this->assertCount(0, $docFresh->officialLkLinks);
        $this->assertCount(0, $docFresh->lembarKerja);
    }

    /**
     * 10. Revisi pengajuan oleh bukan pemilik (uploaded_by) wajib ditolak.
     */
    public function test_revise_submission_by_non_owner_is_rejected(): void
    {
        Storage::fake('private');
        Queue::fake();

        $file = $this->createDigitalPdf('Dokumen Milik Pegawai');
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc = $service->upload($file, [
            'document_name' => 'Dokumen Awal',
            'year' => 2026,
        ], $this->pegawai);

        $doc->update(['approval_status' => 'revisi', 'approval_notes' => 'Harap ganti cap basah']);

        $this->expectException(HttpException::class);

        // Pegawai lain mencoba merevisi
        $service->reviseSubmission($doc, $this->otherPegawai, [
            'document_name' => 'Percobaan Revisi Ilegal',
        ]);
    }

    /**
     * 11. Revisi pengajuan saat status dokumen bukan 'revisi' wajib ditolak.
     */
    public function test_revise_submission_when_status_is_not_revisi_is_rejected(): void
    {
        Storage::fake('private');
        Queue::fake();

        $file = $this->createDigitalPdf('Dokumen Berstatus Menunggu');
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc = $service->upload($file, [
            'document_name' => 'Dokumen Menunggu',
            'year' => 2026,
        ], $this->pegawai);

        // Status masih 'menunggu' (bukan 'revisi')
        $this->expectException(ValidationException::class);

        $service->reviseSubmission($doc, $this->pegawai, [
            'document_name' => 'Revisi Saat Menunggu',
        ]);
    }

    /**
     * 12. Revisi dengan ganti berkas menghapus berkas fisik lama HANYA setelah DB commit,
     * status kembali 'menunggu', approval_notes dibersihkan, dan ProcessEvidenceDocumentJob di-dispatch.
     */
    public function test_revise_submission_replaces_file_deletes_old_file_after_commit_and_reverts_status_to_menunggu(): void
    {
        Storage::fake('private');
        Queue::fake();

        $fileOld = $this->createDigitalPdf('Berkas Versi 1');
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc = $service->upload($fileOld, [
            'document_name' => 'Surat Tugas Versi 1',
            'year' => 2026,
        ], $this->pegawai);

        $oldFilePath = $doc->file_path;
        $this->assertTrue(Storage::disk('private')->exists($oldFilePath));

        // Admin meminta revisi
        $doc->update([
            'approval_status' => 'revisi',
            'approval_notes' => 'Tanda tangan belum lengkap',
        ]);

        $fileNew = $this->createDigitalPdf('Berkas Versi 2 Lengkap');

        $revised = $service->reviseSubmission(
            $doc,
            $this->pegawai,
            ['document_name' => 'Surat Tugas Versi 2 Sempurna'],
            $fileNew
        );

        $this->assertSame('menunggu', $revised->approval_status);
        $this->assertNull($revised->approval_notes);
        $this->assertSame('Surat Tugas Versi 2 Sempurna', $revised->document_name);
        $this->assertNotSame($oldFilePath, $revised->file_path);

        // Berkas lama harus terhapus, berkas baru harus ada
        $this->assertFalse(Storage::disk('private')->exists($oldFilePath));
        $this->assertTrue(Storage::disk('private')->exists($revised->file_path));

        // ProcessEvidenceDocumentJob harus di-dispatch ulang
        Queue::assertPushed(ProcessEvidenceDocumentJob::class, fn ($job) => $job->documentId === $doc->id);
    }

    /**
     * 13. Kegagalan basis data saat revisi berkas TIDAK menghapus berkas lama.
     */
    public function test_revise_submission_preserves_old_file_when_db_fails(): void
    {
        Storage::fake('private');
        Queue::fake();

        $fileOld = $this->createDigitalPdf('Berkas Aman');
        /** @var EvidenceUploadService $service */
        $service = app(EvidenceUploadService::class);

        $doc = $service->upload($fileOld, [
            'document_name' => 'Dokumen Aman',
            'year' => 2026,
        ], $this->pegawai);

        $oldFilePath = $doc->file_path;
        $doc->update(['approval_status' => 'revisi']);

        $fileNew = $this->createDigitalPdf('Berkas Baru');

        // Picu error database dengan melewatkan year yang tidak valid (string yang melanggar tipe / check)
        // atau kita simulasikan kegagalan DB
        // Pasang hook sementara agar penyimpanan DB sengaja melempar exception
        EvidenceDocument::saving(function (): never {
            throw new \RuntimeException('Simulasi kegagalan transaksi basis data');
        });

        try {
            $service->reviseSubmission($doc, $this->pegawai, [
                'document_name' => 'Gagal Simpan',
            ], $fileNew);

            $this->fail('Seharusnya melempar exception basis data');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulasi kegagalan transaksi basis data', $e->getMessage());

            // Berkas lama HARUS tetap ada utuh
            $this->assertTrue(
                Storage::disk('private')->exists($oldFilePath),
                'Berkas lama dilarang dihapus jika transaksi basis data gagal'
            );
        } finally {
            EvidenceDocument::flushEventListeners();
        }
    }

    /**
     * 14. Batas tepat 20 MB (20971520 byte) diterima oleh inspectAndValidate dengan batas default.
     */
    public function test_exact_20mb_file_is_accepted_by_upload_service(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'exact_20mb_');
        $this->assertNotFalse($tmpPath);

        try {
            $fp = fopen($tmpPath, 'wb');
            $this->assertNotFalse($fp);
            fwrite($fp, "%PDF-1.4\n%%EOF\n");
            ftruncate($fp, 20971520);
            fclose($fp);

            $uploaded = new UploadedFile($tmpPath, 'exact_20mb.pdf', 'application/pdf', null, true);

            /** @var EvidenceUploadService $service */
            $service = app(EvidenceUploadService::class);
            $meta = $service->inspectAndValidate($uploaded);

            $this->assertSame('application/pdf', $meta['mime']);
            $this->assertSame(20971520, $meta['size']);
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * 15. Berkas 20 MB + 1 byte (20971521 byte) ditolak oleh inspectAndValidate dengan batas default.
     */
    public function test_20mb_plus_one_byte_file_is_rejected_by_upload_service(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'over_20mb_');
        $this->assertNotFalse($tmpPath);

        try {
            $fp = fopen($tmpPath, 'wb');
            $this->assertNotFalse($fp);
            fwrite($fp, "%PDF-1.4\n%%EOF\n");
            ftruncate($fp, 20971521);
            fclose($fp);

            $uploaded = new UploadedFile($tmpPath, 'over_20mb.pdf', 'application/pdf', null, true);

            /** @var EvidenceUploadService $service */
            $service = app(EvidenceUploadService::class);

            $this->expectException(ValidationException::class);
            $service->inspectAndValidate($uploaded);
        } finally {
            @unlink($tmpPath);
        }
    }
}
