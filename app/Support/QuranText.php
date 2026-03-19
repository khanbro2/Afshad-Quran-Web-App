<?php

namespace App\Support;

class QuranText
{
    protected const NORMALIZABLE_MARKS_PATTERN = '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';
    protected const ANALYSIS_DISPLAY_MARKS_PATTERN = '/[\x{0653}-\x{065F}\x{06D6}-\x{06ED}]/u';
    protected const ANALYSIS_SUPERSCRIPT_ALEF_PATTERN = '/\x{064E}(?=\x{0670})/u';

    protected const BUCKWALTER_MAP = [
        '\'' => 'ء',
        '|' => 'آ',
        '>' => 'أ',
        '&' => 'ؤ',
        '<' => 'إ',
        '}' => 'ئ',
        'A' => 'ا',
        'b' => 'ب',
        'p' => 'ة',
        't' => 'ت',
        'v' => 'ث',
        'j' => 'ج',
        'H' => 'ح',
        'x' => 'خ',
        'd' => 'د',
        '*' => 'ذ',
        'r' => 'ر',
        'z' => 'ز',
        's' => 'س',
        '$' => 'ش',
        'S' => 'ص',
        'D' => 'ض',
        'T' => 'ط',
        'Z' => 'ظ',
        'E' => 'ع',
        'g' => 'غ',
        'f' => 'ف',
        'q' => 'ق',
        'k' => 'ك',
        'l' => 'ل',
        'm' => 'م',
        'n' => 'ن',
        'h' => 'ه',
        'w' => 'و',
        'Y' => 'ى',
        'y' => 'ي',
        'F' => 'ً',
        'N' => 'ٌ',
        'K' => 'ٍ',
        'a' => 'َ',
        'u' => 'ُ',
        'i' => 'ِ',
        '~' => 'ّ',
        'o' => 'ْ',
        '`' => 'ٰ',
        '{' => 'ٱ',
    ];

    public static function buckwalterToArabic(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($characters)) {
            return $text;
        }

        $converted = '';

        foreach ($characters as $character) {
            $converted .= self::BUCKWALTER_MAP[$character] ?? $character;
        }

        return $converted;
    }

    public static function normalizeArabic(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $normalized = preg_replace(self::NORMALIZABLE_MARKS_PATTERN, '', $text);

        return $normalized === '' ? null : $normalized;
    }

    public static function removeDiacritics(?string $text): ?string
    {
        return self::normalizeArabic($text);
    }

    public static function normalizeArabicForMatching(?string $text): ?string
    {
        $normalized = self::removeDiacritics($text);

        if ($normalized === null) {
            return null;
        }

        return str_replace(['ٱ', 'أ', 'إ', 'آ', 'ى', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'ا', 'ي', 'و', 'ي'], $normalized);
    }

    public static function normalizeArabicForAnalysisDisplay(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $normalized = preg_replace(self::ANALYSIS_DISPLAY_MARKS_PATTERN, '', $text);
        $normalized = $normalized === null ? null : preg_replace(self::ANALYSIS_SUPERSCRIPT_ALEF_PATTERN, '', $normalized);

        return $normalized === '' ? null : $normalized;
    }

    public static function normalizeArabicForAyahDisplay(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $normalized = preg_replace(self::ANALYSIS_SUPERSCRIPT_ALEF_PATTERN, '', $text);

        return $normalized === '' ? null : $normalized;
    }

    public static function orthographyScore(?string $text): int
    {
        if ($text === null || $text === '') {
            return 0;
        }

        if (preg_match_all(self::NORMALIZABLE_MARKS_PATTERN, $text, $matches) !== false) {
            return count($matches[0]);
        }

        return 0;
    }
}
