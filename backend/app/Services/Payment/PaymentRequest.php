<?php

namespace App\Services\Payment;

/**
 * Żądanie utworzenia transakcji płatności.
 */
class PaymentRequest
{
    public function __construct(
        public readonly string $sessionId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $description,
        public readonly string $email,
        public readonly string $returnUrl,
    ) {}
}
