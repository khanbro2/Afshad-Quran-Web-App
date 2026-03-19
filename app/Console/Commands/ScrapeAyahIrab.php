<?php

namespace App\Console\Commands;

use App\Support\AyahTranslationImporter;
use App\Support\SurahQuranIrabScraper;
use Illuminate\Console\Command;
use Throwable;

class ScrapeAyahIrab extends Command
{
    protected $signature = 'app:scrape-ayah-irab
        {--from-page=1 : First mushaf page to scrape}
        {--to-page=604 : Last mushaf page to scrape}';

    protected $description = 'Scrape ayah-level Arabic i\'rab from SurahQuran page-by-page and import into ayahs.irab_arabic.';

    public function handle(): int
    {
        $fromPage = max(1, (int) $this->option('from-page'));
        $toPage = max($fromPage, (int) $this->option('to-page'));

        try {
            $verses = SurahQuranIrabScraper::scrapePages(
                $fromPage,
                $toPage,
                function (int $page, int $pageCount, int $totalCount): void {
                    $this->line("Scraped page {$page}. Ayahs found: {$pageCount}. Running total: {$totalCount}.");
                }
            );
        } catch (Throwable $throwable) {
            $this->error("Failed to scrape i'rab: {$throwable->getMessage()}");

            return self::FAILURE;
        }

        $stats = AyahTranslationImporter::importIntoColumn($verses, 'irab_arabic');

        $this->info("Ayah i'rab scrape/import complete.");
        $this->line("Parsed verses: {$stats['parsed']}");
        $this->line("Updated ayahs: {$stats['updated']}");
        $this->line("Unchanged ayahs: {$stats['unchanged']}");
        $this->line("Missing surahs: {$stats['missing_surah']}");
        $this->line("Missing ayahs: {$stats['missing_ayah']}");

        return self::SUCCESS;
    }
}
