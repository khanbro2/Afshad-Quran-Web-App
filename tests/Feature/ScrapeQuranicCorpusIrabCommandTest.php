<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapeQuranicCorpusIrabCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scrapes_and_imports_irab_from_quranic_corpus(): void
    {
        $surah = Surah::query()->create([
            'number' => 1,
            'arabic_name' => 'الفاتحة',
            'english_name' => 'The Opening',
            'transliterated_name' => 'Al-Fatihah',
            'revelation_type' => 'Meccan',
            'total_ayahs' => 7,
        ]);

        Ayah::query()->create([
            'surah_id' => $surah->id,
            'ayah_number' => 1,
            'full_arabic_text' => 'بسم الله',
            'simple_text' => 'بسم الله',
            'uthmani_text' => 'بِسْمِ اللَّهِ',
            'irab_arabic' => null,
        ]);

        Http::fake([
            'https://corpus.quran.com/grammar.jsp*' => Http::response(<<<'HTML'
<html><body>
<h3>### Chapter (1) sūrat l-fātiḥah (The Opening)</h3>
<p>«بسم» الباء حرف جر، «اسم» اسم مجرور بالكسرة.</p>
<p>Quran Recitation by Saad Al-Ghamadi</p>
</body></html>
HTML, 200),
        ]);

        $this->artisan('app:scrape-corpus-irab', ['--surah' => 1, '--ayah' => 1])
            ->expectsOutput('Scraped 1:1')
            ->expectsOutput('Quranic Corpus i\'rab scrape complete.')
            ->assertSuccessful();

        $ayah = Ayah::query()->firstOrFail();

        $this->assertSame('«بسم» الباء حرف جر، «اسم» اسم مجرور بالكسرة.', $ayah->irab_arabic);
    }
}
