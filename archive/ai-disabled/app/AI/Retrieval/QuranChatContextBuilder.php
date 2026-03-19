<?php

namespace App\AI\Retrieval;

use App\AI\DTOs\QuranChatRequestData;
use App\Models\Ayah;
use App\Models\AyahTheme;
use App\Models\BroadTheme;
use App\Models\Word;
use App\Services\TafseerService;
use App\Support\QuranText;
use Illuminate\Support\Collection;

class QuranChatContextBuilder
{
    public function __construct(
        protected TafseerService $tafseerService,
    ) {
    }

    /**
     * @return array{
     *   context:string,
     *   matched_ayahs:array<int, array<string, mixed>>,
     *   matched_words:array<int, array<string, mixed>>,
     *   matched_themes:array<int, array<string, mixed>>,
     *   sources_used:array<int, array<string, string>>
     * }
     */
    /**
     * @param  array<int, string>|null  $overrideTerms
     */
    public function build(QuranChatRequestData $request, ?array $overrideTerms = null): array
    {
        $question = trim($request->question);
        $normalizedQuestion = $this->normalizeArabic($question);
        $terms = $overrideTerms !== null && $overrideTerms !== []
            ? array_values($overrideTerms)
            : $this->extractTerms($question);

        $matchedAyahs = $this->findAyahs($question, $normalizedQuestion, $terms);
        $matchedWords = $this->findWords($question, $normalizedQuestion, $terms);
        $matchedThemes = $this->findThemes($question, $terms);

        $ayahsFromThemes = $this->ayahsFromThemes($matchedThemes);
        $ayahsFromWords = $this->ayahsFromWords($matchedWords);

        $allAyahs = $ayahsFromWords
            ->merge($ayahsFromThemes)
            ->merge($matchedAyahs)
            ->unique('id')
            ->take(6)
            ->values();

        return [
            'context' => $this->buildContextText($allAyahs, $matchedWords, $matchedThemes),
            'matched_ayahs' => $this->serializeAyahs($allAyahs),
            'matched_words' => $this->serializeWords($matchedWords),
            'matched_themes' => $this->serializeThemes($matchedThemes),
            'sources_used' => $this->collectSources($allAyahs, $matchedWords, $matchedThemes),
            'total_ayah_matches' => $allAyahs->count(),
            'total_word_matches' => $matchedWords->count(),
            'total_theme_matches' => $matchedThemes->count(),
        ];
    }

    public function hasUsableContext(array $payload): bool
    {
        return trim((string) ($payload['context'] ?? '')) !== '';
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Ayah>
     */
    protected function findAyahs(string $question, string $normalizedQuestion, array $terms): Collection
    {
        return Ayah::query()
            ->with(['surah', 'themes', 'broadThemes', 'words.morphologies', 'words.root', 'words.lemma'])
            ->where(function ($query) use ($question, $normalizedQuestion, $terms) {
                if ($question !== '') {
                    $query->orWhere('full_arabic_text', 'like', '%' . $question . '%')
                        ->orWhere('uthmani_text', 'like', '%' . $question . '%')
                        ->orWhere('urdu_translation', 'like', '%' . $question . '%')
                        ->orWhere('urdu_translation_ahmedali', 'like', '%' . $question . '%')
                        ->orWhere('urdu_translation_kanzuliman', 'like', '%' . $question . '%')
                        ->orWhere('urdu_translation_maududi', 'like', '%' . $question . '%')
                        ->orWhere('urdu_translation_mufti_taqi', 'like', '%' . $question . '%')
                        ->orWhere('english_translation_mufti_taqi', 'like', '%' . $question . '%');
                }

                if ($normalizedQuestion !== '') {
                    $query->orWhere('simple_text', 'like', '%' . $normalizedQuestion . '%');
                }

                foreach ($terms as $term) {
                    $query->orWhere('urdu_translation', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_maududi', 'like', '%' . $term . '%')
                        ->orWhere('urdu_translation_mufti_taqi', 'like', '%' . $term . '%')
                        ->orWhere('english_translation_mufti_taqi', 'like', '%' . $term . '%');
                }
            })
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->limit(3)
            ->get();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Word>
     */
    protected function findWords(string $question, string $normalizedQuestion, array $terms): Collection
    {
        return Word::query()
            ->with(['ayah.surah', 'root', 'lemma', 'morphologies'])
            ->where(function ($query) use ($question, $normalizedQuestion, $terms) {
                if ($question !== '') {
                    $query->orWhere('arabic_text', 'like', '%' . $question . '%')
                        ->orWhere('translation_basic', 'like', '%' . $question . '%')
                        ->orWhere('translation_urdu', 'like', '%' . $question . '%')
                        ->orWhere('transliteration', 'like', '%' . $question . '%');
                }

                if ($normalizedQuestion !== '') {
                    $query->orWhere('normalized_text', 'like', '%' . $normalizedQuestion . '%')
                        ->orWhere('form', 'like', '%' . $normalizedQuestion . '%');
                }

                foreach ($terms as $term) {
                    $query->orWhere('translation_basic', 'like', '%' . $term . '%')
                        ->orWhere('translation_urdu', 'like', '%' . $term . '%')
                        ->orWhere('transliteration', 'like', '%' . $term . '%');
                }
            })
            ->orderBy('surah_number')
            ->orderBy('ayah_number')
            ->limit(6)
            ->get()
            ->filter(fn (Word $word) => $this->wordMatchesStrongly($word, $terms))
            ->values();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, array<string, mixed>>
     */
    protected function findThemes(string $question, array $terms): Collection
    {
        $ayahThemes = AyahTheme::query()
            ->where('is_active', true)
            ->where(function ($query) use ($question, $terms) {
                if ($question !== '') {
                    $query->orWhere('title_english', 'like', '%' . $question . '%')
                        ->orWhere('title_urdu', 'like', '%' . $question . '%')
                        ->orWhere('description', 'like', '%' . $question . '%');
                }

                foreach ($terms as $term) {
                    $query->orWhere('title_english', 'like', '%' . $term . '%')
                        ->orWhere('title_urdu', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                }
            })
            ->withCount('ayahs')
            ->limit(4)
            ->get()
            ->map(fn (AyahTheme $theme) => ['type' => 'theme', 'model' => $theme]);

        $broadThemes = BroadTheme::query()
            ->where('is_active', true)
            ->where(function ($query) use ($question, $terms) {
                if ($question !== '') {
                    $query->orWhere('title_english', 'like', '%' . $question . '%')
                        ->orWhere('title_urdu', 'like', '%' . $question . '%')
                        ->orWhere('description', 'like', '%' . $question . '%');
                }

                foreach ($terms as $term) {
                    $query->orWhere('title_english', 'like', '%' . $term . '%')
                        ->orWhere('title_urdu', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                }
            })
            ->withCount('ayahs')
            ->limit(4)
            ->get()
            ->map(fn (BroadTheme $theme) => ['type' => 'broad_theme', 'model' => $theme]);

        return $ayahThemes->merge($broadThemes)->take(6)->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $themes
     * @return Collection<int, Ayah>
     */
    protected function ayahsFromThemes(Collection $themes): Collection
    {
        return $themes
            ->flatMap(function (array $item) {
                $model = $item['model'];

                return $model->ayahs()
                    ->with(['surah', 'themes', 'broadThemes', 'words.morphologies', 'words.root', 'words.lemma'])
                    ->orderBy('surah_id')
                    ->orderBy('ayah_number')
                    ->limit(2)
                    ->get();
            })
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, Word>  $words
     * @return Collection<int, Ayah>
     */
    protected function ayahsFromWords(Collection $words): Collection
    {
        return $words
            ->map(fn (Word $word) => $word->ayah)
            ->filter()
            ->unique('id')
            ->values();
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
            $sections[] = 'MATCHED AYAHS:' . "\n" . $ayahs->map(function (Ayah $ayah) {
                $translations = array_filter([
                    $ayah->urdu_translation ? 'Quran Foundation Urdu: ' . $ayah->urdu_translation : null,
                    $ayah->urdu_translation_maududi ? 'Tafheem ul Quran: ' . $ayah->urdu_translation_maududi : null,
                    $ayah->urdu_translation_mufti_taqi ? 'Mufti Taqi Urdu: ' . $ayah->urdu_translation_mufti_taqi : null,
                    $ayah->english_translation_mufti_taqi ? 'Mufti Taqi English: ' . $ayah->english_translation_mufti_taqi : null,
                ]);

                $tafseer = $this->tafseerService->getAyahTafseers($ayah)
                    ->take(2)
                    ->map(function ($entry) {
                        $title = $entry->tafseer->title_urdu ?: ($entry->tafseer->title_english ?: $entry->tafseer->slug);
                        $content = trim(strip_tags((string) ($entry->content_html ?: $entry->content_text ?: '')));

                        return $title . ': ' . mb_substr($content, 0, 700);
                    })
                    ->implode("\n");

                $themeText = $ayah->themes->pluck('title_english')
                    ->merge($ayah->broadThemes->pluck('title_english')->map(fn (string $title) => 'Broad: ' . $title))
                    ->implode(', ');

                $wordHighlights = $ayah->words
                    ->take(6)
                    ->map(function ($word) {
                        $pos = $word->morphologies->pluck('pos_tag')->filter()->unique()->implode(', ');

                        return implode(' | ', array_filter([
                            $word->display_form,
                            $word->translation_urdu ? 'urdu: ' . $word->translation_urdu : null,
                            $word->lemma?->display_lemma_arabic ? 'lemma: ' . $word->lemma->display_lemma_arabic : null,
                            $word->root?->display_root_arabic ? 'root: ' . $word->root->display_root_arabic : null,
                            $pos !== '' ? 'pos: ' . $pos : null,
                        ]));
                    })
                    ->implode("\n");

                return implode("\n", array_filter([
                    'Reference: Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
                    'Arabic: ' . QuranText::normalizeArabicForAyahDisplay($ayah->display_text),
                    $translations !== [] ? implode("\n", $translations) : null,
                    $themeText !== '' ? 'Themes: ' . $themeText : null,
                    $wordHighlights !== '' ? "Word Notes:\n" . $wordHighlights : null,
                    $tafseer !== '' ? "Tafseer Excerpts:\n" . $tafseer : null,
                ]));
            })->implode("\n\n---\n\n");
        }

        if ($words->isNotEmpty()) {
            $sections[] = 'MATCHED WORDS / OCCURRENCES:' . "\n" . $words->map(function (Word $word) {
                $occurrences = Word::query()
                    ->when(
                        filled($word->normalized_text),
                        fn ($query) => $query->where('normalized_text', $word->normalized_text),
                        fn ($query) => $query->where('form', $word->form)
                    )
                    ->count();

                return implode(' | ', array_filter([
                    $word->display_form,
                    'Surah ' . $word->surah_number . ':' . $word->ayah_number,
                    $word->translation_urdu ? 'urdu: ' . $word->translation_urdu : null,
                    $word->translation_basic ? 'english: ' . $word->translation_basic : null,
                    $word->lemma?->display_lemma_arabic ? 'lemma: ' . $word->lemma->display_lemma_arabic : null,
                    $word->root?->display_root_arabic ? 'root: ' . $word->root->display_root_arabic : null,
                    $word->morphologies->pluck('pos_tag')->filter()->unique()->isNotEmpty() ? 'pos: ' . $word->morphologies->pluck('pos_tag')->filter()->unique()->implode(', ') : null,
                    'occurrences: ' . $occurrences,
                ]));
            })->implode("\n");
        }

        if ($themes->isNotEmpty()) {
            $sections[] = 'MATCHED THEMES:' . "\n" . $themes->map(function (array $item) {
                $theme = $item['model'];

                return implode(' | ', array_filter([
                    $item['type'] === 'broad_theme' ? 'Broad Theme' : 'Theme',
                    $theme->title_english,
                    $theme->title_urdu,
                    $theme->description,
                    'ayahs: ' . ($theme->ayahs_count ?? $theme->ayahs()->count()),
                ]));
            })->implode("\n");
        }

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param  Collection<int, Ayah>  $ayahs
     * @return array<int, array<string, mixed>>
     */
    protected function serializeAyahs(Collection $ayahs): array
    {
        return $ayahs->map(function (Ayah $ayah) {
            return [
                'id' => $ayah->id,
                'reference' => 'Surah ' . $ayah->surah->number . ':' . $ayah->ayah_number,
                'surah_number' => $ayah->surah->number,
                'ayah_number' => $ayah->ayah_number,
                'arabic_text' => QuranText::normalizeArabicForAyahDisplay($ayah->display_text),
                'translation' => $ayah->urdu_translation_mufti_taqi ?: $ayah->urdu_translation ?: $ayah->urdu_translation_maududi,
                'url' => route('ayahs.show', [$ayah->surah, $ayah]),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Word>  $words
     * @return array<int, array<string, mixed>>
     */
    protected function serializeWords(Collection $words): array
    {
        return $words->map(function (Word $word) {
            $occurrenceQuery = Word::query()
                ->when(
                    filled($word->normalized_text),
                    fn ($query) => $query->where('normalized_text', $word->normalized_text),
                    fn ($query) => $query->where('form', $word->form)
                );

            $count = (clone $occurrenceQuery)->count();
            $samples = (clone $occurrenceQuery)
                ->with('ayah.surah')
                ->orderBy('surah_number')
                ->orderBy('ayah_number')
                ->orderBy('position')
                ->limit(3)
                ->get();

            return [
                'id' => $word->id,
                'arabic' => $word->display_form,
                'translation' => $word->translation_urdu ?: $word->translation_basic,
                'lemma' => $word->lemma?->display_lemma_arabic,
                'root' => $word->root?->display_root_arabic,
                'pos' => $word->morphologies->pluck('pos_tag')->filter()->unique()->values()->all(),
                'occurrence_count' => $count,
                'url' => route('words.show', $word),
                'occurrences' => $samples->map(function (Word $occurrence) {
                    return [
                        'reference' => 'Surah ' . $occurrence->surah_number . ':' . $occurrence->ayah_number,
                        'url' => route('ayahs.show', [$occurrence->ayah->surah, $occurrence->ayah]),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $themes
     * @return array<int, array<string, mixed>>
     */
    protected function serializeThemes(Collection $themes): array
    {
        return $themes->map(function (array $item) {
            $theme = $item['model'];

            return [
                'type' => $item['type'],
                'title_english' => $theme->title_english,
                'title_urdu' => $theme->title_urdu,
                'description' => $theme->description,
                'ayah_count' => $theme->ayahs_count ?? $theme->ayahs()->count(),
                'url' => $item['type'] === 'broad_theme'
                    ? route('broad-themes.show', $theme)
                    : route('ayah-themes.show', $theme),
            ];
        })->values()->all();
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
            $sources[] = ['type' => 'tafsir', 'label' => 'Tafseer excerpts'];
        }

        if ($words->isNotEmpty()) {
            $sources[] = ['type' => 'word', 'label' => 'Words, roots, lemmas, POS, occurrences'];
        }

        if ($themes->isNotEmpty()) {
            $sources[] = ['type' => 'theme', 'label' => 'Themes and broad themes'];
        }

        return $sources;
    }

    protected function normalizeArabic(string $value): string
    {
        return preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $value) ?? $value;
    }

    /**
     * @return array<int, string>
     */
    protected function extractTerms(string $question): array
    {
        $stopwords = [
            'about', 'aur', 'ayah', 'ayat', 'bare', 'database', 'hai', 'hain', 'is', 'ka', 'ke', 'kehta',
            'ki', 'kis', 'kya', 'main', 'mein', 'me', 'or', 'par', 'quran', 'qurani', 'say', 'se', 'surah',
            'what', 'who', 'why', 'with', 'ye', 'yeh',
        ];

        return collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($question)) ?: [])
            ->filter(fn ($term) => is_string($term) && mb_strlen(trim($term)) >= 3)
            ->map(fn ($term) => trim($term))
            ->filter(fn ($term) => ! in_array($term, $stopwords, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $terms
     */
    protected function wordMatchesStrongly(Word $word, array $terms): bool
    {
        $haystacks = array_filter([
            mb_strtolower((string) $word->translation_basic),
            mb_strtolower((string) $word->translation_urdu),
            mb_strtolower((string) $word->transliteration),
            mb_strtolower((string) $word->normalized_text),
            mb_strtolower((string) $word->form),
            mb_strtolower((string) $word->display_form),
            mb_strtolower((string) $word->lemma?->display_lemma_arabic),
            mb_strtolower((string) $word->root?->display_root_arabic),
        ]);

        foreach ($terms as $term) {
            $term = mb_strtolower(trim($term));

            foreach ($haystacks as $haystack) {
                if ($haystack === '') {
                    continue;
                }

                if ($this->matchesTerm($haystack, $term)) {
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

        if (preg_match('/^[a-z0-9]+$/u', $term)) {
            return (bool) preg_match('/\b' . preg_quote($term, '/') . '\b/u', $haystack);
        }

        return str_contains($haystack, $term);
    }
}
