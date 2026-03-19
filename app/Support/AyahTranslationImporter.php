<?php

namespace App\Support;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Support\Facades\DB;

class AyahTranslationImporter
{
    /**
     * @param  array<int, array{surah_number:int, ayah_number:int, text:string}>  $verses
     * @return array{parsed:int,updated:int,unchanged:int,missing_surah:int,missing_ayah:int}
     */
    public static function importIntoColumn(array $verses, string $column): array
    {
        $surahIds = Surah::query()
            ->pluck('id', 'number')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ayahRows = Ayah::query()
            ->get(['id', 'surah_id', 'ayah_number', $column])
            ->mapWithKeys(fn (Ayah $ayah) => [
                $ayah->surah_id.':'.$ayah->ayah_number => [
                    'id' => (int) $ayah->id,
                    $column => $ayah->{$column},
                ],
            ])
            ->all();

        $stats = [
            'parsed' => count($verses),
            'updated' => 0,
            'unchanged' => 0,
            'missing_surah' => 0,
            'missing_ayah' => 0,
        ];

        DB::transaction(function () use ($verses, $surahIds, $ayahRows, $column, &$stats): void {
            foreach ($verses as $verse) {
                $surahId = $surahIds[$verse['surah_number']] ?? null;

                if ($surahId === null) {
                    $stats['missing_surah']++;
                    continue;
                }

                $ayahKey = $surahId.':'.$verse['ayah_number'];
                $ayahRow = $ayahRows[$ayahKey] ?? null;

                if ($ayahRow === null) {
                    $stats['missing_ayah']++;
                    continue;
                }

                if ((string) $ayahRow[$column] === $verse['text']) {
                    $stats['unchanged']++;
                    continue;
                }

                DB::table('ayahs')
                    ->where('id', $ayahRow['id'])
                    ->update([
                        $column => $verse['text'],
                        'updated_at' => now(),
                    ]);

                $stats['updated']++;
            }
        });

        return $stats;
    }
}
