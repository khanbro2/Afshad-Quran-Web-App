<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class OpenAIProvider implements AIProvider
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    public function generate(array $messages): array
    {
        $apiKey = config('ai_assistant.openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('AI provider is not configured.');
        }

        $response = $this->http->withToken($apiKey)
            ->timeout(config('ai_assistant.timeout', 30))
            ->baseUrl(config('ai_assistant.openai.base_url'))
            ->post('/chat/completions', [
                'model' => config('ai_assistant.openai.model'),
                'messages' => $messages,
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ])
            ->throw()
            ->json();

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI provider returned an empty response.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI provider returned invalid JSON.');
        }

        return [
            'answer' => (string) ($decoded['answer'] ?? ''),
            'key_points' => array_values(array_filter($decoded['key_points'] ?? [], fn ($item) => is_string($item) && $item !== '')),
        ];
    }
}
