<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Przelewy24 — główny operator płatności (MVP).
 *
 * Przepływ REST API:
 *   1. POST /transaction/register  → sessionId + token
 *   2. Przekierowanie na https://(sandbox.)secure.przelewy24.pl/trnRequest/{token}
 *   3. Webhook POST /transaction/verify (weryfikacja sumy CRC / sign)
 *
 * Pełna implementacja zgodna z dokumentacją P24 REST API v1.
 */
class Przelewy24Provider implements PaymentProviderInterface
{
    private array $cfg;

    public function __construct()
    {
        $env = config('services.przelewy24.env', 'sandbox');
        $this->cfg = [
            'merchant_id' => config('services.przelewy24.merchant_id'),
            'pos_id'      => config('services.przelewy24.pos_id'),
            'crc'         => config('services.przelewy24.crc'),
            'api_key'     => config('services.przelewy24.api_key'),
            'base_url'    => $env === 'live'
                ? 'https://secure.przelewy24.pl'
                : 'https://sandbox.przelewy24.pl',
        ];
    }

    public function name(): string
    {
        return 'przelewy24';
    }

    public function createTransaction(PaymentRequest $request): PaymentRedirect
    {
        $sign = hash('sha384',
            json_encode([
                'sessionId'  => $request->sessionId,
                'merchantId' => (int) $this->cfg['merchant_id'],
                'amount'     => (int) round($request->amount * 100),
                'currency'   => $request->currency,
                'crc'        => $this->cfg['crc'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $payload = [
            'merchantId'  => (int) $this->cfg['merchant_id'],
            'posId'       => (int) $this->cfg['pos_id'],
            'sessionId'   => $request->sessionId,
            'amount'      => (int) round($request->amount * 100),
            'currency'    => $request->currency,
            'description' => $request->description,
            'email'       => $request->email,
            'urlReturn'   => $request->returnUrl,
            'urlStatus'   => rtrim(config('app.url'), '/') . '/api/webhooks/przelewy24',
            'sign'        => $sign,
        ];

        $resp = Http::withBasicAuth($this->cfg['pos_id'], $this->cfg['api_key'])
            ->post("{$this->cfg['base_url']}/api/v1/transaction/register", $payload);

        if (!$resp->successful()) {
            Log::error('P24 register failed', ['body' => $resp->body()]);
            throw new \RuntimeException('Nie udało się zainicjować płatności Przelewy24.');
        }

        $data = $resp->json('data');
        $token = $data['token'];

        return new PaymentRedirect(
            redirectUrl: "{$this->cfg['base_url']}/trnRequest/{$token}",
            providerTransactionId: $request->sessionId,
        );
    }

    public function verifyNotification(array $payload): bool
    {
        // Weryfikacja sumy kontrolnej z webhooka
        $expected = hash('sha384',
            json_encode([
                'sessionId'  => $payload['sessionId'] ?? '',
                'merchantId' => (int) ($payload['merchantId'] ?? $this->cfg['merchant_id']),
                'amount'     => (int) ($payload['amount'] ?? 0),
                'originAmount' => (int) ($payload['originAmount'] ?? 0),
                'currency'   => $payload['currency'] ?? '',
                'orderId'    => (int) ($payload['orderId'] ?? 0),
                'crc'        => $this->cfg['crc'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return hash_equals($expected, $payload['sign'] ?? '');
    }

    public function getTransaction(string $providerTransactionId): ?array
    {
        $resp = Http::withBasicAuth($this->cfg['pos_id'], $this->cfg['api_key'])
            ->get("{$this->cfg['base_url']}/api/v1/transaction/by/sessionId/{$providerTransactionId}");

        return $resp->successful() ? $resp->json('data') : null;
    }
}
