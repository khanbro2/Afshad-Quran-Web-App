<?php

namespace App\AI\QuranChat\Services;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\QuranChat\DTOs\QuestionIntentData;

class QuestionIntentService
{
    public function __construct(
        protected EntityResolver $resolver,
    ) {
    }

    public function detect(QuranChatRequestData $request): QuestionIntentData
    {
        $question = trim($request->question);
        $normalizedQuestion = $this->resolver->normalize($question);
        $terms = $this->resolver->extractTerms($question);
        $resolvedEntities = $this->resolver->resolve($question, $terms);
        $resolvedTerms = $this->resolver->expandSearchTerms($resolvedEntities, $terms);
        $surahNumber = $this->resolver->detectSurahNumber($question);
        $ayahReference = $this->resolver->detectAyahReference($question);
        $type = $this->detectType($normalizedQuestion, $surahNumber, $ayahReference, $resolvedEntities);

        return new QuestionIntentData(
            type: $type,
            terms: $terms,
            resolvedTerms: $resolvedTerms,
            resolvedEntities: $resolvedEntities,
            question: $question,
            primaryEntity: $resolvedEntities[0]['canonical'] ?? null,
            secondaryEntity: $resolvedEntities[1]['canonical'] ?? null,
            surahNumber: $ayahReference['surah_number'] ?? $surahNumber,
            ayahNumber: $ayahReference['ayah_number'] ?? null,
            requiresLlm: in_array($type, ['summary_explanation', 'topic_entity_query', 'comparison_query', 'linguistic_summary_query', 'evidence_based_answer'], true),
            evidenceOnly: in_array($type, ['summary_explanation', 'topic_entity_query', 'evidence_based_answer'], true),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $resolvedEntities
     * @param  array{surah_number:int,ayah_number:int}|null  $ayahReference
     */
    protected function detectType(string $question, ?int $surahNumber, ?array $ayahReference, array $resolvedEntities): string
    {
        if ($this->containsAny($question, config('ai_assistant.safety.blocked_patterns', []))) {
            return 'unsupported_or_weak_query';
        }

        if ($this->containsAny($question, ['root kya', 'lemma kya', 'is lafz', 'is word', 'word detail'])) {
            return 'word_detail';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.morphology_query', []))) {
            return 'morphology_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.translation_query', []))) {
            return 'translation_query';
        }

        if ($this->containsAny($question, ['tafseer', 'tafsir']) || ($ayahReference !== null && $this->containsAny($question, ['samjhao']))) {
            return 'tafseer_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.theme_query', []))) {
            return 'theme_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.comparison_query', [])) && count($resolvedEntities) >= 2) {
            return 'comparison_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.relation_query', [])) && count($resolvedEntities) >= 2) {
            return 'relation_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.chronology_or_story_query', []))) {
            return 'chronology_or_story_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.cross_reference_query', []))) {
            return 'cross_reference_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.linguistic_summary_query', []))) {
            return 'linguistic_summary_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.evidence_based_answer', []))) {
            return 'evidence_based_answer';
        }

        if ($this->isWordOccurrenceQuestion($question)) {
            return 'word_occurrence';
        }

        if ($this->isExactCountQuestion($question)) {
            return $surahNumber !== null ? 'surah_scoped_query' : 'exact_count';
        }

        if ($this->isAyahListQuestion($question)) {
            return $surahNumber !== null ? 'surah_scoped_query' : 'ayah_list';
        }

        if ($surahNumber !== null && count($resolvedEntities) > 0) {
            return 'surah_scoped_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.topic_entity_query', [])) && count($resolvedEntities) > 0) {
            return 'topic_entity_query';
        }

        if ($this->containsAny($question, config('ai_assistant.intent_keywords.summary_explanation', [])) && count($resolvedEntities) > 0) {
            return 'summary_explanation';
        }

        if (count($resolvedEntities) > 0) {
            return 'summary_explanation';
        }

        return 'fallback_unknown';
    }

    protected function isWordOccurrenceQuestion(string $question): bool
    {
        return $this->containsAny($question, config('ai_assistant.intent_keywords.word_occurrence', []))
            || ($this->containsAny($question, ['root', 'lemma']) && $this->containsAny($question, ['kitni', 'count', 'martaba', 'dafa']));
    }

    protected function isExactCountQuestion(string $question): bool
    {
        return $this->containsAny($question, ['kitni ayat', 'kitni ayaat', 'kitni jagah', 'kitna zikr', 'how many', 'count']);
    }

    protected function isAyahListQuestion(string $question): bool
    {
        return $this->containsAny($question, ['ayat batao', 'ayat dikhao', 'list', 'kin ayat', 'kahan aya', 'references']);
    }

    /**
     * @param  array<int, string>  $needles
     */
    protected function containsAny(string $question, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = $this->resolver->normalize((string) $needle);

            if ($needle !== '' && str_contains($question, $needle)) {
                return true;
            }
        }

        return false;
    }
}
