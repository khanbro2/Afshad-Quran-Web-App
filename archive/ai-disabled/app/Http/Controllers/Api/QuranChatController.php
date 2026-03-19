<?php

namespace App\Http\Controllers\Api;

use App\AI\Services\QuranChatService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\QuranChatRequest;
use App\Models\QuranChatLog;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class QuranChatController extends Controller
{
    public function ask(QuranChatRequest $request, QuranChatService $service): JsonResponse
    {
        $requestId = (string) str()->uuid();
        $dto = $request->toDto();

        try {
            $response = $service->ask($dto);

            QuranChatLog::create([
                'request_id' => $requestId,
                'question' => $dto->question,
                'language' => $dto->language,
                'answer' => $response->answer,
                'status' => 'success',
                'matched_ayah_count' => count($response->matchedAyahs),
                'matched_word_count' => count($response->matchedWords),
                'matched_theme_count' => count($response->matchedThemes),
                'meta' => [
                    'cached' => $response->cached,
                    'refused' => $response->refused,
                    'safety_note' => $response->safetyNote,
                    'matched_ayahs' => array_slice($response->matchedAyahs, 0, 10),
                    'matched_words' => array_slice($response->matchedWords, 0, 10),
                    'matched_themes' => array_slice($response->matchedThemes, 0, 10),
                    'sources_used' => $response->sourcesUsed,
                ],
            ]);

            return response()->json([
                'request_id' => $requestId,
                ...$response->toArray(),
            ]);
        } catch (RuntimeException $exception) {
            QuranChatLog::create([
                'request_id' => $requestId,
                'question' => $dto->question,
                'language' => $dto->language,
                'answer' => null,
                'status' => 'provider_unavailable',
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'AI service unavailable',
                'code' => 'ai_provider_unavailable',
            ], 503);
        } catch (Throwable $exception) {
            QuranChatLog::create([
                'request_id' => $requestId,
                'question' => $dto->question,
                'language' => $dto->language,
                'answer' => null,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            report($exception);

            return response()->json([
                'message' => 'Unable to generate a grounded Quran answer right now.',
                'code' => 'ai_request_failed',
            ], 500);
        }
    }
}
