<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentManager $payments,
        private BillingService $billing,
    ) {}

    /**
     * Webhook Przelewy24 — weryfikowany, idempotentny.
     * P24 wysyła POST z sessionId, orderId, amount, currency, sign.
     */
    public function przelewy24(Request $request)
    {
        $payload = $request->all();
        $provider = $this->payments->provider('przelewy24');

        if (!$provider->verifyNotification($payload)) {
            Log::warning('P24 webhook invalid signature', ['payload' => $payload]);
            return response('INVALID SIGNATURE', 400);
        }

        $status = $payload['status'] ?? null;
        $sessionId = $payload['sessionId'] ?? null;

        // P24: status 5 = opłacona, 2 = błąd
        if (((int) $status) === 5) {
            $tx = $this->billing->confirmDeposit($sessionId, 'przelewy24');
            Log::info('P24 deposit confirmed', ['tx' => $tx?->id]);
        } elseif (((int) $status) === 2) {
            Log::warning('P24 payment failed', ['session' => $sessionId]);
        }

        return response('OK');
    }
}
