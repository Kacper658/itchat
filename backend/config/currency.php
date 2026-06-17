<?php

return [

    // Waluta bazowa systemu — w niej przechowywane salda i koszty w DB
    'system' => env('SYSTEM_CURRENCY', 'PLN'),

    // Kursy względem waluty bazowej (1 jednostka obcej = X waluty bazowej)
    'rates' => [
        'PLN' => 1.0,
        'EUR' => (float) env('EUR_TO_PLN', 4.30),
        'USD' => (float) env('USD_TO_PLN', 4.00),
        'GBP' => (float) env('GBP_TO_PLN', 5.00),
    ],

    // Mapowanie kraju (ISO) → język + waluta (geolokalizacja)
    'country_map' => [
        'PL' => ['language' => 'pl', 'currency' => 'PLN'],
        'DE' => ['language' => 'de', 'currency' => 'EUR'],
        'AT' => ['language' => 'de', 'currency' => 'EUR'],
        'CH' => ['language' => 'de', 'currency' => 'EUR'],
        'FR' => ['language' => 'fr', 'currency' => 'EUR'],
        'ES' => ['language' => 'es', 'currency' => 'EUR'],
        'IT' => ['language' => 'it', 'currency' => 'EUR'],
        'NL' => ['language' => 'nl', 'currency' => 'EUR'],
        'US' => ['language' => 'en', 'currency' => 'USD'],
        'GB' => ['language' => 'en', 'currency' => 'GBP'],
        'IE' => ['language' => 'en', 'currency' => 'EUR'],
    ],

    'fallback' => [
        'language' => env('GEO_FALLBACK_LANGUAGE', 'en'),
        'currency' => env('GEO_FALLBACK_CURRENCY', 'EUR'),
    ],

    'symbols' => [
        'PLN' => 'zł',
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
    ],
];
