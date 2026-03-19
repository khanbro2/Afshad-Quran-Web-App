<?php

namespace App\AI\DTOs;

readonly class QuranChatResponseData
{
    /**
     * @param  array<int, array<string, mixed>>  $matchedAyahs
     * @param  array<int, array<string, mixed>>  $matchedWords
     * @param  array<int, array<string, mixed>>  $matchedThemes
     * @param  array<int, array<string, string>>  $sourcesUsed
     */
    public function __construct(
        public string $answer,
        public array $matchedAyahs,
        public array $matchedWords,
        public array $matchedThemes,
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
            'matched_ayahs' => $this->matchedAyahs,
            'matched_words' => $this->matchedWords,
            'matched_themes' => $this->matchedThemes,
            'sources_used' => $this->sourcesUsed,
            'cached' => $this->cached,
            'safety' => [
                'refused' => $this->refused,
                'note' => $this->safetyNote,
            ],
        ];
    }
}
