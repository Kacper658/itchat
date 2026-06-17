<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\BillingService;
use App\Services\GeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private GeoService $geo,
        private BillingService $billing,
    ) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'email'                  => ['required', 'email', 'max:255', 'unique:users'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'],
            'language'               => ['nullable', 'string', 'max:5'],
            'currency'               => ['nullable', 'string', 'max:3'],
            'guest_chat'             => ['nullable', 'array'],
            'guest_chat.title'       => ['nullable', 'string', 'max:255'],
            'guest_chat.messages'    => ['nullable', 'array'],
            'guest_chat.messages.*.role'    => ['required', 'in:user,assistant'],
            'guest_chat.messages.*.content' => ['required', 'string'],
        ]);

        // Geo fallback jeśli nie podano jawnie
        $geo = $this->geo->detect();
        $language = $data['language'] ?? session('locale', $geo['language']);
        $currency = $data['currency'] ?? session('currency', $geo['currency']);

        $user = DB::transaction(function () use ($data, $language, $currency) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'language' => $language,
                'currency' => $currency,
            ]);

            $this->billing->ensureWallet($user);

            // --- Przepisanie rozmowy gościa na konto ---
            if (!empty($data['guest_chat']['messages'])) {
                $conversation = $user->conversations()->create([
                    'title' => $data['guest_chat']['title'] ?? 'Rozmowa z gościa',
                    'model' => config('ai.providers.deepseek.model'),
                ]);
                foreach ($data['guest_chat']['messages'] as $msg) {
                    $conversation->messages()->create([
                        'role'    => $msg['role'],
                        'content' => $msg['content'],
                    ]);
                }
            }

            return $user;
        });

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user'  => $user->fresh()->load('wallet'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Podane dane logowania są nieprawidłowe.'],
            ]);
        }

        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'email' => ['Konto zostało zablokowane. Skontaktuj się z obsługą.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user'  => $user->load('wallet', 'activeSubscription.plan'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Wylogowano.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('wallet', 'activeSubscription.plan'),
        ]);
    }
}
