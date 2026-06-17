<?php

namespace App\Services;

/**
 * Geolokalizacja: IP → kraj → język + waluta.
 * Używa bazy MaxMind GeoLite2 (jeśli dostępna) lub fallback.
 */
class GeoService
{
    public function detect(?string $ip = null): array
    {
        $ip = $ip ?: request()->ip();
        $country = $this->resolveCountry($ip);

        $map = config("currency.country_map.{$country}");
        $fallback = config('currency.fallback');

        return [
            'ip'       => $ip,           // nie jest nigdy zapisywane w bazie (RODO)
            'country'  => $country,
            'language' => $map['language'] ?? $fallback['language'],
            'currency' => $map['currency'] ?? $fallback['currency'],
        ];
    }

    /**
     * Rozwiąż kod kraju z IP.
     * Produkcja: MaxMind GeoLite2 (biblioteka geoip2/geoip2).
     * Fallback: '—' (użyje wartości domyślnych z config).
     */
    private function resolveCountry(string $ip): ?string
    {
        // Lokalne adresy → traktuj jako PL (dewelopment)
        if (app()->environment('local') || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return 'PL';
        }

        $dbPath = config('services.geoip.db');
        if ($dbPath && file_exists($dbPath) && class_exists(\GeoIp2\Database\Reader::class)) {
            try {
                $reader = new \GeoIp2\Database\Reader($dbPath);
                return $reader->country($ip)->country->isoCode;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
