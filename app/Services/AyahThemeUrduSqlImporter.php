<?php

namespace App\Services;

use App\Models\AyahTheme;
use RuntimeException;

class AyahThemeUrduSqlImporter
{
    public function import(string $sqlPath): array
    {
        $fullPath = base_path($sqlPath);

        if (! is_file($fullPath)) {
            throw new RuntimeException("Urdu SQL file not found: {$fullPath}");
        }

        $lines = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new RuntimeException("Unable to read Urdu SQL file: {$fullPath}");
        }

        $stats = [
            'lines_processed' => 0,
            'mappings_found' => 0,
            'themes_updated' => 0,
            'themes_missing' => 0,
        ];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '--')) {
                continue;
            }

            $stats['lines_processed']++;

            if (! preg_match("/^UPDATE themes SET theme_urdu = '(.*)' WHERE theme = '(.*)';$/u", $line, $matches)) {
                continue;
            }

            $stats['mappings_found']++;

            $urduTitle = str_replace("''", "'", $matches[1]);
            $englishTitle = str_replace("''", "'", $matches[2]);

            $updated = AyahTheme::query()
                ->where('title_english', $englishTitle)
                ->update([
                    'title_urdu' => $urduTitle,
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                $stats['themes_updated'] += $updated;
            } else {
                $stats['themes_missing']++;
            }
        }

        return $stats;
    }
}
