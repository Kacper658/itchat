<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\PaymentRequest;
use App\Services\Payment\PaymentRedirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Jedyna brama do operacji na portfelu.
 * Saldo nigdy nie spada poniżej 0. Transakcje = append-only log.
 */
class BillingService
{
    public function __construct(
        private PaymentManager $payments,
    ) {}

    public function ensureWallet(User $user): Wallet
    {
        return $user->wallet ?? Wallet::create([
            'user_id'  => $user->id,
            'balance'  => 0,
            'currency' => $user->currency ?: config('currency.system'),
        ]);
    }

    /**
     * Pobierz opłatę z portfela (atomowo, z blokadą wiersza).
     * @throws RuntimeException gdy saldo < amount
     */
    public function charge(User $user, float $amount, string $type, array $meta = []): Transaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Kwota obciążenia musi być dodatnia.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta) {
            $wallet = Wallet::lockForUpdate()->findOrFail($this->ensureWallet($user)->id);

            if ((float) $wallet->balance < $amount) {
                throw new RuntimeException('Insufficient balance', 402);
            }

            $wallet->balance = bcsub($wallet->balance, $amount, 2);
            $wallet->save();

            return Transaction::create([
                'wallet_id'  => $wallet->id,
                'user_id'    => $user->id,
                'type'       => $type,
                'amount'     => -$amount,
                'currency'   => $wallet->currency,
                'balance_after' => $wallet->balance,
                'status'     => 'paid',
                'provider'   => $meta['provider'] ?? 'system',
                'description'=> $meta['description'] ?? null,
                'message_id' => $meta['message_id'] ?? null,
            ]);
        });
    }

    /**
     * Dodaj środki do portfela (wpłata / bonus / refund).
     */
    public function credit(User $user, float $amount, string $type, array $meta = []): Transaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Kwota dopisania musi być dodatnia.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta) {
            $wallet = Wallet::lockForUpdate()->findOrFail($this->ensureWallet($user)->id);

            $wallet->balance = bcadd($wallet->balance, $amount, 2);
            $wallet->save();

            return Transaction::create([
                'wallet_id'  => $wallet->id,
                'user_id'    => $user->id,
                'type'       => $type,
                'amount'     => $amount,
                'currency'   => $wallet->currency,
                'balance_after' => $wallet->balance,
                'status'     => $meta['status'] ?? 'paid',
                'provider'   => $meta['provider'] ?? 'system',
                'provider_transaction_id' => $meta['provider_transaction_id'] ?? null,
                'description'=> $meta['description'] ?? null,
            ]);
        });
    }

    /**
     * Inicjuj wpłatę przez operatora płatności.
     * Zwraca transakcję pending + URL przekierowania.
     */
    public function initiateDeposit(User $user, float $amount): array
    {
        $provider = $this->payments->provider();
        $wallet   = $this->ensureWallet($user);
        $system   = config('currency.system');
        $amountSystem = $this->toSystemCurrency($amount, $wallet->currency);

        $sessionId = Str::uuid()->toString();

        $transaction = Transaction::create([
            'wallet_id'  => $wallet->id,
            'user_id'    => $user->id,
            'type'       => Transaction::TYPE_DEPOSIT,
            'amount'     => $amountSystem,
            'currency'   => $system,
            'balance_after' => $wallet->balance,
            'status'     => 'pending',
            'provider'   => $provider->name(),
            'provider_transaction_id' => $sessionId,
            'description' => 'Wpłata ' . $amount . ' ' . $wallet->currency,
        ]);

        $redirect = $provider->createTransaction(new PaymentRequest(
            sessionId: $sessionId,
            amount: $amountSystem,
            currency: $system,
            description: $transaction->description,
            email: $user->email,
            returnUrl: config('services.przelewy24.return_url'),
        ));

        return [
            'transaction_id' => $transaction->id,
            'redirect_url'   => $redirect->redirectUrl,
        ];
    }

    /**
     * Potwierdzenie wpłaty z webhooka (idempotentne po provider_transaction_id).
     */
    public function confirmDeposit(string $providerTransactionId, string $providerName): ?Transaction
    {
        $tx = Transaction::where('provider', $providerName)
            ->where('provider_transaction_id', $providerTransactionId)
            ->first();

        if (!$tx || $tx->status === 'paid') {
            return $tx; // już obsłużone (idempotencja)
        }

        $user = User::findOrFail($tx->user_id);
        $this->credit($user, (float) $tx->amount, Transaction::TYPE_DEPOSIT, [
            'provider' => $providerName,
            'provider_transaction_id' => $providerTransactionId,
            'status'   => 'paid',
        ]);

        $tx->update(['status' => 'paid']);
        return $tx->fresh();
    }

    public function hasBalance(User $user, float $amount): bool
    {
        return (float) ($user->wallet?->balance ?? 0) >= $amount;
    }

    /** Przelicz kwotę z waluty użytkownika na walutę bazową. */
    public function toSystemCurrency(float $amount, string $fromCurrency): float
    {
        $system = config('currency.system');
        if ($fromCurrency === $system) {
            return round($amount, 2);
        }
        $rate = config("currency.rates.{$fromCurrency}", 1);
        return round($amount * $rate, 2);
    }
}
