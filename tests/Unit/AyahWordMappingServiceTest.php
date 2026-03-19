<?php

namespace Tests\Unit;

use App\Models\Ayah;
use App\Models\Morphology;
use App\Models\Word;
use App\Support\AyahWordMappingService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class AyahWordMappingServiceTest extends TestCase
{
    public function test_it_separates_bismillah_before_mapping_word_rows(): void
    {
        $ayah = new Ayah([
            'full_arabic_text' => 'بسم   الله الرحمن الرحيم   يا أيها الناس',
            'ayah_number' => 1,
        ]);

        $ayah->setRelation('words', new EloquentCollection([
            $this->makeWord(1, '>ay~uhaA', 'أَيُّهَا', [
                $this->makeMorphology(1, 'VOC', 'PREFIX|ya+'),
                $this->makeMorphology(2, 'N', 'STEM|POS:N|LEM:>ay~uhaA|NOM'),
            ]),
            $this->makeWord(2, 'n~aAsu', 'نَّاسُ', [
                $this->makeMorphology(1, 'DET', 'PREFIX|Al+'),
                $this->makeMorphology(2, 'N', 'STEM|POS:N|LEM:n~aAs|ROOT:nws|MP|NOM'),
            ]),
        ]));

        $prepared = AyahWordMappingService::prepare($ayah);

        $this->assertTrue(AyahWordMappingService::startsWithBismillah($ayah->display_text));
        $this->assertSame('بسم الله الرحمن الرحيم', $prepared['bismillah_text']);
        $this->assertSame(['يا أيها', 'الناس'], $prepared['mapped_words']);
        $this->assertFalse($prepared['used_fallback']);
    }

    public function test_it_supports_joined_visible_words_like_ya_ayyuha(): void
    {
        $ayah = new Ayah([
            'full_arabic_text' => 'يا أيها الناس',
            'ayah_number' => 1,
        ]);

        $ayah->setRelation('words', new EloquentCollection([
            $this->makeWord(1, '>ay~uhaA', 'أَيُّهَا', [
                $this->makeMorphology(1, 'VOC', 'PREFIX|ya+'),
                $this->makeMorphology(2, 'N', 'STEM|POS:N|LEM:>ay~uhaA|NOM'),
            ]),
            $this->makeWord(2, 'n~aAsu', 'نَّاسُ', [
                $this->makeMorphology(1, 'DET', 'PREFIX|Al+'),
                $this->makeMorphology(2, 'N', 'STEM|POS:N|LEM:n~aAs|ROOT:nws|MP|NOM'),
            ]),
        ]));

        $prepared = AyahWordMappingService::prepare($ayah);

        $this->assertNull($prepared['bismillah_text']);
        $this->assertSame(['يا أيها', 'الناس'], $prepared['mapped_words']);
        $this->assertFalse($prepared['used_fallback']);
    }

    public function test_it_falls_back_safely_when_display_tokens_do_not_match_word_rows(): void
    {
        $ayah = new Ayah([
            'full_arabic_text' => 'كلمة واحدة',
            'ayah_number' => 1,
        ]);

        $ayah->setRelation('words', new EloquentCollection([
            $this->makeWord(1, 'kalima', 'كلمة'),
            $this->makeWord(2, 'wAHida', 'واحدة'),
            $this->makeWord(3, 'zA^ida', 'زائدة'),
        ]));

        $prepared = AyahWordMappingService::prepare($ayah);

        $this->assertTrue($prepared['used_fallback']);
        $this->assertSame(['كلمة', 'واحدة', 'زائدة'], $prepared['mapped_words']);
    }

    public function test_it_aligns_uthmani_marked_pronoun_forms_without_falling_back(): void
    {
        $ayah = new Ayah([
            'uthmani_text' => 'بِهِۦ لَهُۥ',
            'ayah_number' => 1,
        ]);

        $ayah->setRelation('words', new EloquentCollection([
            $this->makeWord(1, 'bh', 'بِهِ', [
                $this->makeMorphology(1, 'P', 'PREFIX|bi'),
                $this->makeMorphology(2, 'PRON', 'SUFFIX|PRON:3MS'),
            ]),
            $this->makeWord(2, 'lh', 'لَهُ', [
                $this->makeMorphology(1, 'P', 'PREFIX|li'),
                $this->makeMorphology(2, 'PRON', 'SUFFIX|PRON:3MS'),
            ]),
        ]));

        $prepared = AyahWordMappingService::prepare($ayah);

        $this->assertFalse($prepared['used_fallback']);
        $this->assertSame(['بِهِ', 'لَهُ'], $prepared['mapped_words']);
    }

    public function test_it_ignores_standalone_quranic_annotation_tokens_when_mapping(): void
    {
        $ayah = new Ayah([
            'uthmani_text' => 'مَثَلًا ۘ يُضِلُّ بِهِ كَثِيرًا',
            'ayah_number' => 1,
        ]);

        $ayah->setRelation('words', new EloquentCollection([
            $this->makeWord(1, 'mathalan', 'مَثَلًا'),
            $this->makeWord(2, 'yuDil~u', 'يُضِلُّ'),
            $this->makeWord(3, 'bihi', 'بِهِ'),
            $this->makeWord(4, 'kaviyran', 'كَثِيرًا'),
        ]));

        $prepared = AyahWordMappingService::prepare($ayah);

        $this->assertFalse($prepared['used_fallback']);
        $this->assertSame(['مَثَلًا', 'يُضِلُّ', 'بِهِ', 'كَثِيرًا'], $prepared['mapped_words']);
        $this->assertSame([], $prepared['remaining_tokens']);
    }

    /**
     * @param  array<int, Morphology>  $morphologies
     */
    protected function makeWord(int $position, string $form, string $arabicText, array $morphologies = []): Word
    {
        $word = new Word([
            'position' => $position,
            'form' => $form,
            'arabic_text' => $arabicText,
        ]);

        $word->setRelation('morphologies', new EloquentCollection($morphologies));

        return $word;
    }

    protected function makeMorphology(int $segmentNumber, string $posTag, string $rawFeatures): Morphology
    {
        return new Morphology([
            'segment_number' => $segmentNumber,
            'pos_tag' => $posTag,
            'raw_features' => $rawFeatures,
        ]);
    }
}
