<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class UnsupportedQuestionHandler implements QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['unsupported_or_weak_query', 'fallback_unknown'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        return new QuranChatResponseData(
            answer: (string) config('ai_assistant.not_found_message'),
            matchedAyahs: array_values($evidence['matched_ayahs'] ?? []),
            matchedWords: array_values($evidence['matched_words'] ?? []),
            matchedThemes: array_values($evidence['matched_themes'] ?? []),
            sourcesUsed: array_values($evidence['sources_used'] ?? []),
        );
    }
}
