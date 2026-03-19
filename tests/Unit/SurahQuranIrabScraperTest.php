<?php

namespace Tests\Unit;

use App\Models\Surah;
use App\Support\SurahQuranIrabScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurahQuranIrabScraperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_a_page_into_ayah_level_irab_rows(): void
    {
        Surah::query()->create([
            'number' => 2,
            'arabic_name' => 'البقرة',
            'english_name' => 'The Cow',
            'transliterated_name' => 'Al-Baqarah',
            'revelation_type' => 'Medinan',
            'total_ayahs' => 286,
        ]);

        $html = <<<'HTML'
<html><body>
<h1>إعراب الصفحة رقم 3</h1>
<p>سورة البقرة</p>
<p>6 - { إِنَّ الَّذِينَ كَفَرُوا }</p>
<p>"سواء": خبر مقدم مرفوع.</p>
<p>7 - { خَتَمَ اللَّهُ }</p>
<p>جملة "ختم الله" مستأنفة لا محل لها.</p>
</body></html>
HTML;

        $method = new \ReflectionMethod(SurahQuranIrabScraper::class, 'parsePage');
        $method->setAccessible(true);

        $rows = $method->invoke(null, $html, [
            'البقرة' => 2,
        ]);

        $this->assertSame([
            [
                'surah_number' => 2,
                'ayah_number' => 6,
                'text' => '"سواء": خبر مقدم مرفوع.',
            ],
            [
                'surah_number' => 2,
                'ayah_number' => 7,
                'text' => 'جملة "ختم الله" مستأنفة لا محل لها.',
            ],
        ], $rows);
    }
}
