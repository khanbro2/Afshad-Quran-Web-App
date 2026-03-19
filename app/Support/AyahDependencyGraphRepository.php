<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class AyahDependencyGraphRepository
{
    private const DATASET_PATH = 'storage/datasets/quran/ayah-dependency-graphs.json';

    /**
     * @var array<string, array{text?: string}>
     */
    private static array $graphs = [];

    private static bool $loaded = false;

    public function findByReference(int $surahNumber, int $ayahNumber): ?string
    {
        $graphs = $this->loadGraphs();
        $key = $surahNumber . ':' . $ayahNumber;
        $entry = $graphs[$key] ?? null;

        if (!is_array($entry)) {
            return null;
        }

        $svg = trim((string) ($entry['text'] ?? ''));

        return $svg !== '' ? $svg : null;
    }

    /**
     * @return array<string, array{text?: string}>
     */
    private function loadGraphs(): array
    {
        if (self::$loaded) {
            return self::$graphs;
        }

        $path = base_path(self::DATASET_PATH);

        if (!File::exists($path)) {
            self::$loaded = true;

            return self::$graphs;
        }

        $decoded = json_decode(File::get($path), true);

        if (is_array($decoded)) {
            self::$graphs = $decoded;
        }

        self::$loaded = true;

        return self::$graphs;
    }
}
