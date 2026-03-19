<?php

namespace App\Console\Commands;

use App\Models\Tafseer;
use App\Services\TafseerImport\TafseerImportManager;
use Illuminate\Console\Command;

class ImportAllTafaseerCommand extends Command
{
    protected $signature = 'app:import-all-tafaseer
                            {--update : Update existing records}
                            {--fromSurah=1 : Start from this surah}
                            {--toSurah=114 : End at this surah}';

    protected $description = 'Import all active tafaseer from their configured sources';

    public function handle(TafseerImportManager $importer): int
    {
        $update = (bool) $this->option('update');
        $fromSurah = (int) $this->option('fromSurah');
        $toSurah = (int) $this->option('toSurah');

        $tafaseer = Tafseer::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($tafaseer->isEmpty()) {
            $this->error('No active tafaseer found.');
            return self::FAILURE;
        }

        foreach ($tafaseer as $tafseer) {
            $this->newLine();
            $this->info("Importing: {$tafseer->title_urdu} ({$tafseer->slug})");

            $effectiveFromSurah = $fromSurah;
            $effectiveFromAyah = 1;

            if (! $update) {
                $resumePoint = $importer->findResumePoint($tafseer->slug, $fromSurah, $toSurah);

                if (($resumePoint['complete'] ?? false) === true) {
                    $this->line("Already complete in selected range. Skipping {$tafseer->slug}.");
                    continue;
                }

                $effectiveFromSurah = (int) ($resumePoint['surah'] ?? $fromSurah);
                $effectiveFromAyah = (int) ($resumePoint['ayah'] ?? 1);
            }

            $this->line("Starting from Surah {$effectiveFromSurah}, Ayah {$effectiveFromAyah}");

            $stats = $importer->import(
                $tafseer->slug,
                $update,
                $effectiveFromSurah,
                $toSurah,
                function (string $stage, array $payload): void {
                    if ($stage === 'fetching') {
                        $this->line("Fetching {$payload['tafseer']} => Surah {$payload['surah']}, Ayah {$payload['ayah']}");
                    }

                    if ($stage === 'failed') {
                        $this->warn("Failed {$payload['tafseer']} => Surah {$payload['surah']}, Ayah {$payload['ayah']}");
                    }
                },
                $effectiveFromAyah
            );

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Processed', $stats['processed']],
                    ['Inserted', $stats['inserted']],
                    ['Updated', $stats['updated']],
                    ['Skipped', $stats['skipped']],
                    ['Failed', $stats['failed']],
                ]
            );
        }

        $this->info('All active tafaseer import completed.');

        return self::SUCCESS;
    }
}
