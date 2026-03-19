<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapeAyahIrabCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scrapes_and_imports_irab_from_surahquran_pages(): void
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
            'https://surahquran.com/quran-expressed/1.html' => Http::response(<<<'HTML'
<html><body>
<h1>إعراب سورة الفاتحة</h1>
<p>سورة الفاتحة</p>
<p>1 - { بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ }</p>
<p>"بسم": الباء حرف جر، "اسم" اسم مجرور بالكسرة.</p>
</body></html>
HTML, 200),
        ]);

        $this->artisan('app:scrape-ayah-irab', ['--from-page' => 1, '--to-page' => 1])
            ->expectsOutput('Scraped page 1. Ayahs found: 1. Running total: 1.')
            ->expectsOutput("Ayah i'rab scrape/import complete.")
            ->assertSuccessful();

        $ayah = Ayah::query()->firstOrFail();

        $this->assertSame('"بسم": الباء حرف جر، "اسم" اسم مجرور بالكسرة.', $ayah->irab_arabic);
    }
}
