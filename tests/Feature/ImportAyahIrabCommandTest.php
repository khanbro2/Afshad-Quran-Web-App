<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportAyahIrabCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_ayah_level_irab_from_json(): void
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
            'irab_arabic' => null,
        ]);

        $path = storage_path('framework/testing/ayah-irab-import.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, json_encode([
            ['surah' => 1, 'ayah' => 1, 'irab' => 'بسم: جار ومجرور'],
        ], JSON_UNESCAPED_UNICODE));

        $this->artisan('app:import-ayah-irab', ['--file' => $path])
            ->expectsOutput("Ayah i'rab import complete.")
            ->expectsOutput('Parsed verses: 1')
            ->expectsOutput('Updated ayahs: 1')
            ->assertSuccessful();

        $ayah->refresh();

        $this->assertSame('بسم: جار ومجرور', $ayah->irab_arabic);
    }
}
