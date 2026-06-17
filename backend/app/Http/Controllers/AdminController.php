<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /** Dashboard — statystyki zbiorcze. */
    public function stats(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subMonth();
        $to   = $request->date('to')?->endOfDay() ?? now();

        $revenue = Transaction::where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', 'paid')
            ->sum('amount');

        $aiCost = Message::sum('cost');

        $deposits = Transaction::where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', 'paid')->sum('amount');
        $outflows = abs(Transaction::whereIn('type', [
            Transaction::TYPE_CHAT_COST,
            Transaction::TYPE_SUBSCRIPTION_FEE,
            Transaction::TYPE_REFUND,
        ])->sum('amount'));

        return response()->json([
            'users' => [
                'total'    => User::count(),
                'active_30d' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
                'new'      => User::whereBetween('created_at', [$from, $to])->count(),
            ],
            'conversations' => DB::table('conversations')->whereBetween('created_at', [$from, $to])->count(),
            'messages'      => DB::table('messages')->whereBetween('created_at', [$from, $to])->count(),
            'revenue'       => round($revenue, 2),
            'ai_cost'       => round($aiCost, 2),
            'margin'        => round($revenue - $aiCost, 2),
            'wallets' => [
                'total_balance' => round(DB::table('wallets')->sum('balance'), 2),
                'deposits_in'   => round($deposits, 2),
                'outflows_out'  => round($outflows, 2),
            ],
        ]);
    }

    /** Lista klientów. */
    public function users(Request $request)
    {
        $query = User::with('wallet', 'activeSubscription.plan')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'ilike', "%{$s}%")
                  ->orWhere('email', 'ilike', "%{$s}%"))
            ->latest();

        return response()->json($query->paginate(25));
    }

    /** Edycja klienta (blokada, rola). */
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'is_blocked' => ['sometimes', 'boolean'],
            'role'       => ['sometimes', 'in:user,admin'],
        ]);

        $user->update($data);
        return response()->json($user->fresh()->load('wallet'));
    }

    /** Doładowanie bonus przez admina. */
    public function bonus(Request $request, User $user)
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $tx = app(\App\Services\BillingService::class)
            ->credit($user, (float) $data['amount'], Transaction::TYPE_BONUS, [
                'description' => $data['description'] ?? 'Bonus od administratora',
                'status'      => 'paid',
            ]);

        return response()->json([
            'message' => 'Bonus dodany.',
            'balance' => $user->wallet->fresh()->balance,
            'transaction' => $tx,
        ]);
    }

    /** Wszystkie transakcje. */
    public function transactions(Request $request)
    {
        $query = Transaction::with('user:id,name,email')
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest();

        return response()->json($query->paginate(50));
    }

    /** Lista planów. */
    public function plans()
    {
        return response()->json(Plan::orderBy('sort_order')->get());
    }

    /** Edycja limitów planu. */
    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:255'],
            'price'              => ['sometimes', 'numeric', 'min:0'],
            'max_messages_day'   => ['sometimes', 'integer', 'min:0'],
            'max_tokens_month'   => ['sometimes', 'integer', 'min:0'],
            'max_prompt_length'  => ['sometimes', 'integer', 'min:1'],
            'max_response_length'=> ['sometimes', 'integer', 'min:1'],
            'tickets_per_charge' => ['sometimes', 'integer', 'min:0'],
            'is_active'          => ['sometimes', 'boolean'],
        ]);

        $plan->update($data);
        return response()->json($plan->fresh());
    }

    /**
     * Eksport pytań użytkowników — ANONIMOWO.
     * Zwraca: message, language, created_at. BEZ email/ip/name/user_id.
     */
    public function exportMessages(Request $request): StreamedResponse
    {
        $format = $request->get('format', 'csv');
        $rows = DB::table('messages')
            ->join('users', 'messages.user_id', '=', 'users.id')  // tylko po language, nie identyfikator
            ->where('messages.role', 'user')
            ->select('messages.content', 'users.language', 'messages.created_at')
            ->orderBy('messages.created_at')
            ->get();

        $headers = ['Content-Type' => 'text/csv', 'Cache-Control' => 'no-store'];

        if ($format === 'json') {
            return response()->stream(function () use ($rows) {
                echo $rows->map(fn ($r) => [
                    'message'    => $r->content,
                    'language'   => $r->language,
                    'created_at' => $r->created_at,
                ])->toJson(JSON_PRETTY_PRINT);
            }, 200, ['Content-Type' => 'application/json']);
        }

        return response()->stream(function () use ($rows) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['message', 'language', 'created_at']);
            foreach ($rows as $r) {
                fputcsv($fh, [$r->content, $r->language, $r->created_at]);
            }
            fclose($fh);
        }, 200, $headers);
    }
}
