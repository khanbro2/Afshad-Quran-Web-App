<x-layouts.app :title="'Surah ' . $surah->number . ' Ayah ' . $ayah->ayah_number . ' | Quran Study'">
    <section class="space-y-8">
        <div class="overflow-hidden rounded-[2.25rem] border border-[#d7ccb8] bg-[#fbf5ea] px-6 py-6 shadow-[0_20px_52px_rgba(70,54,28,0.10),inset_0_1px_0_rgba(255,255,255,0.78)]">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.32em] text-[#0e7c66]">Ayah Detail</p>
                    <h1 class="text-3xl font-semibold leading-tight text-[#14231c] sm:text-4xl">Surah {{ $surah->number }}, Ayah {{ $ayah->ayah_number }}</h1>
                    <p class="text-sm text-stone-500">
                        <a href="{{ route('surahs.show', $surah) }}" class="inline-flex items-center rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Back to surah</a>
                    </p>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <form method="POST" action="{{ route('favorites.ayah.toggle', [$surah, $ayah]) }}">
                            @csrf
                            <button type="submit" class="rounded-full border px-3 py-1.5 text-xs font-semibold shadow-sm transition {{ $isFavoriteAyah ? 'border-[#0e7c66] bg-[#eef8f4] text-[#0e7c66]' : 'border-[#d6c5a4] bg-[#fffaf1] text-[#7c5c31] hover:border-[#0e7c66] hover:text-[#0e7c66]' }}">
                                {{ $isFavoriteAyah ? 'Favorited' : 'Add Favorite' }}
                            </button>
                        </form>
                        <button type="button" onclick="copyAyahArabic()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Arabic</button>
                        <button type="button" onclick="copyAyahReference()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Reference</button>
                        <button type="button" onclick="copyAyahLink()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Link</button>
                        <a href="{{ route('ayahs.share-card', [$surah, $ayah]) }}" target="_blank" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Share Card</a>
                        <button type="button" onclick="shareAyah()" class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-3 py-1.5 text-xs font-semibold text-[#0e7c66] shadow-sm transition hover:bg-[#e5f4ee]">Share</button>
                    </div>
                    @if (session('favorite_status'))
                        <p class="text-xs font-medium text-[#0e7c66]">{{ session('favorite_status') }}</p>
                    @endif
                    <p id="ayah-copy-status" class="hidden text-xs font-medium text-[#0e7c66]"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (!empty($previousAyah))
                        <a href="{{ route('ayahs.show', [$previousAyah->surah, $previousAyah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-4 py-2 text-xs font-semibold text-[#7c5c31] shadow-[0_6px_16px_rgba(92,66,26,0.08)] transition hover:-translate-y-0.5 hover:border-[#b99a64] hover:bg-[#fff5e3] hover:text-[#5d411f]">&larr; Previous ({{ $previousAyah->surah->number }}:{{ $previousAyah->ayah_number }})</a>
                    @endif
                    @if (!empty($nextAyah))
                        <a href="{{ route('ayahs.show', [$nextAyah->surah, $nextAyah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-4 py-2 text-xs font-semibold text-[#7c5c31] shadow-[0_6px_16px_rgba(92,66,26,0.08)] transition hover:-translate-y-0.5 hover:border-[#b99a64] hover:bg-[#fff5e3] hover:text-[#5d411f]">Next ({{ $nextAyah->surah->number }}:{{ $nextAyah->ayah_number }}) &rarr;</a>
                    @endif
                </div>
                <div class="mt-2 rounded-[1.25rem] border border-[#d8cfbf] bg-[#f5ede0] p-4 text-sm text-stone-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_10px_24px_rgba(64,44,17,0.05)]">
                    <form id="jump-ayah-form" class="flex flex-wrap items-end gap-2.5" onsubmit="event.preventDefault(); jumpToAyah();">
                        <label class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-[#9b7a47]">Jump</span>
                            <input id="jump-surah" type="number" min="1" max="114" value="{{ $surah->number }}" class="w-20 rounded-xl border border-[#d4c3a8] bg-white/90 px-3 py-2 text-sm text-[#243229] shadow-inner outline-none transition focus:border-[#0e7c66]" aria-label="Surah number">
                        </label>
                        <label class="flex items-center gap-2">
                            <span class="sr-only">Ayah</span>
                            <input id="jump-ayah" type="number" min="1" value="{{ $ayah->ayah_number }}" class="w-20 rounded-xl border border-[#d4c3a8] bg-white/90 px-3 py-2 text-sm text-[#243229] shadow-inner outline-none transition focus:border-[#0e7c66]" aria-label="Ayah number">
                        </label>
                        <button type="submit" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Go</button>
                        <span class="text-xs text-[#8a7d69]">Enter Surah and Ayah to jump directly.</span>
                    </form>
                </div>
                <p class="text-right text-3xl text-[#2a241c] sm:text-4xl" dir="rtl" lang="ar">{{ $surah->display_arabic_name }}</p>
            </div>

            <div class="mt-5 rounded-[1.6rem] border border-[#ddcfb8] bg-[#f6efe2] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.92),0_10px_24px_rgba(78,58,23,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Navigation</p>
                        <p class="mt-1 text-sm text-[#7d6d55]" dir="rtl" lang="ur">تیز نیویگیشن کے لیے شارٹ کٹس</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if (!empty($firstAyahInSurah) && $firstAyahInSurah->ayah_number !== $ayah->ayah_number)
                            <a href="{{ route('ayahs.show', [$firstAyahInSurah->surah, $firstAyahInSurah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">
                                <span>First Ayah</span>
                                <span class="ml-1" dir="rtl" lang="ur">پہلی آیت</span>
                            </a>
                        @endif
                        @if (!empty($lastAyahInSurah) && $lastAyahInSurah->ayah_number !== $ayah->ayah_number)
                            <a href="{{ route('ayahs.show', [$lastAyahInSurah->surah, $lastAyahInSurah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">
                                <span>Last Ayah</span>
                                <span class="ml-1" dir="rtl" lang="ur">آخری آیت</span>
                            </a>
                        @endif
                        @if (!empty($previousSurahFirstAyah))
                            <a href="{{ route('ayahs.show', [$previousSurahFirstAyah->surah, $previousSurahFirstAyah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">
                                <span>&larr; Previous Surah</span>
                                <span class="ml-1" dir="rtl" lang="ur">پچھلی سورت</span>
                            </a>
                        @endif
                        @if (!empty($nextSurahFirstAyah))
                            <a href="{{ route('ayahs.show', [$nextSurahFirstAyah->surah, $nextSurahFirstAyah]) }}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">
                                <span>Next Surah &rarr;</span>
                                <span class="ml-1" dir="rtl" lang="ur">اگلی سورت</span>
                            </a>
                        @endif
                        @if (!empty($randomAyah))
                            <a href="{{ route('ayahs.show', [$randomAyah->surah, $randomAyah]) }}" class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-3 py-1.5 text-xs font-semibold text-[#0e7c66] shadow-sm transition hover:bg-[#e5f4ee]">
                                <span>Random Ayah</span>
                                <span class="ml-1" dir="rtl" lang="ur">رینڈم آیت</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <script>
                function jumpToAyah() {
                    const surah = Number(document.getElementById('jump-surah').value);
                    const ayah = Number(document.getElementById('jump-ayah').value);
                    if (!Number.isInteger(surah) || surah < 1 || surah > 114 || !Number.isInteger(ayah) || ayah < 1) {
                        alert('Please enter valid surah and ayah numbers.');
                        return;
                    }
                    window.location.href = '/surahs/' + surah + '/ayahs/' + ayah;
                }

                function setAyahCopyStatus(message) {
                    const status = document.getElementById('ayah-copy-status');
                    if (!status) return;
                    status.textContent = message;
                    status.classList.remove('hidden');
                    clearTimeout(window.__ayahCopyStatusTimeout);
                    window.__ayahCopyStatusTimeout = setTimeout(() => status.classList.add('hidden'), 2200);
                }

                async function copyTextValue(value, message) {
                    try {
                        await navigator.clipboard.writeText(value);
                        setAyahCopyStatus(message);
                    } catch (error) {
                        setAyahCopyStatus('Copy failed');
                    }
                }

                function copyAyahArabic() {
                    copyTextValue(@json($displayAyahText), 'Arabic text copied');
                }

                function copyAyahReference() {
                    copyTextValue(@json('Surah ' . $surah->number . ', Ayah ' . $ayah->ayah_number), 'Reference copied');
                }

                function copyAyahLink() {
                    copyTextValue(window.location.href, 'Page link copied');
                }

                async function shareAyah() {
                    const payload = {
                        title: @json('Surah ' . $surah->number . ', Ayah ' . $ayah->ayah_number),
                        text: @json($displayAyahText),
                        url: window.location.href,
                    };

                    if (navigator.share) {
                        try {
                            await navigator.share(payload);
                            setAyahCopyStatus('Share sheet opened');
                            return;
                        } catch (error) {
                            if (error && error.name === 'AbortError') return;
                        }
                    }

                    copyAyahLink();
                }
            </script>

            @if ($ayah->themes->count() || $ayah->broadThemes->count())
                <div class="mt-7 rounded-[1.55rem] border border-[#d9c9ad] bg-[#f4ede0] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.92),0_12px_24px_rgba(75,55,21,0.06)]">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Ayah Topics</p>
                        <p class="mt-1 text-sm text-[#7d6d55]" dir="rtl" lang="ur">اٰیت کے موضوعات</p>
                        <p class="mt-1 text-xs text-[#8a7d69]" dir="rtl" lang="ur">کسی بھی موضوع پر کلک کرکے اس سے متعلق تمام آیات ایک جگہ دیکھی جا سکتی ہیں۔</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($ayah->themes as $theme)
                            <a href="{{ route('ayah-themes.show', $theme) }}" class="inline-flex items-center gap-2 rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#5f4a28] shadow-sm transition hover:border-[#0e7c66]">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $theme->badge_color ?: '#0e7c66' }}"></span>
                                @if($theme->display_title_urdu)
                                    <span dir="rtl" lang="ur">{{ $theme->display_title_urdu }}</span>
                                @endif
                                <span class="text-[10px] uppercase tracking-[0.08em] text-[#7d6d55]">{{ $theme->title_english }}</span>
                            </a>
                        @endforeach
                        @foreach ($ayah->broadThemes as $broadTheme)
                            <a href="{{ route('broad-themes.show', $broadTheme) }}" class="inline-flex items-center gap-2 rounded-full border border-[#cfc2f3] bg-[#f8f5ff] px-3 py-1.5 text-xs font-semibold text-[#56458b] shadow-sm transition hover:border-[#7048ff]">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $broadTheme->badge_color ?: '#7048ff' }}"></span>
                                @if($broadTheme->title_urdu)
                                    <span dir="rtl" lang="ur">{{ $broadTheme->title_urdu }}</span>
                                @endif
                                <span class="text-[10px] uppercase tracking-[0.08em] text-[#7b73a0]">{{ $broadTheme->title_english }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-7 rounded-[2rem] border border-[#dfd0b9] bg-[#f6efe1] px-5 py-7 shadow-[inset_0_1px_0_rgba(255,255,255,0.95),0_16px_34px_rgba(75,55,21,0.07)]">
                <p class="text-right text-4xl leading-loose text-[#171b18] sm:text-5xl" dir="rtl" lang="ar">{{ $displayAyahText }}</p>
@if (
    $ayah->urdu_translation_ahmedali ||
    $ayah->urdu_translation_kanzuliman ||
    $ayah->urdu_translation_maududi ||
    $ayah->urdu_translation ||
    $ayah->urdu_translation_mufti_taqi ||
    $ayah->urdu_translation_jalandhry ||
    $ayah->urdu_translation_bayan_simple ||
    $ayah->english_translation_mufti_taqi
)
    <div class="mt-6 rounded-[1.7rem] border border-[#d6c8b2] bg-[#ebe0cf] p-3.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.7),0_14px_28px_rgba(92,69,33,0.08)] sm:p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-[#d9ccb7] bg-[#f7f0e4] px-4 py-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0b8667]">Translations</p>
                <p class="mt-1 text-sm text-[#7d6d55]" dir="rtl" lang="ur">ترجمہ کا سائز اپنی سہولت کے مطابق تبدیل کریں</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[#8b7654]">Text Size</span>
                <div class="flex items-center gap-2 rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-2 shadow-sm">
                    <span class="text-[11px] font-semibold text-[#7c5c31]">A</span>
                    <input type="range" min="80" max="160" step="5" value="100" class="translation-size-slider h-1.5 w-28 cursor-pointer accent-[#0e7c66]" aria-label="Translation text size">
                    <span class="translation-size-value min-w-[3rem] text-right text-[11px] font-semibold text-[#0e7c66]">100%</span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap justify-end gap-3 text-right" dir="ltr" style="flex-wrap: wrap-reverse;">

            @if ($ayah->english_translation_mufti_taqi)
                <details class="group rounded-[1.35rem] border border-[#c9d3ff] bg-[#f4f6ff] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(77,102,214,0.08)] transition hover:-translate-y-0.5 hover:bg-[#edf1ff] hover:shadow-[0_16px_26px_rgba(77,102,214,0.12)]" dir="ltr">
                    <summary class="list-none cursor-pointer text-left">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#d4dbff] bg-[#f7f9ff] px-3 py-2 text-left shadow-sm">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#4f63d6]">Mufti Taqi Usmani (English)</span>
                            <span class="mt-1 text-[11px] text-stone-500">Book: The Noble Quran Translation</span>
                            <span class="text-[11px] text-stone-500">Translator: Mufti Muhammad Taqi Usmani</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#dfe4fb] pt-3 text-left text-stone-800" dir="ltr" lang="en" data-base-size="1.0625" data-base-line="2">{{ $ayah->english_translation_mufti_taqi }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_mufti_taqi)
                <details class="group rounded-[1.35rem] border border-[#d7c6ff] bg-[#f8f3ff] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(121,77,255,0.08)] transition hover:-translate-y-0.5 hover:bg-[#f2ebff] hover:shadow-[0_16px_26px_rgba(121,77,255,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#e0d0ff] bg-[#faf7ff] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#7048ff]">آسان ترجمۂ قرآن</span>
                            <span class="mt-1 text-[11px] text-stone-500">مترجم: مفتی محمد تقی عثمانی</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#e6dff5] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_mufti_taqi }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_ahmedali)
                <details class="group rounded-[1.35rem] border border-[#9ee8cf] bg-[#f4fbf8] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(13,138,110,0.08)] transition hover:-translate-y-0.5 hover:bg-[#eef8f4] hover:shadow-[0_16px_26px_rgba(13,138,110,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#a9ead5] bg-[#f3fffa] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#0c8a69]">ترجمہ احمد علی</span>
                            <span class="mt-1 text-[11px] text-stone-500">مترجم: احمد علی لاہوری</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#d8eee6] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_ahmedali }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_kanzuliman)
                <details class="group rounded-[1.35rem] border border-[#f1c1ca] bg-[#fff5f6] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(196,62,97,0.08)] transition hover:-translate-y-0.5 hover:bg-[#fff0f2] hover:shadow-[0_16px_26px_rgba(196,62,97,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#f6ccd4] bg-[#fff7f8] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#d53c63]">کنز الایمان</span>
                            <span class="mt-1 text-[11px] text-stone-500">مترجم: امام احمد رضا خان بریلوی</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#f1dde1] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_kanzuliman }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_maududi)
                <details class="group rounded-[1.35rem] border border-[#efcf81] bg-[#fff8e8] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(177,124,9,0.08)] transition hover:-translate-y-0.5 hover:bg-[#fff2d8] hover:shadow-[0_16px_26px_rgba(177,124,9,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#efd694] bg-[#fffaf0] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#cb7a00]">تفہیم القرآن</span>
                            <span class="mt-1 text-[11px] text-stone-500">مصنف و مترجم: سید ابوالاعلیٰ مودودی</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#eee2b8] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_maududi }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation)
                <details class="group rounded-[1.35rem] border border-[#b7ddff] bg-[#f2f8ff] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(35,121,214,0.08)] transition hover:-translate-y-0.5 hover:bg-[#ebf5ff] hover:shadow-[0_16px_26px_rgba(35,121,214,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#cbe6ff] bg-[#f6fbff] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#1874d1]">قرآن فاؤنڈیشن اردو</span>
                            <span class="mt-1 text-[11px] text-stone-500">ادارہ: Quran Foundation</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#dceaf7] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_jalandhry)
                <details class="group rounded-[1.35rem] border border-[#c6d7a1] bg-[#f7fbe8] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(103,126,28,0.08)] transition hover:-translate-y-0.5 hover:bg-[#f2f8dc] hover:shadow-[0_16px_26px_rgba(103,126,28,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#d4e0b4] bg-[#fcfff4] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#6a7c1d]">ترجمہ جالندھری</span>
                            <span class="mt-1 text-[11px] text-stone-500">مترجم: فتح محمد جالندھری</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#dfe8c5] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_jalandhry }}</p>
                </details>
            @endif

            @if ($ayah->urdu_translation_bayan_simple)
                <details class="group rounded-[1.35rem] border border-[#d9c2a7] bg-[#fff7ef] px-4 py-2.5 text-sm text-stone-700 shadow-[0_10px_20px_rgba(144,94,41,0.08)] transition hover:-translate-y-0.5 hover:bg-[#fff1e4] hover:shadow-[0_16px_26px_rgba(144,94,41,0.12)]">
                    <summary class="list-none cursor-pointer">
                        <span class="inline-flex flex-col rounded-[1rem] border border-[#e7d1b8] bg-[#fffaf5] px-3 py-2 text-right shadow-sm" dir="rtl" lang="ur">
                            <span class="text-xs font-semibold tracking-[0.08em] text-[#9a5a21]">بیان القرآن (سادہ)</span>
                            <span class="mt-1 text-[11px] text-stone-500">اصل نسبت: اشرف علی تھانوی</span>
                        </span>
                    </summary>
                    <p class="translation-text mt-3 max-w-4xl border-t border-[#efdfd0] pt-3 text-right text-stone-800" dir="rtl" lang="ur" data-base-size="1.25" data-base-line="2.25">{{ $ayah->urdu_translation_bayan_simple }}</p>
                </details>
            @endif

            
        </div>
    </div>
@endif
{{--
<div class="card shadow-sm border-0 rounded-4 mt-4">
    <div class="card-body ">
        <h4 class="mb-3 text-success">Tafsir / ØªÙØ³ÛŒØ±</h4>

        @if($tafseerEntries->count())
            <div class="accordion" id="tafseerAccordion">
                @foreach($tafseerEntries as $index => $entry)
                    @php
                        $headingId = 'tafseerHeading' . $entry->id;
                        $collapseId = 'tafseerCollapse' . $entry->id;
                    @endphp

                    <div class="accordion-item mb-2 border rounded-3">
                        <h2 class="accordion-header" id="{{ $headingId }}">
                            <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                    aria-controls="{{ $collapseId }}">
                                <div>
                                    <strong>{{ $entry->tafseer->title_urdu }}</strong>
                                    @if($entry->tafseer->author)
                                        <div class="small text-muted">{{ $entry->tafseer->author }}</div>
                                    @endif
                                </div>
                            </button>
                        </h2>
                        
                        <div class="border rounded p-3 bg-white">
    <div class="fw-bold">{{ $entry->meta['title'] ?? 'ØªÙØ³ÛŒØ±' }}</div>
<div class="text-muted mb-2">{{ $entry->meta['author'] ?? '' }}</div>
<div dir="rtl" style="white-space: pre-line; line-height: 2;">
    {{ $entry->content }}
</div>
--}}

<section class="mt-6 rounded-[1.9rem] border border-[#cbbd9f] bg-[#f4ecdd] p-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]" dir="rtl">
    @php
        $urduTafseerEntries = $tafseerEntries->filter(fn ($entry) => ($entry->tafseer->language ?? 'ur') === 'ur')->values();
        $arabicTafseerEntries = $tafseerEntries->filter(fn ($entry) => ($entry->tafseer->language ?? '') === 'ar')->values();
        $englishTafseerEntries = $tafseerEntries->filter(fn ($entry) => ($entry->tafseer->language ?? '') === 'en')->values();
        $tafsirGroups = [
            'ur' => ['label' => 'اردو تفاسیر', 'entries' => $urduTafseerEntries, 'dir' => 'rtl'],
            'ar' => ['label' => 'عربی تفاسیر', 'entries' => $arabicTafseerEntries, 'dir' => 'rtl'],
            'en' => ['label' => 'English Tafsir', 'entries' => $englishTafseerEntries, 'dir' => 'ltr'],
        ];
    @endphp
    <div class="flex flex-wrap items-start justify-between gap-3 text-right">
        <div class="text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#0b8667]">Tafsir</p>
            <h2 class="mt-1 text-2xl font-semibold text-[#15211c] text-right" dir="rtl" lang="ur">تفسیر</h2>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[#8b7654]">Text Size</span>
                <div class="flex items-center gap-2 rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-2 shadow-sm">
                    <span class="text-[11px] font-semibold text-[#7c5c31]">A</span>
                    <input type="range" min="80" max="160" step="5" value="100" class="tafsir-size-slider h-1.5 w-28 cursor-pointer accent-[#0e7c66]" aria-label="Tafsir text size">
                    <span class="tafsir-size-value min-w-[3rem] text-right text-[11px] font-semibold text-[#0e7c66]">100%</span>
                </div>
            </div>
            @if ($tafseerEntries->count())
                <span class="rounded-full border border-[#d6c7a6] bg-[#fffaf1] px-3 py-1 text-xs font-semibold text-[#0b8667] shadow-sm">
                    {{ $tafseerEntries->count() }} tafsir {{ \Illuminate\Support\Str::plural('entry', $tafseerEntries->count()) }}
                </span>
            @endif
        </div>
    </div>

    @if ($tafseerEntries->count())
        <div class="mt-4 space-y-4">
            @foreach ($tafsirGroups as $groupLang => $group)
                @continue($group['entries']->isEmpty())
                @php
                    $isEnglishTafsirGroup = $groupLang === 'en';
                    $groupHeaderAlignment = $isEnglishTafsirGroup ? 'justify-between text-left' : 'justify-between text-right';
                    $groupChipWrapClasses = 'flex-wrap justify-start';
                    $panelTextAlignment = $isEnglishTafsirGroup ? 'text-left' : 'text-right';
                @endphp
                <div class="rounded-[1.6rem] border border-[#d8cab2] bg-[#efe4d2] p-3 text-right shadow-[inset_0_1px_0_rgba(255,255,255,0.88)]" dir="{{ $group['dir'] }}">
                    <div class="mb-3 flex items-center gap-3 {{ $groupHeaderAlignment }}">
                        <p class="text-sm font-semibold tracking-[0.14em] text-[#0b8667]" dir="{{ $group['dir'] }}">{{ $group['label'] }}</p>
                        <span class="rounded-full border border-[#d6c7a6] bg-[#fffaf1] px-3 py-1 text-[11px] font-semibold text-[#6b5a3d]">
                            {{ $group['entries']->count() }} {{ \Illuminate\Support\Str::plural('entry', $group['entries']->count()) }}
                        </span>
                    </div>

                    <div class="flex {{ $groupChipWrapClasses }} gap-2" dir="{{ $group['dir'] }}" @if($isEnglishTafsirGroup) style="flex-wrap: wrap-reverse;" @endif>
                        @foreach ($group['entries'] as $entry)
                            @php
                                $displayTitle = $groupLang === 'en'
                                    ? ($entry->tafseer->title_english ?: $entry->tafseer->title_urdu)
                                    : ($entry->tafseer->title_urdu ?: $entry->tafseer->title_english);
                            @endphp
                            <button
                                type="button"
                                class="tafsir-tab rounded-full border border-[#ccb88e] bg-[#fff7e8] px-4 py-2 text-right text-xs font-semibold tracking-[0.08em] text-[#684c20] shadow-sm transition hover:border-[#0b8667] hover:bg-[#0f7665] hover:text-white"
                                data-tafsir-tab="tafsir-panel-{{ $entry->id }}"
                                data-open="false"
                                dir="{{ $group['dir'] }}"
                                lang="{{ $groupLang }}"
                            >
                                {{ $displayTitle }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($group['entries'] as $entry)
                        @php
                            $displayTitle = $groupLang === 'en'
                                ? ($entry->tafseer->title_english ?: $entry->tafseer->title_urdu)
                                : ($entry->tafseer->title_urdu ?: $entry->tafseer->title_english);
                        @endphp
                        <div
                            id="tafsir-panel-{{ $entry->id }}"
                            class="tafsir-panel mt-4 hidden rounded-[1.6rem] border border-[#d8cab2] bg-[#fffaf1] p-5 {{ $panelTextAlignment }} shadow-[0_14px_28px_rgba(60,48,22,0.08)]"
                            dir="{{ $group['dir'] }}"
                        >
                            <div class="{{ $panelTextAlignment }}">
                                <p class="text-lg font-semibold text-[#15211c]" dir="{{ $group['dir'] }}" lang="{{ $groupLang }}">
                                    {{ $displayTitle }}
                                </p>
                                @if ($groupLang !== 'en' && $entry->tafseer->title_english && $entry->tafseer->title_english !== $entry->tafseer->title_urdu)
                                    <p class="mt-1 text-sm text-stone-500" dir="ltr" lang="en">{{ $entry->tafseer->title_english }}</p>
                                @endif
                                @if ($entry->tafseer->author)
                                    <p class="mt-1 text-sm text-stone-500" dir="{{ $group['dir'] }}" lang="{{ $groupLang }}">{{ $entry->tafseer->author }}</p>
                                @endif
                            </div>

                            @if (!empty($entry->meta['title']) || !empty($entry->meta['author']))
                                <div class="mt-4 rounded-[1.35rem] border border-[#e7d8c0] bg-[#f4ebde] px-4 py-3 {{ $panelTextAlignment }} shadow-[inset_0_1px_0_rgba(255,255,255,0.92)]" dir="{{ $group['dir'] }}">
                                    @if (!empty($entry->meta['title']))
                                        <p class="font-semibold text-[#18211c]" dir="{{ $group['dir'] }}" lang="{{ $groupLang }}">{{ $entry->meta['title'] }}</p>
                                    @endif
                                    @if (!empty($entry->meta['author']))
                                        <p class="mt-1 text-sm text-stone-500" dir="{{ $group['dir'] }}" lang="{{ $groupLang }}">{{ $entry->meta['author'] }}</p>
                                    @endif
                                </div>
                            @endif

                            <div class="tafsir-content prose prose-stone mt-4 max-w-none {{ $panelTextAlignment }} prose-p:my-0 prose-p:text-[#2f2a24] [&_.arabic-inline]:font-['Noto_Naskh_Arabic','Amiri','Scheherazade_New',serif] [&_.arabic-inline]:text-[1.08em] [&_.arabic-inline]:leading-[2.2] [&_.arabic-inline]:text-[#1d4ed8] [&_.arabic-inline]:unicode-bidi-isolate [&_.arabic-inline-block]:my-3 [&_.arabic-inline-block]:block [&_.arabic-inline-block]:text-center [&_.arabic-inline-block]:text-[1.18em] [&_.arabic-bracketed]:text-[#1d4ed8]" dir="{{ $groupLang === 'en' ? 'rtl' : $group['dir'] }}" lang="{{ $groupLang }}" data-base-size="1.125" data-base-line="2.25">
                                {!! $entry->content_html ?: nl2br(e($entry->content)) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <script>
            (function () {
                const tabs = document.querySelectorAll('.tafsir-tab');
                const panels = document.querySelectorAll('.tafsir-panel');

                function resetTabs() {
                    tabs.forEach((item) => {
                        item.dataset.open = 'false';
                        item.classList.remove('border-[#0b8667]', 'bg-[#0b8667]', 'text-white', 'shadow-md', 'hover:text-white');
                        item.classList.add('border-[#ccb88e]', 'bg-[#fff7e8]', 'text-[#684c20]');
                    });

                    panels.forEach((panel) => panel.classList.add('hidden'));
                }

                if (tabs.length && panels.length) {
                    tabs.forEach((tab) => {
                        tab.addEventListener('click', function () {
                            const targetId = tab.getAttribute('data-tafsir-tab');
                            const targetPanel = document.getElementById(targetId);
                            if (!targetPanel) return;

                            const isOpen = tab.dataset.open === 'true';

                            resetTabs();

                            if (isOpen) return;

                            tab.dataset.open = 'true';
                            tab.classList.remove('border-[#ccb88e]', 'bg-[#fff7e8]', 'text-[#684c20]');
                            tab.classList.add('border-[#0b8667]', 'bg-[#0b8667]', 'text-white', 'shadow-md', 'hover:text-white');
                            targetPanel.classList.remove('hidden');
                        });
                    });
                }

                function applyResizableText(selector, storageKey, defaultScale, sliderSelector, valueSelector) {
                    const elements = document.querySelectorAll(selector);
                    if (!elements.length) return;

                    const slider = document.querySelector(sliderSelector);
                    const value = document.querySelector(valueSelector);
                    let scale = Number(window.localStorage.getItem(storageKey) || defaultScale);

                    if (!Number.isFinite(scale)) {
                        scale = defaultScale;
                    }

                    function clamp(value) {
                        return Math.min(1.6, Math.max(0.8, value));
                    }

                    function render() {
                        elements.forEach((element) => {
                            const baseSize = Number(element.dataset.baseSize || 1);
                            const baseLine = Number(element.dataset.baseLine || 1.8);
                            element.style.fontSize = `${(baseSize * scale).toFixed(3)}rem`;
                            element.style.lineHeight = `${(baseLine * scale).toFixed(3)}rem`;
                        });

                        if (slider) {
                            slider.value = String(Math.round(scale * 100));
                        }

                        if (value) {
                            value.textContent = `${Math.round(scale * 100)}%`;
                        }
                    }

                    function setScale(nextScale) {
                        scale = clamp(nextScale);
                        window.localStorage.setItem(storageKey, String(scale));
                        render();
                    }

                    render();

                    if (slider) {
                        slider.addEventListener('input', function () {
                            const nextScale = Number(slider.value) / 100;
                            setScale(nextScale);
                        });
                    }
                }

                function enhanceArabicInlineText() {
                    const containers = document.querySelectorAll('.tafsir-content[lang="ur"], .tafsir-content[lang="en"]');

                    const arabicRunPattern = /([\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF][\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\s\u064B-\u065F\u0670\u06D6-\u06ED\u200C\u200D]*)/g;
                    const quranicMarkPattern = /[\u064B-\u065F\u0670\u06D6-\u06ED]/u;
                    const diacriticWordPattern = /([\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]*[\u064B-\u065F\u0670\u06D6-\u06ED]+[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u064B-\u065F\u0670\u06D6-\u06ED]*)/g;

                    containers.forEach((container) => {
                        if (container.dataset.arabicEnhanced === 'true') {
                            return;
                        }

                        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, {
                            acceptNode(node) {
                                if (!node.nodeValue || !arabicRunPattern.test(node.nodeValue) || !quranicMarkPattern.test(node.nodeValue)) {
                                    arabicRunPattern.lastIndex = 0;
                                    return NodeFilter.FILTER_REJECT;
                                }

                                arabicRunPattern.lastIndex = 0;

                                const parent = node.parentElement;

                                if (!parent || parent.closest('.arabic-inline') || ['SCRIPT', 'STYLE'].includes(parent.tagName)) {
                                    return NodeFilter.FILTER_REJECT;
                                }

                                return NodeFilter.FILTER_ACCEPT;
                            }
                        });

                        const textNodes = [];

                        while (walker.nextNode()) {
                            textNodes.push(walker.currentNode);
                        }

                        textNodes.forEach((node) => {
                            const text = node.nodeValue || '';
                            const isEnglishContainer = container.getAttribute('lang') === 'en';

                            if (!arabicRunPattern.test(text) || !quranicMarkPattern.test(text)) {
                                arabicRunPattern.lastIndex = 0;
                                return;
                            }

                            arabicRunPattern.lastIndex = 0;

                            const fragment = document.createDocumentFragment();
                            let lastIndex = 0;

                            text.replace(arabicRunPattern, (match, group, offset) => {
                                if (offset > lastIndex) {
                                    fragment.appendChild(document.createTextNode(text.slice(lastIndex, offset)));
                                }

                                let groupLastIndex = 0;

                                const shouldPromoteWholeGroup =
                                    isEnglishContainer &&
                                    quranicMarkPattern.test(group) &&
                                    group.trim().length >= 18 &&
                                    /\s/.test(group.trim());

                                if (shouldPromoteWholeGroup) {
                                    const span = document.createElement('span');
                                    span.className = 'arabic-inline arabic-inline-block';
                                    span.setAttribute('lang', 'ar');
                                    span.setAttribute('dir', 'rtl');
                                    span.textContent = group.trim();
                                    fragment.appendChild(span);
                                    lastIndex = offset + group.length;

                                    return match;
                                }

                                group.replace(diacriticWordPattern, (wordMatch, wordGroup, wordOffset) => {
                                    if (wordOffset > groupLastIndex) {
                                        fragment.appendChild(document.createTextNode(group.slice(groupLastIndex, wordOffset)));
                                    }

                                    const span = document.createElement('span');
                                    span.className = 'arabic-inline';
                                    span.setAttribute('lang', 'ar');
                                    span.setAttribute('dir', 'rtl');
                                    span.textContent = wordGroup;
                                    fragment.appendChild(span);

                                    groupLastIndex = wordOffset + wordGroup.length;

                                    return wordMatch;
                                });

                                if (groupLastIndex < group.length) {
                                    fragment.appendChild(document.createTextNode(group.slice(groupLastIndex)));
                                }

                                lastIndex = offset + group.length;

                                return match;
                            });

                            if (lastIndex < text.length) {
                                fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
                            }

                            node.parentNode?.replaceChild(fragment, node);
                        });

                        container.dataset.arabicEnhanced = 'true';
                    });
                }

                function enhanceArabicBracketedText() {
                    const containers = document.querySelectorAll('.tafsir-content[lang="ar"]');
                    const bracketPattern = /(\(([^)]{1,200})\)|«([^»]{1,200})»|﴿([^﴾]{1,200})﴾|﴾([^﴿]{1,200})﴿)/g;

                    containers.forEach((container) => {
                        if (container.dataset.arabicBracketEnhanced === 'true') {
                            return;
                        }

                        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, {
                            acceptNode(node) {
                                const parent = node.parentElement;

                                if (!node.nodeValue || !bracketPattern.test(node.nodeValue)) {
                                    bracketPattern.lastIndex = 0;
                                    return NodeFilter.FILTER_REJECT;
                                }

                                bracketPattern.lastIndex = 0;

                                if (!parent || parent.closest('.arabic-bracketed') || ['SCRIPT', 'STYLE'].includes(parent.tagName)) {
                                    return NodeFilter.FILTER_REJECT;
                                }

                                return NodeFilter.FILTER_ACCEPT;
                            }
                        });

                        const textNodes = [];

                        while (walker.nextNode()) {
                            textNodes.push(walker.currentNode);
                        }

                        textNodes.forEach((node) => {
                            const text = node.nodeValue || '';

                            if (!bracketPattern.test(text)) {
                                bracketPattern.lastIndex = 0;
                                return;
                            }

                            bracketPattern.lastIndex = 0;

                            const fragment = document.createDocumentFragment();
                            let lastIndex = 0;

                            text.replace(bracketPattern, (match, _g1, parenInner, angleInner, ornateInner, reverseOrnateInner, offset) => {
                                if (offset > lastIndex) {
                                    fragment.appendChild(document.createTextNode(text.slice(lastIndex, offset)));
                                }

                                const inner = parenInner ?? angleInner ?? ornateInner ?? reverseOrnateInner ?? '';
                                const open = match[0];
                                const close = match[match.length - 1];

                                fragment.appendChild(document.createTextNode(open));

                                const span = document.createElement('span');
                                span.className = 'arabic-bracketed';
                                span.setAttribute('lang', 'ar');
                                span.setAttribute('dir', 'rtl');
                                span.textContent = inner;
                                fragment.appendChild(span);

                                fragment.appendChild(document.createTextNode(close));
                                lastIndex = offset + match.length;

                                return match;
                            });

                            if (lastIndex < text.length) {
                                fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
                            }

                            node.parentNode?.replaceChild(fragment, node);
                        });

                        container.dataset.arabicBracketEnhanced = 'true';
                    });
                }

                enhanceArabicInlineText();
                enhanceArabicBracketedText();
                applyResizableText('.translation-text', 'translation-size', 1, '.translation-size-slider', '.translation-size-value');
                applyResizableText('.tafsir-content', 'tafsir-size', 1, '.tafsir-size-slider', '.tafsir-size-value');
            })();
        </script>
        {{--
        <div class="mt-4 space-y-3">
            @foreach ($tafseerEntries as $index => $entry)
                <details class="group overflow-hidden rounded-[1.6rem] border border-[#d8d9d3] bg-[#fffdf8] shadow-[0_14px_28px_rgba(39,55,48,0.08)]" {{ $index === 0 ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 px-5 py-4 transition hover:bg-white/70">
                        <div>
                            <p class="text-lg font-semibold text-[#15211c]">{{ $entry->tafseer->title_urdu }}</p>
                            @if ($entry->tafseer->author)
                                <p class="mt-1 text-sm text-stone-500">{{ $entry->tafseer->author }}</p>
                            @endif
                        </div>
                        <span class="mt-1 text-sm font-semibold text-[#0b8667] transition group-open:rotate-180">â–¼</span>
                    </summary>

                    <div class="border-t border-[#e5e0d5] px-5 py-4">
                        @if (!empty($entry->meta['title']) || !empty($entry->meta['author']))
                            <div class="mb-4 rounded-[1.35rem] border border-[#efe6d8] bg-[#f7f3ec] px-4 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.92)]">
                                @if (!empty($entry->meta['title']))
                                    <p class="font-semibold text-[#18211c]">{{ $entry->meta['title'] }}</p>
                                @endif
                                @if (!empty($entry->meta['author']))
                                    <p class="mt-1 text-sm text-stone-500">{{ $entry->meta['author'] }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="prose prose-stone max-w-none text-right leading-9 prose-p:my-0 prose-p:text-[#2f2a24]" dir="rtl" lang="ur">
                            {!! $entry->content_html ?: nl2br(e($entry->content)) !!}
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
        --}}
    @else
        <div class="mt-4 rounded-[1.5rem] border border-[#f0d28a] bg-[#fff3d8] px-5 py-4 text-sm text-[#8e5f00] shadow-[0_10px_24px_rgba(166,116,0,0.08)]">
            <p class="font-medium">Tafsir content abhi import nahi hua.</p>
            <p class="mt-1" dir="rtl" lang="ur">ØªÙØ³ÛŒØ± Ú©Ø§ Ø³ÛŒÚ©Ø´Ù† ØªÛŒØ§Ø± ÛÛ’ØŒ Ù„ÛŒÚ©Ù† Ù…ØªØ¹Ù„Ù‚Û Ù…ÙˆØ§Ø¯ Ø§Ø¨Ú¾ÛŒ scrape/import Ù†ÛÛŒÚº ÛÙˆØ§Û”</p>

            @if (isset($availableTafseers) && $availableTafseers->count())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($availableTafseers as $tafseer)
                        <span class="rounded-full border border-stone-300 bg-white px-3 py-1 text-xs font-semibold text-stone-700">
                            {{ $tafseer->title_urdu }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</section>
<details class="fixed right-4 top-1/2 z-40 hidden -translate-y-1/2 md:block">
    <summary class="flex h-12 w-12 cursor-pointer list-none items-center justify-center rounded-full bg-[#0e7c66] text-xl font-semibold text-white shadow-[0_14px_28px_rgba(14,124,102,0.32)]">
        <span aria-hidden="true">+</span>
    </summary>
    <div class="mt-3 w-56 rounded-[1.4rem] border border-[#d7c8ae] bg-[#fffaf2]/95 p-3 shadow-[0_18px_40px_rgba(88,67,29,0.16)] backdrop-blur">
        <div class="space-y-2 text-sm">
        <a href="{{ route('favorites.index') }}" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Favorites</a>
        <a href="{{ route('notes.index') }}" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Notes</a>
        <form method="POST" action="{{ route('favorites.ayah.toggle', [$surah, $ayah]) }}">
            @csrf
            <button type="submit" class="block w-full rounded-xl border px-3 py-2 text-left transition {{ $isFavoriteAyah ? 'border-[#0e7c66] bg-[#eef8f4] text-[#0e7c66]' : 'border-[#e2d5bf] bg-[#f7f1e8] text-[#5f4a28] hover:border-[#0e7c66] hover:text-[#0e7c66]' }}">
                {{ $isFavoriteAyah ? 'Remove Favorite' : 'Add Favorite' }}
            </button>
        </form>
        <a href="#ayah-notes-section" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Add Note</a>
        @if (!empty($randomAyah))
            <a href="{{ route('ayahs.show', [$randomAyah->surah, $randomAyah]) }}" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Random Ayah</a>
        @endif
        <a href="{{ route('ayahs.share-card', [$surah, $ayah]) }}" target="_blank" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Share Card</a>
        <button type="button" onclick="copyAyahLink()" class="block w-full rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-left text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Link</button>
        <button type="button" onclick="shareAyah()" class="block w-full rounded-xl border border-[#bde6d7] bg-[#eef8f4] px-3 py-2 text-left text-[#0e7c66] transition hover:bg-[#e5f4ee]">Share Ayah</button>
        </div>
    </div>
</details>
<div class="fixed inset-x-4 bottom-4 z-40 rounded-[1.4rem] border border-[#d7c8ae] bg-[#fffaf2]/95 p-2 shadow-[0_18px_40px_rgba(88,67,29,0.16)] backdrop-blur md:hidden">
    <div class="grid grid-cols-4 gap-2">
        <form method="POST" action="{{ route('favorites.ayah.toggle', [$surah, $ayah]) }}">
            @csrf
            <button type="submit" class="w-full rounded-xl px-3 py-2 text-xs font-semibold {{ $isFavoriteAyah ? 'bg-[#eef8f4] text-[#0e7c66]' : 'bg-[#f7f1e8] text-[#5f4a28]' }}">
                Fav
            </button>
        </form>
        <a href="{{ route('notes.index') }}" class="rounded-xl bg-[#f7f1e8] px-3 py-2 text-center text-xs font-semibold text-[#5f4a28]">Notes</a>
        <a href="{{ route('ayahs.share-card', [$surah, $ayah]) }}" target="_blank" class="rounded-xl bg-[#f7f1e8] px-3 py-2 text-center text-xs font-semibold text-[#5f4a28]">Card</a>
        <button type="button" onclick="shareAyah()" class="rounded-xl bg-[#eef8f4] px-3 py-2 text-xs font-semibold text-[#0e7c66]">Share</button>
    </div>
</div>
{{--
</div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info mb-0">
                Tafsir system is ready, but tafsir content has not been imported yet.
                <br>
                <small>ØªÙØ³ÛŒØ± Ú©Ø§ Ø³Ø³Ù¹Ù… ØªÛŒØ§Ø± ÛÛ’ØŒ Ù„ÛŒÚ©Ù† Ø§Ø¨Ú¾ÛŒ tafsir content import/scrape Ù†ÛÛŒÚº Ú©ÛŒØ§ Ú¯ÛŒØ§Û”</small>
            </div>

            @if(isset($availableTafseers) && $availableTafseers->count())
                <div class="mt-3">
                    <strong class="d-block mb-2">Available Tafaseer (Configured):</strong>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($availableTafseers as $tafseer)
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $tafseer->title_urdu }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
--}}
               @if ($ayah->irab_arabic)

<details class="mt-5 group rounded-[1.6rem] border border-[#d3c6af] bg-[#f5eee2] shadow-[0_14px_28px_rgba(73,62,34,0.08)]">

    <summary class="flex cursor-pointer items-center justify-between rounded-[1.6rem] px-5 py-4 text-sm font-semibold uppercase tracking-[0.22em] text-[#0b8667] transition hover:bg-[#fbf4e9]">
        <div class="flex w-full items-center justify-between gap-3">
            <span>I'RAB</span>
            <span class="text-base font-semibold normal-case" dir="rtl" lang="ur">اعراب</span>
        </div>
        <span class="text-xs text-[#0b8667] transition-transform group-open:rotate-180">&#9662;</span>
    </summary>

    <div class="px-5 pb-5 pt-2">

        <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-[#3f8f7e]">Ayah-level Arabic parsing note</p>
        </div>

        <p class="mt-3 text-right text-lg leading-10 text-[#174238] sm:text-xl" dir="rtl" lang="ar">
            {{ $ayah->irab_arabic }}
        </p>

    </div>

</details>

@endif

                @php
    $hasLiveDependencyGraph = filled($liveDependencyGraphSvg ?? null);
@endphp

<details class="mt-4 group rounded-[1.6rem] border border-[#d3c6ae] bg-[#f5ede1] shadow-[0_14px_28px_rgba(80,62,24,0.08)]">
    <summary class="flex cursor-pointer list-none items-center justify-between rounded-[1.6rem] px-5 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-[#0e7c66] transition hover:bg-[#fbf4e9]">
        <div class="flex w-full items-center justify-between gap-3">
            <span>Dependency Graph</span>
            <span class="text-base font-semibold normal-case" dir="rtl" lang="ur">ترکیب</span>
        </div>
        <span class="transition group-open:rotate-180">&#9662;</span>
    </summary>

<div id="dependency-graph-panel" class="border-t border-[#e3d7c4] p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#0e7c66]">Dependency Graph</p>
            <p class="text-xs text-[#8a7450]">Structured local SVG dataset view</p>
        </div>
        <span class="text-xs text-[#8a7450]">{{ $surah->number }}:{{ $ayah->ayah_number }}</span>
    </div>

    @if ($hasLiveDependencyGraph)
        @php
            $style = '';
            if ($graphStyle === 'grayscale') {
                $style = 'filter: grayscale(100%);';
            } elseif ($graphStyle === 'sepia') {
                $style = 'filter: sepia(70%);';
            } elseif ($graphStyle === 'invert') {
                $style = 'filter: invert(75%);';
            }
        @endphp

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="inline-flex items-center gap-2 rounded-full border border-[#d7c6a5] bg-[#fffaf1] px-3 py-1.5 text-[#7b5a2d] shadow-sm">
                <span>Live SVG: {{ $hasLiveDependencyGraph ? 'available' : 'missing' }}</span>
            </div>
        </div>

        <div class="mt-3 rounded-[1.2rem] border border-[#d8c9b0] bg-[#fff8ee] p-3">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0e7c66]">Live Dependency Graph</p>
                <p class="text-xs text-[#8a7450]">Structured dataset view for cleaner, lighter rendering.</p>
            </div>
            @if ($hasLiveDependencyGraph)
                <div class="dependency-live-graph mt-2 overflow-auto rounded-xl border border-[#decfb7] bg-[#fffaf1] p-3">
                    {!! $liveDependencyGraphSvg !!}
                </div>
            @else
                <div class="rounded-xl border border-[#d9c49b] bg-[#fbf3e6] p-3 text-sm text-[#8e5f00]">
                    Structured SVG graph not available for this ayah in the local dataset.
                </div>
            @endif
        </div>

        <div class="mt-4 rounded-[1.4rem] border border-[#b79c6c] bg-[#efe3cb] p-4 shadow-[0_22px_44px_rgba(83,61,24,0.16)]">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-[1.15rem] border border-[#d9c29a] bg-[#fbf3e4] px-4 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.96),0_8px_16px_rgba(98,74,30,0.08)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0c7b62]">Experimental Copy</p>
                    <p class="mt-1 text-sm text-[#6a725d]" dir="rtl" lang="ur">یہ ڈپلیکیٹ ورژن صرف styling اور future experiments کے لئے ہے۔</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-[#b9d8ca] bg-white/80 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#0c7b62] shadow-sm backdrop-blur">
                    <span>Sandbox</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-[#0ea56f]"></span>
                </div>
            </div>

            @if ($hasLiveDependencyGraph)
                <div class="rounded-[1.5rem] border border-[#dcc4a0] bg-[#f5ead6] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.95),0_14px_30px_rgba(78,58,24,0.10)] backdrop-blur">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0b8667]">Experimental Dependency Graph</p>
                            <p class="text-sm text-[#7a6b54]">Same live SVG data, separate surface for visual experiments.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                            <span class="rounded-full border border-[#d4e6dc] bg-[#f2fbf6] px-3 py-1.5 text-[#11775f]">Soft Glass</span>
                            <span class="rounded-full border border-[#dbe7f7] bg-[#f3f8ff] px-3 py-1.5 text-[#345d9d]">Clean Canvas</span>
                        </div>
                    </div>

                    <div class="mb-4 grid gap-3 xl:grid-cols-[1fr_auto]">
                        <div class="rounded-[1rem] border border-[#dcc9a8] bg-[#fff7ea] px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8b6a2f]">Legend</p>
                            <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold">
                                <span id="legend-blue" class="rounded-full border border-[#dbe7f7] bg-[#f3f8ff] px-3 py-1.5 text-[#345d9d]">Blue</span>
                                <span id="legend-red" class="rounded-full border border-[#f0d5cf] bg-[#fff2f0] px-3 py-1.5 text-[#b44934]">Red</span>
                                <span id="legend-green" class="rounded-full border border-[#d8ecd7] bg-[#f1fbf0] px-3 py-1.5 text-[#2f8a42]">Green</span>
                                <span id="legend-purple" class="rounded-full border border-[#e8daf7] bg-[#f7f0ff] px-3 py-1.5 text-[#7a3db4]">Purple</span>
                                <span id="legend-gold" class="rounded-full border border-[#ede2cb] bg-[#fff7e8] px-3 py-1.5 text-[#a56b10]">Gold</span>
                            </div>
                        </div>
                        <div class="rounded-[1rem] border border-[#dcc9a8] bg-[#fff7ea] px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8b6a2f]">Mini Map</p>
                            <div id="experimental-graph-minimap" class="relative mt-2 h-24 w-40 overflow-hidden rounded-xl border border-[#ead8bc] bg-[#fffdf8] p-2 shadow-inner">
                                <div id="experimental-graph-minimap-canvas" class="origin-top-left scale-[0.17]"></div>
                                <div id="experimental-graph-minimap-viewport" class="pointer-events-none absolute hidden rounded border-2 border-[#0e7c66]/70 bg-[#0e7c66]/10"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-[#d6e4da] bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-[#4e5b57] shadow-sm">
                            <span id="experimental-graph-zoom-indicator">Interactive Hover Ready</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="experimental-graph-pulse-toggle" class="rounded-full border border-[#d7c6a5] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#6f5c37] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Pulse On</button>
                            <button type="button" id="experimental-graph-focus-toggle" class="rounded-full border border-[#dbe7f7] bg-[#f3f8ff] px-3 py-1.5 text-xs font-semibold text-[#345d9d] shadow-sm transition hover:bg-[#eaf2ff]">Focus Mode</button>
                            <button type="button" id="experimental-graph-share" class="rounded-full border border-[#dbe7f7] bg-[#f3f8ff] px-3 py-1.5 text-xs font-semibold text-[#345d9d] shadow-sm transition hover:bg-[#eaf2ff]">Download PNG Card</button>
                        </div>
                    </div>

                    <div id="experimental-graph-shell" class="rounded-[1.45rem] border border-[#cfb184] bg-[#ead9b8] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.95),0_16px_36px_rgba(92,67,26,0.14)] transition">
                        <div id="experimental-graph-stage" class="dependency-live-graph experimental-dependency-live-graph overflow-auto rounded-[1.1rem] border border-[#ead8bc] bg-[#fffaf1] p-4 shadow-[0_16px_30px_rgba(80,63,33,0.10)]">
                            <div id="experimental-graph-canvas" class="origin-top transition duration-200 ease-out">
                                {!! $liveDependencyGraphSvg !!}
                            </div>
                        </div>
                    </div>

                    <div id="experimental-graph-tooltip" class="pointer-events-none fixed z-30 hidden max-w-[16rem] rounded-[1rem] border border-[#d8c8ae] bg-[#fffaf1]/95 px-3 py-2 text-xs text-[#4f4330] shadow-[0_18px_32px_rgba(74,58,26,0.16)] backdrop-blur">
                        <p id="experimental-graph-tooltip-title" class="text-sm font-semibold text-[#0b8667]"></p>
                        <p id="experimental-graph-tooltip-subtitle" class="mt-1 text-[#7a6b54]"></p>
                        <p id="experimental-graph-tooltip-meta" class="mt-1 text-[#8b6a2f]"></p>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-[#d9c49b] bg-[#fbf3e6] p-3 text-sm text-[#8e5f00]">
                    Experimental copy is waiting because the live SVG graph is not available for this ayah.
                </div>
            @endif
        </div>
    @else
        <div class="mt-3 rounded-xl border border-[#d9c49b] bg-[#fbf3e6] p-3 text-sm text-[#8e5f00]">
            Dependency graph not available yet for this ayah in the local structured source.
        </div>
    @endif
</div>
</details>

<script>
    (function () {
        const canvas = document.getElementById('experimental-graph-canvas');
        const shell = document.getElementById('experimental-graph-shell');
        const stage = document.getElementById('experimental-graph-stage');
        const pulseButton = document.getElementById('experimental-graph-pulse-toggle');
        const focusButton = document.getElementById('experimental-graph-focus-toggle');
        const shareButton = document.getElementById('experimental-graph-share');
        const indicator = document.getElementById('experimental-graph-zoom-indicator');
        const tooltip = document.getElementById('experimental-graph-tooltip');
        const tooltipTitle = document.getElementById('experimental-graph-tooltip-title');
        const tooltipSubtitle = document.getElementById('experimental-graph-tooltip-subtitle');
        const tooltipMeta = document.getElementById('experimental-graph-tooltip-meta');
        const minimap = document.getElementById('experimental-graph-minimap');
        const minimapCanvas = document.getElementById('experimental-graph-minimap-canvas');
        const minimapViewport = document.getElementById('experimental-graph-minimap-viewport');
        const wordRows = Array.from(document.querySelectorAll('[data-graph-word]'));
        const legendBlue = document.getElementById('legend-blue');
        const legendRed = document.getElementById('legend-red');
        const legendGreen = document.getElementById('legend-green');
        const legendPurple = document.getElementById('legend-purple');
        const legendGold = document.getElementById('legend-gold');

        if (!canvas || !shell || !stage) {
            return;
        }

        const svg = canvas.querySelector('svg');
        let pulseEnabled = true;
        let focusEnabled = false;
        let lockedClusterId = null;
        const interactiveSelector = 'text, circle, path, polygon, rect, line';
        const interactiveNodes = svg ? Array.from(svg.querySelectorAll(interactiveSelector)) : [];

        function normalizeArabic(value) {
            return (value || '').replace(/[\u0640\u064B-\u065F\u0670\u06D6-\u06ED\s()\[\]{}*]/g, '').trim();
        }

        function findMatchedRow(key, word) {
            if (!key && !word) return null;

            const normalizedWord = normalizeArabic(word);

            return wordRows.find((row) => {
                const rowKey = row.dataset.graphWord || '';
                const rowArabic = normalizeArabic(row.dataset.graphWordArabic || '');

                return rowKey === key
                    || rowArabic === key
                    || (key && rowKey && (rowKey.includes(key) || key.includes(rowKey)))
                    || (normalizedWord && rowArabic && (rowArabic.includes(normalizedWord) || normalizedWord.includes(rowArabic)))
                    || (normalizedWord && rowKey && (rowKey.includes(normalizedWord) || normalizedWord.includes(rowKey)));
            }) || null;
        }

        function categorizeColor(color) {
            const value = (color || '').toLowerCase();

            if (!value) return null;
            if (value.includes('rgb(52, 93, 157)') || value.includes('rgb(78, 136, 255)') || value.includes('#345d9d') || value.includes('#4e88ff')) return 'blue';
            if (value.includes('rgb(180, 73, 52)') || value.includes('rgb(214, 69, 51)') || value.includes('#b44934') || value.includes('#d64533')) return 'red';
            if (value.includes('rgb(47, 138, 66)') || value.includes('rgb(37, 148, 83)') || value.includes('#2f8a42') || value.includes('#259453')) return 'green';
            if (value.includes('rgb(122, 61, 180)') || value.includes('rgb(137, 53, 190)') || value.includes('#7a3db4') || value.includes('#8935be')) return 'purple';
            if (value.includes('rgb(165, 107, 16)') || value.includes('rgb(214, 144, 17)') || value.includes('#a56b10') || value.includes('#d69011')) return 'gold';

            return null;
        }

        function hydrateLegend(tokenNodes) {
            const counts = { blue: 0, red: 0, green: 0, purple: 0, gold: 0 };

            tokenNodes.forEach((node) => {
                const computed = window.getComputedStyle(node);
                const category = categorizeColor(node.getAttribute('fill') || computed.fill || '');
                if (category) counts[category] += 1;
            });

            if (legendBlue) legendBlue.textContent = `Blue: ${counts.blue} noun/name`;
            if (legendRed) legendRed.textContent = `Red: ${counts.red} particle/jar`;
            if (legendGreen) legendGreen.textContent = `Green: ${counts.green} verb`;
            if (legendPurple) legendPurple.textContent = `Purple: ${counts.purple} description`;
            if (legendGold) legendGold.textContent = `Gold: ${counts.gold} connector`;
        }

        function getBounds(element) {
            try {
                return element.getBBox();
            } catch (error) {
                return null;
            }
        }

        function buildClusters() {
            if (!svg) return [];

            const subGraphs = Array.from(svg.querySelectorAll(':scope > svg[data-ready], :scope > svg')).filter((node) => node !== svg);
            const graphScopes = subGraphs.length ? subGraphs : [svg];
            const clusters = [];

            graphScopes.forEach((graphScope, scopeIndex) => {
                const scopeNodes = Array.from(graphScope.querySelectorAll(interactiveSelector));
                const textNodes = Array.from(graphScope.querySelectorAll('text'));
                const tokenNodes = textNodes.filter((node) => {
                    const fontSize = parseFloat(node.getAttribute('font-size') || '0');
                    const text = (node.textContent || '').trim();
                    return fontSize >= 34 && /[\u0600-\u06FF]/.test(text) && text !== '(*)' && text !== '(' && text !== ')';
                });

                tokenNodes.forEach((node, index) => {
                const bounds = getBounds(node);
                const previousNode = tokenNodes[index - 1] || null;
                const nextNode = tokenNodes[index + 1] || null;
                const previousBounds = previousNode ? getBounds(previousNode) : null;
                const nextBounds = nextNode ? getBounds(nextNode) : null;
                const centerX = bounds ? bounds.x + bounds.width / 2 : 0;
                const centerY = bounds ? bounds.y + bounds.height / 2 : 0;
                const tokenLeft = bounds ? bounds.x : centerX;
                const tokenRight = bounds ? bounds.x + bounds.width : centerX;
                const tokenTop = bounds ? bounds.y : centerY;
                const tokenBottom = bounds ? bounds.y + bounds.height : centerY;
                const word = (node.textContent || '').trim();
                const key = normalizeArabic(word);
                const related = scopeNodes.filter((candidate) => {
                    const candidateBounds = getBounds(candidate);
                    if (!candidateBounds || !bounds) {
                        return candidate === node;
                    }

                    const candidateCenterX = candidateBounds.x + candidateBounds.width / 2;
                    const candidateCenterY = candidateBounds.y + candidateBounds.height / 2;
                    const horizontallyNear = candidateCenterX >= (tokenLeft - 55) && candidateCenterX <= (tokenRight + 55);
                    const overlapsTokenColumn = candidateBounds.x <= (tokenRight + 40) && (candidateBounds.x + candidateBounds.width) >= (tokenLeft - 40);
                    const isBelowToken = candidateBounds.y >= (tokenTop - 8) && candidateBounds.y <= (tokenBottom + 190);
                    const isSameBand = Math.abs(candidateCenterY - centerY) <= 26 && candidateBounds.width <= (bounds.width + 26);
                    const isTinyMarker = candidateBounds.width <= 16 && candidateBounds.height <= 16 && Math.abs(candidateCenterX - centerX) <= 28;
                    const isEdgeLabel = candidate.tagName.toLowerCase() === 'text' && parseFloat(candidate.getAttribute('font-size') || '0') <= 18;
                    const isConnectorShape = ['path', 'polygon', 'line', 'rect'].includes(candidate.tagName.toLowerCase());
                    const corridorTop = tokenBottom - 10;
                    const corridorBottom = tokenBottom + 155;

                    const inAdjacentCorridor = [previousBounds, nextBounds].some((neighborBounds) => {
                        if (!neighborBounds) {
                            return false;
                        }

                        const neighborCenterX = neighborBounds.x + neighborBounds.width / 2;
                        const left = Math.min(centerX, neighborCenterX) - 22;
                        const right = Math.max(centerX, neighborCenterX) + 22;

                        return candidateCenterX >= left
                            && candidateCenterX <= right
                            && candidateCenterY >= corridorTop
                            && candidateCenterY <= corridorBottom;
                    });

                    return candidate === node
                        || isTinyMarker
                        || isSameBand
                        || (isEdgeLabel && ((horizontallyNear && isBelowToken) || inAdjacentCorridor))
                        || (isConnectorShape && (inAdjacentCorridor || (overlapsTokenColumn && candidateCenterY >= corridorTop && candidateCenterY <= corridorBottom)));
                });

                const matchedRow = findMatchedRow(key, word);
                const detailLabels = Array.from(graphScope.querySelectorAll('text')).filter((candidate) => {
                    if (candidate === node) return false;
                    const candidateBounds = getBounds(candidate);
                    return candidateBounds
                        && bounds
                        && candidateBounds.x >= (tokenLeft - 26)
                        && (candidateBounds.x + candidateBounds.width) <= (tokenRight + 26)
                        && candidateBounds.y >= (tokenBottom - 6)
                        && candidateBounds.y <= (tokenBottom + 76)
                        && parseFloat(candidate.getAttribute('font-size') || '0') <= 20;
                }).map((candidate) => (candidate.textContent || '').trim()).filter(Boolean);

                clusters.push({
                    id: 'cluster-' + scopeIndex + '-' + index,
                    word,
                    key,
                    node,
                    related,
                    matchedRow,
                    scope: graphScope,
                    scopeNodes,
                    detailLabels,
                });
            });
            });

            return clusters;
        }

        const clusters = buildClusters();

        function clearState() {
            interactiveNodes.forEach((node) => {
                node.classList.remove('experimental-graph-hovered', 'experimental-graph-pulse', 'experimental-graph-connected');
                node.style.opacity = '1';
            });

            wordRows.forEach((row) => {
                row.classList.remove('ring-2', 'ring-[#0e7c66]', 'bg-[#eef8f4]');
            });
        }

        function setIndicatorText(text) {
            if (indicator) indicator.textContent = text;
        }

        function positionTooltip(cluster) {
            if (!tooltip || !cluster || !cluster.node) return;

            const rect = cluster.node.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const margin = 8;
            const preferredLeft = rect.right + margin;
            const preferredTop = rect.top + Math.max(0, (rect.height - tooltipRect.height) / 2);
            const maxLeft = window.innerWidth - tooltipRect.width - margin;
            const maxTop = window.innerHeight - tooltipRect.height - margin;

            let left = Math.min(preferredLeft, maxLeft);
            let top = Math.min(preferredTop, maxTop);

            if (left < margin) left = margin;
            if (top < margin) top = margin;

            if (rect.right + tooltipRect.width + margin > window.innerWidth) {
                left = Math.max(margin, rect.left - tooltipRect.width - margin);
            }

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        }

        function showTooltip(event, cluster) {
            if (!tooltip || !tooltipTitle || !tooltipSubtitle || !tooltipMeta) return;

            tooltipTitle.textContent = cluster.word;
            tooltipSubtitle.textContent = cluster.matchedRow
                ? ((cluster.matchedRow.dataset.graphWordTransliteration || '').trim() || (cluster.matchedRow.dataset.graphWordArabic || '').trim() || 'Matched ayah word')
                : ((cluster.detailLabels || []).slice(0, 2).join(' • ') || 'Graph token');
            tooltipMeta.textContent = cluster.matchedRow
                ? ((cluster.matchedRow.dataset.graphWordTranslation || '').trim() || (cluster.matchedRow.dataset.graphWordArabic || '').trim() || 'Ayah word row matched')
                : ((cluster.detailLabels || []).slice(2, 4).join(' • ') || 'No direct word-row match');

            tooltip.classList.remove('hidden');
            positionTooltip(cluster);
        }

        function hideTooltip() {
            if (tooltip) tooltip.classList.add('hidden');
        }

        function activateCluster(cluster, locked) {
            clearState();

            cluster.related.forEach((node) => {
                node.classList.add('experimental-graph-hovered', 'experimental-graph-connected');
                if (pulseEnabled) {
                    node.classList.add('experimental-graph-pulse');
                }
            });

            if (focusEnabled) {
                interactiveNodes.forEach((node) => {
                    if (cluster.scopeNodes.includes(node) && !cluster.related.includes(node)) {
                        node.style.opacity = '0.2';
                    }
                });
            }

            if (cluster.matchedRow) {
                cluster.matchedRow.classList.add('ring-2', 'ring-[#0e7c66]', 'bg-[#eef8f4]');
                if (locked) {
                    cluster.matchedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            setIndicatorText(locked ? ('Locked: ' + cluster.word) : ('Hover: ' + cluster.word));
        }

        function updateMinimapViewport() {
            if (!minimap || !minimapViewport || !stage) return;

            const contentWidth = Math.max(stage.scrollWidth, stage.clientWidth);
            const contentHeight = Math.max(stage.scrollHeight, stage.clientHeight);
            const availableWidth = minimap.clientWidth - 16;
            const availableHeight = minimap.clientHeight - 16;
            const scale = Math.min(availableWidth / contentWidth, availableHeight / contentHeight);

            minimapViewport.classList.remove('hidden');
            minimapViewport.style.left = (8 + stage.scrollLeft * scale) + 'px';
            minimapViewport.style.top = (8 + stage.scrollTop * scale) + 'px';
            minimapViewport.style.width = Math.max(18, stage.clientWidth * scale) + 'px';
            minimapViewport.style.height = Math.max(14, stage.clientHeight * scale) + 'px';
        }

        interactiveNodes.forEach((node) => {
            node.style.transition = 'transform 180ms ease, filter 180ms ease, opacity 180ms ease, stroke-width 180ms ease';
            node.style.transformOrigin = 'center';
        });

        setIndicatorText('Interactive Hover Ready');

        hydrateLegend(clusters.map((cluster) => cluster.node));

        if (minimapCanvas && canvas.innerHTML) {
            const previewSvg = svg.cloneNode(true);
            previewSvg.removeAttribute('width');
            previewSvg.removeAttribute('height');
            previewSvg.setAttribute('width', '100%');
            previewSvg.setAttribute('height', '100%');
            previewSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            previewSvg.querySelectorAll('*').forEach((node) => node.setAttribute('pointer-events', 'none'));
            minimapCanvas.innerHTML = '';
            minimapCanvas.appendChild(previewSvg);
            updateMinimapViewport();
        }

        stage.addEventListener('scroll', updateMinimapViewport);
        window.addEventListener('resize', updateMinimapViewport);

        clusters.forEach((cluster) => {
            cluster.node.addEventListener('mouseenter', function (event) {
                if (lockedClusterId !== null) return;
                activateCluster(cluster, false);
                showTooltip(event, cluster);
            });

            cluster.node.addEventListener('mousemove', function (event) {
                if (!tooltip || tooltip.classList.contains('hidden')) return;
                positionTooltip(cluster);
            });

            cluster.node.addEventListener('mouseleave', function () {
                if (lockedClusterId !== null) return;
                clearState();
                hideTooltip();
                setIndicatorText(pulseEnabled ? 'Interactive Hover Ready' : 'Hover Ready');
            });

            cluster.node.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (lockedClusterId === cluster.id) {
                    lockedClusterId = null;
                    clearState();
                    hideTooltip();
                    setIndicatorText(pulseEnabled ? 'Interactive Hover Ready' : 'Hover Ready');
                    return;
                }

                lockedClusterId = cluster.id;
                activateCluster(cluster, true);
                showTooltip(event, cluster);
            });
        });

        document.addEventListener('click', function () {
            lockedClusterId = null;
            clearState();
            hideTooltip();
            setIndicatorText(pulseEnabled ? 'Interactive Hover Ready' : 'Hover Ready');
        });

        if (pulseButton) {
            pulseButton.addEventListener('click', function () {
                pulseEnabled = !pulseEnabled;
                pulseButton.textContent = pulseEnabled ? 'Pulse On' : 'Pulse Off';
                setIndicatorText(pulseEnabled ? 'Pulse Highlights Enabled' : 'Pulse Highlights Disabled');
            });
        }

        if (focusButton) {
            focusButton.addEventListener('click', function () {
                focusEnabled = !focusEnabled;
                shell.classList.toggle('ring-2');
                shell.classList.toggle('ring-[#7dcfb3]');
                shell.classList.toggle('shadow-[0_0_0_6px_rgba(125,207,179,0.18),inset_0_1px_0_rgba(255,255,255,0.95)]');
                focusButton.textContent = focusEnabled ? 'Focus Active' : 'Focus Mode';

                interactiveNodes.forEach((node) => {
                    node.style.opacity = '1';
                });

                setIndicatorText(focusEnabled ? 'Focus Mode Enabled' : 'Interactive Hover Ready');
            });
        }

        if (shareButton && svg) {
            shareButton.addEventListener('click', function () {
                const cardCanvas = document.createElement('canvas');
                const serialized = new XMLSerializer().serializeToString(svg);
                const encoded = window.btoa(unescape(encodeURIComponent(serialized)));
                const image = new Image();

                image.onload = function () {
                    const graphWidth = Math.max(1200, image.width || 1200);
                    const graphHeight = Math.max(520, image.height || 520);
                    const padding = 48;
                    const headerHeight = 132;

                    cardCanvas.width = graphWidth + padding * 2;
                    cardCanvas.height = graphHeight + headerHeight + padding * 2;

                    const context = cardCanvas.getContext('2d');
                    if (!context) return;

                    context.fillStyle = '#f5ead6';
                    context.fillRect(0, 0, cardCanvas.width, cardCanvas.height);

                    context.fillStyle = '#fff8ee';
                    context.strokeStyle = '#d7c3a0';
                    context.lineWidth = 3;
                    context.beginPath();
                    context.roundRect(24, 24, cardCanvas.width - 48, cardCanvas.height - 48, 28);
                    context.fill();
                    context.stroke();

                    context.fillStyle = '#0b8667';
                    context.font = '700 30px Segoe UI';
                    context.fillText('Dependency Graph Share Card', padding, 78);

                    context.fillStyle = '#6f5c37';
                    context.font = '600 24px Segoe UI';
                    context.fillText('Surah {{ $surah->number }} - {{ $surah->name_english }} | Ayah {{ $ayah->ayah_number }}', padding, 114);

                    context.fillStyle = '#8b6a2f';
                    context.font = '20px Segoe UI';
                    context.fillText('Generated from the live structured dependency graph', padding, 146);

                    context.drawImage(image, padding, headerHeight + padding, graphWidth, graphHeight);

                    const link = document.createElement('a');
                    link.href = cardCanvas.toDataURL('image/png');
                    link.download = 'dependency-graph-card-{{ $surah->number }}-{{ $ayah->ayah_number }}.png';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();

                    setIndicatorText('PNG share card downloaded');
                };

                image.src = 'data:image/svg+xml;base64,' + encoded;
            });
        }
    })();
</script>

<style>
    #experimental-graph-minimap {
        position: relative;
    }

    #experimental-graph-minimap-canvas {
        height: 100%;
        width: 100%;
        overflow: hidden;
    }

    #experimental-graph-minimap-canvas svg {
        display: block;
        height: 100%;
        width: 100%;
    }

    .experimental-dependency-live-graph svg text:hover,
    .experimental-dependency-live-graph svg circle:hover,
    .experimental-dependency-live-graph svg path:hover,
    .experimental-dependency-live-graph svg polygon:hover,
    .experimental-dependency-live-graph svg rect:hover,
    .experimental-dependency-live-graph svg line:hover,
    .experimental-graph-hovered {
        filter:
            drop-shadow(0 0 12px rgba(28, 112, 255, 0.28))
            drop-shadow(0 16px 14px rgba(17, 24, 39, 0.42));
        transform: scale(1.04);
        cursor: pointer;
    }

    .experimental-dependency-live-graph svg text:hover,
    .experimental-dependency-live-graph svg .experimental-graph-hovered {
        text-shadow:
            0 12px 12px rgba(17, 24, 39, 0.34),
            0 2px 2px rgba(17, 24, 39, 0.18);
    }

    .experimental-graph-connected {
        stroke-width: 2.4px;
    }

    .experimental-graph-pulse {
        animation: experimentalGraphPulse 1.1s ease-in-out infinite;
    }

    @keyframes experimentalGraphPulse {
        0%, 100% {
            filter: drop-shadow(0 0 0 rgba(14, 124, 102, 0));
        }
        50% {
            filter: drop-shadow(0 0 14px rgba(14, 124, 102, 0.35));
        }
    }
</style>

            </div>
<section id="ayah-notes-section" class="mt-8 rounded-[1.9rem] border border-[#d7c8ae] bg-[#fff9ef] p-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#0b8667]">Personal Notes</p>
            <h2 class="mt-1 text-2xl font-semibold text-[#15211c]" dir="rtl" lang="ur">ذاتی نوٹس</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('notes.ayah.store', [$surah, $ayah]) }}" class="mt-4 space-y-4">
        @csrf
        <textarea
            name="note"
            rows="5"
            class="w-full rounded-[1.4rem] border border-[#d8c9b0] bg-[#fffdf8] px-4 py-3 text-sm leading-7 text-stone-800 shadow-inner focus:border-[#0d8a6e] focus:outline-none"
            placeholder="Write your personal note for this ayah..."
        >{{ old('note', $ayahNote) }}</textarea>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="submit"
                class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-5 py-2 text-sm font-semibold text-white shadow-[0_14px_24px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_18px_28px_rgba(13,138,110,0.28)]"
            >
                Save Note
            </button>
            @if (session('note_status'))
                <p class="text-sm font-medium text-[#0e7c66]">{{ session('note_status') }}</p>
            @endif
        </div>
    </form>
</section>
            <details class="mt-8 group rounded-[1.7rem] border border-[#d9c49b] bg-[#f7efe1] shadow-[0_18px_36px_rgba(109,84,34,0.10)] transition open:shadow-[0_22px_42px_rgba(109,84,34,0.14)]">

    <summary class="flex cursor-pointer items-center justify-between gap-3 px-6 py-4 text-sm font-semibold tracking-[0.2em] text-[#9a6513]">
        <span class="flex items-center gap-2">
            <span aria-hidden="true">&#9888;</span>
            Report Error / Suggest Improvement
        </span>
        <span class="text-xs text-[#b56f00] transition group-open:rotate-180">&#9662;</span>
    </summary>


    <div class="px-6 pb-6 space-y-6">


        {{-- Disclaimer --}}
        <div class="space-y-4 text-sm leading-7 text-stone-700">

            <p dir="rtl" lang="ur">
ہم نے پوری کوشش کی ہے کہ اس کام میں کسی قسم کی کوئی غلطی نہ رہے۔ لیکن اگر آپ کو کسی بھی نوعیت کی کوئی غلطی نظر آئے تو ہم سب سے پہلے اللہ تعالیٰ سے معافی کے طلبگار ہیں، اور اس کے بعد آپ سب سے بھی معذرت خواہ ہیں۔ اگر آپ کو کوئی غلطی، کمی، یا بہتری کی گنجائش محسوس ہو تو براہ کرم ہمیں تفصیل کے ساتھ آگاہ کریں تاکہ ہم اس کا جائزہ لے کر اسے درست کر سکیں۔
            </p>

            <p>
We have made our best effort to ensure that this work is as accurate as possible. However, if you notice any mistake of any kind, we first seek forgiveness from Allah, and then we apologize to you as well. If you find any error or improvement opportunity, please inform us in detail so that we can review and correct it.
            </p>

        </div>


        {{-- Success message --}}
        @if (session('feedback_success'))
            <div class="rounded-xl border border-[#b7e8d9] bg-[#eef8f4] px-4 py-3 text-sm text-[#0d7f65]">
                {{ session('feedback_success') }}
            </div>
        @endif


        {{-- Error messages --}}
        @if ($errors->any())
            <div class="rounded-xl border border-[#ecc7cf] bg-[#fdf2f4] px-4 py-3 text-sm text-[#bf3458]">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Suggestion Form --}}
        <form method="POST" action="{{ route('ayahs.feedback.store', [$surah, $ayah]) }}" class="space-y-4">

            @csrf

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Name</label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full rounded-xl border border-[#d8c9b0] bg-[#fffdf8] px-3 py-2 text-sm shadow-inner focus:border-[#0d8a6e] focus:outline-none"
                        placeholder="Your name"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Email</label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full rounded-xl border border-[#d8c9b0] bg-[#fffdf8] px-3 py-2 text-sm shadow-inner focus:border-[#0d8a6e] focus:outline-none"
                        placeholder="you@example.com"
                    >
                </div>

            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">
                    Comment / Suggestion
                </label>

                <textarea
                    name="comment"
                    rows="5"
                    class="w-full rounded-xl border border-[#d8c9b0] bg-[#fffdf8] px-3 py-2 text-sm shadow-inner focus:border-[#0d8a6e] focus:outline-none"
                    placeholder="Please describe the issue or suggestion in detail..."
                    required
                >{{ old('comment') }}</textarea>
            </div>


            <div>

                <button
                    type="submit"
                    class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-5 py-2 text-sm font-semibold text-white shadow-[0_14px_24px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_18px_28px_rgba(13,138,110,0.28)]"
                >
                    Submit Suggestion
                </button>

            </div>

        </form>


        {{-- Public comments --}}
        @if (!empty($feedbackItems) && $feedbackItems->count())

            <div class="pt-4 space-y-4">

                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-600">
                    Public Suggestions
                </p>


                @foreach ($feedbackItems as $item)

                    <div class="rounded-2xl border border-[#dfd2bc] bg-[#fbf5eb] px-4 py-4 shadow-sm">

                        <div class="flex flex-wrap items-center justify-between gap-2">

                            <p class="text-sm font-semibold text-[#4f3f27]">
                                {{ $item->name ?: 'Anonymous User' }}
                            </p>

                            <p class="text-xs text-[#8d7d65]">
                                {{ $item->created_at->format('d M Y, h:i A') }}
                            </p>

                        </div>


                        <p class="mt-3 text-sm leading-7 text-[#665947] whitespace-pre-line">
                            {{ $item->comment }}
                        </p>


                        @if ($item->admin_reply)

                            <div class="mt-4 rounded-xl border border-[#cfe4dc] bg-[#eef6f2] px-4 py-3">

                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">
                                    Reply from Team
                                </p>

                                <p class="mt-2 text-sm leading-7 text-[#24463d] whitespace-pre-line">
                                    {{ $item->admin_reply }}
                                </p>

                            </div>

                        @endif

                    </div>

                @endforeach

            </div>

        @endif

    </div>

</details>
        </div>

        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            @if ($bismillahText)
                <div class="border-b border-[#e2d5bf] bg-[#f2ecdf] px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0e7c66]">Opening Formula</p>
                    <p class="mt-3 text-right text-3xl text-stone-900 sm:text-4xl" dir="rtl" lang="ar">{{ $bismillahText }}</p>
                    <p class="mt-2 text-sm text-[#7d6d55]">Shown separately so the word-by-word rows stay aligned with imported corpus data.</p>
                </div>
            @endif

            @if ($usedWordFallback)
                <div class="border-b border-[#e2d5bf] bg-[#fbf0dc] px-6 py-3 text-sm text-[#8e5f00]">
                    Word mapping fallback was used for this ayah because display text tokens did not fully match imported word rows.
                </div>
            @endif

            <div class="grid gap-4 border-b border-[#e2d5bf] bg-[#f6efe4] px-6 py-4 text-xs font-semibold uppercase tracking-[0.2em] text-[#7d6d55] lg:grid-cols-[1.1fr_1fr_1.4fr]">
                <div>Translation / Transliteration</div>
                <div class="text-right lg:text-center">Arabic Word</div>
                <div>Syntax and Morphology</div>
            </div>

            <div class="divide-y divide-[#eadfcd]">
                @foreach ($wordRows as $row)
                    @php
                        $word = $row['word'];
                        $translation = $word->translation_basic;
                        $graphWordKey = preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\s\(\)\[\]\{\}\*]+/u', '', $row['display_arabic'] ?: ($word->normalized_text ?: '')) ?? '';
                    @endphp
                    <a
                        href="{{ route('words.show', $word) }}"
                        id="ayah-word-row-{{ $word->id }}"
                        data-graph-word="{{ $graphWordKey }}"
                        data-graph-word-id="{{ $word->id }}"
                        data-graph-word-arabic="{{ $row['display_arabic'] }}"
                        data-graph-word-transliteration="{{ $row['display_transliteration'] ?: $word->transliteration ?: $word->form ?: '' }}"
                        data-graph-word-translation="{{ $translation ?: '' }}"
                        class="grid gap-6 px-6 py-6 transition hover:bg-[#f4efe4] lg:grid-cols-[1.1fr_1fr_1.4fr]"
                    >
                        <div class="space-y-3">
                            <p class="text-sm text-[#9b8b72]">({{ $surah->number }}:{{ $ayah->ayah_number }}:{{ $word->position }})</p>
                            <div>
                                <p class="text-lg font-medium text-[#0e7c66]">{{ $row['display_transliteration'] ?: $word->transliteration ?: $word->form ?: 'N/A' }}</p>
                                @if ($translation)
                                    <p class="mt-2 text-base leading-7 text-stone-800">{{ $translation }}</p>
                                @else
                                    <p class="mt-2 text-sm leading-7 text-[#8d7d65]">English word translation not imported yet.</p>
                                @endif
                                @if ($word->translation_urdu)
                                    <p class="mt-2 rounded-[1.15rem] border border-[#eadfce] bg-[#f7f1e8] px-3 py-2 text-right text-base leading-8 text-[#5f5343]" dir="rtl" lang="ur">{{ $word->translation_urdu }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4 text-center lg:border-x lg:border-[#e2d5bf] lg:px-6">
                            @php
                                $renderedSegments = collect($row['display_segments'])
                                    ->map(fn ($displaySegment) => '<span class="' . e($displaySegment['classes']) . '">' . e($displaySegment['text']) . '</span>')
                                    ->implode('');
                            @endphp
                            <p class="min-h-[4.5rem] rounded-[1.4rem] border border-[#e8dcc8] bg-[#f7f1e8] px-4 py-4 text-5xl leading-[1.2] shadow-[inset_0_1px_0_rgba(255,255,255,0.88)] sm:min-h-[5.25rem] sm:text-6xl" dir="rtl" lang="ar">
                                {!! $renderedSegments !== '' ? $renderedSegments : '<span class="text-sky-700">' . e($row['display_arabic'] ?: '...') . '</span>' !!}
                            </p>
                            <div class="mx-auto flex w-fit flex-wrap items-start justify-center gap-2" dir="rtl">
                                @foreach ($row['segments'] as $segment)
                                    <span @class([
                                        'inline-flex min-w-[4.5rem] flex-col items-center rounded-xl border px-2.5 py-1.5 text-center',
                                        $segment['compact_chip_classes'],
                                    ])>
                                        <span class="text-[0.7rem] font-semibold leading-none">{{ $segment['morphology']->pos_tag }}</span>
                                        <span class="mt-1 text-[0.6rem] leading-tight" dir="rtl" lang="ar">{{ $segment['chip_arabic_label'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-sm text-[#8d7d65]" dir="rtl" lang="ar">{{ $row['display_arabic'] ?: ($word->normalized_text ?: $row['display_transliteration'] ?: $word->form) }}</p>
                        </div>

                        <div class="space-y-3">
                            @forelse ($row['segments'] as $segment)
                                @php($morphology = $segment['morphology'])
                                @php($explanation = $segment['explanation'])
                                <div class="rounded-2xl border px-4 py-3 shadow-sm {{ $segment['card_classes'] }}">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-1.5">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $segment['chip_classes'] }}">{{ $morphology->pos_tag }}</span>
                                                <span class="text-sm font-medium text-stone-900">{{ $explanation['english'] }}</span>
                                            </div>
                                            <p class="text-sm text-[#665947]" dir="rtl" lang="ur">{{ $explanation['urdu'] }}</p>
                                            <p class="text-right text-sm font-semibold text-[#0e7c66]" dir="rtl" lang="ar">{{ $explanation['arabic'] }}</p>
                                            <p class="text-xs text-[#8d7d65]">{{ $explanation['details'] }}</p>
                                        </div>
                                        <dl class="grid gap-2 text-sm sm:min-w-[13rem]">
                                            <div class="flex items-start justify-between gap-3">
                                                <dt class="text-[#9b8b72]">{{ \App\Support\BilingualGrammarService::label('Lemma') }}</dt>
                                                <dd class="text-right text-stone-900" dir="rtl" lang="ar">{{ $explanation['lemma'] }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-3">
                                                <dt class="text-[#9b8b72]">{{ \App\Support\BilingualGrammarService::label('Root') }}</dt>
                                                <dd class="text-right text-stone-900" dir="rtl" lang="ar">{{ $explanation['root'] }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-[#e2d5bf] bg-[#f6efe4] px-4 py-4 text-sm text-[#8d7d65]">
                                    No non-determiner morphology analysis is available for this word yet.
                                </div>
                            @endforelse
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>
