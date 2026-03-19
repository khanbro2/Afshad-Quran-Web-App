<?php

namespace App\AI\Services;

use App\AI\DTOs\AIRequestData;
use App\AI\DTOs\AIResponseData;
use Illuminate\Support\Facades\Cache;

class AIOrchestrator
{
    public function __construct(
        protected AyahScopedChatService $ayahScopedChatService,
    ) {
    }

    public function explainAyah(AIRequestData $request): AIResponseData
    {
        $cacheKey = 'ai_assistant:ayah_explain:' . $request->cacheKey();
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return new AIResponseData(
                answer: (string) ($cached['answer'] ?? ''),
                keyPoints: array_values($cached['key_points'] ?? []),
                sourcesUsed: array_values($cached['sources_used'] ?? []),
                cached: true,
            );
        }

        $response = $this->ayahScopedChatService->answer($request);

        Cache::put($cacheKey, $response->toArray(), now()->addSeconds(config('ai_assistant.cache_ttl_seconds', 21600)));

        return $response;
    }
}
