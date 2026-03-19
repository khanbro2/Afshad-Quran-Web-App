<?php

namespace App\Services\TafseerImport;

use App\Models\Tafseer;
use RuntimeException;

class TafseerImportManager
{
    public function __construct(
        protected EquranLibraryTafseerImporter $equranImporter,
        protected LocalDatasetTafseerImporter $localDatasetImporter,
    ) {
    }

    public function import(
        string $tafseerSlug,
        bool $update = false,
        ?int $fromSurah = null,
        ?int $toSurah = null,
        ?callable $progress = null,
        ?int $fromAyah = null
    ): array {
        return $this->resolveImporter($tafseerSlug)->import(
            $tafseerSlug,
            $update,
            $fromSurah,
            $toSurah,
            $progress,
            $fromAyah
        );
    }

    public function findResumePoint(string $tafseerSlug, int $fromSurah = 1, int $toSurah = 114): ?array
    {
        return $this->resolveImporter($tafseerSlug)->findResumePoint($tafseerSlug, $fromSurah, $toSurah);
    }

    protected function resolveImporter(string $tafseerSlug): EquranLibraryTafseerImporter|LocalDatasetTafseerImporter
    {
        $tafseer = Tafseer::query()->where('slug', $tafseerSlug)->first();

        if (! $tafseer) {
            throw new RuntimeException("Tafseer not found for slug: {$tafseerSlug}");
        }

        if (config()->has('tafseer_import.local_datasets.' . $tafseerSlug)) {
            return $this->localDatasetImporter;
        }

        return $this->equranImporter;
    }
}
