<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use App\Models\Surah;
use App\Models\AyahFeedback;
use App\Support\AyahWordMappingService;
use App\Support\AyahDependencyGraphRepository;
use App\Support\MorphologyAnalysisService;
use App\Support\MorphologyCardColorService;
use App\Support\QuranText;
use App\Support\VisibleWordService;
use App\Support\WordDisplayService;
use App\Services\TafseerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AyahController extends Controller
{
    public function search(Request $request): View
    {
        $queryText = trim((string) $request->input('q', ''));
        $normalizedQuery = preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $queryText) ?? $queryText;
        $results = null;

        if ($queryText !== '') {
            $results = Ayah::query()
                ->with('surah')
                ->where(function ($builder) use ($queryText, $normalizedQuery) {
                    $builder
                        ->where('simple_text', 'like', '%' . $normalizedQuery . '%')
                        ->orWhere('full_arabic_text', 'like', '%' . $queryText . '%')
                        ->orWhere('uthmani_text', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_ahmedali', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_kanzuliman', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_maududi', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_mufti_taqi', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_jalandhry', 'like', '%' . $queryText . '%')
                        ->orWhere('urdu_translation_bayan_simple', 'like', '%' . $queryText . '%')
                        ->orWhere('english_translation_mufti_taqi', 'like', '%' . $queryText . '%');
                })
                ->orderBy('surah_id')
                ->orderBy('ayah_number')
                ->paginate(25)
                ->withQueryString();
        }

        return view('ayahs.search', [
            'queryText' => $queryText,
            'results' => $results,
        ]);
    }

    public function show(Surah $surah, Ayah $ayah, TafseerService $tafseerService, AyahDependencyGraphRepository $dependencyGraphs): View
{
    abort_unless($ayah->surah_id === $surah->id, 404);

    $ayah->load([
        'surah',
        'themes',
        'broadThemes',
        'words' => fn ($query) => $query
            ->with([
                'lemma',
                'root',
                'morphologies' => fn ($morphologies) => $morphologies->orderBy('segment_number'),
            ])
            ->orderBy('position'),
    ]);

    $mapping = AyahWordMappingService::prepare($ayah);

    $wordRows = $ayah->words->values()->map(function ($word, $index) use ($mapping) {
        $displayArabic = VisibleWordService::analysisDisplayArabicWord(
            $word,
            $mapping['mapped_words'][$index] ?? null
        );
        $analysisMorphologies = VisibleWordService::analysisMorphologies($word);

        return [
            'word' => $word,
            'display_arabic' => $displayArabic,
            'display_segments' => VisibleWordService::colorableSegments($word, $displayArabic),
            'display_word_style' => VisibleWordService::wordColorStyle($word, $displayArabic),
            'display_transliteration' => WordDisplayService::reconstructTransliteration($word),
            'segments' => $analysisMorphologies->values()->map(function ($morphology, $index) use ($analysisMorphologies) {
                $analysis = MorphologyAnalysisService::analyze($morphology, $analysisMorphologies, $index);

                return [
                    'morphology' => $morphology,
                    'card_classes' => MorphologyCardColorService::cardClasses($morphology->pos_tag),
                    'chip_classes' => MorphologyCardColorService::chipClasses($morphology->pos_tag),
                    'compact_chip_classes' => MorphologyCardColorService::compactChipClasses($morphology),
                    'analysis' => $analysis,
                    'chip_arabic_label' => $analysis['chip_arabic_label'],
                    'explanation' => [
                        'english' => $analysis['english'],
                        'urdu' => $analysis['urdu'],
                        'arabic' => $analysis['arabic'],
                        'lemma' => $analysis['lemma'],
                        'root' => $analysis['root'],
                        'details' => $analysis['details'],
                    ],
                ];
            }),
        ];
    });

    $displayBismillahText = $mapping['bismillah_text'] ? AyahWordMappingService::displayBismillah() : null;
    $composedDisplayAyahText = collect([
        $displayBismillahText,
        $wordRows->pluck('display_arabic')->implode(' '),
    ])->filter()->implode(' ');

    $previousAyah = Ayah::with('surah')
        ->where('surah_id', $surah->id)
        ->where('ayah_number', '<', $ayah->ayah_number)
        ->orderByDesc('ayah_number')
        ->first();

    $firstAyahInSurah = Ayah::with('surah')
        ->where('surah_id', $surah->id)
        ->orderBy('ayah_number')
        ->first();

    $lastAyahInSurah = Ayah::with('surah')
        ->where('surah_id', $surah->id)
        ->orderByDesc('ayah_number')
        ->first();

    $previousSurah = Surah::where('number', '<', $surah->number)
        ->orderByDesc('number')
        ->first();

    $nextSurah = Surah::where('number', '>', $surah->number)
        ->orderBy('number')
        ->first();

    $previousSurahFirstAyah = $previousSurah
        ? Ayah::with('surah')
            ->where('surah_id', $previousSurah->id)
            ->orderBy('ayah_number')
            ->first()
        : null;

    $nextSurahFirstAyah = $nextSurah
        ? Ayah::with('surah')
            ->where('surah_id', $nextSurah->id)
            ->orderBy('ayah_number')
            ->first()
        : null;

    if (!$previousAyah) {
        if ($previousSurah) {
            $previousAyah = Ayah::with('surah')
                ->where('surah_id', $previousSurah->id)
                ->orderByDesc('ayah_number')
                ->first();
        }
    }

    $nextAyah = Ayah::with('surah')
        ->where('surah_id', $surah->id)
        ->where('ayah_number', '>', $ayah->ayah_number)
        ->orderBy('ayah_number')
        ->first();

    if (!$nextAyah) {
        if ($nextSurah) {
            $nextAyah = Ayah::with('surah')
                ->where('surah_id', $nextSurah->id)
                ->orderBy('ayah_number')
                ->first();
        }
    }

    $randomAyah = Ayah::with('surah')->inRandomOrder()->first();

    $liveDependencyGraphSvg = $dependencyGraphs->findByReference($surah->number, $ayah->ayah_number);

    $feedbackItems = $ayah->feedback()
        ->where('is_public', true)
        ->where('is_approved', true)
        ->latest()
        ->get();

    $tafseerEntries = $tafseerService->getAyahTafseers($ayah);
    $availableTafseers = $tafseerService->getActiveTafseers();
    $favoriteAyahIds = collect(session('favorite_ayah_ids', []))->map(fn ($id) => (int) $id);
    $ayahNotes = session('ayah_notes', []);

    return view('ayahs.show', [
        'surah' => $surah,
        'ayah' => $ayah,
        'displayAyahText' => QuranText::normalizeArabicForAyahDisplay($ayah->display_text ?: $composedDisplayAyahText),
        'bismillahText' => QuranText::normalizeArabicForAyahDisplay($displayBismillahText),
        'usedWordFallback' => $mapping['used_fallback'],
        'wordRows' => $wordRows,
        'previousAyah' => $previousAyah,
        'nextAyah' => $nextAyah,
        'firstAyahInSurah' => $firstAyahInSurah,
        'lastAyahInSurah' => $lastAyahInSurah,
        'previousSurahFirstAyah' => $previousSurahFirstAyah,
        'nextSurahFirstAyah' => $nextSurahFirstAyah,
        'randomAyah' => $randomAyah,
        'liveDependencyGraphSvg' => $liveDependencyGraphSvg,
        'feedbackItems' => $feedbackItems,
        'tafseerEntries' => $tafseerEntries,
        'availableTafseers' => $availableTafseers,
        'isFavoriteAyah' => $favoriteAyahIds->contains($ayah->id),
        'ayahNote' => $ayahNotes[$ayah->id] ?? '',
    ]);
}

    public function shareCard(Request $request, Surah $surah, Ayah $ayah): View
    {
        abort_unless($ayah->surah_id === $surah->id, 404);

        $ayah->load('surah');

        $displayAyahText = QuranText::normalizeArabicForAyahDisplay($ayah->display_text);

        $availableTranslations = collect([
            [
                'slug' => 'mufti_taqi_urdu',
                'label' => 'مفتی تقی عثمانی',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_mufti_taqi,
            ],
            [
                'slug' => 'tafheem_ul_quran',
                'label' => 'تفہیم القرآن',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_maududi,
            ],
            [
                'slug' => 'kanzul_iman',
                'label' => 'کنز الایمان',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_kanzuliman,
            ],
            [
                'slug' => 'ahmed_ali_lahori',
                'label' => 'احمد علی لاہوری',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_ahmedali,
            ],
            [
                'slug' => 'quran_foundation_urdu',
                'label' => 'قرآن فاؤنڈیشن اردو',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation,
            ],
            [
                'slug' => 'jalandhry_urdu',
                'label' => 'Ø¬Ø§Ù„Ù†Ø¯Ú¾Ø±ÛŒ',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_jalandhry,
            ],
            [
                'slug' => 'bayan_ul_quran_simple',
                'label' => 'Ø¨ÛŒØ§Ù† Ø§Ù„Ù‚Ø±Ø¢Ù† (Ø³Ø§Ø¯Û)',
                'language' => 'ur',
                'direction' => 'rtl',
                'text' => $ayah->urdu_translation_bayan_simple,
            ],
            [
                'slug' => 'mufti_taqi_english',
                'label' => 'Mufti Taqi Usmani (English)',
                'language' => 'en',
                'direction' => 'ltr',
                'text' => $ayah->english_translation_mufti_taqi,
            ],
        ])->filter(fn (array $translation) => filled($translation['text']))->values();

        $selectedTranslationSlugs = collect((array) $request->input('translations', []))
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values();

        if ($selectedTranslationSlugs->isEmpty() && $availableTranslations->isNotEmpty()) {
            $selectedTranslationSlugs = collect([$availableTranslations->first()['slug']]);
        }

        $selectedTranslations = $availableTranslations
            ->filter(fn (array $translation) => $selectedTranslationSlugs->contains($translation['slug']))
            ->values();

        if ($selectedTranslations->isEmpty() && $availableTranslations->isNotEmpty()) {
            $selectedTranslations = collect([$availableTranslations->first()]);
            $selectedTranslationSlugs = collect([$availableTranslations->first()['slug']]);
        }

        $selectedTranslationLabel = $ayah->urdu_translation_mufti_taqi
            ? 'مفتی تقی عثمانی'
            : ($ayah->urdu_translation_maududi
                ? 'تفہیم القرآن'
                : ($ayah->urdu_translation_kanzuliman
                    ? 'کنز الایمان'
                    : ($ayah->urdu_translation_ahmedali
                        ? 'احمد علی لاہوری'
                        : ($ayah->urdu_translation
                            ? 'قرآن فاؤنڈیشن اردو'
                            : 'Mufti Taqi Usmani (English)'))));

        return view('ayahs.share-card', [
            'surah' => $surah,
            'ayah' => $ayah,
            'displayAyahText' => $displayAyahText,
            'availableTranslations' => $availableTranslations,
            'selectedTranslations' => $selectedTranslations,
            'selectedTranslationSlugs' => $selectedTranslationSlugs,
        ]);
    }

    public function storeFeedback(Request $request, Surah $surah, Ayah $ayah): RedirectResponse
    {
        abort_unless($ayah->surah_id === $surah->id, 404);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'comment' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        AyahFeedback::create([
            'ayah_id' => $ayah->id,
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'comment' => $validated['comment'],
            'is_public' => true,
            'is_approved' => true,
        ]);

        return redirect()
            ->route('ayahs.show', [$surah, $ayah])
            ->with('feedback_success', 'Your suggestion has been submitted successfully.');
    }
}
