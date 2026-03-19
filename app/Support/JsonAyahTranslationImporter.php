<?php

namespace App\Support;

use InvalidArgumentException;

class JsonAyahTranslationImporter
{
    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function parseFile(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Translation JSON file not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid translation JSON file: {$path}");
        }

        $verses = self::parseNestedSuraFormat($decoded);

        if ($verses !== []) {
            return $verses;
        }

        $verses = self::parseVerseKeyMapFormat($decoded);

        if ($verses !== []) {
            return $verses;
        }

        throw new InvalidArgumentException("Unsupported translation JSON format: {$path}");
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function parseNestedSuraFormat(array $decoded): array
    {
        $suras = $decoded['sura'] ?? null;

        if (! is_array($suras)) {
            return [];
        }

        $verses = [];

        foreach ($suras as $surahNumber => $surahData) {
            if (! is_array($surahData) || ! isset($surahData['aya']) || ! is_array($surahData['aya'])) {
                continue;
            }

            foreach ($surahData['aya'] as $ayahNumber => $text) {
                if (! is_scalar($text) || trim((string) $text) === '') {
                    continue;
                }

                $verses[] = [
                    'surah_number' => (int) $surahNumber,
                    'ayah_number' => (int) $ayahNumber,
                    'text' => trim((string) $text),
                ];
            }
        }

        return $verses;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function parseVerseKeyMapFormat(array $decoded): array
    {
        $verses = [];

        foreach ($decoded as $verseKey => $payload) {
            if (! preg_match('/^(\d+):(\d+)$/', (string) $verseKey, $matches)) {
                continue;
            }

            $text = null;

            if (is_array($payload)) {
                $candidate = $payload['t'] ?? $payload['text'] ?? null;
                $text = is_scalar($candidate) ? trim((string) $candidate) : null;
            } elseif (is_scalar($payload)) {
                $text = trim((string) $payload);
            }

            if ($text === null || $text === '') {
                continue;
            }

            $verses[] = [
                'surah_number' => (int) $matches[1],
                'ayah_number' => (int) $matches[2],
                'text' => $text,
            ];
        }

        return $verses;
    }
}
