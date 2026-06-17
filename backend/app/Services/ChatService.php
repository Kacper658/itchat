<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orkiestruje pełny cykl wiadomości:
 *   walidacja limitów → AI provider → zapis wiadomości → naliczenie kosztu.
 */
class ChatService
{
    /** Koszt marży pobierany per ticket (poza kosztem tokenów). */
    private const TICKET_MARKUP = 0.50;

    public function __construct(
        private AIManager $ai,
        private PlanValidator $validator,
        private BillingService $billing,
    ) {}

    /**
     * Obsłuż wiadomość zalogowanego użytkownika.
     * @throws RuntimeException gdy limit / saldo / błąd AI.
     */
    public function handleUserMessage(User $user, ?int $conversationId, string $content): Message
    {
        $plan = $this->resolvePlan($user);

        // --- Walidacja limitów planu ---
        $check = $this->validator->validate($user, $plan, $content);
        if (!$check['ok']) {
            throw new RuntimeException($check['reason'], 429);
        }

        // --- Sprawdzenie salda (per-ticket) ---
        if ($plan->isPerTicket()) {
            $this->ensureTicketBalance($user, $plan);
        }

        $conversation = $this->resolveConversation($user, $conversationId);

        return DB::transaction(function () use ($user, $plan, $conversation, $content) {
            // Zapis wiadomości użytkownika
            $userMsg = Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'user',
                'content'         => $content,
            ]);

            // Kontekst + system prompt
            $messages = $this->buildContext($conversation);

            try {
                $ai = $this->ai->default()->chat($messages, [
                    'max_tokens' => $plan->max_response_length,
                    'language'   => $user->language,
                ]);
            } catch (RuntimeException $e) {
                Log::error('AI call failed', ['user' => $user->id, 'err' => $e->getMessage()]);
                throw $e;
            }

            // Zapis odpowiedzi asystenta (z tokenami i kosztem)
            $assistantMsg = Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'content'         => $ai->content,
                'prompt_tokens'   => $ai->promptTokens,
                'completion_tokens' => $ai->completionTokens,
                'cost'            => $ai->cost,
            ]);

            // Bump liczników
            $this->validator->incrementUsage($user);
            $this->validator->addTokens($user, $ai->totalTokens());

            // Naliczenie kosztu
            $this->applyBilling($user, $plan, $ai, $assistantMsg);

            // Auto-tytuł pierwszej wiadomości
            if ($conversation->title === 'Nowa rozmowa') {
                $conversation->update(['title' => mb_substr($content, 0, 60)]);
            }

            return $assistantMsg;
        });
    }

    /**
     * Wiadomość gościa — woła AI bez zapisu do DB (gość nie ma konta).
     */
    public function handleGuestMessage(array $history, string $content): AIResponse
    {
        $messages = array_merge(
            [['role' => 'system', 'content' => config('ai.system_prompt')]],
            $history,
            [['role' => 'user', 'content' => $content]]
        );

        return $this->ai->default()->chat($messages, [
            'max_tokens' => 1000,
        ]);
    }

    // ---------------------------------------------------------------
    //  Pomocnicze
    // ---------------------------------------------------------------

    private function resolveConversation(User $user, ?int $id): Conversation
    {
        if ($id) {
            return $user->conversations()->findOrFail($id);
        }
        return $user->conversations()->create(['title' => 'Nowa rozmowa']);
    }

    private function resolvePlan(User $user): Plan
    {
        $sub = $user->activeSubscription;
        if ($sub && $sub->plan) {
            return $sub->plan;
        }
        return Plan::where('slug', 'free')->firstOrFail();
    }

    private function buildContext(Conversation $conversation): array
    {
        $context = $conversation->aiContext(20);
        return array_merge(
            [['role' => 'system', 'content' => config('ai.system_prompt')]],
            $context
        );
    }

    /** Dla planu per-ticket: gdy messages_since_charge >= tickets_per_charge → pobierz opłatę. */
    private function applyBilling(User $user, Plan $plan, AIResponse $ai, Message $msg): void
    {
        // Koszt tokenów (zawsze księgowany jako chat_cost)
        if ($ai->cost > 0) {
            try {
                $this->billing->charge($user, $ai->cost, 'chat_cost', [
                    'message_id'  => $msg->id,
                    'description' => 'Koszt tokenów AI',
                ]);
            } catch (RuntimeException $e) {
                // Brak salda na pokrycie kosztu tokenów → zapisujemy dług jako info
                Log::warning('Cannot charge AI cost (insufficient balance)', [
                    'user' => $user->id, 'cost' => $ai->cost,
                ]);
            }
        }

        // Per-ticket: co N wiadomości pobieramy opłatę planu
        if ($plan->isPerTicket() && $user->messages_since_charge >= $plan->tickets_per_charge) {
            $ticketCost = (float) $plan->price + self::TICKET_MARKUP;
            $this->billing->charge($user, $ticketCost, 'chat_cost', [
                'description' => "Opłata za pakiet {$plan->tickets_per_charge} wiadomości ({$plan->name})",
            ]);
            $user->update(['messages_since_charge' => 0]);
        }
    }

    /** Upewnij się, że użytkownik per-ticket ma saldo na najbliższy ticket. */
    private function ensureTicketBalance(User $user, Plan $plan): void
    {
        if ($user->messages_since_charge < $plan->tickets_per_charge) {
            return; // jeszcze w ramach opłaconego pakietu
        }
        $needed = (float) $plan->price + self::TICKET_MARKUP;
        if (!$this->billing->hasBalance($user, $needed)) {
            throw new RuntimeException('Insufficient balance for ticket', 402);
        }
    }
}
