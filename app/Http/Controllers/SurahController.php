<?php

namespace App\Http\Controllers;

use App\Models\Surah;
use Illuminate\View\View;

class SurahController extends Controller
{
    public function index(): View
    {
        $surahs = Surah::query()
            ->withCount('ayahs')
            ->orderBy('number')
            ->get();

        return view('surahs.index', [
            'surahs' => $surahs,
        ]);
    }

    public function show(Surah $surah): View
    {
        $surah->loadCount('ayahs');
        $surah->load([
            'ayahs' => fn ($query) => $query
                ->with(['themes', 'broadThemes'])
                ->orderBy('ayah_number'),
        ]);

        $themeSummary = $surah->ayahs
            ->flatMap(function ($ayah) {
                return $ayah->themes->map(function ($theme) use ($ayah) {
                    return [
                        'id' => $theme->id,
                        'slug' => $theme->slug,
                        'title_english' => $theme->title_english,
                        'title_urdu' => $theme->title_urdu,
                        'display_title_urdu' => $theme->display_title_urdu,
                        'has_clean_urdu_title' => $theme->has_clean_urdu_title,
                        'badge_color' => $theme->badge_color,
                        'ayah_id' => $ayah->id,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'slug' => $first['slug'],
                    'title_english' => $first['title_english'],
                    'title_urdu' => $first['title_urdu'],
                    'display_title_urdu' => $first['display_title_urdu'],
                    'has_clean_urdu_title' => $first['has_clean_urdu_title'],
                    'badge_color' => $first['badge_color'],
                    'ayah_count' => collect($items)->pluck('ayah_id')->unique()->count(),
                ];
            })
            ->sortByDesc('ayah_count')
            ->values();

        return view('surahs.show', [
            'surah' => $surah,
            'themeSummary' => $themeSummary,
        ]);
    }
}
