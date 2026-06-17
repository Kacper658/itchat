<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustawia język aplikacji na podstawie:
 *   1. ustawień użytkownika (zalogowany)
 *   2. sesji (wykryty geolokalizacyjnie)
 *   3. nagłówka Accept-Language
 *   4. domyślnego 'en'
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = match (true) {
            $request->user()?->language => $request->user()->language,
            session()->has('locale')   => session('locale'),
            default                    => substr($request->header('Accept-Language', 'en'), 0, 2),
        };

        app()->setLocale(in_array($locale, ['pl', 'en', 'de', 'fr', 'es']) ? $locale : 'en');

        return $next($request);
    }
}
