<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\GrammarLabelService;
use App\Support\MorphologyAnalysisService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MorphologyAnalysisServiceTest extends TestCase
{
    public function test_it_surfaces_voice_and_derived_form_for_verbs(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'V',
            'raw_features' => 'STEM|POS:V|IMPF|PASS|(X)|ROOT:qwl|3MP',
            'derived_form' => 'X',
            'voice' => 'PASS',
        ]);

        $analysis = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);

        $this->assertSame('3rd person plural passive imperfect verb', $analysis['english']);
        $this->assertSame('فعل مضارع', $analysis['chip_arabic_label']);
        $this->assertSame('PASS / مجہول', $analysis['details'] !== '' ? collect(explode(' | ', $analysis['details']))->last() : null);
        $this->assertSame('PASS', $analysis['verb']['voice']);
        $this->assertSame('X', $analysis['verb']['derived_form']);
        $this->assertContains(['label' => 'Mood', 'value' => 'IMPF / مضارع'], $analysis['attributes']);
    }

    public function test_it_marks_unknown_tags_as_explicit_fallbacks(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'XYZ',
            'raw_features' => 'PREFIX|mystery+',
        ]);

        $analysis = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);

        $this->assertTrue($analysis['fallback']['used']);
        $this->assertSame('سابقة صرفية', $analysis['arabic']);
        $this->assertSame('سابقة صرفية', GrammarLabelService::fallbackLabel($morphology));
    }

    public function test_chip_and_explanation_labels_do_not_contradict_for_known_tags(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'CERT',
            'raw_features' => 'PREFIX|qad:PART',
        ]);

        $analysis = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);

        $this->assertSame('حرف تحقيق', $analysis['arabic']);
        $this->assertSame('حرف تحقيق', $analysis['chip_arabic_label']);
        $this->assertFalse($analysis['fallback']['used']);
    }

    public function test_it_prefers_exact_particle_labels_for_la_and_illa_cases(): void
    {
        $prohibitiveLa = new Morphology([
            'pos_tag' => 'PRO',
            'raw_features' => 'STEM|POS:PRO|LEM:laA',
            'lemma' => 'laA',
        ]);

        $negativeLam = new Morphology([
            'pos_tag' => 'NEG',
            'raw_features' => 'STEM|POS:NEG|LEM:lam',
            'lemma' => 'lam',
        ]);

        $exceptiveIlla = new Morphology([
            'pos_tag' => 'EXP',
            'raw_features' => 'STEM|POS:EXP|LEM:<il~aA',
            'lemma' => '<il~aA',
        ]);

        $restrictiveIlla = new Morphology([
            'pos_tag' => 'RES',
            'raw_features' => 'STEM|POS:RES|LEM:<il~aA',
            'lemma' => '<il~aA',
        ]);

        $this->assertSame('حرف نهي', MorphologyAnalysisService::analyze($prohibitiveLa, new Collection([$prohibitiveLa]), 0)['arabic']);
        $this->assertSame('حرف نفي وجزم', MorphologyAnalysisService::analyze($negativeLam, new Collection([$negativeLam]), 0)['arabic']);
        $this->assertSame('أداة استثناء', MorphologyAnalysisService::analyze($exceptiveIlla, new Collection([$exceptiveIlla]), 0)['arabic']);
        $this->assertSame('أداة استثناء', MorphologyAnalysisService::analyze($restrictiveIlla, new Collection([$restrictiveIlla]), 0)['arabic']);
    }

    public function test_supported_particle_tags_do_not_fall_back_to_generic_headings(): void
    {
        $supported = [
            new Morphology(['pos_tag' => 'SUB', 'raw_features' => 'PREFIX|>an:SUB+']),
            new Morphology(['pos_tag' => 'PREV', 'raw_features' => 'STEM|POS:PREV|LEM:maA']),
            new Morphology(['pos_tag' => 'CAUS', 'raw_features' => 'PREFIX|f:CAUS+']),
            new Morphology(['pos_tag' => 'SUR', 'raw_features' => 'STEM|POS:SUR']),
            new Morphology(['pos_tag' => 'REP', 'raw_features' => 'STEM|POS:REP']),
        ];

        foreach ($supported as $morphology) {
            $analysis = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);
            $this->assertFalse($analysis['fallback']['used'], 'Unexpected fallback for '.$morphology->pos_tag);
            $this->assertNotSame('تحليل صرفي', $analysis['arabic']);
        }
    }

    public function test_it_exposes_only_relevant_attributes_for_noun_participle_segments(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'N',
            'raw_features' => 'STEM|POS:N|ACT|PCPL|LEM:ma`lik|ROOT:mlk|M|GEN',
            'gender' => 'M',
            'case_type' => 'GEN',
        ]);

        $analysis = MorphologyAnalysisService::analyze($morphology, new Collection([$morphology]), 0);

        $this->assertSame([
            ['label' => 'Case', 'value' => 'GEN / مجرور'],
            ['label' => 'Gender', 'value' => 'M / مذکر'],
            ['label' => 'Participle Type', 'value' => 'active participle / اسم فاعل'],
        ], $analysis['attributes']);
    }

    public function test_it_exposes_only_relevant_attributes_for_pronouns(): void
    {
        $segments = new Collection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:qalob|ROOT:qlb|GEN',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MP',
            ]),
        ]);

        $analysis = MorphologyAnalysisService::analyze($segments[1], $segments, 1);

        $this->assertSame([
            ['label' => 'Person', 'value' => '3rd person / غائب'],
            ['label' => 'Gender', 'value' => 'masculine / مذکر'],
            ['label' => 'Number', 'value' => 'plural / جمع'],
        ], $analysis['attributes']);
    }
}
