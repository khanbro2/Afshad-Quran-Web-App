<?php

namespace App\AI\DTOs;

readonly class QuranChatRequestData
{
    public function __construct(
        public string $question,
        public string $language = 'ur',
    ) {
    }

    public function cacheKey(): string
    {
        return sha1(json_encode([
            'question' => trim($this->question),
            'language' => $this->language,
        ], JSON_THROW_ON_ERROR));
    }
}
