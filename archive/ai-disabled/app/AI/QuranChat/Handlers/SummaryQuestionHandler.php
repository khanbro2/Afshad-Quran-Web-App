<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\Contracts\AIProvider;
use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\Prompts\QuranChatPrompt;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;
use App\AI\QuranChat\Services\RelevanceGate;

class SummaryQuestionHandler implements QuranChatHandler
{
    public function __construct(
        protected QuranChatPrompt $promptBuilder,
        protected AIProvider $provider,
        protected RelevanceGate $relevanceGate,
    ) {
    }

    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['topic_entity_query', 'summary_explanation', 'evidence_based_answer'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        $answer = (string) config('ai_assistant.not_found_message');
        $context = trim((string) ($evidence['context'] ?? ''));

        if ($context !== '' && $this->relevanceGate->allowsSummary($intent, $evidence)) {
            $messages = $this->promptBuilder->build($request, $context);
            $generated = $this->provider->generate($messages);
            $answer = trim((string) ($generated['answer'] ?? ''));

            if ($answer === '') {
                $answer = (string) config('ai_assistant.not_found_message');
            }
        }

        return new QuranChatResponseData(
            answer: $answer,
            matchedAyahs: array_values($evidence['matched_ayahs'] ?? []),
            matchedWords: array_values($evidence['matched_words'] ?? []),
            matchedThemes: array_values($evidence['matched_themes'] ?? []),
            sourcesUsed: array_values($evidence['sources_used'] ?? []),
        );
    }
}
