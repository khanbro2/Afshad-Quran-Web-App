<?php

namespace Tests\Unit;

use App\Support\QuranicCorpusIrabScraper;
use Tests\TestCase;

class QuranicCorpusIrabScraperTest extends TestCase
{
    public function test_it_parses_irab_text_from_quranic_corpus_grammar_html(): void
    {
        $html = <<<'HTML'
<html><body>
<h3>### Chapter (1) sūrat l-fātiḥah (The Opening)</h3>
<p>«بسم» الباء حرف جر، «اسم» اسم مجرور بالكسرة. والجار والمجرور متعلقان بخبر محذوف.</p>
<p>Quran Recitation by Saad Al-Ghamadi</p>
</body></html>
HTML;

        $this->assertSame(
            '«بسم» الباء حرف جر، «اسم» اسم مجرور بالكسرة. والجار والمجرور متعلقان بخبر محذوف.',
            QuranicCorpusIrabScraper::parseAyahIrab($html)
        );
    }
}
