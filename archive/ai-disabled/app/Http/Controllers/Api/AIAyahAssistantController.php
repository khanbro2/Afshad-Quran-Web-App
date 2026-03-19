<?php

namespace App\Http\Controllers\Api;

use App\AI\Services\AIOrchestrator;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\AyahExplainRequest;
use App\Models\QuranChatLog;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class AIAyahAssistantController extends Controller
{
    public function explain(AyahExplainRequest $request, AIOrchestrator $orchestrator): JsonResponse
    {
        $requestId = (string) str()->uuid();
        $dto = $request->toDto();

        try {
            $response = $orchestrator->explainAyah($dto);

            QuranChatLog::create([
                'request_id' => $requestId,
                'question' => (string) ($dto->question ?? ''),
                'language' => $dto->language,
                'answer' => $response->answer,
                'status' => 'ayah_scoped_success',
                'matched_ayah_count' => 1,
                'matched_word_count' => 0,
                'matched_theme_count' => 0,
                'meta' => [
                    'mode' => 'ayah_scoped',
                    'ayah_id' => $dto->ayahId,
                    'translation_slugs' => $dto->translationSlugs,
                    'tafsir_slugs' => $dto->tafsirSlugs,
                    'cached' => $response->cached,
                    'refused' => $response->refused,
                    'safety_note' => $response->safetyNote,
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
                'question' => (string) ($dto->question ?? ''),
                'language' => $dto->language,
                'answer' => null,
                'status' => 'ayah_scoped_provider_unavailable',
                'error_message' => $exception->getMessage(),
                'meta' => [
                    'mode' => 'ayah_scoped',
                    'ayah_id' => $dto->ayahId,
                ],
            ]);

            return response()->json([
                'message' => 'AI service unavailable',
                'code' => 'ai_provider_unavailable',
            ], 503);
        } catch (Throwable $exception) {
            QuranChatLog::create([
                'request_id' => $requestId,
                'question' => (string) ($dto->question ?? ''),
                'language' => $dto->language,
                'answer' => null,
                'status' => 'ayah_scoped_failed',
                'error_message' => $exception->getMessage(),
                'meta' => [
                    'mode' => 'ayah_scoped',
                    'ayah_id' => $dto->ayahId,
                ],
            ]);

            report($exception);

            return response()->json([
                'message' => 'Unable to generate a grounded ayah explanation right now.',
                'code' => 'ai_request_failed',
            ], 500);
        }
    }
}
