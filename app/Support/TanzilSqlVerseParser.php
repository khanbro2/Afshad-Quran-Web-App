<?php

namespace App\Support;

use InvalidArgumentException;

class TanzilSqlVerseParser
{
    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function parseFile(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Tanzil SQL file not found: {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open Tanzil SQL file: {$path}");
        }

        $verses = [];
        $buffer = '';
        $capturingInsert = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                if (! $capturingInsert) {
                    if (! str_starts_with($trimmed, 'INSERT INTO `quran_text`')) {
                        continue;
                    }

                    $capturingInsert = true;
                    $buffer = $line;

                    if (str_contains($line, ';')) {
                        $verses = [...$verses, ...self::parseInsertStatement($buffer)];
                        $capturingInsert = false;
                        $buffer = '';
                    }

                    continue;
                }

                $buffer .= $line;

                if (str_contains($line, ';')) {
                    $verses = [...$verses, ...self::parseInsertStatement($buffer)];
                    $capturingInsert = false;
                    $buffer = '';
                }
            }
        } finally {
            fclose($handle);
        }

        if ($capturingInsert) {
            throw new InvalidArgumentException('Invalid Tanzil SQL format: unterminated INSERT statement.');
        }

        if ($verses === []) {
            throw new InvalidArgumentException('Invalid Tanzil SQL format: no quran_text INSERT rows were parsed.');
        }

        return $verses;
    }

    /**
     * @return array<int, array{surah_number:int, ayah_number:int, text:string}>
     */
    public static function parseInsertStatement(string $sql): array
    {
        preg_match_all(
            "/\\(\\s*\\d+\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*'((?:\\\\\\\\.|\\\\'|''|[^'])*)'\\s*\\)/u",
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        if ($matches === []) {
            throw new InvalidArgumentException('Invalid Tanzil SQL format: unable to parse quran_text INSERT row.');
        }

        return array_map(function (array $match): array {
            return [
                'surah_number' => (int) $match[1],
                'ayah_number' => (int) $match[2],
                'text' => self::decodeSqlString($match[3]),
            ];
        }, $matches);
    }

    protected static function decodeSqlString(string $value): string
    {
        return str_replace(
            ["\\\\", "\\'", "''", "\\r", "\\n"],
            ["\\", "'", "'", "\r", "\n"],
            $value
        );
    }
}
