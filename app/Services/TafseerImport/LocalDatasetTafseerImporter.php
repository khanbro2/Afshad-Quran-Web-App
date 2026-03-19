<?php

namespace App\Services\TafseerImport;

use App\Models\Ayah;
use App\Models\AyahTafseer;
use App\Models\Tafseer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class LocalDatasetTafseerImporter
{
    public function import(
        string $tafseerSlug,
        bool $update = false,
        ?int $fromSurah = null,
        ?int $toSurah = null,
        ?callable $progress = null,
        ?int $fromAyah = null
    ): array {
        $tafseer = Tafseer::query()->where('slug', $tafseerSlug)->first();

        if (! $tafseer) {
            throw new RuntimeException("Tafseer not found for slug: {$tafseerSlug}");
        }

        $dataset = config('tafseer_import.local_datasets.' . $tafseerSlug);

        if (! is_array($dataset) || blank($dataset['path'] ?? null)) {
            throw new RuntimeException("Local dataset config not found for slug: {$tafseerSlug}");
        }

        $path = (string) $dataset['path'];

        if (! File::exists($path)) {
            throw new RuntimeException("Local dataset file not found: {$path}");
        }

        $fromSurah = $fromSurah ?: 1;
        $toSurah = $toSurah ?: 114;
        $fromAyah = max(1, (int) ($fromAyah ?: 1));

        $ayahMap = Ayah::query()
            ->with('surah:id,number')
            ->whereHas('surah', function ($query) use ($fromSurah, $toSurah) {
                $query->whereBetween('number', [$fromSurah, $toSurah]);
            })
            ->get()
            ->mapWithKeys(fn (Ayah $ayah) => [
                $ayah->surah->number . ':' . $ayah->ayah_number => $ayah->id,
            ])
            ->all();

        $existingEntryIds = AyahTafseer::query()
            ->where('tafseer_id', $tafseer->id)
            ->pluck('id', 'ayah_id')
            ->all();

        $stats = [
            'tafseer' => $tafseerSlug,
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        $driver = (string) ($dataset['driver'] ?? 'sql_insert');

        if ($driver === 'json_tafseer_map') {
            return $this->importJsonTafseerMap(
                $tafseer,
                $tafseerSlug,
                $dataset,
                $ayahMap,
                $existingEntryIds,
                $stats,
                $update,
                $fromSurah,
                $toSurah,
                $fromAyah,
                $path,
                $progress,
            );
        }

        $handle = fopen($path, 'r');

        if (! is_resource($handle)) {
            throw new RuntimeException("Unable to open local dataset file: {$path}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $parsed = $this->parseInsertLine($line, $tafseerSlug);

                if ($parsed === null) {
                    continue;
                }

                [$surahNumber, $ayahNumber, $content] = $parsed;

                if ($surahNumber < $fromSurah || $surahNumber > $toSurah) {
                    continue;
                }

                if ($surahNumber === $fromSurah && $ayahNumber < $fromAyah) {
                    continue;
                }

                $stats['processed']++;

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

                $ayahId = $ayahMap[$surahNumber . ':' . $ayahNumber] ?? null;

                if (! $ayahId) {
                    $stats['failed']++;
                    $stats['failures'][] = [
                        'surah' => $surahNumber,
                        'ayah' => $ayahNumber,
                        'error' => 'Ayah not found in database',
                    ];
                    continue;
                }

                $existingId = $existingEntryIds[$ayahId] ?? null;

                if ($existingId && ! $update) {
                    $stats['skipped']++;
                    continue;
                }

                $payload = [
                    'ayah_id' => $ayahId,
                    'tafseer_id' => $tafseer->id,
                    'content' => $content,
                    'content_html' => $this->buildContentHtml($content),
                    'meta' => [
                        'title' => $tafseer->title_urdu ?: ($dataset['title_urdu'] ?? null),
                        'author' => $tafseer->author ?: ($dataset['author'] ?? null),
                        'source_url' => $tafseer->source_url ?: ($dataset['source_url'] ?? null),
                        'source_slug' => $tafseerSlug,
                        'source_type' => 'local_dataset',
                    ],
                ];

                if ($existingId) {
                    AyahTafseer::query()->whereKey($existingId)->update($payload);
                    $stats['updated']++;
                } else {
                    $created = AyahTafseer::query()->create($payload);
                    $existingEntryIds[$ayahId] = $created->id;
                    $stats['inserted']++;
                }
            }
        } finally {
            fclose($handle);
        }

        return $stats;
    }

    public function findResumePoint(string $tafseerSlug, int $fromSurah = 1, int $toSurah = 114): ?array
    {
        $tafseer = Tafseer::query()->where('slug', $tafseerSlug)->first();

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

    protected function parseInsertLine(string $line): ?array
    {
        return $this->parseInsertLineForTable($line, 'arabic_jalalayn');
    }

    protected function parseInsertLineForTable(string $line, string $tableName): ?array
    {
        $line = trim($line);

        if ($line === '' || ! str_starts_with($line, "INSERT INTO `{$tableName}`")) {
            return null;
        }

        if (! preg_match("/^INSERT INTO `{$tableName}` \\(sura, aya, text\\) VALUES \\((\\d+),\\s*(\\d+),\\s*'(.*)'\\);$/u", $line, $matches)) {
            return null;
        }

        $content = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $matches[3]);
        $content = str_replace("''", "'", $content);
        $content = stripslashes($content);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\x{00A0}/u', ' ', $content);
        $content = preg_replace('/[ ]{2,}/u', ' ', $content);
        $content = preg_replace("/\n{3,}/u", "\n\n", $content);

        return [
            (int) $matches[1],
            (int) $matches[2],
            trim((string) $content),
        ];
    }

    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<string, int>  $ayahMap
     * @param  array<int, int>  $existingEntryIds
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    protected function importJsonTafseerMap(
        Tafseer $tafseer,
        string $tafseerSlug,
        array $dataset,
        array $ayahMap,
        array $existingEntryIds,
        array $stats,
        bool $update,
        int $fromSurah,
        int $toSurah,
        int $fromAyah,
        string $path,
        ?callable $progress = null
    ): array {
        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON tafseer dataset: {$path}");
        }

        foreach ($decoded as $verseKey => $rawEntry) {
            if (! preg_match('/^(\d+):(\d+)$/', (string) $verseKey, $matches)) {
                continue;
            }

            $surahNumber = (int) $matches[1];
            $ayahNumber = (int) $matches[2];

            if ($surahNumber < $fromSurah || $surahNumber > $toSurah) {
                continue;
            }

            if ($surahNumber === $fromSurah && $ayahNumber < $fromAyah) {
                continue;
            }

            $resolved = $this->resolveJsonTafseerEntry($decoded, (string) $verseKey, $rawEntry);

            if ($resolved === null) {
                continue;
            }

            $stats['processed']++;

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

            $ayahId = $ayahMap[$surahNumber . ':' . $ayahNumber] ?? null;

            if (! $ayahId) {
                $stats['failed']++;
                $stats['failures'][] = [
                    'surah' => $surahNumber,
                    'ayah' => $ayahNumber,
                    'error' => 'Ayah not found in database',
                ];
                continue;
            }

            $existingId = $existingEntryIds[$ayahId] ?? null;

            if ($existingId && ! $update) {
                $stats['skipped']++;
                continue;
            }

            $contentHtml = $resolved['content_html'];
            $content = $resolved['content'];
            $linkedAyahKeys = $resolved['ayah_keys'];

            $payload = [
                'ayah_id' => $ayahId,
                'tafseer_id' => $tafseer->id,
                'content' => $content,
                'content_html' => $contentHtml,
                'meta' => [
                    'title' => $tafseer->title_urdu ?: ($dataset['title_urdu'] ?? null),
                    'author' => $tafseer->author ?: ($dataset['author'] ?? null),
                    'source_url' => $tafseer->source_url ?: ($dataset['source_url'] ?? null),
                    'source_slug' => $tafseerSlug,
                    'source_type' => 'local_dataset',
                    'linked_ayah_keys' => $linkedAyahKeys,
                ],
            ];

            if ($existingId) {
                AyahTafseer::query()->whereKey($existingId)->update($payload);
                $stats['updated']++;
            } else {
                $created = AyahTafseer::query()->create($payload);
                $existingEntryIds[$ayahId] = $created->id;
                $stats['inserted']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  mixed  $rawEntry
     * @return array{content:string,content_html:string,ayah_keys:array<int, string>}|null
     */
    protected function resolveJsonTafseerEntry(array $decoded, string $verseKey, mixed $rawEntry, int $depth = 0): ?array
    {
        if ($depth > 5) {
            return null;
        }

        if (is_string($rawEntry)) {
            $aliasedEntry = $decoded[$rawEntry] ?? null;

            if ($aliasedEntry === null || $rawEntry === $verseKey) {
                return null;
            }

            return $this->resolveJsonTafseerEntry($decoded, $rawEntry, $aliasedEntry, $depth + 1);
        }

        if (! is_array($rawEntry)) {
            return null;
        }

        $html = trim((string) ($rawEntry['text'] ?? ''));

        if ($html === '') {
            return null;
        }

        $contentHtml = $this->normalizeHtml($html);
        $content = trim(html_entity_decode(strip_tags($contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $content = preg_replace('/\s+/u', ' ', $content) ?? $content;

        $ayahKeys = collect($rawEntry['ayah_keys'] ?? [])
            ->filter(fn ($key) => is_string($key) && preg_match('/^\d+:\d+$/', $key))
            ->values()
            ->all();

        return [
            'content' => $content,
            'content_html' => $contentHtml,
            'ayah_keys' => $ayahKeys,
        ];
    }

    protected function normalizeHtml(string $html): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/\s+dir="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/\s+class="[^"]*"/i', '', $html) ?? $html;

        return trim(Str::of($html)->replace("\u{00A0}", ' ')->toString());
    }

    protected function buildContentHtml(string $content): string
    {
        $paragraphs = preg_split("/\n{2,}/u", trim($content)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph) => '<p>' . nl2br(e(trim($paragraph))) . '</p>')
            ->implode("\n");
    }
}
