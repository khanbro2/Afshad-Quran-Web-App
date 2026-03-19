<?php

namespace Tests\Unit;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\QuranChat\Services\QuestionIntentService;
use Tests\TestCase;

class QuestionIntentServiceTest extends TestCase
{
    public function test_it_classifies_word_occurrence_queries(): void
    {
        $service = app(QuestionIntentService::class);
        $intent = $service->detect(new QuranChatRequestData('namaz ka lafz kitni bar aya hy'));

        $this->assertSame('word_occurrence', $intent->type);
        $this->assertSame('namaz', $intent->primaryEntity);
    }

    public function test_it_classifies_surah_scoped_queries(): void
    {
        $service = app(QuestionIntentService::class);
        $intent = $service->detect(new QuranChatRequestData('Surah Baqarah mein sabr ki ayat'));

        $this->assertSame('surah_scoped_query', $intent->type);
        $this->assertSame(2, $intent->surahNumber);
    }

    public function test_it_classifies_translation_and_tafseer_queries(): void
    {
        $service = app(QuestionIntentService::class);

        $translationIntent = $service->detect(new QuranChatRequestData('2:255 ka Urdu tarjuma'));
        $tafseerIntent = $service->detect(new QuranChatRequestData('2:255 ki tafseer batao'));

        $this->assertSame('translation_query', $translationIntent->type);
        $this->assertSame('tafseer_query', $tafseerIntent->type);
    }
}
