<x-layouts.app :title="'Surah ' . $surah->number . ' | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#0e7c66]">Surah {{ $surah->number }}</p>
                    <h1 class="text-3xl font-semibold text-stone-900 sm:text-4xl">
                        {{ $surah->display_title }}
                    </h1>
                    @if ($surah->display_transliterated_name && $surah->display_english_name !== $surah->display_transliterated_name)
                        <p class="text-sm text-[#7d6d55]">{{ $surah->display_transliterated_name }}</p>
                    @endif
                </div>
                <div class="space-y-2 text-left lg:text-right">
                    <p class="text-right text-4xl text-stone-800 sm:text-5xl" dir="rtl" lang="ar">{{ $surah->display_arabic_name }}</p>
                    <p class="text-sm text-[#7d6d55]">{{ $surah->display_ayah_count }} ayahs</p>
                </div>
            </div>
        </div>

        @if ($themeSummary->count())
            <details class="group rounded-[1.9rem] border border-[#24463d] bg-[#18332c] p-5 shadow-[0_22px_42px_rgba(12,27,24,0.34),inset_0_1px_0_rgba(255,255,255,0.04)]">
                <summary class="flex cursor-pointer list-none flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#78d7bf]">Theme Summary</p>
                        <h2 class="mt-1 text-2xl font-semibold text-white">Surah themes at a glance</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="text-sm text-[#b9d1ca]" dir="rtl" lang="ur">اس سورت میں نمایاں موضوعات دیکھنے کے لیے کھولیں۔</p>
                        <span class="inline-flex rounded-full border border-[#3e7f70] bg-[#21453b] px-3 py-1 text-xs font-semibold text-[#d5fff2] transition group-open:rotate-180 motion-safe:animate-pulse [animation-duration:0.95s]">&#9662;</span>
                    </div>
                </summary>

                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach ($themeSummary as $theme)
                        <a href="{{ route('ayah-themes.show', $theme['slug']) }}" class="rounded-[1.2rem] border border-[#31584d] bg-[#1f3c34] px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:border-[#78d7bf]">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $theme['badge_color'] ?: '#0e7c66' }}"></span>
                                <div class="space-y-1">
                                    @if (!empty($theme['display_title_urdu']))
                                        <p class="text-right text-sm font-semibold leading-7 text-white" dir="rtl" lang="ur">{{ $theme['display_title_urdu'] }}</p>
                                    @endif
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#b9d1ca]">{{ $theme['title_english'] }}</p>
                                </div>
                                <span class="rounded-full border border-[#3e7f70] bg-[#21453b] px-2.5 py-1 text-xs font-semibold text-[#d5fff2]">{{ $theme['ayah_count'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </details>
        @endif

        <div class="space-y-4">
            @foreach ($surah->ayahs as $ayah)
                <a
                    href="{{ route('ayahs.show', [$surah, $ayah]) }}"
                    class="block rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)] transition hover:-translate-y-0.5 hover:border-[#0e7c66]"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-[#0e7c66]">Ayah {{ $ayah->ayah_number }}</p>
                            <p class="text-right text-3xl leading-loose text-stone-900 sm:text-4xl" dir="rtl" lang="ar">{{ \App\Support\QuranText::normalizeArabicForAyahDisplay($ayah->display_text) }}</p>
                            @if ($ayah->broadThemes->count())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($ayah->broadThemes as $theme)
                                        <span class="inline-flex flex-col rounded-[1rem] border border-[#d6c5a4] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] shadow-sm" title="{{ $theme->title_english }}">
                                            @if($theme->title_urdu)
                                                <span class="text-right text-xs font-semibold leading-6" dir="rtl" lang="ur">{{ $theme->title_urdu }}</span>
                                            @endif
                                            <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-[#7d6d55]">{{ $theme->title_english }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="shrink-0 rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-4 py-2 text-sm text-[#7c5c31]">Open ayah</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.app>
