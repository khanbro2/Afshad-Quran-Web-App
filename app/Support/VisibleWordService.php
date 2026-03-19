<?php

namespace App\Support;

use App\Models\Morphology;
use App\Models\Word;
use Illuminate\Support\Collection;

class VisibleWordService
{
    protected const COMBINING_MARKS = '\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}';

    /**
     * @return array<int, string>
     */
    public static function ayahTokens(Word $word): array
    {
        $ayah = $word->relationLoaded('ayah') ? $word->ayah : $word->ayah()->first();

        if (! $ayah || empty($ayah->display_text)) {
            return [];
        }

        $tokens = preg_split('/\s+/u', trim((string) $ayah->display_text), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($tokens) ? array_values($tokens) : [];
    }

    public static function visibleWord(Word $word, ?string $mappedToken = null): string
    {
        if ($mappedToken !== null && $mappedToken !== '') {
            return $mappedToken;
        }

        $tokens = self::ayahTokens($word);

        if (isset($tokens[$word->position - 1])) {
            return (string) $tokens[$word->position - 1];
        }

        return (string) ($word->arabic_text ?: QuranText::buckwalterToArabic($word->form) ?: '');
    }

    public static function displayArabicWord(Word $word, ?string $mappedToken = null): string
    {
        $fallback = self::fallbackVisibleWord($word);

        if ($mappedToken === null || trim($mappedToken) === '') {
            return self::isReliableDisplayArabic($fallback) ? $fallback : self::visibleWord($word, $mappedToken);
        }

        if (! self::isReliableDisplayArabic($fallback)) {
            return $mappedToken;
        }

        $mappedTokens = preg_split('/\s+/u', trim($mappedToken), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($mappedTokens) || count($mappedTokens) <= 1) {
            return self::normalizedWordsMatch($mappedToken, $fallback)
                ? self::preferredDisplayArabic($mappedToken, $fallback)
                : $mappedToken;
        }

        if (
            self::hasVocativePrefix($word)
            && QuranText::normalizeArabicForMatching($mappedTokens[0]) === 'يا'
        ) {
            $stem = (string) ($word->arabic_text ?: QuranText::buckwalterToArabic($word->form) ?: $mappedTokens[1]);

            return 'يَا '.$stem;
        }

        return $fallback;
    }

    public static function analysisDisplayArabicWord(Word $word, ?string $mappedToken = null): string
    {
        $display = self::displayArabicWord($word, $mappedToken);

        return QuranText::normalizeArabicForAnalysisDisplay($display) ?? $display;
    }

    protected static function preferredDisplayArabic(string $mappedToken, string $fallback): string
    {
        $mappedScore = QuranText::orthographyScore($mappedToken);
        $fallbackScore = QuranText::orthographyScore($fallback);

        if ($mappedScore > $fallbackScore) {
            return $mappedToken;
        }

        if ($fallbackScore > $mappedScore) {
            return $fallback;
        }

        return $mappedToken;
    }

    public static function fallbackVisibleWord(Word $word): string
    {
        $morphologies = $word->relationLoaded('morphologies')
            ? $word->morphologies
            : $word->morphologies()->orderBy('segment_number')->get();

        $prefixes = [];
        $suffixes = [];

        foreach ($morphologies->sortBy('segment_number') as $morphology) {
            $kind = self::segmentKind($morphology);

            if ($kind === 'PREFIX') {
                $prefix = self::prefixArabicText((string) $morphology->raw_features);
                if ($prefix !== '') {
                    $prefixes[] = $prefix;
                }

                continue;
            }

            if ($kind === 'SUFFIX') {
                $suffix = self::suffixArabicText((string) $morphology->raw_features, (string) $word->transliteration);
                if ($suffix !== '') {
                    $suffixes[] = $suffix;
                }
            }
        }

        $stem = (string) ($word->arabic_text ?: QuranText::buckwalterToArabic($word->form) ?: '');

        return trim(implode('', $prefixes).$stem.implode('', $suffixes));
    }

    /**
     * @return Collection<int, Morphology>
     */
    public static function analysisMorphologies(Word $word): Collection
    {
        $morphologies = $word->relationLoaded('morphologies')
            ? $word->morphologies
            : $word->morphologies()->orderBy('segment_number')->get();

        return $morphologies
            ->sortBy('segment_number')
            ->values()
            ->reject(fn (Morphology $morphology) => MorphologyFeatureExtractor::for($morphology)->isDeterminer())
            ->values();
    }

    /**
     * @return array<int, array{text: string, classes: string, kind: string, morphology: ?Morphology}>
     */
    public static function colorableSegments(Word $word, ?string $visibleWord = null): array
    {
        $visibleWord = $visibleWord ?? self::visibleWord($word);
        if ($visibleWord === '') {
            return [];
        }

        $morphologies = $word->relationLoaded('morphologies')
            ? $word->morphologies
            : $word->morphologies()->orderBy('segment_number')->get();

        $morphologies = $morphologies->sortBy('segment_number')->values();

        if ($morphologies->isEmpty()) {
            return [[
                'text' => $visibleWord,
                'classes' => MorphologyCardColorService::wordSegmentClasses(null, 'WORD'),
                'kind' => 'WORD',
                'morphology' => null,
            ]];
        }

        $prefixMorphologies = $morphologies->filter(
            fn (Morphology $morphology) => self::segmentKind($morphology) === 'PREFIX' && $morphology->pos_tag !== 'DET'
        )->values();
        $suffixMorphologies = $morphologies->filter(
            fn (Morphology $morphology) => self::segmentKind($morphology) === 'SUFFIX'
        )->values();
        $stemMorphology = $morphologies->first(
            fn (Morphology $morphology) => self::segmentKind($morphology) === 'STEM'
        ) ?? $morphologies->last();

        $segments = [];
        $start = 0;
        $end = self::mbLength($visibleWord);

        foreach ($prefixMorphologies as $index => $morphology) {
            $remaining = self::mbSubstr($visibleWord, $start, $end - $start);
            $prefix = self::extractPrefixText($remaining, $morphology, $prefixMorphologies->slice($index + 1)->all());

            if ($prefix === '') {
                continue;
            }

            $segments[] = [
                'text' => $prefix,
                'classes' => MorphologyCardColorService::wordSegmentClasses($morphology, 'PREFIX'),
                'kind' => 'PREFIX',
                'variant' => 0,
                'morphology' => $morphology,
            ];
            $start += self::mbLength($prefix);
        }

        $suffixSegments = [];
        $stemContext = $stemMorphology instanceof Morphology ? $stemMorphology : null;

        foreach ($suffixMorphologies->reverse()->values() as $suffixIndex => $morphology) {
            $remaining = self::mbSubstr($visibleWord, $start, $end - $start);
            $suffix = self::extractSuffixText($remaining, $morphology, $stemContext);

            if ($suffix === '') {
                continue;
            }

            $end -= self::mbLength($suffix);
            array_unshift($suffixSegments, [
                'text' => $suffix,
                'classes' => MorphologyCardColorService::wordSegmentClasses($morphology, 'SUFFIX', $suffixIndex),
                'kind' => 'SUFFIX',
                'variant' => $suffixIndex,
                'morphology' => $morphology,
            ]);
        }

        $stemLength = max($end - $start, 0);
        $stem = self::mbSubstr($visibleWord, $start, $stemLength);

        if ($stem !== '') {
            $segments[] = [
                'text' => $stem,
                'classes' => MorphologyCardColorService::wordSegmentClasses($stemMorphology, 'STEM'),
                'kind' => 'STEM',
                'variant' => 0,
                'morphology' => $stemMorphology,
            ];
        }

        $segments = [...$segments, ...$suffixSegments];

        return self::mergeAdjacentSegments($segments);
    }

    public static function wordColorStyle(Word $word, ?string $visibleWord = null): string
    {
        return MorphologyCardColorService::wordGradientStyle(self::colorableSegments($word, $visibleWord));
    }

    protected static function segmentKind(Morphology $morphology): string
    {
        return SegmentClassifier::kind($morphology);
    }

    /**
     * @param  array<int, Morphology>  $remainingMorphologies
     */
    protected static function extractPrefixText(string $visibleWord, Morphology $morphology, array $remainingMorphologies): string
    {
        $raw = (string) $morphology->raw_features;

        if (str_contains($raw, 'A:') || str_contains($raw, 'A+')) {
            return self::matchLeadingPattern($visibleWord, '(?:ء['.self::COMBINING_MARKS.']*)?[اأإآ]['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'bi') && preg_match('/^ب/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'ب['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'l:') && preg_match('/^ل/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'ل['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'w:') && preg_match('/^و/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'و['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'f:') && preg_match('/^ف/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'ف['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'ka') && preg_match('/^ك/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'ك['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'sa') && preg_match('/^س/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'س['.self::COMBINING_MARKS.']*');
        }

        if (str_contains($raw, 'ya+') && preg_match('/^ي/u', $visibleWord) === 1) {
            return self::matchLeadingPattern($visibleWord, 'ي['.self::COMBINING_MARKS.']*');
        }

        if (
            self::hasDeterminerAhead($remainingMorphologies)
            && preg_match('/^[وفبلكس]/u', $visibleWord) === 1
        ) {
            return self::matchLeadingPattern($visibleWord, '[وفبلكسي]['.self::COMBINING_MARKS.']*');
        }

        return '';
    }

    protected static function extractSuffixText(string $visibleWord, Morphology $morphology, ?Morphology $stemMorphology = null): string
    {
        foreach (self::suffixPatterns($morphology->raw_features, $stemMorphology) as $pattern) {
            $matched = self::matchTrailingPattern($visibleWord, $pattern);

            if ($matched !== '') {
                return $matched;
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    protected static function suffixPatterns(?string $rawFeatures, ?Morphology $stemMorphology = null): array
    {
        if ($rawFeatures === null || preg_match('/PRON:([^|]+)/', $rawFeatures, $matches) !== 1) {
            return [];
        }

        $code = $matches[1];
        $marks = '['.self::COMBINING_MARKS.']*';
        $patterns = [];

        if ($stemMorphology?->pos_tag === 'V') {
            $stemRaw = strtoupper((string) $stemMorphology->raw_features);

            if (str_contains($stemRaw, '|IMPF|') && $code === '3MP') {
                $patterns[] = 'و'.$marks.'ن'.$marks.'۟?';
            }

            if (str_contains($stemRaw, '|IMPF|') && $code === '2MP') {
                $patterns[] = 'و'.$marks.'ن'.$marks.'۟?';
            }

            if (str_contains($stemRaw, '|PERF|') && $code === '3MP') {
                $patterns[] = 'و'.$marks.'ا'.$marks.'۟?';
            }

            if ($code === '2MP' && (str_contains($stemRaw, '|IMPV|') || str_contains($stemRaw, '|IMPF|') || str_contains($stemRaw, '|JUS'))) {
                $patterns[] = 'و'.$marks.'ا'.$marks.'۟?';
            }

            if ($code === '2MP' && str_contains($stemRaw, '|PERF|')) {
                $patterns[] = 'ت'.$marks.'م'.$marks;
            }

            if ($code === '1S' && str_contains($stemRaw, '|PERF|')) {
                $patterns[] = 'ت'.$marks;
            }

            if ($code === '1P' && str_contains($stemRaw, '|PERF|')) {
                $patterns[] = 'ن'.$marks.'ا'.$marks;
            }
        }

        $defaultPatterns = match ($code) {
            '1P' => ['ن'.$marks.'ا'.$marks],
            '1S' => ['(?:ي|ى)'.$marks],
            '2MS' => ['ت'.$marks, 'ك'.$marks],
            '2FS' => ['ك'.$marks],
            '2MP' => ['ك'.$marks.'م'.$marks],
            '2FP' => ['ك'.$marks.'ن'.$marks],
            '3MS' => ['ه'.$marks],
            '3FS' => ['ه'.$marks.'ا'.$marks],
            '3MP' => ['ه'.$marks.'م'.$marks],
            '3FP' => ['ه'.$marks.'ن'.$marks],
            default => [],
        };

        if (in_array($code, ['2D', '2MD', '2FD'], true)) {
            $defaultPatterns = ['ك'.$marks.'م'.$marks.'ا'.$marks];
        }

        if (in_array($code, ['3D', '3MD', '3FD'], true)) {
            $defaultPatterns = ['ه'.$marks.'م'.$marks.'ا'.$marks];
        }

        return [...$patterns, ...$defaultPatterns];
    }

    /**
     * @param  array<int, Morphology>  $morphologies
     * @return array<int, array{text: string, classes: string, kind: string, morphology: ?Morphology}>
     */
    protected static function mergeAdjacentSegments(array $morphologies): array
    {
        $merged = [];

        foreach ($morphologies as $segment) {
            if ($segment['text'] === '') {
                continue;
            }

            $lastIndex = count($merged) - 1;

            if (
                $lastIndex >= 0
                && $merged[$lastIndex]['classes'] === $segment['classes']
                && $merged[$lastIndex]['kind'] === $segment['kind']
                && $merged[$lastIndex]['morphology'] === $segment['morphology']
            ) {
                $merged[$lastIndex]['text'] .= $segment['text'];
                continue;
            }

            $merged[] = $segment;
        }

        return $merged;
    }

    /**
     * @param  array<int, Morphology>  $morphologies
     */
    protected static function hasDeterminerAhead(array $morphologies): bool
    {
        foreach ($morphologies as $morphology) {
            if (MorphologyFeatureExtractor::for($morphology)->isDeterminer()) {
                return true;
            }
        }

        return false;
    }

    protected static function mbLength(string $text): int
    {
        return mb_strlen($text, 'UTF-8');
    }

    protected static function mbSubstr(string $text, int $start, ?int $length = null): string
    {
        return $length === null
            ? mb_substr($text, $start, null, 'UTF-8')
            : mb_substr($text, $start, $length, 'UTF-8');
    }

    protected static function prefixArabicText(string $rawFeatures): string
    {
        if (! str_starts_with($rawFeatures, 'PREFIX|')) {
            return '';
        }

        if (str_contains($rawFeatures, 'l:EMPH+')) {
            return "\u{0644}\u{064E}";
        }

        return match (true) {
            str_contains($rawFeatures, 'ya+') => 'يَا',
            str_contains($rawFeatures, 'bi') => 'بِ',
            str_contains($rawFeatures, 'w:') => 'وَ',
            str_contains($rawFeatures, 'f:') => 'فَ',
            str_contains($rawFeatures, 'l:') => 'لِ',
            str_contains($rawFeatures, 'Al+') => 'ٱل',
            default => '',
        };
    }

    protected static function suffixArabicText(string $rawFeatures, string $transliteration = ''): string
    {
        if (preg_match('/PRON:([^|]+)/', $rawFeatures, $matches) !== 1) {
            return '';
        }

        $code = $matches[1];

        if ($code === '3MP' && str_contains($transliteration, 'ūna')) {
            return 'ونَ';
        }

        return match ($code) {
            '1P' => 'نَا',
            '1S' => 'ي',
            '2MS' => 'كَ',
            '2FS' => 'كِ',
            '2MP' => 'كُمْ',
            '2FP' => 'كُنَّ',
            '2D', '2MD', '2FD' => 'كُمَا',
            '3MS' => 'هُ',
            '3FS' => 'هَا',
            '3MP' => 'هُمْ',
            '3FP' => 'هُنَّ',
            '3D', '3MD', '3FD' => 'هُمَا',
            default => '',
        };
    }

    protected static function hasVocativePrefix(Word $word): bool
    {
        $morphologies = $word->relationLoaded('morphologies')
            ? $word->morphologies
            : $word->morphologies()->orderBy('segment_number')->get();

        foreach ($morphologies as $morphology) {
            if (str_contains((string) $morphology->raw_features, 'PREFIX|ya+')) {
                return true;
            }
        }

        return false;
    }

    protected static function isReliableDisplayArabic(string $text): bool
    {
        if ($text === '' || str_contains($text, '@')) {
            return false;
        }

        return preg_match('/^[\p{Arabic}\s]+$/u', $text) === 1;
    }

    protected static function normalizedWordsMatch(string $left, string $right): bool
    {
        return (QuranText::normalizeArabicForMatching($left) ?? trim($left))
            === (QuranText::normalizeArabicForMatching($right) ?? trim($right));
    }

    protected static function matchLeadingPattern(string $text, string $pattern): string
    {
        if (preg_match('/^('.$pattern.')/u', $text, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    protected static function matchTrailingPattern(string $text, string $pattern): string
    {
        if (preg_match('/('.$pattern.')$/u', $text, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
