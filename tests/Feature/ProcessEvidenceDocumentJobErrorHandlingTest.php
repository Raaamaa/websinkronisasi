<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Services\Evidence\TextExtractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class ProcessEvidenceDocumentJobErrorHandlingTest extends TestCase
{
    public function test_job_handle_accepts_only_text_extraction_service_parameter(): void
    {
        $reflection = new ReflectionMethod(ProcessEvidenceDocumentJob::class, 'handle');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(TextExtractionService::class, $params[0]->getType()?->getName());
    }

    public function test_extraction_exception_logs_error_and_updates_status_to_manual_review(): void
    {
        Log::spy();

        $user = User::first() ?? User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test User Extraction',
            'email' => 'test_extraction@kantor.local',
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Uji Exception Ekstraksi',
            'year' => 2026,
            'file_path' => 'evidence/test_extract_err.pdf',
            'original_filename' => 'test_extract_err.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'test_extract_err_'.Str::random(10)),
            'processing_status' => 'memproses',
            'uploaded_by' => $user->id,
        ]);

        Storage::fake('private');
        Storage::disk('private')->put('evidence/test_extract_err.pdf', '%PDF-1.4 dummy pdf');

        $mockExtractor = $this->createMock(TextExtractionService::class);
        $mockExtractor->expects($this->once())
            ->method('extract')
            ->willThrowException(new \RuntimeException('Parser corrupt error'));

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle($mockExtractor);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($document): bool {
                return str_contains($message, 'ProcessEvidenceDocumentJob extraction failure: Parser corrupt error')
                    && ($context['document_id'] ?? null) === $document->id
                    && isset($context['exception'])
                    && $context['exception'] instanceof \Throwable;
            });

        $document->refresh();
        $this->assertSame('perlu_review_manual', $document->processing_status);
        $this->assertNotNull($document->error_message);
        $this->assertStringContainsString('Kegagalan pemrosesan dokumen: Parser corrupt error', (string) $document->error_message);
    }

    public function test_empty_extraction_text_logs_warning_and_updates_status_to_manual_review(): void
    {
        Log::spy();

        $user = User::first() ?? User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test User Empty Extract',
            'email' => 'test_empty_extract@kantor.local',
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Uji Teks Kosong',
            'year' => 2026,
            'file_path' => 'evidence/test_empty.pdf',
            'original_filename' => 'test_empty.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'test_empty_'.Str::random(10)),
            'processing_status' => 'memproses',
            'uploaded_by' => $user->id,
        ]);

        Storage::fake('private');
        Storage::disk('private')->put('evidence/test_empty.pdf', '%PDF-1.4 dummy pdf');

        $mockExtractor = $this->createMock(TextExtractionService::class);
        $mockExtractor->expects($this->once())
            ->method('extract')
            ->willReturn('');

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle($mockExtractor);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($document): bool {
                return ($context['document_id'] ?? null) === $document->id
                    && ($context['mime_type'] ?? null) === 'application/pdf';
            });

        $document->refresh();
        $this->assertSame('perlu_review_manual', $document->processing_status);
        $this->assertNotNull($document->error_message);
        $this->assertStringContainsString('Teks tidak dapat diekstrak secara otomatis', (string) $document->error_message);
    }
}
