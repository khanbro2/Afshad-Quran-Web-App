<?php

namespace App\Support;

use App\Models\Morphology;
use Illuminate\Support\Collection;

class MorphologyExplanationService
{
    /**
     * Backward-compatible facade around the canonical morphology analysis pipeline.
     *
     * @param  Collection<int, Morphology>  $analysisMorphologies
     * @return array{
     *   english:string,
     *   urdu:string,
     *   arabic:string,
     *   lemma:string,
     *   root:string,
     *   details:string
     * }
     */
    public static function explain(Morphology $morphology, Collection $analysisMorphologies, int $index): array
    {
        $analysis = MorphologyAnalysisService::analyze($morphology, $analysisMorphologies, $index);

        return [
            'english' => $analysis['english'],
            'urdu' => $analysis['urdu'],
            'arabic' => $analysis['arabic'],
            'lemma' => $analysis['lemma'],
            'root' => $analysis['root'],
            'details' => $analysis['details'],
        ];
    }
}
