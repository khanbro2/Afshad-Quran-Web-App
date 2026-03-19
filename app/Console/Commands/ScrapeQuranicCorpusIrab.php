<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Models\Surah;
use App\Support\QuranicCorpusIrabScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScrapeQuranicCorpusIrab extends Command
{
    protected $signature = 'app:scrape-corpus-irab
        {--surah= : Limit to one surah number}
        {--ayah= : Limit to one ayah number (requires --surah)}
        {--limit= : Limit number of ayahs to scrape}
        {--resume : Skip ayahs that already have irab_arabic}';

    protected $description = 'Scrape ayah-level Arabic i\'rab from Quranic Arabic Corpus grammar pages.';

    public function handle(): int
    {
        $ayahs = Ayah::query()
            ->select(['ayahs.id', 'ayahs.ayah_number', 'ayahs.irab_arabic', 'surahs.number as surah_number'])
            ->join('surahs', 'surahs.id', '=', 'ayahs.surah_id')
            ->when($this->option('surah'), fn ($query, $surah) => $query->where('surahs.number', (int) $surah))
            ->when($this->option('ayah'), fn ($query, $ayah) => $query->where('ayahs.ayah_number', (int) $ayah))
            ->when($this->option('resume'), fn ($query) => $query->whereNull('ayahs.irab_arabic'))
            ->orderBy('surahs.number')
            ->orderBy('ayahs.ayah_number');

        if ($this->option('limit')) {
            $ayahs->limit((int) $this->option('limit'));
        }

        $rows = $ayahs->get();

        if ($rows->isEmpty()) {
            $this->warn('No ayahs matched the requested scrape scope.');

            return self::SUCCESS;
        }

        $updated = 0;
        $failed = 0;
        $noIrab = 0;
        $processed = 0;
        $noIrabRows = [];
        $failedRows = [];

        foreach ($rows as $row) {
            $processed++;

            try {
                $irab = QuranicCorpusIrabScraper::fetchAyahIrab((int) $row->surah_number, (int) $row->ayah_number);

                if ($irab !== null) {
                    DB::table('ayahs')
                        ->where('id', $row->id)
                        ->update([
                            'irab_arabic' => $irab,
                            'updated_at' => now(),
                        ]);

                    $updated++;
                    $this->line("Scraped {$row->surah_number}:{$row->ayah_number}");
                } else {
                    $noIrab++;
                    $noIrabRows[] = "{$row->surah_number}:{$row->ayah_number}";
                    $this->warn("No i'rab found for {$row->surah_number}:{$row->ayah_number}");
                }
            } catch (Throwable $throwable) {
                $failed++;
                $failedRows[] = "{$row->surah_number}:{$row->ayah_number}";
                $this->error("Failed {$row->surah_number}:{$row->ayah_number} - {$throwable->getMessage()}");
            }
        }

        $this->info('Quranic Corpus i\'rab scrape complete.');
        $this->line("Processed ayahs: {$processed}");
        $this->line("Updated ayahs: {$updated}");
        $this->line("No i'rab found: {$noIrab}");
        $this->line("Failed ayahs: {$failed}");

        if (!empty($noIrabRows)) {
            $this->line('No i\'rab found list: ' . implode(', ', $noIrabRows));
        }

        if (!empty($failedRows)) {
            $this->line('Failed ayah list: ' . implode(', ', $failedRows));
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
