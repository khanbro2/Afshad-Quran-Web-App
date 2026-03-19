<?php

namespace App\Support;

use App\Models\Ayah;
use App\Models\Word;
use Illuminate\Support\Facades\Log;

class AyahWordMappingService
{
    protected const BISMILLAH_TOKENS = ['بسم', 'الله', 'الرحمن', 'الرحيم'];
    protected const BISMILLAH_DISPLAY = 'بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ';

    /**
     * Prepare stable display-word mapping for ayah detail rows.
     *
     * The DB `words` rows are the primary source of identity and row count.
     * Quran text is only reconciled onto those rows for visible display. This
     * avoids brittle raw token indexing and lets one DB word consume multiple
     * visible tokens, such as "يا أيها".
     *
     * @return array{
     *   bismillah_text: ?string,
     *   mapped_words: array<int, string>,
     *   remaining_tokens: array<int, string>,
     *   used_fallback: bool
     * }
     */
    public static function prepare(Ayah $ayah): array
    {
        $text = (string) ($ayah->display_text ?? '');
        $tokens = self::tokenizeArabicText($text);
        $bismillahText = self::extractLeadingBismillah($text);

        if ($bismillahText !== null) {
            $tokens = array_slice($tokens, 4);
        }

        $words = $ayah->relationLoaded('words')
            ? $ayah->words->values()
            : $ayah->words()->orderBy('position')->get()->values();

        $mappedWords = [];
        $tokenIndex = 0;
        $usedFallback = false;

        foreach ($words as $word) {
            $span = self::matchDisplaySpan($word, array_slice($tokens, $tokenIndex));

            if ($span !== null) {
                $mappedWords[] = $span['display'];
                $tokenIndex += $span['token_count'];
                continue;
            }

            $mappedWords[] = VisibleWordService::fallbackVisibleWord($word);
            $usedFallback = true;
        }

        $remainingTokens = array_slice($tokens, $tokenIndex);

        if ($usedFallback || $remainingTokens !== []) {
            Log::warning('Ayah word mapping reconciliation used fallback', [
                'surah_number' => $ayah->surah?->number,
                'ayah_number' => $ayah->ayah_number,
                'token_count_after_bismillah' => count($tokens),
                'word_count' => $words->count(),
                'remaining_tokens' => $remainingTokens,
                'bismillah_removed' => $bismillahText !== null,
            ]);
        }

        return [
            'bismillah_text' => $bismillahText,
            'mapped_words' => $mappedWords,
            'remaining_tokens' => $remainingTokens,
            'used_fallback' => $usedFallback || $remainingTokens !== [],
        ];
    }

    public static function startsWithBismillah(?string $text): bool
    {
        $tokens = self::tokenizeArabicText($text);

        if (count($tokens) < 4) {
            return false;
        }

        return array_map([self::class, 'normalizeToken'], array_slice($tokens, 0, 4)) === self::BISMILLAH_TOKENS;
    }

    public static function extractLeadingBismillah(?string $text): ?string
    {
        if (! self::startsWithBismillah($text)) {
            return null;
        }

        return implode(' ', array_slice(self::tokenizeArabicText($text), 0, 4));
    }

    public static function stripLeadingBismillah(?string $text): string
    {
        if (! self::startsWithBismillah($text)) {
            return trim((string) $text);
        }

        return implode(' ', array_slice(self::tokenizeArabicText($text), 4));
    }

    public static function displayBismillah(): string
    {
        return self::BISMILLAH_DISPLAY;
    }

    /**
     * @return array<int, string>
     */
    public static function tokenizeArabicText(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($tokens)) {
            return [];
        }

        $cleaned = array_map(function (string $token): string {
            $studyToken = QuranText::normalizeArabicForAnalysisDisplay($token);

            return trim((string) $studyToken);
        }, $tokens);

        return array_values(array_filter($cleaned, fn (string $token) => $token !== ''));
    }

    /**
     * @param  array<int, string>  $remainingTokens
     * @return array{display: string, token_count: int}|null
     */
    protected static function matchDisplaySpan(Word $word, array $remainingTokens): ?array
    {
        if ($remainingTokens === []) {
            return null;
        }

        $fallback = VisibleWordService::fallbackVisibleWord($word);
        $fallbackNormalized = self::normalizeJoinedText($fallback);

        foreach (range(1, min(3, count($remainingTokens))) as $tokenCount) {
            $candidateTokens = array_slice($remainingTokens, 0, $tokenCount);
            $candidateDisplay = implode(' ', $candidateTokens);
            $candidateNormalized = self::normalizeJoinedText($candidateDisplay);

            if ($candidateNormalized === $fallbackNormalized) {
                return [
                    'display' => $candidateDisplay,
                    'token_count' => $tokenCount,
                ];
            }
        }

        // Special handling for corpus-style joined vocative forms such as
        // "يا أيها", where one DB word maps to two visible Quran tokens.
        if (
            self::hasVocativePrefix($word)
            && count($remainingTokens) >= 2
            && self::normalizeToken($remainingTokens[0]) === 'يا'
        ) {
            $stemNormalized = self::normalizeToken((string) ($word->arabic_text ?: ''));

            if ($stemNormalized !== '' && self::normalizeToken($remainingTokens[1]) === $stemNormalized) {
                return [
                    'display' => $remainingTokens[0].' '.$remainingTokens[1],
                    'token_count' => 2,
                ];
            }
        }

        // Normal happy path for already aligned single-token words.
        return [
            'display' => $remainingTokens[0],
            'token_count' => 1,
        ];
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

    protected static function normalizeJoinedText(?string $text): string
    {
        $tokens = self::tokenizeArabicText($text);

        return implode('', array_map([self::class, 'normalizeToken'], $tokens));
    }

    protected static function normalizeToken(string $token): string
    {
        $normalized = QuranText::normalizeArabicForMatching($token) ?? $token;

        return trim($normalized);
    }
}
