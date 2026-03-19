<?php

namespace App\Console\Commands;

use App\Services\TafseerImport\TafseerImportManager;
use Illuminate\Console\Command;

class ImportTafseerCommand extends Command
{
    protected $signature = 'app:import-tafseer
                            {slug : Tafseer slug}
                            {--update : Update existing records}
                            {--resume : Resume from the next pending ayah for this tafseer}
                            {--fromSurah=1 : Start from this surah}
                            {--toSurah=114 : End at this surah}';

    protected $description = 'Import tafseer ayah-by-ayah from the configured source';

    public function handle(TafseerImportManager $importer): int
    {
        $slug = (string) $this->argument('slug');
        $update = (bool) $this->option('update');
        $resume = (bool) $this->option('resume');
        $fromSurah = (int) $this->option('fromSurah');
        $toSurah = (int) $this->option('toSurah');
        $fromAyah = 1;

        if ($resume && ! $update) {
            $resumePoint = $importer->findResumePoint($slug, $fromSurah, $toSurah);

            if (($resumePoint['complete'] ?? false) === true) {
                $this->info("{$slug} is already complete for the selected range.");
                return self::SUCCESS;
            }

            $fromSurah = (int) ($resumePoint['surah'] ?? $fromSurah);
            $fromAyah = (int) ($resumePoint['ayah'] ?? 1);
        }

        $this->info("Starting tafseer import: {$slug}");
        $this->line("Range: Surah {$fromSurah}, Ayah {$fromAyah} to Surah {$toSurah}");
        $this->line('Mode: ' . ($update ? 'update existing' : 'skip existing'));

        $stats = $importer->import(
            $slug,
            $update,
            $fromSurah,
            $toSurah,
            function (string $stage, array $payload): void {
                if ($stage === 'fetching') {
                    $this->line("Fetching {$payload['tafseer']} => Surah {$payload['surah']}, Ayah {$payload['ayah']}");
                }

                if ($stage === 'failed') {
                    $this->warn("Failed {$payload['tafseer']} => Surah {$payload['surah']}, Ayah {$payload['ayah']}");
                }
            },
            $fromAyah
        );

        $this->newLine();
        $this->info('Import completed');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tafseer', $stats['tafseer']],
                ['Processed', $stats['processed']],
                ['Inserted', $stats['inserted']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        if (! empty($stats['failures'])) {
            $this->warn('Some ayat failed:');

            foreach (array_slice($stats['failures'], 0, 20) as $failure) {
                $this->line(
                    "Surah {$failure['surah']}, Ayah {$failure['ayah']} => " . ($failure['error'] ?? $failure['message'] ?? 'Unknown error')
                );
            }

            if (count($stats['failures']) > 20) {
                $this->line('... and ' . (count($stats['failures']) - 20) . ' more');
            }
        }

        return self::SUCCESS;
    }
}
