<?php

namespace App\Console\Commands;

use App\Services\AyahThemeDatasetImporter;
use Illuminate\Console\Command;

class ImportAyahThemesCommand extends Command
{
    protected $signature = 'app:import-ayah-themes
                            {--path=storage/datasets/quran/ayah-themes.db : Relative path to the SQLite dataset}
                            {--reset : Clear existing ayah theme assignments before import}';

    protected $description = 'Import exact ayah themes and assignments from the local SQLite theme dataset';

    public function handle(AyahThemeDatasetImporter $importer): int
    {
        $path = (string) $this->option('path');
        $reset = (bool) $this->option('reset');

        $this->info('Starting exact ayah theme import');
        $this->line('Dataset: ' . $path);
        $this->line('Mode: ' . ($reset ? 'reset themes and assignments, then import exact dataset themes' : 'append/update without duplicates'));

        $stats = $importer->import($path, $reset, function (array $payload): void {
            $this->line("Importing theme \"{$payload['topic']}\" => Surah {$payload['surah']} Ayah {$payload['ayah_from']}-{$payload['ayah_to']}");
        });

        $this->newLine();
        $this->info('Ayah theme import completed');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Exact Themes Created', $stats['themes_created']],
                ['Rows Processed', $stats['rows_processed']],
                ['Rows Imported', $stats['rows_imported']],
                ['Ayahs Touched', $stats['ayahs_touched']],
                ['Assignments Created', $stats['assignments_created']],
            ]
        );

        return self::SUCCESS;
    }
}
