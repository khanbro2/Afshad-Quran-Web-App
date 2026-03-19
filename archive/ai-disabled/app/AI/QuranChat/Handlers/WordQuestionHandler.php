<?php

namespace App\AI\QuranChat\Handlers;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Contracts\QuranChatHandler;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class WordQuestionHandler implements QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool
    {
        return in_array($intent->type, ['word_detail', 'morphology_query', 'linguistic_summary_query'], true);
    }

    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData
    {
        $morphology = $evidence['morphology'] ?? null;
        $answer = (string) config('ai_assistant.not_found_message');

        if (is_array($morphology) && $morphology !== []) {
            if ($intent->type === 'word_detail') {
                $pos = ! empty($morphology['pos']) ? implode(', ', $morphology['pos']) : 'N/A';
                $answer = $request->language === 'ur'
                    ? "{$morphology['word']} ka root " . ($morphology['root'] ?: 'N/A') . ", lemma " . ($morphology['lemma'] ?: 'N/A') . " aur POS {$pos} hai."
                    : "{$morphology['word']} has root " . ($morphology['root'] ?: 'N/A') . ", lemma " . ($morphology['lemma'] ?: 'N/A') . ", and POS {$pos}.";
            } else {
                $parts = [];

                if (! empty($morphology['irab'])) {
                    $parts[] = 'I\'rab: ' . $morphology['irab'];
                }

                if (! empty($morphology['segments'])) {
                    $parts[] = 'Morphology: ' . implode(' | ', array_map(
                        fn ($segment) => trim(implode(', ', array_filter([
                            $segment['pos'] ?? null,
                            $segment['lemma'] ?? null,
                            $segment['root'] ?? null,
                            $segment['summary'] ?? null,
                        ]))),
                        array_slice($morphology['segments'], 0, 4)
                    ));
                } elseif (! empty($morphology['words'])) {
                    $parts[] = 'Words: ' . implode(' | ', array_map(
                        fn ($word) => trim(implode(', ', array_filter([
                            $word['word'] ?? null,
                            ! empty($word['pos']) ? implode('/', $word['pos']) : null,
                            $word['lemma'] ?? null,
                            $word['root'] ?? null,
                        ]))),
                        array_slice($morphology['words'], 0, 5)
                    ));
                }

                $answer = $parts !== [] ? implode(' ', $parts) : $answer;
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
