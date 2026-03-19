<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class TopicQuestionHandler implements QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['translation_query', 'tafseer_query', 'theme_query', 'comparison_query'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        $answer = (string) config('ai_assistant.not_found_message');

        if ($intent->type === 'translation_query' && is_array($evidence['translation'] ?? null)) {
            $translation = $evidence['translation'];
            $sources = [];

            foreach (($translation['sources'] ?? []) as $label => $text) {
                $sources[] = $label . ': ' . $text;
            }

            if ($sources !== []) {
                $answer = ($translation['reference'] ?? 'Ayah') . ' - ' . implode(' | ', array_slice($sources, 0, 3));
            }
        } elseif ($intent->type === 'tafseer_query' && is_array($evidence['tafseer'] ?? null)) {
            $tafseer = $evidence['tafseer'];
            $entries = array_map(
                fn ($entry) => ($entry['source'] ?? 'Tafseer') . ': ' . ($entry['excerpt'] ?? ''),
                array_slice($tafseer['entries'] ?? [], 0, 2)
            );

            if ($entries !== []) {
                $answer = ($tafseer['reference'] ?? 'Ayah') . ' - ' . implode(' | ', $entries);
            }
        } elseif ($intent->type === 'theme_query') {
            $themes = array_values($evidence['matched_themes'] ?? []);

            if ($themes !== []) {
                $titles = implode('، ', array_map(fn ($theme) => $theme['title_urdu'] ?: ($theme['title_english'] ?? ''), array_slice($themes, 0, 5)));
                $answer = $request->language === 'ur'
                    ? 'Relevant themes ye hain: ' . $titles
                    : 'Relevant themes are: ' . $titles;
            }
        } elseif ($intent->type === 'comparison_query' && is_array($evidence['comparison'] ?? null)) {
            $comparison = $evidence['comparison'];
            $ayahs = array_values($evidence['matched_ayahs'] ?? []);
            $references = implode('، ', array_map(fn ($ayah) => $ayah['reference'] ?? '', array_slice($ayahs, 0, 6)));

            if ($references !== '') {
                $answer = $request->language === 'ur'
                    ? "{$comparison['left']} aur {$comparison['right']} ke muqable ke liye strong references: {$references}"
                    : "Strong references for comparing {$comparison['left']} and {$comparison['right']}: {$references}";
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
