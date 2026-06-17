<?php

namespace App\Services\Payment;

/**
 * Wynik inicjacji płatności — URL do bramki + identyfikator transakcji.
 */
class PaymentRedirect
{
    public function __construct(
        public readonly string $redirectUrl,
        public readonly string $providerTransactionId,
    ) {}
}
