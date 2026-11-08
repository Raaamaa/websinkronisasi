<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MimeMagicBytesSniffingTest extends TestCase
{
    public function test_uploading_pdf_with_executable_extension_stores_safely_as_pdf(): void
    {
        Storage::fake('private');

        $actor = User::where('role', 'admin')->firstOrFail();

        $pdfContent = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Title (Dokumen PDF) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('laporan_palsu.exe', $pdfContent);
        $fileHash = (string) hash_file('sha256', $file->getRealPath());

        EvidenceDocument::where('file_hash', $fileHash)->forceDelete();

        $service = app(EvidenceUploadService::class);
        $metadata = [
            'document_name' => 'SK Uji Ekstensi Aman',
            'document_number' => 'SK-EXT-001',
            'year' => 2026,
        ];

        $document = $service->upload($file, $metadata, $actor);

        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame('laporan_palsu.exe', $document->original_filename);
        $this->assertStringEndsWith('.pdf', $document->file_path, 'File fisik harus berakhiran .pdf berbasis magic bytes, bukan .exe dari klien');
        $this->assertTrue(Storage::disk('private')->exists($document->file_path));
    }
}
