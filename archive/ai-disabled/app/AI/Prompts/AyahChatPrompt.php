<?php

namespace App\AI\Prompts;

use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\AyahQuestionIntentData;
use App\AI\DTOs\SourceBundle;

class AyahChatPrompt
{
    /**
     * @return array<int, array<string, string>>
     */
    public function build(AIRequestData $request, SourceBundle $bundle, AyahQuestionIntentData $intent, string $responseStyle): array
    {
        $question = trim((string) ($intent->question ?: $request->question ?: ''));
        $notFoundMessage = (string) config('ai_assistant.not_found_message');

        if ($question === '') {
            $question = $request->language === 'ur'
                ? 'Is ayah mein kya kaha gaya hai?'
                : 'Explain this ayah briefly.';
        }

        $system = implode("\n", [
            'You are a Quran Ayah Assistant.',
            'You must answer only from the provided ayah context.',
            'The ayah context may include Arabic text, translations, tafaseer, word analysis, roots, lemmas, morphology, i\'rab, themes, and tightly related ayah metadata.',
            'Do not use outside knowledge.',
            'Do not guess or invent meanings or references.',
            'Do not produce fatwa or sectarian preference.',
            'If multiple tafaseer are present, summarize them carefully and neutrally.',
            'If the user asks for references, include tafseer source names and relevant attribution from the provided data.',
            'Match the answer style to the user question: Urdu script -> Urdu script, Roman Urdu -> Roman English letters, English -> English.',
            'Divide the answer into sections using markdown headings like ## Summary, ## Translation, ## Tafseer Summary, ## Source-wise Tafseer, ## Morphology, ## Themes, ## Reference when relevant.',
            'If tafaseer are available, include a combined tafseer summary and also short source-wise tafseer summaries when relevant.',
            'Keep translations, tafseer, morphology, themes, broad themes, and references in separate sections when they are relevant.',
            'If the answer is not supported by the provided context, reply exactly: ' . $notFoundMessage,
            'Return only plain answer text.',
        ]);

        $user = implode("\n\n", [
            'Question Type: ' . $intent->type,
            'Response Style: ' . $responseStyle,
            'Question: ' . $question,
            'CONTEXT:',
            $this->buildContext($bundle, $intent),
        ]);

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    protected function buildContext(SourceBundle $bundle, AyahQuestionIntentData $intent): string
    {
        $sections = [
            'Reference: ' . $bundle->reference,
            'Surah: ' . $bundle->surahName . ' (' . $bundle->surahNumber . '), Ayah: ' . $bundle->ayahNumber,
            'Arabic: ' . $bundle->arabicText,
        ];

        if ($bundle->translations !== []) {
            $sections[] = 'Translations:' . "\n" . collect($bundle->translations)
                ->map(fn (array $translation) => '- ' . $translation['label'] . ' [' . $translation['language'] . ']: ' . trim((string) $translation['text']))
                ->implode("\n");
        }

        if ($bundle->tafasir !== []) {
            $sections[] = 'Tafaseer:' . "\n" . collect($bundle->tafasir)
                ->map(fn (array $tafsir) => '- ' . $tafsir['label'] . ': ' . trim((string) $tafsir['text']))
                ->implode("\n");
        }

        if ($bundle->themes !== [] || $bundle->broadThemes !== []) {
            $sections[] = 'Themes:' . "\n" . collect($bundle->themes)
                ->map(fn (array $theme) => '- ' . implode(' / ', array_filter([$theme['english'] ?? null, $theme['urdu'] ?? null])))
                ->merge(collect($bundle->broadThemes)->map(fn (array $theme) => '- Broad: ' . implode(' / ', array_filter([$theme['english'] ?? null, $theme['urdu'] ?? null]))))
                ->implode("\n");
        }

        if ($bundle->irab !== null && $bundle->irab !== '') {
            $sections[] = 'I\'rab: ' . $bundle->irab;
        }

        if ($bundle->morphology !== []) {
            $sections[] = 'Word Analysis:' . "\n" . collect($bundle->morphology)
                ->map(function (array $word) {
                    return '- #' . ($word['position'] ?? '?') . ' ' . ($word['word'] ?? '')
                        . ' | urdu: ' . ($word['translation_urdu'] ?? '')
                        . ' | lemma: ' . ($word['lemma'] ?? '')
                        . ' | root: ' . ($word['root'] ?? '')
                        . ' | pos: ' . implode(', ', $word['parts_of_speech'] ?? []);
                })
                ->implode("\n");
        }

        if ($intent->type === 'tafseer_comparison' && $bundle->relatedAyahs !== []) {
            $sections[] = 'Tightly Related Ayahs:' . "\n" . collect($bundle->relatedAyahs)
                ->map(fn (array $ayah) => '- ' . $ayah['reference'] . ': ' . ($ayah['translation'] ?? $ayah['arabic_text']))
                ->implode("\n");
        }

        return implode("\n\n", array_filter($sections));
    }
}
