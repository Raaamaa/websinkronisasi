<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\VerificationQueuePage;
use Tests\TestCase;

class HighlightKeywordsHtmlSafetyTest extends TestCase
{
    public function test_highlight_keywords_escapes_xss_payload_without_executable_script(): void
    {
        $page = new VerificationQueuePage;
        $maliciousText = "<script>alert('xss')</script> Surat Keputusan";
        $keywords = ['alert', 'surat'];

        $result = $page->highlightKeywords($maliciousText, $keywords);

        // Karakter < dan > wajib di-escape menjadi &lt; dan &gt;
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);

        // Keyword alert harus di-mark dengan aman
        $this->assertStringContainsString('<mark class="bg-amber-200 text-amber-900 px-1 py-0.5 rounded font-semibold">alert</mark>', $result);
    }

    public function test_highlight_keywords_does_not_corrupt_mark_tags_when_keyword_matches_html_attribute(): void
    {
        $page = new VerificationQueuePage;
        $text = 'Dokumen surat keputusan tim kerja reformasi birokrasi';
        // Keywords mengandung kata 'amber', 'mark', 'bg', 'class' yang ada di tag mark
        $keywords = ['surat', 'amber', 'mark', 'class', 'bg'];

        $result = $page->highlightKeywords($text, $keywords);

        // Tidak boleh ada tag mark bersarang yang rusak seperti <mark class="<mark...
        $this->assertStringNotContainsString('<mark class="bg-<mark', $result);
        $this->assertStringNotContainsString('<<mark', $result);
        $this->assertMatchesRegularExpression('/^((?!<mark[^>]*<mark).)*$/s', $result, 'Ditemukan tag mark bersarang yang merusak HTML!');
    }
}

