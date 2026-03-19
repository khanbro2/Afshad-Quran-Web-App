<?php

namespace App\AI\Services;

use App\AI\Contracts\AIProvider;
use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\AIResponseData;
use App\AI\DTOs\AyahQuestionIntentData;
use App\AI\DTOs\SourceBundle;
use App\AI\Prompts\AyahChatPrompt;
use App\AI\Retrieval\AyahSourceBundleBuilder;
use App\AI\Safety\AISafetyPolicy;

class AyahScopedChatService
{
    public function __construct(
        protected AyahSourceBundleBuilder $contextBuilder,
        protected AyahQuestionClassifier $questionClassifier,
        protected AyahChatPrompt $promptBuilder,
        protected AISafetyPolicy $safetyPolicy,
        protected AIProvider $provider,
    ) {
    }

    public function answer(AIRequestData $request): AIResponseData
    {
        $notFoundMessage = (string) config('ai_assistant.not_found_message');
        $responseStyle = $this->detectResponseStyle((string) $request->question);
        $safety = $this->safetyPolicy->inspect($request->question);

        if ($safety['blocked']) {
            return new AIResponseData(
                answer: (string) $safety['message'],
                keyPoints: [],
                sourcesUsed: [],
                refused: true,
                safetyNote: (string) $safety['message'],
            );
        }

        $bundle = $this->contextBuilder->build($request);

        if (! $this->contextBuilder->hasUsableContext($bundle)) {
            return new AIResponseData(answer: $notFoundMessage, keyPoints: [], sourcesUsed: []);
        }

        $intent = $this->questionClassifier->detect($request);

        return match ($intent->type) {
            'unsupported_query' => new AIResponseData(answer: $notFoundMessage, keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle)),
            'translation_query' => $this->translationResponse($bundle, $intent, $responseStyle),
            'reference_request' => $this->referenceResponse($bundle, $responseStyle),
            'word_meaning' => $this->wordResponse($bundle, $intent, $responseStyle),
            'morphology_query' => $this->morphologyResponse($bundle, $intent, $responseStyle),
            'tafseer_query' => $this->tafseerSummaryResponse($request, $bundle, $intent, $responseStyle),
            'tafseer_comparison' => $this->tafseerComparisonResponse($request, $bundle, $intent, $responseStyle),
            default => $this->summarizedResponse($request, $bundle, $intent, $responseStyle),
        };
    }

    protected function translationResponse(SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        $translations = collect($bundle->translations);

        if ($intent->requestedSource !== null) {
            $translations = $translations->filter(fn (array $translation) => str_contains(mb_strtolower($translation['label']), mb_strtolower($intent->requestedSource)));
        } elseif (str_contains(mb_strtolower((string) $intent->question), 'english')) {
            $translations = $translations->where('language', 'en');
        } else {
            $translations = $translations->where('language', 'ur');
        }

        $selected = $translations->values();

        if ($selected->isEmpty()) {
            return new AIResponseData(answer: (string) config('ai_assistant.not_found_message'), keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
        }

        $answer = $this->formatSections([
            $this->sectionLabel('translation', $responseStyle) => $selected->map(
                fn (array $translation) => '- ' . $translation['label'] . ': ' . trim((string) $translation['text'])
            )->implode("\n"),
        ]);

        return new AIResponseData(answer: $answer, keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
    }

    protected function referenceResponse(SourceBundle $bundle, string $responseStyle): AIResponseData
    {
        $sections = [
            $this->sectionLabel('reference', $responseStyle) => $bundle->reference,
        ];

        if ($bundle->translations !== []) {
            $sections[$this->sectionLabel('translation_sources', $responseStyle)] = collect($bundle->translations)
                ->map(fn (array $translation) => '- ' . $translation['label'])
                ->implode("\n");
        }

        if ($bundle->tafasir !== []) {
            $sections[$this->sectionLabel('tafseer_sources', $responseStyle)] = collect($bundle->tafasir)
                ->map(fn (array $tafsir) => '- ' . $tafsir['label'])
                ->implode("\n");
        }

        return new AIResponseData(
            answer: $this->formatSections($sections),
            keyPoints: [],
            sourcesUsed: $this->collectSourcesUsed($bundle),
        );
    }

    protected function wordResponse(SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        $word = $this->findRequestedWord($bundle, $intent);

        if ($word === null) {
            return new AIResponseData(answer: (string) config('ai_assistant.not_found_message'), keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
        }

        $answer = $this->formatSections([
            $this->sectionLabel('selected_word', $responseStyle) => (string) ($word['word'] ?? 'N/A'),
            $this->sectionLabel('word_analysis', $responseStyle) => implode("\n", [
                '- Lemma: ' . ($word['lemma'] ?: 'N/A'),
                '- Root: ' . ($word['root'] ?: 'N/A'),
                '- POS: ' . implode(', ', $word['parts_of_speech'] ?? []),
                '- Urdu: ' . ($word['translation_urdu'] ?: 'N/A'),
                '- English: ' . ($word['translation'] ?: 'N/A'),
            ]),
        ]);

        return new AIResponseData(answer: $answer, keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
    }

    protected function morphologyResponse(SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        $word = $this->findRequestedWord($bundle, $intent);

        if ($word !== null) {
            $morphologyText = collect($word['morphology'] ?? [])
                ->map(fn (array $item) => implode(', ', array_filter([
                    $item['pos'] ?? null,
                    $item['lemma'] ?? null,
                    $item['root'] ?? null,
                    $item['summary'] ?? null,
                ])))
                ->filter()
                ->implode(' | ');

            $answer = $morphologyText !== ''
                ? $this->formatSections([
                    $this->sectionLabel('selected_word', $responseStyle) => (string) ($word['word'] ?? 'N/A'),
                    $this->sectionLabel('morphology', $responseStyle) => $morphologyText,
                ])
                : (string) config('ai_assistant.not_found_message');

            return new AIResponseData(answer: $answer, keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
        }

        if ($bundle->irab !== null && trim($bundle->irab) !== '') {
            $answer = $this->formatSections([
                $this->sectionLabel('irab', $responseStyle) => $bundle->irab,
            ]);

            return new AIResponseData(answer: $answer, keyPoints: [], sourcesUsed: $this->collectSourcesUsed($bundle));
        }

        $wordLines = collect($bundle->morphology)
            ->take(6)
            ->map(fn (array $word) => '- ' . ($word['word'] ?? '') . ' | lemma: ' . ($word['lemma'] ?? 'N/A') . ' | root: ' . ($word['root'] ?? 'N/A') . ' | pos: ' . implode(', ', $word['parts_of_speech'] ?? []))
            ->implode("\n");

        return new AIResponseData(
            answer: $wordLines !== '' ? $this->formatSections([$this->sectionLabel('morphology', $responseStyle) => $wordLines]) : (string) config('ai_assistant.not_found_message'),
            keyPoints: [],
            sourcesUsed: $this->collectSourcesUsed($bundle),
        );
    }

    protected function summarizedResponse(AIRequestData $request, SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        $messages = $this->promptBuilder->build($request, $bundle, $intent, $responseStyle);
        try {
            $generated = $this->provider->generate($messages);
            $answer = trim((string) ($generated['answer'] ?? ''));
        } catch (\RuntimeException) {
            $answer = '';
        }

        if ($answer === '') {
            $answer = (string) config('ai_assistant.not_found_message');
        }

        return new AIResponseData(
            answer: $answer,
            keyPoints: [],
            sourcesUsed: $this->collectSourcesUsed($bundle),
        );
    }

    protected function tafseerSummaryResponse(AIRequestData $request, SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        if ($bundle->tafasir === []) {
            return new AIResponseData(
                answer: (string) config('ai_assistant.not_found_message'),
                keyPoints: [],
                sourcesUsed: $this->collectSourcesUsed($bundle),
            );
        }

        $llmResponse = $this->summarizedResponse($request, $bundle, $intent, $responseStyle);

        if ($llmResponse->answer !== (string) config('ai_assistant.not_found_message')) {
            return $llmResponse;
        }

        $sections = [
            $this->sectionLabel('tafseer_summary', $responseStyle) => $this->buildCombinedTafseerSummary($bundle, $responseStyle),
            $this->sectionLabel('tafseer_breakdown', $responseStyle) => $this->buildSourceWiseTafseerSummary($bundle),
        ];

        return new AIResponseData(
            answer: $this->formatSections($sections),
            keyPoints: [],
            sourcesUsed: $this->collectSourcesUsed($bundle),
        );
    }

    protected function tafseerComparisonResponse(AIRequestData $request, SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): AIResponseData
    {
        if ($bundle->tafasir === []) {
            return new AIResponseData(
                answer: (string) config('ai_assistant.not_found_message'),
                keyPoints: [],
                sourcesUsed: $this->collectSourcesUsed($bundle),
            );
        }

        $llmResponse = $this->summarizedResponse($request, $bundle, $intent, $responseStyle);

        if ($llmResponse->answer !== (string) config('ai_assistant.not_found_message')) {
            return $llmResponse;
        }

        $sections = [
            $this->sectionLabel('tafseer_breakdown', $responseStyle) => $this->buildSourceWiseTafseerSummary($bundle),
            $this->sectionLabel('common_point', $responseStyle) => $this->buildCombinedTafseerSummary($bundle, $responseStyle),
        ];

        return new AIResponseData(
            answer: $this->formatSections($sections),
            keyPoints: [],
            sourcesUsed: $this->collectSourcesUsed($bundle),
        );
    }

    protected function detectResponseStyle(string $question): string
    {
        $question = trim($question);

        if ($question !== '' && preg_match('/[\x{0600}-\x{06FF}]/u', $question) === 1) {
            return 'ur';
        }

        $romanUrduSignals = ['ayah', 'lafz', 'tafseer', 'tafsir', 'samjhao', 'matlab', 'batao', 'kya', 'mein', 'main', 'ka', 'ki', 'ke', 'roshni'];
        $lower = mb_strtolower($question);

        foreach ($romanUrduSignals as $signal) {
            if (str_contains($lower, $signal)) {
                return 'roman';
            }
        }

        return 'en';
    }

    /**
     * @param  array<string, string>  $sections
     */
    protected function formatSections(array $sections): string
    {
        return collect($sections)
            ->filter(fn ($content) => trim((string) $content) !== '')
            ->map(fn ($content, $heading) => '## ' . $heading . "\n" . trim((string) $content))
            ->implode("\n\n");
    }

    protected function sectionLabel(string $key, string $style): string
    {
        $labels = [
            'ur' => [
                'summary' => 'خلاصہ',
                'translation' => 'ترجمہ',
                'translation_sources' => 'ترجمہ کے ذرائع',
                'tafseer_summary' => 'تفاسیر کا خلاصہ',
                'tafseer_sources' => 'تفاسیر کے ذرائع',
                'tafseer_breakdown' => 'ہر تفسیر الگ',
                'common_point' => 'مشترک نکتہ',
                'selected_word' => 'منتخب لفظ',
                'word_analysis' => 'لفظی تجزیہ',
                'morphology' => 'صرفی و نحوی تجزیہ',
                'irab' => 'اعراب',
                'themes' => 'تھیمز اور موضوعات',
                'broad_themes' => 'وسیع موضوعات',
                'occurrences' => 'Occurrences',
                'reference' => 'حوالہ',
            ],
            'roman' => [
                'summary' => 'Khulasa',
                'translation' => 'Translation',
                'translation_sources' => 'Translation Sources',
                'tafseer_summary' => 'Tafaseer ka Khulasa',
                'tafseer_sources' => 'Tafaseer Sources',
                'tafseer_breakdown' => 'Har Tafsir Alag',
                'common_point' => 'Common Point',
                'selected_word' => 'Selected Word',
                'word_analysis' => 'Word Analysis',
                'morphology' => 'Morphology',
                'irab' => 'Irab',
                'themes' => 'Themes',
                'broad_themes' => 'Broad Themes',
                'occurrences' => 'Occurrences',
                'reference' => 'Reference',
            ],
            'en' => [
                'summary' => 'Summary',
                'translation' => 'Translation',
                'translation_sources' => 'Translation Sources',
                'tafseer_summary' => 'Tafseer Summary',
                'tafseer_sources' => 'Tafseer Sources',
                'tafseer_breakdown' => 'Source-wise Tafseer',
                'common_point' => 'Common Point',
                'selected_word' => 'Selected Word',
                'word_analysis' => 'Word Analysis',
                'morphology' => 'Morphology',
                'irab' => 'I\'rab',
                'themes' => 'Themes',
                'broad_themes' => 'Broad Themes',
                'occurrences' => 'Occurrences',
                'reference' => 'Reference',
            ],
        ];

        return $labels[$style][$key] ?? $labels['en'][$key] ?? $key;
    }

    protected function buildCombinedTafseerSummary(SourceBundle $bundle, string $responseStyle): string
    {
        $translation = $bundle->translations[0]['text'] ?? null;
        $themeSummary = collect($bundle->themes)
            ->pluck('urdu')
            ->filter()
            ->implode('، ');

        $base = match ($responseStyle) {
            'ur' => 'دستیاب تفاسیر کے مطابق اس آیت میں ',
            'roman' => 'Available tafaseer ke mutabiq is ayah mein ',
            default => 'Based on the available tafaseer for this ayah, ',
        };

        $tail = match ($responseStyle) {
            'ur' => 'کا بیان آتا ہے۔',
            'roman' => 'ka bayan aata hai.',
            default => 'this ayah conveys this core meaning.',
        };

        $content = $translation
            ? trim((string) $translation)
            : trim((string) ($bundle->tafasir[0]['text'] ?? ''));

        $content = $this->firstMeaningfulSentence($content);

        if ($themeSummary !== '') {
            $content .= match ($responseStyle) {
                'ur' => ' متعلقہ موضوعات: ' . $themeSummary,
                'roman' => ' Related themes: ' . $themeSummary,
                default => ' Related themes: ' . $themeSummary,
            };
        }

        return trim($base . $content . ' ' . $tail);
    }

    protected function buildSourceWiseTafseerSummary(SourceBundle $bundle): string
    {
        return collect($bundle->tafasir)
            ->map(function (array $tafsir) {
                $summary = $this->firstMeaningfulSentence((string) ($tafsir['text'] ?? ''));

                return '- ' . ($tafsir['label'] ?? 'Tafseer') . ': ' . $summary;
            })
            ->implode("\n");
    }

    protected function firstMeaningfulSentence(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '') {
            return '';
        }

        $parts = preg_split('/(?<=[\.\!\؟\?])\s+/u', $text) ?: [];
        $first = trim((string) ($parts[0] ?? ''));

        if ($first === '') {
            $first = mb_substr($text, 0, 220);
        }

        return $first;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findRequestedWord(SourceBundle $bundle, AyahQuestionIntentData $intent): ?array
    {
        $question = mb_strtolower((string) ($intent->question ?? ''));

        if ($intent->requestedWord !== null) {
            $question = mb_strtolower($intent->requestedWord);
        }

        foreach ($bundle->morphology as $word) {
            $haystack = mb_strtolower(implode(' ', array_filter([
                $word['word'] ?? null,
                $word['translation'] ?? null,
                $word['translation_urdu'] ?? null,
                $word['transliteration'] ?? null,
                $word['lemma'] ?? null,
                $word['root'] ?? null,
            ])));

            if ($question !== '' && str_contains($haystack, $question)) {
                return $word;
            }
        }

        return $bundle->morphology[0] ?? null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function collectSourcesUsed(SourceBundle $bundle): array
    {
        $sources = [['type' => 'ayah', 'label' => $bundle->reference]];

        foreach ($bundle->translations as $translation) {
            $sources[] = ['type' => 'translation', 'label' => (string) $translation['label']];
        }

        foreach ($bundle->tafasir as $tafsir) {
            $sources[] = ['type' => 'tafsir', 'label' => (string) $tafsir['label']];
        }

        if ($bundle->morphology !== []) {
            $sources[] = ['type' => 'morphology', 'label' => 'Word analysis and morphology'];
        }

        if ($bundle->themes !== [] || $bundle->broadThemes !== []) {
            $sources[] = ['type' => 'theme', 'label' => 'Ayah themes and broad themes'];
        }

        return $sources;
    }
}
