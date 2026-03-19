<?php

namespace Tests\Unit;

use App\Models\Ayah;
use App\Support\QuranText;
use Tests\TestCase;

class AyahDisplayTextTest extends TestCase
{
    public function test_it_prefers_uthmani_text_for_display_when_available(): void
    {
        $ayah = new Ayah([
            'full_arabic_text' => 'بسم الله الرحمن الرحيم',
            'simple_text' => 'بسم الله الرحمن الرحيم',
            'uthmani_text' => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
        ]);

        $this->assertSame('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', $ayah->display_text);
    }

    public function test_matching_normalization_still_removes_diacritics_safely(): void
    {
        $this->assertSame(
            'بسم الله الرحمن الرحيم',
            QuranText::normalizeArabicForMatching('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ')
        );
    }
}
