<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use App\Services\GuestChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chat,
        private GuestChatService $guest,
    ) {}

    /** --- Endpoint gościa (nie wymaga konta) --- */
    public function guestMessage(Request $request)
    {
        $data = $request->validate([
            'content'       => ['required', 'string', 'max:4000'],
            'history'       => ['nullable', 'array'],
            'history.*.role'    => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string'],
        ]);

        $result = $this->guest->handle($data['history'] ?? [], $data['content']);

        if ($result['blocked']) {
            return response()->json([
                'blocked' => true,
                'reason'  => $result['reason'],
                'message' => 'Osiągnąłeś limit darmowych wiadomości. Załóż konto, aby kontynuować.',
            ], 429);
        }

        /** @var \App\Services\AI\AIResponse $ai */
        $ai = $result['response'];

        return response()->json([
            'blocked'  => false,
            'message'  => [
                'role'    => 'assistant',
                'content' => $ai->content,
            ],
        ]);
    }

    /** --- Endpoint zalogowanego użytkownika --- */
    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'content'          => ['required', 'string', 'max:32000'],
        ]);

        try {
            $message = $this->chat->handleUserMessage($user, $data['conversation_id'], $data['content']);
        } catch (RuntimeException $e) {
            return $this->mapException($e);
        }

        return response()->json([
            'conversation_id' => $message->conversation_id,
            'message'         => $message->only(['id', 'role', 'content']),
        });
    }

    public function conversations(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->latest()
            ->paginate(50);

        return response()->json($conversations);
    }

    public function storeConversation(Request $request)
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:255']]);

        $conversation = $request->user()->conversations()->create([
            'title' => $data['title'] ?? 'Nowa rozmowa',
            'model' => config('ai.providers.deepseek.model'),
        ]);

        return response()->json($conversation, 201);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        return response()->json(
            $conversation->messages()->select(['id', 'role', 'content', 'created_at'])->get()
        );
    }

    public function destroyConversation(Request $request, Conversation $conversation)
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();

        return response()->json(['message' => 'Rozmowa usunięta.']);
    }

    private function mapException(RuntimeException $e)
    {
        $code = (int) $e->getCode();
        $reason = $e->getMessage();

        $map = [
            402 => ['message' => 'Brak środków na koncie. Opłać rozmowę, aby kontynuować.', 'http' => 402],
            429 => ['message' => $this->limitMessage($reason), 'http' => 429],
        ];

        $resolved = $map[$code] ?? ['message' => $reason ?: 'Wystąpił błąd.', 'http' => 500];

        return response()->json([
            'message' => $resolved['message'],
            'reason'  => $reason,
        ], $resolved['http']);
    }

    private function limitMessage(string $reason): string
    {
        return match ($reason) {
            'prompt_too_long'    => 'Pytanie jest za długie dla Twojego planu.',
            'max_messages_day'   => 'Osiągnąłeś dzienny limit wiadomości.',
            'max_tokens_month'   => 'Osiągnąłeś miesięczny limit tokenów.',
            'guest_limit_reached'=> 'Osiągnąłeś limit darmowych wiadomości.',
            default              => 'Osiągnąłeś limit planu.',
        };
    }
}
