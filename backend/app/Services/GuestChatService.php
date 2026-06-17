<?php

namespace App\Services;

use App\Services\AI\AIResponse;

/**
 * Logika chatu gościa — limit 2 wiadomości (przed paywallem).
 *
 * Frontend trzyma historię w LocalStorage i wysyła ją z każdym zapytaniem.
 * Tu liczymy ile wiadomości user-side zawiera historia.
 */
class GuestChatService
{
    public const MAX_GUEST_MESSAGES = 2;

    public function __construct(
        private ChatService $chat,
    ) {}

    /**
     * @param array $history  wiadomości z LocalStorage [{role, content}, ...]
     * @return array{blocked: bool, response?: AIResponse, reason?: string}
     */
    public function handle(array $history, string $content): array
    {
        $userMessages = $this->countUserMessages($history);

        // Po 2 wiadomościach user-side → blokada (3. nie zostaje wysłana)
        if ($userMessages >= self::MAX_GUEST_MESSAGES) {
            return [
                'blocked' => true,
                'reason'  => 'guest_limit_reached',
            ];
        }

        $response = $this->chat->handleGuestMessage($history, $content);

        return [
            'blocked'  => false,
            'response' => $response,
        ];
    }

    private function countUserMessages(array $history): int
    {
        return collect($history)
            ->filter(fn (array $m) => ($m['role'] ?? '') === 'user')
            ->count();
    }
}
