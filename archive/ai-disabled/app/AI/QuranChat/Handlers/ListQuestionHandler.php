<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class ListQuestionHandler implements QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['ayah_list', 'surah_scoped_query', 'chronology_or_story_query', 'cross_reference_query', 'relation_query'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        $ayahs = array_values($evidence['matched_ayahs'] ?? []);
        $answer = (string) config('ai_assistant.not_found_message');

        if ($ayahs !== []) {
            $references = implode('، ', array_map(fn ($ayah) => $ayah['reference'] ?? '', array_slice($ayahs, 0, 8)));
            $topic = $intent->primaryEntity ?: 'is mawzu';

            $answer = match ($intent->type) {
                'relation_query' => $request->language === 'ur'
                    ? "{$intent->primaryEntity} aur {$intent->secondaryEntity} in ayat mein saath milte hain: {$references}"
                    : "{$intent->primaryEntity} and {$intent->secondaryEntity} appear together in: {$references}",
                'surah_scoped_query' => $request->language === 'ur'
                    ? "Surah scope ke andar relevant ayat ye hain: {$references}"
                    : "Within the requested surah, the relevant ayahs are: {$references}",
                default => $request->language === 'ur'
                    ? "{$topic} se related ayat ye hain: {$references}"
                    : "The relevant ayahs for {$topic} are: {$references}",
            };
        }

        return new QuranChatResponseData(
            answer: $answer,
            matchedAyahs: $ayahs,
            matchedWords: array_values($evidence['matched_words'] ?? []),
            matchedThemes: array_values($evidence['matched_themes'] ?? []),
            sourcesUsed: array_values($evidence['sources_used'] ?? []),
        );
    }
}
