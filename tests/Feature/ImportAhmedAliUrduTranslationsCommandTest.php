<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportAhmedAliUrduTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_ahmed_ali_urdu_translations_into_existing_ayah_rows(): void
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
            'full_arabic_text' => 'بسم الله',
            'simple_text' => 'بسم الله',
            'uthmani_text' => 'بِسْمِ اللَّهِ',
            'urdu_translation' => 'شروع الله کے نام سے',
            'urdu_translation_ahmedali' => null,
        ]);

        $path = storage_path('framework/testing/urdu-ahmedali-test.sql');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO `urdu_ahmedali` (sura, aya, text) VALUES (1, 1, 'شروع الله کا نام لے کر');
SQL);

        $this->artisan('app:import-urdu-ahmedali', ['--file' => $path])
            ->expectsOutput('Ahmed Ali Urdu import complete.')
            ->expectsOutput('Parsed verses: 1')
            ->expectsOutput('Updated ayahs: 1')
            ->assertSuccessful();

        $ayah->refresh();

        $this->assertSame('شروع الله کا نام لے کر', $ayah->urdu_translation_ahmedali);
        $this->assertSame('شروع الله کے نام سے', $ayah->urdu_translation);
    }
}
