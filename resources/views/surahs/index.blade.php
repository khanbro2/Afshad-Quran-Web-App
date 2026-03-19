<x-layouts.app :title="'Surahs | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/50 to-stone-50 px-6 py-8 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-[0.25em] text-emerald-700">Surah List</p>
            <h1 class="mt-3 text-3xl font-semibold text-stone-900 sm:text-4xl">Browse the Quran by surah</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-600">Select a surah to read its ayahs, inspect the Arabic text, and drill down into word-level morphology.</p>
        </div>

        <div class="grid gap-4">
            @foreach ($surahs as $surah)
                <a
                    href="{{ route('surahs.show', $surah) }}"
                    class="group rounded-[2rem] border border-stone-200 bg-white px-5 py-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-base font-semibold text-emerald-700">
                                {{ $surah->number }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <h2 class="text-lg font-semibold text-stone-900">
                                        {{ $surah->display_title }}
                                    </h2>
                                    @if ($surah->display_transliterated_name && $surah->display_english_name !== $surah->display_transliterated_name)
                                        <span class="text-sm text-stone-500">{{ $surah->display_transliterated_name }}</span>
                                    @endif
                                </div>
                                <p class="text-right text-2xl leading-tight text-stone-800" dir="rtl" lang="ar">{{ $surah->display_arabic_name ?? '...' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-6 sm:block sm:text-right">
                            <p class="text-sm text-stone-500">Ayahs</p>
                            <p class="text-xl font-semibold text-stone-900">{{ $surah->display_ayah_count }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.app>
