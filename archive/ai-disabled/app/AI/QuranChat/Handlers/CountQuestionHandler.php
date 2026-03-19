<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class CountQuestionHandler implements QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['word_occurrence', 'exact_count'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        $counts = $evidence['counts'] ?? [];
        $matchedWords = array_values($evidence['matched_words'] ?? []);
        $topic = $intent->primaryEntity ?: implode(', ', array_slice($intent->resolvedTerms ?: $intent->terms, 0, 3));
        $topic = $topic !== '' ? $topic : 'is mawzu';
        $answer = (string) config('ai_assistant.not_found_message');

        if ($intent->type === 'word_occurrence' && ((int) ($counts['exact_word_occurrence_count'] ?? 0) > 0 || $matchedWords !== [])) {
            $modeLabel = match ($counts['mode'] ?? 'word') {
                'root' => 'root',
                'lemma' => 'lemma',
                default => 'lafz',
            };
            $wordCount = (int) ($counts['exact_word_occurrence_count'] ?? 0);

            if ($matchedWords !== []) {
                $aliases = config('ai_assistant.query_aliases.' . ($intent->primaryEntity ?: ''), []);
                $aliases = array_map('mb_strtolower', array_filter(array_map('strval', $aliases)));
                $preferred = array_values(array_filter($matchedWords, function (array $word) use ($aliases) {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $word['arabic'] ?? null,
                        $word['translation'] ?? null,
                        $word['lemma'] ?? null,
                        $word['root'] ?? null,
                    ])));

                    foreach ($aliases as $alias) {
                        if ($alias !== '' && str_contains($haystack, $alias)) {
                            return true;
                        }
                    }

                    return false;
                }));

                $source = $preferred !== [] ? $preferred : $matchedWords;
                $countsByFrequency = array_count_values(array_map(fn ($word) => (int) ($word['occurrence_count'] ?? 0), $source));
                arsort($countsByFrequency);
                $wordCount = (int) array_key_first($countsByFrequency);
            }

            $answer = $request->language === 'ur'
                ? "{$topic} ke {$modeLabel} ki exact {$wordCount} occurrences mili hain."
                : "I found {$wordCount} exact {$modeLabel} occurrences for {$topic}.";
        } elseif ((int) ($counts['exact_ayah_count'] ?? 0) > 0) {
            $surahText = ! empty($counts['surah_name']) ? ' ' . $counts['surah_name'] . ' mein' : '';
            $answer = $request->language === 'ur'
                ? "{$topic} se mutalliq{$surahText} exact {$counts['exact_ayah_count']} ayat mili hain aur {$counts['exact_surah_count']} surahon mein zikr aata hai."
                : "I found {$counts['exact_ayah_count']} ayahs related to {$topic}{$surahText}, spread across {$counts['exact_surah_count']} surahs.";
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
