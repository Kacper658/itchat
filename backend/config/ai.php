<?php

return [

    'default' => env('AI_PROVIDER', 'deepseek'),

    'system_prompt' => env(
        'AI_SYSTEM_PROMPT',
        'Jesteś wirtualnym ekspertem informatycznym (IT helpdesk). '
        . 'Udzielasz konkretnych, dokładnych i bezpiecznych odpowiedzi na pytania techniczne. '
        . 'Odpowiadaj w języku użytkownika.'
    ),

    'providers' => [

        'deepseek' => [
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'api_key'  => env('DEEPSEEK_API_KEY'),
            'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            // Cena w USD za 1M tokenów
            'price_input'  => (float) env('DEEPSEEK_PRICE_INPUT', 0.27),
            'price_output' => (float) env('DEEPSEEK_PRICE_OUTPUT', 1.10),
            'timeout'      => 60,
        ],

        'ollama' => [
            'base_url' => env('OLLAMA_URL', 'http://localhost:11434'),
            'model'    => env('OLLAMA_MODEL', 'llama3'),
            // Model lokalny — koszt 0 (infra własna)
            'price_input'  => 0.0,
            'price_output' => 0.0,
            'timeout'      => 120,
        ],

        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key'  => env('OPENAI_API_KEY'),
            'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'price_input'  => 0.15,
            'price_output' => 0.60,
            'timeout'      => 60,
        ],
    ],
];
