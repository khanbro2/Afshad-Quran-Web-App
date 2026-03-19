<?php

namespace App\AI\QuranChat\Services;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\QuranChat\DTOs\QuestionIntentData;
use App\Models\Ayah;
use App\Models\AyahTheme;
use App\Models\BroadTheme;
use App\Models\Surah;
use App\Models\Word;
use App\Services\TafseerService;
use App\Support\QuranText;
use Illuminate\Support\Collection;

class QuranQueryService
{
    public function __construct(
        protected TafseerService $tafseerService,
        protected EntityResolver $resolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEvidence(QuranChatRequestData $request, QuestionIntentData $intent): array
    {
        $terms = $intent->resolvedTerms !== [] ? $intent->resolvedTerms : $intent->terms;
        $countMode = $this->detectCountMode($intent->question);
        $matchedWords = $this->findWords($terms, $intent->surahNumber, $countMode);
        $matchedThemes = $this->findThemes($terms);
        $matchedAyahs = $this->findAyahs($terms, $intent, $matchedWords, $matchedThemes);

        return [
            'context' => $this->buildContextText($matchedAyahs, $matchedWords, $matchedThemes),
            'matched_ayahs' => $this->serializeAyahs($matchedAyahs),
            'matched_words' => $this->serializeWords($matchedWords),
            'matched_themes' => $this->serializeThemes($matchedThemes),
            'sources_used' => $this->collectSources($matchedAyahs, $matchedWords, $matchedThemes),
            'counts' => $this->buildCountData($intent, $matchedAyahs, $matchedWords, $terms, $countMode),
            'translation' => $this->buildTranslationData($intent),
            'tafseer' => $this->buildTafseerData($intent),
            'morphology' => $this->buildMorphologyData($intent, $matchedWords, $matchedAyahs),
            'comparison' => $this->buildComparisonData($intent),
            'confidence_score' => $this->calculateConfidence($intent, $matchedAyahs, $matchedWords, $matchedThemes),
        ];
    }

    protected function detectCountMode(string $question): string
    {
        $question = $this->resolver->normalize($question);

        return match (true) {
            str_contains($question, 'root') => 'root',
            str_contains($question, 'lemma') => 'lemma',
            default => 'word',
        };
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Word>
     */
    protected function findWords(array $terms, ?int $surahNumber, string $countMode): Collection
    {
        if ($terms === []) {
            return collect();
        }

        return Word::query()
            ->with(['ayah.surah', 'root', 'lemma', 'morphologies'])
            ->when($surahNumber !== null, fn ($query) => $query->where('surah_number', $surahNumber))
            ->where(function ($query) use ($terms, $countMode) {
                foreach ($terms as $term) {
                    $normalized = $this->resolver->normalize((string) $term);
                    $query->orWhere('translation_basic', 'like', '%' . $term . '%')
                        ->orWhere('translation_urdu', 'like', '%' . $term . '%')
                        ->orWhere('transliteration', 'like', '%' . $term . '%')
                        ->orWhere('normalized_text', 'like', '%' . $normalized . '%')
                        ->orWhere('form', 'like', '%' . $term . '%')
                        ->orWhere('arabic_text', 'like', '%' . $term . '%');

                    if ($countMode === 'lemma') {
                        $query->orWhereHas('lemma', fn ($lemmaQuery) => $lemmaQuery->where('lemma_arabic', 'like', '%' . $term . '%'));
                    }

                    if ($countMode === 'root') {
                        $query->orWhereHas('root', fn ($rootQuery) => $rootQuery->where('root_arabic', 'like', '%' . $term . '%')->orWhere('root_letters', 'like', '%' . $term . '%'));
                    }
                }
            })
            ->orderBy('surah_number')
            ->orderBy('ayah_number')
            ->orderBy('position')
            ->get()
            ->filter(fn (Word $word) => $this->wordMatchesStrongly($word, $terms))
            ->values();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, array<string, mixed>>
     */
    protected function findThemes(array $terms): Collection
    {
        if ($terms === []) {
            return collect();
        }

        $ayahThemes = AyahTheme::query()
            ->where('is_active', true)
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('title_english', 'like', '%' . $term . '%')
                        ->orWhere('title_urdu', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                }
            })
            ->withCount('ayahs')
            ->limit(6)
            ->get()
            ->map(fn (AyahTheme $theme) => ['type' => 'theme', 'model' => $theme]);

        $broadThemes = BroadTheme::query()
            ->where('is_active', true)
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('title_english', 'like', '%' . $term . '%')
                        ->orWhere('title_urdu', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                }
            })
            ->withCount('ayahs')
            ->limit(6)
            ->get()
            ->map(fn (BroadTheme $theme) => ['type' => 'broad_theme', 'model' => $theme]);

        return $ayahThemes->merge($broadThemes)->values();
    }

    /**
     * @param  array<int, string>  $terms
     * @param  Collection<int, Word>  $matchedWords
     * @param  Collection<int, array<string, mixed>>  $matchedThemes
     * @return Collection<int, Ayah>
     */
    protected function findAyahs(array $terms, QuestionIntentData $intent, Collection $matchedWords, Collection $matchedThemes): Collection
    {
        if ($intent->surahNumber !== null && $intent->ayahNumber !== null) {
            return Ayah::query()
                ->with(['surah', 'themes', 'broadThemes', 'words.morphologies', 'words.root', 'words.lemma'])
                ->whereHas('surah', fn ($query) => $query->where('number', $intent->surahNumber))
                ->where('ayah_number', $intent->ayahNumber)
                ->get();
        }

        $fromWords = $matchedWords->map(fn (Word $word) => $word->ayah)->filter()->unique('id');

        $fromThemes = $matchedThemes->flatMap(function (array $item) use ($intent) {
            return $item['model']->ayahs()
                ->with(['surah', 'themes', 'broadThemes', 'words.morphologies', 'words.root', 'words.lemma'])
                ->when($intent->surahNumber !== null, fn ($query) => $query->whereHas('surah', fn ($surahQuery) => $surahQuery->where('number', $intent->surahNumber)))
                ->limit(6)
                ->get();
        })->unique('id');

        $fromText = Ayah::query()
            ->with(['surah', 'themes', 'broadThemes', 'words.morphologies', 'words.root', 'words.lemma'])
            ->when($intent->surahNumber !== null, fn ($query) => $query->whereHas('surah', fn ($surahQuery) => $surahQuery->where('number', $intent->surahNumber)))
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('full_arabic_text', 'like', '%' . $term . '%')
                        ->orWhere('uthmani_text', 'like', '%' . $term . '%')
                        ->orWhere('simple_text', 'like', '%' . $this->resolver->normalize($term) . '%')
                        ->orWhere('urdu_translation', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_ahmedali', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_kanzuliman', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_maududi', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_mufti_taqi', 'like', '%' . $term . '%')
                        ->orWhere('english_translation_mufti_taqi', 'like', '%' . $term . '%');
                }
            })
            ->limit(40)
            ->get();

        $ayahs = $fromWords->merge($fromThemes)->merge($fromText)->unique('id')
            ->map(function (Ayah $ayah) use ($terms) {
                $ayah->chat_score = $this->scoreAyah($ayah, $terms);
                return $ayah;
            })
            ->filter(fn (Ayah $ayah) => (int) ($ayah->chat_score ?? 0) >= (int) config('ai_assistant.thresholds.weak_evidence', 6))
            ->sortByDesc('chat_score')
            ->values();

        if (in_array($intent->type, ['relation_query', 'comparison_query'], true) && $intent->primaryEntity !== null && $intent->secondaryEntity !== null) {
            $ayahs = $ayahs->filter(function (Ayah $ayah) use ($intent) {
                $text = $this->resolver->normalize(implode(' ', array_filter([
                    $ayah->urdu_translation,
                    $ayah->urdu_translation_maududi,
                    $ayah->urdu_translation_mufti_taqi,
                    $ayah->english_translation_mufti_taqi,
                ])));

                return str_contains($text, $intent->primaryEntity) && str_contains($text, $intent->secondaryEntity);
            })->values();
        }

        return $ayahs;
    }

    /**
     * @param  array<int, string>  $terms
     * @return array<string, mixed>
     */
    protected function buildCountData(QuestionIntentData $intent, Collection $ayahs, Collection $words, array $terms, string $countMode): array
    {
        $wordCount = match ($countMode) {
            'root' => $words->pluck('root_id')->filter()->isNotEmpty() ? Word::query()->whereIn('root_id', $words->pluck('root_id')->filter()->unique()->all())->count() : 0,
            'lemma' => $words->pluck('lemma_id')->filter()->isNotEmpty() ? Word::query()->whereIn('lemma_id', $words->pluck('lemma_id')->filter()->unique()->all())->count() : 0,
            default => $words->count(),
        };

        return [
            'mode' => $countMode,
            'exact_word_occurrence_count' => $wordCount,
            'exact_ayah_count' => $ayahs->count(),
            'exact_surah_count' => $ayahs->pluck('surah.number')->unique()->count(),
            'exact_cooccurrence_count' => $intent->secondaryEntity !== null ? $ayahs->count() : 0,
            'counted_terms' => $terms,
            'surah_name' => $intent->surahNumber !== null ? $this->surahLabel($intent->surahNumber) : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildTranslationData(QuestionIntentData $intent): ?array
    {
        $ayah = $this->resolveReferencedAyah($intent);
        if ($ayah === null) {
            return null;
        }

        return [
            'reference' => 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
            'sources' => array_filter([
                'Quran Foundation Urdu' => $ayah->urdu_translation,
                'Ahmed Ali Urdu' => $ayah->urdu_translation_ahmedali,
                'Kanzul Iman Urdu' => $ayah->urdu_translation_kanzuliman,
                'Tafhim ul Quran' => $ayah->urdu_translation_maududi,
                'Mufti Taqi Urdu' => $ayah->urdu_translation_mufti_taqi,
                'Mufti Taqi English' => $ayah->english_translation_mufti_taqi,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildTafseerData(QuestionIntentData $intent): ?array
    {
        $ayah = $this->resolveReferencedAyah($intent);
        if ($ayah === null) {
            return null;
        }

        $entries = $this->tafseerService->getAyahTafseers($ayah)->take(3)->map(function ($entry) {
            $label = $entry->tafseer->title_urdu ?: ($entry->tafseer->title_english ?: $entry->tafseer->slug);
            $content = trim(strip_tags((string) ($entry->content_html ?: $entry->content ?: '')));
            return ['source' => $label, 'excerpt' => mb_substr($content, 0, (int) config('ai_assistant.max_tafsir_chars', 2400))];
        })->values()->all();

        return $entries === [] ? null : ['reference' => 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number, 'entries' => $entries];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildMorphologyData(QuestionIntentData $intent, Collection $matchedWords, Collection $matchedAyahs): ?array
    {
        $word = $matchedWords->first();
        if ($word instanceof Word) {
            return [
                'word' => $word->display_form,
                'reference' => 'Surah ' . $word->surah_number . ':' . $word->ayah_number,
                'lemma' => $word->lemma?->display_lemma_arabic,
                'root' => $word->root?->display_root_arabic,
                'pos' => $word->morphologies->pluck('pos_tag')->filter()->unique()->values()->all(),
                'segments' => $word->morphologies->map(fn ($morphology) => array_filter([
                    'segment_number' => $morphology->segment_number,
                    'pos' => $morphology->pos_tag,
                    'lemma' => $morphology->display_lemma,
                    'root' => $morphology->display_root,
                    'summary' => $morphology->arabic_summary ?: $morphology->english_summary,
                ], fn ($value) => $value !== null && $value !== ''))->values()->all(),
            ];
        }

        $ayah = $this->resolveReferencedAyah($intent) ?? $matchedAyahs->first();
        if (! $ayah instanceof Ayah) {
            return null;
        }

        return [
            'reference' => 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
            'irab' => $ayah->irab_arabic,
            'words' => $ayah->words->take(8)->map(fn ($ayahWord) => [
                'word' => $ayahWord->display_form,
                'lemma' => $ayahWord->lemma?->display_lemma_arabic,
                'root' => $ayahWord->root?->display_root_arabic,
                'pos' => $ayahWord->morphologies->pluck('pos_tag')->filter()->unique()->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildComparisonData(QuestionIntentData $intent): ?array
    {
        if ($intent->primaryEntity === null || $intent->secondaryEntity === null) {
            return null;
        }

        return ['left' => $intent->primaryEntity, 'right' => $intent->secondaryEntity];
    }

    protected function calculateConfidence(QuestionIntentData $intent, Collection $ayahs, Collection $words, Collection $themes): int
    {
        $score = 0;
        if ($intent->primaryEntity !== null) {
            $score += 4;
        }
        $score += min($ayahs->count(), 4) * 2;
        $score += min($words->count(), 3) * 2;
        $score += min($themes->count(), 2);
        if ($intent->secondaryEntity !== null && $ayahs->isNotEmpty()) {
            $score += 2;
        }

        return $score;
    }

    protected function resolveReferencedAyah(QuestionIntentData $intent): ?Ayah
    {
        if ($intent->surahNumber === null || $intent->ayahNumber === null) {
            return null;
        }

        return Ayah::query()
            ->with(['surah', 'words.morphologies', 'words.root', 'words.lemma'])
            ->whereHas('surah', fn ($query) => $query->where('number', $intent->surahNumber))
            ->where('ayah_number', $intent->ayahNumber)
            ->first();
    }

    /**
     * @param  array<int, string>  $terms
     */
    protected function scoreAyah(Ayah $ayah, array $terms): int
    {
        $score = 0;
        $haystacks = [
            $this->resolver->normalize((string) $ayah->full_arabic_text),
            $this->resolver->normalize((string) $ayah->uthmani_text),
            $this->resolver->normalize((string) $ayah->simple_text),
            $this->resolver->normalize((string) $ayah->urdu_translation),
            $this->resolver->normalize((string) $ayah->urdu_translation_ahmedali),
            $this->resolver->normalize((string) $ayah->urdu_translation_kanzuliman),
            $this->resolver->normalize((string) $ayah->urdu_translation_maududi),
            $this->resolver->normalize((string) $ayah->urdu_translation_mufti_taqi),
            $this->resolver->normalize((string) $ayah->english_translation_mufti_taqi),
        ];

        foreach ($terms as $term) {
            $term = $this->resolver->normalize((string) $term);
            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && $this->matchesTerm($haystack, $term)) {
                    $score += 2;
                }
            }

            foreach ($ayah->words as $word) {
                foreach ([
                    $this->resolver->normalize((string) $word->translation_urdu),
                    $this->resolver->normalize((string) $word->translation_basic),
                    $this->resolver->normalize((string) $word->transliteration),
                    $this->resolver->normalize((string) $word->normalized_text),
                    $this->resolver->normalize((string) $word->display_form),
                    $this->resolver->normalize((string) $word->lemma?->display_lemma_arabic),
                    $this->resolver->normalize((string) $word->root?->display_root_arabic),
                ] as $wordHaystack) {
                    if ($wordHaystack !== '' && $this->matchesTerm($wordHaystack, $term)) {
                        $score += 3;
                    }
                }
            }
        }

        return $score;
    }

    protected function wordMatchesStrongly(Word $word, array $terms): bool
    {
        $haystacks = array_filter([
            $this->resolver->normalize((string) $word->translation_basic),
            $this->resolver->normalize((string) $word->translation_urdu),
            $this->resolver->normalize((string) $word->transliteration),
            $this->resolver->normalize((string) $word->normalized_text),
            $this->resolver->normalize((string) $word->display_form),
            $this->resolver->normalize((string) $word->lemma?->display_lemma_arabic),
            $this->resolver->normalize((string) $word->root?->display_root_arabic),
        ]);

        foreach ($terms as $term) {
            $term = $this->resolver->normalize((string) $term);
            foreach ($haystacks as $haystack) {
                if ($this->matchesTerm((string) $haystack, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function matchesTerm(string $haystack, string $term): bool
    {
        if ($term === '') {
            return false;
        }

        if (preg_match('/^[a-z0-9 ]+$/u', $term)) {
            return (bool) preg_match('/\b' . preg_quote($term, '/') . '\b/u', $haystack);
        }

        return str_contains($haystack, $term);
    }

    /**
     * @param  Collection<int, Ayah>  $ayahs
     * @param  Collection<int, Word>  $words
     * @param  Collection<int, array<string, mixed>>  $themes
     */
    protected function buildContextText(Collection $ayahs, Collection $words, Collection $themes): string
    {
        $sections = [];

        if ($ayahs->isNotEmpty()) {
            $sections[] = 'MATCHED AYAHS:' . "\n" . $ayahs->take((int) config('ai_assistant.thresholds.max_context_ayahs', 5))->map(function (Ayah $ayah) {
                $translations = array_filter([
                    $ayah->urdu_translation ? 'Quran Foundation Urdu: ' . $ayah->urdu_translation : null,
                    $ayah->urdu_translation_ahmedali ? 'Ahmed Ali Urdu: ' . $ayah->urdu_translation_ahmedali : null,
                    $ayah->urdu_translation_maududi ? 'Tafhim ul Quran: ' . $ayah->urdu_translation_maududi : null,
                    $ayah->urdu_translation_mufti_taqi ? 'Mufti Taqi Urdu: ' . $ayah->urdu_translation_mufti_taqi : null,
                    $ayah->english_translation_mufti_taqi ? 'Mufti Taqi English: ' . $ayah->english_translation_mufti_taqi : null,
                ]);

                $tafseer = $this->tafseerService->getAyahTafseers($ayah)->take(2)->map(function ($entry) {
                    $title = $entry->tafseer->title_urdu ?: ($entry->tafseer->title_english ?: $entry->tafseer->slug);
                    $content = trim(strip_tags((string) ($entry->content_html ?: $entry->content ?: '')));
                    return $title . ': ' . mb_substr($content, 0, 500);
                })->implode("\n");

                return implode("\n", array_filter([
                    'Reference: Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
                    'Arabic: ' . QuranText::normalizeArabicForAyahDisplay($ayah->display_text),
                    $translations !== [] ? implode("\n", $translations) : null,
                    $tafseer !== '' ? "Tafseer:\n" . $tafseer : null,
                ]));
            })->implode("\n\n---\n\n");
        }

        if ($words->isNotEmpty()) {
            $sections[] = 'MATCHED WORDS:' . "\n" . $words->take((int) config('ai_assistant.thresholds.max_context_words', 6))->map(fn (Word $word) => implode(' | ', array_filter([
                $word->display_form,
                'Surah ' . $word->surah_number . ':' . $word->ayah_number,
                $word->translation_urdu ? 'urdu: ' . $word->translation_urdu : null,
                $word->lemma?->display_lemma_arabic ? 'lemma: ' . $word->lemma->display_lemma_arabic : null,
                $word->root?->display_root_arabic ? 'root: ' . $word->root->display_root_arabic : null,
                $word->morphologies->pluck('pos_tag')->filter()->unique()->isNotEmpty() ? 'pos: ' . $word->morphologies->pluck('pos_tag')->filter()->unique()->implode(', ') : null,
            ])))->implode("\n");
        }

        if ($themes->isNotEmpty()) {
            $sections[] = 'MATCHED THEMES:' . "\n" . $themes->take(5)->map(fn (array $item) => implode(' | ', array_filter([
                $item['type'] === 'broad_theme' ? 'Broad Theme' : 'Theme',
                $item['model']->title_english,
                $item['model']->title_urdu,
                'ayahs: ' . ($item['model']->ayahs_count ?? $item['model']->ayahs()->count()),
            ])))->implode("\n");
        }

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param  Collection<int, Ayah>  $ayahs
     * @return array<int, array<string, mixed>>
     */
    protected function serializeAyahs(Collection $ayahs): array
    {
        return $ayahs->take((int) config('ai_assistant.thresholds.max_list_results', 8))->map(fn (Ayah $ayah) => [
            'id' => $ayah->id,
            'reference' => 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
            'surah_number' => $ayah->surah->number,
            'surah_name' => $ayah->surah->display_title,
            'ayah_number' => $ayah->ayah_number,
            'arabic_text' => QuranText::normalizeArabicForAyahDisplay($ayah->display_text),
            'translation' => $ayah->urdu_translation_mufti_taqi ?: $ayah->urdu_translation ?: $ayah->urdu_translation_maududi,
            'url' => route('ayahs.show', [$ayah->surah, $ayah]),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Word>  $words
     * @return array<int, array<string, mixed>>
     */
    protected function serializeWords(Collection $words): array
    {
        return $words->take((int) config('ai_assistant.thresholds.max_list_results', 8))->map(function (Word $word) {
            $occurrenceQuery = Word::query()
                ->when(filled($word->normalized_text), fn ($query) => $query->where('normalized_text', $word->normalized_text), fn ($query) => $query->where('form', $word->form));

            return [
                'id' => $word->id,
                'arabic' => $word->display_form,
                'translation' => $word->translation_urdu ?: $word->translation_basic,
                'lemma' => $word->lemma?->display_lemma_arabic,
                'root' => $word->root?->display_root_arabic,
                'pos' => $word->morphologies->pluck('pos_tag')->filter()->unique()->values()->all(),
                'occurrence_count' => (clone $occurrenceQuery)->count(),
                'url' => route('words.show', $word),
                'occurrences' => (clone $occurrenceQuery)->with('ayah.surah')->orderBy('surah_number')->orderBy('ayah_number')->orderBy('position')->limit(5)->get()->map(fn (Word $occurrence) => [
                    'reference' => 'Surah ' . $occurrence->surah_number . ':' . $occurrence->ayah_number,
                    'url' => route('ayahs.show', [$occurrence->ayah->surah, $occurrence->ayah]),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $themes
     * @return array<int, array<string, mixed>>
     */
    protected function serializeThemes(Collection $themes): array
    {
        return $themes->take((int) config('ai_assistant.thresholds.max_list_results', 8))->map(fn (array $item) => [
            'type' => $item['type'],
            'title_english' => $item['model']->title_english,
            'title_urdu' => $item['model']->title_urdu,
            'description' => $item['model']->description,
            'ayah_count' => $item['model']->ayahs_count ?? $item['model']->ayahs()->count(),
            'url' => $item['type'] === 'broad_theme' ? route('broad-themes.show', $item['model']) : route('ayah-themes.show', $item['model']),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Ayah>  $ayahs
     * @param  Collection<int, Word>  $words
     * @param  Collection<int, array<string, mixed>>  $themes
     * @return array<int, array<string, string>>
     */
    protected function collectSources(Collection $ayahs, Collection $words, Collection $themes): array
    {
        $sources = [];
        if ($ayahs->isNotEmpty()) {
            $sources[] = ['type' => 'ayah', 'label' => 'Matched ayahs and translations'];
            $sources[] = ['type' => 'tafseer', 'label' => 'Tafseer excerpts'];
        }
        if ($words->isNotEmpty()) {
            $sources[] = ['type' => 'word', 'label' => 'Words, roots, lemmas, POS, occurrences'];
        }
        if ($themes->isNotEmpty()) {
            $sources[] = ['type' => 'theme', 'label' => 'Themes and broad themes'];
        }

        return $sources;
    }

    protected function surahLabel(int $surahNumber): string
    {
        $surah = Surah::query()->where('number', $surahNumber)->first();
        return $surah?->display_title ?: 'Surah ' . $surahNumber;
    }
}
