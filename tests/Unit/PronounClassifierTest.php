<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\PronounClassifier;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PronounClassifierTest extends TestCase
{
    public function test_it_classifies_detached_pronouns_explicitly(): void
    {
        $pronoun = new Morphology([
            'pos_tag' => 'PRON',
            'raw_features' => 'STEM|POS:PRON',
        ]);

        $classification = PronounClassifier::classify($pronoun, new Collection([$pronoun]), 0);

        $this->assertSame('detached', $classification['type']);
        $this->assertSame('none', $classification['role']);
        $this->assertSame('detached pronoun', $classification['english_label_key']);
        $this->assertSame('ضمير منفصل', $classification['arabic_exact_label']);
    }

    public function test_it_classifies_attached_pronouns_after_prepositions_as_prepositional(): void
    {
        $examples = ['bi', 'li', 'fiy', 'EalaY'];

        foreach ($examples as $prefix) {
            $segments = new Collection([
                new Morphology([
                    'segment_number' => 1,
                    'pos_tag' => 'P',
                    'raw_features' => 'PREFIX|'.$prefix,
                ]),
                new Morphology([
                    'segment_number' => 2,
                    'pos_tag' => 'PRON',
                    'raw_features' => 'SUFFIX|PRON:3MP',
                ]),
            ]);

            $classification = PronounClassifier::classify($segments[1], $segments, 1);

            $this->assertSame('attached', $classification['type']);
            $this->assertSame('prepositional', $classification['role']);
            $this->assertSame('prepositional pronoun', $classification['english_label_key']);
            $this->assertSame('ضمير متصل في محل جر بحرف الجر', $classification['arabic_exact_label']);
            $this->assertSame('high', $classification['confidence']);
        }
    }

    public function test_it_treats_stem_pronouns_after_prepositions_as_prepositional_not_detached(): void
    {
        $segments = new Collection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'P',
                'raw_features' => 'PREFIX|bi+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'STEM|POS:PRON|3MS',
            ]),
        ]);

        $classification = PronounClassifier::classify($segments[1], $segments, 1);

        $this->assertSame('attached', $classification['type']);
        $this->assertSame('prepositional', $classification['role']);
        $this->assertSame('prepositional pronoun', $classification['english_label_key']);
        $this->assertSame('ضمير متصل في محل جر بحرف الجر', $classification['arabic_exact_label']);
        $this->assertSame('attached-to-preposition', $classification['reason']);
    }

    public function test_it_prefers_governing_preposition_over_nearest_noun_like_host(): void
    {
        $segments = new Collection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'P',
                'raw_features' => 'PREFIX|bi+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:muzaHoziH|ROOT:zHzH|M|GEN',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MS',
            ]),
        ]);

        $classification = PronounClassifier::classify($segments[2], $segments, 2);

        $this->assertSame('attached', $classification['type']);
        $this->assertSame('prepositional', $classification['role']);
        $this->assertSame('prepositional pronoun', $classification['english_label_key']);
        $this->assertSame('ضمير متصل في محل جر بحرف الجر', $classification['arabic_exact_label']);
        $this->assertSame('attached-to-preposition', $classification['reason']);
    }

    public function test_it_falls_back_to_attached_generic_when_role_is_not_safe(): void
    {
        $segments = new Collection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'CONJ',
                'raw_features' => 'PREFIX|w:CONJ+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MS',
            ]),
        ]);

        $classification = PronounClassifier::classify($segments[1], $segments, 1);

        $this->assertSame('attached', $classification['type']);
        $this->assertSame('none', $classification['role']);
        $this->assertSame('attached pronoun', $classification['english_label_key']);
        $this->assertSame('ضمير متصل', $classification['arabic_exact_label']);
        $this->assertSame('medium', $classification['confidence']);
    }
}
