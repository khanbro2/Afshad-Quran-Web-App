<x-layouts.app title="Favorites | Quran Study">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#0e7c66]">Favorites</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-900 sm:text-4xl">Saved Ayahs and Words</h1>
            <p class="mt-2 text-sm text-[#7d6d55]" dir="rtl" lang="ur">یہاں آپ کے محفوظ کیے گئے آیات اور الفاظ موجود ہیں۔</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Ayahs</p>
                        <h2 class="mt-1 text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">محفوظ آیات</h2>
                    </div>
                    <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $favoriteAyahs->count() }}</span>
                </div>

                @if ($favoriteAyahs->count())
                    <div class="mt-5 space-y-3">
                        @foreach ($favoriteAyahs as $favoriteAyah)
                            <a href="{{ route('ayahs.show', [$favoriteAyah->surah, $favoriteAyah]) }}" class="block rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                                <p class="text-sm font-semibold text-[#0e7c66]">Surah {{ $favoriteAyah->surah->number }}, Ayah {{ $favoriteAyah->ayah_number }}</p>
                                <p class="mt-2 text-right text-2xl text-stone-900" dir="rtl" lang="ar">{{ $favoriteAyah->display_text }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 text-sm text-[#7d6d55]">
                        No ayahs saved yet.
                    </div>
                @endif
            </section>

            <section class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Words</p>
                        <h2 class="mt-1 text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">محفوظ الفاظ</h2>
                    </div>
                    <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $favoriteWords->count() }}</span>
                </div>

                @if ($favoriteWords->count())
                    <div class="mt-5 space-y-3">
                        @foreach ($favoriteWords as $favoriteWord)
                            <a href="{{ route('words.show', $favoriteWord) }}" class="block rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                                <p class="text-sm font-semibold text-[#0e7c66]">Surah {{ $favoriteWord->surah_number }}, Ayah {{ $favoriteWord->ayah_number }}, Position {{ $favoriteWord->position }}</p>
                                <p class="mt-2 text-right text-2xl text-stone-900" dir="rtl" lang="ar">{{ $favoriteWord->display_form }}</p>
                                @if ($favoriteWord->translation_urdu)
                                    <p class="mt-2 text-right text-sm text-[#665947]" dir="rtl" lang="ur">{{ $favoriteWord->translation_urdu }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 text-sm text-[#7d6d55]">
                        No words saved yet.
                    </div>
                @endif
            </section>
        </div>
    </section>
</x-layouts.app>
