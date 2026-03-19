<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use App\Models\Tafseer;
use Illuminate\View\View;

class TafseerDashboardController extends Controller
{
    public function index(): View
    {
        $totalAyahs = Ayah::query()->count();

        $tafaseer = Tafseer::query()
            ->where('is_active', true)
            ->withCount('ayahTafseers')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (Tafseer $tafseer) use ($totalAyahs) {
                $importedCount = (int) $tafseer->ayah_tafseers_count;
                $remainingCount = max($totalAyahs - $importedCount, 0);
                $completionPercent = $totalAyahs > 0
                    ? round(($importedCount / $totalAyahs) * 100, 1)
                    : 0.0;

                $latestEntry = $tafseer->ayahTafseers()
                    ->with('ayah.surah')
                    ->latest('ayah_id')
                    ->first();

                return [
                    'tafseer' => $tafseer,
                    'imported_count' => $importedCount,
                    'remaining_count' => $remainingCount,
                    'completion_percent' => $completionPercent,
                    'latest_entry' => $latestEntry,
                    'status' => $importedCount >= $totalAyahs && $totalAyahs > 0
                        ? 'Complete'
                        : ($importedCount > 0 ? 'In Progress' : 'Not Started'),
                ];
            });

        $activeTafseerCount = $tafaseer->count();
        $totalImportedEntries = $tafaseer->sum('imported_count');
        $expectedEntries = $activeTafseerCount * $totalAyahs;
        $overallCompletion = $expectedEntries > 0
            ? round(($totalImportedEntries / $expectedEntries) * 100, 1)
            : 0.0;
        $completedTafseerCount = $tafaseer->filter(fn (array $item) => $item['status'] === 'Complete')->count();

        return view('tafaseer.dashboard', [
            'totalAyahs' => $totalAyahs,
            'tafaseer' => $tafaseer,
            'activeTafseerCount' => $activeTafseerCount,
            'totalImportedEntries' => $totalImportedEntries,
            'expectedEntries' => $expectedEntries,
            'overallCompletion' => $overallCompletion,
            'completedTafseerCount' => $completedTafseerCount,
        ]);
    }
}
