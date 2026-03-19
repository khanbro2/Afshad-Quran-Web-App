<?php

namespace App\Support;

use App\Models\Morphology;
use Illuminate\Support\Collection;

class PronounClassifier
{
    /**
     * @param  Collection<int, Morphology>  $analysisMorphologies
     * @return array{
     *   type:string,
     *   role:string,
     *   english_label_key:string,
     *   arabic_exact_label:string,
     *   urdu_label_key:string,
     *   confidence:string,
     *   reason:string
     * }
     */
    public static function classify(Morphology $morphology, Collection $analysisMorphologies, int $index): array
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        if (! $features->isPronoun()) {
            return [
                'type' => 'none',
                'role' => 'none',
                'english_label_key' => 'pronoun',
                'arabic_exact_label' => 'ضمير',
                'urdu_label_key' => 'pronoun',
                'confidence' => 'low',
                'reason' => 'segment-is-not-pronoun',
            ];
        }

        if (self::hasGoverningPreposition($analysisMorphologies, $index)) {
            return self::resolved('prepositional', 'attached-to-preposition');
        }

        if ($features->isDetachedPronoun()) {
            $role = BilingualGrammarService::pronounRole('detached');

            return [
                'type' => 'detached',
                'role' => 'none',
                'english_label_key' => $role['english'],
                'arabic_exact_label' => $role['arabic'],
                'urdu_label_key' => $role['urdu'],
                'confidence' => 'high',
                'reason' => 'stem-pronoun-without-host',
            ];
        }

        if (! $features->isAttachedPronoun()) {
            $role = BilingualGrammarService::pronounRole('attached');

            return [
                'type' => 'attached',
                'role' => 'none',
                'english_label_key' => $role['english'],
                'arabic_exact_label' => $role['arabic'],
                'urdu_label_key' => $role['urdu'],
                'confidence' => 'low',
                'reason' => 'pronoun-kind-unresolved',
            ];
        }

        $host = self::hostSegmentForPronoun($analysisMorphologies, $index);

        if ($host === null) {
            return self::attachedFallback('missing-host');
        }

        $hostFeatures = MorphologyFeatureExtractor::for($host);

        if ($hostFeatures->isNounLike()) {
            return self::resolved('possessive', 'noun-like-host');
        }

        if ($hostFeatures->posTag() === 'P') {
            return self::resolved('prepositional', 'direct-preposition-host');
        }

        if (! $hostFeatures->isVerb()) {
            return self::attachedFallback('non-verb-non-noun-like-host');
        }

        $hostIndex = $analysisMorphologies->search(fn (Morphology $segment) => $segment === $host);
        if (! is_int($hostIndex)) {
            return self::attachedFallback('host-index-not-found');
        }

        $pronounSegments = $analysisMorphologies
            ->slice($hostIndex + 1)
            ->filter(fn (Morphology $segment) => MorphologyFeatureExtractor::for($segment)->isPronoun())
            ->values();

        $currentPronounIndex = $pronounSegments->search(fn (Morphology $segment) => $segment === $morphology);
        if ($currentPronounIndex === false) {
            return self::attachedFallback('pronoun-not-in-host-chain');
        }

        if ($currentPronounIndex === 0 && self::isVerbSubjectSuffix($hostFeatures, $features)) {
            return self::resolved('subject', 'first-suffix-matches-verb-agreement');
        }

        if ($currentPronounIndex > 0) {
            return self::resolved('object', 'later-pronoun-after-verb-subject-slot');
        }

        if (self::isKnownObjectPronoun($features->pronounCode())) {
            return self::resolved('object', 'attached-pronoun-after-verb');
        }

        return self::attachedFallback('attached-pronoun-role-not-safely-known');
    }

    /**
     * @param  Collection<int, Morphology>  $analysisMorphologies
     */
    protected static function hostSegmentForPronoun(Collection $analysisMorphologies, int $index): ?Morphology
    {
        return $analysisMorphologies
            ->slice(0, $index)
            ->reverse()
            ->first(fn (Morphology $segment) => ! MorphologyFeatureExtractor::for($segment)->isPronoun());
    }

    /**
     * Walk backward through the same-word segment chain to find a governing preposition.
     * This handles corpus rows where an attached pronoun is preceded by a noun-like stem
     * that itself belongs to a prepositional construction, such as P + N + PRON.
     *
     * @param  Collection<int, Morphology>  $analysisMorphologies
     */
    protected static function hasGoverningPreposition(Collection $analysisMorphologies, int $index): bool
    {
        $segments = $analysisMorphologies->slice(0, $index)->values();

        if ($segments->isEmpty()) {
            return false;
        }

        foreach ($segments->reverse() as $segment) {
            $features = MorphologyFeatureExtractor::for($segment);

            if ($features->isPronoun()) {
                continue;
            }

            if ($features->posTag() === 'P') {
                return true;
            }

            if ($features->isVerb()) {
                return false;
            }
        }

        return false;
    }

    protected static function isVerbSubjectSuffix(MorphologyFeatureExtractor $host, MorphologyFeatureExtractor $pronoun): bool
    {
        $pronounCode = $pronoun->pronounCode();
        $verbCode = $host->personCode();
        $mood = $host->mood();

        if ($pronounCode === null || $verbCode === null || $pronounCode !== $verbCode) {
            return false;
        }

        return match (true) {
            $pronounCode === '3MP' && in_array($mood, ['IMPF', 'PERF'], true) => true,
            $pronounCode === '2MP' && in_array($mood, ['IMPV', 'IMPF', 'PERF', 'JUS'], true) => true,
            $pronounCode === '2MS' && $mood === 'PERF' => true,
            $pronounCode === '1S' && $mood === 'PERF' => true,
            $pronounCode === '1P' && $mood === 'PERF' => true,
            in_array($pronounCode, ['2FP', '3FP', '2D', '2MD', '2FD', '3D', '3MD', '3FD'], true) => true,
            default => false,
        };
    }

    protected static function isKnownObjectPronoun(?string $code): bool
    {
        return in_array($code, ['1S', '2MS', '2FS', '2MP', '2FP', '2D', '2MD', '2FD', '3MS', '3FS', '3MP', '3FP', '3D', '3MD', '3FD'], true);
    }

    /**
     * @return array{
     *   type:string,
     *   role:string,
     *   english_label_key:string,
     *   arabic_exact_label:string,
     *   urdu_label_key:string,
     *   confidence:string,
     *   reason:string
     * }
     */
    protected static function resolved(string $role, string $reason): array
    {
        $mapping = BilingualGrammarService::pronounRole($role);

        return [
            'type' => 'attached',
            'role' => $role,
            'english_label_key' => $mapping['english'],
            'arabic_exact_label' => $mapping['arabic'],
            'urdu_label_key' => $mapping['urdu'],
            'confidence' => 'high',
            'reason' => $reason,
        ];
    }

    /**
     * @return array{
     *   type:string,
     *   role:string,
     *   english_label_key:string,
     *   arabic_exact_label:string,
     *   urdu_label_key:string,
     *   confidence:string,
     *   reason:string
     * }
     */
    protected static function attachedFallback(string $reason): array
    {
        $mapping = BilingualGrammarService::pronounRole('attached');

        return [
            'type' => 'attached',
            'role' => 'none',
            'english_label_key' => $mapping['english'],
            'arabic_exact_label' => $mapping['arabic'],
            'urdu_label_key' => $mapping['urdu'],
            'confidence' => 'medium',
            'reason' => $reason,
        ];
    }
}
