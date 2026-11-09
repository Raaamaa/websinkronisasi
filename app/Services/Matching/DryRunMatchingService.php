<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Services\Evidence\TextExtractionService;
use App\Services\Matching\Rules\DeterministicMatchingRule;
use App\Services\Matching\Rules\HeuristicMatchingRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DryRunMatchingService
{
    public function __construct(
        protected DeterministicMatchingRule $deterministicRule,
        protected HeuristicMatchingRule $heuristicRule,
        protected TextExtractionService $textExtractor
    ) {}

    /**
     * Eksekusi simulasi matching cepat tanpa mengubah state basis data (dry-run).
     *
     * @return array{matched: bool, results: Collection<int, array>, existing_doc: ?EvidenceDocument}
     */
    public function evaluateUpload(UploadedFile $file): array
    {
        $sha256 = hash_file('sha256', (string) $file->getRealPath());

        // 1. Cek Cepat Hash SHA-256 Terhadap Berkas Master Aktif (Fast-Path ~2ms)
        $existingDoc = EvidenceDocument::query()
            ->where('file_hash', $sha256)
            ->whereNull('deleted_at')
            ->first();

        if ($existingDoc !== null) {
            // Jika file identik sudah ada, baca relasi match aktifnya langsung
            $existingMatches = $existingDoc->matches()
                ->with(['indicator.lembarKerja', 'matchedItem'])
                ->whereIn('status', ['auto', 'confirmed'])
                ->get();

            return [
                'matched' => $existingMatches->isNotEmpty(),
                'results' => $existingMatches->map(fn ($m) => [
                    'indicator_code' => $m->indicator->code,
                    'indicator_title' => $m->indicator->title,
                    'lk_name' => $m->indicator->lembarKerja->name,
                    'status' => $m->status,
                    'score' => (float) $m->confidence_score,
                    'match_item' => $m->matchedItem?->resolved_name,
                    'method' => $m->match_method,
                ]),
                'existing_doc' => $existingDoc,
            ];
        }

        // 2. Jika Berkas Baru: Ekstraksi Teks Ringan (Dibatasi Maksimal 3 Halaman OCR)
        $extractedText = $this->textExtractor->extract(
            (string) $file->getRealPath(),
            $file->getMimeType() ?: 'application/octet-stream',
            $file->getClientOriginalName()
        );

        // Buat instance model transient (tanpa save())
        $transientDoc = new EvidenceDocument([
            'document_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientOriginalName(),
            'year' => (int) date('Y'),
            'extracted_text' => $extractedText,
        ]);
        $transientDoc->id = (string) Str::uuid();

        $autoThreshold = (float) SystemSetting::get('matching.similarity_threshold', 75.0);
        $verifThreshold = (float) SystemSetting::get('matching.verification_threshold', 60.0);

        $indicators = LkIndicator::with(['evidenceItems', 'lembarKerja'])
            ->whereHas('lembarKerja', fn ($q) => $q->where('is_active', true))
            ->get();

        $candidates = collect();

        foreach ($indicators as $ind) {
            // Cek deterministic
            $exact = $this->deterministicRule->evaluate($transientDoc, $ind);
            if ($exact !== null) {
                $candidates->push([
                    'indicator_code' => $ind->code,
                    'indicator_title' => $ind->title,
                    'lk_name' => $ind->lembarKerja?->name,
                    'status' => $exact->status,
                    'score' => (float) $exact->confidenceScore,
                    'match_item' => $ind->evidenceItems->first()?->resolved_name,
                    'method' => $exact->matchMethod,
                ]);

                continue;
            }

            // Cek heuristic
            $heur = $this->heuristicRule->evaluate($transientDoc, $ind);
            if ($heur !== null && $heur->confidenceScore >= $verifThreshold) {
                $candidates->push([
                    'indicator_code' => $ind->code,
                    'indicator_title' => $ind->title,
                    'lk_name' => $ind->lembarKerja?->name,
                    'status' => $heur->confidenceScore >= $autoThreshold ? 'auto' : 'perlu_verifikasi',
                    'score' => $heur->confidenceScore,
                    'match_item' => $heur->matchedItemId ? $ind->evidenceItems->find($heur->matchedItemId)?->resolved_name : null,
                    'method' => $heur->matchMethod,
                ]);
            }
        }

        return [
            'matched' => $candidates->isNotEmpty(),
            'results' => $candidates->sortByDesc('score')->values(),
            'existing_doc' => null,
        ];
    }
}
