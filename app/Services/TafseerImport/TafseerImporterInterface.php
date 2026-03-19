<?php

namespace App\Services\TafseerImport;

interface TafseerImporterInterface
{
    public function import(string $slug, bool $updateExisting = false, ?int $fromSurah = null, ?int $toSurah = null): array;
}