<?php

namespace App\Services;

use App\Models\Ayah;
use App\Models\AyahTheme;
use App\Support\AyahThemeUrduTranslator;
use PDO;
use RuntimeException;
use Illuminate\Support\Str;

class AyahThemeDatasetImporter
{
    public function __construct(
        protected AyahThemeUrduTranslator $urduTranslator
    ) {
    }

    public function import(string $databasePath, bool $reset = false, ?callable $progress = null): array
    {
        $fullPath = base_path($databasePath);

        if (! is_file($fullPath)) {
            throw new RuntimeException("Dataset file not found: {$fullPath}");
        }

        $pdo = new PDO('sqlite:' . $fullPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $rows = $pdo->query('SELECT theme, surah_number, ayah_from, ayah_to, keywords, total_ayahs FROM themes ORDER BY surah_number, ayah_from, ayah_to')
            ->fetchAll(PDO::FETCH_ASSOC);

        if ($reset) {
            \DB::table('ayah_theme_assignments')->delete();
            AyahTheme::query()->delete();
        }

        $themeIds = $this->syncThemes($rows);

        $stats = [
            'rows_processed' => 0,
            'ayahs_touched' => 0,
            'assignments_created' => 0,
            'rows_imported' => 0,
            'themes_created' => $themeIds->count(),
        ];

        foreach ($rows as $row) {
            $stats['rows_processed']++;

            $topic = trim((string) ($row['theme'] ?? ''));
            $keywords = trim((string) ($row['keywords'] ?? ''));
            $surahNumber = (int) ($row['surah_number'] ?? 0);
            $ayahFrom = (int) ($row['ayah_from'] ?? 0);
            $ayahTo = (int) ($row['ayah_to'] ?? 0);
            $slug = $this->makeThemeSlug($topic);
            $themeId = $themeIds->get($slug);

            if (! $themeId) {
                continue;
            }

            $ayahIds = Ayah::query()
                ->whereHas('surah', fn ($query) => $query->where('number', $surahNumber))
                ->whereBetween('ayah_number', [$ayahFrom, $ayahTo])
                ->pluck('id');

            if ($ayahIds->isEmpty()) {
                continue;
            }

            $stats['ayahs_touched'] += $ayahIds->count();

            foreach ($ayahIds as $ayahId) {
                $inserted = \DB::table('ayah_theme_assignments')->insertOrIgnore([
                    'ayah_id' => $ayahId,
                    'ayah_theme_id' => $themeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $stats['assignments_created'] += $inserted;
            }

            $stats['rows_imported']++;

            if ($progress) {
                $progress([
                    'topic' => $topic,
                    'surah' => $surahNumber,
                    'ayah_from' => $ayahFrom,
                    'ayah_to' => $ayahTo,
                    'keywords' => $keywords,
                    'rows_processed' => $stats['rows_processed'],
                ]);
            }
        }

        return $stats;
    }

    protected function syncThemes(array $rows)
    {
        $grouped = collect($rows)
            ->groupBy(fn (array $row) => trim((string) ($row['theme'] ?? '')))
            ->filter(fn ($items, $theme) => $theme !== '');

        $themeIds = collect();

        foreach ($grouped as $themeName => $items) {
            $keywords = collect($items)
                ->pluck('keywords')
                ->filter(fn ($value) => filled($value))
                ->unique()
                ->implode(', ');

            $slug = $this->makeThemeSlug($themeName);

            $theme = AyahTheme::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title_english' => $themeName,
                    'title_urdu' => $this->urduTranslator->translate($themeName),
                    'description' => $keywords !== '' ? 'Keywords: ' . $keywords : null,
                    'badge_color' => $this->colorForTheme($themeName),
                    'sort_order' => 0,
                    'is_active' => true,
                ]
            );

            $themeIds->put($slug, $theme->id);
        }

        return $themeIds;
    }

    protected function makeThemeSlug(string $topic): string
    {
        $slug = Str::slug(Str::limit($topic, 120, ''));
        $suffix = substr(md5($topic), 0, 8);

        if ($slug !== '') {
            return $slug . '-' . $suffix;
        }

        return 'theme-' . $suffix;
    }

    protected function colorForTheme(string $topic): string
    {
        $palette = [
            '#0e7c66',
            '#cb7a00',
            '#7048ff',
            '#0c8a69',
            '#1874d1',
            '#d53c63',
            '#9b7a47',
            '#8e5f00',
            '#4f63d6',
            '#8b5cf6',
            '#be6a15',
            '#2f855a',
        ];

        $index = abs(crc32($topic)) % count($palette);

        return $palette[$index];
    }
}
