<?php

namespace Tests\Unit;

use App\AI\Contracts\AIProvider;
use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\SourceBundle;
use App\AI\Enums\AIAssistantMode;
use App\AI\Prompts\AyahChatPrompt;
use App\AI\Retrieval\AyahSourceBundleBuilder;
use App\AI\Safety\AISafetyPolicy;
use App\AI\Services\AyahQuestionClassifier;
use App\AI\Services\AyahScopedChatService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AyahScopedChatServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_direct_translation_without_llm(): void
    {
        $builder = Mockery::mock(AyahSourceBundleBuilder::class);
        $builder->shouldReceive('build')->once()->andReturn($this->bundle());
        $builder->shouldReceive('hasUsableContext')->once()->andReturn(true);

        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldNotReceive('generate');

        $service = new AyahScopedChatService(
            $builder,
            app(AyahQuestionClassifier::class),
            app(AyahChatPrompt::class),
            app(AISafetyPolicy::class),
            $provider,
        );

        $response = $service->answer(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'is ayah ka Urdu tarjuma do'));

        $this->assertStringContainsString('Quran Foundation Urdu', $response->answer);
    }

    public function test_it_returns_direct_references_without_llm(): void
    {
        $builder = Mockery::mock(AyahSourceBundleBuilder::class);
        $builder->shouldReceive('build')->once()->andReturn($this->bundle());
        $builder->shouldReceive('hasUsableContext')->once()->andReturn(true);

        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldNotReceive('generate');

        $service = new AyahScopedChatService(
            $builder,
            app(AyahQuestionClassifier::class),
            app(AyahChatPrompt::class),
            app(AISafetyPolicy::class),
            $provider,
        );

        $response = $service->answer(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'sirf tafseer source names batao'));

        $this->assertStringContainsString('Tafaseer Sources', $response->answer);
        $this->assertStringContainsString('Ibn Kathir', $response->answer);
    }

    public function test_it_returns_source_wise_tafseer_fallback_when_provider_is_unavailable(): void
    {
        $builder = Mockery::mock(AyahSourceBundleBuilder::class);
        $builder->shouldReceive('build')->once()->andReturn($this->bundle());
        $builder->shouldReceive('hasUsableContext')->once()->andReturn(true);

        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldReceive('generate')->once()->andThrow(new RuntimeException('AI service unavailable'));

        $service = new AyahScopedChatService(
            $builder,
            app(AyahQuestionClassifier::class),
            app(AyahChatPrompt::class),
            app(AISafetyPolicy::class),
            $provider,
        );

        $response = $service->answer(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'is ayah ki tamam available tafaseer ko alag alag summarize karo aur akhir mein common point batao'));

        $this->assertStringContainsString('## Har Tafsir Alag', $response->answer);
        $this->assertStringContainsString('## Common Point', $response->answer);
        $this->assertStringContainsString('Ibn Kathir', $response->answer);
    }

    protected function bundle(): SourceBundle
    {
        return new SourceBundle(
            ayahId: 1,
            reference: 'Surah 1:1',
            surahName: 'The Opening',
            surahNumber: 1,
            ayahNumber: 1,
            arabicText: 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
            translations: [
                ['slug' => 'quran_foundation_urdu', 'label' => 'Quran Foundation Urdu', 'language' => 'ur', 'text' => 'اللہ کے نام سے'],
                ['slug' => 'mufti_taqi_english', 'label' => 'Mufti Taqi English', 'language' => 'en', 'text' => 'In the name of Allah'],
            ],
            tafasir: [
                ['slug' => 'ibnekaseer', 'label' => 'Ibn Kathir', 'text' => 'This ayah opens with the blessed name of Allah.'],
                ['slug' => 'maududi', 'label' => 'Maududi', 'text' => 'This ayah teaches beginning with the name of Allah.'],
            ],
            themes: [
                ['english' => 'Mercy', 'urdu' => 'رحمت', 'description' => 'Allah ki rehmat'],
            ],
            broadThemes: [],
            morphology: [
                ['position' => 1, 'word' => 'بِسْمِ', 'translation' => 'name', 'translation_urdu' => 'نام', 'transliteration' => 'bismi', 'root' => 'سمو', 'lemma' => 'اسم', 'parts_of_speech' => ['N'], 'morphology' => []],
            ],
            relatedAyahs: [],
            irab: 'مجرور و مضاف',
        );
    }
}
