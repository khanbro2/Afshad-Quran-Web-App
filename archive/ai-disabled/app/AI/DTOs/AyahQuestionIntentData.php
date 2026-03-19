<?php

namespace App\AI\DTOs;

readonly class AyahQuestionIntentData
{
    /**
     * @param  array<int, string>  $keywords
     */
    public function __construct(
        public string $type,
        public ?string $question,
        public array $keywords = [],
        public ?string $requestedSource = null,
        public ?string $requestedWord = null,
        public bool $wantsReferences = false,
        public bool $wantsSourceWiseBreakdown = false,
        public bool $requiresLlm = false,
    ) {
    }
}
