<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Models\Lemma;
use App\Models\Root;
use App\Models\Surah;
use App\Models\Word;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuranMorphology extends Command
{
    protected const BATCH_SIZE = 1000;
    protected const PROGRESS_EVERY = 5000;

    /**
     * @var array<int, int>
     */
    protected array $surahCache = [];

    /**
     * @var array<string, int>
     */
    protected array $ayahCache = [];

    /**
     * @var array<string, int>
     */
    protected array $rootCache = [];

    /**
     * @var array<string, array{id:int, root_id:int|null}>
     */
    protected array $lemmaCache = [];

    /**
     * @var array<string, array{id:int, root_id:int|null, lemma_id:int|null, segment_count:int|null}>
     */
    protected array $wordCache = [];

    /**
     * @var array<string, array{word_id:int, root_id:int|null, lemma_id:int|null}>
     */
    protected array $existingSegmentCache = [];

    protected $signature = 'app:import-quran-morphology
        {--file= : Full path to the morphology file}
        {--resume : Skip already imported morphology segments aggressively}
        {--verify : Only verify counts, or verify after import when combined with --resume}';

    protected $description = 'Import the Quranic Corpus morphology file into words and morphologies tables.';

    public function handle()
    {
        $file = $this->option('file')
            ? base_path($this->option('file'))
            : storage_path('datasets/quran/quranic-corpus-morphology-0.4.txt');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $resumeMode = (bool) $this->option('resume');
        $verifyOnly = (bool) $this->option('verify') && ! $resumeMode;

        $this->warmCaches($resumeMode);

        if ($verifyOnly) {
            $this->printVerificationSummary($file);
            return 0;
        }

        $this->info('Parsing morphology file: ' . $file);
        if ($resumeMode) {
            $this->info('Resume mode enabled: existing segments with linked words/roots/lemmas will be skipped.');
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error('Unable to open the file.');
            return 1;
        }

        $stats = [
            'file_lines' => 0,
            'valid_rows' => 0,
            'skipped_existing' => 0,
            'created_words' => 0,
            'inserted_morphologies' => 0,
            'updated_morphologies' => 0,
            'created_roots' => 0,
            'created_lemmas' => 0,
        ];

        $batch = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $stats['file_lines']++;
                $batch[] = $line;

                if (count($batch) >= self::BATCH_SIZE) {
                    $this->processBatch($batch, $resumeMode, $stats);
                    $batch = [];
                    $this->printProgressIfNeeded($stats);
                }
            }

            if ($batch !== []) {
                $this->processBatch($batch, $resumeMode, $stats);
                $this->printProgressIfNeeded($stats, true);
            }

            fclose($handle);

            $this->info(
                'Import complete. '
                . "File lines: {$stats['file_lines']}. "
                . "Valid morphology rows: {$stats['valid_rows']}. "
                . "Skipped existing: {$stats['skipped_existing']}. "
                . "New words: {$stats['created_words']}. "
                . "Morphologies inserted: {$stats['inserted_morphologies']}. "
                . "Morphologies updated: {$stats['updated_morphologies']}. "
                . "Roots created: {$stats['created_roots']}. "
                . "Lemmas created: {$stats['created_lemmas']}."
            );

            $this->printVerificationSummary($file);

            return 0;
        } catch (\Throwable $e) {
            fclose($handle);

            $this->error('Import failed at file line ' . $stats['file_lines'] . ': ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * @param array<int, string> $batch
     * @param array<string, int> $stats
     */
    protected function processBatch(array $batch, bool $resumeMode, array &$stats): void
    {
        DB::transaction(function () use ($batch, $resumeMode, &$stats) {
            foreach ($batch as $line) {
                $record = $this->parseLine($line);

                if ($record === null) {
                    continue;
                }

                $stats['valid_rows']++;

                $segmentKey = $this->buildSegmentKey(
                    $record['surah_number'],
                    $record['ayah_number'],
                    $record['word_position'],
                    $record['segment_number']
                );

                if ($resumeMode && $this->canSkipExistingSegment($segmentKey, $record['root'], $record['lemma'])) {
                    $stats['skipped_existing']++;
                    continue;
                }

                $surahId = $this->resolveSurahId($record['surah_number']);
                $ayahId = $this->resolveAyahId($surahId, $record['ayah_number']);

                $rootId = null;
                if ($record['root'] !== null) {
                    [$rootId, $rootCreated] = $this->resolveRootId($record['root']);
                    if ($rootCreated) {
                        $stats['created_roots']++;
                    }
                }

                $lemmaId = null;
                if ($record['lemma'] !== null) {
                    [$lemmaId, $lemmaCreated] = $this->resolveLemmaId($record['lemma'], $rootId);
                    if ($lemmaCreated) {
                        $stats['created_lemmas']++;
                    }
                }

                [$wordId, $wordCreated] = $this->resolveWordId(
                    $ayahId,
                    $record['surah_number'],
                    $record['ayah_number'],
                    $record['word_position'],
                    $record['form'],
                    $rootId,
                    $lemmaId,
                    $record['segment_number']
                );

                if ($wordCreated) {
                    $stats['created_words']++;
                }

                $segmentExists = isset($this->existingSegmentCache[$segmentKey]);

                if ($resumeMode && $segmentExists) {
                    $this->existingSegmentCache[$segmentKey] = [
                        'word_id' => $wordId,
                        'root_id' => $rootId,
                        'lemma_id' => $lemmaId,
                    ];
                    continue;
                }

                $payload = [
                    'word_id' => $wordId,
                    'segment_number' => $record['segment_number'],
                    'pos_tag' => $record['pos_tag'],
                    'raw_features' => $record['raw_features'],
                    'lemma' => $record['lemma'],
                    'root' => $record['root'],
                    'person' => $record['person'],
                    'gender' => $record['gender'],
                    'number_type' => $record['number_type'],
                    'case_type' => $record['case_type'],
                    'mood' => $record['mood'],
                    'tense' => $record['tense'],
                    'voice' => $record['voice'],
                    'state' => $record['state'],
                    'derived_form' => $record['derived_form'],
                    'grammatical_features' => null,
                    'extra_json' => $record['extra_json'],
                    'updated_at' => now(),
                ];

                if ($resumeMode) {
                    DB::table('morphologies')->insert($payload + [
                        'created_at' => now(),
                    ]);
                    $stats['inserted_morphologies']++;
                } else {
                    DB::table('morphologies')->updateOrInsert(
                        [
                            'word_id' => $wordId,
                            'segment_number' => $record['segment_number'],
                        ],
                        $payload + [
                            'created_at' => now(),
                        ]
                    );

                    if ($segmentExists) {
                        $stats['updated_morphologies']++;
                    } else {
                        $stats['inserted_morphologies']++;
                    }
                }

                $this->existingSegmentCache[$segmentKey] = [
                    'word_id' => $wordId,
                    'root_id' => $rootId,
                    'lemma_id' => $lemmaId,
                ];
            }
        });
    }

    protected function printProgressIfNeeded(array $stats, bool $force = false): void
    {
        if (! $force && $stats['file_lines'] % self::PROGRESS_EVERY !== 0) {
            return;
        }

        $this->info(
            'Progress: '
            . "{$stats['file_lines']} file lines read, "
            . "{$stats['valid_rows']} valid rows, "
            . "{$stats['skipped_existing']} skipped, "
            . "{$stats['inserted_morphologies']} morphology inserts, "
            . "{$stats['updated_morphologies']} morphology updates."
        );
    }

    /**
     * @return array{
     *     surah_number:int,
     *     ayah_number:int,
     *     word_position:int,
     *     segment_number:int,
     *     form:string,
     *     pos_tag:string,
     *     raw_features:string|null,
     *     root:string|null,
     *     lemma:string|null,
     *     person:string|null,
     *     gender:string|null,
     *     number_type:string|null,
     *     case_type:string|null,
     *     mood:string|null,
     *     tense:string|null,
     *     voice:string|null,
     *     state:string|null,
     *     derived_form:string|null,
     *     extra_json:string|null
     * }|null
     */
    protected function parseLine(string $line): ?array
    {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return null;
        }

        $parts = preg_split('/\t+/', $line);
        if (! is_array($parts) || count($parts) < 3) {
            return null;
        }

        [$location, $form, $posTag] = $parts;
        $features = $parts[3] ?? null;

        if (! preg_match('/^\((\d+):(\d+):(\d+):(\d+)\)$/', $location, $matches)) {
            return null;
        }

        $parsed = $this->parseFeatures($features, (int) $matches[4]);

        return [
            'surah_number' => (int) $matches[1],
            'ayah_number' => (int) $matches[2],
            'word_position' => (int) $matches[3],
            'segment_number' => (int) $parsed['segment_number'],
            'form' => $form,
            'pos_tag' => $posTag,
            'raw_features' => $features,
            'root' => $parsed['root'],
            'lemma' => $parsed['lemma'],
            'person' => $parsed['person'],
            'gender' => $parsed['gender'],
            'number_type' => $parsed['number_type'],
            'case_type' => $parsed['case_type'],
            'mood' => $parsed['mood'],
            'tense' => $parsed['tense'],
            'voice' => $parsed['voice'],
            'state' => $parsed['state'],
            'derived_form' => $parsed['derived_form'],
            'extra_json' => $parsed['extra'] ? json_encode($parsed['extra'], JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    /**
     * @return array{
     *     root:string|null,
     *     lemma:string|null,
     *     person:string|null,
     *     gender:string|null,
     *     number_type:string|null,
     *     case_type:string|null,
     *     mood:string|null,
     *     tense:string|null,
     *     voice:string|null,
     *     state:string|null,
     *     derived_form:string|null,
     *     segment_number:int,
     *     extra:array<int, array<string, string>|string>
     * }
     */
    protected function parseFeatures(?string $features, int $defaultSegmentNumber = 1): array
    {
        $parsed = [
            'root' => null,
            'lemma' => null,
            'person' => null,
            'gender' => null,
            'number_type' => null,
            'case_type' => null,
            'mood' => null,
            'tense' => null,
            'voice' => null,
            'state' => null,
            'derived_form' => null,
            'segment_number' => $defaultSegmentNumber,
            'extra' => [],
        ];

        if ($features === null || $features === '') {
            return $parsed;
        }

        foreach (explode('|', $features) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (str_contains($part, ':')) {
                [$key, $value] = explode(':', $part, 2);
                $key = strtoupper(trim($key));
                $value = trim(trim($value), "{}\n\r\t ");

                switch ($key) {
                    case 'ROOT':
                        $parsed['root'] = $value;
                        break;
                    case 'LEM':
                    case 'LEMMA':
                        $parsed['lemma'] = $value;
                        break;
                    case 'SEG':
                    case 'SEGMENT':
                        $parsed['segment_number'] = (int) $value;
                        break;
                    default:
                        $parsed['extra'][] = [$key => $value];
                        break;
                }

                continue;
            }

            $upper = strtoupper($part);

            if (preg_match('/^[123](MS|FS|MP|FP|P)$/', $upper)) {
                $parsed['person'] = $upper;
                continue;
            }

            if (in_array($upper, ['M', 'F'], true)) {
                $parsed['gender'] = $upper;
                continue;
            }

            if (in_array($upper, ['S', 'P', 'D', 'DUAL'], true)) {
                $parsed['number_type'] = $upper;
                continue;
            }

            if (in_array($upper, ['NOM', 'ACC', 'GEN', 'VOC'], true)) {
                $parsed['case_type'] = $upper;
                continue;
            }

            if (in_array($upper, ['IMPF', 'PERF', 'IMPV', 'JUSS', 'IND', 'SUBJ'], true)) {
                $parsed['mood'] = $upper;
                $parsed['tense'] = match ($upper) {
                    'PERF' => 'perfect',
                    'IMPF' => 'imperfect',
                    'IMPV' => 'imperative',
                    default => $parsed['tense'],
                };
                continue;
            }

            if (in_array($upper, ['ACT', 'PASS'], true)) {
                $parsed['voice'] = $upper;
                continue;
            }

            if (in_array($upper, ['CONST', 'CONSTRUCT', 'ABSOLUTE'], true)) {
                $parsed['state'] = $upper;
                continue;
            }

            if (str_starts_with($upper, 'D')) {
                $parsed['derived_form'] = $upper;
                continue;
            }

            $parsed['extra'][] = $upper;
        }

        return $parsed;
    }

    protected function warmCaches(bool $resumeMode): void
    {
        $this->surahCache = Surah::query()
            ->pluck('id', 'number')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->ayahCache = Ayah::query()
            ->get(['id', 'surah_id', 'ayah_number'])
            ->mapWithKeys(fn (Ayah $ayah) => [$ayah->surah_id . ':' . $ayah->ayah_number => (int) $ayah->id])
            ->all();

        $this->rootCache = Root::query()
            ->get(['id', 'root_arabic'])
            ->mapWithKeys(fn (Root $root) => [$this->normalizeLookupKey($root->root_arabic) => (int) $root->id])
            ->all();

        $this->lemmaCache = Lemma::query()
            ->get(['id', 'lemma_arabic', 'root_id'])
            ->mapWithKeys(fn (Lemma $lemma) => [
                $this->normalizeLookupKey($lemma->lemma_arabic) => [
                    'id' => (int) $lemma->id,
                    'root_id' => $lemma->root_id ? (int) $lemma->root_id : null,
                ],
            ])
            ->all();

        $this->wordCache = Word::query()
            ->get(['id', 'ayah_id', 'position', 'root_id', 'lemma_id', 'segment_count'])
            ->mapWithKeys(fn (Word $word) => [
                $word->ayah_id . ':' . $word->position => [
                    'id' => (int) $word->id,
                    'root_id' => $word->root_id ? (int) $word->root_id : null,
                    'lemma_id' => $word->lemma_id ? (int) $word->lemma_id : null,
                    'segment_count' => $word->segment_count ? (int) $word->segment_count : null,
                ],
            ])
            ->all();

        if (! $resumeMode) {
            $this->warmExistingSegments();
            return;
        }

        $this->warmExistingSegments();
    }

    protected function warmExistingSegments(): void
    {
        $this->existingSegmentCache = [];

        DB::table('morphologies as m')
            ->join('words as w', 'w.id', '=', 'm.word_id')
            ->select(
                'w.surah_number',
                'w.ayah_number',
                'w.position',
                'm.segment_number',
                'm.word_id',
                'w.root_id',
                'w.lemma_id'
            )
            ->orderBy('m.id')
            ->chunk(5000, function ($rows) {
                foreach ($rows as $row) {
                    $segmentKey = $this->buildSegmentKey(
                        (int) $row->surah_number,
                        (int) $row->ayah_number,
                        (int) $row->position,
                        (int) $row->segment_number
                    );

                    $this->existingSegmentCache[$segmentKey] = [
                        'word_id' => (int) $row->word_id,
                        'root_id' => $row->root_id ? (int) $row->root_id : null,
                        'lemma_id' => $row->lemma_id ? (int) $row->lemma_id : null,
                    ];
                }
            });
    }

    protected function canSkipExistingSegment(string $segmentKey, ?string $root, ?string $lemma): bool
    {
        if (! isset($this->existingSegmentCache[$segmentKey])) {
            return false;
        }

        $existing = $this->existingSegmentCache[$segmentKey];

        if ($root !== null && $existing['root_id'] === null) {
            return false;
        }

        if ($lemma !== null && $existing['lemma_id'] === null) {
            return false;
        }

        return true;
    }

    protected function resolveSurahId(int $surahNumber): int
    {
        if (isset($this->surahCache[$surahNumber])) {
            return $this->surahCache[$surahNumber];
        }

        $surah = Surah::query()->create([
            'number' => $surahNumber,
            'arabic_name' => null,
            'english_name' => null,
            'transliterated_name' => null,
            'revelation_type' => null,
            'total_ayahs' => 0,
        ]);

        return $this->surahCache[$surahNumber] = (int) $surah->id;
    }

    protected function resolveAyahId(int $surahId, int $ayahNumber): int
    {
        $key = $surahId . ':' . $ayahNumber;

        if (isset($this->ayahCache[$key])) {
            return $this->ayahCache[$key];
        }

        $ayah = Ayah::query()->create([
            'surah_id' => $surahId,
            'ayah_number' => $ayahNumber,
            'full_arabic_text' => null,
            'simple_text' => null,
            'uthmani_text' => null,
        ]);

        return $this->ayahCache[$key] = (int) $ayah->id;
    }

    /**
     * @return array{0:int,1:bool}
     */
    protected function resolveRootId(string $rootArabic): array
    {
        $cacheKey = $this->normalizeLookupKey($rootArabic);

        if (isset($this->rootCache[$cacheKey])) {
            return [$this->rootCache[$cacheKey], false];
        }

        $root = Root::query()->create([
            'root_arabic' => $rootArabic,
            'root_letters' => null,
            'description' => null,
        ]);

        $this->rootCache[$cacheKey] = (int) $root->id;

        return [(int) $root->id, true];
    }

    /**
     * @return array{0:int,1:bool}
     */
    protected function resolveLemmaId(string $lemmaArabic, ?int $rootId): array
    {
        $cacheKey = $this->normalizeLookupKey($lemmaArabic);

        if (isset($this->lemmaCache[$cacheKey])) {
            $cached = $this->lemmaCache[$cacheKey];

            if ($cached['root_id'] === null && $rootId !== null) {
                Lemma::query()->whereKey($cached['id'])->update(['root_id' => $rootId]);
                $this->lemmaCache[$cacheKey]['root_id'] = $rootId;
            }

            return [$cached['id'], false];
        }

        $lemma = Lemma::query()->create([
            'lemma_arabic' => $lemmaArabic,
            'root_id' => $rootId,
        ]);

        $this->lemmaCache[$cacheKey] = [
            'id' => (int) $lemma->id,
            'root_id' => $rootId,
        ];

        return [(int) $lemma->id, true];
    }

    /**
     * @return array{0:int,1:bool}
     */
    protected function resolveWordId(
        int $ayahId,
        int $surahNumber,
        int $ayahNumber,
        int $wordPosition,
        string $form,
        ?int $rootId,
        ?int $lemmaId,
        int $segmentNumber
    ): array {
        $key = $ayahId . ':' . $wordPosition;

        if (isset($this->wordCache[$key])) {
            $cached = $this->wordCache[$key];
            $updates = [];

            if ($cached['root_id'] === null && $rootId !== null) {
                $updates['root_id'] = $rootId;
                $cached['root_id'] = $rootId;
            }

            if ($cached['lemma_id'] === null && $lemmaId !== null) {
                $updates['lemma_id'] = $lemmaId;
                $cached['lemma_id'] = $lemmaId;
            }

            if ($cached['segment_count'] === null || $segmentNumber > $cached['segment_count']) {
                $updates['segment_count'] = $segmentNumber;
                $cached['segment_count'] = $segmentNumber;
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('words')->where('id', $cached['id'])->update($updates);
                $this->wordCache[$key] = $cached;
            }

            return [$cached['id'], false];
        }

        $word = Word::query()->create([
            'ayah_id' => $ayahId,
            'surah_number' => $surahNumber,
            'ayah_number' => $ayahNumber,
            'position' => $wordPosition,
            'segment_count' => $segmentNumber,
            'form' => $form,
            'arabic_text' => null,
            'normalized_text' => null,
            'translation_basic' => null,
            'transliteration' => null,
            'root_id' => $rootId,
            'lemma_id' => $lemmaId,
        ]);

        $this->wordCache[$key] = [
            'id' => (int) $word->id,
            'root_id' => $rootId,
            'lemma_id' => $lemmaId,
            'segment_count' => $segmentNumber,
        ];

        return [(int) $word->id, true];
    }

    protected function printVerificationSummary(string $file): void
    {
        $counts = $this->getDatabaseCounts();
        $fileMorphologyRows = $this->countMorphologyRowsInFile($file);
        $importedMorphologyRows = $counts['morphologies'];
        $isComplete = $fileMorphologyRows === $importedMorphologyRows;

        $this->newLine();
        $this->info('Final MySQL counts:');
        $this->line("surahs: {$counts['surahs']}");
        $this->line("ayahs: {$counts['ayahs']}");
        $this->line("words: {$counts['words']}");
        $this->line("morphologies: {$counts['morphologies']}");
        $this->line("roots: {$counts['roots']}");
        $this->line("lemmas: {$counts['lemmas']}");
        $this->newLine();
        $this->line("file morphology rows: {$fileMorphologyRows}");
        $this->line("imported morphology rows: {$importedMorphologyRows}");
        $this->line('import complete: ' . ($isComplete ? 'YES' : 'NO'));
    }

    /**
     * @return array{surahs:int, ayahs:int, words:int, morphologies:int, roots:int, lemmas:int}
     */
    protected function getDatabaseCounts(): array
    {
        return [
            'surahs' => (int) DB::table('surahs')->count(),
            'ayahs' => (int) DB::table('ayahs')->count(),
            'words' => (int) DB::table('words')->count(),
            'morphologies' => (int) DB::table('morphologies')->count(),
            'roots' => (int) DB::table('roots')->count(),
            'lemmas' => (int) DB::table('lemmas')->count(),
        ];
    }

    protected function countMorphologyRowsInFile(string $file): int
    {
        $count = 0;
        $handle = fopen($file, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open the morphology file for verification.');
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if ($this->parseLine($line) !== null) {
                    $count++;
                }
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    protected function buildSegmentKey(int $surahNumber, int $ayahNumber, int $wordPosition, int $segmentNumber): string
    {
        return "{$surahNumber}:{$ayahNumber}:{$wordPosition}:{$segmentNumber}";
    }

    protected function normalizeLookupKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
