<x-layouts.app title="Notes | Quran Study">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#0e7c66]">Notes</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-900 sm:text-4xl">Personal Notes</h1>
            <p class="mt-2 text-sm text-[#7d6d55]" dir="rtl" lang="ur">یہاں آپ کے محفوظ کیے گئے ذاتی نوٹس موجود ہیں۔</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Ayah Notes</p>
                        <h2 class="mt-1 text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">آیات کے نوٹس</h2>
                    </div>
                    <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $ayahs->count() }}</span>
                </div>

                @if ($ayahs->count())
                    <div class="mt-5 space-y-3">
                        @foreach ($ayahs as $ayah)
                            <a href="{{ route('ayahs.show', [$ayah->surah, $ayah]) }}" class="block rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                                <p class="text-sm font-semibold text-[#0e7c66]">Surah {{ $ayah->surah->number }}, Ayah {{ $ayah->ayah_number }}</p>
                                <p class="mt-2 text-right text-2xl text-stone-900" dir="rtl" lang="ar">{{ $ayah->display_text }}</p>
                                <p class="mt-3 text-right text-sm leading-7 text-[#665947]" dir="rtl" lang="ur">{{ $ayah->personal_note }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 text-sm text-[#7d6d55]">
                        No ayah notes saved yet.
                    </div>
                @endif
            </section>

            <section class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#0e7c66]">Word Notes</p>
                        <h2 class="mt-1 text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">الفاظ کے نوٹس</h2>
                    </div>
                    <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $words->count() }}</span>
                </div>

                @if ($words->count())
                    <div class="mt-5 space-y-3">
                        @foreach ($words as $word)
                            <a href="{{ route('words.show', $word) }}" class="block rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                                <p class="text-sm font-semibold text-[#0e7c66]">Surah {{ $word->surah_number }}, Ayah {{ $word->ayah_number }}, Position {{ $word->position }}</p>
                                <p class="mt-2 text-right text-2xl text-stone-900" dir="rtl" lang="ar">{{ $word->display_form }}</p>
                                <p class="mt-3 text-right text-sm leading-7 text-[#665947]" dir="rtl" lang="ur">{{ $word->personal_note }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 text-sm text-[#7d6d55]">
                        No word notes saved yet.
                    </div>
                @endif
            </section>
        </div>
    </section>
</x-layouts.app>
