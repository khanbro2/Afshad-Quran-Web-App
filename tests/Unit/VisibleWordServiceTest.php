<?php

namespace Tests\Unit;

use App\Models\Morphology;
use App\Models\Word;
use App\Support\QuranText;
use App\Support\VisibleWordService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class VisibleWordServiceTest extends TestCase
{
    public function test_it_returns_segment_spans_for_a_joined_word(): void
    {
        $word = new Word([
            'form' => 'wna',
            'transliteration' => 'fayattabiʿūna',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'CONJ',
                'raw_features' => 'PREFIX|f:CONJ+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|IMPF|3MP',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MP',
            ]),
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'فيتبعون');

        $this->assertSame(['ف', 'يتبع', 'ون'], array_column($segments, 'text'));
        $this->assertSame(['text-teal-700', 'text-sky-700', 'text-violet-700'], array_column($segments, 'classes'));
    }

    public function test_it_builds_vocalized_display_words_while_matching_remains_normalized(): void
    {
        $word = new Word([
            'form' => '>ay~uhaA',
            'arabic_text' => 'أَيُّهَا',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'VOC',
                'raw_features' => 'PREFIX|ya+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:>ay~uhaA|NOM',
            ]),
        ]));

        $this->assertSame('يَا أَيُّهَا', VisibleWordService::displayArabicWord($word, 'يا أيها'));
        $this->assertSame('ياايها', str_replace(' ', '', QuranText::normalizeArabicForMatching('يَا أَيُّهَا')));
    }

    public function test_it_prefers_richer_uthmani_pronoun_surface_when_tokens_match_normally(): void
    {
        $word = new Word([
            'form' => 'bh',
            'arabic_text' => 'بِهِ',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
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
        ]));

        $this->assertSame('بِهِۦ', VisibleWordService::displayArabicWord($word, 'بِهِۦ'));

        $lamWord = new Word([
            'form' => 'lh',
            'arabic_text' => 'لَهُ',
        ]);
        $lamWord->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'P',
                'raw_features' => 'PREFIX|li',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:3MS',
            ]),
        ]));

        $this->assertSame('لَهُۥ', VisibleWordService::displayArabicWord($lamWord, 'لَهُۥ'));
    }

    public function test_it_provides_cleaner_analysis_word_display_without_quranic_annotation_marks(): void
    {
        $biWord = new Word([
            'form' => 'bh',
            'arabic_text' => 'بِهِ',
        ]);
        $biWord->setRelation('morphologies', new EloquentCollection([
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
        ]));

        $this->assertSame('بِهِ', VisibleWordService::analysisDisplayArabicWord($biWord, 'بِهِۦ'));

        $mathalWord = new Word([
            'form' => 'mavalFA',
            'arabic_text' => 'مَثَلًا',
        ]);
        $mathalWord->setRelation('morphologies', new EloquentCollection([]));

        $this->assertSame('مَثَلًا', VisibleWordService::analysisDisplayArabicWord($mathalWord, 'مَثَلًۭا'));

        $samawatWord = new Word([
            'form' => 'Alsmwt',
            'arabic_text' => 'ٱلسَّمَٰوَاتِ',
        ]);
        $samawatWord->setRelation('morphologies', new EloquentCollection([]));

        $this->assertSame('ٱلسَّمٰوَاتِ', VisibleWordService::analysisDisplayArabicWord($samawatWord, 'ٱلسَّمَٰوَاتِ'));
    }

    public function test_it_keeps_prefix_stem_and_suffix_as_separate_colored_segments(): void
    {
        $word = new Word([
            'form' => '>asolamtumo',
            'arabic_text' => 'أسلمتم',
            'transliteration' => 'a-aslamtum',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'INTG',
                'raw_features' => 'PREFIX|A:INTG+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|PERF|(IV)|LEM:>asolama|ROOT:slm|2MP',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:2MP',
            ]),
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'ءَأَسْلَمْتُمْ');

        $this->assertCount(3, $segments);
        $this->assertSame('text-teal-700', $segments[0]['classes']);
        $this->assertSame('text-sky-700', $segments[1]['classes']);
        $this->assertSame('text-violet-700', $segments[2]['classes']);
        $this->assertSame('تُمْ', $segments[2]['text']);
    }

    public function test_it_extracts_imperative_plural_suffixes_without_collapsing_the_whole_word(): void
    {
        $word = new Word([
            'form' => 'wA@',
            'arabic_text' => 'وا@',
            'transliteration' => 'wa-ittaqū',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'CONJ',
                'raw_features' => 'PREFIX|w:CONJ+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|IMPV|(VIII)|LEM:{t~aqaY`|ROOT:wqy|2MP',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:2MP',
            ]),
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'وَٱتَّقُوا۟');

        $this->assertSame(['PREFIX', 'STEM', 'SUFFIX'], array_column($segments, 'kind'));
        $this->assertSame('وَ', $segments[0]['text']);
        $this->assertSame('وا۟', mb_substr($segments[2]['text'], -3, null, 'UTF-8'));
    }

    public function test_it_hides_det_from_analysis_but_keeps_joined_word_behavior_for_bil_akhirah(): void
    {
        $word = new Word([
            'form' => 'biAl|xrp',
            'arabic_text' => 'بِالْآخِرَةِ',
        ]);

        $morphologies = new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'P',
                'raw_features' => 'PREFIX|bi+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'DET',
                'raw_features' => 'PREFIX|Al+',
            ]),
            new Morphology([
                'segment_number' => 3,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:>aAxirap|GEN',
            ]),
        ]);

        $word->setRelation('morphologies', $morphologies);

        $analysis = VisibleWordService::analysisMorphologies($word);
        $segments = VisibleWordService::colorableSegments($word, 'بِالْآخِرَةِ');

        $this->assertSame(['P', 'N'], $analysis->pluck('pos_tag')->all());
        $this->assertSame(['بِ', 'الْآخِرَةِ'], array_column($segments, 'text'));
    }

    public function test_it_extracts_marked_preposition_pronoun_suffixes_without_losing_uthmani_marks(): void
    {
        $word = new Word([
            'form' => 'bh',
            'arabic_text' => 'بِهِ',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
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
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'بِهِۦ');

        $this->assertSame(['بِ', 'هِۦ'], array_column($segments, 'text'));
        $this->assertSame(['PREFIX', 'SUFFIX'], array_column($segments, 'kind'));
    }

    public function test_it_keeps_subject_and_object_suffixes_separate_for_razaqnahum(): void
    {
        $word = new Word([
            'form' => 'rzqnAhm',
            'arabic_text' => 'رَزَقْنَاهُمْ',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|PERF|(I)|LEM:razaqa|ROOT:rzq|1P',
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
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'رَزَقْنَاهُمْ');

        $this->assertSame(['STEM', 'SUFFIX', 'SUFFIX'], array_column($segments, 'kind'));
        $this->assertSame('هم', QuranText::removeDiacritics($segments[2]['text']));
    }

    public function test_it_extracts_first_person_singular_perfect_subject_suffix_separately_before_object_suffix(): void
    {
        $word = new Word([
            'form' => 'faD~altukum',
            'arabic_text' => 'فَضَّلْتُكُمْ',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
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
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'فَضَّلْتُكُمْ');

        $this->assertSame(['STEM', 'SUFFIX', 'SUFFIX'], array_column($segments, 'kind'));
        $this->assertSame(['فَضَّلْ', 'تُ', 'كُمْ'], array_column($segments, 'text'));
        $this->assertSame(['text-sky-700', 'text-amber-700', 'text-violet-700'], array_column($segments, 'classes'));
    }

    public function test_it_extracts_first_person_singular_possessive_suffix_written_with_alif_maqsurah(): void
    {
        $word = new Word([
            'form' => '*ur~iy~atiy',
            'arabic_text' => 'ذُرِّيَّتِى',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|LEM:*ur~iy~ap|ROOT:*rr|F|GEN',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:1S',
            ]),
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'ذُرِّيَّتِى');

        $this->assertSame(['STEM', 'SUFFIX'], array_column($segments, 'kind'));
        $this->assertSame(['ذُرِّيَّتِ', 'ى'], array_column($segments, 'text'));
        $this->assertSame(['text-sky-700', 'text-violet-700'], array_column($segments, 'classes'));
    }

    public function test_it_extracts_second_person_plural_imperfect_suffix_written_as_waw_nun(): void
    {
        $word = new Word([
            'form' => 'wna',
            'arabic_text' => 'ونَ',
            'transliteration' => "tus'alūna",
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'V',
                'raw_features' => 'STEM|POS:V|IMPF|PASS|LEM:sa>ala|ROOT:sAl|2MP',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'PRON',
                'raw_features' => 'SUFFIX|PRON:2MP',
            ]),
        ]));

        $segments = VisibleWordService::colorableSegments($word, 'تُسْـَلُونَ');

        $this->assertSame(['STEM', 'SUFFIX'], array_column($segments, 'kind'));
        $this->assertSame(['تُسْـَلُ', 'ونَ'], array_column($segments, 'text'));
        $this->assertSame(['text-sky-700', 'text-violet-700'], array_column($segments, 'classes'));
    }
    public function test_it_uses_fathah_for_emphatic_lam_prefix_in_analysis_display(): void
    {
        $word = new Word([
            'form' => 'Ha`fiZuwna',
            'arabic_text' => 'Ø­ÙÙ°ÙÙØ¸ÙÙˆÙ†Ù',
        ]);

        $word->setRelation('morphologies', new EloquentCollection([
            new Morphology([
                'segment_number' => 1,
                'pos_tag' => 'EMPH',
                'raw_features' => 'PREFIX|l:EMPH+',
            ]),
            new Morphology([
                'segment_number' => 2,
                'pos_tag' => 'N',
                'raw_features' => 'STEM|POS:N|ACT|PCPL|LEM:Ha`fiZ|ROOT:HfZ|MP|NOM',
            ]),
        ]));

        $this->assertStringStartsWith('Ù„ÙŽ', VisibleWordService::analysisDisplayArabicWord($word, 'Ù„ÙŽØ­ÙÙ°ÙÙØ¸ÙÙˆÙ†Ù'));
    }
}
