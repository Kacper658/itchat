<?php

namespace App\Services\Payment;

use InvalidArgumentException;

/**
 * Fabryka dostawców płatności.
 * Rozwiązuje providera wg konfiguracji — zmiana operatora bez przebudowy.
 */
class PaymentManager
{
    private array $providers = [];
    private static array $map = [
        'przelewy24' => Przelewy24Provider::class,
        // 'payu'   => PayUProvider::class,    // przyszłość
        // 'stripe' => StripeProvider::class,  // przyszłość
    ];

    public function provider(?string $name = null): PaymentProviderInterface
    {
        $name = $name ?: config('services.payment.default', 'przelewy24');

        if (!isset($this->providers[$name])) {
            if (!isset(self::$map[$name])) {
                throw new InvalidArgumentException("Nieznany operator płatności: {$name}");
            }
            $class = self::$map[$name];
            $this->providers[$name] = new $class();
        }

        return $this->providers[$name];
    }
}
