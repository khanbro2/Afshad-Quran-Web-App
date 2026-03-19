<?php

namespace App\Support;

use App\Models\Surah;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SurahQuranIrabScraper
{
    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function scrapePages(int $fromPage = 1, int $toPage = 604, ?callable $progress = null): array
    {
        if ($fromPage < 1 || $toPage < $fromPage) {
            throw new InvalidArgumentException('Invalid page range for i\'rab scraping.');
        }

        $surahNumbersByName = self::surahNumbersByNormalizedName();
        $verses = [];

        for ($page = $fromPage; $page <= $toPage; $page++) {
            $html = self::fetchPage($page);
            $pageVerses = self::parsePage($html, $surahNumbersByName);

            foreach ($pageVerses as $verse) {
                $verses[$verse['surah_number'].':'.$verse['ayah_number']] = $verse;
            }

            if ($progress !== null) {
                $progress($page, count($pageVerses), count($verses));
            }
        }

        ksort($verses);

        return array_values($verses);
    }

    protected static function fetchPage(int $page): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 Quran Study App I\'rab Importer',
                'Accept-Language' => 'ar,en;q=0.8',
            ])
            ->get("https://surahquran.com/quran-expressed/{$page}.html");

        $response->throw();

        return (string) $response->body();
    }

    /**
     * @param  array<string, int>  $surahNumbersByName
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function parsePage(string $html, array $surahNumbersByName): array
    {
        $html = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|li|h1|h2|h3|h4|section|article|tr|td)>/iu', "$0\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\x{FEFF}/u", '', $text) ?? $text;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($line) => $line !== ''));

        $currentSurahNumber = self::detectPrimarySurahNumber($lines, $surahNumbersByName);
        $verses = [];
        $pendingAyah = null;
        $pendingText = '';

        foreach ($lines as $line) {
            if (preg_match('/^سورة\s+(.+)$/u', $line, $matches) === 1) {
                $candidate = self::normalizeSurahName($matches[1]);
                $currentSurahNumber = $surahNumbersByName[$candidate] ?? $currentSurahNumber;

                continue;
            }

            if (preg_match('/^(\d+)\s*-\s*\{.+\}$/u', $line, $matches) === 1) {
                if ($pendingAyah !== null && $pendingText !== '' && $currentSurahNumber !== null) {
                    $verses[] = [
                        'surah_number' => $currentSurahNumber,
                        'ayah_number' => $pendingAyah,
                        'text' => trim($pendingText),
                    ];
                }

                $pendingAyah = (int) $matches[1];
                $pendingText = '';

                continue;
            }

            if ($pendingAyah !== null && ! self::isNonIrabLine($line)) {
                $pendingText .= ($pendingText === '' ? '' : ' ').$line;
            }
        }

        if ($pendingAyah !== null && $pendingText !== '' && $currentSurahNumber !== null) {
            $verses[] = [
                'surah_number' => $currentSurahNumber,
                'ayah_number' => $pendingAyah,
                'text' => trim($pendingText),
            ];
        }

        return $verses;
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<string, int>  $surahNumbersByName
     */
    protected static function detectPrimarySurahNumber(array $lines, array $surahNumbersByName): ?int
    {
        foreach ($lines as $line) {
            if (preg_match('/^إعراب سورة\s+(.+)$/u', $line, $matches) === 1) {
                $candidate = self::normalizeSurahName($matches[1]);

                return $surahNumbersByName[$candidate] ?? null;
            }

            if (preg_match('/^سورة\s+(.+)$/u', $line, $matches) === 1) {
                $candidate = self::normalizeSurahName($matches[1]);

                return $surahNumbersByName[$candidate] ?? null;
            }
        }

        return null;
    }

    protected static function isNonIrabLine(string $line): bool
    {
        return preg_match('/^(السابق|التالي|إعراب الصفحة|مُشكِل إعراب القرآن الكريم|الصفحة رقم|تحميل و استماع|القرآن الكريم|شكرا لدعمكم|الحقوق محفوظة|فهرس|نبذة عن موقعنا)/u', $line) === 1;
    }

    /**
     * @return array<string, int>
     */
    protected static function surahNumbersByNormalizedName(): array
    {
        return Surah::query()
            ->get(['number', 'arabic_name'])
            ->mapWithKeys(fn (Surah $surah) => [
                self::normalizeSurahName((string) $surah->arabic_name) => (int) $surah->number,
            ])
            ->all();
    }

    protected static function normalizeSurahName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/^سورة\s+/u', '', $name) ?? $name;
        $name = QuranText::normalizeArabicForMatching($name);

        return trim($name);
    }
}
