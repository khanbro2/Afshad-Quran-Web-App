<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportKanzulImanAndMaududiUrduTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_kanzul_iman_urdu_translations_into_existing_ayah_rows(): void
    {
        $surah = $this->makeSurah();
        $ayah = $this->makeAyah($surah->id);

        $path = storage_path('framework/testing/urdu-kanzuliman-test.sql');
        $this->ensureDirectory($path);
        file_put_contents($path, <<<'SQL'
INSERT INTO `urdu_kanzuliman` (sura, aya, text) VALUES (1, 1, 'الله کے نام سے شروع جو نہایت مہربان رحم والا');
SQL);

        $this->artisan('app:import-urdu-kanzuliman', ['--file' => $path])
            ->expectsOutput('Kanzul Iman Urdu import complete.')
            ->expectsOutput('Parsed verses: 1')
            ->expectsOutput('Updated ayahs: 1')
            ->assertSuccessful();

        $ayah->refresh();

        $this->assertSame('الله کے نام سے شروع جو نہایت مہربان رحم والا', $ayah->urdu_translation_kanzuliman);
    }

    public function test_it_imports_maududi_urdu_translations_into_existing_ayah_rows(): void
    {
        $surah = $this->makeSurah();
        $ayah = $this->makeAyah($surah->id);

        $path = storage_path('framework/testing/urdu-maududi-test.sql');
        $this->ensureDirectory($path);
        file_put_contents($path, <<<'SQL'
INSERT INTO `urdu_maududi` (sura, aya, text) VALUES (1, 1, 'شروع اللہ کے نام سے جو بڑا مہربان اور نہایت رحم والا ہے');
SQL);

        $this->artisan('app:import-urdu-maududi', ['--file' => $path])
            ->expectsOutput('Maududi Urdu import complete.')
            ->expectsOutput('Parsed verses: 1')
            ->expectsOutput('Updated ayahs: 1')
            ->assertSuccessful();

        $ayah->refresh();

        $this->assertSame('شروع اللہ کے نام سے جو بڑا مہربان اور نہایت رحم والا ہے', $ayah->urdu_translation_maududi);
    }

    protected function makeSurah(): Surah
    {
        return Surah::query()->create([
            'number' => 1,
            'arabic_name' => 'الفاتحة',
            'english_name' => 'The Opening',
            'transliterated_name' => 'Al-Fatihah',
            'revelation_type' => 'Meccan',
            'total_ayahs' => 7,
        ]);
    }

    protected function makeAyah(int $surahId): Ayah
    {
        return Ayah::query()->create([
            'surah_id' => $surahId,
            'ayah_number' => 1,
            'full_arabic_text' => 'بسم الله',
            'simple_text' => 'بسم الله',
            'uthmani_text' => 'بِسْمِ اللَّهِ',
            'urdu_translation' => null,
            'urdu_translation_ahmedali' => null,
            'urdu_translation_kanzuliman' => null,
            'urdu_translation_maududi' => null,
        ]);
    }

    protected function ensureDirectory(string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
    }
}
