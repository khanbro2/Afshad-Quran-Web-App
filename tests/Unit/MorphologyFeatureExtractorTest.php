<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\MorphologyFeatureExtractor;
use Tests\TestCase;

class MorphologyFeatureExtractorTest extends TestCase
{
    public function test_it_extracts_segment_kind_and_pronoun_type_flags(): void
    {
        $prefix = MorphologyFeatureExtractor::for(new Morphology([
            'pos_tag' => 'CONJ',
            'raw_features' => 'PREFIX|w:CONJ+',
        ]));

        $attachedPronoun = MorphologyFeatureExtractor::for(new Morphology([
            'pos_tag' => 'PRON',
            'raw_features' => 'SUFFIX|PRON:3MP',
        ]));

        $detachedPronoun = MorphologyFeatureExtractor::for(new Morphology([
            'pos_tag' => 'PRON',
            'raw_features' => 'STEM|POS:PRON',
        ]));

        $this->assertSame('PREFIX', $prefix->segmentKind());
        $this->assertTrue($attachedPronoun->isAttachedPronoun());
        $this->assertTrue($detachedPronoun->isDetachedPronoun());
    }

    public function test_it_normalizes_verb_features_from_structured_fields_and_raw_features(): void
    {
        $features = MorphologyFeatureExtractor::for(new Morphology([
            'pos_tag' => 'V',
            'raw_features' => 'STEM|POS:V|IMPF|PASS|3MP',
            'voice' => null,
            'mood' => null,
            'derived_form' => 'X',
        ]));

        $this->assertSame('IMPF', $features->mood());
        $this->assertSame('imperfect', $features->tenseAspect());
        $this->assertSame('PASS', $features->voice());
        $this->assertSame('X', $features->derivedForm());
        $this->assertSame('3MP', $features->personCode());
    }
}
