<?php

namespace App\AI\QuranChat\Services;

use App\AI\QuranChat\DTOs\QuestionIntentData;

class RelevanceGate
{
    public function allowsSummary(QuestionIntentData $intent, array $evidence): bool
    {
        $score = (int) ($evidence['confidence_score'] ?? 0);
        $matchedAyahs = count($evidence['matched_ayahs'] ?? []);
        $hasEntity = $intent->primaryEntity !== null || $intent->resolvedEntities !== [];

        if (! $hasEntity) {
            return false;
        }

        if ($matchedAyahs < (int) config('ai_assistant.thresholds.summary_min_ayahs', 2)) {
            return false;
        }

        return $score >= (int) config('ai_assistant.thresholds.strong_evidence', 10);
    }
}
