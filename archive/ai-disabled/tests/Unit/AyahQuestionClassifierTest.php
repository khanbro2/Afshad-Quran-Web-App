<?php

namespace Tests\Unit;

use App\AI\DTOs\AIRequestData;
use App\AI\Enums\AIAssistantMode;
use App\AI\Services\AyahQuestionClassifier;
use Tests\TestCase;

class AyahQuestionClassifierTest extends TestCase
{
    public function test_it_classifies_translation_query(): void
    {
        $classifier = app(AyahQuestionClassifier::class);
        $intent = $classifier->detect(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'is ayah ka Urdu tarjuma do'));

        $this->assertSame('translation_query', $intent->type);
    }

    public function test_it_classifies_tafseer_comparison_and_references(): void
    {
        $classifier = app(AyahQuestionClassifier::class);

        $comparison = $classifier->detect(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'har tafseer alag alag batao'));
        $references = $classifier->detect(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'kis kis tafseer ne is ayah ko explain kiya hai'));

        $this->assertSame('tafseer_comparison', $comparison->type);
        $this->assertSame('reference_request', $references->type);
    }

    public function test_it_classifies_word_and_morphology_queries(): void
    {
        $classifier = app(AyahQuestionClassifier::class);

        $word = $classifier->detect(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'is lafz ka root kya hai'));
        $morphology = $classifier->detect(new AIRequestData(1, 'ur', AIAssistantMode::Simple, [], [], 'is ayah ka grammatical breakdown do'));

        $this->assertSame('word_meaning', $word->type);
        $this->assertSame('morphology_query', $morphology->type);
    }
}
