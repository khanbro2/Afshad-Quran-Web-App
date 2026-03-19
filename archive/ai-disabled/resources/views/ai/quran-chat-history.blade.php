<x-layouts.app :title="'Quran Chat History | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#0e7c66]">Recent Chat Logs</p>
                    <h1 class="text-3xl font-semibold text-stone-900 sm:text-4xl">Quran Chat History</h1>
                    <p class="max-w-3xl text-sm leading-7 text-[#7d6d55]" dir="rtl" lang="ur">یہاں recent سوالات اور ان کے جوابات محفوظ ہوں گے تاکہ ہم دیکھ سکیں لوگ کیا پوچھ رہے ہیں اور AI کہاں غلطی کر رہی ہے۔</p>
                </div>
                <a href="{{ route('ai.quran-chat') }}" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Ask New Question</a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($logs as $log)
                <article class="rounded-[1.7rem] border border-[#d7ccb8] bg-[#fff9ef] px-5 py-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-3 py-1 text-xs font-semibold text-[#0e7c66]">{{ strtoupper($log->language) }}</span>
                                <span class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $log->status }}</span>
                                <span class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1 text-xs font-semibold text-[#7c5c31]">{{ $log->created_at?->format('Y-m-d h:i A') }}</span>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Question</p>
                            <p class="text-sm leading-7 text-[#243229]">{{ $log->question }}</p>
                        </div>
                        <div class="text-xs text-[#8a7d69]">
                            <p>Request ID</p>
                            <p class="mt-1 font-mono">{{ $log->request_id }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <div class="rounded-[1.2rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-3 text-sm text-[#5c503f]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Matched Ayahs</p>
                            <p class="mt-2">{{ $log->matched_ayah_count }}</p>
                        </div>
                        <div class="rounded-[1.2rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-3 text-sm text-[#5c503f]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Matched Words</p>
                            <p class="mt-2">{{ $log->matched_word_count }}</p>
                        </div>
                        <div class="rounded-[1.2rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-3 text-sm text-[#5c503f]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#9b7a47]">Matched Themes</p>
                            <p class="mt-2">{{ $log->matched_theme_count }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[1.25rem] border border-[#e1d4bd] bg-[#f6efe2] px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Answer</p>
                        <p class="mt-2 text-sm leading-7 text-[#4f4332]">{{ $log->answer ?: ($log->error_message ?: 'No answer saved.') }}</p>
                    </div>
                </article>
            @empty
                <div class="rounded-[1.7rem] border border-[#d7ccb8] bg-[#fff9ef] px-5 py-8 text-center text-sm text-[#7d6d55] shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
                    Abhi tak koi chat history save nahi hui.
                </div>
            @endforelse
        </div>

        <div>
            {{ $logs->links() }}
        </div>
    </section>
</x-layouts.app>
