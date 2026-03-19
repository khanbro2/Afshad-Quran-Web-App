<?php

namespace Tests\Unit;

use App\Support\QuranText;
use Tests\TestCase;

class QuranTextTest extends TestCase
{
    public function test_it_normalizes_uthmani_pronoun_ha_variants_for_matching(): void
    {
        $this->assertSame('به', QuranText::removeDiacritics('بِهِۦ'));
        $this->assertSame('به', QuranText::normalizeArabicForMatching('بِهِۦ'));
        $this->assertSame('له', QuranText::normalizeArabicForMatching('لَهُۥ'));
        $this->assertSame('فيه', QuranText::normalizeArabicForMatching('فِيهِ'));
        $this->assertSame('عليهم', QuranText::normalizeArabicForMatching('عَلَيْهِمْ'));
    }

    public function test_it_counts_uthmani_marks_when_choosing_richer_display_text(): void
    {
        $this->assertGreaterThan(
            QuranText::orthographyScore('بِهِ'),
            QuranText::orthographyScore('بِهِۦ')
        );
    }

    public function test_it_strips_quranic_annotation_marks_for_analysis_display_but_keeps_harakat(): void
    {
        $this->assertSame('بِهِ', QuranText::normalizeArabicForAnalysisDisplay('بِهِۦ'));
        $this->assertSame('لَهُ', QuranText::normalizeArabicForAnalysisDisplay('لَهُۥ'));
        $this->assertSame('مَثَلًا', QuranText::normalizeArabicForAnalysisDisplay('مَثَلًۭا'));
        $this->assertSame('فِيهِ', QuranText::normalizeArabicForAnalysisDisplay('فِيهِ'));
        $this->assertSame('ٱلسَّمٰوَاتِ', QuranText::normalizeArabicForAnalysisDisplay('ٱلسَّمَٰوَاتِ'));
    }

    public function test_it_collapses_fatha_before_khara_zabar_for_ayah_display_without_stripping_other_marks(): void
    {
        $this->assertSame('ٱلسَّمٰوَاتِ', QuranText::normalizeArabicForAyahDisplay('ٱلسَّمَٰوَاتِ'));
        $this->assertSame('كُلٌّ لَّهُۥ قٰنِتُونَ', QuranText::normalizeArabicForAyahDisplay('كُلٌّ لَّهُۥ قَٰنِتُونَ'));
        $this->assertSame('بِهِۦ', QuranText::normalizeArabicForAyahDisplay('بِهِۦ'));
    }
}
