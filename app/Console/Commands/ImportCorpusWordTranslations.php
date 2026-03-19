<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCorpusWordTranslations extends Command
{
    protected $signature = 'app:import-corpus-word-translations
        {--resume : Skip words that already have translation_basic}
        {--surah= : Limit import to one surah number}
        {--ayah= : Limit import to one ayah number (requires --surah)}
        {--refresh-transliteration : Refresh transliteration from corpus even when translation_basic already exists}';

    protected $description = 'Import word-by-word English translations from Quranic Arabic Corpus into translation_basic.';

    public function handle(): int
    {
        $resume = (bool) $this->option('resume');
        $refreshTransliteration = (bool) $this->option('refresh-transliteration');
        $surahFilter = $this->option('surah') ? (int) $this->option('surah') : null;
        $ayahFilter = $this->option('ayah') ? (int) $this->option('ayah') : null;

        if ($ayahFilter !== null && $surahFilter === null) {
            $this->error('The --ayah option requires --surah.');
            return self::FAILURE;
        }

        $ayahs = Ayah::query()
            ->with([
                'surah:id,number',
                'words' => fn ($query) => $query->orderBy('position'),
            ])
            ->when($surahFilter !== null, fn ($query) => $query->whereHas('surah', fn ($surah) => $surah->where('number', $surahFilter)))
            ->when($ayahFilter !== null, fn ($query) => $query->where('ayah_number', $ayahFilter))
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->get();

        $stats = [
            'ayahs_processed' => 0,
            'translations_updated' => 0,
            'translations_skipped' => 0,
        ];

        foreach ($ayahs as $ayah) {
            $surahNumber = (int) $ayah->surah->number;
            $ayahNumber = (int) $ayah->ayah_number;

            if ($resume && ! $refreshTransliteration && $ayah->words->every(fn ($word) => ! empty($word->translation_basic))) {
                $stats['translations_skipped'] += $ayah->words->count();
                $stats['ayahs_processed']++;
                continue;
            }

            $translations = $this->fetchVerseTranslations($surahNumber, $ayahNumber);

            if ($translations === []) {
                $this->warn("No translations parsed for {$surahNumber}:{$ayahNumber}");
                continue;
            }

            DB::transaction(function () use ($ayah, $translations, $resume, $refreshTransliteration, &$stats) {
                foreach ($ayah->words as $index => $word) {
                    $position = $index + 1;
                    $translation = $translations[$position]['translation'] ?? null;
                    $transliteration = $translations[$position]['transliteration'] ?? null;

                    if (($translation === null || $translation === '') && ($transliteration === null || $transliteration === '')) {
                        continue;
                    }

                    if ($resume && ! $refreshTransliteration && ! empty($word->translation_basic)) {
                        $stats['translations_skipped']++;
                        continue;
                    }

                    $updates = ['updated_at' => now()];
                    $didUpdate = false;

                    if ($translation !== null && $translation !== '' && (! $resume || empty($word->translation_basic) || $refreshTransliteration)) {
                        $updates['translation_basic'] = $translation;
                        $didUpdate = true;
                    }

                    if ($transliteration !== null && $transliteration !== '' && $word->transliteration !== $transliteration) {
                        $updates['transliteration'] = $transliteration;
                        $didUpdate = true;
                    }

                    if (! $didUpdate) {
                        $stats['translations_skipped']++;
                        continue;
                    }

                    DB::table('words')
                        ->where('id', $word->id)
                        ->update($updates);

                    $stats['translations_updated']++;
                }
            });

            $stats['ayahs_processed']++;

            if ($stats['ayahs_processed'] % 100 === 0) {
                $this->info("Processed {$stats['ayahs_processed']} ayahs, updated {$stats['translations_updated']} word translations.");
            }
        }

        $remaining = (int) DB::table('words')->whereNull('translation_basic')->count();

        $this->info("Done. Ayahs processed: {$stats['ayahs_processed']}. Updated: {$stats['translations_updated']}. Skipped: {$stats['translations_skipped']}. Remaining null translation_basic: {$remaining}.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{translation:string, transliteration:string}>
     */
    protected function fetchVerseTranslations(int $surahNumber, int $ayahNumber): array
    {
        $url = "https://corpus.quran.com/wordbyword.jsp?chapter={$surahNumber}&verse={$ayahNumber}";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'User-Agent: Mozilla/5.0',
                    'Accept-Language: en-US,en;q=0.9',
                ]),
                'timeout' => 25,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            return [];
        }

        $matches = [];
        preg_match_all(
            '/<tr><td><span class="location">\(' . $surahNumber . ':' . $ayahNumber . ':(\d+)\)<\/span><br\/>(?:<a [^>]+>|<span class="phonetic">)(.*?)<\/(?:a|span)><br\/>(.*?)<\/td>/su',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $translations = [];

        foreach ($matches as $match) {
            $position = (int) $match[1];
            $transliterationHtml = html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $transliteration = trim(preg_replace('/\s+/u', ' ', $transliterationHtml) ?? '');
            $translationHtml = html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $translation = trim(preg_replace('/\s+/u', ' ', $translationHtml) ?? '');
            $translations[$position] = [
                'translation' => $translation,
                'transliteration' => $transliteration,
            ];
        }

        ksort($translations);

        return $translations;
    }
}
