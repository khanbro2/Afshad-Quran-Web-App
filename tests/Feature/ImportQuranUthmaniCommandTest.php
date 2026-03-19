<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportQuranUthmaniCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_uthmani_text_into_existing_ayah_rows(): void
    {
        $surah = Surah::query()->create([
            'number' => 1,
            'arabic_name' => 'الفاتحة',
            'english_name' => 'The Opening',
            'transliterated_name' => 'Al-Fatihah',
            'revelation_type' => 'Meccan',
            'total_ayahs' => 7,
        ]);

        $ayah = Ayah::query()->create([
            'surah_id' => $surah->id,
            'ayah_number' => 1,
            'full_arabic_text' => 'بسم الله الرحمن الرحيم',
            'simple_text' => 'بسم الله الرحمن الرحيم',
            'uthmani_text' => null,
        ]);

        $path = storage_path('framework/testing/quran-uthmani-test.sql');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `quran_text` (`index`, `sura`, `aya`, `text`) VALUES
(1, 1, 1, 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ');
SQL);

        $this->artisan('app:import-quran-uthmani', ['--file' => $path])
            ->expectsOutput('Tanzil Uthmani import complete.')
            ->expectsOutput('Parsed verses: 1')
            ->expectsOutput('Updated ayahs: 1')
            ->assertSuccessful();

        $ayah->refresh();

        $this->assertSame('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', $ayah->uthmani_text);
        $this->assertSame('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', $ayah->display_text);
        $this->assertSame('بسم الله الرحمن الرحيم', $ayah->full_arabic_text);
    }
}
