<?php

namespace App\Support;

use App\Models\Morphology;

class SegmentClassifier
{
    public static function kind(Morphology $morphology): string
    {
        return MorphologyFeatureExtractor::for($morphology)->segmentKind();
    }

    /**
     * @return array{
     *   kind:string,
     *   is_prefix:bool,
     *   is_suffix:bool,
     *   is_stem:bool,
     *   is_visible_analysis:bool
     * }
     */
    public static function metadata(Morphology $morphology): array
    {
        $kind = self::kind($morphology);

        return [
            'kind' => $kind,
            'is_prefix' => $kind === 'PREFIX',
            'is_suffix' => $kind === 'SUFFIX',
            'is_stem' => $kind === 'STEM',
            'is_visible_analysis' => $morphology->pos_tag !== 'DET',
        ];
    }
}
