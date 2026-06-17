<?php

namespace App\Services\AI;

use InvalidArgumentException;

/**
 * Fabryka dostawców AI.
 * Rozwiązuje providera na podstawie konfiguracji (AI_PROVIDER).
 */
class AIManager
{
    private array $providers = [];
    private static array $map = [
        'deepseek' => DeepSeekProvider::class,
        'ollama'   => OllamaProvider::class,
        // 'openai' => OpenAIProvider::class,  // przyszłość
    ];

    public function provider(?string $name = null): AIProviderInterface
    {
        $name = $name ?: config('ai.default', 'deepseek');

        if (!isset($this->providers[$name])) {
            if (!isset(self::$map[$name])) {
                throw new InvalidArgumentException("Nieznany dostawca AI: {$name}");
            }
            $class = self::$map[$name];
            $this->providers[$name] = new $class();
        }

        return $this->providers[$name];
    }

    public function default(): AIProviderInterface
    {
        return $this->provider();
    }
}
