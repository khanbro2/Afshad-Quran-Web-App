<?php

namespace App\Console\Commands;

use App\Services\AyahThemeUrduSqlImporter;
use Illuminate\Console\Command;

class ImportAyahThemesUrduCommand extends Command
{
    protected $signature = 'app:import-ayah-themes-urdu
                            {--path=storage/datasets/quran/ayah-themes-urdu.sql : Relative path to the Urdu SQL mapping file}';

    protected $description = 'Import exact Urdu titles for ayah themes from the provided SQL mapping file';

    public function handle(AyahThemeUrduSqlImporter $importer): int
    {
        $path = (string) $this->option('path');

        $this->info('Starting ayah theme Urdu import');
        $this->line('SQL file: ' . $path);

        $stats = $importer->import($path);

        $this->newLine();
        $this->info('Ayah theme Urdu import completed');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Lines Processed', $stats['lines_processed']],
                ['Mappings Found', $stats['mappings_found']],
                ['Themes Updated', $stats['themes_updated']],
                ['Themes Missing', $stats['themes_missing']],
            ]
        );

        return self::SUCCESS;
    }
}
