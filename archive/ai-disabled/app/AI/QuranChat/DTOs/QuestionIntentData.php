<?php

namespace App\AI\QuranChat\DTOs;

readonly class QuestionIntentData
{
    /**
     * @param  array<int, string>  $terms
     * @param  array<int, string>  $resolvedTerms
     * @param  array<int, array<string, mixed>>  $resolvedEntities
     */
    public function __construct(
        public string $type,
        public array $terms,
        public array $resolvedTerms,
        public array $resolvedEntities,
        public string $question,
        public ?string $primaryEntity = null,
        public ?string $secondaryEntity = null,
        public ?int $surahNumber = null,
        public ?int $ayahNumber = null,
        public bool $requiresLlm = false,
        public bool $evidenceOnly = false,
    ) {
    }
}
