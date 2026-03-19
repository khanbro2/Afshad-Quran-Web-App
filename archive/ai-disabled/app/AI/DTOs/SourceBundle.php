<?php

namespace App\AI\DTOs;

readonly class SourceBundle
{
    /**
     * @param  array<int, array<string, mixed>>  $translations
     * @param  array<int, array<string, mixed>>  $tafasir
     * @param  array<int, array<string, mixed>>  $themes
     * @param  array<int, array<string, mixed>>  $broadThemes
     * @param  array<int, array<string, mixed>>  $morphology
     * @param  array<int, array<string, mixed>>  $relatedAyahs
     */
    public function __construct(
        public int $ayahId,
        public string $reference,
        public string $surahName,
        public int $surahNumber,
        public int $ayahNumber,
        public string $arabicText,
        public array $translations,
        public array $tafasir,
        public array $themes,
        public array $broadThemes,
        public array $morphology,
        public array $relatedAyahs = [],
        public ?string $irab = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPromptArray(): array
    {
        return [
            'ayah_id' => $this->ayahId,
            'reference' => $this->reference,
            'surah_name' => $this->surahName,
            'surah_number' => $this->surahNumber,
            'ayah_number' => $this->ayahNumber,
            'arabic_text' => $this->arabicText,
            'translations' => $this->translations,
            'tafasir' => $this->tafasir,
            'themes' => $this->themes,
            'broad_themes' => $this->broadThemes,
            'morphology' => $this->morphology,
            'related_ayahs' => $this->relatedAyahs,
            'irab' => $this->irab,
        ];
    }
}
