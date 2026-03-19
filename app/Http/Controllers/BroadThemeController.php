<?php

namespace App\Http\Controllers;

use App\Models\BroadTheme;
use Illuminate\View\View;

class BroadThemeController extends Controller
{
    public function index(): View
    {
        $themes = BroadTheme::query()
            ->where('is_active', true)
            ->withCount('ayahs')
            ->orderBy('sort_order')
            ->orderBy('title_english')
            ->get();

        return view('broad-themes.index', [
            'themes' => $themes,
        ]);
    }

    public function show(BroadTheme $broadTheme): View
    {
        $broadTheme->loadCount('ayahs');

        $ayahs = $broadTheme->ayahs()
            ->with('surah')
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->paginate(30);

        return view('broad-themes.show', [
            'theme' => $broadTheme,
            'ayahs' => $ayahs,
        ]);
    }
}
