<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Process;
use JsonException;
use RuntimeException;

class OllamaService implements AIProvider
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array{answer:string,key_points:array<int,string>}
     */
    public function generate(array $messages): array
    {
        return [
            'answer' => $this->askQuestion($this->compilePrompt($messages)),
            'key_points' => [],
        ];
    }

    public function askQuestion(string $question): string
    {
        $primaryModel = (string) config('ai_assistant.ollama.model', 'llama3');
        $fallbackModel = (string) config('ai_assistant.ollama.fallback_model', '');

        $answer = $this->askViaHttp($question, $primaryModel);

        if ($answer !== null) {
            return $answer;
        }

        if ($fallbackModel !== '' && $fallbackModel !== $primaryModel) {
            $answer = $this->askViaHttp($question, $fallbackModel);

            if ($answer !== null) {
                return $answer;
            }

            $answer = $this->askViaCli($question, $fallbackModel);

            if ($answer !== null) {
                return $answer;
            }
        }

        throw new RuntimeException('AI service unavailable');
    }

    protected function askViaHttp(string $question, string $model): ?string
    {
        try {
            $response = $this->http
                ->baseUrl(rtrim((string) config('ai_assistant.ollama.base_url'), '/'))
                ->timeout(config('ai_assistant.timeout', 30))
                ->post((string) config('ai_assistant.ollama.endpoint', '/api/chat'), [
                    'model' => $model,
                    'stream' => (bool) config('ai_assistant.ollama.stream', false),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $question,
                        ],
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException) {
            return null;
        }

        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $content = trim((string) data_get($payload, 'message.content', ''));

        return $content !== '' ? $content : null;
    }

    protected function askViaCli(string $question, string $model): ?string
    {
        if (! (bool) config('ai_assistant.ollama.cli_fallback', true)) {
            return null;
        }

        $result = Process::timeout((int) config('ai_assistant.ollama.fallback_timeout', 45))
            ->run(['ollama', 'run', $model, $question]);

        if (! $result->successful()) {
            return null;
        }

        $output = trim($result->output());

        return $output !== '' ? $this->cleanCliOutput($output) : null;
    }

    protected function cleanCliOutput(string $output): string
    {
        $output = preg_replace('/\x1b\[[0-9;?]*[A-Za-z]/', '', $output) ?? $output;
        $output = preg_replace('/[^\P{C}\n\t]/u', '', $output) ?? $output;

        return trim($output);
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     */
    protected function compilePrompt(array $messages): string
    {
        $system = collect($messages)
            ->where('role', 'system')
            ->pluck('content')
            ->filter(fn ($content) => is_string($content) && trim($content) !== '')
            ->implode("\n\n");

        $user = collect($messages)
            ->where('role', 'user')
            ->pluck('content')
            ->filter(fn ($content) => is_string($content) && trim($content) !== '')
            ->implode("\n\n");

        return trim(implode("\n\n", array_filter([
            $system !== '' ? "SYSTEM:\n{$system}" : null,
            $user !== '' ? "USER:\n{$user}" : null,
        ])));
    }
}
