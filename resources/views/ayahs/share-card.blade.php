<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Share Card | Surah {{ $surah->number }} Ayah {{ $ayah->ayah_number }}</title>
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
                line-height: 1.95;
            }
        </style>
    @endif
</head>
<body class="min-h-screen bg-[#efe4d1] text-stone-900">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-8">
        <div class="w-full max-w-4xl rounded-[2.2rem] border border-[#d7c8ae] bg-[#fff9ef] p-6 shadow-[0_30px_70px_rgba(88,67,29,0.16),inset_0_1px_0_rgba(255,255,255,0.88)] sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#0e7c66]">Quran Share Card</p>
                    <h1 class="mt-2 text-2xl font-semibold text-stone-900 sm:text-3xl">Surah {{ $surah->number }}, Ayah {{ $ayah->ayah_number }}</h1>
                    <p class="mt-1 text-sm text-[#7d6d55]">{{ $surah->display_english_name ?: $surah->display_transliterated_name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl text-[#2a241c] sm:text-4xl" dir="rtl" lang="ar">{{ $surah->display_arabic_name }}</p>
                    <p class="mt-2 text-xs uppercase tracking-[0.22em] text-[#9a825e]">Select One or More Translations</p>
                </div>
            </div>

            @if ($availableTranslations->isNotEmpty())
                <form method="GET" action="{{ route('ayahs.share-card', [$surah, $ayah]) }}" class="mt-8 rounded-[1.8rem] border border-[#dfd0b8] bg-[#f7f1e8] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.86)] print:hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Translation Selection</p>
                            <p class="mt-2 text-sm text-[#7d6d55]">Jo translations aap share card mein dikhana chahte hain unhein select kar lein.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="rounded-full border border-[#0e7c66] bg-[#0e7c66] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0b6856]">Update Card</button>
                            <a href="{{ route('ayahs.share-card', [$surah, $ayah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-4 py-2 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Reset</a>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($availableTranslations as $translation)
                            <label class="flex min-h-[4.6rem] cursor-pointer items-start gap-3 rounded-[1.35rem] border px-4 py-3 shadow-sm transition {{ $selectedTranslationSlugs->contains($translation['slug']) ? 'border-[#0e7c66] bg-[#eef8f4] ring-1 ring-[#9dd4c3]' : 'border-[#dbcdb6] bg-[#fffdf8] hover:border-[#cdb58d]' }}">
                                <input
                                    type="checkbox"
                                    name="translations[]"
                                    value="{{ $translation['slug'] }}"
                                    class="mt-1 h-4 w-4 rounded border-[#b59c74] text-[#0e7c66] focus:ring-[#0e7c66]"
                                    {{ $selectedTranslationSlugs->contains($translation['slug']) ? 'checked' : '' }}
                                >
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-stone-900 {{ $translation['language'] === 'ur' ? 'text-right' : '' }}" @if ($translation['language'] === 'ur') dir="rtl" lang="ur" @endif>
                                        {{ $translation['label'] }}
                                    </span>
                                    <span class="mt-1 block text-xs uppercase tracking-[0.18em] text-[#8e7858]">
                                        {{ $translation['language'] === 'ur' ? 'Urdu' : 'English' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </form>
            @endif

            <div class="mt-8 rounded-[2rem] border border-[#e2d5bf] bg-[#f7f1e8] px-6 py-8 shadow-[inset_0_1px_0_rgba(255,255,255,0.88)]">
                <p class="text-right text-4xl leading-loose text-[#171b18] sm:text-5xl" dir="rtl" lang="ar">{{ $displayAyahText }}</p>

                @if ($selectedTranslations->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        @foreach ($selectedTranslations as $translation)
                            <section class="rounded-[1.5rem] border border-[#d8c9b0] bg-[#fffdf8] px-5 py-4 shadow-[0_14px_30px_rgba(95,73,39,0.06)]">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">
                                        {{ $loop->iteration === 1 ? 'Translation' : 'Additional Translation' }}
                                    </p>
                                    <p class="text-sm font-medium text-[#7d6d55] {{ $translation['language'] === 'ur' ? 'text-right' : 'text-left' }}" @if ($translation['language'] === 'ur') dir="rtl" lang="ur" @endif>
                                        {{ $translation['label'] }}
                                    </p>
                                </div>

                                <div class="mt-3 rounded-[1.2rem] border border-[#efe2cb] bg-[#fffaf2] px-4 py-4">
                                    <p
                                        class="text-lg leading-9 text-[#5f5343] {{ $translation['direction'] === 'rtl' ? 'text-right' : 'text-left' }}"
                                        dir="{{ $translation['direction'] }}"
                                        lang="{{ $translation['language'] }}"
                                    >
                                        {{ $translation['text'] }}
                                    </p>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[1.5rem] border border-dashed border-[#d4c3a5] bg-[#fffdf8] px-5 py-6 text-center text-sm text-[#7d6d55]">
                        Koi translation select nahi hui. Upar se ek ya zyada translations choose kar lein.
                    </div>
                @endif
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-end gap-3 print:hidden">
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="window.print()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-4 py-2 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Print / Save PDF</button>
                    <button type="button" onclick="copyCardLink()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-4 py-2 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Link</button>
                    <a href="{{ route('ayahs.show', [$surah, $ayah]) }}" class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-4 py-2 text-xs font-semibold text-[#0e7c66] shadow-sm transition hover:bg-[#e5f4ee]">Back to Ayah</a>
                </div>
            </div>

            <p id="share-card-status" class="mt-3 hidden text-sm font-medium text-[#0e7c66]"></p>
        </div>
    </div>

    <script>
        function setShareCardStatus(message) {
            const status = document.getElementById('share-card-status');
            if (!status) return;
            status.textContent = message;
            status.classList.remove('hidden');
            clearTimeout(window.__shareCardStatusTimeout);
            window.__shareCardStatusTimeout = setTimeout(() => status.classList.add('hidden'), 2200);
        }

        async function copyCardLink() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                setShareCardStatus('Share card link copied');
            } catch (error) {
                setShareCardStatus('Copy failed');
            }
        }
    </script>
</body>
</html>
