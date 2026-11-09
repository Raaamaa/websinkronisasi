<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Services\Matching\Rules\DeterministicMatchingRule;
use App\Services\Matching\TextPreprocessor;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeterministicMatchingRuleTest extends TestCase
{
    protected DeterministicMatchingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DeterministicMatchingRule(new TextPreprocessor);
    }

    public function test_short_indicator_code_does_not_trigger_confirmed_exact_code(): void
    {
        $indicator = new LkIndicator([
            'code' => '1',
            'title' => 'Indikator Tunggal Uji Batas',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect());

        $document = new EvidenceDocument([
            'document_name' => 'Laporan Pelaksanaan Kegiatan Bagian 1 Tahun 2026',
            'original_filename' => 'laporan_kegiatan_1.pdf',
        ]);
        $document->id = (string) Str::uuid();

        $match = $this->rule->evaluateExactCode($document, $indicator);

        $this->assertNull($match);
    }

    public function test_short_document_number_does_not_false_match_exact_number(): void
    {
        $indicator = new LkIndicator([
            'code' => 'TEST-01',
            'title' => 'Indikator dengan Dokumen B-105/2026 Tahun 2026',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();

        $item = new LkEvidenceItem([
            'indicator_id' => $indicator->id,
            'raw_template_name' => 'Surat Pengantar B-105/2026',
            'resolved_name' => 'Surat Pengantar B-105/2026',
            'order_index' => 1,
        ]);
        $item->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect([$item]));

        // Dokumen dengan nomor pendek "1" tidak boleh mencocokkan B-105/2026 atau "Tahun 2026"
        $document = new EvidenceDocument([
            'document_name' => 'Dokumen Biasa',
            'document_number' => '1',
            'original_filename' => 'dok.pdf',
        ]);
        $document->id = (string) Str::uuid();

        $match = $this->rule->evaluateExactNumber($document, $indicator);

        $this->assertNull($match);
    }

    public function test_exact_code_matches_valid_code_with_strict_boundary(): void
    {
        $indicator = new LkIndicator([
            'code' => 'ZI-1.1',
            'title' => 'Pembentukan Tim Kerja Pembangunan ZI',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect());

        $docValid = new EvidenceDocument([
            'document_name' => 'Surat Keputusan Tim Kerja ZI-1.1 WBK Tahun 2026',
            'original_filename' => 'SK_Tim_Kerja_ZI-1.1.pdf',
        ]);
        $docValid->id = (string) Str::uuid();

        $docSuffixMismatch = new EvidenceDocument([
            'document_name' => 'Surat Keputusan Tim ZI-1.10 WBK Tahun 2026',
            'original_filename' => 'SK_ZI-1.10.pdf',
        ]);
        $docSuffixMismatch->id = (string) Str::uuid();

        $matchValid = $this->rule->evaluateExactCode($docValid, $indicator);
        $matchInvalid = $this->rule->evaluateExactCode($docSuffixMismatch, $indicator);

        $this->assertNotNull($matchValid);
        $this->assertSame('confirmed', $matchValid->status);
        $this->assertSame('exact_code', $matchValid->matchMethod);
        $this->assertSame(100.0, $matchValid->confidenceScore);

        $this->assertNull($matchInvalid);
    }

    public function test_constants_defined_for_unmeasured_lengths_k2(): void
    {
        $this->assertSame(3, DeterministicMatchingRule::MIN_CODE_LENGTH);
        $this->assertSame(4, DeterministicMatchingRule::MIN_NUMBER_LENGTH);
    }
}
