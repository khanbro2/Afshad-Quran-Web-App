<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Support\MorphologyAnalysisService;
use App\Support\MorphologyCardColorService;
use App\Support\WordDisplayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WordController extends Controller
{
    public function show(Word $word): View
    {
        $word->load([
            'ayah.surah',
            'lemma',
            'root',
            'morphologies' => fn ($query) => $query->orderBy('segment_number'),
        ]);

        $analysisMorphologies = $word->morphologies->values();
        $matchingWordsQuery = $this->buildMatchingWordsQuery($word, $analysisMorphologies);

        $occurrenceCount = (clone $matchingWordsQuery)->count();

        $occurrences = (clone $matchingWordsQuery)
            ->with('ayah.surah')
            ->orderBy('surah_number')
            ->orderBy('ayah_number')
            ->orderBy('position')
            ->get(['id', 'ayah_id', 'surah_number', 'ayah_number', 'position']);

        $segmentRows = $analysisMorphologies->map(fn ($morphology, $index) => [
            'morphology' => $morphology,
            'card_classes' => MorphologyCardColorService::cardClasses($morphology->pos_tag),
            'chip_classes' => MorphologyCardColorService::chipClasses($morphology->pos_tag),
            'analysis' => MorphologyAnalysisService::analyze($morphology, $analysisMorphologies, $index),
        ]);

        $favoriteWordIds = collect(session('favorite_word_ids', []))->map(fn ($id) => (int) $id);
        $wordNotes = session('word_notes', []);

        return view('words.show', [
            'word' => $word,
            'displayArabic' => WordDisplayService::reconstructAnalysisArabicWord($word),
            'displayTransliteration' => WordDisplayService::reconstructTransliteration($word),
            'displayEnglishTranslation' => $word->translation_basic,
            'displayUrduTranslation' => $this->resolveDisplayUrduTranslation($word, $analysisMorphologies),
            'occurrenceCount' => $occurrenceCount,
            'occurrences' => $occurrences,
            'isFavoriteWord' => $favoriteWordIds->contains($word->id),
            'wordNote' => $wordNotes[$word->id] ?? '',
            'segmentRows' => $segmentRows,
        ]);
    }

    protected function buildMatchingWordsQuery(Word $word, Collection $analysisMorphologies): Builder
    {
        $signatureMorphologies = $analysisMorphologies
            ->filter(fn ($morphology) => filled($morphology->raw_features))
            ->values();

        if ($signatureMorphologies->isNotEmpty()) {
            $segmentCount = $word->segment_count ?: $signatureMorphologies->count();

            $query = Word::query()
                ->where('segment_count', $segmentCount)
                ->has('morphologies', '=', $signatureMorphologies->count());

            foreach ($signatureMorphologies as $morphology) {
                $segmentNumber = $morphology->segment_number;
                $rawFeatures = $morphology->raw_features;

                $query->whereHas('morphologies', function (Builder $segmentQuery) use ($segmentNumber, $rawFeatures) {
                    $segmentQuery
                        ->where('segment_number', $segmentNumber)
                        ->where('raw_features', $rawFeatures);
                });
            }

            return $query;
        }

        return Word::query()
            ->when(
                filled($word->normalized_text),
                fn ($query) => $query->where('normalized_text', $word->normalized_text),
                fn ($query) => $query->when(
                    filled($word->arabic_text),
                    fn ($fallbackQuery) => $fallbackQuery->where('arabic_text', $word->arabic_text),
                    fn ($fallbackQuery) => $fallbackQuery->where('form', $word->form)
                )
            );
    }

    protected function resolveDisplayUrduTranslation(Word $word, Collection $analysisMorphologies): ?string
    {
        $translation = trim((string) $word->translation_urdu);

        if ($translation === '') {
            return null;
        }

        if ($analysisMorphologies->count() <= 1) {
            return $translation;
        }

        $compact = preg_replace('/\s+/u', '', $translation) ?? $translation;
        $likelySuffixOnlyTranslations = ['کم', 'تم', 'ہم', 'میں', 'میں', 'تو', 'تمہارا', 'تمھارا'];

        if (in_array($compact, $likelySuffixOnlyTranslations, true) || mb_strlen($compact) <= 3) {
            return null;
        }

        return $translation;
    }
}
