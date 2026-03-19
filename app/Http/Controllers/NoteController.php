<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use App\Models\Word;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $ayahNotes = collect($request->session()->get('ayah_notes', []));
        $wordNotes = collect($request->session()->get('word_notes', []));

        $ayahIds = $ayahNotes->keys()->map(fn ($id) => (int) $id)->filter()->values();
        $wordIds = $wordNotes->keys()->map(fn ($id) => (int) $id)->filter()->values();

        $ayahs = Ayah::with('surah')
            ->whereIn('id', $ayahIds)
            ->get()
            ->map(function (Ayah $ayah) use ($ayahNotes) {
                $ayah->personal_note = $ayahNotes->get((string) $ayah->id) ?? $ayahNotes->get($ayah->id);
                return $ayah;
            })
            ->sortBy(fn (Ayah $ayah) => sprintf('%03d-%03d', $ayah->surah?->number ?? 0, $ayah->ayah_number))
            ->values();

        $words = Word::with('ayah.surah')
            ->whereIn('id', $wordIds)
            ->get()
            ->map(function (Word $word) use ($wordNotes) {
                $word->personal_note = $wordNotes->get((string) $word->id) ?? $wordNotes->get($word->id);
                return $word;
            })
            ->sortBy(fn (Word $word) => sprintf('%03d-%03d-%03d', $word->surah_number, $word->ayah_number, $word->position))
            ->values();

        return view('notes.index', [
            'ayahs' => $ayahs,
            'words' => $words,
        ]);
    }

    public function storeAyah(Request $request, \App\Models\Surah $surah, Ayah $ayah): RedirectResponse
    {
        abort_unless($ayah->surah_id === $surah->id, 404);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $notes = $request->session()->get('ayah_notes', []);
        $note = trim((string) ($validated['note'] ?? ''));

        if ($note === '') {
            unset($notes[$ayah->id]);
            $message = 'Ayah note removed.';
        } else {
            $notes[$ayah->id] = $note;
            $message = 'Ayah note saved.';
        }

        $request->session()->put('ayah_notes', $notes);

        return back()->with('note_status', $message);
    }

    public function storeWord(Request $request, Word $word): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $notes = $request->session()->get('word_notes', []);
        $note = trim((string) ($validated['note'] ?? ''));

        if ($note === '') {
            unset($notes[$word->id]);
            $message = 'Word note removed.';
        } else {
            $notes[$word->id] = $note;
            $message = 'Word note saved.';
        }

        $request->session()->put('word_notes', $notes);

        return back()->with('note_status', $message);
    }
}
