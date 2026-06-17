<?php

namespace App\Services\AI;

/**
 * Interfejs dostawcy modelu językowego.
 * Pozwala wymieniać DeepSeek ⇄ Ollama ⇄ OpenAI bez zmiany logiki aplikacji.
 */
interface AIProviderInterface
{
    /**
     * Wyślij konwersację i odbierz odpowiedź.
     *
     * @param array $messages  [['role'=>'user'|'assistant'|'system', 'content'=>'...'], ...]
     * @param array $options   ['max_tokens', 'temperature', 'language']
     * @return AIResponse
     */
    public function chat(array $messages, array $options = []): AIResponse;

    /** Nazwa dostawcy (np. deepseek). */
    public function name(): string;

    /** Domyślny identyfikator modelu. */
    public function defaultModel(): string;
}
