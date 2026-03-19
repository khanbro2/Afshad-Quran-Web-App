<x-layouts.app :title="'Word ' . $word->id . ' | Quran Study'">
    <section class="space-y-8">
        <div id="word-overview-section" class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium uppercase tracking-[0.25em] text-[#0e7c66]">Word Detail</p>
                    <h1 class="text-4xl font-semibold leading-[1.2] text-stone-900 sm:text-5xl" dir="rtl" lang="ar">{{ $displayArabic ?: 'Word ' . $word->id }}</h1>
                    <p class="text-sm text-[#8d7d65]">{{ $displayTransliteration ?: $word->form ?: 'N/A' }}</p>
                    @if ($displayEnglishTranslation)
                        <p class="text-sm text-stone-700">{{ $displayEnglishTranslation }}</p>
                    @endif
                    @if ($displayUrduTranslation)
                        <p class="rounded-[1.15rem] border border-[#eadfce] bg-[#f7f1e8] px-3 py-2 text-right text-sm text-[#665947]" dir="rtl" lang="ur">{{ $displayUrduTranslation }}</p>
                    @endif
                    <p class="text-sm text-[#8d7d65]">
                        Surah {{ $word->surah_number }}, Ayah {{ $word->ayah_number }}, Position {{ $word->position }}
                    </p>
                    @if ($word->ayah && $word->ayah->surah)
                        <p class="text-sm text-[#8d7d65]">
                            <a href="{{ route('ayahs.show', [$word->ayah->surah, $word->ayah]) }}" class="text-[#0e7c66] hover:text-[#0b6c59]">Back to ayah</a>
                        </p>
                    @endif
                    <div class="flex flex-wrap gap-2 pt-1">
                        <form method="POST" action="{{ route('favorites.word.toggle', $word) }}">
                            @csrf
                            <button type="submit" class="rounded-full border px-3 py-1.5 text-xs font-semibold shadow-sm transition {{ $isFavoriteWord ? 'border-[#0e7c66] bg-[#eef8f4] text-[#0e7c66]' : 'border-[#d6c5a4] bg-[#fffaf1] text-[#7c5c31] hover:border-[#0e7c66] hover:text-[#0e7c66]' }}">
                                {{ $isFavoriteWord ? 'Favorited' : 'Add Favorite' }}
                            </button>
                        </form>
                        <button type="button" onclick="copyWordArabic()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Word</button>
                        <button type="button" onclick="copyWordReference()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Reference</button>
                        <button type="button" onclick="copyWordLink()" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1.5 text-xs font-semibold text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Link</button>
                        <button type="button" onclick="shareWord()" class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-3 py-1.5 text-xs font-semibold text-[#0e7c66] shadow-sm transition hover:bg-[#e5f4ee]">Share</button>
                    </div>
                    @if (session('favorite_status'))
                        <p class="text-xs font-medium text-[#0e7c66]">{{ session('favorite_status') }}</p>
                    @endif
                    <p id="word-copy-status" class="hidden text-xs font-medium text-[#0e7c66]"></p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#9b8b72]">{{ \App\Support\BilingualGrammarService::label('Lemma') }}</p>
                        <p class="mt-2 text-right text-xl text-stone-900" dir="rtl" lang="ar">{{ $word->lemma?->display_lemma_arabic ?: 'N/A' }}</p>
                    </div>
                    <div class="rounded-2xl border border-[#e2d5bf] bg-[#f7f1e8] px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#9b8b72]">{{ \App\Support\BilingualGrammarService::label('Root') }}</p>
                        <p class="mt-2 text-right text-xl text-stone-900" dir="rtl" lang="ar">{{ $word->root?->display_root_arabic ?: 'N/A' }}</p>
                    </div>
                    <div class="rounded-2xl border border-[#d8c49f] bg-[#fbf3e6] px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#9b8b72]">Quran Frequency</p>
                        <p class="mt-2 text-2xl font-semibold text-[#0e7c66]">{{ number_format($occurrenceCount) }}</p>
                        <p class="mt-1 text-sm text-[#665947]" dir="rtl" lang="ur">یہ لفظ قرآن میں {{ number_format($occurrenceCount) }} بار آیا ہے۔</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($occurrences->count())
            <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                <div class="space-y-2">
                    <p class="text-sm font-medium uppercase tracking-[0.25em] text-[#0e7c66]">Occurrences</p>
                    <h2 class="text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">مقامات</h2>
                    <p class="text-sm text-[#8d7d65]" dir="rtl" lang="ur">نیچے ان تمام مقامات کی فہرست ہے جہاں یہ لفظ آیا ہے۔ ہر بٹن پر سورۃ، آیت اور لفظ کی پوزیشن دی گئی ہے۔</p>
                </div>

                <div class="mt-5 flex flex-wrap gap-2" dir="rtl">
                    @foreach ($occurrences as $occurrence)
                        @if ($occurrence->ayah && $occurrence->ayah->surah)
                            <a
                                href="{{ route('ayahs.show', [$occurrence->ayah->surah, $occurrence->ayah]) }}"
                                class="rounded-full border border-[#d8c49f] bg-[#f7f1e8] px-4 py-2 text-sm text-[#5f4a28] shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66] hover:bg-[#eef8f4] hover:text-[#0e7c66]"
                            >
                                {{ $occurrence->surah_number }}:{{ $occurrence->ayah_number }}
                                <span class="text-[#9b8b72]">({{ $occurrence->position }})</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div id="word-notes-section" class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="space-y-2">
                <p class="text-sm font-medium uppercase tracking-[0.25em] text-[#0e7c66]">Personal Notes</p>
                <h2 class="text-2xl font-semibold text-stone-900" dir="rtl" lang="ur">ذاتی نوٹس</h2>
            </div>

            <form method="POST" action="{{ route('notes.word.store', $word) }}" class="mt-4 space-y-4">
                @csrf
                <textarea
                    name="note"
                    rows="5"
                    class="w-full rounded-[1.4rem] border border-[#d8c9b0] bg-[#fffdf8] px-4 py-3 text-sm leading-7 text-stone-800 shadow-inner focus:border-[#0d8a6e] focus:outline-none"
                    placeholder="Write your personal note for this word..."
                >{{ old('note', $wordNote) }}</textarea>

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
        </div>

        <div class="space-y-4">
            <div class="space-y-1">
                <h2 class="text-xl font-semibold text-stone-900">Morphology Segments</h2>
                <p class="text-sm text-stone-500">All imported morphology rows for this word in segment order.</p>
            </div>

            <div class="space-y-4">
                @foreach ($segmentRows as $segment)
                    @php($morphology = $segment['morphology'])
                    @php($analysis = $segment['analysis'])
                    <article class="rounded-[2rem] border px-6 py-5 shadow-sm {{ $segment['card_classes'] }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-stone-400">Segment {{ $morphology->segment_number ?: $loop->iteration }}</p>
                                <p @class([
                                    'mt-2 inline-flex rounded-full px-3 py-1 text-sm font-medium',
                                    $segment['chip_classes'],
                                ])>{{ $morphology->pos_tag }}</p>
                                <p class="mt-2 text-sm font-medium text-stone-900">{{ $analysis['english'] }}</p>
                                <p class="mt-1 text-sm text-stone-600" dir="rtl" lang="ur">{{ $analysis['urdu'] }}</p>
                                <p class="mt-1 text-right text-sm font-semibold text-emerald-700" dir="rtl" lang="ar">{{ $analysis['arabic'] }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $analysis['details'] }}</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Lemma') }}</p>
                                    <p class="mt-1 text-right text-stone-900" dir="rtl" lang="ar">{{ $analysis['lemma'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Root') }}</p>
                                    <p class="mt-1 text-right text-stone-900" dir="rtl" lang="ar">{{ $analysis['root'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Gender') }}</p>
                                    <p class="mt-1 text-stone-900">{{ \App\Support\BilingualGrammarService::abbreviation($morphology->gender) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Case') }}</p>
                                    <p class="mt-1 text-stone-900">{{ \App\Support\BilingualGrammarService::abbreviation($morphology->case_type) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Arabic Grammar') }}</p>
                                    <p class="mt-1 text-right text-stone-900" dir="rtl" lang="ar">{{ $analysis['arabic'] }}</p>
                                </div>
                            </div>
                        </div>

                        @if (! empty($analysis['attributes']))
                            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($analysis['attributes'] as $attribute)
                                    <div class="rounded-2xl bg-stone-50 px-4 py-3">
                                        <dt class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label($attribute['label']) }}</dt>
                                        <dd class="mt-1 text-stone-900">{{ $attribute['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <div class="mt-6 rounded-2xl bg-stone-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-stone-400">{{ \App\Support\BilingualGrammarService::label('Raw Features') }}</p>
                            <p class="mt-2 break-words text-sm leading-7 text-stone-700">{{ $morphology->raw_features ?: 'N/A' }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <script>
            function setWordCopyStatus(message) {
                const status = document.getElementById('word-copy-status');
                if (!status) return;
                status.textContent = message;
                status.classList.remove('hidden');
                clearTimeout(window.__wordCopyStatusTimeout);
                window.__wordCopyStatusTimeout = setTimeout(() => status.classList.add('hidden'), 2200);
            }

            async function copyWordTextValue(value, message) {
                try {
                    await navigator.clipboard.writeText(value);
                    setWordCopyStatus(message);
                } catch (error) {
                    setWordCopyStatus('Copy failed');
                }
            }

            function copyWordArabic() {
                copyWordTextValue(@json($displayArabic ?: $word->form ?: 'N/A'), 'Word copied');
            }

            function copyWordReference() {
                copyWordTextValue(@json('Surah ' . $word->surah_number . ', Ayah ' . $word->ayah_number . ', Position ' . $word->position), 'Reference copied');
            }

            function copyWordLink() {
                copyWordTextValue(window.location.href, 'Page link copied');
            }

            async function shareWord() {
                const payload = {
                    title: @json('Word ' . $word->id),
                    text: @json(($displayArabic ?: $word->form ?: 'Word') . ' - Surah ' . $word->surah_number . ', Ayah ' . $word->ayah_number . ', Position ' . $word->position),
                    url: window.location.href,
                };

                if (navigator.share) {
                    try {
                        await navigator.share(payload);
                        setWordCopyStatus('Share sheet opened');
                        return;
                    } catch (error) {
                        if (error && error.name === 'AbortError') return;
                    }
                }

                copyWordLink();
            }
        </script>
        <details class="fixed right-4 top-1/2 z-40 hidden -translate-y-1/2 md:block">
            <summary class="flex h-12 w-12 cursor-pointer list-none items-center justify-center rounded-full bg-[#0e7c66] text-xl font-semibold text-white shadow-[0_14px_28px_rgba(14,124,102,0.32)]">
                <span aria-hidden="true">+</span>
            </summary>
            <div class="mt-3 w-56 rounded-[1.4rem] border border-[#d7c8ae] bg-[#fffaf2]/95 p-3 shadow-[0_18px_40px_rgba(88,67,29,0.16)] backdrop-blur">
                <div class="space-y-2 text-sm">
                <a href="{{ route('favorites.index') }}" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Favorites</a>
                <a href="{{ route('notes.index') }}" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Notes</a>
                <form method="POST" action="{{ route('favorites.word.toggle', $word) }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-xl border px-3 py-2 text-left transition {{ $isFavoriteWord ? 'border-[#0e7c66] bg-[#eef8f4] text-[#0e7c66]' : 'border-[#e2d5bf] bg-[#f7f1e8] text-[#5f4a28] hover:border-[#0e7c66] hover:text-[#0e7c66]' }}">
                        {{ $isFavoriteWord ? 'Remove Favorite' : 'Add Favorite' }}
                    </button>
                </form>
                <a href="#word-notes-section" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Add Note</a>
                <a href="#word-overview-section" class="block rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Top Section</a>
                <button type="button" onclick="copyWordLink()" class="block w-full rounded-xl border border-[#e2d5bf] bg-[#f7f1e8] px-3 py-2 text-left text-[#5f4a28] transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Copy Link</button>
                <button type="button" onclick="shareWord()" class="block w-full rounded-xl border border-[#bde6d7] bg-[#eef8f4] px-3 py-2 text-left text-[#0e7c66] transition hover:bg-[#e5f4ee]">Share Word</button>
                </div>
            </div>
        </details>
        <div class="fixed inset-x-4 bottom-4 z-40 rounded-[1.4rem] border border-[#d7c8ae] bg-[#fffaf2]/95 p-2 shadow-[0_18px_40px_rgba(88,67,29,0.16)] backdrop-blur md:hidden">
            <div class="grid grid-cols-4 gap-2">
                <form method="POST" action="{{ route('favorites.word.toggle', $word) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl px-3 py-2 text-xs font-semibold {{ $isFavoriteWord ? 'bg-[#eef8f4] text-[#0e7c66]' : 'bg-[#f7f1e8] text-[#5f4a28]' }}">
                        Fav
                    </button>
                </form>
                <a href="{{ route('notes.index') }}" class="rounded-xl bg-[#f7f1e8] px-3 py-2 text-center text-xs font-semibold text-[#5f4a28]">Notes</a>
                <button type="button" onclick="copyWordLink()" class="rounded-xl bg-[#f7f1e8] px-3 py-2 text-xs font-semibold text-[#5f4a28]">Copy</button>
                <button type="button" onclick="shareWord()" class="rounded-xl bg-[#eef8f4] px-3 py-2 text-xs font-semibold text-[#0e7c66]">Share</button>
            </div>
        </div>
    </section>
</x-layouts.app>
