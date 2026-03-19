<?php

namespace App\AI\Prompts;

use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\SourceBundle;

class AyahExplainPrompt
{
    /**
     * @return array<int, array<string, string>>
     */
    public function build(AIRequestData $request, SourceBundle $bundle): array
    {
        $question = trim((string) $request->question);
        $notFoundMessage = (string) config('ai_assistant.not_found_message', 'Is sawal ka jawab database mein maujood nahi hai.');

        if ($question === '') {
            $question = $request->language === 'ur'
                ? 'Is ayah ki sada aur mukhtasar wazahat karein.'
                : 'Explain this ayah simply and briefly.';
        }

        $system = implode("\n", [
            'You are a Quran assistant for an internal Quran study app.',
            'Answer ONLY from the provided Quran database context.',
            'Allowed context only: ayah Arabic text, translations, tafseer, themes, broad themes, and morphology/linguistic notes.',
            'Do NOT use outside knowledge.',
            'Do NOT guess, infer unsupported claims, or fill gaps from memory.',
            'Do NOT invent references, scholars, historical details, or extra verses.',
            'Do NOT give fatwa, halal/haram rulings, or sectarian preference.',
            'If the answer is not clearly present in the provided context, reply with exactly: ' . $notFoundMessage,
            'Return only the final answer text.',
        ]);

        $user = implode("\n\n", [
            'Question: ' . $question,
            'CONTEXT:',
            $this->buildContext($bundle),
        ]);

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    protected function buildContext(SourceBundle $bundle): string
    {
        $sections = [
            'Reference: ' . $bundle->reference,
            'Ayah Text: ' . $bundle->arabicText,
        ];

        if ($bundle->translations !== []) {
            $sections[] = 'Translations:' . "\n" . collect($bundle->translations)
                ->map(fn (array $translation) => '- ' . ($translation['label'] ?? 'Translation') . ' [' . ($translation['language'] ?? 'unknown') . ']: ' . trim((string) ($translation['text'] ?? '')))
                ->filter(fn (string $line) => $line !== '- Translation [unknown]:')
                ->implode("\n");
        }

        if ($bundle->tafasir !== []) {
            $sections[] = 'Tafseer:' . "\n" . collect($bundle->tafasir)
                ->map(fn (array $tafsir) => '- ' . ($tafsir['label'] ?? 'Tafseer') . ': ' . trim((string) ($tafsir['text'] ?? '')))
                ->filter(fn (string $line) => ! str_ends_with($line, ':'))
                ->implode("\n");
        }

        $themeLines = collect($bundle->themes)
            ->map(fn (array $theme) => trim(implode(' / ', array_filter([
                $theme['english'] ?? null,
                $theme['urdu'] ?? null,
            ]))))
            ->filter()
            ->values();

        $broadThemeLines = collect($bundle->broadThemes)
            ->map(fn (array $theme) => trim(implode(' / ', array_filter([
                $theme['english'] ?? null,
                $theme['urdu'] ?? null,
            ]))))
            ->filter()
            ->values();

        if ($themeLines->isNotEmpty() || $broadThemeLines->isNotEmpty()) {
            $sections[] = 'Themes:' . "\n" . $themeLines
                ->merge($broadThemeLines->map(fn (string $theme) => 'Broad: ' . $theme))
                ->map(fn (string $theme) => '- ' . $theme)
                ->implode("\n");
        }

        if ($bundle->morphology !== []) {
            $sections[] = 'Morphology:' . "\n" . collect($bundle->morphology)
                ->map(function (array $item) {
                    $parts = array_filter([
                        $item['word'] ?? null,
                        $item['translation_urdu'] ? 'urdu: ' . $item['translation_urdu'] : null,
                        $item['translation'] ? 'english: ' . $item['translation'] : null,
                        $item['transliteration'] ? 'transliteration: ' . $item['transliteration'] : null,
                        $item['lemma'] ? 'lemma: ' . $item['lemma'] : null,
                        $item['root'] ? 'root: ' . $item['root'] : null,
                        ! empty($item['parts_of_speech']) ? 'pos: ' . implode(', ', $item['parts_of_speech']) : null,
                    ]);

                    return '- ' . implode(' | ', $parts);
                })
                ->filter(fn (string $line) => $line !== '- ')
                ->implode("\n");
        }

        return implode("\n\n", array_filter($sections));
    }
}
