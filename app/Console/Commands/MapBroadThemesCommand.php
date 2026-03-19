<?php

namespace App\Console\Commands;

use App\Services\BroadThemeMappingService;
use Illuminate\Console\Command;

class MapBroadThemesCommand extends Command
{
    protected $signature = 'app:map-broad-themes {--no-reset : Keep existing ayah broad-theme assignments before remapping}';

    protected $description = 'Map exact ayah themes into strict broad themes and assign them to ayahs';

    public function handle(BroadThemeMappingService $mappingService): int
    {
        $stats = $mappingService->remapAyahs(! $this->option('no-reset'));

        $this->info('Broad theme mapping completed');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Exact Themes Checked', $stats['themes_checked']],
                ['Exact Themes Matched', $stats['exact_themes_matched']],
                ['Ayahs Tagged', $stats['ayahs_tagged']],
                ['Assignments Created', $stats['assignments_created']],
            ]
        );

        return self::SUCCESS;
    }
}
