<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\MorphologyExplanationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PronounRoleAssignmentTest extends TestCase
{
    public function test_it_marks_subject_and_object_pronouns_separately_after_a_verb(): void
    {
        $segments = collect([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'CONJ',
                'raw_features' => 'PREFIX|w:CONJ+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|IMPF|(X)|LEM:{sotaEojala|ROOT:Ejl|3MP',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MP',
            ]),
            new Morphology([
                'segment_number' => 4,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:2MS',
            ]),
        ]);

        $subject = MorphologyExplanationService::explain($segments[2], $segments, 2);
        $object = MorphologyExplanationService::explain($segments[3], $segments, 3);

        $this->assertSame('3rd person masculine plural subject pronoun', $subject['english']);
        $this->assertSame('ضمير متصل في محل رفع فاعل', $subject['arabic']);
        $this->assertStringContainsString('فاعل', $subject['urdu']);

        $this->assertSame('2nd person masculine singular object pronoun', $object['english']);
        $this->assertSame('ضمير متصل في محل نصب مفعول به', $object['arabic']);
        $this->assertStringContainsString('مفعول', $object['urdu']);
    }

    public function test_it_marks_first_person_singular_perfect_suffix_as_subject_before_later_object_suffix(): void
    {
        $segments = collect([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|PERF|(II)|LEM:faD~ala|ROOT:fDl|1S',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:1S',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:2MP',
            ]),
        ]);

        $subject = MorphologyExplanationService::explain($segments[1], $segments, 1);
        $object = MorphologyExplanationService::explain($segments[2], $segments, 2);

        $this->assertSame('1st person singular subject pronoun', $subject['english']);
        $this->assertSame('ضمير متصل في محل رفع فاعل', $subject['arabic']);
        $this->assertStringContainsString('فاعل', $subject['urdu']);

        $this->assertSame('2nd person masculine plural object pronoun', $object['english']);
        $this->assertSame('ضمير متصل في محل نصب مفعول به', $object['arabic']);
        $this->assertStringContainsString('مفعول', $object['urdu']);
    }

    public function test_it_marks_pronouns_after_nouns_as_possessive(): void
    {
        $segments = collect([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:qalob|ROOT:qlb|GEN',
                'lemma' => 'qalob',
                'root' => 'qlb',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MP',
            ]),
        ]);

        $possessive = MorphologyExplanationService::explain($segments[1], $segments, 1);

        $this->assertSame('3rd person masculine plural possessive pronoun', $possessive['english']);
        $this->assertSame('ضمير متصل في محل جر مضاف إليه', $possessive['arabic']);
        $this->assertStringContainsString('مضاف', $possessive['urdu']);
    }

    public function test_it_marks_pronouns_after_prepositions_as_prepositional(): void
    {
        $examples = [
            ['PREFIX|bi', 'SUFFIX|PRON:3MS'],
            ['PREFIX|li', 'SUFFIX|PRON:3MS'],
            ['PREFIX|bi', 'SUFFIX|PRON:3MP'],
            ['PREFIX|fiy', 'SUFFIX|PRON:3MS'],
            ['PREFIX|EalaY', 'SUFFIX|PRON:3MP'],
        ];

        foreach ($examples as [$prefixFeatures, $pronounFeatures]) {
            $segments = collect([
                new Morphology([
                    'segment_number' => 1,
                    'pos_tag' => 'P',
                    'raw_features' => $prefixFeatures,
                ]),
                new Morphology([
                    'segment_number' => 2,
                    'pos_tag' => 'PRON',
                    'raw_features' => $pronounFeatures,
                ]),
            ]);

            $analysis = MorphologyExplanationService::explain($segments[1], $segments, 1);

            $this->assertStringEndsWith('prepositional pronoun', $analysis['english']);
            $this->assertSame('ضمير متصل في محل جر بحرف الجر', $analysis['arabic']);
            $this->assertStringContainsString('بحرف جر', $analysis['urdu']);
        }
    }

    public function test_it_marks_stem_pronouns_after_prepositions_as_prepositional_in_runtime_style_rows(): void
    {
        $segments = collect([
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

        $analysis = MorphologyExplanationService::explain($segments[1], $segments, 1);

        $this->assertSame('prepositional pronoun', $analysis['english']);
        $this->assertSame('ضمير متصل في محل جر بحرف الجر', $analysis['arabic']);
        $this->assertStringContainsString('بحرف جر', $analysis['urdu']);
    }

    public function test_it_marks_pronouns_as_prepositional_even_when_a_noun_like_segment_sits_between(): void
    {
        $segments = collect([
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

        $analysis = MorphologyExplanationService::explain($segments[2], $segments, 2);

        $this->assertStringEndsWith('prepositional pronoun', $analysis['english']);
        $this->assertSame('ضمير متصل في محل جر بحرف الجر', $analysis['arabic']);
        $this->assertStringContainsString('بحرف جر', $analysis['urdu']);
    }

    public function test_it_marks_plural_verb_suffixes_as_subject_pronouns(): void
    {
        $cases = [
            [
                new Morphology([
                    'segment_number' => 1,
                    'pos_tag' => 'V',
                    'raw_features' => 'STEM|POS:V|IMPV|(VIII)|LEM:{t~aqaY`|ROOT:wqy|2MP',
                ]),
                new Morphology([
                    'segment_number' => 2,
                    'pos_tag' => 'PRON',
                    'raw_features' => 'SUFFIX|PRON:2MP',
                ]),
            ],
            [
                new Morphology([
                    'segment_number' => 1,
                    'pos_tag' => 'V',
                    'raw_features' => 'STEM|POS:V|PERF|(I)|LEM:faEala|ROOT:fEl|3MP',
                ]),
                new Morphology([
                    'segment_number' => 2,
                    'pos_tag' => 'PRON',
                    'raw_features' => 'SUFFIX|PRON:3MP',
                ]),
            ],
            [
                new Morphology([
                    'segment_number' => 1,
                    'pos_tag' => 'V',
                    'raw_features' => 'STEM|POS:V|IMPF|(IV)|LEM:>anfaqa|ROOT:nfq|3MP',
                ]),
                new Morphology([
                    'segment_number' => 2,
                    'pos_tag' => 'PRON',
                    'raw_features' => 'SUFFIX|PRON:3MP',
                ]),
            ],
        ];

        foreach ($cases as $segments) {
            $collection = new Collection($segments);
            $subject = MorphologyExplanationService::explain($collection[1], $collection, 1);

            $this->assertStringEndsWith('subject pronoun', $subject['english']);
            $this->assertSame('ضمير متصل في محل رفع فاعل', $subject['arabic']);
        }
    }

    public function test_it_does_not_copy_verb_lemma_or_root_into_pronoun_segments(): void
    {
        $segments = collect([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|PERF|(I)|LEM:razaqa|ROOT:rzq|1P',
                'lemma' => 'razaqa',
                'root' => 'rzq',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:1P',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MP',
            ]),
        ]);

        $subject = MorphologyExplanationService::explain($segments[1], $segments, 1);
        $object = MorphologyExplanationService::explain($segments[2], $segments, 2);

        $this->assertSame('N/A', $subject['lemma']);
        $this->assertSame('N/A', $subject['root']);
        $this->assertSame('N/A', $object['lemma']);
        $this->assertSame('N/A', $object['root']);
    }
}
