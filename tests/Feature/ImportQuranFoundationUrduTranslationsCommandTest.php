<?php

namespace Tests\Feature;

use App\Models\Ayah;
use App\Models\Surah;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportQuranFoundationUrduTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_ayah_and_word_level_urdu_translations_from_quran_foundation(): void
    {
        config()->set('services.quran_foundation.base_url', 'https://apis-prelive.quran.foundation/content/api/v4');
        config()->set('services.quran_foundation.auth_base_url', 'https://prelive-oauth2.quran.foundation');
        config()->set('services.quran_foundation.client_id', 'client-id');
        config()->set('services.quran_foundation.client_secret', 'client-secret');
        config()->set('services.quran_foundation.auth_token', null);
        config()->set('services.quran_foundation.urdu_translation_resource_id', 200);

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
            'urdu_translation' => null,
        ]);

        Word::query()->create([
            'ayah_id' => $ayah->id,
            'surah_number' => 1,
            'ayah_number' => 1,
            'position' => 1,
            'segment_count' => 1,
            'form' => 'bsm',
            'arabic_text' => 'بسم',
            'normalized_text' => 'بسم',
            'translation_basic' => 'In the name',
            'translation_urdu' => null,
        ]);

        Word::query()->create([
            'ayah_id' => $ayah->id,
            'surah_number' => 1,
            'ayah_number' => 1,
            'position' => 2,
            'segment_count' => 1,
            'form' => 'Allh',
            'arabic_text' => 'الله',
            'normalized_text' => 'الله',
            'translation_basic' => 'Allah',
            'translation_urdu' => null,
        ]);

        Http::fake([
            'https://prelive-oauth2.quran.foundation/oauth2/token' => Http::response([
                'access_token' => 'token-xyz',
                'expires_in' => 3600,
            ], 200),
            'https://apis-prelive.quran.foundation/content/api/v4/verses/by_key/1:1*' => Http::response([
                'verse' => [
                    'translations' => [
                        ['text' => 'شروع الله کے نام سے'],
                    ],
                    'words' => [
                        ['position' => 1, 'translation' => ['text' => 'نام سے']],
                        ['position' => 2, 'translation' => ['text' => 'الله']],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('app:import-quran-foundation-urdu', ['--surah' => 1, '--ayah' => 1])
            ->expectsOutput('Quran Foundation Urdu import complete.')
            ->assertSuccessful();

        $ayah->refresh();
        $words = $ayah->words()->orderBy('position')->get();

        $this->assertSame('شروع الله کے نام سے', $ayah->urdu_translation);
        $this->assertSame('نام سے', $words[0]->translation_urdu);
        $this->assertSame('الله', $words[1]->translation_urdu);
    }
}
