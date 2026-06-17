<?php

namespace App\Services\Payment;

interface PaymentProviderInterface
{
    /** Zarejestruj transakcję u operatora, zwróć URL do przekierowania. */
    public function createTransaction(PaymentRequest $request): PaymentRedirect;

    /** Zweryfikuj powiadomienie (webhook) od operatora. */
    public function verifyNotification(array $payload): bool;

    /** Pobierz szczegóły transakcji po id operatora (opcjonalnie). */
    public function getTransaction(string $providerTransactionId): ?array;

    public function name(): string;
}
