<?php

namespace App\AI\DTOs;

use App\AI\Enums\AIAssistantMode;

readonly class AIRequestData
{
    /**
     * @param  array<int, string>  $translationSlugs
     * @param  array<int, string>  $tafsirSlugs
     */
    public function __construct(
        public int $ayahId,
        public string $language,
        public AIAssistantMode $mode,
        public array $translationSlugs,
        public array $tafsirSlugs,
        public ?string $question = null,
    ) {
    }

    public function cacheKey(): string
    {
        return sha1(json_encode([
            'ayah_id' => $this->ayahId,
            'language' => $this->language,
            'mode' => $this->mode->value,
            'translation_slugs' => array_values($this->translationSlugs),
            'tafsir_slugs' => array_values($this->tafsirSlugs),
            'question' => trim((string) $this->question),
        ], JSON_THROW_ON_ERROR));
    }
}
