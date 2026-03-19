<?php

namespace App\Services\TafseerImport;

use App\Models\Ayah;
use App\Models\AyahTafseer;
use App\Models\Surah;
use App\Models\Tafseer;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class EquranLibraryTafseerImporter
{
    protected string $baseUrl = 'https://equranlibrary.com/tafseer';

    protected array $sourceSlugMap = [
        'ibnekaseer' => 'ibnekaseer',
        'tafheemulquran' => 'tafheemulquran',
        'maarifulquran' => 'maarifulquran',
        'tafseerusmani' => 'usmani',
        'usmani' => 'usmani',
        'bayanulquran' => 'bayanulquranthanvi',
        'bayanulquran_scrape' => 'bayanulquranthanvi',
    ];

    protected array $defaultMetaBySlug = [
        'ibnekaseer' => [
            'title' => 'تفسیر ابن کثیر',
            'author' => 'ابو الفداء اسماعیل ابن کثیر',
        ],
        'tafheemulquran' => [
            'title' => 'تفہیم القرآن',
            'author' => 'سید ابو الاعلی مودودی',
        ],
        'maarifulquran' => [
            'title' => 'معارف القرآن',
            'author' => 'مفتی محمد شفیع',
        ],
        'tafseerusmani' => [
            'title' => 'تفسیر عثمانی',
            'author' => 'مولانا شبیر احمد عثمانی',
        ],
        'usmani' => [
            'title' => 'تفسیر عثمانی',
            'author' => 'مولانا شبیر احمد عثمانی',
        ],
        'bayanulquran' => [
            'title' => 'بیان القرآن',
            'author' => 'مولانا اشرف علی تھانوی',
        ],
    ];

    public function import(
        string $tafseerSlug,
        bool $update = false,
        ?int $fromSurah = null,
        ?int $toSurah = null,
        ?callable $progress = null,
        ?int $fromAyah = null
    ): array
    {
        $fromSurah = $fromSurah ?: 1;
        $toSurah = $toSurah ?: 114;

        $tafseer = Tafseer::query()
            ->where('slug', $tafseerSlug)
            ->first();

        if (! $tafseer) {
            throw new RuntimeException("Tafseer not found for slug: {$tafseerSlug}");
        }

        $stats = [
            'tafseer' => $tafseerSlug,
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        for ($surahNumber = $fromSurah; $surahNumber <= $toSurah; $surahNumber++) {
            $surah = Surah::query()->where('number', $surahNumber)->first();

            if (! $surah) {
                $stats['failed']++;
                $stats['failures'][] = [
                    'surah' => $surahNumber,
                    'ayah' => 0,
                    'error' => "Surah {$surahNumber} not found in database",
                ];
                continue;
            }

            $maxAyah = (int) $surah->total_ayahs;

            if ($maxAyah <= 0) {
                $stats['failed']++;
                $stats['failures'][] = [
                    'surah' => $surahNumber,
                    'ayah' => 0,
                    'error' => "Invalid total_ayahs for Surah {$surahNumber}",
                ];
                continue;
            }

            $startAyah = $surahNumber === $fromSurah ? max(1, (int) ($fromAyah ?: 1)) : 1;

            for ($ayahNumber = $startAyah; $ayahNumber <= $maxAyah; $ayahNumber++) {
                $stats['processed']++;

                try {
                    $ayah = Ayah::query()
                        ->where('surah_id', $surah->id)
                        ->where('ayah_number', $ayahNumber)
                        ->first();

                    if (! $ayah) {
                        throw new RuntimeException("Ayah not found in database for Surah {$surahNumber}, Ayah {$ayahNumber}");
                    }

                    $existing = AyahTafseer::query()
                        ->where('ayah_id', $ayah->id)
                        ->where('tafseer_id', $tafseer->id)
                        ->first();

                    if ($existing && ! $update) {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($progress) {
                        $progress('fetching', [
                            'tafseer' => $tafseerSlug,
                            'surah' => $surahNumber,
                            'ayah' => $ayahNumber,
                            'processed' => $stats['processed'],
                            'from_surah' => $fromSurah,
                            'to_surah' => $toSurah,
                        ]);
                    }

                    $data = $this->fetchAyahTafseer($tafseer, $surahNumber, $ayahNumber);

                    $payload = [
                        'ayah_id' => $ayah->id,
                        'tafseer_id' => $tafseer->id,
                        'content' => $data['content'],
                        'content_html' => $this->buildContentHtml($data['content']),
                        'meta' => [
                            'title' => $data['title'] ?? null,
                            'author' => $data['author'] ?? null,
                            'source_url' => $data['source_url'] ?? null,
                            'source_slug' => $data['source_slug'] ?? null,
                        ],
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $stats['updated']++;
                    } else {
                        AyahTafseer::query()->create($payload);
                        $stats['inserted']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $stats['failures'][] = [
                        'surah' => $surahNumber,
                        'ayah' => $ayahNumber,
                        'error' => $e->getMessage(),
                    ];

                    if ($progress) {
                        $progress('failed', [
                            'tafseer' => $tafseerSlug,
                            'surah' => $surahNumber,
                            'ayah' => $ayahNumber,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $stats;
    }

    public function findResumePoint(string $tafseerSlug, int $fromSurah = 1, int $toSurah = 114): ?array
    {
        $tafseer = Tafseer::query()
            ->where('slug', $tafseerSlug)
            ->first();

        if (! $tafseer) {
            throw new RuntimeException("Tafseer not found for slug: {$tafseerSlug}");
        }

        $latestEntry = AyahTafseer::query()
            ->where('tafseer_id', $tafseer->id)
            ->whereHas('ayah.surah', function ($query) use ($fromSurah, $toSurah) {
                $query->whereBetween('number', [$fromSurah, $toSurah]);
            })
            ->with('ayah.surah')
            ->latest('ayah_id')
            ->first();

        if (! $latestEntry || ! $latestEntry->ayah || ! $latestEntry->ayah->surah) {
            return [
                'surah' => $fromSurah,
                'ayah' => 1,
                'complete' => false,
            ];
        }

        $currentSurah = $latestEntry->ayah->surah;
        $currentAyah = (int) $latestEntry->ayah->ayah_number;
        $maxAyah = (int) $currentSurah->total_ayahs;

        if ($currentAyah < $maxAyah) {
            return [
                'surah' => (int) $currentSurah->number,
                'ayah' => $currentAyah + 1,
                'complete' => false,
            ];
        }

        if ((int) $currentSurah->number < $toSurah) {
            return [
                'surah' => (int) $currentSurah->number + 1,
                'ayah' => 1,
                'complete' => false,
            ];
        }

        return [
            'surah' => (int) $currentSurah->number,
            'ayah' => $maxAyah,
            'complete' => true,
        ];
    }

    public function fetchAyahTafseer(Tafseer $tafseer, int $surah, int $ayah): array
    {
        $sourceSlug = $this->resolveSourceSlug($tafseer->slug);
        $url = "{$this->baseUrl}/{$sourceSlug}/{$surah}/{$ayah}";

        $response = Http::timeout(30)
            ->retry(2, 1000)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept-Language' => 'en-US,en;q=0.9,ur;q=0.8,ar;q=0.7',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Request failed with status {$response->status()} for {$url}");
        }

        $html = $response->body();

        if ($this->pageLooksMissing($html)) {
            throw new RuntimeException("Ayah page not found for {$url}");
        }

        $parsed = $this->parseTafseerHtml($html, $tafseer);

        if (blank($parsed['content'])) {
            throw new RuntimeException("Empty tafseer content parsed for {$url}");
        }

        return [
            'source_url' => $url,
            'source_slug' => $sourceSlug,
            'title' => $parsed['title'],
            'author' => $parsed['author'],
            'content' => $parsed['content'],
            'raw_html' => $html,
        ];
    }

    protected function parseTafseerHtml(string $html, Tafseer $tafseer): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DOMXPath($dom);
        $fallbackMeta = $this->defaultMeta($tafseer->slug);

        $title = $this->cleanText($this->firstNodeText($xpath, [
            "//title",
            "//div[contains(@class,'label-center')]",
        ]));

        if ($title !== '') {
            $title = preg_replace('/\s*-\s*\d+\s*:\s*\d+\s*$/u', '', $title);
            $title = preg_replace('/\s*-\s*[^-]+:\s*\d+\s*$/u', '', $title);
        }

        $content = $this->extractTafseerText($xpath, $html);

        return [
            'title' => $title ?: ($tafseer->title_urdu ?: Arr::get($fallbackMeta, 'title')),
            'author' => $tafseer->author ?: Arr::get($fallbackMeta, 'author'),
            'content' => $content,
        ];
    }

    protected function extractTafseerText(DOMXPath $xpath, string $html): string
    {
        $queries = [
            "(//div[contains(@class,'translation')]//span[contains(@class,'preformatted')])[last()]",
            "//div[contains(@class,'translation')]//span[contains(@class,'preformatted')]",
            "//div[contains(@class,'translation')]//span",
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);

            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            $parts = [];

            foreach ($nodes as $node) {
                $text = $this->cleanTafseerText($node->textContent);

                if ($this->looksLikeRealTafseer($text)) {
                    $parts[$text] = mb_strlen($text);
                }
            }

            if ($parts !== []) {
                arsort($parts);

                return (string) array_key_first($parts);
            }
        }

        return $this->extractByRegexFallback($html);
    }

    protected function extractByRegexFallback(string $html): string
    {
        $patterns = [
            '/<div[^>]*class="[^"]*translation[^"]*"[^>]*>\s*<span[^>]*class="[^"]*preformatted[^"]*"[^>]*>(.*?)<\/span>/is',
            '/<div[^>]*class="[^"]*translation[^"]*"[^>]*>\s*<span[^>]*>(.*?)<\/span>/is',
        ];

        $matchesByLength = [];

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $html, $matches)) {
                continue;
            }

            foreach ($matches[1] as $match) {
                $text = strip_tags(html_entity_decode($match, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $text = $this->cleanTafseerText($text);

                if ($this->looksLikeRealTafseer($text)) {
                    $matchesByLength[$text] = mb_strlen($text);
                }
            }
        }

        if ($matchesByLength !== []) {
            arsort($matchesByLength);

            return (string) array_key_first($matchesByLength);
        }

        return '';
    }

    protected function firstNodeText(DOMXPath $xpath, array $queries): string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);

            if ($nodes && $nodes->length > 0) {
                $text = trim($nodes->item(0)->textContent);

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    protected function pageLooksMissing(string $html): bool
    {
        foreach ([
            '404',
            'Page Not Found',
            'Not Found',
            'Sorry, the page you are looking for could not be found',
            'HTTP Status 404',
        ] as $check) {
            if (Str::contains($html, $check)) {
                return true;
            }
        }

        return false;
    }

    protected function looksLikeRealTafseer(?string $text): bool
    {
        $text = trim((string) $text);

        if ($text === '' || mb_strlen($text) < 80) {
            return false;
        }

        foreach ([
            'Debug Ayah ID',
            'Surah ID',
            'Ayah Number',
            'Tafseer Entries Count',
            'Back to surah',
            'Previous',
            'Next',
            'JUMP',
            'Go',
            'View Ayah In',
            'Navigate',
        ] as $bad) {
            if (Str::contains($text, $bad)) {
                return false;
            }
        }

        return true;
    }

    protected function cleanText(?string $text): string
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        return trim($text);
    }

    protected function cleanTafseerText(?string $text): string
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\x{00A0}/u', ' ', $text);
        $text = preg_replace('/[ ]{2,}/u', ' ', $text);
        $text = preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = preg_replace("/\n[ \t]+/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    protected function resolveSourceSlug(string $tafseerSlug): string
    {
        return $this->sourceSlugMap[$tafseerSlug] ?? $tafseerSlug;
    }

    protected function defaultMeta(string $tafseerSlug): array
    {
        return $this->defaultMetaBySlug[$tafseerSlug] ?? [];
    }

    protected function buildContentHtml(string $content): string
    {
        $paragraphs = preg_split("/\n{2,}/u", trim($content)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph) => '<p>' . nl2br(e(trim($paragraph))) . '</p>')
            ->implode("\n");
    }
}
