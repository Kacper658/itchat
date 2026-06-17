<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dostawca DeepSeek (OpenAI-compatible API).
 * Domyślny dostawca. Zamienialny na OllamaProvider bez zmiany reszty aplikacji.
 */
class DeepSeekProvider implements AIProviderInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = config('ai.providers.deepseek');
    }

    public function name(): string
    {
        return 'deepseek';
    }

    public function defaultModel(): string
    {
        return $this->config['model'];
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $model      = $options['model'] ?? $this->config['model'];
        $maxTokens  = $options['max_tokens'] ?? config('ai.max_tokens', 2048);
        $temperature = $options['temperature'] ?? 0.7;

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'])
            ->post("{$this->config['base_url']}/v1/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (!$response->successful()) {
            Log::error('DeepSeek API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'Błąd dostawcy AI (' . $response->status() . '). Spróbuj ponownie.'
            );
        }

        $data  = $response->json();
        $usage = $data['usage'] ?? [];

        $promptTokens     = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;
        $content          = trim($data['choices'][0]['message']['content'] ?? '');

        return new AIResponse(
            content: $content,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            model: $model,
        );
    }

    /**
     * cost = (prompt_tokens/1M × price_in) + (completion_tokens/1M × price_out)
     * Zwracamy koszt w walucie bazowej (PLN). Ceny w USD → przeliczenie.
     */
    private function calculateCost(int $promptTokens, int $completionTokens): float
    {
        $usdCost = ($promptTokens / 1_000_000 * $this->config['price_input'])
                 + ($completionTokens / 1_000_000 * $this->config['price_output']);

        $usdToPln = (float) config('currency.rates.USD', 4.0);
        return round($usdCost * $usdToPln, 6);
    }
}
