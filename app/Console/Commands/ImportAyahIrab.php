<?php

namespace App\Console\Commands;

use App\Support\AyahIrabExtractor;
use App\Support\AyahTranslationImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportAyahIrab extends Command
{
    protected $signature = 'app:import-ayah-irab
        {--file= : Path to the ayah-level i\'rab source file}
        {--format= : Force input format (sql, json, csv, txt)}
        {--table=ayah_irab : SQL table name when importing from SQL files}';

    protected $description = 'Import ayah-level Arabic i\'rab text into ayahs.irab_arabic.';

    public function handle(): int
    {
        $pathOption = trim((string) $this->option('file'));

        if ($pathOption === '') {
            $this->error("Provide an i'rab file with --file=... (sql/json/csv/txt).");

            return self::FAILURE;
        }

        $path = $this->resolvePath($pathOption);

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $verses = AyahIrabExtractor::extract(
                $path,
                $this->option('format') ? (string) $this->option('format') : null,
                (string) $this->option('table')
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $stats = AyahTranslationImporter::importIntoColumn($verses, 'irab_arabic');

        $this->info("Ayah i'rab import complete.");
        $this->line("Parsed verses: {$stats['parsed']}");
        $this->line("Updated ayahs: {$stats['updated']}");
        $this->line("Unchanged ayahs: {$stats['unchanged']}");
        $this->line("Missing surahs: {$stats['missing_surah']}");
        $this->line("Missing ayahs: {$stats['missing_ayah']}");

        return self::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
