<x-layouts.app :title="'Ayah Themes | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#0e7c66]">Ayah Themes</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-900 sm:text-4xl">Exact themes imported from the dataset</h1>
            <div class="mt-3 space-y-2 text-sm text-[#7d6d55]">
                <p>These themes now follow the SQLite dataset exactly. Whatever topic exists in the source file is shown here as-is.</p>
                <p dir="rtl" lang="ur">یہ موضوعات SQLite dataset کے مطابق دکھائے جا رہے ہیں۔ جہاں صاف اور درست اردو عنوان دستیاب ہے وہاں وہ بھی دکھایا جائے گا۔</p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $theme)
                <a href="{{ route('ayah-themes.show', $theme) }}" class="rounded-[1.9rem] border border-[#d7c8ae] bg-[#fff9ef] p-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)] transition hover:-translate-y-0.5 hover:border-[#0e7c66]">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            @if ($theme->display_title_urdu)
                                <p class="text-right text-xl font-semibold leading-9 text-stone-900" dir="rtl" lang="ur">{{ $theme->display_title_urdu }}</p>
                            @endif
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em]" style="color: {{ $theme->badge_color ?: '#0e7c66' }}">{{ $theme->title_english }}</p>
                        </div>
                        <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $theme->ayahs_count }}</span>
                    </div>
                    @if ($theme->description)
                        <p class="mt-4 text-sm leading-7 text-[#7d6d55]">{{ $theme->description }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.app>
