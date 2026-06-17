<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        private BillingService $billing,
    ) {}

    public function wallet(Request $request)
    {
        $wallet = $this->billing->ensureWallet($request->user());

        return response()->json([
            'balance'  => $wallet->balance,
            'currency' => $wallet->currency,
        ]);
    }

    public function transactions(Request $request)
    {
        $txs = $request->user()
            ->transactions()
            ->latest()
            ->paginate(50);

        return response()->json($txs);
    }

    public function plans()
    {
        return response()->json(
            Plan::where('is_active', true)->orderBy('sort_order')->get()
        );
    }

    /** Inicjuj wpłatę → zwraca redirect_url do bramki Przelewy24. */
    public function deposit(Request $request)
    {
        $data = $request->validate([
            'amount'   => ['required', 'numeric', 'min:1', 'max:10000'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $user = $request->user();
        $this->billing->ensureWallet($user);

        $result = $this->billing->initiateDeposit($user, (float) $data['amount']);

        return response()->json($result);
    }

    /** Wybór / zmiana planu subskrypcji. */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($data['plan_id']);

        // Dezaktywacja starej subskrypcji
        Subscription::where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->update(['status' => Subscription::STATUS_CANCELLED]);

        $now = now();
        $subscription = Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'status'     => $plan->isFree()
                ? Subscription::STATUS_ACTIVE
                : Subscription::STATUS_PENDING_PAYMENT,
            'started_at' => $now,
            'expires_at' => $plan->isMonthly() ? $now->copy()->addMonth() : null,
            'auto_renew' => $plan->isMonthly(),
        ]);

        // Plan free → aktywny natychmiast
        if ($plan->isFree()) {
            return response()->json([
                'subscription' => $subscription->load('plan'),
                'message' => 'Aktywowano plan Free.',
            ]);
        }

        // Plan płatny → wymaga opłaty
        return response()->json([
            'subscription' => $subscription->load('plan'),
            'requires_payment' => true,
            'message' => 'Opłać subskrypcję, aby ją aktywować.',
        ]);
    }

    public function subscription(Request $request)
    {
        return response()->json([
            'subscription' => $request->user()->activeSubscription?->load('plan'),
        ]);
    }
}
