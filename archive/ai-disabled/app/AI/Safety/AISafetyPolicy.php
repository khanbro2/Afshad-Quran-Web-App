<?php

namespace App\AI\Safety;

class AISafetyPolicy
{
    /**
     * @return array{blocked:bool,message:?string}
     */
    public function inspect(?string $question): array
    {
        $question = mb_strtolower(trim((string) $question));

        if ($question === '') {
            return ['blocked' => false, 'message' => null];
        }

        foreach (config('ai_assistant.safety.blocked_patterns', []) as $pattern) {
            if (str_contains($question, mb_strtolower($pattern))) {
                return [
                    'blocked' => true,
                    'message' => 'This assistant cannot provide fatwas, halal/haram rulings, or final sectarian/legal judgments. It can still help explain the ayah from available sources.',
                ];
            }
        }

        return ['blocked' => false, 'message' => null];
    }
}
