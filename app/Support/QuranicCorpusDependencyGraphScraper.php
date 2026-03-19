<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class QuranicCorpusDependencyGraphScraper
{
    public static function fetchGraphImages(int $surahNumber, int $ayahNumber): array
    {
        $pendingTokens = [1];
        $visitedTokens = [];
        $allImageUrls = [];
        $seenImageUrls = [];

        while (!empty($pendingTokens)) {
            $token = array_shift($pendingTokens);

            if (in_array($token, $visitedTokens, true)) {
                continue;
            }

            $visitedTokens[] = $token;

            $response = Http::connectTimeout(20)
                ->timeout(90)
                ->retry(3, 1500)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 Quran Study App Dependency Graph Importer',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get('https://corpus.quran.com/treebank.jsp', [
                    'chapter' => $surahNumber,
                    'verse' => $ayahNumber,
                    'token' => $token,
                ]);

            $response->throw();
            $html = $response->body();

            // Extract graph image URLs from current token page
            $graphUrls = self::parseGraphImageUrls($html);

            foreach ($graphUrls as $url) {
                if (!in_array($url, $seenImageUrls, true)) {
                    $seenImageUrls[] = $url;
                    $allImageUrls[] = $url;
                }
            }

            // Discover other token pages for the same ayah via pagination links
            if (preg_match_all('/token=(\d+)/i', $html, $tokenMatches) && !empty($tokenMatches[1])) {
                foreach ($tokenMatches[1] as $tokenValue) {
                    $tokenValue = (int) $tokenValue;

                    if (
                        $tokenValue > 0 &&
                        !in_array($tokenValue, $visitedTokens, true) &&
                        !in_array($tokenValue, $pendingTokens, true)
                    ) {
                        $pendingTokens[] = $tokenValue;
                    }
                }
            }
        }

        if (empty($allImageUrls)) {
            return [];
        }

        $images = [];

        foreach ($allImageUrls as $url) {
            $imageData = self::fetchGraphImageByUrl($url);

            if ($imageData === null || $imageData === '') {
                continue;
            }

            if (!self::isValidPng($imageData)) {
                continue;
            }

            $images[] = $imageData;
        }

        return $images;
    }

    public static function fetchGraphImageByUrl(string $url): ?string
    {
        $graphResponse = Http::connectTimeout(20)
            ->timeout(90)
            ->retry(3, 1500)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 Quran Study App Dependency Graph Importer',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->get($url);

        $graphResponse->throw();

        return $graphResponse->body();
    }

    protected static function isValidPng(string $content): bool
    {
        return $content !== '' && str_starts_with($content, "\x89PNG\r\n\x1a\n");
    }

    public static function parseGraphImageUrls(string $html): array
{
    $urls = [];

    // Pattern 1: CSS background url(/graphimage?id=10)
    if (
        preg_match_all(
            '/url\((["\']?)(\/graphimage\?id=\d+)\1\)/i',
            $html,
            $matches
        ) && !empty($matches[2])
    ) {
        foreach ($matches[2] as $path) {
            $urls[] = 'https://corpus.quran.com' . $path;
        }
    }

    // Pattern 2: direct graphimage URLs
    if (preg_match_all('/graphimage\?id=(\d+)/i', $html, $matches) && !empty($matches[1])) {
        foreach ($matches[1] as $id) {
            $urls[] = 'https://corpus.quran.com/graphimage?id=' . (int) $id;
        }
    }

    // remove duplicates
    $unique = [];

    foreach ($urls as $url) {
        if (!in_array($url, $unique, true)) {
            $unique[] = $url;
        }
    }

    return $unique;
}
}