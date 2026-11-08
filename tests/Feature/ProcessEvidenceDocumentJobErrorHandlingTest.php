<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\TextExtractionService;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessEvidenceDocumentJobErrorHandlingTest extends TestCase
{
    public function test_matching_exception_updates_status_to_manual_review_and_logs_error(): void
    {
        $user = User::first() ?? User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'test_user_job@kantor.local',
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Uji Exception Matching',
            'year' => 2026,
            'file_path' => 'evidence/test_exception.pdf',
            'original_filename' => 'test_exception.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'test_exception_'.Str::random(10)),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        Storage::fake('private');
        Storage::disk('private')->put('evidence/test_exception.pdf', '%PDF-1.4 dummy pdf');

        // Mock MatchingEngineService to throw an exception
        $mockEngine = $this->createMock(MatchingEngineService::class);
        $mockEngine->expects($this->once())
            ->method('matchDocument')
            ->willThrowException(new \RuntimeException('Koneksi engine matching terputus secara tidak terduga!'));

        $this->app->instance(MatchingEngineService::class, $mockEngine);

        try {
            $job = new ProcessEvidenceDocumentJob($document->id);
            $job->handle(
                app(TextExtractionService::class),
                $mockEngine
            );
            $this->fail('Harusnya melempar RuntimeException dan tidak menelan error matching.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Koneksi engine matching terputus', $e->getMessage());
        }

        $document->refresh();
        $this->assertSame('perlu_review_manual', $document->processing_status);
        $this->assertNotNull($document->error_message);
        $this->assertStringContainsString('Koneksi engine matching terputus', (string) $document->error_message);
    }
}
