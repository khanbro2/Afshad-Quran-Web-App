<x-layouts.app :title="'Ayah Search | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fffaf2]/95 px-6 py-8 shadow-[0_18px_38px_rgba(88,67,29,0.10)]">
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#0e7c66]">Quran Search</p>
            <h1 class="mt-3 text-3xl font-semibold text-stone-900 sm:text-4xl">Lafz likhein aur dekhein woh kin ayat mein aata hai</h1>
            <div class="mt-3 max-w-3xl space-y-2 text-sm leading-7 text-stone-600">
                <p>You can search Arabic, Urdu, or English words. For Arabic, the app attempts to match both simple and Uthmani text.</p>
                <p dir="rtl" lang="ur">آپ عربی، اردو یا انگریزی لفظ تلاش کر سکتے ہیں۔ عربی کے لیے سادہ اور عثمانی متن دونوں میں مطابقت ڈھونڈنے کی کوشش کی جاتی ہے۔</p>
            </div>

            <form method="GET" action="{{ route('ayahs.search') }}" class="mt-6 rounded-[1.7rem] border border-[#ddd0bb] bg-[#f6efe2] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.88)]">
                <div class="flex flex-col gap-3 md:flex-row md:items-end">
                    <label class="flex-1">
                        <span class="text-xs font-semibold uppercase tracking-[0.22em] text-[#9b7a47]">Search Word</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $queryText }}"
                            placeholder="مثال: الحمد ، کتاب ، رحمت"
                            class="mt-2 w-full rounded-[1.2rem] border border-[#d4c3a8] bg-white/90 px-4 py-3 text-base text-[#243229] shadow-inner outline-none transition focus:border-[#0e7c66]"
                        >
                    </label>
                    <button type="submit" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-5 py-3 text-sm font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">
                        Search
                    </button>
                </div>
            </form>
        </div>

        @if ($queryText !== '' && $results)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.6rem] border border-[#d7c8ae] bg-[#fff9ef] px-5 py-4 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Results</p>
                    <p class="mt-1 text-sm text-stone-600">
                        <span class="font-semibold text-stone-900">{{ $results->total() }}</span>
                        matches found for
                        <span class="font-semibold text-[#0e7c66]">{{ $queryText }}</span>
                    </p>
                </div>
                <p class="text-sm text-[#7d6d55]" dir="rtl" lang="ur">مطلوب لفظ کی تمام ملتی جلتی آیات نیچے دکھائی جا رہی ہیں</p>
            </div>

            <div class="space-y-4">
                @forelse ($results as $result)
                    <article class="rounded-[1.8rem] border border-[#d7ccb8] bg-[#fffaf2] p-5 shadow-[0_14px_30px_rgba(88,67,29,0.08)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Surah {{ $result->surah->number }}, Ayah {{ $result->ayah_number }}</p>
                                <h2 class="mt-2 text-xl font-semibold text-stone-900">{{ $result->surah->display_title }}</h2>
                                <p class="mt-1 text-sm text-stone-500">{{ $result->surah->display_english_name ?: $result->surah->display_transliterated_name }}</p>
                            </div>
                            <a href="{{ route('ayahs.show', [$result->surah, $result]) }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">
                                Open Ayah
                            </a>
                        </div>

                        <div class="mt-5 rounded-[1.5rem] border border-[#e2d5bf] bg-[#f7f1e8] px-5 py-5">
                            <p class="text-right text-3xl leading-loose text-[#171b18] sm:text-4xl" dir="rtl" lang="ar">{{ $result->display_text }}</p>
                        </div>

                        @if ($result->urdu_translation_mufti_taqi || $result->urdu_translation_maududi || $result->english_translation_mufti_taqi)
                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                @if ($result->urdu_translation_mufti_taqi || $result->urdu_translation_maududi)
                                    <div class="rounded-[1.35rem] border border-[#d7c6ff] bg-[#f8f3ff] px-4 py-4 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#7048ff]">Urdu Preview</p>
                                        <p class="mt-3 text-right text-lg leading-9 text-stone-800" dir="rtl" lang="ur">
                                            {{ $result->urdu_translation_mufti_taqi ?: $result->urdu_translation_maududi }}
                                        </p>
                                    </div>
                                @endif

                                @if ($result->english_translation_mufti_taqi)
                                    <div class="rounded-[1.35rem] border border-[#c9d3ff] bg-[#f4f6ff] px-4 py-4 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#4f63d6]">English Preview</p>
                                        <p class="mt-3 text-left text-base leading-8 text-stone-800" dir="ltr" lang="en">
                                            {{ $result->english_translation_mufti_taqi }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-[1.6rem] border border-[#f0d28a] bg-[#fff3d8] px-5 py-4 text-sm text-[#8e5f00] shadow-[0_10px_24px_rgba(166,116,0,0.08)]">
                        <p class="font-medium">Koi ayah nahi mili.</p>
                        <p class="mt-1" dir="rtl" lang="ur">اس لفظ کے لیے کوئی نتیجہ نہیں ملا، کسی اور املا یا لفظ کے ساتھ کوشش کریں۔</p>
                    </div>
                @endforelse
            </div>

            @if ($results->hasPages())
                <div class="rounded-[1.6rem] border border-[#d7c8ae] bg-[#fff9ef] px-5 py-4 shadow-sm">
                    {{ $results->links() }}
                </div>
            @endif
        @elseif ($queryText !== '')
            <div class="rounded-[1.6rem] border border-[#f0d28a] bg-[#fff3d8] px-5 py-4 text-sm text-[#8e5f00] shadow-[0_10px_24px_rgba(166,116,0,0.08)]">
                <p class="font-medium">Koi result nahi mila.</p>
            </div>
        @else
            <div class="rounded-[1.8rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-sm">
                <div class="space-y-2 text-sm leading-7 text-stone-600">
                    <p>Enter a word above to start searching. You can search by Arabic ayah text, Urdu translation, or English translation.</p>
                    <p dir="rtl" lang="ur">تلاش شروع کرنے کے لیے اوپر کوئی لفظ لکھیں۔ آپ عربی آیت کے متن، اردو ترجمہ، یا انگریزی ترجمہ کی بنیاد پر بھی تلاش کر سکتے ہیں۔</p>
                </div>
            </div>
        @endif
    </section>
</x-layouts.app>
