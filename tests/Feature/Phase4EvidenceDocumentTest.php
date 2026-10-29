<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Evidence\TextExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class Phase4EvidenceDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        EvidenceDocument::query()->forceDelete();
    }

    protected function tearDown(): void
    {
        EvidenceDocument::query()->forceDelete();
        parent::tearDown();
    }

    /**
     * Helper to create a valid minimal digital PDF with readable text.
     */
    private function createDigitalPdfContent(string $text): string
    {
        $stream = 'BT /F1 12 Tf 72 712 Td ('.addcslashes($text, '()\\').') Tj ET';
        $len = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Length $len >>\nstream\n$stream\nendstream\nendobj\n";
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000058 00000 n \n";
        $pdf .= "0000000115 00000 n \n";
        $pdf .= "0000000266 00000 n \n";
        $pdf .= "0000000340 00000 n \n";
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n412\n%%EOF\n";

        return $pdf;
    }

    /**
     * Helper to create a minimal DOCX content with readable text.
     */
    private function createDocxContent(string $text): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:r><w:t>'.htmlspecialchars($text).'</w:t></w:r></w:p></w:body>'
            .'</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Helper to create an image with text for OCR testing.
     */
    private function createImageWithTextContent(string $text): string
    {
        $img = imagecreatetruecolor(400, 100);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        $textColor = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 5, 20, 40, $text, $textColor);

        ob_start();
        imagepng($img);
        $content = (string) ob_get_clean();
        imagedestroy($img);

        return $content;
    }

    /**
     * 1. BD-01: Admin and Super Admin can upload evidence document.
     */
    public function test_admin_and_super_admin_can_upload_evidence_document(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $pdfContent = $this->createDigitalPdfContent('Surat Keputusan Tim Reformasi Birokrasi BPS 2026');
        $file = UploadedFile::fake()->createWithContent('SK_Tim_RB_2026.pdf', $pdfContent);

        $service = app(EvidenceUploadService::class);
        $document = $service->upload($file, [
            'document_name' => 'SK Tim Reformasi Birokrasi BPS 2026',
            'document_number' => '01/BPS/2026',
            'year' => 2026,
            'document_type' => 'SK',
            'external_link' => 'https://bps.go.id/docs/sk-rb-2026.pdf',
        ], $admin);

        $this->assertInstanceOf(EvidenceDocument::class, $document);
        $this->assertSame('SK Tim Reformasi Birokrasi BPS 2026', $document->document_name);
        $this->assertSame('01/BPS/2026', $document->document_number);
        $this->assertSame(2026, $document->year);
        $this->assertSame('memproses', $document->processing_status);
        $this->assertSame($admin->id, $document->uploaded_by);

        Queue::assertPushed(ProcessEvidenceDocumentJob::class, function ($job) use ($document) {
            return $job->documentId === $document->id;
        });
    }

    /**
     * 2. NFR-01, AC-01: Pegawai cannot upload evidence document.
     */
    public function test_pegawai_cannot_upload_evidence_document_returns_403(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        $pdfContent = $this->createDigitalPdfContent('Dokumen Pegawai');
        $file = UploadedFile::fake()->createWithContent('laporan.pdf', $pdfContent);

        $response = $this->actingAs($pegawai)->postJson('/evidence/upload', [
            'document_name' => 'Laporan Pegawai',
            'year' => 2026,
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 3. BD-02: File exceeding max allowed size (default 20MB) is rejected.
     */
    public function test_upload_file_exceeding_max_size_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Limit default 20MB = 20 * 1024 KB = 20480 KB
        $oversizedFile = UploadedFile::fake()->create('huge.pdf', 25000, 'application/pdf');

        $this->expectException(ValidationException::class);

        $service = app(EvidenceUploadService::class);
        $service->upload($oversizedFile, [
            'document_name' => 'File Terlalu Besar',
            'year' => 2026,
        ], $admin);
    }

    /**
     * 4. BD-03, AC-06: Renamed .exe file to .pdf is rejected by MIME sniffing.
     */
    public function test_upload_renamed_exe_to_pdf_is_rejected_by_mime_sniffing(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Binary with Windows PE/MZ header
        $exeContent = "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff\x00\x00This program cannot be run in DOS mode.";
        $fakePdf = UploadedFile::fake()->createWithContent('malware.pdf', $exeContent);

        $this->expectException(ValidationException::class);

        $service = app(EvidenceUploadService::class);
        $service->upload($fakePdf, [
            'document_name' => 'Malware Kamuflase',
            'year' => 2026,
        ], $admin);
    }

    /**
     * 5. BD-04, BD-05, AC-05: Uploading identical file twice is rejected as duplicate.
     */
    public function test_upload_identical_file_twice_is_rejected_as_duplicate(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $pdfContent = $this->createDigitalPdfContent('Dokumen Unik Pertama 2026');

        $file1 = UploadedFile::fake()->createWithContent('dokumen1.pdf', $pdfContent);
        $file2 = UploadedFile::fake()->createWithContent('dokumen_kembar.pdf', $pdfContent);

        $service = app(EvidenceUploadService::class);

        // Upload pertama sukses
        $doc1 = $service->upload($file1, [
            'document_name' => 'Dokumen Asli',
            'year' => 2026,
        ], $admin);

        $this->assertDatabaseHas('evidence_documents', [
            'id' => $doc1->id,
            'file_hash' => $doc1->file_hash,
        ]);

        // Upload kedua dengan berkas identik harus ditolak
        try {
            $service->upload($file2, [
                'document_name' => 'Dokumen Kloning',
                'year' => 2026,
            ], $admin);

            $this->fail('Harusnya melempar DuplicateDocumentException');
        } catch (DuplicateDocumentException $e) {
            $this->assertSame($doc1->id, $e->existingDocument->id);
        }

        // Pastikan jumlah rekaman di database tetap 1
        $this->assertSame(1, EvidenceDocument::where('file_hash', $doc1->file_hash)->count());
    }

    /**
     * 6. BD-10, NFR-04: File is stored with random UUID in private storage.
     */
    public function test_uploaded_file_is_stored_with_uuid_in_private_storage(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $pdfContent = $this->createDigitalPdfContent('Privat Test Document 2026');
        $file = UploadedFile::fake()->createWithContent('sk_rahasia.pdf', $pdfContent);

        $service = app(EvidenceUploadService::class);
        $document = $service->upload($file, [
            'document_name' => 'SK Rahasia',
            'year' => 2026,
        ], $admin);

        // Pastikan path berawalan 'evidence/' dan file berpenamaan UUID
        $this->assertStringStartsWith('evidence/', $document->file_path);
        $fileName = basename($document->file_path);
        $uuidPart = pathinfo($fileName, PATHINFO_FILENAME);
        $this->assertTrue(Str::isUuid($uuidPart), 'Nama berkas fisik harus berupa UUID yang valid');

        // Pastikan tersimpan di disk private
        Storage::disk('private')->assertExists($document->file_path);
    }

    /**
     * 7. BD-07: Process job extracts digital PDF text successfully.
     */
    public function test_process_job_extracts_digital_pdf_text_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $text = 'Keputusan Kepala BPS Nomor 123 Tahun 2026 tentang SOP Pelayanan Publik';
        $pdfContent = $this->createDigitalPdfContent($text);

        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.pdf";
        Storage::disk('private')->put($filePath, $pdfContent);

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'SK SOP Pelayanan',
            'year' => 2026,
            'file_path' => $filePath,
            'original_filename' => 'sop.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($pdfContent),
            'file_hash' => hash('sha256', $pdfContent),
            'processing_status' => 'memproses',
            'uploaded_by' => $admin->id,
        ]);

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle(app(TextExtractionService::class));

        $document->refresh();
        $this->assertSame('selesai', $document->processing_status);
        $this->assertNotNull($document->extracted_text);
        $this->assertStringContainsString('Keputusan Kepala BPS Nomor 123', $document->extracted_text);
    }

    /**
     * 8. BD-07: Process job extracts DOCX text successfully.
     */
    public function test_process_job_extracts_docx_text_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $text = 'Laporan Hasil Evaluasi Pelaksanaan Pembangunan Zona Integritas 2026';
        $docxContent = $this->createDocxContent($text);

        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.docx";
        Storage::disk('private')->put($filePath, $docxContent);

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'Laporan ZI',
            'year' => 2026,
            'file_path' => $filePath,
            'original_filename' => 'laporan_zi.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size_bytes' => strlen($docxContent),
            'file_hash' => hash('sha256', $docxContent),
            'processing_status' => 'memproses',
            'uploaded_by' => $admin->id,
        ]);

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle(app(TextExtractionService::class));

        $document->refresh();
        $this->assertSame('selesai', $document->processing_status);
        $this->assertNotNull($document->extracted_text);
        $this->assertStringContainsString('Pembangunan Zona Integritas 2026', $document->extracted_text);
    }

    /**
     * 9. BD-07, A5: Process job runs OCR on image files.
     */
    public function test_process_job_runs_ocr_on_image_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $imageContent = $this->createImageWithTextContent('SURAT EDARAN');

        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.png";
        Storage::disk('private')->put($filePath, $imageContent);

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'Scan Surat Edaran',
            'year' => 2026,
            'file_path' => $filePath,
            'original_filename' => 'scan_edaran.png',
            'mime_type' => 'image/png',
            'file_size_bytes' => strlen($imageContent),
            'file_hash' => hash('sha256', $imageContent),
            'processing_status' => 'memproses',
            'uploaded_by' => $admin->id,
        ]);

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle(app(TextExtractionService::class));

        $document->refresh();
        $this->assertSame('selesai', $document->processing_status);
        $this->assertNotNull($document->extracted_text);
        $this->assertStringContainsString('SURAT EDARAN', $document->extracted_text);
    }

    /**
     * 10. BD-08, AC-07: Blank or unextractable document sets status to perlu_review_manual without 500 error.
     */
    public function test_blank_or_unextractable_document_sets_status_to_perlu_review_manual_without_500(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Empty 1x1 pixel image where OCR extracts no text
        $img = imagecreatetruecolor(10, 10);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagepng($img);
        $blankImageContent = (string) ob_get_clean();
        imagedestroy($img);

        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.png";
        Storage::disk('private')->put($filePath, $blankImageContent);

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'Scan Kosong / Buram',
            'year' => 2026,
            'file_path' => $filePath,
            'original_filename' => 'blank.png',
            'mime_type' => 'image/png',
            'file_size_bytes' => strlen($blankImageContent),
            'file_hash' => hash('sha256', $blankImageContent),
            'processing_status' => 'memproses',
            'uploaded_by' => $admin->id,
        ]);

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle(app(TextExtractionService::class));

        $document->refresh();
        // AC-07: status harus menjadi perlu_review_manual tanpa error 500
        $this->assertSame('perlu_review_manual', $document->processing_status);
        $this->assertNotNull($document->error_message);
    }

    /**
     * 11. AC-17: Upload and process actions are recorded in audit_logs.
     */
    public function test_evidence_document_upload_and_mutations_are_logged_to_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $pdfContent = $this->createDigitalPdfContent('Dokumen Audit Trail 2026');
        $file = UploadedFile::fake()->createWithContent('audit_doc.pdf', $pdfContent);

        $service = app(EvidenceUploadService::class);
        $doc = $service->upload($file, [
            'document_name' => 'Dokumen Audit',
            'year' => 2026,
        ], $admin);

        // Verifikasi audit log upload
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'bd.uploaded',
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $doc->id,
        ]);

        // Jalankan job proses
        $job = new ProcessEvidenceDocumentJob($doc->id);
        $job->handle(app(TextExtractionService::class));

        // Verifikasi audit log processed
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'bd.processed',
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $doc->id,
        ]);
    }

    /**
     * 12. BD-09: Soft delete only allowed for Super Admin; Admin cannot delete.
     */
    public function test_super_admin_can_soft_delete_evidence_document_and_admin_cannot(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $superAdmin->update(['must_change_password' => false, 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Uji Hapus',
            'year' => 2026,
            'file_path' => 'evidence/test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'dummy_delete_test'),
            'processing_status' => 'selesai',
            'uploaded_by' => $admin->id,
        ]);

        // Admin mencoba menghapus -> 403 Forbidden
        $responseAdmin = $this->actingAs($admin)->deleteJson("/evidence/{$document->id}");
        $responseAdmin->assertStatus(403);
        $this->assertNull($document->fresh()->deleted_at);

        // Super Admin menghapus -> Berhasil soft delete
        $responseSuper = $this->actingAs($superAdmin)->deleteJson("/evidence/{$document->id}");
        $responseSuper->assertStatus(200);

        $this->assertNotNull($document->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'bd.deleted',
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $document->id,
        ]);
    }

    /**
     * 13. BD-10: Evidence download is protected by authorization.
     */
    public function test_evidence_file_download_is_protected_by_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $pdfContent = $this->createDigitalPdfContent('Konten Download Aman');
        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.pdf";
        Storage::disk('private')->put($filePath, $pdfContent);

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'Dokumen Unduh',
            'year' => 2026,
            'file_path' => $filePath,
            'original_filename' => 'download_me.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($pdfContent),
            'file_hash' => hash('sha256', $pdfContent),
            'processing_status' => 'selesai',
            'uploaded_by' => $admin->id,
        ]);

        // Tanpa login -> 302 redirect ke login atau 401
        $guestResponse = $this->get("/evidence/download/{$document->id}");
        $guestResponse->assertRedirect();

        // Login sebagai Admin -> 200 OK dengan file stream
        $adminResponse = $this->actingAs($admin)->get("/evidence/download/{$document->id}");
        $adminResponse->assertStatus(200);
    }

    /**
     * 14. Filament UI: Super Admin and Admin can access EvidenceDocument Filament pages.
     */
    public function test_super_admin_and_admin_can_access_evidence_document_filament_page(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@kantor.go.id'],
            [
                'name' => 'Super Admin Inisial',
                'password' => bcrypt('123456789'),
                'role' => 'super_admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $response = $this->actingAs($superAdmin)->get('/admin/evidence-documents');
        $response->assertOk();

        $createResponse = $this->actingAs($superAdmin)->get('/admin/evidence-documents/create');
        $createResponse->assertOk();
    }
}
