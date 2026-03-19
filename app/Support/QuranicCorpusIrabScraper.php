<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuranicCorpusIrabScraper
{
    public static function fetchAyahIrab(int $surahNumber, int $ayahNumber): ?string
    {
        $response = Http::connectTimeout(20)
            ->timeout(90)
            ->retry(3, 1500)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 Quran Study App I\'rab Importer',
                'Accept-Language' => 'ar,en;q=0.8',
            ])
            ->get('https://corpus.quran.com/grammar.jsp', [
                'chapter' => $surahNumber,
                'verse' => $ayahNumber,
            ]);

        $response->throw();

        return self::parseAyahIrab($response->body());
    }

    public static function parseAyahIrab(string $html): ?string
    {
        // Prefer explicit grammar notes paragraph if present.
        if (preg_match('/<p\b[^>]*class=["\']?[^"\'>]*grammarNotes[^"\'>]*["\']?[^>]*>(.*?)<\/p>/isu', $html, $matches) === 1) {
            $decoded = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return self::normalizeIrabText($decoded);
        }

        $htmlNormalized = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $html) ?? $html;
        $htmlNormalized = preg_replace('/<\/\s*(p|div|li|h1|h2|h3|h4|td|tr|section|article)>/iu', "$0\n", $htmlNormalized) ?? $htmlNormalized;
        $text = html_entity_decode(strip_tags($htmlNormalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\x{FEFF}/u", '', $text) ?? $text;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{2,}/u', "\n", $text) ?? $text;

        if (preg_match('/###\s+Chapter.+?\n(.+?)\nQuran Recitation/usu', $text, $matches) === 1) {
            return self::normalizeIrabText($matches[1]);
        }

        if (preg_match('/Chapter.+?\n(.+?)\nQuran Recitation/usu', $text, $matches) === 1) {
            return self::normalizeIrabText($matches[1]);
        }

        if (preg_match('/\n(«.+?)\nQuran Recitation/usu', $text, $matches) === 1) {
            return self::normalizeIrabText($matches[1]);
        }

        // No grammar notes found in this page.
        return null;
    }

    protected static function normalizeIrabText(string $text): ?string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text !== '' ? $text : null;
    }
}
