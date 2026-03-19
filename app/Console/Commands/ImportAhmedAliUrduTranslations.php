<?php

namespace App\Console\Commands;

use App\Support\AyahTranslationImporter;
use App\Support\AyahTranslationSqlParser;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportAhmedAliUrduTranslations extends Command
{
    protected $signature = 'app:import-urdu-ahmedali
        {--file=storage/datasets/quran/urdu_ahmedali.sql : Path to the Ahmed Ali Urdu SQL file}';

    protected $description = 'Import Ahmed Ali Urdu ayah translations into ayahs.urdu_translation_ahmedali.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->option('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $verses = AyahTranslationSqlParser::parseFile($path, 'urdu_ahmedali');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $stats = AyahTranslationImporter::importIntoColumn($verses, 'urdu_translation_ahmedali');

        $this->info('Ahmed Ali Urdu import complete.');
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
            return storage_path('datasets/quran/urdu_ahmedali.sql');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
