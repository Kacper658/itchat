<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

/**
 * Waliduje limity planu użytkownika przed wysłaniem zapytania do AI.
 */
class PlanValidator
{
    public const OK                 = 'ok';
    public const LIMIT_PROMPT_LEN   = 'prompt_too_long';
    public const LIMIT_MESSAGES_DAY = 'max_messages_day';
    public const LIMIT_TOKENS_MONTH = 'max_tokens_month';

    /**
     * @return array{ok: bool, reason: ?string}
     */
    public function validate(User $user, Plan $plan, string $prompt): array
    {
        // 1. Długość pytania
        if (mb_strlen($prompt) > $plan->max_prompt_length) {
            return ['ok' => false, 'reason' => self::LIMIT_PROMPT_LEN];
        }

        // 2. Liczba wiadomości dziennie
        if ($this->isSameDay($user) && $user->messages_today >= $plan->max_messages_day) {
            return ['ok' => false, 'reason' => self::LIMIT_MESSAGES_DAY];
        }

        // 3. Limit tokenów miesięczny
        if ($user->tokens_this_month >= $plan->max_tokens_month) {
            return ['ok' => false, 'reason' => self::LIMIT_TOKENS_MONTH];
        }

        return ['ok' => true, 'reason' => null];
    }

    /** Bump liczników po udanej wiadomości. */
    public function incrementUsage(User $user): void
    {
        $today = now()->toDateString();

        if (!$this->isSameDay($user)) {
            $user->messages_today = 0;
            $user->messages_day = $today;
        }

        $user->messages_today++;
        $user->messages_since_charge++;
        $user->last_message_at = now();
        $user->save();
    }

    public function addTokens(User $user, int $tokens): void
    {
        $user->increment('tokens_this_month', $tokens);
    }

    private function isSameDay(User $user): bool
    {
        return $user->messages_day?->toDateString() === now()->toDateString();
    }

    /** Reset miesięcznych liczników tokenów. */
    public function resetMonthly(User $user): void
    {
        $user->update(['tokens_this_month' => 0]);
    }
}
