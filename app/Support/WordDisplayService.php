<?php

namespace App\Support;

use App\Models\Word;

class WordDisplayService
{
    public static function reconstructTransliteration(Word $word): string
    {
        return $word->transliteration ?: $word->form ?: '';
    }

    public static function reconstructArabicWord(Word $word): string
    {
        return VisibleWordService::visibleWord($word);
    }

    public static function reconstructAnalysisArabicWord(Word $word): string
    {
        return VisibleWordService::analysisDisplayArabicWord($word);
    }
}
