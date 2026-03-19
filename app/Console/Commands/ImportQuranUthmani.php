<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Models\Surah;
use App\Support\TanzilSqlVerseParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ImportQuranUthmani extends Command
{
    protected $signature = 'app:import-quran-uthmani
        {--file=storage/datasets/quran/quran-uthmani.sql : Path to the Tanzil Uthmani SQL file}';

    protected $description = 'Import Tanzil Uthmani Quran text into ayahs.uthmani_text.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $verses = TanzilSqlVerseParser::parseFile($path);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $surahIds = Surah::query()
            ->pluck('id', 'number')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ayahRows = Ayah::query()
            ->get(['id', 'surah_id', 'ayah_number', 'uthmani_text'])
            ->mapWithKeys(fn (Ayah $ayah) => [
                $ayah->surah_id.':'.$ayah->ayah_number => [
                    'id' => (int) $ayah->id,
                    'uthmani_text' => $ayah->uthmani_text,
                ],
            ])
            ->all();

        $stats = [
            'parsed' => count($verses),
            'updated' => 0,
            'unchanged' => 0,
            'missing_surah' => 0,
            'missing_ayah' => 0,
        ];

        DB::transaction(function () use ($verses, $surahIds, $ayahRows, &$stats): void {
            foreach ($verses as $verse) {
                $surahId = $surahIds[$verse['surah_number']] ?? null;

                if ($surahId === null) {
                    $stats['missing_surah']++;
                    continue;
                }

                $ayahKey = $surahId.':'.$verse['ayah_number'];
                $ayahRow = $ayahRows[$ayahKey] ?? null;

                if ($ayahRow === null) {
                    $stats['missing_ayah']++;
                    continue;
                }

                if ((string) $ayahRow['uthmani_text'] === $verse['text']) {
                    $stats['unchanged']++;
                    continue;
                }

                DB::table('ayahs')
                    ->where('id', $ayahRow['id'])
                    ->update([
                        'uthmani_text' => $verse['text'],
                        'updated_at' => now(),
                    ]);

                $stats['updated']++;
            }
        });

        $this->info('Tanzil Uthmani import complete.');
        $this->line("Parsed verses: {$stats['parsed']}");
        $this->line("Updated ayahs: {$stats['updated']}");
        $this->line("Unchanged ayahs: {$stats['unchanged']}");
        $this->line("Missing surahs: {$stats['missing_surah']}");
        $this->line("Missing ayahs: {$stats['missing_ayah']}");

        return self::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if ($path === '') {
            return storage_path('datasets/quran/quran-uthmani.sql');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
