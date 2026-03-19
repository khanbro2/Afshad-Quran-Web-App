<x-layouts.app :title="'Ask Quran | Quran Study'">
    <section class="space-y-8">
        <div class="rounded-[2rem] border border-[#d7c8ae] bg-[#fff9ef] px-6 py-6 shadow-[0_20px_44px_rgba(88,67,29,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-[#0e7c66]">Global Quran Chat</p>
                    <h1 class="text-3xl font-semibold text-stone-900 sm:text-4xl">Ask Quran</h1>
                    <p class="max-w-3xl text-sm leading-7 text-[#7d6d55]" dir="rtl" lang="ur">یہ چیٹ پورے ڈیٹا بیس میں آیات، تراجم، تفاسیر، themes، broad themes، morphology، lemma، root، POS اور occurrences کی بنیاد پر جواب دینے کی کوشش کرتی ہے۔</p>
                </div>
                <div class="inline-flex w-fit rounded-full border border-[#d6c5a4] bg-[#f7f0e4] p-1 shadow-sm">
                    <button type="button" class="quran-chat-language rounded-full bg-[#0e7c66] px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white shadow-sm" data-language="ur">Urdu</button>
                    <button type="button" class="quran-chat-language rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-[#7c5c31]" data-language="en">English</button>
                </div>
            </div>
        </div>

        <div class="rounded-[1.9rem] border border-[#d7ccb8] bg-[#fff9ef] px-5 py-5 shadow-[0_18px_34px_rgba(87,67,31,0.08),inset_0_1px_0_rgba(255,255,255,0.82)]">
            <label for="quran-chat-question" class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Ask A Question</label>
            <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    <textarea id="quran-chat-question" rows="4" maxlength="500" placeholder="Quran ke bare mein apna sawal likhein..." class="w-full rounded-[1.15rem] border border-[#d6c5a4] bg-white/90 px-4 py-3 text-sm text-[#243229] shadow-inner outline-none transition focus:border-[#0e7c66]"></textarea>
                    <p class="mt-2 text-xs text-[#8a7d69]">Misal: sabr ke bare mein Quran kya kehta hai? Ya koi lafz, theme, ya root pooch sakte hain.</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button id="quran-chat-send" type="button" class="rounded-xl border border-[#0c745d] bg-[#0e7c66] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_10px_20px_rgba(13,138,110,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0b6c59] hover:shadow-[0_14px_24px_rgba(13,138,110,0.28)]">Ask Quran</button>
                    <button id="quran-chat-clear" type="button" class="rounded-xl border border-[#d6c5a4] bg-[#fffaf1] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#7c5c31] shadow-sm transition hover:border-[#0e7c66] hover:text-[#0e7c66]">Clear</button>
                </div>
            </div>
        </div>

        <div id="quran-chat-loading" class="hidden rounded-[1.6rem] border border-[#e1d4bd] bg-[#f6efe2] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.86)]">
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-[#0e7c66]">Searching Quran Database</p>
            <div class="mt-3 h-3 w-40 animate-pulse rounded-full bg-[#d6e8df]"></div>
            <div class="mt-2 h-3 w-full animate-pulse rounded-full bg-[#eadfcd]"></div>
            <div class="mt-2 h-3 w-5/6 animate-pulse rounded-full bg-[#eadfcd]"></div>
        </div>

        <div id="quran-chat-error" class="hidden rounded-[1.15rem] border border-[#ecc7cf] bg-[#fdf2f4] px-4 py-3 text-sm leading-7 text-[#bf3458]"></div>

        <div id="quran-chat-result" class="hidden space-y-6">
            <div class="rounded-[1.6rem] border border-[#e1d4bd] bg-[#f6efe2] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.86)]">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Answer</p>
                <div id="quran-chat-answer" class="mt-3 space-y-3 text-sm leading-8 text-[#4f4332]"></div>
            </div>

            <div id="quran-chat-ayahs-wrap" class="hidden rounded-[1.6rem] border border-[#d7ccb8] bg-[#fff9ef] p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Matched Ayahs</p>
                <div id="quran-chat-ayahs" class="mt-3 grid gap-3"></div>
            </div>

            <div id="quran-chat-words-wrap" class="hidden rounded-[1.6rem] border border-[#d7ccb8] bg-[#fff9ef] p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Matched Words</p>
                <div id="quran-chat-words" class="mt-3 grid gap-3"></div>
            </div>

            <div id="quran-chat-themes-wrap" class="hidden rounded-[1.6rem] border border-[#d7ccb8] bg-[#fff9ef] p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0e7c66]">Matched Topics</p>
                <div id="quran-chat-themes" class="mt-3 grid gap-3 md:grid-cols-2"></div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const endpoint = @json(url('/api/ai/quran-chat'));
            const questionInput = document.getElementById('quran-chat-question');
            const sendButton = document.getElementById('quran-chat-send');
            const clearButton = document.getElementById('quran-chat-clear');
            const loadingBox = document.getElementById('quran-chat-loading');
            const errorBox = document.getElementById('quran-chat-error');
            const resultBox = document.getElementById('quran-chat-result');
            const answerBox = document.getElementById('quran-chat-answer');
            const ayahsWrap = document.getElementById('quran-chat-ayahs-wrap');
            const ayahsBox = document.getElementById('quran-chat-ayahs');
            const wordsWrap = document.getElementById('quran-chat-words-wrap');
            const wordsBox = document.getElementById('quran-chat-words');
            const themesWrap = document.getElementById('quran-chat-themes-wrap');
            const themesBox = document.getElementById('quran-chat-themes');
            const languageButtons = document.querySelectorAll('.quran-chat-language');

            let language = 'ur';

            function setLanguage(nextLanguage) {
                language = nextLanguage;
                languageButtons.forEach((button) => {
                    const active = button.dataset.language === nextLanguage;
                    button.classList.toggle('bg-[#0e7c66]', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('shadow-sm', active);
                    button.classList.toggle('text-[#7c5c31]', !active);
                });
            }

            function resetOutput() {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
                resultBox.classList.add('hidden');
                answerBox.innerHTML = '';
                ayahsBox.innerHTML = '';
                wordsBox.innerHTML = '';
                themesBox.innerHTML = '';
                ayahsWrap.classList.add('hidden');
                wordsWrap.classList.add('hidden');
                themesWrap.classList.add('hidden');
            }

            function setLoading(active) {
                loadingBox.classList.toggle('hidden', !active);
                sendButton.disabled = active;
                questionInput.disabled = active;
                sendButton.classList.toggle('opacity-60', active);
            }

            function renderAnswer(text) {
                String(text || '').split(/\n{2,}/).filter(Boolean).forEach((paragraph) => {
                    const node = document.createElement('p');
                    node.className = language === 'ur' ? 'text-right' : 'text-left';
                    node.setAttribute('dir', language === 'ur' ? 'rtl' : 'ltr');
                    node.setAttribute('lang', language === 'ur' ? 'ur' : 'en');
                    node.textContent = paragraph.trim();
                    answerBox.appendChild(node);
                });
            }

            function renderAyahs(items) {
                if (!Array.isArray(items) || !items.length) return;
                ayahsWrap.classList.remove('hidden');
                items.forEach((item) => {
                    const card = document.createElement('a');
                    card.href = item.url;
                    card.className = 'block rounded-[1.25rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]';
                    card.innerHTML = `
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0e7c66]">${item.reference}</p>
                        <p class="mt-2 text-right text-2xl leading-loose text-stone-900" dir="rtl" lang="ar">${item.arabic_text}</p>
                        <p class="mt-2 text-sm leading-7 text-[#5c503f]" dir="rtl" lang="ur">${item.translation || ''}</p>
                    `;
                    ayahsBox.appendChild(card);
                });
            }

            function renderWords(items) {
                if (!Array.isArray(items) || !items.length) return;
                wordsWrap.classList.remove('hidden');
                items.forEach((item) => {
                    const occurrenceLinks = Array.isArray(item.occurrences)
                        ? item.occurrences.map((occurrence) => `<a href="${occurrence.url}" class="rounded-full border border-[#d6c5a4] bg-[#fffaf1] px-3 py-1 text-xs font-semibold text-[#7c5c31] shadow-sm">${occurrence.reference}</a>`).join(' ')
                        : '';

                    const card = document.createElement('div');
                    card.className = 'rounded-[1.25rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-4 shadow-sm';
                    card.innerHTML = `
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <a href="${item.url}" class="text-right text-2xl font-semibold text-[#14231c]" dir="rtl" lang="ar">${item.arabic}</a>
                                <p class="mt-1 text-sm text-[#5c503f]">${item.translation || ''}</p>
                                <p class="mt-2 text-xs text-[#8a7d69]">Lemma: ${item.lemma || 'N/A'} | Root: ${item.root || 'N/A'} | POS: ${(item.pos || []).join(', ') || 'N/A'}</p>
                            </div>
                            <div class="rounded-full border border-[#bde6d7] bg-[#eef8f4] px-3 py-1 text-xs font-semibold text-[#0e7c66]">Occurrences: ${item.occurrence_count}</div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">${occurrenceLinks}</div>
                    `;
                    wordsBox.appendChild(card);
                });
            }

            function renderThemes(items) {
                if (!Array.isArray(items) || !items.length) return;
                themesWrap.classList.remove('hidden');
                items.forEach((item) => {
                    const card = document.createElement('a');
                    card.href = item.url;
                    card.className = 'block rounded-[1.25rem] border border-[#d6c5a4] bg-[#fffaf1] px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#0e7c66]';
                    card.innerHTML = `
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0e7c66]">${item.type === 'broad_theme' ? 'Broad Theme' : 'Theme'}</p>
                        <p class="mt-2 text-lg font-semibold text-[#14231c]">${item.title_english || ''}</p>
                        <p class="mt-1 text-right text-sm text-[#5c503f]" dir="rtl" lang="ur">${item.title_urdu || ''}</p>
                        <p class="mt-2 text-sm leading-6 text-[#7d6d55]">${item.description || ''}</p>
                        <p class="mt-3 text-xs font-semibold text-[#9b7a47]">Ayahs: ${item.ayah_count}</p>
                    `;
                    themesBox.appendChild(card);
                });
            }

            async function askQuran() {
                const question = String(questionInput.value || '').trim();
                if (!question) {
                    errorBox.classList.remove('hidden');
                    errorBox.textContent = 'Please enter a question first.';
                    return;
                }

                resetOutput();
                setLoading(true);

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ question, language }),
                    });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to generate answer right now.');
                    }

                    resultBox.classList.remove('hidden');
                    renderAnswer(payload.answer);
                    renderAyahs(payload.matched_ayahs);
                    renderWords(payload.matched_words);
                    renderThemes(payload.matched_themes);
                } catch (error) {
                    errorBox.classList.remove('hidden');
                    errorBox.textContent = error instanceof Error ? error.message : 'Unable to generate a grounded Quran answer right now.';
                } finally {
                    setLoading(false);
                }
            }

            languageButtons.forEach((button) => button.addEventListener('click', () => setLanguage(button.dataset.language || 'ur')));
            sendButton.addEventListener('click', askQuran);
            clearButton.addEventListener('click', () => {
                questionInput.value = '';
                resetOutput();
            });
            questionInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    askQuran();
                }
            });
        })();
    </script>
</x-layouts.app>
