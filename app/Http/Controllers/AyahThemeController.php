<?php

namespace App\Http\Controllers;

use App\Models\AyahTheme;
use Illuminate\View\View;

class AyahThemeController extends Controller
{
    public function index(): View
    {
        $themes = AyahTheme::query()
            ->where('is_active', true)
            ->withCount('ayahs')
            ->orderByDesc('ayahs_count')
            ->orderBy('title_english')
            ->get();

        return view('ayah-themes.index', [
            'themes' => $themes,
        ]);
    }

    public function show(AyahTheme $ayahTheme): View
    {
        $ayahTheme->loadCount('ayahs');

        $ayahs = $ayahTheme->ayahs()
            ->with('surah')
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->paginate(30);

        return view('ayah-themes.show', [
            'theme' => $ayahTheme,
            'ayahs' => $ayahs,
        ]);
    }
}
