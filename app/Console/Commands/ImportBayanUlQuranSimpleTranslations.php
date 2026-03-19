<?php

namespace App\Console\Commands;

use App\Support\AyahTranslationImporter;
use App\Support\JsonAyahTranslationImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportBayanUlQuranSimpleTranslations extends Command
{
    protected $signature = 'app:import-bayan-simple-translations
        {--file=storage/datasets/quran/bayan-ul-quran-simple.json : Path to the Bayan-ul-Quran simple JSON file}';

    protected $description = 'Import Bayan-ul-Quran simple ayah translations into ayahs.urdu_translation_bayan_simple.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $verses = JsonAyahTranslationImporter::parseFile($path);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $stats = AyahTranslationImporter::importIntoColumn($verses, 'urdu_translation_bayan_simple');

        $this->info('Bayan-ul-Quran simple import complete.');
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
            return storage_path('datasets/quran/bayan-ul-quran-simple.json');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
