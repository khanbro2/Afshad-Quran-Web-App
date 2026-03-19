<?php

namespace App\AI\DTOs;

readonly class AIResponseData
{
    /**
     * @param  array<int, string>  $keyPoints
     * @param  array<int, array<string, string>>  $sourcesUsed
     */
    public function __construct(
        public string $answer,
        public array $keyPoints,
        public array $sourcesUsed,
        public bool $cached = false,
        public bool $refused = false,
        public ?string $safetyNote = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'key_points' => $this->keyPoints,
            'sources_used' => $this->sourcesUsed,
            'cached' => $this->cached,
            'safety' => [
                'refused' => $this->refused,
                'note' => $this->safetyNote,
            ],
        ];
    }
}
