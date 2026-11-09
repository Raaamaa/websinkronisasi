<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Search\DocumentSearchService;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchWildcardAndModelHiddenTest extends TestCase
{
    protected string $docId;

    protected function tearDown(): void
    {
        if (isset($this->docId)) {
            EvidenceDocument::where('id', $this->docId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_search_escapes_ilike_wildcards_and_does_not_match_all_documents(): void
    {
        $user = User::where('role', 'super_admin')->firstOrFail();

        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Biasa Tanpa Simbol Persen',
            'document_number' => 'SK/123/2026',
            'year' => 2026,
            'file_path' => 'evidence/test_wildcard.pdf',
            'original_filename' => 'doc_wildcard.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_wildcard_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
            'extracted_text' => 'Teks dokumen rahasia kedinasan BPS',
        ]);
        $this->docId = $doc->id;

        $service = app(DocumentSearchService::class);

        // Pencarian dengan wildcard '%' murni tidak boleh mencocokkan dokumen yang tidak memiliki karakter '%'
        $results = $service->search('%%%%%');

        $matchedIds = $results->pluck('id')->all();
        $this->assertNotContains($doc->id, $matchedIds, 'Query ILIKE dengan simbol % tidak di-escape dan mencocokkan seluruh dokumen!');
    }

    public function test_evidence_document_model_hides_sensitive_extracted_text_and_file_path(): void
    {
        $doc = new EvidenceDocument([
            'document_name' => 'Dokumen Uji Hidden',
            'file_path' => 'private/secret/path.pdf',
            'extracted_text' => 'Konten teks ekstraksi OCR masif',
            'uploaded_by' => (string) Str::uuid(),
        ]);

        $array = $doc->toArray();

        $this->assertArrayNotHasKey('extracted_text', $array, 'extracted_text bocor di toArray()');
        $this->assertArrayNotHasKey('file_path', $array, 'file_path bocor di toArray()');
    }
}
