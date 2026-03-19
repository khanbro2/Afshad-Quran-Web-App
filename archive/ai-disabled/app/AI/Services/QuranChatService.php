<?php

namespace App\AI\Services;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\Handlers\CountQuestionHandler;
use App\AI\QuranChat\Handlers\ListQuestionHandler;
use App\AI\QuranChat\Handlers\SummaryQuestionHandler;
use App\AI\QuranChat\Handlers\TopicQuestionHandler;
use App\AI\QuranChat\Handlers\UnsupportedQuestionHandler;
use App\AI\QuranChat\Handlers\WordQuestionHandler;
use App\AI\QuranChat\Services\QuranQueryService;
use App\AI\QuranChat\Services\QuestionIntentService;
use App\AI\Safety\AISafetyPolicy;
use Illuminate\Support\Facades\Cache;

class QuranChatService
{
    public function __construct(
        protected QuranQueryService $queryService,
        protected AISafetyPolicy $safetyPolicy,
        protected QuestionIntentService $intentService,
        protected CountQuestionHandler $countHandler,
        protected ListQuestionHandler $listHandler,
        protected WordQuestionHandler $wordHandler,
        protected TopicQuestionHandler $topicHandler,
        protected SummaryQuestionHandler $summaryHandler,
        protected UnsupportedQuestionHandler $unsupportedHandler,
    ) {
    }

    public function ask(QuranChatRequestData $request): QuranChatResponseData
    {
        $notFoundMessage = (string) config('ai_assistant.not_found_message');
        $safety = $this->safetyPolicy->inspect($request->question);

        if ($safety['blocked']) {
            return new QuranChatResponseData(
                answer: (string) $safety['message'],
                matchedAyahs: [],
                matchedWords: [],
                matchedThemes: [],
                sourcesUsed: [],
                refused: true,
                safetyNote: (string) $safety['message'],
            );
        }

        $cacheKey = 'ai_assistant:quran_chat:' . $request->cacheKey();
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return new QuranChatResponseData(
                answer: (string) ($cached['answer'] ?? ''),
                matchedAyahs: array_values($cached['matched_ayahs'] ?? []),
                matchedWords: array_values($cached['matched_words'] ?? []),
                matchedThemes: array_values($cached['matched_themes'] ?? []),
                sourcesUsed: array_values($cached['sources_used'] ?? []),
                cached: true,
            );
        }

        $intent = $this->intentService->detect($request);
        $evidence = $this->queryService->buildEvidence($request, $intent);

        if ($intent->type !== 'translation_query' && $intent->type !== 'tafseer_query' && $intent->type !== 'unsupported_or_weak_query' && $intent->type !== 'fallback_unknown') {
            $hasUsableEvidence = ($evidence['matched_ayahs'] ?? []) !== []
                || ($evidence['matched_words'] ?? []) !== []
                || ($evidence['matched_themes'] ?? []) !== [];

            if (! $hasUsableEvidence) {
                return new QuranChatResponseData(
                    answer: $notFoundMessage,
                    matchedAyahs: [],
                    matchedWords: [],
                    matchedThemes: [],
                    sourcesUsed: [],
                );
            }
        }

        foreach ($this->handlers() as $handler) {
            if ($handler->supports($intent)) {
                $response = $handler->handle($request, $intent, $evidence);
                Cache::put($cacheKey, $response->toArray(), now()->addSeconds(config('ai_assistant.cache_ttl_seconds', 21600)));

                return $response;
            }
        }

        $response = new QuranChatResponseData(
            answer: $notFoundMessage,
            matchedAyahs: array_values($evidence['matched_ayahs'] ?? []),
            matchedWords: array_values($evidence['matched_words'] ?? []),
            matchedThemes: array_values($evidence['matched_themes'] ?? []),
            sourcesUsed: array_values($evidence['sources_used'] ?? []),
        );

        Cache::put($cacheKey, $response->toArray(), now()->addSeconds(config('ai_assistant.cache_ttl_seconds', 21600)));

        return $response;
    }

    /**
     * @return array<int, object>
     */
    protected function handlers(): array
    {
        return [
            $this->unsupportedHandler,
            $this->countHandler,
            $this->listHandler,
            $this->wordHandler,
            $this->topicHandler,
            $this->summaryHandler,
        ];
    }
}
