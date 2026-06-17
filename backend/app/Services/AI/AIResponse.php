<?php

namespace App\Services\AI;

/**
 * Wynik wywołania dostawcy AI.
 */
class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly float $cost = 0.0,
        public readonly string $model = '',
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
