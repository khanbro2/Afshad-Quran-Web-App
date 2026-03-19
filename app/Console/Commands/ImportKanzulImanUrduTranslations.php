<?php

namespace App\Console\Commands;

use App\Support\AyahTranslationImporter;
use App\Support\AyahTranslationSqlParser;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportKanzulImanUrduTranslations extends Command
{
    protected $signature = 'app:import-urdu-kanzuliman
        {--file=storage/datasets/quran/urdu_kanzuliman.sql : Path to the Kanzul Iman Urdu SQL file}';

    protected $description = 'Import Kanzul Iman (Ahmed Raza Khan) Urdu ayah translations into ayahs.urdu_translation_kanzuliman.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $verses = AyahTranslationSqlParser::parseFile($path, 'urdu_kanzuliman');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $stats = AyahTranslationImporter::importIntoColumn($verses, 'urdu_translation_kanzuliman');

        $this->info('Kanzul Iman Urdu import complete.');
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
            return storage_path('datasets/quran/urdu_kanzuliman.sql');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
