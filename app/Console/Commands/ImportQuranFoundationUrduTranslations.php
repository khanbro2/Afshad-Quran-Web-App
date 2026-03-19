<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Support\QuranFoundationClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportQuranFoundationUrduTranslations extends Command
{
    protected $signature = 'app:import-quran-foundation-urdu
        {--resume : Skip rows that already have Urdu translation values}
        {--surah= : Limit import to one surah number}
        {--ayah= : Limit import to one ayah number (requires --surah)}
        {--language=ur : Word translation language code}
        {--translation-resource= : Quran Foundation translation resource ID for full ayah Urdu}
        {--skip-ayah : Skip full ayah Urdu translation import}
        {--skip-words : Skip word-by-word Urdu translation import}';

    protected $description = 'Import Quran Foundation Urdu translations for ayahs and aligned words.';

    public function handle(QuranFoundationClient $client): int
    {
        $resume = (bool) $this->option('resume');
        $surahFilter = $this->option('surah') ? (int) $this->option('surah') : null;
        $ayahFilter = $this->option('ayah') ? (int) $this->option('ayah') : null;
        $language = (string) $this->option('language');
        $skipAyah = (bool) $this->option('skip-ayah');
        $skipWords = (bool) $this->option('skip-words');
        $translationResource = $this->option('translation-resource');
        $translationResourceId = $translationResource !== null && $translationResource !== ''
            ? (int) $translationResource
            : (config('services.quran_foundation.urdu_translation_resource_id') ?: null);

        if ($ayahFilter !== null && $surahFilter === null) {
            $this->error('The --ayah option requires --surah.');
            return self::FAILURE;
        }

        if ($skipAyah && $skipWords) {
            $this->error('At least one of ayah or words import must remain enabled.');
            return self::FAILURE;
        }

        if (! $skipAyah && ! $translationResourceId) {
            $this->warn('Full ayah Urdu import is enabled but no translation resource ID is configured. Ayah Urdu will be skipped.');
            $skipAyah = true;
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
            'ayahs_updated' => 0,
            'ayahs_skipped' => 0,
            'words_updated' => 0,
            'words_skipped' => 0,
        ];

        foreach ($ayahs as $ayah) {
            $surahNumber = (int) $ayah->surah->number;
            $ayahNumber = (int) $ayah->ayah_number;

            if ($resume) {
                $ayahDone = $skipAyah || ! empty($ayah->urdu_translation);
                $wordsDone = $skipWords || $ayah->words->every(fn ($word) => ! empty($word->translation_urdu));

                if ($ayahDone && $wordsDone) {
                    $stats['ayahs_processed']++;
                    $stats['ayahs_skipped']++;
                    $stats['words_skipped'] += $ayah->words->count();
                    continue;
                }
            }

            $verseKey = "{$surahNumber}:{$ayahNumber}";

            try {
                $verse = $client->verseByKey($verseKey, $language, ! $skipWords, $skipAyah ? null : $translationResourceId);
            } catch (RuntimeException $exception) {
                $this->error($exception->getMessage());
                return self::FAILURE;
            } catch (\Throwable $exception) {
                $this->warn("Failed to fetch {$verseKey}: {$exception->getMessage()}");
                continue;
            }

            $ayahTranslation = $skipAyah ? null : $this->extractAyahTranslation($verse);
            $wordTranslations = $skipWords ? [] : $this->extractWordTranslations($verse);

            DB::transaction(function () use ($ayah, $resume, $skipAyah, $skipWords, $ayahTranslation, $wordTranslations, &$stats) {
                if (! $skipAyah && $ayahTranslation !== null && (! $resume || empty($ayah->urdu_translation))) {
                    if ($ayah->urdu_translation !== $ayahTranslation) {
                        $ayah->forceFill(['urdu_translation' => $ayahTranslation])->save();
                        $stats['ayahs_updated']++;
                    } else {
                        $stats['ayahs_skipped']++;
                    }
                } elseif (! $skipAyah) {
                    $stats['ayahs_skipped']++;
                }

                if ($skipWords) {
                    return;
                }

                foreach ($ayah->words as $word) {
                    $translation = $wordTranslations[$word->position] ?? null;

                    if ($translation === null || ($resume && ! empty($word->translation_urdu))) {
                        $stats['words_skipped']++;
                        continue;
                    }

                    if ($word->translation_urdu === $translation) {
                        $stats['words_skipped']++;
                        continue;
                    }

                    $word->forceFill(['translation_urdu' => $translation])->save();
                    $stats['words_updated']++;
                }
            });

            $stats['ayahs_processed']++;

            if ($stats['ayahs_processed'] % 100 === 0) {
                $this->info("Processed {$stats['ayahs_processed']} ayahs. Ayahs updated: {$stats['ayahs_updated']}. Words updated: {$stats['words_updated']}.");
            }
        }

        $this->info('Quran Foundation Urdu import complete.');
        $this->info('Ayahs processed: '.$stats['ayahs_processed']);
        $this->info('Ayahs updated: '.$stats['ayahs_updated']);
        $this->info('Ayahs skipped: '.$stats['ayahs_skipped']);
        $this->info('Words updated: '.$stats['words_updated']);
        $this->info('Words skipped: '.$stats['words_skipped']);

        return self::SUCCESS;
    }

    protected function extractAyahTranslation(array $verse): ?string
    {
        $translations = $verse['translations'] ?? null;

        if (! is_array($translations) || $translations === []) {
            return null;
        }

        $first = $translations[0] ?? null;

        if (! is_array($first)) {
            return null;
        }

        $text = $first['text'] ?? null;

        return is_string($text) && $text !== '' ? trim(strip_tags($text)) : null;
    }

    /**
     * @return array<int, string>
     */
    protected function extractWordTranslations(array $verse): array
    {
        $words = $verse['words'] ?? null;

        if (! is_array($words)) {
            return [];
        }

        $translations = [];

        foreach ($words as $index => $word) {
            if (! is_array($word)) {
                continue;
            }

            $position = isset($word['position']) ? (int) $word['position'] : $index + 1;
            $translationNode = $word['translation'] ?? null;

            $text = null;

            if (is_array($translationNode)) {
                $text = $translationNode['text'] ?? null;
            } elseif (is_string($translationNode)) {
                $text = $translationNode;
            }

            if (! is_string($text) || trim($text) === '') {
                continue;
            }

            $translations[$position] = trim(strip_tags($text));
        }

        ksort($translations);

        return $translations;
    }
}
