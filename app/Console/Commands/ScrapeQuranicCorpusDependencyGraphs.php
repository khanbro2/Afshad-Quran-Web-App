<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Support\QuranicCorpusDependencyGraphScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScrapeQuranicCorpusDependencyGraphs extends Command
{
    protected $signature = 'app:scrape-corpus-dependency-graphs
        {--surah= : Limit to one surah number}
        {--ayah= : Limit to one ayah number (requires --surah)}
        {--limit= : Limit number of ayahs to process}
        {--resume : Skip existing downloaded graph files}';

    protected $description = 'Download dependency graph images from Quranic Arabic Corpus treebank pages.';

    public function handle(): int
    {
        $ayahs = Ayah::query()
            ->select(['ayahs.id', 'ayahs.ayah_number', 'surahs.number as surah_number'])
            ->join('surahs', 'surahs.id', '=', 'ayahs.surah_id')
            ->when($this->option('surah'), fn ($query, $surah) => $query->where('surahs.number', (int) $surah))
            ->when($this->option('ayah'), fn ($query, $ayah) => $query->where('ayahs.ayah_number', (int) $ayah))
            ->orderBy('surahs.number')
            ->orderBy('ayahs.ayah_number');

        if ($this->option('limit')) {
            $ayahs->limit((int) $this->option('limit'));
        }

        $rows = $ayahs->get();

        if ($rows->isEmpty()) {
            $this->warn('No ayahs matched the requested scope.');
            return self::SUCCESS;
        }

        $processed = 0;
        $downloaded = 0;
        $missingGraph = 0;
        $failed = 0;
        $skipExisting = 0;

        foreach ($rows as $row) {

            // Quranic Corpus only has dependency graphs for Surah 1–8 and 59–114
if ($row->surah_number >= 9 && $row->surah_number <= 58) {
    $this->line("Skipping {$row->surah_number}:{$row->ayah_number} (graphs not available)");
    continue;
}

            $processed++;

            $this->line("Processing {$row->surah_number}:{$row->ayah_number}");

            $folder = "dependency-graphs/{$row->surah_number}";
            $basePath = "{$folder}/{$row->ayah_number}.png";

            $existingIndexedFiles = collect(Storage::files($folder))
                ->filter(fn ($file) => preg_match("/^{$row->ayah_number}-(\d+)\.png$/", basename($file)))
                ->values();

            if ($this->option('resume') && ($existingIndexedFiles->isNotEmpty() || Storage::exists($basePath))) {
                $skipExisting++;
                $this->line("Skipping existing {$row->surah_number}:{$row->ayah_number}");
                continue;
            }

            if (!$this->option('resume')) {
                if (Storage::exists($basePath)) {
                    Storage::delete($basePath);
                }

                foreach ($existingIndexedFiles as $file) {
                    Storage::delete($file);
                }
            }

            try {
                $images = QuranicCorpusDependencyGraphScraper::fetchGraphImages(
                    (int) $row->surah_number,
                    (int) $row->ayah_number
                );

                if (empty($images)) {
                    $missingGraph++;
                    $this->warn("No dependency graph found for {$row->surah_number}:{$row->ayah_number}");
                    continue;
                }

                $saved = 0;

                foreach ($images as $index => $imageData) {
                    if ($imageData === '' || !str_starts_with($imageData, "\x89PNG\r\n\x1a\n")) {
                        $this->warn("Skipped invalid PNG for {$row->surah_number}:{$row->ayah_number} variant " . ($index + 1));
                        continue;
                    }

                    $variantIndex = $index + 1;
                    $indexedPath = "{$folder}/{$row->ayah_number}-{$variantIndex}.png";

                    Storage::put($indexedPath, $imageData);

                    $saved++;
                    $this->line("Downloaded graph {$row->surah_number}:{$row->ayah_number} variant {$variantIndex} -> {$indexedPath}");
                }

                $downloaded += $saved;

                usleep(150000); // 150ms pause
            } catch (Throwable $throwable) {
                $failed++;
                $this->error("Failed {$row->surah_number}:{$row->ayah_number} - {$throwable->getMessage()}");
            }
        }

        $this->info('Dependency graph download complete.');
        $this->line("Processed ayahs: {$processed}");
        $this->line("Downloaded graphs: {$downloaded}");
        $this->line("Missing graph: {$missingGraph}");
        $this->line("Failed ayahs: {$failed}");
        $this->line("Skipped existing (resume): {$skipExisting}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}