<?php

namespace App\AI\Retrieval;

use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\SourceBundle;
use App\Models\Ayah;
use App\Services\TafseerService;
use App\Support\QuranText;

class AyahSourceBundleBuilder
{
    public function __construct(
        protected TafseerService $tafseerService,
    ) {
    }

    public function build(AIRequestData $request): SourceBundle
    {
        $ayah = Ayah::query()
            ->with([
                'surah',
                'themes',
                'broadThemes',
                'words.root',
                'words.lemma',
                'words.morphologies',
            ])
            ->findOrFail($request->ayahId);

        $translations = collect([
            ['slug' => 'quran_foundation_urdu', 'label' => 'Quran Foundation Urdu', 'language' => 'ur', 'text' => $ayah->urdu_translation],
            ['slug' => 'ahmed_ali_urdu', 'label' => 'Ahmed Ali Urdu', 'language' => 'ur', 'text' => $ayah->urdu_translation_ahmedali],
            ['slug' => 'kanzul_iman_urdu', 'label' => 'Kanzul Iman Urdu', 'language' => 'ur', 'text' => $ayah->urdu_translation_kanzuliman],
            ['slug' => 'maududi_urdu', 'label' => 'Tafhim ul Quran', 'language' => 'ur', 'text' => $ayah->urdu_translation_maududi],
            ['slug' => 'mufti_taqi_urdu', 'label' => 'Mufti Taqi Urdu', 'language' => 'ur', 'text' => $ayah->urdu_translation_mufti_taqi],
            ['slug' => 'mufti_taqi_english', 'label' => 'Mufti Taqi English', 'language' => 'en', 'text' => $ayah->english_translation_mufti_taqi],
        ])
            ->filter(fn (array $item) => filled($item['text']))
            ->filter(fn (array $item) => $request->translationSlugs === [] || in_array($item['slug'], $request->translationSlugs, true))
            ->values()
            ->all();

        $tafasir = $this->tafseerService
            ->getAyahTafseers($ayah)
            ->filter(fn ($entry) => $request->tafsirSlugs === [] || in_array($entry->tafseer->slug, $request->tafsirSlugs, true))
            ->map(function ($entry) {
                $content = trim(strip_tags((string) ($entry->content_html ?: $entry->content ?: '')));
                $label = $entry->tafseer->title_urdu ?: ($entry->tafseer->title_english ?: $entry->tafseer->slug);

                return [
                    'slug' => $entry->tafseer->slug,
                    'label' => $label,
                    'text' => mb_substr($content, 0, config('ai_assistant.max_tafsir_chars', 2400)),
                ];
            })
            ->values()
            ->all();

        $morphology = $ayah->words
            ->map(function ($word) {
                return [
                    'id' => $word->id,
                    'position' => $word->position,
                    'word' => $word->display_form,
                    'arabic_form' => $word->display_form,
                    'translation' => $word->translation_basic,
                    'translation_urdu' => $word->translation_urdu,
                    'transliteration' => $word->transliteration,
                    'root' => $word->root?->display_root_arabic,
                    'lemma' => $word->lemma?->display_lemma_arabic,
                    'parts_of_speech' => $word->morphologies->pluck('pos_tag')->filter()->unique()->values()->all(),
                    'morphology' => $word->morphologies->map(function ($morphology) {
                        return array_filter([
                            'segment_number' => $morphology->segment_number,
                            'pos' => $morphology->pos_tag,
                            'lemma' => $morphology->display_lemma,
                            'root' => $morphology->display_root,
                            'summary' => $morphology->arabic_summary ?: $morphology->english_summary,
                        ], fn ($value) => $value !== null && $value !== '');
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $relatedAyahs = $this->buildRelatedAyahs($ayah);

        return new SourceBundle(
            ayahId: $ayah->id,
            reference: 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
            surahName: $ayah->surah->display_title,
            surahNumber: (int) $ayah->surah->number,
            ayahNumber: (int) $ayah->ayah_number,
            arabicText: QuranText::normalizeArabicForAyahDisplay($ayah->display_text),
            translations: $translations,
            tafasir: $tafasir,
            themes: $ayah->themes->map(fn ($theme) => [
                'english' => $theme->title_english,
                'urdu' => $theme->display_title_urdu ?: $theme->title_urdu,
                'description' => $theme->description,
            ])->values()->all(),
            broadThemes: $ayah->broadThemes->map(fn ($theme) => [
                'english' => $theme->title_english,
                'urdu' => $theme->title_urdu,
                'description' => $theme->description_urdu ?: $theme->description,
            ])->values()->all(),
            morphology: $morphology,
            relatedAyahs: $relatedAyahs,
            irab: $ayah->irab_arabic,
        );
    }

    public function hasUsableContext(SourceBundle $bundle): bool
    {
        return filled(trim($bundle->arabicText))
            || $bundle->translations !== []
            || $bundle->tafasir !== []
            || $bundle->themes !== []
            || $bundle->broadThemes !== []
            || $bundle->morphology !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRelatedAyahs(Ayah $ayah): array
    {
        $themeIds = $ayah->themes->pluck('id');
        $broadThemeIds = $ayah->broadThemes->pluck('id');

        if ($themeIds->isEmpty() && $broadThemeIds->isEmpty()) {
            return [];
        }

        return Ayah::query()
            ->with('surah')
            ->whereKeyNot($ayah->id)
            ->where(function ($query) use ($themeIds, $broadThemeIds) {
                if ($themeIds->isNotEmpty()) {
                    $query->orWhereHas('themes', fn ($themeQuery) => $themeQuery->whereIn('ayah_themes.id', $themeIds->all()));
                }

                if ($broadThemeIds->isNotEmpty()) {
                    $query->orWhereHas('broadThemes', fn ($themeQuery) => $themeQuery->whereIn('broad_themes.id', $broadThemeIds->all()));
                }
            })
            ->limit(3)
            ->get()
            ->map(fn (Ayah $relatedAyah) => [
                'reference' => 'Surah ' . $relatedAyah->surah->number . ':' . $relatedAyah->ayah_number,
                'arabic_text' => QuranText::normalizeArabicForAyahDisplay($relatedAyah->display_text),
                'translation' => $relatedAyah->urdu_translation_mufti_taqi ?: $relatedAyah->urdu_translation ?: $relatedAyah->urdu_translation_maududi,
            ])
            ->values()
            ->all();
    }
}
