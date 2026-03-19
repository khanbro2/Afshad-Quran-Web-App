<?php

namespace App\Support;

use InvalidArgumentException;

class AyahIrabExtractor
{
    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function extract(string $path, ?string $format = null, string $tableName = 'ayah_irab'): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("I'rab file not found: {$path}");
        }

        $resolvedFormat = strtolower($format ?: pathinfo($path, PATHINFO_EXTENSION));

        return match ($resolvedFormat) {
            'sql' => self::extractFromSql($path, $tableName),
            'json' => self::extractFromJson($path),
            'csv' => self::extractFromDelimited($path, ','),
            'tsv', 'txt' => self::extractFromText($path),
            default => throw new InvalidArgumentException("Unsupported i'rab format: {$resolvedFormat}"),
        };
    }

    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function extractFromSql(string $path, string $tableName): array
    {
        return AyahTranslationSqlParser::parseFile($path, $tableName);
    }

    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function extractFromJson(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid i'rab JSON format: {$path}");
        }

        $rows = $decoded['verses'] ?? $decoded['ayahs'] ?? $decoded;

        if (! is_array($rows)) {
            throw new InvalidArgumentException("Invalid i'rab JSON format: {$path}");
        }

        $verses = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $verses[] = self::normalizeRow($row);
        }

        if ($verses === []) {
            throw new InvalidArgumentException("Invalid i'rab JSON format: no usable rows found in {$path}");
        }

        return $verses;
    }

    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function extractFromDelimited(string $path, string $delimiter): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open i'rab file: {$path}");
        }

        try {
            $header = fgetcsv($handle, 0, $delimiter);

            if (! is_array($header)) {
                throw new InvalidArgumentException("Invalid i'rab CSV format: {$path}");
            }

            $normalizedHeader = array_map(fn ($value) => strtolower(trim((string) $value)), $header);
            $verses = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $assoc = [];

                foreach ($normalizedHeader as $index => $column) {
                    $assoc[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                $verses[] = self::normalizeRow($assoc);
            }
        } finally {
            fclose($handle);
        }

        if ($verses === []) {
            throw new InvalidArgumentException("Invalid i'rab CSV format: no usable rows found in {$path}");
        }

        return $verses;
    }

    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    protected static function extractFromText(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || $lines === []) {
            throw new InvalidArgumentException("Invalid i'rab text format: {$path}");
        }

        $verses = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^(\d+)\s*[:|,]\s*(\d+)\s*[|,]\s*(.+)$/u', $trimmed, $matches) === 1) {
                $verses[] = [
                    'surah_number' => (int) $matches[1],
                    'ayah_number' => (int) $matches[2],
                    'text' => trim($matches[3]),
                ];

                continue;
            }

            throw new InvalidArgumentException("Invalid i'rab text row: {$trimmed}");
        }

        if ($verses === []) {
            throw new InvalidArgumentException("Invalid i'rab text format: no usable rows found in {$path}");
        }

        return $verses;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{surah_number:int, ayah_number:int, text:string}
     */
    protected static function normalizeRow(array $row): array
    {
        $surah = $row['surah_number'] ?? $row['surah'] ?? $row['sura'] ?? $row['chapter'] ?? null;
        $ayah = $row['ayah_number'] ?? $row['ayah'] ?? $row['aya'] ?? $row['verse'] ?? null;
        $text = $row['text'] ?? $row['irab'] ?? $row['i_rab'] ?? $row['content'] ?? null;

        if (! is_numeric($surah) || ! is_numeric($ayah) || ! is_string($text) || trim($text) === '') {
            throw new InvalidArgumentException('Invalid i\'rab row: surah, ayah, and text are required.');
        }

        return [
            'surah_number' => (int) $surah,
            'ayah_number' => (int) $ayah,
            'text' => trim($text),
        ];
    }
}
