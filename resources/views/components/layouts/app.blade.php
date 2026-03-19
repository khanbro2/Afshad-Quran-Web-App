<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Quran Study' }}</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            @font-face {
                font-family: "Jameel Noori Nastaleeq";
                src: url('/fonts/JameelNooriNastaleeq-Regular.ttf') format('truetype');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }

            body {
                font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            }

            [lang="ur"] {
                font-family: "Jameel Noori Nastaleeq", "Noto Nastaliq Urdu", "Awami Nastaliq", "Urdu Typesetting", serif;
                line-height: 1.9;
            }
        </style>
    @endif
</head>
<body class="min-h-screen bg-[#efe4d1] text-stone-900">
    <div class="pointer-events-none fixed inset-0 opacity-[0.18]" aria-hidden="true">
        <div class="h-full w-full bg-[radial-gradient(circle_at_1px_1px,rgba(136,103,48,0.16)_1px,transparent_0)] bg-[size:28px_28px]"></div>
    </div>
    <div class="pointer-events-none fixed inset-0 opacity-[0.08]" aria-hidden="true">
        <div class="h-full w-full bg-[linear-gradient(45deg,transparent_0,transparent_46%,rgba(14,124,102,0.16)_46%,rgba(14,124,102,0.16)_54%,transparent_54%,transparent_100%)] bg-[size:120px_120px]"></div>
    </div>
    <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-8 rounded-[2rem] border border-[#d7c8ae] bg-[#fffaf2]/95 px-5 py-4 shadow-sm backdrop-blur">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <a href="{{ route('surahs.index') }}" class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700">Quran Study</a>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600">Simple reading interface for surahs, ayahs, words, and morphology.</p>
                </div>
                <nav class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('ayah-themes.index') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Themes</a>
                    <a href="{{ route('broad-themes.index') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Topics</a>
                    <a href="{{ route('tafaseer.dashboard') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Tafaseer</a>
                    <a href="{{ route('ayahs.search') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Search</a>
                    <a href="{{ route('surahs.index') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Surahs</a>
                    <a href="{{ route('favorites.index') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Favorites</a>
                    <a href="{{ route('notes.index') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Notes</a>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
