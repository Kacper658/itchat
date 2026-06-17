<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dostawca Ollama — model lokalny.
 * Włączenie: AI_PROVIDER=ollama w .env. Koszt = 0 (infra własna).
 */
class OllamaProvider implements AIProviderInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = config('ai.providers.ollama');
    }

    public function name(): string
    {
        return 'ollama';
    }

    public function defaultModel(): string
    {
        return $this->config['model'];
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? $this->config['model'];

        // Ollama API: POST /api/chat
        $response = Http::timeout($this->config['timeout'])
            ->post("{$this->config['base_url']}/api/chat", [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
                'options'  => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'num_predict'  => $options['max_tokens'] ?? 2048,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Ollama API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Błąd lokalnego modelu AI. Spróbuj ponownie.');
        }

        $data  = $response->json();
        $content = trim($data['message']['content'] ?? '');

        $promptTokens     = $data['prompt_eval_count'] ?? 0;
        $completionTokens = $data['eval_count'] ?? 0;

        return new AIResponse(
            content: $content,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: 0.0, // model lokalny
            model: $model,
        );
    }
}
