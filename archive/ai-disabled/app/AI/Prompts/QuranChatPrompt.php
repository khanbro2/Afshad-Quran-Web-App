<?php

namespace App\AI\Prompts;

use App\AI\DTOs\QuranChatRequestData;

class QuranChatPrompt
{
    /**
     * @return array<int, array<string, string>>
     */
    public function build(QuranChatRequestData $request, string $context): array
    {
        $notFoundMessage = (string) config('ai_assistant.not_found_message', 'Is sawal ka jawab database mein wazeh tor par maujood nahi hai.');

        $system = implode("\n", [
            'You are a Quran database assistant for an internal Quran study app.',
            'Answer ONLY from the provided Quran database context.',
            'Allowed evidence only: ayah Arabic text, translations, tafseer excerpts, themes, broad themes, morphology, lemma, root, POS, and word occurrences present in the context.',
            'Do NOT use outside knowledge.',
            'Do NOT guess, infer beyond the context, or invent references.',
            'Do NOT give fatwa, halal/haram rulings, or sectarian preference.',
            'If the answer is not clearly and directly supported by the provided context, reply with exactly: ' . $notFoundMessage,
            'Answer in the same language requested by the user.',
            'Keep the answer concise, evidence-based, and plain text only.',
        ]);

        $user = implode("\n\n", [
            'Language: ' . $request->language,
            'Question: ' . trim($request->question),
            'CONTEXT:',
            $context,
        ]);

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
