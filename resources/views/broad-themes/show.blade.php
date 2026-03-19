<x-layouts.app :title="$theme->title_english . ' | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em]" style="color: {{ $theme->badge_color ?: '#0e7c66' }}">Topic / موضوع</p>
                    @if ($theme->title_urdu)
                        <h1 class="mt-2 text-right text-3xl font-semibold text-stone-900 sm:text-4xl" dir="rtl" lang="ur">{{ $theme->title_urdu }}</h1>
                    @endif
                    <p class="mt-3 text-sm font-semibold uppercase tracking-[0.16em]" style="color: {{ $theme->badge_color ?: '#0e7c66' }}">{{ $theme->title_english }}</p>
                    @if ($theme->description)
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-[#7d6d55]">{{ $theme->description }}</p>
                    @endif
                    @if ($theme->description_urdu)
                        <p class="mt-2 max-w-3xl text-sm leading-8 text-[#7d6d55]" dir="rtl" lang="ur">{{ $theme->description_urdu }}</p>
                    @endif
                    <p class="mt-2 max-w-3xl text-xs text-[#8a7d69]" dir="rtl" lang="ur">اس موضوع میں وہ آیات شامل کی گئی ہیں جن میں یہی مفہوم، مضمون یا اسی نوعیت کے مضامین نمایاں ہوں۔</p>
                </div>
                <div class="rounded-[1.4rem] border border-[#d8c49f] bg-[#fbf3e6] px-4 py-3 text-right shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#9b7a47]">Tagged Ayahs</p>
                    <p class="mt-2 text-3xl font-semibold text-stone-900">{{ $theme->ayahs_count }}</p>
                </div>
            </div>
        </div>

        @if ($ayahs->count())
            <div class="space-y-4">
                @foreach ($ayahs as $ayah)
                    <a href="{{ route('ayahs.show', [$ayah->surah, $ayah]) }}" class="block rounded-[1.8rem] border border-[#d7ccb8] bg-[#fffaf2] px-5 py-5 shadow-[0_14px_30px_rgba(88,67,29,0.08)] transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Surah {{ $ayah->surah->number }}, Ayah {{ $ayah->ayah_number }}</p>
                                <p class="mt-2 text-lg font-semibold text-stone-900">{{ $ayah->surah->display_title }}</p>
                            </div>
                            <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">Open Ayah</span>
                        </div>
                        <div class="mt-4 rounded-[1.5rem] border border-[#e2d5bf] bg-[#f7f1e8] px-5 py-5">
                            <p class="text-right text-3xl leading-loose text-[#171b18] sm:text-4xl" dir="rtl" lang="ar">{{ \App\Support\QuranText::normalizeArabicForAyahDisplay($ayah->display_text) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="rounded-[1.6rem] border border-[#d7c8ae] bg-[#fff9ef] px-5 py-4 shadow-sm">
                {{ $ayahs->links() }}
            </div>
        @else
            <div class="rounded-[1.6rem] border border-[#f0d28a] bg-[#fff3d8] px-5 py-4 text-sm text-[#8e5f00] shadow-[0_10px_24px_rgba(166,116,0,0.08)]">
                <p class="font-medium">No ayahs tagged yet for this topic.</p>
            </div>
        @endif
    </section>
</x-layouts.app>
