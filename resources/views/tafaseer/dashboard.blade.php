<x-layouts.app :title="'Tafaseer Dashboard | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#0e7c66]">Tafaseer Dashboard</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-900 sm:text-4xl">Import progress aur configured tafasir ki sari details</h1>
            <div class="mt-3 space-y-2 text-sm text-[#7d6d55]">
                <p>Track imported counts, remaining ayat, completion percent, and the latest imported ayah for each tafsir.</p>
                <p dir="rtl" lang="ur">یہاں آپ ہر تفسیر کی امپورٹ شدہ آیات، باقی آیات، تکمیل کی شرح، اور آخری امپورٹ شدہ آیت ایک جگہ دیکھ سکتے ہیں۔</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-[1.8rem] border border-[#d7c8ae] bg-[#fffaf2] px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Total Ayahs</p>
                <p class="mt-3 text-3xl font-semibold text-stone-900">{{ number_format($totalAyahs) }}</p>
            </section>
            <section class="rounded-[1.8rem] border border-[#d7c8ae] bg-[#fffaf2] px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Active Tafaseer</p>
                <p class="mt-3 text-3xl font-semibold text-stone-900">{{ number_format($activeTafseerCount) }}</p>
            </section>
            <section class="rounded-[1.8rem] border border-[#d7c8ae] bg-[#fffaf2] px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Imported Entries</p>
                <p class="mt-3 text-3xl font-semibold text-stone-900">{{ number_format($totalImportedEntries) }}</p>
            </section>
            <section class="rounded-[1.8rem] border border-[#d7c8ae] bg-[#fffaf2] px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">Overall Completion</p>
                <p class="mt-3 text-3xl font-semibold text-stone-900">{{ $overallCompletion }}%</p>
                <p class="mt-2 text-sm text-[#7d6d55]">{{ $completedTafseerCount }} complete / {{ $activeTafseerCount }} active</p>
            </section>
        </div>

        <section class="rounded-[1.9rem] border border-[#d7c8ae] bg-[#fff9ef] p-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#0b8667]">Resume Command</p>
                    <h2 class="mt-1 text-2xl font-semibold text-stone-900">Continue import from where it stopped</h2>
                </div>
                <span class="rounded-full border border-[#d8c49f] bg-[#fbf3e6] px-3 py-1 text-xs font-semibold text-[#7c5c31]">Safe Resume</span>
            </div>
            <div class="mt-4 rounded-[1.4rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4 shadow-inner">
                <code class="block overflow-x-auto text-sm text-[#5f4a28]">php artisan app:import-all-tafaseer --fromSurah=1 --toSurah=114</code>
                <p class="mt-3 text-sm text-[#7d6d55]">Use this without <code>--update</code> to skip already imported entries and continue the remaining tafasir.</p>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            @forelse ($tafaseer as $item)
                @php
                    $tafseer = $item['tafseer'];
                    $latestEntry = $item['latest_entry'];
                    $progressWidth = max(4, min(100, $item['completion_percent']));
                @endphp
                <article class="rounded-[1.9rem] border border-[#d7c8ae] bg-[#fff9ef] p-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#0e7c66]">{{ $tafseer->slug }}</p>
                            <h2 class="mt-1 text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">{{ $tafseer->title_urdu }}</h2>
                            @if ($tafseer->author)
                                <p class="mt-1 text-sm text-[#7d6d55]" dir="rtl" lang="ur">{{ $tafseer->author }}</p>
                            @endif
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold shadow-sm {{ $item['status'] === 'Complete' ? 'border border-[#9fd9c8] bg-[#eef8f4] text-[#0e7c66]' : ($item['status'] === 'In Progress' ? 'border border-[#f0d28a] bg-[#fff3d8] text-[#8e5f00]' : 'border border-[#e2d5bf] bg-[#f7f1e8] text-[#7c5c31]') }}">
                            {{ $item['status'] }}
                        </span>
                    </div>

                    <div class="mt-4 rounded-[1.35rem] border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-4">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-[#0e7c66]">Completion</span>
                            <span class="font-semibold text-stone-900">{{ $item['completion_percent'] }}%</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-[#eadfce]">
                            <div class="h-full rounded-full bg-[#0e7c66]" style="width: {{ $progressWidth }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.25rem] border border-[#e2d5bf] bg-[#fffdf8] px-4 py-3 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Imported</p>
                            <p class="mt-2 text-2xl font-semibold text-stone-900">{{ number_format($item['imported_count']) }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-[#e2d5bf] bg-[#fffdf8] px-4 py-3 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Remaining</p>
                            <p class="mt-2 text-2xl font-semibold text-stone-900">{{ number_format($item['remaining_count']) }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-[#e2d5bf] bg-[#fffdf8] px-4 py-3 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Source</p>
                            <p class="mt-2 text-sm font-semibold text-stone-900">{{ $tafseer->source_name ?: 'eQuranLibrary' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[1.35rem] border border-[#d8cab2] bg-[#f4ebde] px-4 py-4 shadow-inner">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0b8667]">Latest Imported Ayah</p>
                        @if ($latestEntry && $latestEntry->ayah && $latestEntry->ayah->surah)
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-stone-900">
                                        Surah {{ $latestEntry->ayah->surah->number }}, Ayah {{ $latestEntry->ayah->ayah_number }}
                                    </p>
                                    <p class="mt-1 text-sm text-[#7d6d55]">{{ $latestEntry->ayah->surah->display_title }}</p>
                                </div>
                                <a href="{{ route('ayahs.show', [$latestEntry->ayah->surah, $latestEntry->ayah]) }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">
                                    Open Ayah
                                </a>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-[#7d6d55]">No imported ayah found yet for this tafsir.</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-[1.6rem] border border-[#f0d28a] bg-[#fff3d8] px-5 py-4 text-sm text-[#8e5f00] shadow-[0_10px_24px_rgba(166,116,0,0.08)] xl:col-span-2">
                    <p class="font-medium">No active tafaseer found.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-layouts.app>
