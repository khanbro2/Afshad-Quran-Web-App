<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\MorphologyAnalysisService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GrammarConsistencyTest extends TestCase
{
    public function test_same_morphology_segment_yields_same_canonical_analysis_for_all_views(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'EXP',
            'raw_features' => 'STEM|POS:EXP|LEM:<il~aA',
            'lemma' => '<il~aA',
        ]);

        $analysisForAyah = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);
        $analysisForWord = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);

        $this->assertSame($analysisForAyah['arabic'], $analysisForWord['arabic']);
        $this->assertSame('أداة استثناء', $analysisForAyah['arabic']);
        $this->assertSame($analysisForAyah['chip_arabic_label'], $analysisForWord['chip_arabic_label']);
        $this->assertSame($analysisForAyah['urdu'], $analysisForWord['urdu']);
        $this->assertSame($analysisForAyah['english'], $analysisForWord['english']);
    }

    public function test_prepositional_pronouns_stay_consistent_across_consumers(): void
    {
        $segments = new Collection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'P',
                'raw_features' => 'PREFIX|bi',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MS',
            ]),
        ]);

        $analysisForAyah = MorphologyAnalysisService::analyze($segments[1], $segments, 1);
        $analysisForWord = MorphologyAnalysisService::analyze($segments[1], $segments, 1);

        $this->assertSame('ضمير متصل في محل جر بحرف الجر', $analysisForAyah['arabic']);
        $this->assertSame($analysisForAyah['arabic'], $analysisForWord['arabic']);
        $this->assertSame($analysisForAyah['chip_arabic_label'], $analysisForWord['chip_arabic_label']);
        $this->assertSame($analysisForAyah['english'], $analysisForWord['english']);
        $this->assertSame($analysisForAyah['urdu'], $analysisForWord['urdu']);
    }
}
