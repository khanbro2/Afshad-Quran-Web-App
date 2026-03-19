<?php

namespace App\Services;

use App\Models\Ayah;
use App\Models\Tafseer;
use App\Models\AyahTafseer;
use Illuminate\Support\Collection;

class TafseerService
{
    public function getActiveTafseers(): Collection
    {
        return Tafseer::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getAyahTafseers(Ayah $ayah): Collection
    {
        return AyahTafseer::query()
            ->with('tafseer')
            ->where('ayah_id', $ayah->id)
            ->whereHas('tafseer', function ($q) {
                $q->where('is_active', true);
            })
            ->get()
            ->sortBy(fn ($item) => $item->tafseer->sort_order)
            ->values();
    }

    public function getAyahTafseerBySlug(Ayah $ayah, string $slug): ?AyahTafseer
    {
        return AyahTafseer::query()
            ->with('tafseer')
            ->where('ayah_id', $ayah->id)
            ->whereHas('tafseer', function ($q) use ($slug) {
                $q->where('slug', $slug)->where('is_active', true);
            })
            ->first();
    }
}