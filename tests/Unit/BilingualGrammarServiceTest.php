<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Support\BilingualGrammarService;
use App\Support\MorphologyExplanationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BilingualGrammarServiceTest extends TestCase
{
    public function test_it_formats_bilingual_labels(): void
    {
        $this->assertSame('Lemma / اصل لفظ', BilingualGrammarService::label('Lemma'));
        $this->assertSame('Person / شخص', BilingualGrammarService::label('Person'));
    }

    public function test_it_formats_a_bilingual_grammar_phrase(): void
    {
        $morphology = new Morphology([
            'pos_tag' => 'PN',
            'case_type' => 'NOM',
            'raw_features' => 'STEM|POS:PN|NOM',
        ]);

        $explanation = MorphologyExplanationService::explain($morphology, new Collection([$morphology]), 0);

        $this->assertSame('proper noun nominative', $explanation['english']);
        $this->assertSame('اسم علم مرفوع', $explanation['urdu']);
    }

    public function test_it_formats_case_abbreviations_bilingually(): void
    {
        $this->assertSame('NOM / مرفوع', BilingualGrammarService::abbreviation('NOM'));
    }

    public function test_it_keeps_category_headings_separate_from_exact_urdu_labels(): void
    {
        $this->assertSame('سابقہ صرفی', BilingualGrammarService::urduForArabicLabel('سابقة صرفية'));
        $this->assertSame('تجزیہ صرفی', BilingualGrammarService::urduForArabicLabel('تحليل صرفي'));
        $this->assertSame('لاحقہ صرفی', BilingualGrammarService::urduForArabicLabel('لاحقة صرفية'));
    }

    public function test_it_maps_exact_particle_tags_to_correct_urdu_labels(): void
    {
        $conjunction = new Morphology([
            'pos_tag' => 'CONJ',
            'raw_features' => 'PREFIX|w:CONJ+',
        ]);

        $interrogative = new Morphology([
            'pos_tag' => 'INTG',
            'raw_features' => 'PREFIX|A:INTG+',
        ]);

        $vocative = new Morphology([
            'pos_tag' => 'VOC',
            'raw_features' => 'PREFIX|ya+',
        ]);

        $this->assertSame('حرف عطف', MorphologyExplanationService::explain($conjunction, new Collection([$conjunction]), 0)['urdu']);
        $this->assertSame('حرف استفہام', MorphologyExplanationService::explain($interrogative, new Collection([$interrogative]), 0)['urdu']);
        $this->assertSame('حرف ندا', MorphologyExplanationService::explain($vocative, new Collection([$vocative]), 0)['urdu']);
    }

    public function test_it_uses_specific_corpus_labels_for_time_adverbs_and_certainty_particles(): void
    {
        $timeAdverb = new Morphology([
            'pos_tag' => 'T',
            'case_type' => 'ACC',
            'raw_features' => 'STEM|POS:T|ACC',
        ]);

        $certaintyParticle = new Morphology([
            'pos_tag' => 'CERT',
            'raw_features' => 'PREFIX|qad:PART',
        ]);

        $locationAdverb = new Morphology([
            'pos_tag' => 'LOC',
            'raw_features' => 'STEM|POS:LOC',
        ]);

        $timeExplanation = MorphologyExplanationService::explain($timeAdverb, new Collection([$timeAdverb]), 0);
        $certaintyExplanation = MorphologyExplanationService::explain($certaintyParticle, new Collection([$certaintyParticle]), 0);
        $locationExplanation = MorphologyExplanationService::explain($locationAdverb, new Collection([$locationAdverb]), 0);

        $this->assertSame('accusative time adverb', $timeExplanation['english']);
        $this->assertSame('ظرف زمان منصوب', $timeExplanation['arabic']);
        $this->assertSame('ظرف زمان منصوب', $timeExplanation['urdu']);

        $this->assertSame('particle of certainty', $certaintyExplanation['english']);
        $this->assertSame('حرف تحقيق', $certaintyExplanation['arabic']);
        $this->assertSame('حرف تحقیق', $certaintyExplanation['urdu']);

        $this->assertSame('location adverb', $locationExplanation['english']);
        $this->assertSame('ظرف مكان', $locationExplanation['arabic']);
        $this->assertSame('ظرف مکان', $locationExplanation['urdu']);
    }

    public function test_it_provides_pronoun_role_mappings_from_central_config(): void
    {
        $this->assertSame([
            'english' => 'subject pronoun',
            'urdu' => 'ضمیر بطور فاعل',
            'arabic' => 'ضمير متصل في محل رفع فاعل',
        ], BilingualGrammarService::pronounRole('subject'));

        $this->assertSame([
            'english' => 'possessive pronoun',
            'urdu' => 'ضمیر بطور مضاف الیہ',
            'arabic' => 'ضمير متصل في محل جر مضاف إليه',
        ], BilingualGrammarService::pronounRole('possessive'));
    }
}
