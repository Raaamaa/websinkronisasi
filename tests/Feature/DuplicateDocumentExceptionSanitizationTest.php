<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuplicateDocumentExceptionSanitizationTest extends TestCase
{
    public function test_duplicate_upload_response_payload_is_sanitized_without_server_leakage(): void
    {
        Storage::fake('private');

        $actor = User::where('role', 'admin')->firstOrFail();

        $pdfContent = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Title (Dokumen Sanitasi) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('surat_tugas.pdf', $pdfContent);
        $fileHash = (string) hash_file('sha256', $file->getRealPath());

        // Pastikan dokumen bersih
        EvidenceDocument::where('file_hash', $fileHash)->forceDelete();

        // 1. Upload pertama (sukses 201)
        $response1 = $this->actingAs($actor)->postJson('/evidence/upload', [
            'document_name' => 'Surat Tugas Sanitasi Uji',
            'document_number' => 'ST-001/2026',
            'year' => 2026,
            'file' => $file,
        ]);
        $response1->assertStatus(201);
        $data201 = $response1->json('data');
        $this->assertArrayNotHasKey('file_path', $data201, 'Respons 201 dilarang memuat file_path');
        $this->assertArrayNotHasKey('extracted_text', $data201, 'Respons 201 dilarang memuat extracted_text');
        $this->assertArrayNotHasKey('uploaded_by', $data201, 'Respons 201 dilarang memuat uploaded_by');

        // 2. Upload kedua dengan berkas identik (harus ditolak 409)
        $response2 = $this->actingAs($actor)->postJson('/evidence/upload', [
            'document_name' => 'Surat Tugas Duplikat',
            'document_number' => 'ST-002/2026',
            'year' => 2026,
            'file' => $file,
        ]);

        $response2->assertStatus(409);
        $json = $response2->json();

        // Verifikasi informasi yang boleh ada
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('existing_document', $json);
        $this->assertArrayHasKey('id', $json['existing_document']);
        $this->assertArrayHasKey('document_name', $json['existing_document']);
        $this->assertArrayHasKey('document_number', $json['existing_document']);
        $this->assertArrayHasKey('year', $json['existing_document']);

        // Verifikasi ketat: TIDAK boleh membocorkan file_path, extracted_text, atau uploaded_by (Invarian #3, T20)
        $this->assertArrayNotHasKey('file_path', $json['existing_document'], 'file_path internal server dilarang bocor ke klien');
        $this->assertArrayNotHasKey('extracted_text', $json['existing_document'], 'extracted_text dilarang bocor ke payload');
        $this->assertArrayNotHasKey('uploaded_by', $json['existing_document'], 'uploaded_by dilarang bocor ke payload 409');
        $this->assertArrayNotHasKey('storage_disk', $json['existing_document']);
    }
}
