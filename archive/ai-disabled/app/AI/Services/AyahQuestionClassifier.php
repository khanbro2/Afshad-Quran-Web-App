<?php

namespace App\AI\Services;

use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\AyahQuestionIntentData;

class AyahQuestionClassifier
{
    public function detect(AIRequestData $request): AyahQuestionIntentData
    {
        $question = trim((string) $request->question);
        $normalized = $this->normalize($question);
        $keywords = $this->extractKeywords($normalized);

        $wantsReferences = $this->containsAny($normalized, ['reference', 'references', 'source', 'sources', 'hawala', 'hawalay', 'kis tafseer', 'kon kon si tafseer']);
        $wantsSourceWiseBreakdown = $this->containsAny($normalized, ['har tafseer', 'alag alag', 'source wise', 'mukhtalif tafaseer', 'tafseer comparison', 'tafaseer mein kya farq']);
        $requestedSource = $this->detectSource($normalized);
        $requestedWord = $this->detectRequestedWord($question);

        $type = match (true) {
            $this->containsAny($normalized, config('ai_assistant.safety.blocked_patterns', [])) => 'unsupported_query',
            $wantsReferences => 'reference_request',
            $wantsSourceWiseBreakdown => 'tafseer_comparison',
            $this->containsAny($normalized, ['tafseer', 'tafsir']) => 'tafseer_query',
            $this->containsAny($normalized, ['tarjuma', 'translation', 'urdu', 'english']) => 'translation_query',
            $this->containsAny($normalized, ['root', 'lemma', 'lafz ka matlab', 'word ka matlab', 'is lafz']) => 'word_meaning',
            $this->containsAny($normalized, ['morphology', 'morphological', 'i rab', 'irab', 'grammatical', 'grammar', 'pos']) => 'morphology_query',
            $this->containsAny($normalized, ['linguistic', 'morphology se', 'roots aur lemmas']) => 'linguistic_explanation',
            default => 'simple_explanation',
        };

        return new AyahQuestionIntentData(
            type: $type,
            question: $question !== '' ? $question : null,
            keywords: $keywords,
            requestedSource: $requestedSource,
            requestedWord: $requestedWord,
            wantsReferences: $wantsReferences,
            wantsSourceWiseBreakdown: $wantsSourceWiseBreakdown,
            requiresLlm: in_array($type, ['simple_explanation', 'tafseer_query', 'tafseer_comparison', 'linguistic_explanation'], true),
        );
    }

    protected function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    protected function extractKeywords(string $question): array
    {
        $stopwords = ['is', 'ayah', 'ayah', 'ka', 'ki', 'ke', 'kya', 'hai', 'mein', 'main', 'do', 'batao', 'dikhao', 'iska', 'is', 'ko', 'par'];

        return collect(explode(' ', $question))
            ->filter(fn ($term) => $term !== '' && mb_strlen($term) >= 2)
            ->reject(fn ($term) => in_array($term, $stopwords, true))
            ->unique()
            ->values()
            ->all();
    }

    protected function detectSource(string $question): ?string
    {
        foreach (config('ai_assistant.supported_sources.translations', []) as $key => $label) {
            if (str_contains($question, $this->normalize($key)) || str_contains($question, $this->normalize($label))) {
                return (string) $label;
            }
        }

        foreach (config('ai_assistant.supported_sources.tafseer', []) as $key => $label) {
            if (str_contains($question, $this->normalize($key)) || str_contains($question, $this->normalize($label))) {
                return (string) $label;
            }
        }

        return null;
    }

    protected function detectRequestedWord(string $question): ?string
    {
        if (preg_match('/["\']([^"\']+)["\']/u', $question, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $needles
     */
    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $this->normalize((string) $needle))) {
                return true;
            }
        }

        return false;
    }
}
