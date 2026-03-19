<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use App\Models\Word;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    public function index(Request $request): View
    {
        $favoriteAyahIds = collect($request->session()->get('favorite_ayah_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $favoriteWordIds = collect($request->session()->get('favorite_word_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $favoriteAyahs = Ayah::with('surah')
            ->whereIn('id', $favoriteAyahIds)
            ->get()
            ->sortBy(fn (Ayah $ayah) => sprintf('%03d-%03d', $ayah->surah?->number ?? 0, $ayah->ayah_number))
            ->values();

        $favoriteWords = Word::with('ayah.surah')
            ->whereIn('id', $favoriteWordIds)
            ->get()
            ->sortBy(fn (Word $word) => sprintf('%03d-%03d-%03d', $word->surah_number, $word->ayah_number, $word->position))
            ->values();

        return view('favorites.index', [
            'favoriteAyahs' => $favoriteAyahs,
            'favoriteWords' => $favoriteWords,
        ]);
    }

    public function toggleAyah(Request $request, \App\Models\Surah $surah, Ayah $ayah): RedirectResponse
    {
        abort_unless($ayah->surah_id === $surah->id, 404);

        $ids = collect($request->session()->get('favorite_ayah_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $wasFavorited = $ids->contains($ayah->id);

        $updated = $wasFavorited
            ? $ids->reject(fn ($id) => $id === $ayah->id)->values()
            : $ids->push($ayah->id)->unique()->values();

        $request->session()->put('favorite_ayah_ids', $updated->all());

        return back()->with('favorite_status', $wasFavorited ? 'Ayah removed from favorites.' : 'Ayah added to favorites.');
    }

    public function toggleWord(Request $request, Word $word): RedirectResponse
    {
        $ids = collect($request->session()->get('favorite_word_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $wasFavorited = $ids->contains($word->id);

        $updated = $wasFavorited
            ? $ids->reject(fn ($id) => $id === $word->id)->values()
            : $ids->push($word->id)->unique()->values();

        $request->session()->put('favorite_word_ids', $updated->all());

        return back()->with('favorite_status', $wasFavorited ? 'Word removed from favorites.' : 'Word added to favorites.');
    }
}
