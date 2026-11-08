<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DuplicateDocumentException;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceUploadRaceConditionTest extends TestCase
{
    protected ?string $winnerId = null;

    protected function tearDown(): void
    {
        if ($this->winnerId !== null) {
            DB::connection('pgsql_race')->table('evidence_documents')->where('id', $this->winnerId)->delete();
            $this->winnerId = null;
        }

        // Hapus event listeners agar tidak bocor ke tes lain
        EvidenceDocument::flushEventListeners();

        parent::tearDown();
    }

    public function test_concurrent_upload_race_condition_handles_unique_violation_and_cleans_orphan_file(): void
    {
        Storage::fake('private');

        $actor = User::where('role', 'admin')->firstOrFail();

        $pdfContent = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Title (Dokumen Race Condition) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('surat_keputusan.pdf', $pdfContent);
        $fileHash = (string) hash_file('sha256', $file->getRealPath());

        // Pastikan dokumen belum ada sebelum tes
        EvidenceDocument::where('file_hash', $fileHash)->forceDelete();

        $service = app(EvidenceUploadService::class);
        $metadata = [
            'document_name' => 'SK Uji Race Condition',
            'document_number' => 'SK-RACE-002',
            'year' => 2026,
        ];

        // Simulasi race condition: Tepat setelah inspectAndValidate lolos, koneksi pgsql_race
        // menyisipkan dokumen dengan file_hash identik dan langsung melakukan commit.
        EvidenceDocument::creating(function (EvidenceDocument $doc) use ($actor): void {
            $this->winnerId = (string) Str::uuid();

            DB::connection('pgsql_race')->table('evidence_documents')->insert([
                'id' => $this->winnerId,
                'document_name' => 'Dokumen Pemenang Race',
                'document_number' => 'SK-RACE-WINNER',
                'year' => 2026,
                'file_path' => 'evidence/winner.pdf',
                'original_filename' => 'winner.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1024,
                'file_hash' => $doc->file_hash,
                'processing_status' => 'selesai',
                'uploaded_by' => $actor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $service->upload($file, $metadata, $actor);
            $this->fail('Seharusnya melempar DuplicateDocumentException saat race condition terjadi');
        } catch (DuplicateDocumentException $e) {
            // Verifikasi bahwa existingDocument pada exception TIDAK null dan hash sama persis
            $this->assertNotNull($e->existingDocument, 'Existing document pada DuplicateDocumentException tidak boleh null');
            $this->assertSame($this->winnerId, $e->existingDocument->id);
            $this->assertSame($fileHash, $e->existingDocument->file_hash);

            // Verifikasi tidak ada berkas fisik yatim yang tersisa selain berkas pemenang
            $files = Storage::disk('private')->allFiles('evidence');
            $this->assertCount(0, $files, 'Berkas fisik yatim milik upload yang kalah harus dihapus bersih');
        }
    }

    /**
     * Membuktikan bahwa mengunggah ulang dokumen dengan file_hash yang sama setelah dokumen lama
     * di-soft-delete adalah DIPERBOLEHKAN (T8, partial unique index WHERE deleted_at IS NULL).
     */
    public function test_reuploading_soft_deleted_document_file_hash_succeeds(): void
    {
        Storage::fake('private');

        $actor = User::where('role', 'admin')->firstOrFail();

        $pdfContent = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Title (Dokumen Soft Delete Reupload) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('surat_reupload.pdf', $pdfContent);
        $fileHash = (string) hash_file('sha256', $file->getRealPath());

        EvidenceDocument::where('file_hash', $fileHash)->forceDelete();

        $service = app(EvidenceUploadService::class);
        $metadata = [
            'document_name' => 'Dokumen Awal Soft Delete',
            'document_number' => 'SK-REUPLOAD-001',
            'year' => 2026,
        ];

        // 1. Upload awal
        $doc1 = $service->upload($file, $metadata, $actor);
        $this->assertNotNull($doc1);
        $this->assertNull($doc1->deleted_at);

        // 2. Soft-delete dokumen awal
        $doc1->delete();
        $this->assertNotNull(EvidenceDocument::withTrashed()->find($doc1->id)->deleted_at);

        // 3. Upload ulang dokumen dengan hash identik harus SUKSES tanpa exception
        $file2 = UploadedFile::fake()->createWithContent('surat_reupload.pdf', $pdfContent);
        $doc2 = $service->upload($file2, [
            'document_name' => 'Dokumen Re-upload Baru',
            'document_number' => 'SK-REUPLOAD-002',
            'year' => 2026,
        ], $actor);

        $this->assertNotNull($doc2);
        $this->assertNotEquals($doc1->id, $doc2->id);
        $this->assertSame($fileHash, $doc2->file_hash);
        $this->assertNull($doc2->deleted_at);
    }
}
